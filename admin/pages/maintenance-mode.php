<?php
// Bakım modu işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_maintenance_settings'])) {
        // Bakım modu ayarlarını güncelle
        $settings = [
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'maintenance_message' => sanitizeInput($_POST['maintenance_message'] ?? ''),
            'maintenance_end_time' => isset($_POST['maintenance_end_time']) && !empty($_POST['maintenance_end_time']) ? 
                                     date('Y-m-d H:i:s', strtotime($_POST['maintenance_end_time'])) : null,
            'maintenance_title' => sanitizeInput($_POST['maintenance_title'] ?? ''),
            'maintenance_show_timer' => isset($_POST['maintenance_show_timer']) ? 1 : 0,
            'maintenance_allow_admin' => isset($_POST['maintenance_allow_admin']) ? 1 : 0,
            'maintenance_allowed_ips' => sanitizeInput($_POST['maintenance_allowed_ips'] ?? ''),
            'maintenance_bg_color' => sanitizeInput($_POST['maintenance_bg_color'] ?? '#f3f4f6'),
            'maintenance_text_color' => sanitizeInput($_POST['maintenance_text_color'] ?? '#1f2937'),
            'maintenance_contact_email' => sanitizeInput($_POST['maintenance_contact_email'] ?? '')
        ];
        
        try {
            foreach ($settings as $key => $value) {
                updateSiteSetting($key, $value);
            }
            $_SESSION['success_message'] = "Bakım modu ayarları başarıyla güncellendi!";
            $_SESSION['form_submitted'] = true;
        } catch (Exception $e) {
            $error_message = "Ayarlar güncellenirken hata oluştu: " . $e->getMessage();
        }
    }
}

// Session'dan başarı mesajını al
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Mevcut ayarları yükle
$maintenance_settings = [
    'maintenance_mode' => getSiteSetting('maintenance_mode', 0),
    'maintenance_message' => getSiteSetting('maintenance_message', 'Sitemiz bakım modundadır. Kısa süre içinde tekrar hizmetinizde olacağız.'),
    'maintenance_end_time' => getSiteSetting('maintenance_end_time', ''),
    'maintenance_title' => getSiteSetting('maintenance_title', 'Bakım Modu'),
    'maintenance_show_timer' => getSiteSetting('maintenance_show_timer', 1),
    'maintenance_allow_admin' => getSiteSetting('maintenance_allow_admin', 1),
    'maintenance_allowed_ips' => getSiteSetting('maintenance_allowed_ips', ''),
    'maintenance_bg_color' => getSiteSetting('maintenance_bg_color', '#f3f4f6'),
    'maintenance_text_color' => getSiteSetting('maintenance_text_color', '#1f2937'),
    'maintenance_contact_email' => getSiteSetting('maintenance_contact_email', '')
];

// Bakım modu bitiş zamanı için varsayılan değer (şu andan 1 saat sonra)
$default_end_time = date('Y-m-d\TH:i', strtotime('+1 hour'));
$current_end_time = !empty($maintenance_settings['maintenance_end_time']) ? 
                   date('Y-m-d\TH:i', strtotime($maintenance_settings['maintenance_end_time'])) : 
                   $default_end_time;
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

    <!-- Bakım Modu Durumu -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bakım Modu Durumu</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    <?php if ($maintenance_settings['maintenance_mode']): ?>
                        <span class="text-red-600 dark:text-red-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Bakım modu şu anda aktif
                        </span>
                    <?php else: ?>
                        <span class="text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-1"></i> Site normal şekilde çalışıyor
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex items-center">
                <?php if ($maintenance_settings['maintenance_end_time'] && $maintenance_settings['maintenance_mode']): ?>
                <div class="mr-4 text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Bakım bitiş zamanı:</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?php echo date('d.m.Y H:i', strtotime($maintenance_settings['maintenance_end_time'])); ?>
                    </p>
                    <?php 
                    $now = new DateTime();
                    $end = new DateTime($maintenance_settings['maintenance_end_time']);
                    $interval = $now->diff($end);
                    if ($end > $now): 
                    ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?php echo $interval->format('%a gün %h saat %i dakika kaldı'); ?>
                    </p>
                    <?php else: ?>
                    <p class="text-xs text-red-500 dark:text-red-400">
                        Süre doldu
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bakım Modu Ayarları -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Bakım Modu Ayarları</h3>
        
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Temel Ayarlar -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Temel Ayarlar</h4>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $maintenance_settings['maintenance_mode'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maintenance_mode" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Bakım modunu etkinleştir</label>
                    </div>
                    
                    <div>
                        <label for="maintenance_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bakım Modu Başlığı</label>
                        <input type="text" id="maintenance_title" name="maintenance_title" value="<?php echo htmlspecialchars($maintenance_settings['maintenance_title']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="maintenance_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bakım Modu Mesajı</label>
                        <textarea id="maintenance_message" name="maintenance_message" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"><?php echo htmlspecialchars($maintenance_settings['maintenance_message']); ?></textarea>
                    </div>
                    
                    <div>
                        <label for="maintenance_end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bakım Bitiş Zamanı</label>
                        <input type="datetime-local" id="maintenance_end_time" name="maintenance_end_time" value="<?php echo $current_end_time; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bakım modunun otomatik olarak devre dışı bırakılacağı zamanı belirtin.</p>
                    </div>
                </div>

                <!-- Gelişmiş Ayarlar -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Gelişmiş Ayarlar</h4>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="maintenance_show_timer" name="maintenance_show_timer" <?php echo $maintenance_settings['maintenance_show_timer'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maintenance_show_timer" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Geri sayım sayacını göster</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="maintenance_allow_admin" name="maintenance_allow_admin" <?php echo $maintenance_settings['maintenance_allow_admin'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maintenance_allow_admin" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Admin kullanıcılarına erişim izni ver</label>
                    </div>
                    
                    <div>
                        <label for="maintenance_contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">İletişim E-posta Adresi</label>
                        <input type="email" id="maintenance_contact_email" name="maintenance_contact_email" value="<?php echo htmlspecialchars($maintenance_settings['maintenance_contact_email']); ?>" placeholder="ornek@siteadi.com" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bakım sayfasında gösterilecek iletişim e-posta adresi.</p>
                    </div>

                    
                    <div>
                        <label for="maintenance_allowed_ips" class="block text-sm font-medium text-gray-700 dark:text-gray-300">İzin Verilen IP Adresleri</label>
                        <textarea id="maintenance_allowed_ips" name="maintenance_allowed_ips" rows="2" placeholder="127.0.0.1, 192.168.1.1" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"><?php echo htmlspecialchars($maintenance_settings['maintenance_allowed_ips']); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Virgülle ayrılmış IP adresleri. Bu IP'lerden gelen ziyaretçiler bakım modunu görmeyecek.</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="maintenance_bg_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Arka Plan Rengi</label>
                            <div class="mt-1 flex">
                                <input type="color" id="maintenance_bg_color" name="maintenance_bg_color" value="<?php echo htmlspecialchars($maintenance_settings['maintenance_bg_color']); ?>" class="h-8 w-8 border border-gray-300 rounded">
                                <input type="text" value="<?php echo htmlspecialchars($maintenance_settings['maintenance_bg_color']); ?>" class="ml-2 flex-1 px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" disabled>
                            </div>
                        </div>
                        
                        <div>
                            <label for="maintenance_text_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metin Rengi</label>
                            <div class="mt-1 flex">
                                <input type="color" id="maintenance_text_color" name="maintenance_text_color" value="<?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>" class="h-8 w-8 border border-gray-300 rounded">
                                <input type="text" value="<?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>" class="ml-2 flex-1 px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" name="update_maintenance_settings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>
                    Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- Bakım Modu Önizleme -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bakım Modu Önizleme</h3>
        
        <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
            <div class="p-8 text-center" style="background-color: <?php echo htmlspecialchars($maintenance_settings['maintenance_bg_color']); ?>; color: <?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>">
                <div class="max-w-md mx-auto">
                    <h2 class="text-2xl font-bold mb-4" style="color: <?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>">
                        <?php echo htmlspecialchars($maintenance_settings['maintenance_title']); ?>
                    </h2>
                    
                    <p class="mb-6" style="color: <?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>">
                        <?php echo nl2br(htmlspecialchars($maintenance_settings['maintenance_message'])); ?>
                    </p>
                    
                    <?php if ($maintenance_settings['maintenance_show_timer'] && !empty($maintenance_settings['maintenance_end_time'])): ?>
                    <div class="mb-6">
                        <div class="text-sm mb-1" style="color: <?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>">Kalan Süre:</div>
                        <div class="grid grid-cols-4 gap-2 max-w-xs mx-auto">
                            <div class="p-2 rounded" style="background-color: rgba(0,0,0,0.1);">
                                <div class="text-xl font-bold" id="preview-days">00</div>
                                <div class="text-xs">Gün</div>
                            </div>
                            <div class="p-2 rounded" style="background-color: rgba(0,0,0,0.1);">
                                <div class="text-xl font-bold" id="preview-hours">00</div>
                                <div class="text-xs">Saat</div>
                            </div>
                            <div class="p-2 rounded" style="background-color: rgba(0,0,0,0.1);">
                                <div class="text-xl font-bold" id="preview-minutes">00</div>
                                <div class="text-xs">Dakika</div>
                            </div>
                            <div class="p-2 rounded" style="background-color: rgba(0,0,0,0.1);">
                                <div class="text-xl font-bold" id="preview-seconds">00</div>
                                <div class="text-xs">Saniye</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($maintenance_settings['maintenance_contact_email'])): ?>
                    <div class="mt-4">
                        <a href="#" class="inline-block px-4 py-2 rounded" style="background-color: rgba(0,0,0,0.1); color: <?php echo htmlspecialchars($maintenance_settings['maintenance_text_color']); ?>">
                            <i class="fas fa-envelope mr-2"></i> İletişime Geç
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
// Renk seçici için
document.getElementById('maintenance_bg_color').addEventListener('input', function(e) {
    document.querySelector('.p-8.text-center').style.backgroundColor = e.target.value;
    document.querySelector('input[type="text"][disabled]').value = e.target.value;
});

document.getElementById('maintenance_text_color').addEventListener('input', function(e) {
    const elements = document.querySelectorAll('.p-8.text-center h2, .p-8.text-center p, .p-8.text-center div');
    elements.forEach(el => el.style.color = e.target.value);
    document.querySelectorAll('input[type="text"][disabled]')[1].value = e.target.value;
});

// Önizleme için geri sayım sayacı
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($maintenance_settings['maintenance_show_timer'] && !empty($maintenance_settings['maintenance_end_time'])): ?>
    const previewEndTime = new Date("<?php echo $maintenance_settings['maintenance_end_time']; ?>").getTime();
    
    function updatePreviewCountdown() {
        const now = new Date().getTime();
        const distance = previewEndTime - now;
        
        if (distance < 0) {
            document.getElementById("preview-days").textContent = "00";
            document.getElementById("preview-hours").textContent = "00";
            document.getElementById("preview-minutes").textContent = "00";
            document.getElementById("preview-seconds").textContent = "00";
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById("preview-days").textContent = days.toString().padStart(2, "0");
        document.getElementById("preview-hours").textContent = hours.toString().padStart(2, "0");
        document.getElementById("preview-minutes").textContent = minutes.toString().padStart(2, "0");
        document.getElementById("preview-seconds").textContent = seconds.toString().padStart(2, "0");
    }
    
    updatePreviewCountdown();
    setInterval(updatePreviewCountdown, 1000);
    <?php endif; ?>
    
    // İletişim e-posta alanı değiştiğinde önizlemeyi güncelle
    const contactEmailInput = document.getElementById('maintenance_contact_email');
    if (contactEmailInput) {
        contactEmailInput.addEventListener('input', function() {
            const contactLinkContainer = document.querySelector('.mt-4');
            if (this.value.trim() === '') {
                if (contactLinkContainer) {
                    contactLinkContainer.style.display = 'none';
                }
            } else {
                if (contactLinkContainer) {
                    contactLinkContainer.style.display = 'block';
                } else {
                    const previewContainer = document.querySelector('.max-w-md.mx-auto');
                    if (previewContainer) {
                        const newContactLink = document.createElement('div');
                        newContactLink.className = 'mt-4';
                        newContactLink.innerHTML = '<a href="#" class="inline-block px-4 py-2 rounded" style="background-color: rgba(0,0,0,0.1); color: ' + 
                            document.querySelector('input[name="maintenance_text_color"]').value + 
                            '"><i class="fas fa-envelope mr-2"></i> İletişime Geç</a>';
                        previewContainer.appendChild(newContactLink);
                    }
                }
            }
        });
    }
});

// Form gönderildikten sonra sayfayı yenile
<?php if (isset($_SESSION['form_submitted']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
window.onload = function() {
    // Sayfayı yeniden yükle, POST verilerini temizle
    window.location.href = '?page=maintenance-mode';
};
<?php 
    unset($_SESSION['form_submitted']);
endif; 
?>
</script> 