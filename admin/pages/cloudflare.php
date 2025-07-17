<?php
// Yetki kontrolü
if (!isUserAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Ayarları kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'site_key' => $_POST['site_key'] ?? '',
        'secret_key' => $_POST['secret_key'] ?? '',
        'login_enabled' => isset($_POST['login_enabled']) ? 1 : 0,
        'register_enabled' => isset($_POST['register_enabled']) ? 1 : 0,
        'contact_enabled' => isset($_POST['contact_enabled']) ? 1 : 0,
        'article_enabled' => isset($_POST['article_enabled']) ? 1 : 0,
        'difficulty' => $_POST['difficulty'] ?? 'normal',
        'theme' => $_POST['theme'] ?? 'light',
        'language' => $_POST['language'] ?? 'tr'
    ];

    if (updateCloudflareSettings($settings)) {
        $success_message = 'Cloudflare ayarları başarıyla güncellendi.';
    } else {
        $error_message = 'Ayarlar kaydedilirken bir hata oluştu.';
    }
}

// Mevcut ayarları al
$settings = getCloudflareSettings();
?>

<div class="space-y-6">
    <?php if ($success_message): ?>
    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
        <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <form id="cloudflare-all-form" method="POST" class="space-y-6">
        <!-- Genel Ayarlar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Genel Ayarlar</h3>
                <p class="text-gray-600 dark:text-gray-400">Cloudflare CAPTCHA sistemini aktif edin ve anahtar bilgilerini girin.</p>
            </div>
            <div class="p-6 space-y-4">
                <label for="is_enabled" class="flex items-center cursor-pointer select-none p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900 transition mb-4">
                    <input type="checkbox" id="is_enabled" name="is_enabled" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?php echo $settings['is_enabled'] ? 'checked' : ''; ?>>
                    <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">CAPTCHA Sistemini Aktifleştir</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site Anahtarı</label>
                        <input type="text" name="site_key" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" value="<?php echo htmlspecialchars($settings['site_key']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gizli Anahtar</label>
                        <input type="text" name="secret_key" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" value="<?php echo htmlspecialchars($settings['secret_key']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- CAPTCHA Görünüm Ayarları -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Görünüm Ayarları</h3>
                <p class="text-gray-600 dark:text-gray-400">Zorluk, tema ve dil seçeneklerini belirleyin.</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zorluk Seviyesi</label>
                        <select name="difficulty" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="easy" <?php echo $settings['difficulty'] == 'easy' ? 'selected' : ''; ?>>Kolay</option>
                            <option value="normal" <?php echo $settings['difficulty'] == 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="hard" <?php echo $settings['difficulty'] == 'hard' ? 'selected' : ''; ?>>Zor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tema</label>
                        <select name="theme" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="light" <?php echo $settings['theme'] == 'light' ? 'selected' : ''; ?>>Açık</option>
                            <option value="dark" <?php echo $settings['theme'] == 'dark' ? 'selected' : ''; ?>>Koyu</option>
                            <option value="auto" <?php echo $settings['theme'] == 'auto' ? 'selected' : ''; ?>>Otomatik</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dil</label>
                        <select name="language" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="tr" <?php echo $settings['language'] == 'tr' ? 'selected' : ''; ?>>Türkçe</option>
                            <option value="en" <?php echo $settings['language'] == 'en' ? 'selected' : ''; ?>>İngilizce</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- CAPTCHA Aktif Sayfalar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">CAPTCHA Aktif Sayfalar</h3>
                <p class="text-gray-600 dark:text-gray-400">Hangi sayfalarda CAPTCHA kullanılacağını seçin.</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label for="login_enabled" class="flex items-center cursor-pointer select-none p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
                        <input type="checkbox" id="login_enabled" name="login_enabled" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?php echo $settings['login_enabled'] ? 'checked' : ''; ?>>
                        <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">Giriş Sayfası</span>
                    </label>
                    <label for="register_enabled" class="flex items-center cursor-pointer select-none p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
                        <input type="checkbox" id="register_enabled" name="register_enabled" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?php echo $settings['register_enabled'] ? 'checked' : ''; ?>>
                        <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">Kayıt Sayfası</span>
                    </label>
                    <label for="contact_enabled" class="flex items-center cursor-pointer select-none p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
                        <input type="checkbox" id="contact_enabled" name="contact_enabled" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?php echo $settings['contact_enabled'] ? 'checked' : ''; ?>>
                        <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">İletişim Sayfası</span>
                    </label>
                    <label for="article_enabled" class="flex items-center cursor-pointer select-none p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900 transition">
                        <input type="checkbox" id="article_enabled" name="article_enabled" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?php echo $settings['article_enabled'] ? 'checked' : ''; ?>>
                        <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">Makale Ekleme Sayfası</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">Kaydet</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cloudflare-all-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
                submitBtn.disabled = true;
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    showSuccessMessage('Cloudflare ayarları');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    setTimeout(() => { location.reload(); }, 1000);
                })
                .catch(error => {
                    showErrorMessage('Bir hata oluştu. Lütfen tekrar deneyin.');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });
    }
    function showSuccessMessage(type) {
        removeExistingAlerts();
        const alertDiv = document.createElement('div');
        alertDiv.className = 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6';
        alertDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + type + ' başarıyla güncellendi.';
        const container = document.querySelector('.space-y-6');
        container.insertBefore(alertDiv, container.firstChild);
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s';
            alertDiv.style.opacity = '0';
            setTimeout(() => { alertDiv.remove(); }, 500);
        }, 4000);
    }
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
            setTimeout(() => { alertDiv.remove(); }, 500);
        }, 4000);
    }
    function removeExistingAlerts() {
        const existingAlerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        existingAlerts.forEach(alert => alert.remove());
    }
    const successAlert = document.querySelector('.bg-green-100');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(() => { successAlert.remove(); }, 500);
        }, 4000);
    }
});
</script>