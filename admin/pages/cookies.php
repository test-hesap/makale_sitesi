<?php
// Yetki kontrolü
if (!isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

// Veritabanı bağlantısı
$db = new Database();

// Çerez ayarlarını kaydet
if (isset($_POST['save_settings'])) {
    $cookieConsentEnabled = isset($_POST['cookie_consent_enabled']) ? 1 : 0;
    $cookieConsentText = sanitizeInput($_POST['cookie_consent_text']);
    $cookieConsentButtonText = sanitizeInput($_POST['cookie_consent_button_text']);
    $cookieConsentPosition = sanitizeInput($_POST['cookie_consent_position']);
    $cookieConsentTheme = sanitizeInput($_POST['cookie_consent_theme']);
    $cookieConsentShowLink = isset($_POST['cookie_consent_show_link']) ? 1 : 0;
    $cookieConsentLinkText = sanitizeInput($_POST['cookie_consent_link_text']);
    $cookieAnalyticsEnabled = isset($_POST['cookie_analytics_enabled']) ? 1 : 0;
    $cookieMarketingEnabled = isset($_POST['cookie_marketing_enabled']) ? 1 : 0;
    
    // Ayarları güncelle
    $settings = [
        'cookie_consent_enabled' => $cookieConsentEnabled,
        'cookie_consent_text' => $cookieConsentText,
        'cookie_consent_button_text' => $cookieConsentButtonText,
        'cookie_consent_position' => $cookieConsentPosition,
        'cookie_consent_theme' => $cookieConsentTheme,
        'cookie_consent_show_link' => $cookieConsentShowLink,
        'cookie_consent_link_text' => $cookieConsentLinkText,
        'cookie_analytics_enabled' => $cookieAnalyticsEnabled,
        'cookie_marketing_enabled' => $cookieMarketingEnabled
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    
    $success_message = "Çerez ayarları başarıyla güncellendi.";
}

// Mevcut çerez ayarlarını al
$cookieSettings = [
    'cookie_consent_enabled' => getSetting('cookie_consent_enabled', '1'),
    'cookie_consent_text' => getSetting('cookie_consent_text', 'Bu web sitesi, size en iyi deneyimi sunmak için çerezler kullanır.'),
    'cookie_consent_button_text' => getSetting('cookie_consent_button_text', 'Kabul Et'),
    'cookie_consent_position' => getSetting('cookie_consent_position', 'bottom'),
    'cookie_consent_theme' => getSetting('cookie_consent_theme', 'dark'),
    'cookie_consent_show_link' => getSetting('cookie_consent_show_link', '1'),
    'cookie_consent_link_text' => getSetting('cookie_consent_link_text', 'Daha fazla bilgi'),
    'cookie_analytics_enabled' => getSetting('cookie_analytics_enabled', '1'),
    'cookie_marketing_enabled' => getSetting('cookie_marketing_enabled', '1')
];
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
    <?php if (isset($success_message)): ?>
    <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 mb-4">
        <p><?php echo $success_message; ?></p>
    </div>
    <?php endif; ?>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Çerez Ayarları</h2>
        </div>
        
        <form method="post" class="space-y-6">
            <!-- Çerez Bildirimi Ayarları -->
            <div class="border dark:border-gray-700 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Çerez Bildirimi</h3>
                
                <div class="space-y-4">
                    <!-- Çerez Bildirimi Aktif/Pasif -->
                    <div class="flex items-center">
                        <input type="checkbox" id="cookie_consent_enabled" name="cookie_consent_enabled" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600" <?php echo $cookieSettings['cookie_consent_enabled'] ? 'checked' : ''; ?>>
                        <label for="cookie_consent_enabled" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Çerez Bildirimini Aktifleştir</label>
                    </div>
                    
                    <!-- Çerez Bildirimi Metni -->
                    <div>
                        <label for="cookie_consent_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Çerez Bildirimi Metni</label>
                        <textarea id="cookie_consent_text" name="cookie_consent_text" rows="3" class="w-full px-3 py-2 text-gray-700 dark:text-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"><?php echo $cookieSettings['cookie_consent_text']; ?></textarea>
                    </div>
                    
                    <!-- Çerez Bildirimi Buton Metni -->
                    <div>
                        <label for="cookie_consent_button_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buton Metni</label>
                        <input type="text" id="cookie_consent_button_text" name="cookie_consent_button_text" value="<?php echo $cookieSettings['cookie_consent_button_text']; ?>" class="w-full px-3 py-2 text-gray-700 dark:text-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                    </div>
                    
                    <!-- Çerez Bildirimi Pozisyonu -->
                    <div>
                        <label for="cookie_consent_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bildirim Pozisyonu</label>
                        <select id="cookie_consent_position" name="cookie_consent_position" class="w-full px-3 py-2 text-gray-700 dark:text-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                            <option value="bottom" <?php echo $cookieSettings['cookie_consent_position'] === 'bottom' ? 'selected' : ''; ?>>Alt</option>
                            <option value="top" <?php echo $cookieSettings['cookie_consent_position'] === 'top' ? 'selected' : ''; ?>>Üst</option>
                            <option value="bottom-left" <?php echo $cookieSettings['cookie_consent_position'] === 'bottom-left' ? 'selected' : ''; ?>>Sol Alt</option>
                            <option value="bottom-right" <?php echo $cookieSettings['cookie_consent_position'] === 'bottom-right' ? 'selected' : ''; ?>>Sağ Alt</option>
                        </select>
                    </div>
                    
                    <!-- Çerez Bildirimi Tema -->
                    <div>
                        <label for="cookie_consent_theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bildirim Teması</label>
                        <select id="cookie_consent_theme" name="cookie_consent_theme" class="w-full px-3 py-2 text-gray-700 dark:text-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 hidden">
                            <option value="dark" <?php echo $cookieSettings['cookie_consent_theme'] === 'dark' ? 'selected' : ''; ?>>Koyu</option>
                            <option value="light" <?php echo $cookieSettings['cookie_consent_theme'] === 'light' ? 'selected' : ''; ?>>Açık</option>
                        </select>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="theme-select px-3 py-2 text-sm border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center <?php echo $cookieSettings['cookie_consent_theme'] === 'dark' ? 'bg-blue-100 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : ''; ?>" data-theme="dark">
                                <i class="fas fa-moon mr-2"></i> Koyu Tema
                            </button>
                            <button type="button" class="theme-select px-3 py-2 text-sm border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center <?php echo $cookieSettings['cookie_consent_theme'] === 'light' ? 'bg-blue-100 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : ''; ?>" data-theme="light">
                                <i class="fas fa-sun mr-2"></i> Açık Tema
                            </button>
                        </div>
                    </div>
                    
                    <!-- Çerez Politikası Bağlantısı -->
                    <div class="flex items-center">
                        <input type="checkbox" id="cookie_consent_show_link" name="cookie_consent_show_link" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600" <?php echo $cookieSettings['cookie_consent_show_link'] ? 'checked' : ''; ?>>
                        <label for="cookie_consent_show_link" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Çerez Politikası Bağlantısını Göster</label>
                    </div>
                    
                    <!-- Çerez Politikası Bağlantı Metni -->
                    <div>
                        <label for="cookie_consent_link_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bağlantı Metni</label>
                        <input type="text" id="cookie_consent_link_text" name="cookie_consent_link_text" value="<?php echo $cookieSettings['cookie_consent_link_text']; ?>" class="w-full px-3 py-2 text-gray-700 dark:text-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                    </div>
                </div>
            </div>
            
            <!-- Çerez Kategorileri -->
            <div class="border dark:border-gray-700 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Çerez Kategorileri</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="cookie_analytics_enabled" name="cookie_analytics_enabled" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600" <?php echo $cookieSettings['cookie_analytics_enabled'] ? 'checked' : ''; ?>>
                        <label for="cookie_analytics_enabled" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Analitik Çerezleri Aktifleştir (Google Analytics)</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="cookie_marketing_enabled" name="cookie_marketing_enabled" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600" <?php echo $cookieSettings['cookie_marketing_enabled'] ? 'checked' : ''; ?>>
                        <label for="cookie_marketing_enabled" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Pazarlama Çerezlerini Aktifleştir (Google AdSense)</label>
                    </div>
                </div>
            </div>
            
            <!-- Önizleme -->
            <div class="border dark:border-gray-700 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Önizleme</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pozisyon:</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="preview-position px-2 py-1 text-xs border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-position="bottom">Alt</button>
                            <button type="button" class="preview-position px-2 py-1 text-xs border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-position="top">Üst</button>
                            <button type="button" class="preview-position px-2 py-1 text-xs border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-position="bottom-left">Sol Alt</button>
                            <button type="button" class="preview-position px-2 py-1 text-xs border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-position="bottom-right">Sağ Alt</button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tema:</label>
                        <div class="flex gap-2">
                            <button type="button" class="preview-theme px-2 py-1 text-xs border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-theme="dark">
                                <i class="fas fa-moon mr-1"></i> Koyu
                            </button>
                            <button type="button" class="preview-theme px-2 py-1 text-xs border rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-theme="light">
                                <i class="fas fa-sun mr-1"></i> Açık
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="relative border dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 p-2 h-40">
                    <div class="absolute w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 text-xs">
                        <span>Sayfa içeriği</span>
                    </div>
                    <div id="cookie-consent-preview" class="absolute p-3 border dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 shadow-md w-full bottom-0 left-0">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700 dark:text-gray-300 mr-4 truncate" id="preview-text">
                                <?php echo $cookieSettings['cookie_consent_text']; ?>
                                <?php if ($cookieSettings['cookie_consent_show_link']): ?>
                                <a href="/cerezler" class="text-blue-600 dark:text-blue-400 hover:underline ml-1" id="preview-link"><?php echo $cookieSettings['cookie_consent_link_text']; ?></a>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs whitespace-nowrap flex-shrink-0" id="preview-button">
                                <?php echo $cookieSettings['cookie_consent_button_text']; ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Not: Bu bir önizlemedir. Gerçek görünüm site temanıza göre değişebilir.</p>
            </div>
            
            <!-- Kaydet Butonu -->
            <div class="flex justify-end">
                <button type="submit" name="save_settings" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i> Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Önizleme güncellemesi
document.addEventListener('DOMContentLoaded', function() {
    const previewText = document.getElementById('preview-text');
    const previewButton = document.getElementById('preview-button');
    const previewLink = document.getElementById('preview-link');
    const previewContainer = document.getElementById('cookie-consent-preview');
    
    // Pozisyon butonları
    const positionButtons = document.querySelectorAll('.preview-position');
    positionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const position = this.getAttribute('data-position');
            
            // Aktif buton stilini güncelle
            positionButtons.forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500'));
            this.classList.add('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500');
            
            // Pozisyon seçicisini güncelle
            document.getElementById('cookie_consent_position').value = position;
            
            // Önizlemeyi güncelle
            updatePreviewPosition(position);
        });
    });
    
    // Sayfa yüklendiğinde aktif pozisyonu seç
    const activePosition = document.getElementById('cookie_consent_position').value;
    positionButtons.forEach(button => {
        if (button.getAttribute('data-position') === activePosition) {
            button.classList.add('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500');
        }
    });
    updatePreviewPosition(activePosition);
    
    // Pozisyon önizlemesini güncelle
    function updatePreviewPosition(position) {
        // Önce tüm pozisyon sınıflarını kaldır
        previewContainer.classList.remove('bottom-0', 'top-0', 'left-0', 'right-0', 'w-full', 'w-64');
        
        // Yeni pozisyon sınıflarını ekle
        switch(position) {
            case 'top':
                previewContainer.classList.add('top-0', 'left-0', 'w-full');
                break;
            case 'bottom-left':
                previewContainer.classList.add('bottom-0', 'left-0', 'w-64');
                break;
            case 'bottom-right':
                previewContainer.classList.add('bottom-0', 'right-0', 'w-64');
                break;
            default: // bottom
                previewContainer.classList.add('bottom-0', 'left-0', 'w-full');
        }
    }
    
    // Metin güncelleme
    document.getElementById('cookie_consent_text').addEventListener('input', function() {
        const linkElement = previewLink ? previewLink.outerHTML : '';
        previewText.innerHTML = this.value + (document.getElementById('cookie_consent_show_link').checked ? ' ' + linkElement : '');
    });
    
    // Buton metni güncelleme
    document.getElementById('cookie_consent_button_text').addEventListener('input', function() {
        previewButton.textContent = this.value;
    });
    
    // Tema butonları
    const themeButtons = document.querySelectorAll('.preview-theme');
    themeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            
            // Aktif buton stilini güncelle
            themeButtons.forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500'));
            this.classList.add('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500');
            
            // Tema seçicisini güncelle
            document.getElementById('cookie_consent_theme').value = theme;
            
            // Önizlemeyi güncelle
            updatePreviewTheme(theme);
        });
    });
    
    // Sayfa yüklendiğinde aktif temayı seç
    const activeTheme = document.getElementById('cookie_consent_theme').value;
    themeButtons.forEach(button => {
        if (button.getAttribute('data-theme') === activeTheme) {
            button.classList.add('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500');
        }
    });
    updatePreviewTheme(activeTheme);
    
    // Tema önizlemesini güncelle
    function updatePreviewTheme(theme) {
        if (theme === 'dark') {
            previewContainer.classList.add('bg-gray-800', 'text-white');
            previewContainer.classList.remove('bg-white', 'text-gray-800');
            
            if (previewText) previewText.classList.add('text-gray-300');
            if (previewText) previewText.classList.remove('text-gray-700');
        } else {
            previewContainer.classList.add('bg-white', 'text-gray-800');
            previewContainer.classList.remove('bg-gray-800', 'text-white');
            
            if (previewText) previewText.classList.add('text-gray-700');
            if (previewText) previewText.classList.remove('text-gray-300');
        }
    }
    
    // Bağlantı gösterme/gizleme
    document.getElementById('cookie_consent_show_link').addEventListener('change', function() {
        if (this.checked && previewLink) {
            previewLink.style.display = 'inline';
        } else if (previewLink) {
            previewLink.style.display = 'none';
        } else if (this.checked) {
            const linkText = document.getElementById('cookie_consent_link_text').value;
            const newLink = document.createElement('a');
            newLink.href = '/cerezler';
            newLink.className = 'text-blue-600 dark:text-blue-400 hover:underline ml-1';
            newLink.id = 'preview-link';
            newLink.textContent = linkText;
            previewText.appendChild(document.createTextNode(' '));
            previewText.appendChild(newLink);
        }
    });
    
    // Bağlantı metni güncelleme
    document.getElementById('cookie_consent_link_text').addEventListener('input', function() {
        if (previewLink) {
            previewLink.textContent = this.value;
        }
    });
    
    // Tema başlangıç ayarı
    const initialTheme = document.getElementById('cookie_consent_theme').value;
    if (initialTheme === 'dark') {
        previewContainer.classList.add('bg-gray-800', 'text-white');
    } else {
        previewContainer.classList.add('bg-white', 'text-gray-800');
    }

    // Tema seçimi butonları
    const themeSelectButtons = document.querySelectorAll('.theme-select');
    themeSelectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            
            // Aktif buton stilini güncelle
            themeSelectButtons.forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500'));
            this.classList.add('bg-blue-100', 'border-blue-500', 'dark:bg-blue-900', 'dark:border-blue-500');
            
            // Select değerini güncelle
            document.getElementById('cookie_consent_theme').value = theme;
            
            // Önizleme butonlarını güncelle
            themeButtons.forEach(btn => {
                if (btn.getAttribute('data-theme') === theme) {
                    btn.click();
                }
            });
        });
    });
});
</script> 