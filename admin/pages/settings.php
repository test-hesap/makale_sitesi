<?php
// Form gönderildiğinde ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_message = '';
    
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'general_settings') {
        $site_title = $_POST['site_title'] ?? '';
        $site_description = $_POST['site_description'] ?? '';
        $site_logo = $_POST['site_logo'] ?? '';
        $site_logo_dark = $_POST['site_logo_dark'] ?? '';
        
        // Favicon kaldırma işlemi
        if (isset($_POST['remove_favicon']) && $_POST['remove_favicon'] == 1) {
            // Favicon ayarını veritabanından kaldır
            updateSetting('site_favicon', '');
            
            // Mevcut favicon dosyasını silmek için yolu al
            $current_favicon = getSiteSetting('site_favicon');
            if (!empty($current_favicon) && file_exists('../' . $current_favicon)) {
                @unlink('../' . $current_favicon);
            }
        }
        
        // Favicon upload işlemi
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/favicon/';
            
            // Klasör yoksa oluştur
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['ico', 'png', 'jpg', 'jpeg', 'gif', 'svg'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'favicon.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_path)) {
                    // Yolu kaydederken başında slash olmamalı, çünkü header'da ekleniyor
                    updateSetting('site_favicon', 'assets/images/favicon/' . $new_filename . '?t=' . time());
                    
                    // Önbelleklerin temizlendiğinden emin olmak için
                    clearstatcache();
                } else {
                    $_SESSION['error_message'] = "Favicon yüklenirken bir hata oluştu.";
                }
            } else {
                $_SESSION['error_message'] = "Sadece ICO, PNG, JPG, JPEG, GIF ve SVG dosyaları yüklenebilir.";
            }
        }

        updateSetting('site_title', $site_title);
        updateSetting('site_description', $site_description);
        updateSetting('site_logo', $site_logo);
        updateSetting('site_logo_dark', $site_logo_dark);

        $success_message = "Genel ayarlar başarıyla güncellendi.";
    }
    
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'email_settings') {
        $smtp_host = $_POST['smtp_host'] ?? '';
        $smtp_port = $_POST['smtp_port'] ?? '';
        $smtp_username = $_POST['smtp_username'] ?? '';
        $smtp_password = $_POST['smtp_password'] ?? '';
        
        updateSetting('smtp_host', $smtp_host);
        updateSetting('smtp_port', $smtp_port);
        updateSetting('smtp_username', $smtp_username);
        if (!empty($smtp_password)) {
            updateSetting('smtp_password', $smtp_password);
        }
        
        $success_message = "E-posta ayarları başarıyla güncellendi.";
    }
    
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'seo_settings') {
        $canonical_tag = $_POST['canonical_tag'] ?? '';
        $seo_description = $_POST['seo_description'] ?? '';
        $seo_tags = $_POST['seo_tags'] ?? '';
        
        updateSetting('canonical_tag', $canonical_tag);
        updateSetting('seo_description', $seo_description);
        updateSetting('seo_tags', $seo_tags);
        
        $success_message = "SEO ayarları başarıyla güncellendi.";
    }
    
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'social_media_settings') {
        $social_facebook = $_POST['social_facebook'] ?? '';
        $social_twitter = $_POST['social_twitter'] ?? '';
        $social_instagram = $_POST['social_instagram'] ?? '';
        $social_youtube = $_POST['social_youtube'] ?? '';
        
        updateSetting('social_facebook', $social_facebook);
        updateSetting('social_twitter', $social_twitter);
        updateSetting('social_instagram', $social_instagram);
        updateSetting('social_youtube', $social_youtube);
        
        $success_message = "Sosyal medya ayarları başarıyla güncellendi.";
    }
    
    // POST işleminden sonra JavaScript ile yenileme yapacağız
    if (!empty($success_message)) {
        $_SESSION['success_message'] = $success_message;
        $_SESSION['form_submitted'] = true;
    }
}

// Gereksiz session değişkenlerini temizle
if (isset($_SESSION['success_message']) && empty($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['form_submitted']) && !$_POST) {
    unset($_SESSION['form_submitted']);
}

// Session'dan başarı mesajını al
$success = '';
if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Session'dan hata mesajını al
$error = '';
if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (!empty($success)):
?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<!-- Site Ayarları -->
<div class="space-y-6">
    <!-- Genel Ayarlar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Genel Ayarlar</h3>
            <p class="text-gray-600 dark:text-gray-400">Site genel ayarlarını yönetin</p>
        </div>
        <div class="p-6">
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="form_type" value="general_settings">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site Başlığı</label>
                        <input type="text" name="site_title" value="<?php echo htmlspecialchars(getSiteSetting('site_title', 'Makale Sitesi')); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site Logosu</label>
                        <input type="text" name="site_logo" value="<?php echo htmlspecialchars(getSiteSetting('site_logo')); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-moon mr-2 text-purple-500"></i>Karanlık Tema Logosu
                        </label>
                        <input type="text" name="site_logo_dark" value="<?php echo htmlspecialchars(getSiteSetting('site_logo_dark')); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Karanlık tema etkinken görüntülenecek logo. Boş bırakırsanız normal logo kullanılacaktır.
                        </p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site Açıklaması</label>
                    <textarea name="site_description" rows="3" 
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"><?php echo htmlspecialchars(getSiteSetting('site_description')); ?></textarea>
                </div>
                
                <!-- Favicon Yükleme Bölümü -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-star mr-2 text-yellow-500"></i>Site Favicon'u
                            </label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <input type="file" name="favicon" id="favicon-input" accept=".ico,.png,.jpg,.jpeg,.gif,.svg" 
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        ICO, PNG, JPG, JPEG, GIF veya SVG formatında olmalıdır. Önerilen boyut: 32x32 veya 16x16 piksel
                                    </p>
                                    <?php if(!empty(getSiteSetting('site_favicon'))): ?>
                                    <div class="mt-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="remove_favicon" value="1" class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                                            <span class="ml-2 text-sm text-red-600 dark:text-red-400">Mevcut favicon'u kaldır</span>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php 
                                $current_favicon = getSiteSetting('site_favicon');
                                if (!empty($current_favicon)): 
                                // Önbellek kırıcı olarak timestamp ekle
                                $cache_buster = '?v=' . time();
                                ?>
                                <div class="flex-shrink-0">
                                    <div class="w-16 h-16 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 flex items-center justify-center">
                                        <img src="/<?php echo ltrim($current_favicon, '/') . $cache_buster; ?>" 
                                             alt="Mevcut Favicon" 
                                             class="max-w-full max-h-full object-contain">
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">Mevcut</p>
                                </div>
                                <?php else: ?>
                                <div class="flex-shrink-0">
                                    <div class="w-16 h-16 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-600 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">Yok</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i>Kaydet
                </button>
            </form>
        </div>
    </div>

    <!-- SEO Ayarları -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SEO Ayarları</h3>
            <p class="text-gray-600 dark:text-gray-400">Site SEO ayarlarını yönetin</p>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="seo_settings">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Canonical Etiketi</label>
                    <input type="url" name="canonical_tag" value="<?php echo htmlspecialchars(getSiteSetting('canonical_tag')); ?>" 
                           placeholder="https://example.com" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Site için canonical URL belirtin</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SEO Açıklaması</label>
                    <textarea name="seo_description" rows="4" 
                              placeholder="Site meta açıklaması..." 
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"><?php echo htmlspecialchars(getSiteSetting('seo_description')); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Arama motorları için meta açıklama (150-160 karakter önerilir)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SEO Etiketleri</label>
                    <input type="text" name="seo_tags" value="<?php echo htmlspecialchars(getSiteSetting('seo_tags')); ?>" 
                           placeholder="etiket1, etiket2, etiket3" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Virgülle ayırarak anahtar kelimeler girin</p>
                </div>
                
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Kaydet
                </button>
            </form>
        </div>
    </div>

    <!-- E-posta Ayarları -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">E-posta Ayarları</h3>
            <p class="text-gray-600 dark:text-gray-400">SMTP ayarlarını yapılandırın</p>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="email_settings">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Sunucu</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars(getSiteSetting('smtp_host')); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?php echo htmlspecialchars(getSiteSetting('smtp_port', '587')); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Kullanıcı Adı</label>
                        <input type="text" name="smtp_username" value="<?php echo htmlspecialchars(getSiteSetting('smtp_username')); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Şifre</label>
                        <div class="relative">
                            <input type="password" name="smtp_password" 
                                   placeholder="<?php echo getSiteSetting('smtp_password') ? '••••••••' : 'SMTP şifrenizi girin'; ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <?php if (getSiteSetting('smtp_password')): ?>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <span class="text-green-600 dark:text-green-400" title="Şifre kaydedildi">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Değiştirmek istemiyorsanız boş bırakın</p>
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Kaydet
                </button>
            </form>
        </div>
    </div>

    <!-- Ödeme Ayarları -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ödeme Ayarları</h3>
            <p class="text-gray-600 dark:text-gray-400">Ödeme gateway ayarları</p>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <!-- Stripe -->
                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Stripe</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key</label>
                            <input type="text" value="<?php echo getSiteSetting('stripe_public_key'); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secret Key</label>
                            <input type="password" value="<?php echo getSiteSetting('stripe_secret_key'); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- PayTR -->
                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">PayTR</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Merchant ID</label>
                            <input type="text" value="<?php echo getSiteSetting('paytr_merchant_id'); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Merchant Key</label>
                            <input type="password" value="<?php echo getSiteSetting('paytr_merchant_key'); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Merchant Salt</label>
                            <input type="password" value="<?php echo getSiteSetting('paytr_merchant_salt'); ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Test Modu -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" <?php echo getSiteSetting('payment_test_mode') ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Test Modu Aktif</span>
                    </label>
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Kaydet
                </button>
            </div>
        </div>
    </div>

    <!-- Sosyal Medya Ayarları -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sosyal Medya</h3>
            <p class="text-gray-600 dark:text-gray-400">Sosyal medya hesap bağlantıları</p>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="form_type" value="social_media_settings">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Facebook</label>
                        <input type="url" name="social_facebook" value="<?php echo getSiteSetting('social_facebook'); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Twitter</label>
                        <input type="url" name="social_twitter" value="<?php echo getSiteSetting('social_twitter'); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Instagram</label>
                        <input type="url" name="social_instagram" value="<?php echo getSiteSetting('social_instagram'); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">YouTube</label>
                        <input type="url" name="social_youtube" value="<?php echo getSiteSetting('social_youtube'); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Kaydet
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// AJAX ile form gönderimi
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[method="POST"]');
    
    // Eğer form gönderildiyse sayfayı yenile (fallback)
    <?php if (isset($_SESSION['form_submitted']) && $_SESSION['form_submitted'] === true): ?>
    setTimeout(() => {
        location.reload();
    }, 100);
    <?php 
    unset($_SESSION['form_submitted']); 
    endif; 
    ?>
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
                submitBtn.disabled = true;
                
                // AJAX isteği
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Başarı mesajını göster
                    showSuccessMessage(getFormTypeName(formData.get('form_type')));
                    
                    // Butonu normale döndür
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Sayfayı 1 saniye sonra yenile (değişikliklerin görünmesi için)
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                })
                .catch(error => {
                    console.error('Hata:', error);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    showErrorMessage('Bir hata oluştu. Lütfen tekrar deneyin.');
                });
            }
        });
    });
    
    // Form türüne göre mesaj adını getir
    function getFormTypeName(formType) {
        switch(formType) {
            case 'general_settings': return 'Genel ayarlar';
            case 'seo_settings': return 'SEO ayarları';
            case 'email_settings': return 'E-posta ayarları';
            case 'social_media_settings': return 'Sosyal medya ayarları';
            default: return 'Ayarlar';
        }
    }
    
    // Başarı mesajı göster
    function showSuccessMessage(type) {
        removeExistingAlerts();
        const alertDiv = document.createElement('div');
        alertDiv.className = 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6';
        alertDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + type + ' başarıyla güncellendi.';
        
        const container = document.querySelector('.space-y-6');
        container.insertBefore(alertDiv, container.firstChild);
        
        // 4 saniye sonra kaybol
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s';
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                alertDiv.remove();
            }, 500);
        }, 4000);
    }
    
    // Hata mesajı göster
    function showErrorMessage(message) {
        removeExistingAlerts();
        const alertDiv = document.createElement('div');
        alertDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6';
        alertDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
        
        const container = document.querySelector('.space-y-6');
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s';
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                alertDiv.remove();
            }, 500);
        }, 4000);
    }
    
    // Mevcut uyarıları kaldır
    function removeExistingAlerts() {
        const existingAlerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        existingAlerts.forEach(alert => alert.remove());
    }
    
    // Sayfa yüklendiğinde mevcut başarı mesajı varsa otomatik kaybol
    const successAlert = document.querySelector('.bg-green-100');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.remove();
            }, 500);
        }, 4000);
    }
});
</script>