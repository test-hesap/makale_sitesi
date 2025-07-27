<?php
/**
 * IP Ban API
 * Admin panelinden IP adreslerini banlamak için kullanılır
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

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
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz güvenlik token'
    ]);
    exit;
}

// POST verisini al
$ipAddress = $_POST['ip_address'] ?? '';
$reason = $_POST['reason'] ?? '';
$banType = $_POST['ban_type'] ?? 'permanent';
$expiryDate = null;

if ($banType === 'temporary' && !empty($_POST['expiry_date'])) {
    $expiryDate = date('Y-m-d H:i:s', strtotime($_POST['expiry_date']));
}

// Parametreleri kontrol et
if (empty($ipAddress) || empty($reason)) {
    echo json_encode([
        'success' => false,
        'message' => 'IP adresi ve ban nedeni zorunludur'
    ]);
    exit;
}

// IP formatını doğrula
if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz IP adresi formatı'
    ]);
    exit;
}

try {
    $currentUser = getCurrentUser();
    $bannedBy = $currentUser['id'];
    
    // IP'yi banla
    if (banIP($ipAddress, $reason, $expiryDate, $bannedBy)) {
        echo json_encode([
            'success' => true,
            'message' => 'IP adresi başarıyla banlandı'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'IP banlanırken bir hata oluştu'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
