<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = "Çerez Bildirimi Test";
$metaDescription = "Çerez bildirimi test sayfası";

// Header'ı dahil et
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">Çerez Bildirimi Test Sayfası</h1>
        
        <div class="mb-6">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Çerez Ayarları</h2>
            <div id="cookie-settings" class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                <p>Yükleniyor...</p>
            </div>
        </div>
        
        <div class="mb-6">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Çerez Bildirimi Kontrolleri</h2>
            <div class="space-y-4">
                <button id="show-cookie-consent" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Çerez Bildirimini Göster
                </button>
                
                <button id="delete-cookie" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Çerez Kabul Kaydını Sil
                </button>
            </div>
        </div>
        
        <div class="mb-6">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Hata Ayıklama</h2>
            <div id="debug-output" class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                <p>JavaScript konsolu kontrol edin.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Çerez ayarlarını göster
    fetch('/api/get-settings.php?settings=cookie_consent_enabled,cookie_consent_text,cookie_consent_button_text,cookie_consent_position,cookie_consent_theme,cookie_consent_show_link,cookie_consent_link_text,cookie_analytics_enabled,cookie_marketing_enabled')
        .then(response => response.json())
        .then(data => {
            const settingsDiv = document.getElementById('cookie-settings');
            let html = '<ul class="space-y-2">';
            
            for (const [key, value] of Object.entries(data)) {
                html += `<li><strong>${key}:</strong> ${value}</li>`;
            }
            
            html += '</ul>';
            settingsDiv.innerHTML = html;
            
            // Debug çıktısına ekle
            document.getElementById('debug-output').innerHTML += '<p class="mt-2 text-green-600">Ayarlar başarıyla alındı.</p>';
        })
        .catch(error => {
            document.getElementById('cookie-settings').innerHTML = `<p class="text-red-500">Hata: ${error.message}</p>`;
            document.getElementById('debug-output').innerHTML += `<p class="mt-2 text-red-500">Hata: ${error.message}</p>`;
        });
    
    // Çerez bildirimini göster butonu
    document.getElementById('show-cookie-consent').addEventListener('click', function() {
        // Varsa mevcut bildirimi kaldır
        const existingConsent = document.getElementById('cookie-consent');
        if (existingConsent) {
            existingConsent.remove();
        }
        
        // Bildirimi göster
        initCookieConsent();
        
        document.getElementById('debug-output').innerHTML += '<p class="mt-2 text-blue-600">Çerez bildirimi gösterildi.</p>';
    });
    
    // Çerez kabul kaydını sil butonu
    document.getElementById('delete-cookie').addEventListener('click', function() {
        document.cookie = "cookie_consent_accepted=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax";
        
        document.getElementById('debug-output').innerHTML += '<p class="mt-2 text-orange-600">Çerez kabul kaydı silindi.</p>';
    });
});
</script>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?> 