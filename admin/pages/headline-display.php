<?php
// Form gönderildiğinde ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'headline_settings') {
        $headline_display_type = $_POST['headline_display_type'] ?? 'static';
        $headline_auto_change = $_POST['headline_auto_change'] ?? 0;
        $headline_change_interval = $_POST['headline_change_interval'] ?? 5000;
        
        updateSetting('headline_display_type', $headline_display_type);
        updateSetting('headline_auto_change', $headline_auto_change);
        updateSetting('headline_change_interval', $headline_change_interval);
        
        $_SESSION['success_message'] = "Manşet gösterim ayarları başarıyla güncellendi.";
        $redirect_url = '/admin/?page=headline-display';
    }
    
    // Manşet makalelerini güncelle
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'headline_articles') {
        $selected_articles = $_POST['selected_articles'] ?? [];
        
        try {
            $database = new Database();
            $db = $database->pdo;
            
            // Önce mevcut manşet makalelerini temizle
            $db->exec("DELETE FROM headline_articles");
            
            // Yeni seçilen makaleleri ekle
            if (!empty($selected_articles)) {
                $insertStmt = $db->prepare("INSERT INTO headline_articles (article_id, display_order) VALUES (?, ?)");
                foreach ($selected_articles as $index => $article_id) {
                    if (!empty($article_id)) {
                        $insertStmt->execute([$article_id, $index + 1]);
                    }
                }
            }
            
            // Gösterim türünü formdan al veya mevcut değeri koru
            $headline_display_type = $_POST['current_display_type'] ?? getSiteSetting('headline_display_type', 'custom');
            updateSetting('headline_display_type', $headline_display_type);
            
            $_SESSION['success_message'] = "Manşet makaleleri başarıyla güncellendi.";
            $redirect_url = '/admin/?page=headline-display';
        } catch (Exception $e) {
            $_SESSION['success_message'] = "Hata: " . $e->getMessage();
            $redirect_url = '/admin/?page=headline-display';
        }
    }
}

// Mevcut ayarları al ve cache'i temizle
$currentDisplayType = getSiteSetting('headline_display_type', 'static');
$currentAutoChange = getSiteSetting('headline_auto_change', 0);
$currentChangeInterval = getSiteSetting('headline_change_interval', 5000);

// Mevcut manşet makalelerini al
$selectedHeadlineArticles = [];
try {
    $database = new Database();
    $db = $database->pdo;
    
    $headlineQuery = "SELECT ha.*, a.title, a.featured_image, a.excerpt 
                      FROM headline_articles ha 
                      LEFT JOIN articles a ON ha.article_id = a.id 
                      WHERE ha.is_active = 1 
                      ORDER BY ha.display_order";
    $headlineStmt = $db->prepare($headlineQuery);
    $headlineStmt->execute();
    $selectedHeadlineArticles = $headlineStmt->fetchAll();
    
    // Tüm yayınlanmış makaleleri al (seçim için)
    $allArticlesQuery = "SELECT id, title, featured_image, created_at 
                         FROM articles 
                         WHERE status = 'published' 
                         ORDER BY created_at DESC";
    $allArticlesStmt = $db->prepare($allArticlesQuery);
    $allArticlesStmt->execute();
    $allArticles = $allArticlesStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Headline articles fetch error: " . $e->getMessage());
}

// Session'dan başarı mesajını al
$success = '';
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (!empty($success)):
?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<!-- Manşet Gösterim Ayarları -->
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-newspaper mr-2 text-blue-500"></i>
                Manşet Gösterim Ayarları
            </h3>
            <p class="text-gray-600 dark:text-gray-400">Ana sayfadaki manşet gösterim türünü ve davranışını ayarlayın</p>
        </div>
        <div class="p-6">
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="form_type" value="headline_settings">
                
                <!-- Gösterim Türü -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Manşet Gösterim Türü</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-blue-500 transition-colors">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" name="headline_display_type" value="static" 
                                       <?php echo $currentDisplayType === 'static' ? 'checked' : ''; ?>
                                       class="mt-1 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Sabit Gösterim</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        En son öne çıkan makale sabit olarak gösterilir
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-blue-500 transition-colors">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" name="headline_display_type" value="carousel" 
                                       <?php echo $currentDisplayType === 'carousel' ? 'checked' : ''; ?>
                                       class="mt-1 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Döngüsel Gösterim</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Öne çıkan makaleler sırayla döngü halinde gösterilir
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-blue-500 transition-colors">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" name="headline_display_type" value="custom" 
                                       <?php echo $currentDisplayType === 'custom' ? 'checked' : ''; ?>
                                       class="mt-1 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Döngüsel Seçilmiş Gösterim</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Manuel olarak seçilen makaleler döngü halinde gösterilir
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Otomatik Değişim (Sadece döngüsel gösterimlerde) -->
                <div id="carousel-options" style="display: <?php echo in_array($currentDisplayType, ['carousel', 'custom']) ? 'block' : 'none'; ?>;">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Döngüsel Gösterim Seçenekleri</h4>
                        
                        <div class="space-y-4">
                            <!-- Otomatik Değişim -->
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" name="headline_auto_change" value="1" 
                                       <?php echo $currentAutoChange ? 'checked' : ''; ?>
                                       id="auto_change" class="text-blue-600 focus:ring-blue-500 rounded">
                                <label for="auto_change" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Otomatik manşet değişimi
                                </label>
                            </div>
                            
                            <!-- Değişim Aralığı -->
                            <div id="interval-setting" style="display: <?php echo $currentAutoChange ? 'block' : 'none'; ?>;">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Değişim Aralığı (milisaniye)
                                </label>
                                <select name="headline_change_interval" class="w-full max-w-xs px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="3000" <?php echo $currentChangeInterval == 3000 ? 'selected' : ''; ?>>3 saniye</option>
                                    <option value="5000" <?php echo $currentChangeInterval == 5000 ? 'selected' : ''; ?>>5 saniye</option>
                                    <option value="7000" <?php echo $currentChangeInterval == 7000 ? 'selected' : ''; ?>>7 saniye</option>
                                    <option value="10000" <?php echo $currentChangeInterval == 10000 ? 'selected' : ''; ?>>10 saniye</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manşet Makale Seçimi (Sadece custom modda) -->
                <div id="custom-articles" style="display: <?php echo $currentDisplayType === 'custom' ? 'block' : 'none'; ?>;">
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Manşet Makalelerini Seç</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Manşette gösterilmesini istediğiniz makaleleri seçin ve sıralarını belirleyin.
                        </p>
                        
                        <div class="space-y-4">
                            <div id="selected-articles-container">
                                <?php if (!empty($selectedHeadlineArticles)): ?>
                                    <?php foreach ($selectedHeadlineArticles as $index => $selectedArticle): ?>
                                    <div class="selected-article-item flex items-center space-x-4 p-3 bg-white dark:bg-gray-700 rounded-lg border">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
                                                <?php echo $index + 1; ?>
                                            </span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <?php if ($selectedArticle['featured_image']): ?>
                                                <img src="<?php echo htmlspecialchars($selectedArticle['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($selectedArticle['title']); ?>" 
                                                     class="w-12 h-12 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="font-medium text-gray-900 dark:text-white text-sm">
                                                <?php echo htmlspecialchars($selectedArticle['title']); ?>
                                            </h5>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars(substr($selectedArticle['excerpt'] ?? '', 0, 80)); ?>...
                                            </p>
                                        </div>
                                        <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                                                onclick="removeSelectedArticle(this, <?php echo $selectedArticle['article_id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <input type="hidden" name="selected_articles[]" value="<?php echo $selectedArticle['article_id']; ?>" form="articles-form">
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <select id="article-selector" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">Makale seçin...</option>
                                    <?php 
                                    // Seçilmiş makalelerin ID'lerini bir diziye ekleyelim
                                    $selectedArticleIds = array_column($selectedHeadlineArticles, 'article_id');
                                    
                                    foreach ($allArticles as $article):
                                        // Eğer makale zaten seçilmişse, dropdown'da gösterme
                                        if (!in_array($article['id'], $selectedArticleIds)):
                                    ?>
                                    <option value="<?php echo $article['id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($article['title']); ?>"
                                            data-image="<?php echo htmlspecialchars($article['featured_image'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </option>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </select>
                                <button type="button" onclick="addSelectedArticle()" 
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Ekle
                                </button>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" onclick="saveArticles()" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Manşet Makalelerini Kaydet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kaydet Butonu -->
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ayrı form: Manşet Makaleleri için -->
    <form id="articles-form" method="POST" action="" style="display: none;">
        <input type="hidden" name="form_type" value="headline_articles">
        <input type="hidden" name="current_display_type" value="<?php echo $currentDisplayType; ?>">
    </form>

    <!-- Önizleme -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-eye mr-2 text-green-500"></i>
                Önizleme
            </h3>
            <p class="text-gray-600 dark:text-gray-400">Mevcut ayarlarla manşet bölümü şu şekilde görünecek</p>
        </div>
        <div class="p-6">
            <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <strong>Aktif Mod:</strong> 
                    <?php 
                    switch($currentDisplayType) {
                        case 'static': echo 'Sabit Gösterim'; break;
                        case 'carousel': echo 'Döngüsel Gösterim'; break;
                        case 'custom': echo 'Döngüsel Seçilmiş Gösterim'; break;
                        default: echo 'Sabit Gösterim';
                    }
                    ?>
                </p>
                <?php if (in_array($currentDisplayType, ['carousel', 'custom'])): ?>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <strong>Otomatik Değişim:</strong> 
                    <?php echo $currentAutoChange ? 'Açık' : 'Kapalı'; ?>
                </p>
                <?php if ($currentAutoChange): ?>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong>Değişim Aralığı:</strong> 
                    <?php echo ($currentChangeInterval / 1000); ?> saniye
                </p>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($currentDisplayType === 'custom'): ?>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    <strong>Seçili Makale Sayısı:</strong> 
                    <?php echo count($selectedHeadlineArticles); ?> makale
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Gösterim türü değiştiğinde seçenekleri göster/gizle
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="headline_display_type"]');
    const carouselOptions = document.getElementById('carousel-options');
    const customArticles = document.getElementById('custom-articles');
    const autoChangeCheckbox = document.getElementById('auto_change');
    const intervalSetting = document.getElementById('interval-setting');
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'carousel' || this.value === 'custom') {
                carouselOptions.style.display = 'block';
            } else {
                carouselOptions.style.display = 'none';
            }
            
            if (this.value === 'custom') {
                customArticles.style.display = 'block';
            } else {
                customArticles.style.display = 'none';
            }
        });
    });
    
    // Otomatik değişim checkbox değiştiğinde interval ayarını göster/gizle
    autoChangeCheckbox.addEventListener('change', function() {
        if (this.checked) {
            intervalSetting.style.display = 'block';
        } else {
            intervalSetting.style.display = 'none';
        }
    });
});

// Makale seçimi fonksiyonları
function addSelectedArticle() {
    const selector = document.getElementById('article-selector');
    const container = document.getElementById('selected-articles-container');
    
    if (selector.value && selector.selectedOptions[0]) {
        const option = selector.selectedOptions[0];
        const articleId = option.value;
        const title = option.dataset.title;
        const image = option.dataset.image;
        
        // Zaten seçili mi kontrol et
        const existing = container.querySelector(`input[value="${articleId}"]`);
        if (existing) {
            alert('Bu makale zaten seçilmiş!');
            return;
        }
        
        // Seçilen makaleyi listeden kaldır
        selector.remove(selector.selectedIndex);
        
        // Yeni item oluştur
        const itemCount = container.children.length + 1;
        const itemDiv = document.createElement('div');
        itemDiv.className = 'selected-article-item flex items-center space-x-4 p-3 bg-white dark:bg-gray-700 rounded-lg border';
        
        itemDiv.innerHTML = `
            <div class="flex-shrink-0">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
                    ${itemCount}
                </span>
            </div>
            <div class="flex-shrink-0">
                ${image ? 
                    `<img src="${image}" alt="${title}" class="w-12 h-12 object-cover rounded">` :
                    `<div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>`
                }
            </div>
            <div class="flex-1">
                <h5 class="font-medium text-gray-900 dark:text-white text-sm">${title}</h5>
            </div>
            <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                    onclick="removeSelectedArticle(this, ${articleId})">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" name="selected_articles[]" value="${articleId}" form="articles-form">
        `;
        
        container.appendChild(itemDiv);
        selector.value = '';
        updateItemNumbers();
    }
}

function removeSelectedArticle(button, articleId) {
    // Kaldırılan makaleyi al
    const item = button.closest('.selected-article-item');
    const title = item.querySelector('h5').textContent;
    const imageEl = item.querySelector('img');
    const image = imageEl ? imageEl.getAttribute('src') : '';
    
    // Makaleyi tekrar seçilebilir listesine ekle
    const selector = document.getElementById('article-selector');
    const option = document.createElement('option');
    option.value = articleId;
    option.dataset.title = title;
    option.dataset.image = image;
    option.textContent = title;
    selector.appendChild(option);
    
    // Makaleyi seçili listesinden kaldır
    item.remove();
    updateItemNumbers();
}

function updateItemNumbers() {
    const items = document.querySelectorAll('.selected-article-item');
    items.forEach((item, index) => {
        const numberSpan = item.querySelector('.w-8.h-8 span, .inline-flex span');
        if (numberSpan) {
            numberSpan.textContent = index + 1;
        }
    });
}

function saveArticles() {
    // Mevcut gösterim türünü güncelle
    const selectedDisplayType = document.querySelector('input[name="headline_display_type"]:checked').value;
    document.querySelector('input[name="current_display_type"]').value = selectedDisplayType;
    
    // Formu gönder
    document.getElementById('articles-form').submit();
}

// Sürükle-bırak özelliği için Sortable.js kullanılabilir (gelecekte)
</script>

<?php if (isset($redirect_url)): ?>
<script>
window.location.href = '<?php echo $redirect_url; ?>';
</script>
<?php endif; ?>
