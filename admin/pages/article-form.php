<?php
// Makale ekleme/düzenleme sayfası - Bu dosya articles.php tarafından include edilir

// FORM İŞLEME - ÖNCELİKLE HEADER GÖNDERİLMEDEN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // İçerik için sanitize kullanma
    $category_id = intval($_POST['category_id'] ?? 0);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $featured_image = sanitizeInput($_POST['featured_image'] ?? '');
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $tags = sanitizeInput($_POST['tags'] ?? '');
    
    $action = $_GET['action'] ?? '';
    $article_id = $_GET['id'] ?? 0;
    
    // Kapak görselini yükle
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $featured_image = uploadImage($_FILES['cover_image'], 'articles');
        } catch (Exception $e) {
            $form_error = 'Kapak görseli yüklenemedi: ' . $e->getMessage();
        }
    }
    
    $slug = generateSlug($title);
    
    // Slug benzersizlik kontrolü
    if ($action == 'add') {
        $checkSlug = $db->prepare("SELECT COUNT(*) FROM articles WHERE slug = ?");
        $checkSlug->execute([$slug]);
        if ($checkSlug->fetchColumn() > 0) {
            $slug = $slug . '-' . time();
        }
    } else {
        // Edit durumunda mevcut makaleden farklı slug kontrolü
        $checkSlug = $db->prepare("SELECT COUNT(*) FROM articles WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $article_id]);
        if ($checkSlug->fetchColumn() > 0) {
            $slug = $slug . '-' . time();
        }
    }
    
    // Validation
    if (empty($title) || empty($content)) {
        $form_error = "Başlık ve içerik gereklidir.";
    } elseif ($category_id <= 0) {
        $form_error = "Geçerli bir kategori seçmelisiniz.";
    } else {
        try {
            if ($action == 'add') {
                // Yeni makale ekle
                $current_user_id = $_SESSION['user_id'] ?? 1; // Fallback admin user
                $stmt = $db->prepare("INSERT INTO articles (title, slug, content, excerpt, featured_image, category_id, user_id, is_premium, status, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $category_id, $current_user_id, $is_premium, $status, $meta_title, $meta_description]);
                
                // Yeni eklenen makalenin ID'sini al
                $article_id = $db->lastInsertId();
                
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
                            $articleTagStmt->execute([$article_id, $tagId]);
                        }
                    }
                }
                
                // Başarılı işlem sonrası JavaScript ile yönlendirme
                echo "<script>
                    window.location.href = '/admin/?page=articles&success=1';
                </script>";
                exit;
            } else {
                // Makaleyi güncelle
                $stmt = $db->prepare("UPDATE articles SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, category_id = ?, is_premium = ?, status = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $category_id, $is_premium, $status, $meta_title, $meta_description, $article_id]);
                
                // Mevcut etiketleri temizle
                $db->prepare("DELETE FROM article_tags WHERE article_id = ?")->execute([$article_id]);
                
                // Yeni etiketleri ekle
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
                            $articleTagStmt->execute([$article_id, $tagId]);
                        }
                    }
                }
                
                // Başarılı işlem sonrası JavaScript ile yönlendirme
                echo "<script>
                    window.location.href = '/admin/?page=articles&success=1';
                </script>";
                exit;
            }
        } catch (Exception $e) {
            $form_error = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

$action = $_GET['action'] ?? '';
if ($action != 'add' && $action != 'edit') {
    header('Location: ?page=articles');
    exit;
}

$article_id = $_GET['id'] ?? 0;
$article = null;

// Veritabanı bağlantısı kontrolü
if (!$db) {
    $database_error = 'Veritabanı bağlantısı bulunamadı.';
    goto display_page;
}

if ($action == 'edit' && $article_id) {
    $stmt = $db->prepare("SELECT a.*, GROUP_CONCAT(t.name) as tag_list 
                         FROM articles a 
                         LEFT JOIN article_tags at ON a.id = at.article_id 
                         LEFT JOIN tags t ON at.tag_id = t.id 
                         WHERE a.id = ? 
                         GROUP BY a.id");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: ?page=articles');
        exit;
    }
}

// Kategorileri al
display_page:
try {
    if ($db) {
        $categoriesStmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
        $categories = $categoriesStmt->fetchAll();
    } else {
        $categories = [];
    }
} catch (Exception $e) {
    $categories = [];
    $database_error = "Kategoriler yüklenirken hata oluştu: " . $e->getMessage();
}
?>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/2m1i3gn0z5hsv17cjtyflqqbd9yscepyequpmg8ykbc1v3q0/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Başlık -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php echo $action == 'add' ? 'Yeni Makale Ekle' : 'Makale Düzenle'; ?>
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            <?php echo $action == 'add' ? 'Yeni bir makale oluşturun' : 'Mevcut makaleyi düzenleyin'; ?>
        </p>
    </div>
    <a href="?page=articles" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Geri Dön
    </a>
</div>

<?php if (isset($database_error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $database_error; ?>
</div>
<?php endif; ?>

<?php if (isset($form_error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $form_error; ?>
</div>
<?php endif; ?>

<?php if (isset($success)): ?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<form method="POST" class="space-y-6" enctype="multipart/form-data">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ana İçerik -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Başlık -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Makale Başlığı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" required
                               value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="Makale başlığını yazın...">
                    </div>
                    
                    <!-- Özet -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Makale Özeti
                        </label>
                        <textarea name="excerpt" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  placeholder="Makale özetini yazın..."><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- İçerik -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Makale İçeriği <span class="text-red-500">*</span>
                </label>
                <textarea name="content" id="content" rows="20" required
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                          placeholder="Makale içeriğini yazın..."><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
            </div>

            <!-- SEO Ayarları -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Ayarları</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Başlık
                        </label>
                        <input type="text" name="meta_title"
                               value="<?php echo htmlspecialchars($article['meta_title'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="SEO için meta başlık...">
                        <p class="text-xs text-gray-500 mt-1">Boş bırakılırsa makale başlığı kullanılır</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Açıklama
                        </label>
                        <textarea name="meta_description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  placeholder="SEO için meta açıklama..."><?php echo htmlspecialchars($article['meta_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Etiketler
                        </label>
                        <input type="text" name="tags"
                               value="<?php echo htmlspecialchars($article['tag_list'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="etiket1, etiket2, etiket3...">
                        <p class="text-xs text-gray-500 mt-1">Etiketleri virgülle ayırın</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yan Panel -->
        <div class="space-y-6">
            <!-- Yayın Ayarları -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Yayın Ayarları</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durum</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="published" <?php echo ($article['status'] ?? 'published') == 'published' ? 'selected' : ''; ?>>Yayınlandı</option>
                            <option value="pending" <?php echo ($article['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>İnceleme Bekliyor</option>
                            <option value="draft" <?php echo ($article['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Taslak</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_premium" value="1" 
                                   <?php echo ($article['is_premium'] ?? 0) ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-crown text-yellow-500 mr-1"></i>Premium İçerik
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Kategori -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kategori</h3>
                <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Kategori Seçin</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo ($article['category_id'] ?? 0) == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Öne Çıkan Görsel -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Öne Çıkan Görsel</h3>
                <div class="space-y-4">
                    <!-- Dosya Yükleme -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-image mr-1"></i>Kapak Görseli Yükle
                        </label>
                        <div id="cover_image_upload" class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="cover_image" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Dosya Seç</span>
                                        <input id="cover_image" name="cover_image" type="file" accept="image/*" class="sr-only">
                                    </label>
                                    <p class="pl-1">veya sürükleyip bırakın</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    PNG, JPG, GIF - max 5MB
                                </p>
                            </div>
                        </div>
                        
                        <!-- Görsel önizlemesi -->
                        <div id="cover_image_preview" class="mt-4 <?php echo !empty($article['featured_image']) ? '' : 'hidden'; ?>">
                            <?php if (!empty($article['featured_image'])): ?>
                            <div class="relative">
                                <img id="preview_image" src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="Kapak Görseli Önizlemesi" class="w-full h-48 object-cover rounded-lg">
                                <button type="button" id="remove_cover_image" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                <i class="fas fa-check text-green-500 mr-1"></i>
                                Kapak görseli mevcut
                            </p>
                            <?php else: ?>
                            <div class="text-center text-gray-500 py-4">
                                <i class="fas fa-image text-2xl mb-2"></i>
                                <p>Kapak görseli yok</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Alternatif: URL Girişi -->
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-link mr-1"></i>Alternatif: Görsel URL'si
                        </label>
                        <input type="text" name="featured_image"
                               value="<?php echo htmlspecialchars($article['featured_image'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="https://example.com/image.jpg">
                        <p class="text-xs text-gray-500 mt-1">
                            Dosya yükleme yerine harici bir URL kullanabilirsiniz. Önerilen boyut: 1200x630 px
                        </p>
                    </div>
                </div>
            </div>

            <!-- İşlem Butonları -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="space-y-3">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $action == 'add' ? 'Makale Ekle' : 'Değişiklikleri Kaydet'; ?>
                    </button>
                    
                    <?php if ($action == 'edit'): ?>
                    <a href="/makale/<?php echo $article['slug']; ?>" target="_blank" 
                       class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors inline-block text-center">
                        <i class="fas fa-eye mr-2"></i>Önizleme
                    </a>
                    <?php endif; ?>
                    
                    <a href="?page=articles" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors inline-block text-center">
                        <i class="fas fa-times mr-2"></i>İptal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- TinyMCE Başlatma Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#content',
        height: 500,
        menubar: 'file edit view insert format tools table help',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'paste', 'code', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | link image media table | code fullscreen | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px; line-height:1.6; }',
        
        // Dil ayarları
        language: 'tr',
        
        // Branding kaldır
        branding: false,
        promotion: false,
        
        // Context menü ayarları - sağ tık menüsünde copy/paste için
        contextmenu: false, // TinyMCE context menüsünü devre dışı bırak
        
        // Paragraf ayarları - ÖNEMLİ!
        forced_root_block: 'p',
        force_p_newlines: true,
        forced_root_block_attrs: {},
        remove_trailing_brs: false,
        
        // Format seçenekleri
        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
        
        // Görsel yükleme
        image_advtab: true,
        image_upload_url: '/admin/api/upload-image.php',
        images_upload_credentials: true,
        automatic_uploads: true,
        
        // Dosya tarayıcısı
        file_picker_types: 'image',
        file_picker_callback: function(callback, value, meta) {
            if (meta.filetype === 'image') {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                
                input.onchange = function() {
                    var file = this.files[0];
                    if (file) {
                        var reader = new FileReader();
                        reader.onload = function() {
                            callback(reader.result, {
                                alt: file.name
                            });
                        };
                        reader.readAsDataURL(file);
                    }
                };
                
                input.click();
            }
        },
        
        // İçerik temizleme
        paste_data_images: true,
        paste_as_text: false,
        paste_auto_cleanup_on_paste: true,
        paste_strip_class_attributes: 'all',
        
        // Enter tuşu davranışı
        force_br_newlines: false,
        force_p_newlines: true,
        
        // Otomatik kaydetme
        autosave_ask_before_unload: true,
        autosave_interval: '30s',
        autosave_prefix: 'tinymce-autosave-{path}{query}-{id}-',
        autosave_restore_when_empty: false,
        autosave_retention: '2m',
        
        // Yazım denetimi
        browser_spellcheck: true,
        
        // Mobil optimizasyon
        mobile: {
            theme: 'silver'
        },
        
        // Setup callback
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
            
            // Add event listener for right-click context menu
            editor.on('init', function() {
                editor.getBody().addEventListener('contextmenu', function(e) {
                    // Stop TinyMCE context menu, use browser default
                    e.stopPropagation();
                }, true);
            });
        }
    });
    
    // Form gönderme kontrolü
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // TinyMCE içeriğini senkronize et
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            
            // Form validasyonu
            const title = document.querySelector('input[name="title"]').value.trim();
            const category = document.querySelector('select[name="category_id"]').value;
            
            if (!title) {
                e.preventDefault();
                alert('Lütfen makale başlığını girin!');
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Lütfen bir kategori seçin!');
                return false;
            }
            
            // Loading göster
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
            }
        });
    }
    
    // Slug oluşturma
    const titleInput = document.querySelector('input[name="title"]');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            // Başlık değiştiğinde slug önizlemesi yapılabilir
        });
    }
    
    // Kapak görseli yükleme işlevselliği
    const coverImageInput = document.getElementById('cover_image');
    const uploadArea = document.getElementById('cover_image_upload');
    const previewArea = document.getElementById('cover_image_preview');
    const previewImage = document.getElementById('preview_image');
    const removeButton = document.getElementById('remove_cover_image');

    if (coverImageInput && uploadArea && previewArea && previewImage && removeButton) {
        // Dosya seçildiğinde önizleme göster
        coverImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Dosya türünü kontrol et
                if (!file.type.startsWith('image/')) {
                    alert('Lütfen sadece resim dosyası seçin.');
                    e.target.value = '';
                    return;
                }
                
                // Dosya boyutunu kontrol et (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Dosya boyutu 5MB\'dan küçük olmalıdır.');
                    e.target.value = '';
                    return;
                }
                
                // Önizleme göster
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    uploadArea.classList.add('hidden');
                    previewArea.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        // Kapak görselini kaldır
        removeButton.addEventListener('click', function() {
            coverImageInput.value = '';
            previewImage.src = '';
            document.querySelector('input[name="featured_image"]').value = '';
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
                coverImageInput.files = files;
                coverImageInput.dispatchEvent(new Event('change'));
            }
        });
    }
});
</script> 