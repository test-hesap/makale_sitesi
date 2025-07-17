<?php
// Form gönderildiğinde ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'article_display_settings') {
        // Öne çıkan makaleler ayarları
        $featured_per_page = intval($_POST['featured_articles_per_page'] ?? 6);
        $featured_pagination_type = $_POST['featured_articles_pagination_type'] ?? 'pagination';
        
        // Son eklenen makaleler ayarları
        $recent_per_page = intval($_POST['recent_articles_per_page'] ?? 6);
        $recent_pagination_type = $_POST['recent_articles_pagination_type'] ?? 'pagination';
        
        // Popüler makaleler ayarları
        $popular_per_page = intval($_POST['popular_articles_per_page'] ?? 6);
        $popular_pagination_type = $_POST['popular_articles_pagination_type'] ?? 'pagination';
        
        // Ayarları güncelle
        updateSetting('featured_articles_per_page', $featured_per_page);
        updateSetting('featured_articles_pagination_type', $featured_pagination_type);
        updateSetting('recent_articles_per_page', $recent_per_page);
        updateSetting('recent_articles_pagination_type', $recent_pagination_type);
        updateSetting('popular_articles_per_page', $popular_per_page);
        updateSetting('popular_articles_pagination_type', $popular_pagination_type);
        
        // Başarı mesajını session'a kaydet ve JavaScript ile redirect yap
        $_SESSION['success_message'] = "Makale gösterim ayarları başarıyla güncellendi.";
        echo '<script>window.location.href = "' . $_SERVER['REQUEST_URI'] . '";</script>';
        exit;
    }
}

// Session'dan başarı mesajını al (redirect sonrası)
$success = '';
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Mevcut ayarları al
$featuredPerPage = getSiteSetting('featured_articles_per_page', 6);
$featuredPaginationType = getSiteSetting('featured_articles_pagination_type', 'pagination');
$recentPerPage = getSiteSetting('recent_articles_per_page', 6);
$recentPaginationType = getSiteSetting('recent_articles_pagination_type', 'pagination');
$popularPerPage = getSiteSetting('popular_articles_per_page', 6);
$popularPaginationType = getSiteSetting('popular_articles_pagination_type', 'pagination');

if (!empty($success)):
?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<!-- Makale Gösterim Ayarları -->
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-th-list mr-2 text-blue-500"></i>
                Makale Gösterim Ayarları
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Ana sayfada "Öne Çıkan Makaleler", "Son Eklenen Makaleler" ve "Popüler Makaleler" bölümlerinin sayfalama ayarlarını yönetin.
            </p>
        </div>

        <div class="p-6">
            <form method="POST" action="">
                <input type="hidden" name="form_type" value="article_display_settings">
                
                <div class="space-y-8">
                    <!-- Öne Çıkan Makaleler -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>
                            Öne Çıkan Makaleler
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Sayfa Başına Makale Sayısı -->
                            <div>
                                <label for="featured_articles_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sayfa Başına Makale Sayısı
                                </label>
                                <select name="featured_articles_per_page" id="featured_articles_per_page" 
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="3" <?php echo $featuredPerPage == 3 ? 'selected' : ''; ?>>3 Makale</option>
                                    <option value="6" <?php echo $featuredPerPage == 6 ? 'selected' : ''; ?>>6 Makale</option>
                                    <option value="9" <?php echo $featuredPerPage == 9 ? 'selected' : ''; ?>>9 Makale</option>
                                    <option value="12" <?php echo $featuredPerPage == 12 ? 'selected' : ''; ?>>12 Makale</option>
                                    <option value="15" <?php echo $featuredPerPage == 15 ? 'selected' : ''; ?>>15 Makale</option>
                                    <option value="18" <?php echo $featuredPerPage == 18 ? 'selected' : ''; ?>>18 Makale</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    6'dan fazla makale olduğunda sayfalama devreye girer
                                </p>
                            </div>
                            
                            <!-- Sayfalama Türü -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sayfalama Türü
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" name="featured_articles_pagination_type" value="pagination" 
                                               <?php echo $featuredPaginationType === 'pagination' ? 'checked' : ''; ?>
                                               class="mr-3 text-blue-600">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Sayfa Numaraları</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Klasik sayfalama (1, 2, 3...)
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" name="featured_articles_pagination_type" value="infinite" 
                                               <?php echo $featuredPaginationType === 'infinite' ? 'checked' : ''; ?>
                                               class="mr-3 text-blue-600">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Sonsuz Kaydırma</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                "Daha Fazla Yükle" butonu ile sürekli yükleme
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Son Eklenen Makaleler -->
                    <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg border border-green-200 dark:border-green-800">
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-clock text-green-500 mr-2"></i>
                            Son Eklenen Makaleler
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Sayfa Başına Makale Sayısı -->
                            <div>
                                <label for="recent_articles_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sayfa Başına Makale Sayısı
                                </label>
                                <select name="recent_articles_per_page" id="recent_articles_per_page" 
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="3" <?php echo $recentPerPage == 3 ? 'selected' : ''; ?>>3 Makale</option>
                                    <option value="4" <?php echo $recentPerPage == 4 ? 'selected' : ''; ?>>4 Makale</option>
                                    <option value="6" <?php echo $recentPerPage == 6 ? 'selected' : ''; ?>>6 Makale</option>
                                    <option value="9" <?php echo $recentPerPage == 9 ? 'selected' : ''; ?>>9 Makale</option>
                                    <option value="12" <?php echo $recentPerPage == 12 ? 'selected' : ''; ?>>12 Makale</option>
                                    <option value="15" <?php echo $recentPerPage == 15 ? 'selected' : ''; ?>>15 Makale</option>
                                    <option value="18" <?php echo $recentPerPage == 18 ? 'selected' : ''; ?>>18 Makale</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    6'dan fazla makale olduğunda sayfalama devreye girer
                                </p>
                            </div>
                            
                            <!-- Sayfalama Türü -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sayfalama Türü
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" name="recent_articles_pagination_type" value="pagination" 
                                               <?php echo $recentPaginationType === 'pagination' ? 'checked' : ''; ?>
                                               class="mr-3 text-blue-600">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Sayfa Numaraları</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Klasik sayfalama (1, 2, 3...)
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" name="recent_articles_pagination_type" value="infinite" 
                                               <?php echo $recentPaginationType === 'infinite' ? 'checked' : ''; ?>
                                               class="mr-3 text-blue-600">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Sonsuz Kaydırma</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                "Daha Fazla Yükle" butonu ile sürekli yükleme
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Popüler Makaleler -->
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-lg border border-purple-200 dark:border-purple-800">
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-fire text-purple-500 mr-2"></i>
                            Popüler Makaleler
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Sayfa Başına Makale Sayısı -->
                            <div>
                                <label for="popular_articles_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sayfa Başına Makale Sayısı
                                </label>
                                <select name="popular_articles_per_page" id="popular_articles_per_page" 
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="3" <?php echo $popularPerPage == 3 ? 'selected' : ''; ?>>3 Makale</option>
                                    <option value="4" <?php echo $popularPerPage == 4 ? 'selected' : ''; ?>>4 Makale</option>
                                    <option value="6" <?php echo $popularPerPage == 6 ? 'selected' : ''; ?>>6 Makale</option>
                                    <option value="9" <?php echo $popularPerPage == 9 ? 'selected' : ''; ?>>9 Makale</option>
                                    <option value="12" <?php echo $popularPerPage == 12 ? 'selected' : ''; ?>>12 Makale</option>
                                    <option value="15" <?php echo $popularPerPage == 15 ? 'selected' : ''; ?>>15 Makale</option>
                                    <option value="18" <?php echo $popularPerPage == 18 ? 'selected' : ''; ?>>18 Makale</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    6'dan fazla makale olduğunda sayfalama devreye girer
                                </p>
                            </div>
                            
                            <!-- Sayfalama Türü -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sayfalama Türü
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" name="popular_articles_pagination_type" value="pagination" 
                                               <?php echo $popularPaginationType === 'pagination' ? 'checked' : ''; ?>
                                               class="mr-3 text-blue-600">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Sayfa Numaraları</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Klasik sayfalama (1, 2, 3...)
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" name="popular_articles_pagination_type" value="infinite" 
                                               <?php echo $popularPaginationType === 'infinite' ? 'checked' : ''; ?>
                                               class="mr-3 text-blue-600">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Sonsuz Kaydırma</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                "Daha Fazla Yükle" butonu ile sürekli yükleme
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kaydet Butonu -->
                <div class="flex justify-end mt-8">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bilgi Kutusu -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center">
            <i class="fas fa-info-circle mr-2"></i>
            Önemli Bilgiler
        </h4>
        <div class="space-y-3 text-blue-800 dark:text-blue-200">
            <div class="flex items-start space-x-2">
                <i class="fas fa-check-circle mt-1 text-blue-600 dark:text-blue-400"></i>
                <p><strong>Sayfalama Devreye Girme:</strong> Ayarlanan sayfa başına makale sayısından fazla makale olduğunda otomatik olarak sayfalama devreye girer.</p>
            </div>
            <div class="flex items-start space-x-2">
                <i class="fas fa-check-circle mt-1 text-blue-600 dark:text-blue-400"></i>
                <p><strong>Sonsuz Kaydırma:</strong> Bu seçenek aktif olduğunda, kullanıcılar sayfanın sonuna geldiğinde "Daha Fazla Yükle" butonu görünür ve yeni makaleler AJAX ile yüklenir.</p>
            </div>
            <div class="flex items-start space-x-2">
                <i class="fas fa-check-circle mt-1 text-blue-600 dark:text-blue-400"></i>
                <p><strong>Performans:</strong> Çok fazla makale gösterimi sayfa yükleme süresini etkileyebilir. Optimum performans için 6-12 makale arası önerilir.</p>
            </div>
            <div class="flex items-start space-x-2">
                <i class="fas fa-check-circle mt-1 text-blue-600 dark:text-blue-400"></i>
                <p><strong>Mobil Uyumluluk:</strong> Sonsuz kaydırma mobil cihazlarda daha iyi kullanıcı deneyimi sağlar.</p>
            </div>
        </div>
    </div>

    <!-- Önizleme -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-eye mr-2 text-green-500"></i>
                Mevcut Ayarlar Özeti
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Öne Çıkan Makaleler Özeti -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h5 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                        <i class="fas fa-star mr-1"></i>
                        Öne Çıkan Makaleler
                    </h5>
                    <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <p><strong>Sayfa başına:</strong> <?php echo $featuredPerPage; ?> makale</p>
                        <p><strong>Sayfalama:</strong> <?php echo $featuredPaginationType === 'pagination' ? 'Sayfa Numaraları' : 'Sonsuz Kaydırma'; ?></p>
                    </div>
                </div>

                <!-- Son Eklenen Makaleler Özeti -->
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <h5 class="font-semibold text-green-900 dark:text-green-100 mb-2 flex items-center">
                        <i class="fas fa-clock mr-1"></i>
                        Son Eklenen Makaleler
                    </h5>
                    <div class="text-sm text-green-800 dark:text-green-200 space-y-1">
                        <p><strong>Sayfa başına:</strong> <?php echo $recentPerPage; ?> makale</p>
                        <p><strong>Sayfalama:</strong> <?php echo $recentPaginationType === 'pagination' ? 'Sayfa Numaraları' : 'Sonsuz Kaydırma'; ?></p>
                    </div>
                </div>

                <!-- Popüler Makaleler Özeti -->
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <h5 class="font-semibold text-purple-900 dark:text-purple-100 mb-2 flex items-center">
                        <i class="fas fa-fire mr-1"></i>
                        Popüler Makaleler
                    </h5>
                    <div class="text-sm text-purple-800 dark:text-purple-200 space-y-1">
                        <p><strong>Sayfa başına:</strong> <?php echo $popularPerPage; ?> makale</p>
                        <p><strong>Sayfalama:</strong> <?php echo $popularPaginationType === 'pagination' ? 'Sayfa Numaraları' : 'Sonsuz Kaydırma'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validasyonu
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const selects = document.querySelectorAll('select[name$="_per_page"]');
    
    // Sayfa başına makale sayısı değiştiğinde uyarı göster
    selects.forEach(select => {
        select.addEventListener('change', function() {
            const value = parseInt(this.value);
            const warning = this.parentElement.querySelector('.warning-message');
            
            // Mevcut uyarıyı kaldır
            if (warning) {
                warning.remove();
            }
            
            if (value > 12) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'warning-message text-xs text-orange-600 dark:text-orange-400 mt-1 flex items-center';
                warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Yüksek sayıda makale sayfa yükleme süresini artırabilir.';
                this.parentElement.appendChild(warningDiv);
            }
        });
    });
    
    // Form gönderiminde onay
    form.addEventListener('submit', function(e) {
        const hasHighValues = Array.from(selects).some(select => parseInt(select.value) > 15);
        
        if (hasHighValues) {
            const confirmed = confirm('15\'ten fazla makale gösterimi sayfa performansını etkileyebilir. Devam etmek istiyor musunuz?');
            if (!confirmed) {
                e.preventDefault();
            }
        }
    });
});
</script>
