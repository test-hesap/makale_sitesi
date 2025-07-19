<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isUserAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// JSON verilerini al
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['filename'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dosya adı belirtilmedi']);
    exit;
}

$filename = $data['filename'];
$backup_dir = '../../database/backups';
$backup_file = $backup_dir . '/' . basename($filename);

// Dosya yolunu doğrula
if (strpos(realpath($backup_file), realpath($backup_dir)) !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz dosya yolu']);
    exit;
}

// Dosyayı sil
if (file_exists($backup_file) && unlink($backup_file)) {
    echo json_encode(['success' => true, 'message' => 'Yedek başarıyla silindi']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Yedek silinirken bir hata oluştu']);
} 