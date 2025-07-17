<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Hata ayıklama için log tutma
$logFile = '../logs/api_settings_' . date('Y-m-d') . '.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Veritabanı bağlantısı
    $db = new Database();
    
    // İstenen ayarları al
    $requestedSettings = isset($_GET['settings']) ? explode(',', $_GET['settings']) : [];
    logMessage("İstenen ayarlar: " . implode(', ', $requestedSettings));
    
    // Sonuç dizisi
    $result = [];
    
    if (empty($requestedSettings)) {
        // Tüm ayarları getir
        logMessage("Tüm ayarlar istendi");
        $query = "SELECT setting_key, setting_value FROM settings";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
    } else {
        // İstenen ayarları getir
        $placeholders = implode(',', array_fill(0, count($requestedSettings), '?'));
        logMessage("Belirli ayarlar istendi. SQL sorgusu: SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
        
        $query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)";
        $stmt = $db->prepare($query);
        $stmt->execute($requestedSettings);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['setting_key']] = $row['setting_value'];
            logMessage("Ayar bulundu: {$row['setting_key']} = {$row['setting_value']}");
        }
        
        // İstenen ayarlar yoksa varsayılan değerleri ekle
        $defaultValues = [
            'cookie_consent_enabled' => '1',
            'cookie_consent_text' => 'Bu web sitesi, size en iyi deneyimi sunmak için çerezler kullanır.',
            'cookie_consent_button_text' => 'Kabul Et',
            'cookie_consent_position' => 'bottom',
            'cookie_consent_theme' => 'dark',
            'cookie_consent_show_link' => '1',
            'cookie_consent_link_text' => 'Daha fazla bilgi',
            'cookie_analytics_enabled' => '1',
            'cookie_marketing_enabled' => '1'
        ];
        
        foreach ($requestedSettings as $key) {
            if (!isset($result[$key]) && isset($defaultValues[$key])) {
                $result[$key] = $defaultValues[$key];
                logMessage("Varsayılan değer kullanıldı: $key = {$defaultValues[$key]}");
            }
        }
    }
    
    logMessage("Döndürülen sonuç: " . json_encode($result));
    
    // Sonucu JSON olarak döndür
    echo json_encode($result);
    
} catch (Exception $e) {
    logMessage("HATA: " . $e->getMessage());
    
    // Hata durumunda varsayılan değerleri döndür
    $defaultValues = [
        'cookie_consent_enabled' => '1',
        'cookie_consent_text' => 'Bu web sitesi, size en iyi deneyimi sunmak için çerezler kullanır.',
        'cookie_consent_button_text' => 'Kabul Et',
        'cookie_consent_position' => 'bottom',
        'cookie_consent_theme' => 'dark',
        'cookie_consent_show_link' => '1',
        'cookie_consent_link_text' => 'Daha fazla bilgi',
        'cookie_analytics_enabled' => '1',
        'cookie_marketing_enabled' => '1'
    ];
    
    $result = [];
    foreach ($requestedSettings as $key) {
        if (isset($defaultValues[$key])) {
            $result[$key] = $defaultValues[$key];
        }
    }
    
    // Sonuç boşsa tüm varsayılan değerleri ekle
    if (empty($result)) {
        $result = $defaultValues;
    }
    
    echo json_encode($result);
}
?> 