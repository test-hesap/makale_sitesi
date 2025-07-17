<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$database = new Database();
$settings = getSettings();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
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
            // Kullanıcıyı kontrol et
            $stmt = $database->query(
                "SELECT * FROM users WHERE username = ? OR email = ?",
                [$username, $username]
            );
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Oturum başlat (onay durumundan bağımsız olarak)
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
            } else {
                $error = $language == 'en' 
                    ? 'Username or password is incorrect.' 
                    : 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (Exception $e) {
            $error = $language == 'en' 
                ? 'An error occurred: ' . $e->getMessage() 
                : 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

$language = getCurrentLanguage();
$pageTitle = ($language == 'en' ? 'Login - ' : 'Giriş Yap - ') . $settings['site_title'];
$metaDescription = $language == 'en' ? 'Login to your account and access content.' : 'Hesabınıza giriş yapın ve içeriklere erişin.';

require_once 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <?= $language == 'en' ? 'Login' : 'Giriş Yap' ?>
            </h1>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                // Hata mesajlarını çevir
                $errorMessage = $error;
                if ($language == 'en') {
                    $errorMessages = [
                        'Kullanıcı adı veya şifre hatalı.' => 'Username or password is incorrect.',
                        'Hesabınız henüz onaylanmamış.' => 'Your account has not been approved yet.',
                        'CAPTCHA doğrulaması başarısız oldu. Lütfen tekrar deneyin.' => 'CAPTCHA verification failed. Please try again.',
                        'Bir hata oluştu: ' => 'An error occurred: '
                    ];
                    foreach ($errorMessages as $tr => $en) {
                        $errorMessage = str_replace($tr, $en, $errorMessage);
                    }
                }
                echo htmlspecialchars($errorMessage);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $language == 'en' 
                    ? 'Registration completed successfully. You can now login.' 
                    : 'Kayıt işleminiz başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.' ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Username or Email' : 'Kullanıcı Adı veya E-posta' ?>
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?= $language == 'en' ? 'Enter your username or email' : 'Kullanıcı adınızı veya e-posta adresinizi girin' ?>"
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Password' : 'Şifre' ?>
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?= $language == 'en' ? 'Enter your password' : 'Şifrenizi girin' ?>"
                </div>
                
                <?php if (shouldShowCaptcha('login')): ?>
                <div class="flex justify-center">
                    <?php echo getCaptchaScript(); ?>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center mt-3">
                    <input type="checkbox" id="remember_me" name="remember_me"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        <?= $language == 'en' ? 'Remember me' : 'Beni hatırla' ?>
                    </label>
                </div>
                
                <div class="mt-3">
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                        <?= $language == 'en' ? 'Login' : 'Giriş Yap' ?>
                    </button>
                </div>
            </form>
            
            <!-- Kayıt ve Şifremi Unuttum Linkleri -->
            <div class="mt-6 space-y-2">
                <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <?= $language == 'en' ? "Don't have an account?" : 'Hesabınız yok mu?' ?>
                    <a href="/register" class="text-blue-600 dark:text-blue-400 hover:underline">
                        <?= $language == 'en' ? 'Register' : 'Kayıt olun' ?>
                    </a>
                </p>
                <p class="text-center text-sm">
                    <a href="/sifremi-unuttum" class="text-blue-600 dark:text-blue-400 hover:underline">
                        <?= $language == 'en' ? 'Forgot password' : 'Şifremi unuttum' ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 