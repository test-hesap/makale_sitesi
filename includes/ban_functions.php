<?php
/**
 * IP Banlama ve Kullanıcı Ban fonksiyonları
 */

// Log dosyası yolunu tanımla
$logFile = __DIR__ . '/../logs/ban_user_errors_' . date('Y-m-d') . '.log';

/**
 * Kullanıcı ID'sine göre banlı olup olmadığını kontrol eder
 * 
 * @param int $userId Kullanıcı ID
 * @param bool $returnInfo Ban bilgisini döndür (opsiyonel)
 * @return bool|array Kullanıcı banlıysa true veya ban bilgisi, değilse false
 */
function isUserBanned($userId, $returnInfo = false) {
    if (empty($userId)) return false;
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // Aktif banı kontrol et (süresi dolmamış veya süresiz)
        $query = "SELECT * FROM banned_users 
                 WHERE user_id = ? AND is_active = 1 
                 AND (expiry_date IS NULL OR expiry_date > NOW())";
        
        $stmt = $database->query($query, [$userId]);
        $isBanned = $stmt->rowCount() > 0;
        
        if ($returnInfo && $isBanned) {
            return $stmt->fetch();
        }
        
        return $isBanned;
    } catch (Exception $e) {
        error_log('Ban kontrol hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * IP adresine göre banlı olup olmadığını kontrol eder
 * 
 * @param string|null $ip IP adresi (null ise mevcut IP kullanılır)
 * @return bool IP banlıysa true, değilse false
 */
function isIPBanned($ip = null) {
    if ($ip === null) {
        $ip = getClientIP();
    }
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // Aktif IP banını kontrol et (süresi dolmamış veya süresiz)
        $query = "SELECT * FROM ip_bans 
                 WHERE ip_address = ? AND is_active = 1 
                 AND (expiry_date IS NULL OR expiry_date > NOW())";
        
        $stmt = $database->query($query, [$ip]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log('IP ban kontrol hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcıyı banlar
 * 
 * @param int $userId Banlanacak kullanıcı ID
 * @param string $reason Ban sebebi
 * @param string|null $expiryDate Ban bitiş tarihi (null ise süresiz)
 * @param int|null $bannedBy Banlayan admin ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function banUser($userId, $reason, $expiryDate = null, $bannedBy = null) {
    // Ban işlemi için log dosyası
    $logFile = APP_ROOT . '/logs/ban_user_errors_' . date('Y-m-d') . '.log';
    
    // Debug fonksiyonu (eğer dışarıdan bir debugLog fonksiyonu yoksa burada tanımlıyoruz)
    if (!function_exists('debugLog')) {
        function debugLog($message, $data = null) {
            global $logFile;
            
            // Log dosyası tanımlı değilse veya boşsa varsayılan bir log dosyası belirle
            if (empty($logFile)) {
                $logFile = __DIR__ . '/../logs/ban_user_errors_' . date('Y-m-d') . '.log';
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$message}";
            
            if ($data !== null) {
                $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            
            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        }
    }
    
    if (empty($userId)) {
        debugLog('banUser: User ID boş');
        return false;
    }
    
    debugLog('banUser: Ban işlemi başlatıldı', [
        'user_id' => $userId, 
        'reason' => $reason, 
        'expiry_date' => $expiryDate, 
        'banned_by' => $bannedBy
    ]);
    
    try {
        debugLog('banUser: Database.php dosyası dahil ediliyor');
        require_once APP_ROOT . '/config/database.php';
        
        debugLog('banUser: Database nesnesi oluşturuluyor');
        $database = new Database();
        
        // IP adresi olarak mevcut istemci IP'sini kullan
        $ipAddress = getClientIP();
        debugLog('banUser: İstemci IP adresi alındı', ['ip' => $ipAddress]);
        
        // Kullanıcıyı banla
        $query = "INSERT INTO banned_users (user_id, ip_address, reason, expiry_date, banned_by) 
                 VALUES (?, ?, ?, ?, ?)";
        
        debugLog('banUser: Ban verileri ekleniyor', [
            'query' => $query,
            'params' => [$userId, $ipAddress, $reason, $expiryDate, $bannedBy]
        ]);
        
        $database->query($query, [$userId, $ipAddress, $reason, $expiryDate, $bannedBy]);
        debugLog('banUser: Ban kaydı eklendi');
        
        // Kullanıcı hesabını pasifleştir
        $updateQuery = "UPDATE users SET is_active = 0, status = 'banned' WHERE id = ?";
        debugLog('banUser: Kullanıcı hesabı pasifleştiriliyor', ['query' => $updateQuery, 'user_id' => $userId]);
        
        $database->query($updateQuery, [$userId]);
        debugLog('banUser: Kullanıcı hesabı pasifleştirildi');
        
        // Kullanıcı şu an aktif oturumdaysa, oturumunu sonlandır
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            debugLog('banUser: Aktif kullanıcı banlandı, oturum sonlandırılıyor');
            session_write_close(); // Mevcut oturumu kaydet ve kapat
        }
        
        return true;
    } catch (Exception $e) {
        $errorMessage = 'Kullanıcı banlama hatası: ' . $e->getMessage();
        debugLog('banUser: HATA', [
            'error' => $errorMessage, 
            'trace' => $e->getTraceAsString()
        ]);
        
        // Standart hata günlüğüne de yaz
        error_log($errorMessage);
        
        // Hata fırlat
        throw $e;
    }
}

/**
 * Kullanıcı banını kaldırır
 * 
 * @param int $userId Banı kaldırılacak kullanıcı ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function unbanUser($userId) {
    if (empty($userId)) return false;
    
    try {
        // Log dosyası tanımlama
        $logFile = defined('APP_ROOT') ? APP_ROOT . '/logs/unban_user_' . date('Y-m-d') . '.log' : sys_get_temp_dir() . '/unban_user_' . date('Y-m-d') . '.log';
        
        // Debug log
        if (function_exists('banIpDebugLog')) {
            banIpDebugLog('unbanUser: Ban kaldırma işlemi başladı', $logFile, ['user_id' => $userId]);
        }
        
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // Kullanıcının IP adresini al (en son kullanılan)
        $userIpQuery = "SELECT last_ip FROM users WHERE id = ?";
        $stmt = $database->query($userIpQuery, [$userId]);
        $userIpData = $stmt->fetch();
        $userIp = $userIpData['last_ip'] ?? null;
        
        if (function_exists('banIpDebugLog')) {
            banIpDebugLog('unbanUser: Kullanıcı IP bilgisi alındı', $logFile, ['user_ip' => $userIp]);
        }
        
        // Ban kayıtlarını pasifleştir
        $query = "UPDATE banned_users SET is_active = 0 WHERE user_id = ?";
        $database->query($query, [$userId]);
        
        if (function_exists('banIpDebugLog')) {
            banIpDebugLog('unbanUser: Kullanıcı ban kaydı pasifleştirildi', $logFile);
        }
        
        // Kullanıcı hesabını aktifleştir
        $updateQuery = "UPDATE users SET is_active = 1, status = 'active' WHERE id = ?";
        $database->query($updateQuery, [$userId]);
        
        if (function_exists('banIpDebugLog')) {
            banIpDebugLog('unbanUser: Kullanıcı hesabı aktifleştirildi', $logFile);
        }
        
        // Eğer kullanıcının IP adresi varsa, bu IP'nin banını da kaldır
        if ($userIp) {
            if (function_exists('banIpDebugLog')) {
                banIpDebugLog('unbanUser: IP banı kaldırılıyor', $logFile, ['ip' => $userIp]);
            }
            
            // IP ban kaydını pasifleştir
            $ipBanQuery = "UPDATE ip_bans SET is_active = 0 WHERE ip_address = ?";
            $database->query($ipBanQuery, [$userIp]);
            
            if (function_exists('banIpDebugLog')) {
                banIpDebugLog('unbanUser: IP banı kaldırıldı', $logFile);
            }
            
            // IP'yi güvenli IP olarak ekle veya güncelle
            try {
                // Kullanıcı adını al
                $usernameQuery = "SELECT username FROM users WHERE id = ?";
                $usernameStmt = $database->query($usernameQuery, [$userId]);
                $username = $usernameStmt->fetchColumn();
                
                if ($username) {
                    $safeIpQuery = "INSERT INTO safe_ips (ip_address, username, last_successful_login) 
                                  VALUES (?, ?, NOW()) 
                                  ON DUPLICATE KEY UPDATE last_successful_login = NOW()";
                    try {
                        $database->query($safeIpQuery, [$userIp, $username]);
                        if (function_exists('banIpDebugLog')) {
                            banIpDebugLog('unbanUser: IP güvenli listeye eklendi', $logFile, ['ip' => $userIp, 'username' => $username]);
                        }
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
                            if (function_exists('banIpDebugLog')) {
                                banIpDebugLog('unbanUser: safe_ips tablosu oluşturuldu ve IP güvenli listeye eklendi', $logFile);
                            }
                        } else {
                            if (function_exists('banIpDebugLog')) {
                                banIpDebugLog('unbanUser: Güvenli IP eklerken hata', $logFile, ['error' => $e->getMessage()]);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                if (function_exists('banIpDebugLog')) {
                    banIpDebugLog('unbanUser: Güvenli IP işleminde hata', $logFile, ['error' => $e->getMessage()]);
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Kullanıcı ban kaldırma hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * IP adresini banlar
 * 
 * @param string $ipAddress Banlanacak IP adresi
 * @param string $reason Ban sebebi
 * @param string|null $expiryDate Ban bitiş tarihi (null ise süresiz)
 * @param int|null $bannedBy Banlayan admin ID
 * @return bool İşlem başarılıysa true, değilse false
 */
function banIP($ipAddress, $reason, $expiryDate = null, $bannedBy = null) {
    if (empty($ipAddress)) return false;
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // IP'yi banla
        $query = "INSERT INTO ip_bans (ip_address, reason, expiry_date, banned_by) 
                 VALUES (?, ?, ?, ?)";
        
        $database->query($query, [$ipAddress, $reason, $expiryDate, $bannedBy]);
        
        return true;
    } catch (Exception $e) {
        error_log('IP banlama hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * IP banını kaldırır
 * 
 * @param string $ipAddress Banı kaldırılacak IP adresi
 * @return bool İşlem başarılıysa true, değilse false
 */
function unbanIP($ipAddress) {
    if (empty($ipAddress)) return false;
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // IP ban kayıtlarını pasifleştir
        $query = "UPDATE ip_bans SET is_active = 0 WHERE ip_address = ?";
        $database->query($query, [$ipAddress]);
        
        return true;
    } catch (Exception $e) {
        error_log('IP ban kaldırma hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcının gerçek IP adresini döndürür
 * 
 * @return string IP adresi
 */
function getClientIP() {
    // APP_ROOT kontrol et
    if (!defined('APP_ROOT')) {
        define('APP_ROOT', dirname(dirname(__FILE__)));
    }
    
    // Log dizini kontrol et ve oluştur
    $logDir = APP_ROOT . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/ban_user_errors_' . date('Y-m-d') . '.log';
    
    // IP debug loglama fonksiyonu - global olarak sadece bir kez tanımlandığından emin olalım
    if (!function_exists('banIpDebugLog')) {
        /**
         * IP işlemleri için özel log fonksiyonu
         *
         * @param string $message Log mesajı
         * @param string $logFilePath Log dosyası yolu (zorunlu)
         * @param mixed $data Opsiyonel veri (null olabilir)
         */
        function banIpDebugLog($message, $logFilePath, $data = null) {
            if (empty($logFilePath)) {
                error_log("Log dosyası yolu belirtilmemiş");
                return;
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$message}";
            
            if ($data !== null) {
                $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            
            @file_put_contents($logFilePath, $logMessage . PHP_EOL, FILE_APPEND);
        }
    }

    $ipAddress = '';
    $serverVars = [];
    
    // Debug için SERVER değişkenlerini kaydedelim
    if (isset($_SERVER)) {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR', 'HTTP_CLIENT_IP'] as $key) {
            $serverVars[$key] = isset($_SERVER[$key]) ? $_SERVER[$key] : 'yok';
        }
        banIpDebugLog('getClientIP: SERVER değişkenleri', $logFile, $serverVars);
    } else {
        banIpDebugLog('getClientIP: $_SERVER değişkeni tanımlı değil', $logFile);
    }
    
    // CloudFlare kullanılıyorsa
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
        banIpDebugLog('getClientIP: CloudFlare IP kullanıldı', $logFile, ['ip' => $ipAddress]);
    }
    // Proxy kullanılıyorsa
    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        banIpDebugLog('getClientIP: X-Forwarded-For IP kullanıldı', $logFile, ['ip' => $ipAddress]);
    }
    // Normal durum
    elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        banIpDebugLog('getClientIP: Remote Address kullanıldı', $logFile, ['ip' => $ipAddress]);
    } else {
        banIpDebugLog('getClientIP: IP adresi bulunamadı, geçerli bir SERVER değişkeni yok', $logFile);
        $ipAddress = '0.0.0.0'; // Varsayılan değer
    }
    
    // Birden fazla IP varsa (proxy zinciri) ilkini al
    if (strpos($ipAddress, ',') !== false) {
        $ipArray = explode(',', $ipAddress);
        $ipAddress = trim($ipArray[0]);
        banIpDebugLog('getClientIP: Proxy zinciri algılandı, ilk IP kullanılıyor', $logFile, ['original' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'yok', 'used_ip' => $ipAddress]);
    }
    
    banIpDebugLog('getClientIP: Belirlenen IP adresi', $logFile, ['ip' => $ipAddress]);
    return $ipAddress;
}

/**
 * Başarısız giriş denemelerini kaydeder ve kontrol eder
 * 
 * @param string $username Denenen kullanıcı adı
 * @param bool $success Giriş başarılı mı
 * @return bool Çok fazla deneme varsa true, değilse false
 */
function trackLoginAttempt($username, $success = false) {
    $ip = getClientIP();
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // Başarılı giriş denemelerini kaydet ve IP'yi güvenli listesine ekle
        if ($success) {
            // Giriş denemesini kaydet
            $query = "INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 1)";
            $database->query($query, [$ip, $username]);
            
            // Kullanıcının IP'sini güvenli listesine ekle veya güncelle
            $safeQuery = "INSERT INTO safe_ips (ip_address, username, last_successful_login) 
                         VALUES (?, ?, NOW()) 
                         ON DUPLICATE KEY UPDATE last_successful_login = NOW()";
            try {
                $database->query($safeQuery, [$ip, $username]);
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
                    $database->query($safeQuery, [$ip, $username]);
                }
            }
            return false; // Başarılı girişte her zaman false döndür
        }
        
        // Başarısız giriş denemelerini kaydet
        $query = "INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 0)";
        $database->query($query, [$ip, $username]);
        
        // Önce güvenli listesine bakılır - eğer kullanıcı daha önce bu IP'den başarılı giriş yapmışsa
        $safeQuery = "SELECT id FROM safe_ips WHERE ip_address = ? AND username = ? AND 
                     last_successful_login > DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $safeStmt = $database->query($safeQuery, [$ip, $username]);
        $isSafeIP = $safeStmt->rowCount() > 0;
        
        // Eğer IP güvenliyse banlama işlemini yapma
        if ($isSafeIP) {
            return false;
        }
        
        // Son 15 dakikadaki başarısız denemeleri say
        $failedQuery = "SELECT COUNT(*) FROM login_attempts 
                        WHERE ip_address = ? AND success = 0 
                        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $stmt = $database->query($failedQuery, [$ip]);
        $failedCount = $stmt->fetchColumn();
        
        // Eğer çok fazla başarısız deneme varsa ve bu IP banlanmamışsa
        if ($failedCount >= 5 && !isIPBanned($ip)) {
            // 1 saatlik geçici ban uygula
            $expiryDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
            banIP($ip, 'Çok fazla başarısız giriş denemesi', $expiryDate);
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Login takip hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * Kayıt denemelerini kaydeder ve kontrol eder
 * 
 * @param string $username Denenen kullanıcı adı
 * @param string $email Denenen e-posta
 * @param bool $success Kayıt başarılı mı
 * @return bool Çok fazla deneme varsa true, değilse false
 */
function trackRegistrationAttempt($username, $email, $success = false) {
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        // Kayıt denemesini kaydet
        $query = "INSERT INTO registration_attempts (ip_address, username, email, success, user_agent) 
                 VALUES (?, ?, ?, ?, ?)";
        $database->query($query, [$ip, $username, $email, $success ? 1 : 0, $userAgent]);
        
        // Son 1 saatteki kayıt denemelerini say
        $attemptQuery = "SELECT COUNT(*) FROM registration_attempts 
                        WHERE ip_address = ? 
                        AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $database->query($attemptQuery, [$ip]);
        $attemptCount = $stmt->fetchColumn();
        
        // Eğer çok fazla deneme varsa ve bu IP banlanmamışsa
        if ($attemptCount >= 3 && !isIPBanned($ip)) {
            // 24 saatlik geçici ban uygula
            $expiryDate = date('Y-m-d H:i:s', strtotime('+24 hour'));
            banIP($ip, 'Çok fazla kayıt denemesi (muhtemel bot)', $expiryDate);
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Kayıt takip hatası: ' . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcı banlama geçmişini alır
 * 
 * @param int $userId Kullanıcı ID
 * @return array Ban kayıtları
 */
function getUserBanHistory($userId) {
    if (empty($userId)) return [];
    
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        
        $query = "SELECT b.*, u.username as banned_by_username 
                 FROM banned_users b 
                 LEFT JOIN users u ON b.banned_by = u.id 
                 WHERE b.user_id = ? 
                 ORDER BY b.ban_date DESC";
        
        $stmt = $database->query($query, [$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Ban geçmişi hatası: ' . $e->getMessage());
        return [];
    }
}
