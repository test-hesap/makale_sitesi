<?php
/**
 * Banlı kullanıcıların görüntüleyeceği sayfa
 */

// Gerekli dosyaları dahil et
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/ban_functions.php';

// Hata ayıklama modunu kapat
ini_set('display_errors', 0);
error_reporting(0);

// Sayfa başlığı ve meta etiketleri
$pageTitle = 'Hesap Engellenmiş';
$metaDesc = 'Hesabınız engellenmiştir';

// Oturum bilgilerini kontrol et
session_start();
// Öncelikle URL'den kullanıcı ID'sini kontrol et, yoksa oturumdakini al
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : ($_SESSION['user_id'] ?? null);
$banReason = '';
$banExpiry = null;

// Banlı kullanıcının bilgilerini al
if ($userId) {
    try {
        $database = new Database();
        
        // Tablo yapısını kontrol et
        $tableCheck = $database->pdo->query("SHOW COLUMNS FROM banned_users LIKE 'is_active'");
        $hasIsActiveColumn = $tableCheck->rowCount() > 0;
        
        // Sorguyu oluştur
        if ($hasIsActiveColumn) {
            $query = "SELECT bu.*, DATE_FORMAT(bu.expiry_date, '%d.%m.%Y %H:%i') as formatted_expiry, 
                      DATE_FORMAT(bu.ban_date, '%d.%m.%Y %H:%i') as formatted_date,
                      u.username as banned_username, u.last_ip as user_ip,
                      a.username as admin_username
                      FROM banned_users bu 
                      LEFT JOIN users u ON bu.user_id = u.id
                      LEFT JOIN users a ON bu.banned_by = a.id
                      WHERE bu.user_id = ? AND bu.is_active = 1 
                      AND (bu.expiry_date IS NULL OR bu.expiry_date > NOW())
                      ORDER BY bu.ban_date DESC LIMIT 1";
        } else {
            $query = "SELECT bu.*, DATE_FORMAT(bu.expiry_date, '%d.%m.%Y %H:%i') as formatted_expiry, 
                      DATE_FORMAT(bu.ban_date, '%d.%m.%Y %H:%i') as formatted_date,
                      u.username as banned_username, u.last_ip as user_ip,
                      a.username as admin_username
                      FROM banned_users bu 
                      LEFT JOIN users u ON bu.user_id = u.id
                      LEFT JOIN users a ON bu.banned_by = a.id
                      WHERE bu.user_id = ?
                      AND (bu.expiry_date IS NULL OR bu.expiry_date > NOW())
                      ORDER BY bu.ban_date DESC LIMIT 1";
        }
        
        $stmt = $database->query($query, [$userId]);
        if ($banInfo = $stmt->fetch()) {
            $banReason = $banInfo['reason'];
            $banExpiry = $banInfo['expiry_date'] ? $banInfo['formatted_expiry'] : null;
            $banDate = $banInfo['formatted_date'];
            $adminName = $banInfo['admin_username'];
            $userIp = $banInfo['user_ip'] ?? '';
            
            // IP kontrolü - Eğer kullanıcının ban süresi dolmuşsa ama IP banı hala aktifse düzelt
            if (!empty($userIp) && (!$banInfo['expiry_date'] || strtotime($banInfo['expiry_date']) < time())) {
                // Ban süresi dolmuş, IP banını kontrol et
                $ipCheckQuery = "SELECT id FROM ip_bans 
                               WHERE ip_address = ? AND is_active = 1 
                               AND (expiry_date IS NULL OR expiry_date > NOW())";
                               
                $ipStmt = $database->query($ipCheckQuery, [$userIp]);
                if ($ipStmt->rowCount() > 0) {
                    // IP hala banlı, ban kaldır
                    $ipUnbanQuery = "UPDATE ip_bans SET is_active = 0 WHERE ip_address = ?";
                    $database->query($ipUnbanQuery, [$userIp]);
                    
                    error_log("Kullanıcı ban süresi dolmuş ama IP hala banlı, IP banı kaldırıldı: User ID: $userId, IP: $userIp");
                    
                    // Kullanıcı adını al
                    $usernameQuery = "SELECT username FROM users WHERE id = ?";
                    $usernameStmt = $database->query($usernameQuery, [$userId]);
                    $username = $usernameStmt->fetchColumn();
                    
                    if ($username) {
                        // IP'yi güvenli listesine ekle
                        try {
                            $safeIpQuery = "INSERT INTO safe_ips (ip_address, username, last_successful_login) 
                                         VALUES (?, ?, NOW()) 
                                         ON DUPLICATE KEY UPDATE last_successful_login = NOW()";
                            try {
                                $database->query($safeIpQuery, [$userIp, $username]);
                                error_log("Kullanıcının IP'si güvenli listeye eklendi: User ID: $userId, IP: $userIp");
                            } catch (Exception $e) {
                                // safe_ips tablosu yoksa oluştur
                                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                                    $database->query("CREATE TABLE IF NOT EXISTS safe_ips (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        ip_address VARCHAR(45) NOT NULL,
                                        username VARCHAR(50) NOT NULL,
                                        last_successful_login DATETIME DEFAULT CURRENT_TIMESTAMP,
                                        UNIQUE KEY (ip_address, username)
                                    )");
                                    $database->query($safeIpQuery, [$userIp, $username]);
                                    error_log("safe_ips tablosu oluşturuldu ve kullanıcının IP'si güvenli listeye eklendi");
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Güvenli IP işleminde hata: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Ban bilgisi alma hatası: ' . $e->getMessage());
    }
}

// URL'den kullanıcı ID'si geldiyse oturumu sonlandırmayalım (admin görüntüleme)
if (!isset($_GET['user_id'])) {
    // Oturumu sonlandır (kullanıcının tekrar giriş yapmaya çalışmaması için)
    session_destroy();

    // Çerezleri temizle
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $metaDesc; ?>">
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #d9534f;
            margin-bottom: 20px;
        }
        .ban-icon {
            font-size: 64px;
            color: #d9534f;
            margin-bottom: 20px;
        }
        .ban-details {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        .ban-reason {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .ban-expiry {
            margin-top: 10px;
            font-style: italic;
        }
        .contact {
            margin-top: 30px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .admin-info {
            margin-top: 15px;
            font-size: 0.9em;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ban-icon">⛔</div>
        <h1>Hesabınız Engellenmiştir</h1>
        
        <p>Üzgünüz, hesabınıza erişim engellenmiştir.</p>
        
        <?php if ($banReason): ?>
        <div class="ban-details">
            <div class="ban-reason">
                <strong>Engelleme Nedeni:</strong> <?php echo htmlspecialchars($banReason); ?>
            </div>
            
            <?php if ($banDate): ?>
            <div>
                <strong>Engellenme Tarihi:</strong> <?php echo htmlspecialchars($banDate); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($banExpiry): ?>
            <div class="ban-expiry">
                <strong>Bitiş Tarihi:</strong> <?php echo htmlspecialchars($banExpiry); ?>
                <p>Bu tarihten sonra hesabınıza tekrar giriş yapabilirsiniz.</p>
            </div>
            <?php else: ?>
            <div class="ban-expiry">
                <p>Bu engelleme <strong>süresizdir</strong>.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($adminName): ?>
            <div class="admin-info">
                <em>Bu işlem <?php echo htmlspecialchars($adminName); ?> tarafından gerçekleştirilmiştir.</em>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="contact">
            <p>Eğer bu engellemede bir yanlışlık olduğunu düşünüyorsanız, site yöneticisiyle iletişime geçebilirsiniz.</p>
            <a href="/contact.php" class="button">İletişime Geç</a>
        </div>
        
        <p><a href="/" class="button">Ana Sayfaya Dön</a></p>
    </div>
</body>
</html>
