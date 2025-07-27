<?php
/**
 * Abonelik durumlarını güncelleyen cron işi
 * 
 * Bu betik, aşağıdaki işlemleri gerçekleştirir:
 * 1. Süresi dolan aktif abonelikleri "expired" olarak işaretler
 * 2. Kullanıcıların premium_expires_at değerini günceller
 * 
 * Bu betik günlük olarak çalıştırılmalıdır.
 * Örnek cron komutu: 0 0 * * * php /path/to/subscription-cron.php
 */

// Hata raporlamayı ayarla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Başlangıç zamanı
$start_time = microtime(true);
$log = [];

// Yolu ayarla
$docRoot = dirname(__DIR__);
require_once $docRoot . '/config/database.php';

// Log fonksiyonu
function logMessage($message) {
    global $log;
    $timestamp = date('[Y-m-d H:i:s]');
    $log[] = "$timestamp $message";
    echo "$timestamp $message\n";
}

logMessage("Abonelik güncelleme işlemi başlatılıyor...");

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Süresi dolan aktif abonelikleri "expired" olarak işaretle
    $expiredQuery = "
        UPDATE user_subscriptions 
        SET status = 'expired', updated_at = NOW() 
        WHERE status = 'active' AND end_date < NOW()
    ";
    $expiredStmt = $db->prepare($expiredQuery);
    $expiredStmt->execute();
    $expiredCount = $expiredStmt->rowCount();
    
    logMessage("$expiredCount adet süresi dolan aktif abonelik 'expired' olarak işaretlendi.");
    
    // Süresi dolan iptal edilmiş abonelikleri "expired" olarak işaretle
    $cancelledQuery = "
        UPDATE user_subscriptions 
        SET status = 'expired', updated_at = NOW() 
        WHERE status = 'cancelled' AND end_date < NOW()
    ";
    $cancelledStmt = $db->prepare($cancelledQuery);
    $cancelledStmt->execute();
    $cancelledCount = $cancelledStmt->rowCount();
    
    logMessage("$cancelledCount adet süresi dolan iptal edilmiş abonelik 'expired' olarak işaretlendi.");
    
    // Tüm kullanıcıların premium_expires_at değerini güncelle
    $updateUsersQuery = "
        UPDATE users u
        LEFT JOIN (
            SELECT user_id, MAX(end_date) as max_end_date
            FROM user_subscriptions
            WHERE status IN ('active', 'cancelled')
            GROUP BY user_id
        ) s ON u.id = s.user_id
        SET 
            u.is_premium = CASE WHEN s.max_end_date > NOW() THEN 1 ELSE 0 END,
            u.premium_expires_at = s.max_end_date
        WHERE (u.is_premium = 1 AND s.max_end_date IS NULL) 
           OR (u.is_premium = 0 AND s.max_end_date > NOW())
           OR (u.is_premium = 1 AND s.max_end_date < NOW())
           OR (u.premium_expires_at <> s.max_end_date AND s.max_end_date IS NOT NULL)
    ";
    $updateUsersStmt = $db->prepare($updateUsersQuery);
    $updateUsersStmt->execute();
    $userCount = $updateUsersStmt->rowCount();
    
    logMessage("$userCount kullanıcının premium durumu güncellendi.");
    
    // İstatistikler
    $statsQuery = "
        SELECT 
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
            COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_count,
            COUNT(*) as total_count
        FROM user_subscriptions
    ";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    logMessage("Abonelik istatistikleri:");
    logMessage("- Aktif: {$stats['active_count']}");
    logMessage("- İptal Edilmiş: {$stats['cancelled_count']}");
    logMessage("- Süresi Dolmuş: {$stats['expired_count']}");
    logMessage("- Toplam: {$stats['total_count']}");
    
} catch (Exception $e) {
    logMessage("HATA: " . $e->getMessage());
}

// Bitiş zamanı
$end_time = microtime(true);
$execution_time = ($end_time - $start_time);
logMessage("İşlem tamamlandı. Süre: " . round($execution_time, 2) . " saniye.");

// Log dosyasına yaz
$log_dir = $docRoot . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/subscription_cron_' . date('Y-m-d') . '.log';
file_put_contents($log_file, implode("\n", $log) . "\n", FILE_APPEND);
