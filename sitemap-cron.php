<?php
/**
 * Sitemap Cron Job Script
 * Bu script cron job ile çalıştırılarak sitemap'i otomatik oluşturur
 */

// Hata ayıklama için
error_reporting(E_ALL);
ini_set('display_errors', 0); // Cron'da display_errors kapalı olmalı

// Base path tanımı
define('APP_ROOT', __DIR__);
define('BASE_PATH', __DIR__);

require_once 'config/database.php';
require_once 'includes/functions.php';

// Log dosyası
$log_file = APP_ROOT . '/logs/sitemap_cron.log';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    
    // Logs dizini yoksa oluştur
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

try {
    writeLog("Sitemap cron job başlatıldı");
    
    // Sitemap etkin mi kontrol et
    $sitemap_enabled = getSiteSetting('sitemap_enabled', 1);
    if (!$sitemap_enabled) {
        writeLog("Sitemap devre dışı - işlem sonlandırıldı");
        echo "Sitemap disabled\n";
        exit(0);
    }
    
    // Sitemap oluştur
    if (generateSitemapXML()) {
        writeLog("Sitemap başarıyla oluşturuldu");
        
        // Son cron çalışma zamanını kaydet
        updateSiteSetting('sitemap_last_cron_run', date('Y-m-d H:i:s'));
        
        echo "SUCCESS: Sitemap created successfully at " . date('Y-m-d H:i:s') . "\n";
        writeLog("Cron job başarıyla tamamlandı");
        
    } else {
        writeLog("HATA: Sitemap oluşturulamadı");
        echo "ERROR: Failed to create sitemap\n";
        exit(1);
    }
    
} catch (Exception $e) {
    $error_message = "Cron job hatası: " . $e->getMessage();
    writeLog($error_message);
    echo "ERROR: " . $error_message . "\n";
    exit(1);
}
?>
