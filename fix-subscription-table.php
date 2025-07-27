<?php
// Bu betik, user_subscriptions tablosuna cancelled_at sütununu ekler

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->pdo;
    
    // user_subscriptions tablosunda cancelled_at sütunu var mı kontrol et
    $checkQuery = "SHOW COLUMNS FROM `user_subscriptions` LIKE 'cancelled_at'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Sütun yoksa ekle
        $alterQuery = "ALTER TABLE `user_subscriptions` ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `end_date`;";
        $db->exec($alterQuery);
        echo "Başarılı! 'cancelled_at' sütunu user_subscriptions tablosuna eklendi.<br>";
    } else {
        echo "'cancelled_at' sütunu zaten mevcut.<br>";
    }
    
    // user_subscriptions tablosunda updated_at sütunu var mı kontrol et
    $checkQuery = "SHOW COLUMNS FROM `user_subscriptions` LIKE 'updated_at'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Sütun yoksa ekle
        $alterQuery = "ALTER TABLE `user_subscriptions` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;";
        $db->exec($alterQuery);
        echo "Başarılı! 'updated_at' sütunu user_subscriptions tablosuna eklendi.<br>";
    } else {
        echo "'updated_at' sütunu zaten mevcut.<br>";
    }
    
    echo "<p>İşlem tamamlandı. Artık abonelik iptal işlemleri çalışacaktır.</p>";
    echo "<p><a href='/admin/?page=user_subscriptions'>Kullanıcı Abonelikleri sayfasına dön</a></p>";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
