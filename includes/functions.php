<?php
// Güvenlik kontrolleri
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Oturum başlat (cron job'lar için atla)
if (session_status() === PHP_SESSION_NONE && !isset($_SESSION)) {
    session_start();
}

// Temel güvenlik fonksiyonları
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// URL fonksiyonları
function generateSlug($text) {
    $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
    $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
    $text = str_replace($turkish, $english, $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function getSiteUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}

// Kullanıcı fonksiyonları
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Eğer session yok ama remember_token çerezi varsa kontrol et
    return checkRememberToken();
}

// Beni hatırla için çerez kontrolü
function checkRememberToken() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }

    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    $token = $_COOKIE['remember_token'];
    
    try {
        $stmt = $database->query(
            "SELECT * FROM remember_tokens WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
        $tokenData = $stmt->fetch();
        
        if ($tokenData) {
            // Token geçerli, kullanıcı oturumunu başlat
            $stmt = $database->query(
                "SELECT * FROM users WHERE id = ?",
                [$tokenData['user_id']]
            );
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                return true;
            }
        }
    } catch (Exception $e) {
        // Hata durumunda günlüğe kaydet
        error_log('Remember token kontrol hatası: ' . $e->getMessage());
    }
    
    // Token geçersiz veya süresi dolmuş, çerezi temizle
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    return false;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    $stmt = $database->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    return $stmt->fetch();
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['is_admin'];
}

function isUserAdmin() {
    return isAdmin();
}

function isPremium() {
    $user = getCurrentUser();
    // Admin kullanıcıları her zaman premium içeriklere erişebilir
    if ($user && $user['is_admin']) return true;
    
    if (!$user || !$user['is_premium']) return false;
    
    if ($user['premium_expires_at'] && strtotime($user['premium_expires_at']) < time()) {
        // Premium süresi dolmuş, güncelle
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        $database->query("UPDATE users SET is_premium = 0 WHERE id = ?", [$user['id']]);
        return false;
    }
    
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /');
        exit;
    }
}

// Dosya upload fonksiyonları
function uploadImage($file, $directory = 'uploads') {
    // Temel hata kontrolü
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Geçersiz dosya yükleme');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Dosya çok büyük');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('Dosya kısmen yüklendi');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('Dosya seçilmedi');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Geçici dizin bulunamadı');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Dosya yazılamadı');
            case UPLOAD_ERR_EXTENSION:
                throw new Exception('Dosya uzantısı engellenmiş');
            default:
                throw new Exception('Bilinmeyen yükleme hatası');
        }
    }
    
    // Dosya boyutu kontrolü
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize || $file['size'] <= 0) {
        throw new Exception('Dosya boyutu 0 ile 5MB arasında olmalıdır');
    }
    
    // Dosya adı güvenlik kontrolü
    $originalName = basename($file['name']);
    if (empty($originalName) || strlen($originalName) > 255) {
        throw new Exception('Geçersiz dosya adı');
    }
    
    // Dosya uzantısı kontrolü
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Sadece JPG, PNG, GIF ve WebP dosyaları kabul edilir');
    }
    
    // MIME type kontrolü (sadece browser tarafından gönderilen, güvenilir değil)
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedMimeTypes)) {
        throw new Exception('Geçersiz dosya türü');
    }
    
    // Gerçek dosya içeriği kontrolü (daha güvenilir)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($realMimeType, $allowedMimeTypes)) {
        throw new Exception('Dosya içeriği resim formatında değil');
    }
    
    // Getimagesize ile additional güvenlik kontrolü
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Dosya geçerli bir resim değil');
    }
    
    // Dosya boyutları kontrolü
    if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
        throw new Exception('Resim boyutları çok büyük (maksimum 5000x5000 piksel)');
    }
    
    if ($imageInfo[0] < 10 || $imageInfo[1] < 10) {
        throw new Exception('Resim boyutları çok küçük (minimum 10x10 piksel)');
    }
    
    // Dizin güvenliği kontrolü
    $directory = preg_replace('/[^a-zA-Z0-9_-]/', '', $directory);
    if (empty($directory)) {
        $directory = 'uploads';
    }
    
    // Upload dizini oluştur
    $uploadDir = APP_ROOT . '/assets/images/' . $directory . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Upload dizini oluşturulamadı');
        }
    }
    
    // Güvenli dosya adı oluştur
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Dosya zaten var mı kontrol et
    if (file_exists($filepath)) {
        throw new Exception('Dosya zaten mevcut, lütfen tekrar deneyin');
    }
    
    // Dosyayı taşı
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Dosya kaydedilemedi');
    }
    
    // Dosya izinlerini ayarla
    chmod($filepath, 0644);
    
    // Kaydedilen dosyayı ek güvenlik kontrolleri ile doğrula
    if (!validateImage($filepath)) {
        // Güvenlik riski olan dosyayı sil
        unlink($filepath);
        throw new Exception('Dosya güvenlik kontrollerinden geçemedi');
    }
    
    // .htaccess ile PHP çalıştırmayı engelle
    $htaccessPath = $uploadDir . '.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "Options -ExecCGI\n";
        $htaccessContent .= "AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
        $htaccessContent .= "<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\n";
        $htaccessContent .= "    Order allow,deny\n";
        $htaccessContent .= "    Deny from all\n";
        $htaccessContent .= "</FilesMatch>\n";
        file_put_contents($htaccessPath, $htaccessContent);
    }
    
    return '/assets/images/' . $directory . '/' . $filename;
}

// E-posta fonksiyonları
function sendEmail($to, $subject, $body, $isHTML = true) {
    // SMTP ayarlarını al
    $settings = getSettings();
    
    if (empty($settings['smtp_host']) || empty($settings['smtp_username'])) {
        return false;
    }
    
    require_once APP_ROOT . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once APP_ROOT . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once APP_ROOT . '/vendor/phpmailer/phpmailer/src/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $settings['smtp_port'];
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($settings['smtp_username'], $settings['site_title']);
        $mail->addAddress($to);
        
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("E-posta gönderme hatası: " . $mail->ErrorInfo);
        return false;
    }
}

// Sayfalama fonksiyonu
function paginate($total, $perPage, $currentPage, $url) {
    $totalPages = ceil($total / $perPage);
    $pagination = '';
    
    if ($totalPages > 1) {
        $pagination .= '<nav class="mt-8"><div class="flex justify-center"><div class="flex space-x-1">';
        
        // Önceki sayfa
        if ($currentPage > 1) {
            $prev = $currentPage - 1;
            $pagination .= '<a href="' . $url . '?page=' . $prev . '" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Önceki</a>';
        }
        
        // Sayfa numaraları
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $currentPage) {
                $pagination .= '<span class="px-3 py-2 bg-blue-500 text-white rounded">' . $i . '</span>';
            } else {
                $pagination .= '<a href="' . $url . '?page=' . $i . '" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">' . $i . '</a>';
            }
        }
        
        // Sonraki sayfa
        if ($currentPage < $totalPages) {
            $next = $currentPage + 1;
            $pagination .= '<a href="' . $url . '?page=' . $next . '" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Sonraki</a>';
        }
        
        $pagination .= '</div></div></nav>';
    }
    
    return $pagination;
}

// Site ayarları
function getSettings($force_refresh = false) {
    static $settings = null;
    
    if ($settings === null || $force_refresh) {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        $stmt = $database->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

function getSetting($key, $default = '') {
    static $settings = null;
    
    // Cache temizlenmişse veya yoksa yeniden yükle
    if ($settings === null || (isset($_SESSION['clear_settings_cache']) && $_SESSION['clear_settings_cache'])) {
        $settings = getSettings();
        if (isset($_SESSION['clear_settings_cache'])) {
            unset($_SESSION['clear_settings_cache']);
        }
    }
    
    return $settings[$key] ?? $default;
}

function refreshSettings() {
    return getSettings(true);
}

function getSiteSetting($key, $default = '') {
    return getSetting($key, $default);
}

function getCurrentDomain() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

function updateSiteSetting($key, $value) {
    return updateSetting($key, $value);
}

function updateSetting($key, $value) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    // Önce ayarın var olup olmadığını kontrol et
    $stmt = $database->query("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$key]);
    $exists = $stmt->fetchColumn() > 0;
    
    if ($exists) {
        // Güncelle
        $result = $database->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
    } else {
        // Yeni ekle
        $result = $database->query("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$key, $value]);
    }
    
    // Cache'i temizle
    if (isset($_SESSION['settings_cache'])) {
        unset($_SESSION['settings_cache']);
    }
    
    // Sonraki getSetting çağrılarında cache'i yenile
    $_SESSION['clear_settings_cache'] = true;
    
    return $result;
}

// Çevrimiçi kullanıcı sayacı
function updateOnlineUsers() {
    try {
        $database = new Database();
        $db = $database->pdo;
        
        // Oturum ID'sini al
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionId = session_id();
        
        // Kullanıcı ID'sini al
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // IP adresi ve user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Eski kayıtları temizle (15 dakikadan eski)
        $stmt = $db->prepare("
            DELETE FROM online_users 
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute();
        
        // Mevcut kullanıcıyı kontrol et
        $stmt = $db->prepare("
            SELECT id FROM online_users 
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Mevcut kaydı güncelle
            $stmt = $db->prepare("
                UPDATE online_users 
                SET last_activity = NOW(),
                    user_id = ?,
                    ip_address = ?,
                    user_agent = ?
                WHERE session_id = ?
            ");
            $stmt->execute([$userId, $ipAddress, $userAgent, $sessionId]);
        } else {
            // Yeni kayıt ekle
            $stmt = $db->prepare("
                INSERT INTO online_users (
                    user_id,
                    session_id,
                    last_activity,
                    ip_address,
                    user_agent
                ) VALUES (?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([$userId, $sessionId, $ipAddress, $userAgent]);
        }
    } catch (Exception $e) {
        // Hata durumunda sessizce devam et
        error_log('Online users update error: ' . $e->getMessage());
    }
}

// Çevrimiçi kullanıcıları, misafirleri ve botları getir
function getOnlineUsers() {
    try {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        $db = $database->pdo;
        
        // Bot listesi ve isimleri
        $botPatterns = [
            'googlebot' => 'Google',
            'bingbot' => 'Bing',
            'yandexbot' => 'Yandex',
            'duckduckbot' => 'DuckDuckGo',
            'baiduspider' => 'Baidu',
            'facebookexternalhit' => 'Facebook',
            'twitterbot' => 'Twitter',
            'bot' => 'Bot',
            'crawl' => 'Crawler',
            'spider' => 'Spider',
            'slurp' => 'Yahoo',
            'search' => 'Search Bot',
            'mediapartners' => 'Google AdSense',
            'adsbot' => 'Google AdSense',
            'rogerbot' => 'Moz',
            'linkedinbot' => 'LinkedIn',
            'embedly' => 'Embedly'
        ];
        
        // Son 5 dakika içinde aktif olan kullanıcıları getir
        $stmt = $db->prepare("
            SELECT 
                ou.id, 
                ou.user_id, 
                ou.ip_address, 
                ou.user_agent,
                ou.last_activity,
                u.username
            FROM online_users ou
            LEFT JOIN users u ON ou.user_id = u.id
            WHERE ou.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY ou.last_activity DESC
        ");
        $stmt->execute();
        $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sonuçları kategorilere ayır
        $result = [
            'users' => [],
            'guests' => 0,
            'bots' => 0,
            'bot_names' => [],
            'total' => 0 // Toplam sayıyı sonradan hesaplayacağız
        ];
        
        foreach ($onlineUsers as $user) {
            // Bot kontrolü
            $isBot = false;
            $botName = '';
            
            if (!empty($user['user_agent'])) {
                $userAgent = strtolower($user['user_agent']);
                foreach ($botPatterns as $pattern => $name) {
                    if (strpos($userAgent, $pattern) !== false) {
                        $isBot = true;
                        $botName = $name;
                        break;
                    }
                }
            }
            
            if ($isBot) {
                $result['bots']++;
                if (!in_array($botName, $result['bot_names'])) {
                    $result['bot_names'][] = $botName;
                }
            } else if (!empty($user['user_id']) && !empty($user['username'])) {
                // Kullanıcının daha önce eklenip eklenmediğini kontrol et
                $userExists = false;
                foreach ($result['users'] as $existingUser) {
                    if ($existingUser['id'] == $user['user_id']) {
                        $userExists = true;
                        break;
                    }
                }
                
                // Kullanıcı daha önce eklenmemişse listeye ekle
                if (!$userExists) {
                    $result['users'][] = [
                        'id' => $user['user_id'],
                        'username' => $user['username'],
                        'last_activity' => $user['last_activity']
                    ];
                }
            } else {
                $result['guests']++;
            }
        }
        
        // Toplam sayıyı hesapla: benzersiz kullanıcılar + misafirler + botlar
        $result['total'] = count($result['users']) + $result['guests'] + $result['bots'];
        
        return $result;
    } catch (Exception $e) {
        error_log('Get online users error: ' . $e->getMessage());
        return [
            'users' => [],
            'guests' => 0,
            'bots' => 0,
            'bot_names' => [],
            'total' => 0
        ];
    }
}

function getOnlineCount() {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    $stmt = $database->query("SELECT COUNT(*) as count FROM online_users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    return $stmt->fetch()['count'];
}

// Tema ile ilgili fonksiyonlar
function getCurrentTheme() {
    if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light', 'dark'])) {
        return $_COOKIE['theme'];
    }
    return 'light'; // varsayılan tema
}

function setTheme($theme) {
    if (in_array($theme, ['light', 'dark'])) {
        setcookie('theme', $theme, time() + (86400 * 365), '/'); // 1 yıl geçerli
        return true;
    }
    return false;
}

// Dil fonksiyonları
function getCurrentLanguage() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        return $user['language_preference'] ?? 'tr';
    }
    return $_COOKIE['language'] ?? 'tr';
}

function setLanguage($language) {
    if (isLoggedIn()) {
        require_once APP_ROOT . '/config/database.php';
        $database = new Database();
        $database->query("UPDATE users SET language_preference = ? WHERE id = ?", [$language, $_SESSION['user_id']]);
    } else {
        setcookie('language', $language, time() + (365 * 24 * 60 * 60), '/');
    }
}

// Çeviri fonksiyonu
function t($key, $placeholders = []) {
    static $translations = null;
    $language = getCurrentLanguage();
    
    if ($translations === null) {
        $langFile = APP_ROOT . '/includes/languages/' . $language . '.php';
        if (file_exists($langFile)) {
            $translations = include $langFile;
        } else {
            $translations = include APP_ROOT . '/includes/languages/tr.php'; // Varsayılan dil
        }
    }
    
    $text = $translations[$key] ?? $key;
    
    // Yer tutucuları değiştir
    if (!empty($placeholders)) {
        foreach ($placeholders as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, $value, $text);
        }
    }
    
    return $text;
}

// Zaman fonksiyonları
function timeAgo($datetime) {
    if (!$datetime) return date('d.m.Y');
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return t('just_now');
    if ($time < 3600) return floor($time/60) . ' ' . t('minute_ago');
    if ($time < 86400) return floor($time/3600) . ' ' . t('hour_ago');
    if ($time < 2592000) return floor($time/86400) . ' ' . t('day_ago');
    if ($time < 31536000) return floor($time/2592000) . ' ' . t('month_ago');
    return floor($time/31536000) . ' ' . t('year_ago');
}

// Tarih formatla
function formatDate($datetime, $format = 'd.m.Y') {
    if (!$datetime) return date($format);
    return date($format, strtotime($datetime));
}

// Excerpt oluşturma
function createExcerpt($content, $length = 150) {
    // HTML entity'leri decode et
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    // HTML tag'lerini temizle
    $content = strip_tags($content);
    // Uzunluğu kontrol et
    if (mb_strlen($content, 'UTF-8') <= $length) {
        return $content;
    }
    // Türkçe karakterleri dikkate alarak kes
    return mb_substr($content, 0, $length, 'UTF-8') . '...';
}

// Error handling
function logError($message, $file = '', $line = '') {
    $log = date('Y-m-d H:i:s') . " - $message";
    if ($file) $log .= " in $file";
    if ($line) $log .= " on line $line";
    $log .= "\n";
    
    error_log($log, 3, APP_ROOT . '/logs/error.log');
}

// Cache fonksiyonları
function cacheGet($key) {
    $cacheFile = APP_ROOT . '/cache/' . md5($key) . '.cache';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        return unserialize(file_get_contents($cacheFile));
    }
    return false;
}

function cacheSet($key, $data, $ttl = 3600) {
    $cacheDir = APP_ROOT . '/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    file_put_contents($cacheFile, serialize($data));
}

// Kullanıcının premium içeriğe erişimi var mı?
function hasPremiumAccess() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin kullanıcıları her zaman premium içeriğe erişebilir
    if (isAdmin()) {
        return true;
    }

    try {
        $database = new Database();
        $userId = $_SESSION['user_id'];
        
        // Aktif premium aboneliği var mı?
        $subscription = $database->query(
            "SELECT us.* 
             FROM user_subscriptions us 
             JOIN subscription_plans p ON p.id = us.plan_id 
             WHERE us.user_id = ? 
             AND us.status = 'active' 
             AND us.end_date > NOW()
             AND p.price > 0
             LIMIT 1",
            [$userId]
        )->fetch();
        
        return $subscription ? true : false;
    } catch (Exception $e) {
        return false;
    }
}

// Kullanıcıya reklam gösterilmeli mi?
function shouldShowAds() {
    if (!isLoggedIn()) {
        return true;
    }
    
    // Admin kullanıcılarına reklam gösterilmez
    if (isAdmin()) {
        return false;
    }

    try {
        $database = new Database();
        $userId = $_SESSION['user_id'];
        
        // Reklamsız premium aboneliği var mı?
        $subscription = $database->query(
            "SELECT us.* 
             FROM user_subscriptions us 
             JOIN subscription_plans p ON p.id = us.plan_id 
             WHERE us.user_id = ? 
             AND us.status = 'active' 
             AND us.end_date > NOW()
             AND p.price > 0
             LIMIT 1",
            [$userId]
        )->fetch();
        
        return $subscription ? false : true;
    } catch (Exception $e) {
        return true;
    }
}

// Kullanıcının aktif abonelik planını getir
function getUserActivePlan() {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $database = new Database();
        $userId = $_SESSION['user_id'];
        
        return $database->query(
            "SELECT p.*, us.end_date as subscription_end_date 
             FROM user_subscriptions us 
             JOIN subscription_plans p ON p.id = us.plan_id 
             WHERE us.user_id = ? 
             AND us.status = 'active' 
             AND us.end_date > NOW()
             ORDER BY p.price DESC
             LIMIT 1",
            [$userId]
        )->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Site e-posta ayarı
define('SITE_EMAIL', 'iletisim@siteadi.com');

/**
 * Okunmamış mesaj sayısını döndürür
 */
function getUnreadMessageCount() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    try {
        $database = new Database();
        $db = $database->pdo;
        
        $currentUser = getCurrentUser();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE receiver_id = ? 
            AND is_read = 0 
            AND is_deleted_by_receiver = FALSE
        ");
        $stmt->execute([$currentUser['id']]);
        
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Okunmamış mesaj sayısı alınırken hata: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Cloudflare CAPTCHA fonksiyonları
 */
function getCloudflareSettings() {
    try {
        $database = new Database();
        $stmt = $database->prepare("SELECT * FROM cloudflare_settings WHERE id = 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Cloudflare ayarları alınırken hata: " . $e->getMessage());
        return false;
    }
}

function updateCloudflareSettings($settings) {
    try {
        $database = new Database();
        $sql = "UPDATE cloudflare_settings SET 
                is_enabled = :is_enabled,
                site_key = :site_key,
                secret_key = :secret_key,
                login_enabled = :login_enabled,
                register_enabled = :register_enabled,
                contact_enabled = :contact_enabled,
                article_enabled = :article_enabled,
                difficulty = :difficulty,
                theme = :theme,
                language = :language
                WHERE id = 1";
                
        $stmt = $database->prepare($sql);
        return $stmt->execute($settings);
    } catch (Exception $e) {
        error_log("Cloudflare ayarları güncellenirken hata: " . $e->getMessage());
        return false;
    }
}

function shouldShowCaptcha($page) {
    $settings = getCloudflareSettings();
    if (!$settings || !$settings['is_enabled']) {
        return false;
    }

    switch ($page) {
        case 'login':
            return $settings['login_enabled'] == 1;
        case 'register':
            return $settings['register_enabled'] == 1;
        case 'contact':
            return $settings['contact_enabled'] == 1;
        case 'article':
            return $settings['article_enabled'] == 1;
        default:
            return false;
    }
}

function getCaptchaScript() {
    $settings = getCloudflareSettings();
    if (!$settings) {
        return '';
    }
    
    return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <div class="cf-turnstile" 
         data-sitekey="' . htmlspecialchars($settings['site_key']) . '"
         data-theme="' . htmlspecialchars($settings['theme']) . '"
         data-language="' . htmlspecialchars($settings['language']) . '">
    </div>';
}

function verifyCaptcha($token) {
    $settings = getCloudflareSettings();
    if (!$settings || !$settings['is_enabled']) {
        return true;
    }

    $secret_key = $settings['secret_key'];
    
    $data = http_build_query([
        'secret' => $secret_key,
        'response' => $token
    ]);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $data
        ]
    ];

    $context = stream_context_create($opts);
    $response = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    $result = json_decode($response, true);

    return isset($result['success']) && $result['success'] === true;
}

// Sitemap fonksiyonları
function generateSitemapXML() {
    try {
        $database = new Database();
        $db = $database->pdo;
        
        $baseUrl = getCurrentDomain();
        $sitemap_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Ana sayfa
        $sitemap_content .= '  <url>' . "\n";
        $sitemap_content .= '    <loc>' . $baseUrl . '/</loc>' . "\n";
        $sitemap_content .= '    <lastmod>' . date('c') . '</lastmod>' . "\n";
        $sitemap_content .= '    <changefreq>' . getSiteSetting('sitemap_changefreq_homepage', 'daily') . '</changefreq>' . "\n";
        $sitemap_content .= '    <priority>' . getSiteSetting('sitemap_priority_homepage', '1.0') . '</priority>' . "\n";
        $sitemap_content .= '  </url>' . "\n";
        
        // Sabit sayfalar
        $staticPages = [
            '/premium' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), getSiteSetting('sitemap_priority_static', '0.5')],
            '/search' => ['weekly', '0.6'],
            '/contact' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), getSiteSetting('sitemap_priority_static', '0.5')],
            '/about' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), getSiteSetting('sitemap_priority_static', '0.5')],
            '/privacy' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), '0.3']
        ];
        
        foreach ($staticPages as $page => $info) {
            $sitemap_content .= '  <url>' . "\n";
            $sitemap_content .= '    <loc>' . $baseUrl . $page . '</loc>' . "\n";
            $sitemap_content .= '    <lastmod>' . date('c') . '</lastmod>' . "\n";
            $sitemap_content .= '    <changefreq>' . $info[0] . '</changefreq>' . "\n";
            $sitemap_content .= '    <priority>' . $info[1] . '</priority>' . "\n";
            $sitemap_content .= '  </url>' . "\n";
        }
        
        // Kategoriler
        $categoriesQuery = "SELECT slug, updated_at FROM categories WHERE is_active = 1";
        $categoriesStmt = $db->prepare($categoriesQuery);
        $categoriesStmt->execute();
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $category) {
            $sitemap_content .= '  <url>' . "\n";
            $sitemap_content .= '    <loc>' . $baseUrl . '/kategori/' . $category['slug'] . '</loc>' . "\n";
            $sitemap_content .= '    <lastmod>' . date('c', strtotime($category['updated_at'] ?? 'now')) . '</lastmod>' . "\n";
            $sitemap_content .= '    <changefreq>' . getSiteSetting('sitemap_changefreq_categories', 'weekly') . '</changefreq>' . "\n";
            $sitemap_content .= '    <priority>' . getSiteSetting('sitemap_priority_categories', '0.7') . '</priority>' . "\n";
            $sitemap_content .= '  </url>' . "\n";
        }
        
        // Makaleler
        $articlesQuery = "SELECT slug, updated_at, created_at FROM articles 
                          WHERE status = 'published' 
                          ORDER BY updated_at DESC";
        $articlesStmt = $db->prepare($articlesQuery);
        $articlesStmt->execute();
        $articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($articles as $article) {
            $lastmod = $article['updated_at'] ?: $article['created_at'];
            $sitemap_content .= '  <url>' . "\n";
            $sitemap_content .= '    <loc>' . $baseUrl . '/makale/' . $article['slug'] . '</loc>' . "\n";
            $sitemap_content .= '    <lastmod>' . date('c', strtotime($lastmod)) . '</lastmod>' . "\n";
            $sitemap_content .= '    <changefreq>' . getSiteSetting('sitemap_changefreq_articles', 'monthly') . '</changefreq>' . "\n";
            $sitemap_content .= '    <priority>' . getSiteSetting('sitemap_priority_articles', '0.8') . '</priority>' . "\n";
            $sitemap_content .= '  </url>' . "\n";
        }
        
        // Kullanıcı profilleri (opsiyonel)
        if (getSiteSetting('sitemap_include_users', 0)) {
            $usersQuery = "SELECT id, username, updated_at FROM users 
                           WHERE is_approved = 1 AND is_admin = 0 
                           ORDER BY updated_at DESC";
            $usersStmt = $db->prepare($usersQuery);
            $usersStmt->execute();
            $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $sitemap_content .= '  <url>' . "\n";
                $sitemap_content .= '    <loc>' . $baseUrl . '/uye/' . $user['username'] . '</loc>' . "\n";
                $sitemap_content .= '    <lastmod>' . date('c', strtotime($user['updated_at'] ?? 'now')) . '</lastmod>' . "\n";
                $sitemap_content .= '    <changefreq>monthly</changefreq>' . "\n";
                $sitemap_content .= '    <priority>0.4</priority>' . "\n";
                $sitemap_content .= '  </url>' . "\n";
            }
        }
        
        $sitemap_content .= '</urlset>';
        
        // Sitemap dosyasını kaydet
        $sitemap_file = APP_ROOT . '/sitemap.xml';
        if (file_put_contents($sitemap_file, $sitemap_content) === false) {
            throw new Exception("Sitemap dosyası yazılamadı. Dizin izinlerini kontrol edin.");
        }
        
        // robots.txt'yi güncelle
        updateRobotsTxt();
        
        return true;
    } catch (Exception $e) {
        error_log("Sitemap oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

function updateRobotsTxt() {
    try {
        $robots_content = "User-agent: *\n";
        $robots_content .= "Allow: /\n";
        $robots_content .= "Disallow: /admin/\n";
        $robots_content .= "Disallow: /config/\n";
        $robots_content .= "Disallow: /includes/\n";
        $robots_content .= "Disallow: /vendor/\n";
        $robots_content .= "Disallow: /logs/\n";
        $robots_content .= "Disallow: /cache/\n";
        $robots_content .= "Disallow: /database/\n";
        $robots_content .= "\n";
        $robots_content .= "Sitemap: " . getCurrentDomain() . "/sitemap.xml\n";
        
        $robots_file = APP_ROOT . '/robots.txt';
        file_put_contents($robots_file, $robots_content);
        
        return true;
    } catch (Exception $e) {
        error_log("robots.txt güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

function autoGenerateSitemap() {
    // Otomatik güncelleme aktif mi kontrol et
    if (getSiteSetting('sitemap_auto_generate', 1) && getSiteSetting('sitemap_enabled', 1)) {
        return generateSitemapXML();
    }
    return false;
}

function getSitemapStats() {
    try {
        $database = new Database();
        $db = $database->pdo;
        
        $stats = [
            'total_urls' => 0,
            'articles' => 0,
            'categories' => 0,
            'static_pages' => 0
        ];
        
        // Yayınlanan makale sayısı
        $stmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        $stmt->execute();
        $stats['articles'] = $stmt->fetchColumn();
        
        // Aktif kategori sayısı
        $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE is_active = 1");
        $stmt->execute();
        $stats['categories'] = $stmt->fetchColumn();
        
        // Sabit sayfa sayısı (tahmini)
        $stats['static_pages'] = 6; // Ana sayfa, premium, arama, iletişim, hakkında, gizlilik
        
        $stats['total_urls'] = $stats['articles'] + $stats['categories'] + $stats['static_pages'];
        
        return $stats;
        
    } catch (Exception $e) {
        // Hata durumunda varsayılan değerler döndür
        return [
            'total_urls' => 0,
            'articles' => 0,
            'categories' => 0,
            'static_pages' => 0
        ];
    }
}

// Analitik fonksiyonları
function trackPageView($pageUrl, $pageTitle = null) {
    if (!$pageUrl) return false;
    
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    // Session ID oluştur veya al
    if (!isset($_SESSION['analytics_session'])) {
        $_SESSION['analytics_session'] = bin2hex(random_bytes(16));
    }
    
    // Kullanıcı bilgilerini al
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $_SESSION['analytics_session'];
    $ipAddress = getUserIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Cihaz tipini tespit et
    $deviceType = detectDeviceType($userAgent);
    $browser = detectBrowser($userAgent);
    $os = detectOperatingSystem($userAgent);
    
    try {
        $stmt = $database->query("INSERT INTO page_views (page_url, page_title, user_id, session_id, ip_address, user_agent, referer, device_type, browser, operating_system, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())", [
            $pageUrl, $pageTitle, $userId, $sessionId, $ipAddress, $userAgent, $referer, $deviceType, $browser, $os
        ]);
        
        // Makale görüntülenmesi ise views_count'u artır
        if (strpos($pageUrl, 'article.php') !== false && preg_match('/[?&]id=(\d+)/', $pageUrl, $matches)) {
            $articleId = $matches[1];
            $database->query("UPDATE articles SET views_count = views_count + 1 WHERE id = ?", [$articleId]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Page view tracking error: " . $e->getMessage());
        return false;
    }
}

function getUserIP() {
    // Cloudflare, proxy gibi servisleri de kontrol et
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function detectDeviceType($userAgent) {
    $userAgent = strtolower($userAgent);
    
    if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
        return 'tablet';
    } elseif (preg_match('/mobile|iphone|ipod|android|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile/i', $userAgent)) {
        return 'mobile';
    }
    
    return 'desktop';
}

function detectBrowser($userAgent) {
    $browsers = [
        'Chrome' => '/chrome/i',
        'Firefox' => '/firefox/i',
        'Safari' => '/safari/i',
        'Edge' => '/edge/i',
        'Internet Explorer' => '/msie|trident/i',
        'Opera' => '/opera/i'
    ];
    
    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return $browser;
        }
    }
    
    return 'Unknown';
}

function detectOperatingSystem($userAgent) {
    $os = [
        'Windows' => '/windows|win32/i',
        'Mac OS' => '/macintosh|mac os x/i',
        'Linux' => '/linux/i',
        'Android' => '/android/i',
        'iOS' => '/iphone|ipad|ipod/i'
    ];
    
    foreach ($os as $osName => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return $osName;
        }
    }
    
    return 'Unknown';
}

// Analitik veri çekme fonksiyonları
function getAnalyticsData($period = 'today') {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $dateCondition = '';
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
            break;
        default:
            $dateCondition = "DATE(created_at) = CURDATE()";
    }
    
    // Toplam sayfa görüntülenmeleri
    $stmt = $database->query("SELECT COUNT(*) as total_views FROM page_views WHERE $dateCondition");
    $totalViews = $stmt->fetch()['total_views'];
    
    // Benzersiz ziyaretçiler
    $stmt = $database->query("SELECT COUNT(DISTINCT session_id) as unique_visitors FROM page_views WHERE $dateCondition");
    $uniqueVisitors = $stmt->fetch()['unique_visitors'];
    
    // Ortalama oturum süresi (dakika)
    $stmt = $database->query("SELECT AVG(reading_time) as avg_session FROM page_views WHERE $dateCondition AND reading_time > 0");
    $avgSession = $stmt->fetch()['avg_session'] ?? 0;
    
    // Bounce rate hesapla
    $stmt = $database->query("SELECT (SUM(bounce) / COUNT(*)) * 100 as bounce_rate FROM page_views WHERE $dateCondition");
    $bounceRate = $stmt->fetch()['bounce_rate'] ?? 0;
    
    return [
        'visitors' => $uniqueVisitors,
        'pageviews' => $totalViews,
        'bounce_rate' => round($bounceRate, 1),
        'avg_session' => gmdate('i:s', $avgSession * 60)
    ];
}

function getPopularPages($limit = 10, $days = 7) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $stmt = $database->query("
        SELECT 
            page_url, 
            page_title,
            COUNT(*) as views,
            COUNT(DISTINCT session_id) as unique_views
        FROM page_views 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY page_url, page_title
        ORDER BY views DESC
        LIMIT ?
    ", [$days, $limit]);
    
    return $stmt->fetchAll();
}

function getMostReadArticles($limit = 10, $days = 7) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $stmt = $database->query("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.views_count,
            COUNT(pv.id) as recent_views,
            COUNT(DISTINCT pv.session_id) as unique_recent_views,
            AVG(pv.reading_time) as avg_reading_time,
            COUNT(c.id) as comment_count
        FROM articles a
        LEFT JOIN page_views pv ON pv.page_url LIKE CONCAT('%article.php%id=', a.id, '%') 
            AND pv.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        LEFT JOIN comments c ON c.article_id = a.id
        WHERE a.status = 'published'
        GROUP BY a.id, a.title, a.slug, a.views_count
        ORDER BY recent_views DESC, a.views_count DESC
        LIMIT ?
    ", [$days, $limit]);
    
    return $stmt->fetchAll();
}

function getTrafficSources($days = 7) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $stmt = $database->query("
        SELECT 
            CASE 
                WHEN referer = '' OR referer IS NULL THEN 'Direct'
                WHEN referer LIKE '%google%' THEN 'Google'
                WHEN referer LIKE '%facebook%' THEN 'Facebook'
                WHEN referer LIKE '%twitter%' THEN 'Twitter'
                WHEN referer LIKE '%instagram%' THEN 'Instagram'
                WHEN referer LIKE '%linkedin%' THEN 'LinkedIn'
                WHEN referer LIKE '%youtube%' THEN 'YouTube'
                ELSE 'Other Referral'
            END as source,
            COUNT(DISTINCT session_id) as visitors,
            COUNT(*) as page_views
        FROM page_views 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY source
        ORDER BY visitors DESC
    ", [$days]);
    
    $results = $stmt->fetchAll();
    $totalVisitors = array_sum(array_column($results, 'visitors'));
    
    // Yüzdeleri hesapla
    foreach ($results as &$result) {
        $result['percentage'] = $totalVisitors > 0 ? round(($result['visitors'] / $totalVisitors) * 100, 1) : 0;
    }
    
    return $results;
}

function getDeviceStatistics($days = 7) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $stmt = $database->query("
        SELECT 
            device_type as device,
            COUNT(DISTINCT session_id) as visitors,
            COUNT(*) as page_views
        FROM page_views 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY device_type
        ORDER BY visitors DESC
    ", [$days]);
    
    $results = $stmt->fetchAll();
    $totalVisitors = array_sum(array_column($results, 'visitors'));
    
    // Yüzdeleri hesapla
    foreach ($results as &$result) {
        $result['percentage'] = $totalVisitors > 0 ? round(($result['visitors'] / $totalVisitors) * 100, 1) : 0;
    }
    
    return $results;
}

function getBrowserStatistics($days = 7) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $stmt = $database->query("
        SELECT 
            browser,
            COUNT(DISTINCT session_id) as visitors,
            COUNT(*) as page_views
        FROM page_views 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY browser
        ORDER BY visitors DESC
    ", [$days]);
    
    $results = $stmt->fetchAll();
    $totalVisitors = array_sum(array_column($results, 'visitors'));
    
    // Yüzdeleri hesapla
    foreach ($results as &$result) {
        $result['percentage'] = $totalVisitors > 0 ? round(($result['visitors'] / $totalVisitors) * 100, 1) : 0;
    }
    
    return $results;
}

function getVisitorChart($days = 7) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $stmt = $database->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(DISTINCT session_id) as visitors,
            COUNT(*) as page_views
        FROM page_views 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ", [$days]);
    
    return $stmt->fetchAll();
}

// Analitik veri silme fonksiyonları
function clearAnalyticsData($type = 'all', $days = null) {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    try {
        switch ($type) {
            case 'page_views':
                if ($days) {
                    $database->query("DELETE FROM page_views WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
                } else {
                    $database->query("DELETE FROM page_views");
                }
                break;
                
            case 'site_statistics':
                if ($days) {
                    $database->query("DELETE FROM site_statistics WHERE date < DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$days]);
                } else {
                    $database->query("DELETE FROM site_statistics");
                }
                break;
                
            case 'article_statistics':
                if ($days) {
                    $database->query("DELETE FROM article_statistics WHERE date < DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$days]);
                } else {
                    $database->query("DELETE FROM article_statistics");
                }
                break;
                
            case 'traffic_sources':
                if ($days) {
                    $database->query("DELETE FROM traffic_sources WHERE date < DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$days]);
                } else {
                    $database->query("DELETE FROM traffic_sources");
                }
                break;
                
            case 'old_data':
                // 90 günden eski verileri sil
                $database->query("DELETE FROM page_views WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
                $database->query("DELETE FROM site_statistics WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
                $database->query("DELETE FROM article_statistics WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
                $database->query("DELETE FROM traffic_sources WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
                break;
                
            case 'all':
            default:
                $database->query("DELETE FROM page_views");
                $database->query("DELETE FROM site_statistics");
                $database->query("DELETE FROM article_statistics");
                $database->query("DELETE FROM traffic_sources");
                break;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Analytics data clear error: " . $e->getMessage());
        return false;
    }
}

function getAnalyticsDataCount() {
    require_once APP_ROOT . '/config/database.php';
    $database = new Database();
    
    $counts = [];
    
    try {
        // Sayfa görüntülenmeleri
        $stmt = $database->query("SELECT COUNT(*) as count FROM page_views");
        $counts['page_views'] = $stmt->fetch()['count'];
        
        // Site istatistikleri
        $stmt = $database->query("SELECT COUNT(*) as count FROM site_statistics");
        $counts['site_statistics'] = $stmt->fetch()['count'];
        
        // Makale istatistikleri
        $stmt = $database->query("SELECT COUNT(*) as count FROM article_statistics");
        $counts['article_statistics'] = $stmt->fetch()['count'];
        
        // Trafik kaynakları
        $stmt = $database->query("SELECT COUNT(*) as count FROM traffic_sources");
        $counts['traffic_sources'] = $stmt->fetch()['count'];
        
        // Toplam boyut (yaklaşık MB)
        $stmt = $database->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name IN ('page_views', 'site_statistics', 'article_statistics', 'traffic_sources')
        ");
        $counts['total_size_mb'] = $stmt->fetch()['size_mb'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Analytics count error: " . $e->getMessage());
        $counts = [
            'page_views' => 0,
            'site_statistics' => 0,
            'article_statistics' => 0,
            'traffic_sources' => 0,
            'total_size_mb' => 0
        ];
    }
    
    return $counts;
}

// Resim doğrulama fonksiyonu
function validateImage($filePath) {
    // Dosya var mı kontrol et
    if (!file_exists($filePath)) {
        return false;
    }
    
    // Gerçek MIME type kontrolü
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        return false;
    }
    
    // getimagesize ile kontrol
    $imageInfo = getimagesize($filePath);
    if ($imageInfo === false) {
        return false;
    }
    
    // Boyut kontrolleri
    if ($imageInfo[0] < 10 || $imageInfo[1] < 10) {
        return false;
    }
    
    if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
        return false;
    }
    
    // Dosya içeriğinde PHP kodu var mı kontrol et (temel)
    $content = file_get_contents($filePath);
    $suspiciousPatterns = [
        '/<\?php/i',
        '/<\?=/i',
        '/<script/i',
        '/eval\s*\(/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/shell_exec\s*\(/i',
        '/base64_decode\s*\(/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Bakım modu kontrolü
 * 
 * @return bool Bakım modu aktif mi?
 */
function isMaintenanceMode() {
    $maintenance_mode = getSiteSetting('maintenance_mode', 0);
    
    // Bakım modu aktif değilse
    if (!$maintenance_mode) {
        return false;
    }
    
    // Admin kullanıcılarına izin verilmişse ve kullanıcı admin ise
    if (getSiteSetting('maintenance_allow_admin', 1) && isAdmin()) {
        return false;
    }
    
    // İzin verilen IP'ler kontrolü
    $allowed_ips = getSiteSetting('maintenance_allowed_ips', '');
    if (!empty($allowed_ips)) {
        $ip_list = array_map('trim', explode(',', $allowed_ips));
        $user_ip = getUserIP();
        
        if (in_array($user_ip, $ip_list)) {
            return false;
        }
    }
    
    // Bitiş zamanı kontrolü
    $end_time = getSiteSetting('maintenance_end_time', '');
    
    return true;
}

/**
 * Bakım modu sayfasını göster
 */
function showMaintenancePage() {
    $title = getSiteSetting('maintenance_title', 'Bakım Modu');
    $message = getSiteSetting('maintenance_message', 'Sitemiz bakım modundadır. Kısa süre içinde tekrar hizmetinizde olacağız.');
    $bg_color = getSiteSetting('maintenance_bg_color', '#f3f4f6');
    $text_color = getSiteSetting('maintenance_text_color', '#1f2937');
    $show_timer = getSiteSetting('maintenance_show_timer', 1);
    $end_time = getSiteSetting('maintenance_end_time', '');
    $contact_email = getSiteSetting('maintenance_contact_email', '');
    
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // 1 saat sonra tekrar dene
    
    // Bakım modu sayfası HTML
    echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: ' . htmlspecialchars($bg_color) . ';
            color: ' . htmlspecialchars($text_color) . ';
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 2rem;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .countdown {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .countdown-item {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            min-width: 80px;
        }
        .countdown-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .countdown-label {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 2rem;
        }

        .expired-message {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            color: #ef4444;
            font-weight: bold;
        }
        .expired-message p {
            margin: 0;
        }
        .contact-link {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            color: inherit;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .contact-link:hover {
            background-color: rgba(0, 0, 0, 0.2);
        }
        .contact-link i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>' . htmlspecialchars($title) . '</h1>
        <p>' . nl2br(htmlspecialchars($message)) . '</p>';
        
    // Geri sayım sayacı
    if ($show_timer && !empty($end_time)) {
        echo '
        <div class="countdown">
            <div class="countdown-item">
                <div class="countdown-number" id="days">00</div>
                <div class="countdown-label">Gün</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-number" id="hours">00</div>
                <div class="countdown-label">Saat</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-number" id="minutes">00</div>
                <div class="countdown-label">Dakika</div>
            </div>
            <div class="countdown-item">
                <div class="countdown-number" id="seconds">00</div>
                <div class="countdown-label">Saniye</div>
            </div>
        </div>
        
        <script>
        // Geri sayım için
        const endTime = new Date("' . $end_time . '").getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                document.getElementById("days").textContent = "00";
                document.getElementById("hours").textContent = "00";
                document.getElementById("minutes").textContent = "00";
                document.getElementById("seconds").textContent = "00";
                
                // Süre bitti mesajını göster ama form dolduruluyorsa sayfayı yenileme
                const countdownEl = document.querySelector(".countdown");
                if (countdownEl) {
                    const expiredMessage = document.createElement("div");
                    expiredMessage.className = "expired-message";
                    expiredMessage.innerHTML = "<p>Bakım süresi doldu.</p>";
                    
                    // Eğer bu mesaj daha önce eklenmemişse ekle
                    if (!document.querySelector(".expired-message")) {
                        countdownEl.parentNode.insertBefore(expiredMessage, countdownEl.nextSibling);
                        
                        // Yalnızca kullanıcı form doldurmuyor ve sayfa daha önce yenilenmemişse yenile
                        if (!document.activeElement || 
                            !document.activeElement.closest("#maintenance-contact-form") && 
                            !document.querySelector(".reload-attempted")) {
                            
                            document.body.classList.add("reload-attempted");
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 5000);
                        }
                    }
                }
                
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("days").textContent = days.toString().padStart(2, "0");
            document.getElementById("hours").textContent = hours.toString().padStart(2, "0");
            document.getElementById("minutes").textContent = minutes.toString().padStart(2, "0");
            document.getElementById("seconds").textContent = seconds.toString().padStart(2, "0");
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        </script>';
    }
    
    // İletişim e-posta linki
    if (!empty($contact_email)) {
        echo '
        <div>
            <a href="mailto:' . htmlspecialchars($contact_email) . '" class="contact-link">
                <i class="fas fa-envelope"></i> İletişime Geç
            </a>
        </div>';
    }
    
    echo '
    </div>
</body>
</html>';
    
    exit;
}