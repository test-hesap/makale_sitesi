<?php
// Hata ayıklama modu
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Config dosyasını dahil et
require_once __DIR__ . '/config/database.php';

// Veritabanı bağlantısı
$database = new Database();
$pdo = $database->pdo;

// Tablo bilgilerini al
try {
    // Banned_users tablosu bilgisi
    $stmt = $pdo->query('DESCRIBE banned_users');
    echo "<h3>banned_users tablosu yapısı:</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
