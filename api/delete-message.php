<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Oturum açmanız gerekiyor'
    ]);
    exit;
}

$messageId = intval($_GET['id'] ?? 0);
if (!$messageId) {
    echo json_encode([
        'success' => false,
        'error' => 'Geçersiz mesaj ID'
    ]);
    exit;
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Mesajın mevcut kullanıcıya ait olup olmadığını kontrol et
    $stmt = $db->prepare("
        SELECT id, sender_id, receiver_id 
        FROM messages 
        WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
    ");
    $stmt->execute([$messageId, $userId, $userId]);
    
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$message) {
        echo json_encode([
            'success' => false,
            'error' => 'Mesaj bulunamadı veya silme yetkiniz yok'
        ]);
        exit;
    }
    
    // Mesajı tamamen silmek yerine, sadece ilgili kullanıcı için silinmiş olarak işaretle
    if ($message['sender_id'] == $userId) {
        $stmt = $db->prepare("UPDATE messages SET is_deleted_by_sender = TRUE WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE messages SET is_deleted_by_receiver = TRUE WHERE id = ?");
    }
    $stmt->execute([$messageId]);
    
    // Eğer hem gönderen hem alıcı mesajı silmişse, mesajı tamamen kaldır
    $stmt = $db->prepare("
        DELETE FROM messages 
        WHERE id = ? AND is_deleted_by_sender = TRUE AND is_deleted_by_receiver = TRUE
    ");
    $stmt->execute([$messageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesaj başarıyla silindi'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Mesaj silinirken bir hata oluştu'
    ]);
} 