<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ad_id']) || !isset($data['type']) || !in_array($data['type'], ['impression', 'click'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $db = new Database();
    
    // Önce istatistik kaydının var olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT id FROM ad_statistics WHERE ad_id = ?");
    $stmt->execute([$data['ad_id']]);
    $stat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stat) {
        // Kayıt varsa güncelle
        $field = $data['type'] === 'impression' ? 'impressions' : 'clicks';
        $stmt = $db->prepare("UPDATE ad_statistics SET $field = $field + 1 WHERE ad_id = ?");
        $stmt->execute([$data['ad_id']]);
    } else {
        // Kayıt yoksa oluştur
        $impressions = $data['type'] === 'impression' ? 1 : 0;
        $clicks = $data['type'] === 'click' ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO ad_statistics (ad_id, impressions, clicks) VALUES (?, ?, ?)");
        $stmt->execute([$data['ad_id'], $impressions, $clicks]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 