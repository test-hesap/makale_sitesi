<?php
// Sitemap işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_sitemap'])) {
        // Sitemap oluştur
        try {
            generateSitemapXML();
            $success_message = "Sitemap başarıyla oluşturuldu ve güncellendi!";
        } catch (Exception $e) {
            $error_message = "Sitemap oluşturulurken hata oluştu: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_settings'])) {
        // Sitemap ayarlarını güncelle
        $settings = [
            'sitemap_enabled' => isset($_POST['sitemap_enabled']) ? 1 : 0,
            'sitemap_auto_generate' => isset($_POST['sitemap_auto_generate']) ? 1 : 0,
            'sitemap_include_images' => isset($_POST['sitemap_include_images']) ? 1 : 0,
            'sitemap_include_users' => isset($_POST['sitemap_include_users']) ? 1 : 0,
            'sitemap_priority_homepage' => floatval($_POST['sitemap_priority_homepage'] ?? 1.0),
            'sitemap_priority_articles' => floatval($_POST['sitemap_priority_articles'] ?? 0.8),
            'sitemap_priority_categories' => floatval($_POST['sitemap_priority_categories'] ?? 0.7),
            'sitemap_priority_static' => floatval($_POST['sitemap_priority_static'] ?? 0.5),
            'sitemap_changefreq_homepage' => $_POST['sitemap_changefreq_homepage'] ?? 'daily',
            'sitemap_changefreq_articles' => $_POST['sitemap_changefreq_articles'] ?? 'monthly',
            'sitemap_changefreq_categories' => $_POST['sitemap_changefreq_categories'] ?? 'weekly',
            'sitemap_changefreq_static' => $_POST['sitemap_changefreq_static'] ?? 'yearly'
        ];
        
        try {
            foreach ($settings as $key => $value) {
                updateSiteSetting($key, $value);
            }
            $success_message = "Sitemap ayarları başarıyla güncellendi!";
        } catch (Exception $e) {
            $error_message = "Ayarlar güncellenirken hata oluştu: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_robots'])) {
        // Robots.txt güncelle
        try {
            $robots_content = sanitizeInput($_POST['robots_content']);
            $robots_file = BASE_PATH . '/robots.txt';
            
            if (file_put_contents($robots_file, $robots_content) !== false) {
                $success_message = "robots.txt dosyası başarıyla güncellendi!";
            } else {
                $error_message = "robots.txt dosyası güncellenirken hata oluştu!";
            }
        } catch (Exception $e) {
            $error_message = "robots.txt güncellenirken hata oluştu: " . $e->getMessage();
        }
    }
}

// Mevcut ayarları yükle
$sitemap_settings = [
    'sitemap_enabled' => getSiteSetting('sitemap_enabled', 1),
    'sitemap_auto_generate' => getSiteSetting('sitemap_auto_generate', 1),
    'sitemap_include_images' => getSiteSetting('sitemap_include_images', 0),
    'sitemap_include_users' => getSiteSetting('sitemap_include_users', 0),
    'sitemap_priority_homepage' => getSiteSetting('sitemap_priority_homepage', 1.0),
    'sitemap_priority_articles' => getSiteSetting('sitemap_priority_articles', 0.8),
    'sitemap_priority_categories' => getSiteSetting('sitemap_priority_categories', 0.7),
    'sitemap_priority_static' => getSiteSetting('sitemap_priority_static', 0.5),
    'sitemap_changefreq_homepage' => getSiteSetting('sitemap_changefreq_homepage', 'daily'),
    'sitemap_changefreq_articles' => getSiteSetting('sitemap_changefreq_articles', 'monthly'),
    'sitemap_changefreq_categories' => getSiteSetting('sitemap_changefreq_categories', 'weekly'),
    'sitemap_changefreq_static' => getSiteSetting('sitemap_changefreq_static', 'yearly')
];

// Sitemap istatistikleri
$sitemap_stats = getSitemapStats();

// Son sitemap oluşturma zamanı
$sitemap_file = BASE_PATH . '/sitemap.xml';
$last_generated = file_exists($sitemap_file) ? filemtime($sitemap_file) : null;

// Sitemap dosya boyutu
$sitemap_size = file_exists($sitemap_file) ? filesize($sitemap_file) : 0;

// Robots.txt içeriğini oku
$robots_file = BASE_PATH . '/robots.txt';
$robots_content = file_exists($robots_file) ? file_get_contents($robots_file) : '';
?>

<div class="space-y-6">
    <?php if (isset($success_message)): ?>
    <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success_message; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error_message; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sitemap İstatistikleri -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam URL</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $sitemap_stats['total_urls']; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sitemap text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Makaleler</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $sitemap_stats['articles']; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kategoriler</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $sitemap_stats['categories']; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sabit Sayfalar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $sitemap_stats['static_pages']; ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sitemap Bilgileri -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sitemap Bilgileri</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Sitemap URL:</label>
                <div class="flex items-center space-x-2">
                    <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm"><?php echo getCurrentDomain(); ?>/sitemap.xml</code>
                    <a href="<?php echo getCurrentDomain(); ?>/sitemap.xml" target="_blank" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Son Güncelleme:</label>
                <p class="text-sm text-gray-900 dark:text-white">
                    <?php if ($last_generated): ?>
                        <?php echo date('d.m.Y H:i:s', $last_generated); ?>
                    <?php else: ?>
                        <span class="text-red-600">Henüz oluşturulmamış</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Dosya Boyutu:</label>
                <p class="text-sm text-gray-900 dark:text-white">
                    <?php if ($sitemap_size > 0): ?>
                        <?php echo number_format($sitemap_size / 1024, 2); ?> KB
                    <?php else: ?>
                        <span class="text-red-600">0 KB</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Sitemap Oluştur Butonu -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sitemap Oluştur</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sitemap'i manuel olarak oluşturun veya güncelleyin</p>
            </div>
            <form method="POST" class="inline">
                <button type="submit" name="generate_sitemap" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-refresh mr-2"></i>
                    Sitemap Oluştur
                </button>
            </form>
        </div>
    </div>

    <!-- Sitemap Ayarları -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Sitemap Ayarları</h3>
        
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Genel Ayarlar -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Genel Ayarlar</h4>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="sitemap_enabled" name="sitemap_enabled" <?php echo $sitemap_settings['sitemap_enabled'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="sitemap_enabled" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sitemap'i etkinleştir</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="sitemap_auto_generate" name="sitemap_auto_generate" <?php echo $sitemap_settings['sitemap_auto_generate'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="sitemap_auto_generate" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Otomatik güncelleme</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="sitemap_include_images" name="sitemap_include_images" <?php echo $sitemap_settings['sitemap_include_images'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="sitemap_include_images" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Görselleri dahil et</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="sitemap_include_users" name="sitemap_include_users" <?php echo $sitemap_settings['sitemap_include_users'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="sitemap_include_users" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Kullanıcı profillerini dahil et</label>
                    </div>
                </div>

                <!-- Öncelik Ayarları -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Öncelik Ayarları</h4>
                    
                    <div>
                        <label for="sitemap_priority_homepage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ana Sayfa Önceliği</label>
                        <input type="number" id="sitemap_priority_homepage" name="sitemap_priority_homepage" min="0" max="1" step="0.1" value="<?php echo $sitemap_settings['sitemap_priority_homepage']; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="sitemap_priority_articles" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Makale Önceliği</label>
                        <input type="number" id="sitemap_priority_articles" name="sitemap_priority_articles" min="0" max="1" step="0.1" value="<?php echo $sitemap_settings['sitemap_priority_articles']; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="sitemap_priority_categories" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori Önceliği</label>
                        <input type="number" id="sitemap_priority_categories" name="sitemap_priority_categories" min="0" max="1" step="0.1" value="<?php echo $sitemap_settings['sitemap_priority_categories']; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="sitemap_priority_static" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sabit Sayfa Önceliği</label>
                        <input type="number" id="sitemap_priority_static" name="sitemap_priority_static" min="0" max="1" step="0.1" value="<?php echo $sitemap_settings['sitemap_priority_static']; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <!-- Değişim Sıklığı Ayarları -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Değişim Sıklığı</h4>
                    
                    <div>
                        <label for="sitemap_changefreq_homepage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ana Sayfa</label>
                        <select id="sitemap_changefreq_homepage" name="sitemap_changefreq_homepage" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="always" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'always' ? 'selected' : ''; ?>>Her zaman</option>
                            <option value="hourly" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'hourly' ? 'selected' : ''; ?>>Saatlik</option>
                            <option value="daily" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                            <option value="weekly" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                            <option value="monthly" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                            <option value="yearly" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'yearly' ? 'selected' : ''; ?>>Yıllık</option>
                            <option value="never" <?php echo $sitemap_settings['sitemap_changefreq_homepage'] === 'never' ? 'selected' : ''; ?>>Hiçbir zaman</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="sitemap_changefreq_articles" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Makaleler</label>
                        <select id="sitemap_changefreq_articles" name="sitemap_changefreq_articles" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="always" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'always' ? 'selected' : ''; ?>>Her zaman</option>
                            <option value="hourly" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'hourly' ? 'selected' : ''; ?>>Saatlik</option>
                            <option value="daily" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                            <option value="weekly" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                            <option value="monthly" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                            <option value="yearly" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'yearly' ? 'selected' : ''; ?>>Yıllık</option>
                            <option value="never" <?php echo $sitemap_settings['sitemap_changefreq_articles'] === 'never' ? 'selected' : ''; ?>>Hiçbir zaman</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="sitemap_changefreq_categories" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategoriler</label>
                        <select id="sitemap_changefreq_categories" name="sitemap_changefreq_categories" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="always" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'always' ? 'selected' : ''; ?>>Her zaman</option>
                            <option value="hourly" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'hourly' ? 'selected' : ''; ?>>Saatlik</option>
                            <option value="daily" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                            <option value="weekly" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                            <option value="monthly" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                            <option value="yearly" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'yearly' ? 'selected' : ''; ?>>Yıllık</option>
                            <option value="never" <?php echo $sitemap_settings['sitemap_changefreq_categories'] === 'never' ? 'selected' : ''; ?>>Hiçbir zaman</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="sitemap_changefreq_static" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sabit Sayfalar</label>
                        <select id="sitemap_changefreq_static" name="sitemap_changefreq_static" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="always" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'always' ? 'selected' : ''; ?>>Her zaman</option>
                            <option value="hourly" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'hourly' ? 'selected' : ''; ?>>Saatlik</option>
                            <option value="daily" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                            <option value="weekly" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                            <option value="monthly" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                            <option value="yearly" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'yearly' ? 'selected' : ''; ?>>Yıllık</option>
                            <option value="never" <?php echo $sitemap_settings['sitemap_changefreq_static'] === 'never' ? 'selected' : ''; ?>>Hiçbir zaman</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="update_settings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>
                    Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- Otomatik Güncelleme Cron Job -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Otomatik Güncelleme İçin</h3>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Sitemap'i otomatik olarak güncellemek için sunucunuza aşağıdaki cron görevi ekleyin:
            </p>
            
            <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400">Günlük saat 03:00'da çalışacak cron kodu:</span>
                    <button onclick="copyCronCode()" class="text-blue-400 hover:text-blue-300">
                        <i class="fas fa-copy mr-1"></i>
                        Kopyala
                    </button>
                </div>
                <code id="cron-code">0 3 * * * wget -q -O /dev/null <?php echo getCurrentDomain(); ?>/sitemap-cron.php</code>
            </div>
            
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Bu komut her gece saat 03:00'da sitemap'i otomatik olarak güncelleyecektir.
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cron Bilgileri -->
            <div class="space-y-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Cron Job Bilgileri</h4>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Script URL:</span>
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?php echo getCurrentDomain(); ?>/sitemap-cron.php</code>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Son Çalışma:</span>
                        <span class="text-gray-900 dark:text-white">
                            <?php 
                            $last_run = getSiteSetting('sitemap_last_cron_run', '');
                            echo $last_run ? date('d.m.Y H:i:s', strtotime($last_run)) : 'Henüz çalışmadı';
                            ?>
                        </span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Log Dosyası:</span>
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">/logs/sitemap_cron.log</code>
                    </div>
                </div>
            </div>
            
            <!-- Cron Test -->
            <div class="space-y-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Test Cron Job</h4>
                
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Cron job'u test etmek için aşağıdaki butona tıklayın:
                </p>
                
                <form method="GET" action="<?php echo getCurrentDomain(); ?>/sitemap-cron.php" target="_blank">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-play mr-2"></i>
                        Cron Job'u Test Et
                    </button>
                </form>
                
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Test sonucu yeni sekmede açılacaktır.
                </div>
            </div>
        </div>
        
        <!-- SEO İpuçları -->
        <div class="mt-6 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-lightbulb mr-2"></i>
                SEO İpuçları
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Google Search Console'a sitenizi ekleyin ve sitemap'inizi gönderin.</li>
                <li>• Sayfalarınızın meta açıklamalarını ve başlıklarını optimize edin.</li>
                <li>• Sayfalarınızın yükleme hızını artırın.</li>
                <li>• Kaliteli içerik üretmeye odaklanın.</li>
                <li>• Düzenli olarak içeriklerinizi güncelleyin.</li>
            </ul>
        </div>
    </div>

    <!-- Robots.txt Yönetimi -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Robots.txt Yönetimi</h3>
        
        <div class="mb-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Robots.txt Dosyası</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Arama motorları için robot yönergelerini yönetin</p>
                </div>
                <div class="flex space-x-2">
                    <a href="<?php echo getCurrentDomain(); ?>/robots.txt" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        Dosyayı Görüntüle
                    </a>
                    <button onclick="resetRobotsToDefault()" class="text-orange-600 hover:text-orange-700 text-sm">
                        <i class="fas fa-undo mr-1"></i>
                        Varsayılana Sıfırla
                    </button>
                </div>
            </div>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <textarea 
                    name="robots_content" 
                    id="robots_content" 
                    rows="12" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                    placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/"><?php echo htmlspecialchars($robots_content); ?></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" name="update_robots" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>
                    Robots.txt Güncelle
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function resetRobotsToDefault() {
    const defaultContent = `User-agent: *
Allow: /
Disallow: /admin/
Disallow: /config/
Disallow: /includes/
Disallow: /vendor/
Disallow: /logs/
Disallow: /cache/
Disallow: /database/

Sitemap: ${window.location.origin}/sitemap.xml`;
    
    document.getElementById('robots_content').value = defaultContent;
}

function copyCronCode() {
    const cronCode = document.getElementById('cron-code').textContent;
    
    // Geçici bir textarea oluştur
    const tempTextarea = document.createElement('textarea');
    tempTextarea.value = cronCode;
    document.body.appendChild(tempTextarea);
    
    // Metni seç ve kopyala
    tempTextarea.select();
    document.execCommand('copy');
    
    // Geçici textarea'yı kaldır
    document.body.removeChild(tempTextarea);
    
    // Kullanıcıya geri bildirim ver
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check mr-1"></i>Kopyalandı!';
    button.classList.add('text-green-400');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('text-green-400');
    }, 2000);
}
</script>
