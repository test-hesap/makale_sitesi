<?php
require_once 'includes/header.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

// Onay kontrolü
$currentUser = getCurrentUser();
if (!$currentUser['is_approved']) {
    header('Location: /');
    exit;
}

$pageTitle = t('add_article') . ' - ' . getSiteSetting('site_title');
$metaDescription = getCurrentLanguage() == 'en' ? 'Write and publish a new article.' : 'Yeni makale yazın ve yayınlayın.';

// TinyMCE ekle
echo '<script src="https://cdn.tiny.cloud/1/2m1i3gn0z5hsv17cjtyflqqbd9yscepyequpmg8ykbc1v3q0/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>';
echo '<script>
    tinymce.init({
        selector: "#content",
        height: 600,
        plugins: [
            "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
            "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
            "insertdatetime", "media", "table", "help", "wordcount", "emoticons", "contextmenu"
        ],
        toolbar: "undo redo | styles | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | " +
                "bullist numlist outdent indent | link image media table emoticons | " +
                "forecolor backcolor | removeformat code fullscreen help",
        menubar: "file edit view insert format tools table help",
        content_style: "body { font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6; }",
        branding: false,
        promotion: false,
        
        // Context menü ayarları - sağ tık menüsünde copy/paste için
        contextmenu: false, // TinyMCE context menüsünü devre dışı bırak
        paste_data_images: true,
        paste_as_text: false,
        image_title: true,
        automatic_uploads: true,
        file_picker_types: "image",
        images_upload_url: "/admin/api/upload-image.php",
        images_upload_handler: function (blobInfo, success, failure) {
            var xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open("POST", "/admin/api/upload-image.php");
            
            xhr.onload = function() {
                var json;
                if (xhr.status != 200) {
                    failure("HTTP Error: " + xhr.status);
                    return;
                }
                json = JSON.parse(xhr.responseText);
                if (!json || typeof json.location != "string") {
                    failure("Invalid JSON: " + xhr.responseText);
                    return;
                }
                success(json.location);
            };
            
            formData = new FormData();
            formData.append("file", blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        },
        setup: function (editor) {
            editor.on("change", function () {
                editor.save();
            });
            
            // Add event listener for right-click context menu
            editor.on("init", function() {
                editor.getBody().addEventListener("contextmenu", function(e) {
                    // Stop TinyMCE context menu, use browser default
                    e.stopPropagation();
                }, true);
            });
        }
    });
</script>';

$success = false;
$error = '';

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $categoryId = intval($_POST['category_id'] ?? 0);
    $tags = sanitizeInput($_POST['tags'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    
    if (empty($title) || empty($content)) {
        $error = getCurrentLanguage() == 'en' ? 'Title and content fields are required.' : 'Başlık ve içerik alanları zorunludur.';
    } elseif ($categoryId <= 0) {
        $error = getCurrentLanguage() == 'en' ? 'Please select a category.' : 'Lütfen bir kategori seçin.';
    } else {
        try {
            $database = new Database();
            $db = $database->pdo;
            
            // Kapak görselini yükle
            $featuredImage = '';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $featuredImage = uploadImage($_FILES['cover_image'], 'articles');
                    
                    // Yüklenen dosyanın güvenliğini doğrula
                    $fullImagePath = APP_ROOT . $featuredImage;
                    if (!validateImage($fullImagePath)) {
                        if (file_exists($fullImagePath)) {
                            unlink($fullImagePath);
                        }
                        throw new Exception(getCurrentLanguage() == 'en' ? 'The uploaded file failed security checks' : 'Yüklenen dosya güvenlik kontrollerinden geçemedi');
                    }
                } catch (Exception $e) {
                    $error = (getCurrentLanguage() == 'en' ? 'Cover image could not be uploaded: ' : 'Kapak görseli yüklenemedi: ') . $e->getMessage();
                }
            }
            
            if (empty($error)) {
            
            // Slug oluştur
            $slug = generateSlug($title);
            
            // Slug çakışması kontrolü
            $counter = 1;
            $originalSlug = $slug;
            while (true) {
                $stmt = $db->prepare("SELECT id FROM articles WHERE slug = ?");
                $stmt->execute([$slug]);
                if (!$stmt->fetch()) {
                    break;
                }
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            // Makaleyi ekle
            $stmt = $db->prepare("
                INSERT INTO articles (
                    title, slug, content, user_id, category_id, featured_image,
                    status, meta_description, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $title, $slug, $content, $currentUser['id'], 
                $categoryId, $featuredImage, $metaDescription
            ]);
            
            $articleId = $db->lastInsertId();

            // Etiketleri işle
            if (!empty($tags)) {
                $tagArray = array_map('trim', explode(',', $tags));
                foreach ($tagArray as $tagName) {
                    if (!empty($tagName)) {
                        // Önce tag'i tags tablosuna ekle veya varsa ID'sini al
                        $tagSlug = generateSlug($tagName);
                        $tagStmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), usage_count = usage_count + 1");
                        $tagStmt->execute([$tagName, $tagSlug]);
                        $tagId = $db->lastInsertId();
                        
                        // Makale-tag ilişkisini kaydet
                        $articleTagStmt = $db->prepare("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                        $articleTagStmt->execute([$articleId, $tagId]);
                    }
                }
            }
            
            // Otomatik sitemap güncellemesi
            autoGenerateSitemap();
            
            $success = true;
            }
            
        } catch (Exception $e) {
            $error = (getCurrentLanguage() == 'en' ? 'An error occurred while saving the article: ' : 'Makale kaydedilirken bir hata oluştu: ') . $e->getMessage();
        }
    }
}

// Kategorileri çek
try {
    $database = new Database();
    $db = $database->pdo;
    
    // Sadece form için ihtiyaç duyduğumuz kategori alanları
    $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Footer için ihtiyaç duyduğumuz tüm kategori bilgileri (slug dahil)
    $GLOBALS['categories'] = $db->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
    $GLOBALS['categories'] = [];
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <!-- Başlık -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <i class="fas fa-pen-fancy mr-2"></i><?= t('write_article') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                <?= t('article_submission_message') ?>
            </p>
        </div>

        <?php if ($success): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-6 py-4 rounded-lg mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-2xl mr-4"></i>
                <div>
                    <h3 class="font-semibold mb-1"><?= t('article_submitted') ?></h3>
                    <p><?= t('article_submission_message') ?></p>
                </div>
            </div>
            <div class="mt-4 flex gap-4">
                <a href="/makale_ekle" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i><?= t('write_article') ?>
                </a>
                <a href="/profile" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-user mr-2"></i><?= t('profile') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <form method="POST" action="" class="space-y-6" enctype="multipart/form-data">
                <!-- Başlık -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-heading mr-1"></i><?= t('title') ?>
                    </label>
                    <input type="text" name="title" required 
                           class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg shadow-sm 
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                                  dark:text-white placeholder-gray-400 dark:placeholder-gray-400">
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-folder mr-1"></i><?= t('category') ?>
                    </label>
                    <select name="category_id" required 
                            class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg shadow-sm 
                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                                   dark:text-white">
                        <option value=""><?= t('select_category') ?></option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Etiketler -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-tags mr-1"></i><?= t('tags') ?>
                    </label>
                    <input type="text" name="tags" placeholder="<?= t('tags_placeholder') ?>" 
                           class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg shadow-sm 
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                                  dark:text-white placeholder-gray-400 dark:placeholder-gray-400">
                </div>

                <!-- Meta Açıklama -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-info-circle mr-1"></i><?= t('meta_description') ?>
                    </label>
                    <textarea name="meta_description" rows="2" placeholder="<?= t('meta_desc_placeholder') ?>" 
                              class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg shadow-sm 
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                                     dark:text-white placeholder-gray-400 dark:placeholder-gray-400"></textarea>
                </div>

                <!-- İçerik -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-pen mr-1"></i><?= t('content') ?>
                    </label>
                    <textarea id="content" name="content" required></textarea>
                </div>

                <!-- Kapak Görseli -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-image mr-1"></i><?= t('cover_image') ?>
                    </label>
                    <div id="cover_image_upload" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                <label for="cover_image" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span><?= t('upload_file') ?></span>
                                    <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="sr-only">
                                </label>
                                <p class="pl-1"><?= t('drag_drop') ?></p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?= t('image_specs') ?>
                            </p>
                        </div>
                    </div>
                    <!-- Görsel önizlemesi -->
                    <div id="cover_image_preview" class="mt-4 hidden">
                        <div class="relative">
                            <img id="preview_image" src="" alt="<?= t('cover_image') ?>" class="w-full h-48 object-cover rounded-lg">
                            <button type="button" id="remove_cover_image" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            <i class="fas fa-check text-green-500 mr-1"></i>
                            <?= t('cover_image_selected') ?>
                        </p>
                    </div>
                </div>

                <?php if (shouldShowCaptcha('article')): ?>
                <div class="flex justify-center">
                    <?php echo getCaptchaScript(); ?>
                </div>
                <?php endif; ?>

                <!-- Gönder Butonu -->
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="window.history.back()" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-2"></i><?= t('cancel') ?>
                    </button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i><?= t('article_submit') ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Yardım Kutusu -->
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-3">
                <i class="fas fa-lightbulb mr-2"></i><?= t('tips') ?>
            </h3>
            <ul class="list-disc list-inside text-blue-800 dark:text-blue-300 space-y-2">
                <li><?= t('tip_1') ?></li>
                <li><?= t('tip_2') ?></li>
                <li><?= t('tip_3') ?></li>
                <li><?= t('tip_4') ?></li>
                <li><?= t('tip_5') ?></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const coverImageInput = document.getElementById('cover_image');
    const uploadArea = document.getElementById('cover_image_upload');
    const previewArea = document.getElementById('cover_image_preview');
    const previewImage = document.getElementById('preview_image');
    const removeButton = document.getElementById('remove_cover_image');
    const currentLang = document.documentElement.lang || 'tr';
    
    // Hata mesajları
    const errorMessages = {
        fileType: {
            tr: 'Lütfen sadece JPG, PNG, GIF veya WebP dosyası seçin.',
            en: 'Please select only JPG, PNG, GIF or WebP file.'
        },
        fileSize: {
            tr: 'Dosya boyutu 5MB\'dan küçük olmalıdır.',
            en: 'File size must be less than 5MB.'
        },
        minFileSize: {
            tr: 'Dosya çok küçük. En az 1KB olmalıdır.',
            en: 'File is too small. It must be at least 1KB.'
        },
        fileNameLength: {
            tr: 'Dosya adı çok uzun.',
            en: 'File name is too long.'
        },
        invalidChars: {
            tr: 'Dosya adında geçersiz karakterler bulunuyor.',
            en: 'File name contains invalid characters.'
        }
    };

    // Dosya seçildiğinde önizleme göster
    coverImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Dosya türünü kontrol et
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type.toLowerCase())) {
                alert(errorMessages.fileType[currentLang] || errorMessages.fileType.tr);
                e.target.value = '';
                return;
            }
            
            // Dosya boyutunu kontrol et (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert(errorMessages.fileSize[currentLang] || errorMessages.fileSize.tr);
                e.target.value = '';
                return;
            }
            
            // Minimum dosya boyutu kontrolü
            if (file.size < 1024) { // 1KB minimum
                alert(errorMessages.minFileSize[currentLang] || errorMessages.minFileSize.tr);
                e.target.value = '';
                return;
            }
            
            // Dosya adı güvenlik kontrolü
            const fileName = file.name;
            if (fileName.length > 255) {
                alert(errorMessages.fileNameLength[currentLang] || errorMessages.fileNameLength.tr);
                e.target.value = '';
                return;
            }
            
            // Tehlikeli karakterleri kontrol et
            const dangerousChars = /[<>:"\/\\|?*\x00-\x1f]/;
            if (dangerousChars.test(fileName)) {
                alert(errorMessages.invalidChars[currentLang] || errorMessages.invalidChars.tr);
                e.target.value = '';
                return;
            }
            
            // Önizleme göster
            const reader = new FileReader();
            reader.onload = function(e) {
                // Resim boyutlarını kontrol et
                const img = new Image();
                img.onload = function() {
                    // Minimum boyut kontrolü
                    if (this.width < 10 || this.height < 10) {
                        alert(currentLang === 'en' ? 
                              'Image is too small. It must be at least 10x10 pixels.' : 
                              'Resim çok küçük. En az 10x10 piksel olmalıdır.');
                        coverImageInput.value = '';
                        return;
                    }
                    
                    // Maksimum boyut kontrolü
                    if (this.width > 5000 || this.height > 5000) {
                        alert(currentLang === 'en' ? 
                              'Image is too large. It must be at most 5000x5000 pixels.' : 
                              'Resim çok büyük. En fazla 5000x5000 piksel olmalıdır.');
                        coverImageInput.value = '';
                        return;
                    }
                    
                    // Önizleme göster
                    previewImage.src = e.target.result;
                    uploadArea.classList.add('hidden');
                    previewArea.classList.remove('hidden');
                };
                img.onerror = function() {
                    alert(currentLang === 'en' ? 
                          'File is not a valid image.' : 
                          'Dosya geçerli bir resim değil.');
                    coverImageInput.value = '';
                    return;
                };
                img.src = e.target.result;
            };
            reader.onerror = function() {
                alert(currentLang === 'en' ? 
                      'File could not be read.' : 
                      'Dosya okunamadı.');
                coverImageInput.value = '';
                return;
            };
            reader.readAsDataURL(file);
        }
    });

    // Kapak görselini kaldır
    removeButton.addEventListener('click', function() {
        coverImageInput.value = '';
        previewImage.src = '';
        uploadArea.classList.remove('hidden');
        previewArea.classList.add('hidden');
    });

    // Sürükle bırak işlevselliği
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            
            // Aynı güvenlik kontrollerini uygula
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type.toLowerCase())) {
                alert(errorMessages.fileType[currentLang] || errorMessages.fileType.tr);
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert(errorMessages.fileSize[currentLang] || errorMessages.fileSize.tr);
                return;
            }
            
            if (file.size < 1024) {
                alert(errorMessages.minFileSize[currentLang] || errorMessages.minFileSize.tr);
                return;
            }
            
            const fileName = file.name;
            if (fileName.length > 255) {
                alert(errorMessages.fileNameLength[currentLang] || errorMessages.fileNameLength.tr);
                return;
            }
            
            const dangerousChars = /[<>:"\/\\|?*\x00-\x1f]/;
            if (dangerousChars.test(fileName)) {
                alert(errorMessages.invalidChars[currentLang] || errorMessages.invalidChars.tr);
                return;
            }
            
            // DataTransfer kullanarak dosyayı input'a aktar
            const dt = new DataTransfer();
            dt.items.add(file);
            coverImageInput.files = dt.files;
            coverImageInput.dispatchEvent(new Event('change'));
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>