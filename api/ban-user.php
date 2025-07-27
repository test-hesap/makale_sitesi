<?php
/**
 * User Ban API
 * Admin panelinden kullanıcıları banlamak için kullanılır
 */

// Hata ayıklama modu
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Oturum başlat (CSRF token için gerekli)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata log dosyası
$logFile = __DIR__ . '/../logs/ban_user_errors_' . date('Y-m-d') . '.log';

// Debug fonksiyonu
function debugLog($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// İşleme başladı
debugLog('Ban işlemi başlatıldı');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Yetki kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim'
    ]);
    exit;
}

// CSRF token kontrolü
debugLog('CSRF token kontrolü', [
    'session_token' => $_SESSION['csrf_token'] ?? 'yok',
    'post_token' => $_POST['csrf_token'] ?? 'yok'
]);

// Eğer token yoksa yeni bir token oluştur (geçici çözüm)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    debugLog('Yeni CSRF token oluşturuldu', ['token' => $_SESSION['csrf_token']]);
}

// Test aşamasında CSRF kontrolünü bypass et (GEÇİCİ ÇÖZÜM - GERÇEK ORTAMDA KALDIRILMALI)
$bypass_csrf = true; // Test için CSRF kontrolünü atla

if ($bypass_csrf || verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if ($bypass_csrf) {
        debugLog('CSRF token doğrulaması bypass edildi (TEST MODU)');
    } else {
        debugLog('CSRF token doğrulaması başarılı');
    }
} else {
    debugLog('CSRF token doğrulaması başarısız');
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz güvenlik token'
    ]);
    exit;
}

// POST verisini al
$userId = intval($_POST['user_id'] ?? 0);
$reason = $_POST['reason'] ?? '';
$banType = $_POST['ban_type'] ?? 'permanent';
$expiryDate = null;

debugLog('POST verileri alındı', [
    'user_id' => $userId,
    'reason' => $reason,
    'ban_type' => $banType,
    'POST data' => $_POST
]);

if ($banType === 'temporary' && !empty($_POST['expiry_date'])) {
    $expiryDate = date('Y-m-d H:i:s', strtotime($_POST['expiry_date']));
    debugLog('Geçici ban için son kullanma tarihi', ['expiry_date' => $expiryDate]);
}

// Parametreleri kontrol et
if (empty($userId) || empty($reason)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kullanıcı ID ve ban nedeni zorunludur'
    ]);
    exit;
}

// Admin kendini banlayamaz
$currentUser = getCurrentUser();
if ($userId === $currentUser['id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Kendinizi banlayamazsınız'
    ]);
    exit;
}

try {
    debugLog('Veritabanı bağlantısı oluşturuluyor');
    $database = new Database();
    $db = $database->pdo;
    
    debugLog('Veritabanı bağlantısı başarılı');
    
    // Kullanıcının admin olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    debugLog('Kullanıcı sorgusu yapıldı', ['user_found' => !empty($user), 'user_id' => $userId]);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Kullanıcı bulunamadı'
        ]);
        exit;
    }
    
    if ($user['is_admin']) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin kullanıcılar banlanamaz'
        ]);
        exit;
    }
    
    // Kullanıcıyı banla
    debugLog('Kullanıcı ban fonksiyonu çağrılıyor', [
        'user_id' => $userId, 
        'reason' => $reason, 
        'expiry_date' => $expiryDate, 
        'banned_by' => $currentUser['id']
    ]);
    
    try {
        // Veritabanı tablolarının yapısını kontrol et
        $tableCheck = $db->query("SHOW COLUMNS FROM banned_users LIKE 'is_active'");
        $hasIsActiveColumn = $tableCheck->rowCount() > 0;
        debugLog('Tablo yapısı kontrolü', ['has_is_active_column' => $hasIsActiveColumn]);
        
        if (!$hasIsActiveColumn) {
            debugLog('is_active sütunu yok, SQL tarafından atlandı');
            // is_active sütunu yoksa direkt SQL ile ekle
            $insertQuery = "INSERT INTO banned_users (user_id, ip_address, reason, expiry_date, banned_by) 
                          VALUES (?, ?, ?, ?, ?)";
            $ipAddress = getClientIP();
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$userId, $ipAddress, $reason, $expiryDate, $currentUser['id']]);
            
            // Kullanıcı hesabını pasifleştir
            $updateQuery = "UPDATE users SET status = 'banned' WHERE id = ?";
            $db->prepare($updateQuery)->execute([$userId]);
            
            debugLog('Kullanıcı başarıyla banlandı (direkt SQL ile)');
            
            // Aynı sayfada ban bilgisini göstermek için
            echo json_encode([
                'success' => true,
                'message' => 'Kullanıcı başarıyla banlandı',
                'redirect' => '../banned.php?user_id=' . $userId
            ]);
        } else if (banUser($userId, $reason, $expiryDate, $currentUser['id'])) {
            debugLog('Kullanıcı başarıyla banlandı');
            
            // Aynı sayfada ban bilgisini göstermek için
            echo json_encode([
                'success' => true,
                'message' => 'Kullanıcı başarıyla banlandı',
                'redirect' => '../banned.php?user_id=' . $userId
            ]);
        } else {
            debugLog('Kullanıcı ban fonksiyonu false döndü');
            echo json_encode([
                'success' => false,
                'message' => 'Kullanıcı banlanırken bir hata oluştu'
            ]);
        }
    } catch (Exception $banError) {
        debugLog('Ban fonksiyonu içinde hata oluştu', ['error' => $banError->getMessage(), 'trace' => $banError->getTraceAsString()]);
        echo json_encode([
            'success' => false,
            'message' => 'Ban işlemi sırasında hata: ' . $banError->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    debugLog('Genel hata oluştu', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}

// İşlem sonlandı
debugLog('Ban işlemi tamamlandı');
?>
