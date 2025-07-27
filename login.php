<?php
require_once 'config/database.php';
require_once 'includes/ip_helper.php';
require_once 'includes/functions.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$database = new Database();
$settings = getSettings();

// IP kontrolü - Banlı IP ise giriş yapmayı engelle
$clientIP = alternativeGetClientIP();
$error = '';

if (isIPBanned($clientIP)) {
    $language = getCurrentLanguage();
    $error = $language == 'en' ? 'Login is not available from your IP address.' : 'IP adresinizden giriş yapılamıyor.';
    // Logla
    error_log("Banned IP attempted login: $clientIP");
    
    // IP ban bilgilerini al (sadece bilgi amaçlı)
    try {
        $ipBanQuery = "SELECT reason, expiry_date FROM ip_bans 
                     WHERE ip_address = ? AND is_active = 1 
                     AND (expiry_date IS NULL OR expiry_date > NOW())
                     LIMIT 1";
        $stmt = $database->query($ipBanQuery, [$clientIP]);
        $ipBanInfo = $stmt->fetch();
        
        if ($ipBanInfo && !empty($ipBanInfo['expiry_date'])) {
            // Ban süreli ise ve son 24 saatte yapılmışsa, yani muhtemelen ban sebebi
            // giriş denemesi ise, sadece bu hata mesajını göster
            $banDate = strtotime($ipBanInfo['expiry_date']);
            $now = time();
            $diff = $banDate - $now;
            
            // Eğer ban süresi 24 saatten azsa (muhtemelen giriş denemesi limitinden dolayı)
            if ($diff < 24*60*60) {
                $error = $language == 'en' 
                    ? 'Too many failed login attempts. Please try again later.' 
                    : 'Çok fazla başarısız giriş denemesi. Lütfen daha sonra tekrar deneyin.';
            }
        }
    } catch (Exception $e) {
        error_log("IP ban kontrolü hatası: " . $e->getMessage());
    }
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isIPBanned($clientIP)) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['p4ss_w0rd'] ?? ''; // Şifre alan adı değiştirildi
    $cf_token = $_POST['cf-turnstile-response'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    $errors = [];

    $language = getCurrentLanguage();
    
    if (shouldShowCaptcha('login') && !verifyCaptcha($cf_token)) {
        $errors[] = $language == 'en' 
            ? "CAPTCHA verification failed. Please try again." 
            : "CAPTCHA doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    }

    if (empty($errors)) {
        try {
            // Giriş denemesini kaydet
            $tooManyAttempts = trackLoginAttempt($username, false);
            
            if ($tooManyAttempts) {
                $errors[] = $language == 'en' 
                    ? "Too many failed login attempts. Please try again later." 
                    : "Çok fazla başarısız giriş denemesi. Lütfen daha sonra tekrar deneyin.";
            } else {
                // Kullanıcıyı kontrol et
                $stmt = $database->query(
                    "SELECT * FROM users WHERE username = ? OR email = ?",
                    [$username, $username]
                );
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Kullanıcı banlı mı kontrol et
                    if (isUserBanned($user['id'])) {
                        // Kullanıcıyı banned.php sayfasına yönlendir
                        $_SESSION['user_id'] = $user['id']; // Ban nedeni gösterilebilmesi için oturum bilgisini ayarla
                        header('Location: /banned.php');
                        exit;
                    } else {
                        // Başarılı giriş denemesini kaydet - bu fonksiyon artık güvenli IP listesini de güncelliyor
                        trackLoginAttempt($username, true);
                        
                        // Son IP adresini güncelle
                        $database->query(
                            "UPDATE users SET last_ip = ?, last_login = NOW() WHERE id = ?",
                            [$clientIP, $user['id']]
                        );
                        
                        // Eğer varsa bu IP'deki banı kaldır - kullanıcı başarılı giriş yaptı
                        try {
                            $ipBanCheckQuery = "SELECT id FROM ip_bans WHERE ip_address = ? AND is_active = 1";
                            $ipBanStmt = $database->query($ipBanCheckQuery, [$clientIP]);
                            
                            if ($ipBanStmt->rowCount() > 0) {
                                // IP banlı, ban kaldır
                                $ipUnbanQuery = "UPDATE ip_bans SET is_active = 0 WHERE ip_address = ?";
                                $database->query($ipUnbanQuery, [$clientIP]);
                                
                                error_log("Başarılı giriş sonrası IP banı kaldırıldı: User: $username, IP: $clientIP");
                            }
                        } catch (Exception $e) {
                            error_log("IP ban kontrolü hatası: " . $e->getMessage());
                        }
                        
                        // Oturum başlat
                        $_SESSION['user_id'] = $user['id'];
                        
                        // "Beni hatırla" için çerez oluştur
                        if ($rememberMe) {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            $database->query(
                                "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
                                [$user['id'], $token, $expires]
                            );
                            
                            // HttpOnly ve Secure parametreleri
                            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'; // HTTPS durumuna göre
                            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', $secure, true);
                        }
                        
                        // Yönlendir
                        if (isset($_GET['redirect'])) {
                            header('Location: ' . $_GET['redirect']);
                        } else {
                            header('Location: /');
                        }
                        exit;
                    }
                } else {
                    $error = $language == 'en' 
                        ? 'Username or password is incorrect.' 
                        : 'Kullanıcı adı veya şifre hatalı.';
                }
            }
        } catch (Exception $e) {
            $error = $language == 'en' 
                ? 'An error occurred: ' . $e->getMessage() 
                : 'Bir hata oluştu: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$language = getCurrentLanguage();
$pageTitle = ($language == 'en' ? 'Login - ' : 'Giriş Yap - ') . $settings['site_title'];
$metaDescription = $language == 'en' ? 'Login to your account and access content.' : 'Hesabınıza giriş yapın ve içeriklere erişin.';

require_once 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8 min-h-[calc(100vh-350px)]">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-center text-gray-900 dark:text-white">
                <?php echo $language == 'en' ? 'Login to Your Account' : 'Hesabınıza Giriş Yapın'; ?>
            </h1>
            
            <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <?php echo $language == 'en' ? 'Username or Email' : 'Kullanıcı Adı veya E-posta'; ?>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        autocomplete="username"
                        required
                        <?php echo isIPBanned($clientIP) ? 'disabled' : ''; ?>
                    >
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        <?php echo $language == 'en' ? 'Password' : 'Şifre'; ?>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="p4ss_w0rd" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        autocomplete="new-password"
                        data-lpignore="true" 
                        required
                        <?php echo isIPBanned($clientIP) ? 'disabled' : ''; ?>
                    >
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember_me" 
                            name="remember_me" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            <?php echo isIPBanned($clientIP) ? 'disabled' : ''; ?>
                        >
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            <?php echo $language == 'en' ? 'Remember me' : 'Beni hatırla'; ?>
                        </label>
                    </div>
                    
                    <a href="/sifremi-unuttum.php" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        <?php echo $language == 'en' ? 'Forgot Password?' : 'Şifremi Unuttum?'; ?>
                    </a>
                </div>
                
                <?php if (shouldShowCaptcha('login')): ?>
                <div class="mb-6">
                    <?php echo getCaptchaScript(); ?>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between">
                    <button 
                        type="submit" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                        <?php echo isIPBanned($clientIP) ? 'disabled' : ''; ?>
                    >
                        <?php echo $language == 'en' ? 'Sign In' : 'Giriş Yap'; ?>
                    </button>
                </div>
            </form>
            
            <hr class="my-6 border-gray-300 dark:border-gray-600">
            
            <div class="text-center">
                <p class="text-gray-700 dark:text-gray-300">
                    <?php echo $language == 'en' ? "Don't have an account?" : "Hesabınız yok mu?"; ?> 
                    <a href="/register.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-bold">
                        <?php echo $language == 'en' ? 'Register' : 'Kayıt Ol'; ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

<script>
// Firefox'un şifre alanlarında sarı arka plan renklendirmesini önle
document.addEventListener('DOMContentLoaded', function() {
    // Şifre alanını bul
    const passwordField = document.getElementById('password');
    
    if (passwordField) {
        // Sayfa yüklendiğinde şifre alanını readonly yap
        passwordField.setAttribute('readonly', true);
        
        // Şifre alanına tıklandığında readonly özelliğini kaldır
        passwordField.addEventListener('click', function() {
            this.removeAttribute('readonly');
        });
        
        // Şifre alanına odaklandığında readonly özelliğini kaldır
        passwordField.addEventListener('focus', function() {
            this.removeAttribute('readonly');
        });
    }

    // Firefox'ta şifre alanlarında sarı arka planı engellemek için CSS ekle
    const style = document.createElement('style');
    style.textContent = `
        input[type="password"] {
            background-color: #fff !important;
            -webkit-box-shadow: none !important;
            box-shadow: none !important;
        }
        
        input[type="password"]:-webkit-autofill,
        input[type="password"]:-webkit-autofill:hover, 
        input[type="password"]:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #fff inset !important;
            background-color: #fff !important;
        }

        .dark input[type="password"] {
            background-color: #374151 !important;
        }

        .dark input[type="password"]:-webkit-autofill,
        .dark input[type="password"]:-webkit-autofill:hover, 
        .dark input[type="password"]:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #374151 inset !important;
            background-color: #374151 !important;
            -webkit-text-fill-color: white !important;
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php require_once 'includes/footer.php'; ?>
