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
$language = getCurrentLanguage();

// Token'ı hem GET hem de POST parametrelerinden alabiliriz
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$tokenValid = false;
$email = '';

// Token kontrolü
if (!empty($token)) {
    try {
        // Debug için token bilgisini yazdır
        error_log("Token kontrol ediliyor: " . $token);
        
        $stmt = $database->query(
            "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
        $resetData = $stmt->fetch();
        
        // Eğer token bulunamadıysa, tüm token listesine bakalım (debug için)
        if (!$resetData) {
            $allTokens = $database->query("SELECT * FROM password_resets")->fetchAll();
            error_log("Mevcut token sayısı: " . count($allTokens));
            foreach ($allTokens as $t) {
                error_log("Veritabanındaki token: " . $t['token'] . " - E-posta: " . $t['email'] . " - Süre: " . $t['expires_at']);
            }
        }
        
        if ($resetData) {
            $tokenValid = true;
            $email = $resetData['email'];
        } else {
            $error = $language == 'en' 
                ? "Invalid or expired token. Please request a new password reset." 
                : "Geçersiz veya süresi dolmuş token. Lütfen yeni bir şifre sıfırlama talebinde bulunun.";
        }
    } catch (Exception $e) {
        $error = $language == 'en' 
            ? 'An error occurred: ' . $e->getMessage() 
            : 'Bir hata oluştu: ' . $e->getMessage();
    }
} else {
    $error = $language == 'en' 
        ? "No token provided. Please use the link sent to your email." 
        : "Token sağlanmadı. Lütfen e-postanıza gönderilen bağlantıyı kullanın.";
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $cf_token = $_POST['cf-turnstile-response'] ?? '';
    $errors = [];
    
    if (shouldShowCaptcha('login') && !verifyCaptcha($cf_token)) {
        $errors[] = $language == 'en' 
            ? "CAPTCHA verification failed. Please try again." 
            : "CAPTCHA doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    }
    
    if (empty($password)) {
        $errors[] = $language == 'en' 
            ? "Password is required." 
            : "Şifre gereklidir.";
    } elseif (strlen($password) < 6) {
        $errors[] = $language == 'en' 
            ? "Password must be at least 6 characters." 
            : "Şifre en az 6 karakter olmalıdır.";
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = $language == 'en' 
            ? "Passwords do not match." 
            : "Şifreler eşleşmiyor.";
    }
    
    if (empty($errors)) {
        try {
            // Debug bilgileri
            error_log("Şifre sıfırlama işlemi başlatılıyor - E-posta: " . $email . " - Token: " . $token);
            
            // Şifreyi güncelle
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Kullanıcıyı kontrol et
            $checkUser = $database->query("SELECT id FROM users WHERE email = ?", [$email]);
            $user = $checkUser->fetch();
            
            if (!$user) {
                error_log("Kullanıcı bulunamadı: " . $email);
                throw new Exception($language == 'en' ? 'User not found.' : 'Kullanıcı bulunamadı.');
            }
            
            error_log("Kullanıcı bulundu (ID: " . $user['id'] . "). Şifre güncelleniyor.");
            
            $database->query(
                "UPDATE users SET password = ? WHERE email = ?",
                [$hashedPassword, $email]
            );
            
            error_log("Şifre güncellendi. Token siliniyor: " . $token);
            
            // Token'ı sil
            $database->query(
                "DELETE FROM password_resets WHERE token = ?",
                [$token]
            );
            
            error_log("İşlem başarıyla tamamlandı");
            
            $success = $language == 'en' 
                ? "Your password has been successfully reset. You can now login with your new password." 
                : "Şifreniz başarıyla sıfırlandı. Artık yeni şifrenizle giriş yapabilirsiniz.";
        } catch (Exception $e) {
            $error = $language == 'en' 
                ? 'An error occurred: ' . $e->getMessage() 
                : 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

$pageTitle = ($language == 'en' ? 'Reset Password - ' : 'Şifre Sıfırlama - ') . $settings['site_title'];
$metaDescription = $language == 'en' ? 'Reset your password and regain access to your account.' : 'Şifrenizi sıfırlayın ve hesabınıza yeniden erişin.';

require_once 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <?= $language == 'en' ? 'Reset Password' : 'Şifre Sıfırla' ?>
            </h1>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $errorMsg): ?>
                    <li><?= htmlspecialchars($errorMsg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($success) ?>
            </div>
            
            <div class="mt-6">
                <a href="/login" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors block text-center">
                    <?= $language == 'en' ? 'Go to Login' : 'Giriş Yap' ?>
                </a>
            </div>
            <?php elseif ($tokenValid): ?>
            <form method="POST" action="" class="space-y-4">
                <!-- Token değerini form gönderimi sırasında korumak için gizli input -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'New Password' : 'Yeni Şifre' ?>
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?= $language == 'en' ? 'Enter your new password' : 'Yeni şifrenizi girin' ?>">
                </div>
                
                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Confirm New Password' : 'Yeni Şifreyi Onaylayın' ?>
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?= $language == 'en' ? 'Confirm your new password' : 'Yeni şifrenizi tekrar girin' ?>">
                </div>
                
                <?php if (shouldShowCaptcha('login')): ?>
                <div class="flex justify-center">
                    <?php echo getCaptchaScript(); ?>
                </div>
                <?php endif; ?>
                
                <div>
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                        <?= $language == 'en' ? 'Reset Password' : 'Şifreyi Sıfırla' ?>
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="text-center mt-4">
                <a href="/sifremi-unuttum" class="text-blue-600 dark:text-blue-400 hover:underline">
                    <?= $language == 'en' ? 'Request a new password reset' : 'Yeni şifre sıfırlama talebinde bulun' ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
