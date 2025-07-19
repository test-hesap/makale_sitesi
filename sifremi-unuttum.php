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

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $cf_token = $_POST['cf-turnstile-response'] ?? '';
    $errors = [];
    
    if (shouldShowCaptcha('login') && !verifyCaptcha($cf_token)) {
        $errors[] = $language == 'en' 
            ? "CAPTCHA verification failed. Please try again." 
            : "CAPTCHA doğrulaması başarısız oldu. Lütfen tekrar deneyin.";
    }
    
    if (empty($email)) {
        $errors[] = $language == 'en' 
            ? "Email is required." 
            : "E-posta adresi gereklidir.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $language == 'en' 
            ? "Please enter a valid email address." 
            : "Geçerli bir e-posta adresi girin.";
    }
    
    if (empty($errors)) {
        try {
            // Bu e-posta adresine sahip bir kullanıcı var mı kontrol et
            $stmt = $database->query(
                "SELECT * FROM users WHERE email = ?",
                [$email]
            );
            $user = $stmt->fetch();
            
            if ($user) {
                // Önce var olan token'ları temizle
                $database->query(
                    "DELETE FROM password_resets WHERE email = ?",
                    [$email]
                );
                
                // Yeni token oluştur (24 saat geçerli)
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Debug için token bilgisini yazdır
                error_log("Oluşturulan token: " . $token . " - E-posta: " . $email . " - Süre: " . $expiresAt);
                
                // Token'ı veritabanına kaydet
                $database->query(
                    "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)",
                    [$email, $token, $expiresAt]
                );
                
                // Şifre sıfırlama e-postası gönder
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $siteUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
                $resetLink = $siteUrl . '/sifre-sifirla?token=' . $token;
                
                $subject = $language == 'en' ? 'Password Reset Request' : 'Şifre Sıfırlama Talebi';
                $message = $language == 'en' 
                    ? "Hello,\n\nYou have requested to reset your password. Please click on the link below to reset your password:\n\n$resetLink\n\nThis link will expire in 24 hours.\n\nIf you did not request a password reset, please ignore this email.\n\nRegards,\n{$settings['site_title']}" 
                    : "Merhaba,\n\nŞifrenizi sıfırlamak için talepte bulundunuz. Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:\n\n$resetLink\n\nBu bağlantı 24 saat içinde geçerliliğini yitirecektir.\n\nEğer şifre sıfırlama talebinde bulunmadıysanız, bu e-postayı görmezden gelebilirsiniz.\n\nSaygılarımızla,\n{$settings['site_title']}";
                
                $htmlMessage = str_replace("\n", "<br>", $message);
                
                $mailSent = sendEmail($email, $subject, $htmlMessage);
                
                if ($mailSent) {
                    $success = $language == 'en' 
                        ? "A password reset link has been sent to your email address. Please check your inbox." 
                        : "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi. Lütfen gelen kutunuzu kontrol edin.";
                } else {
                    $error = $language == 'en' 
                        ? "Failed to send email. Please try again later or contact support." 
                        : "E-posta gönderilemedi. Lütfen daha sonra tekrar deneyin veya destek ekibine başvurun.";
                }
            } else {
                // Kullanıcı bulunamadığında bile başarılı mesajı ver (güvenlik için)
                $success = $language == 'en' 
                    ? "If your email is registered, a password reset link has been sent to your email address." 
                    : "Eğer e-posta adresiniz kayıtlıysa, şifre sıfırlama bağlantısı e-posta adresinize gönderilmiştir.";
            }
        } catch (Exception $e) {
            $error = $language == 'en' 
                ? 'An error occurred: ' . $e->getMessage() 
                : 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

$pageTitle = ($language == 'en' ? 'Forgot Password - ' : 'Şifremi Unuttum - ') . $settings['site_title'];
$metaDescription = $language == 'en' ? 'Reset your password and regain access to your account.' : 'Şifrenizi sıfırlayın ve hesabınıza yeniden erişin.';

require_once 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <?= $language == 'en' ? 'Forgot Password' : 'Şifremi Unuttum' ?>
            </h1>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!isset($success)): ?>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                <?= $language == 'en' 
                    ? 'Enter your email address and we will send you a link to reset your password.' 
                    : 'E-posta adresinizi girin, şifrenizi sıfırlamak için size bir bağlantı göndereceğiz.' ?>
            </p>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Email Address' : 'E-posta Adresi' ?>
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?= $language == 'en' ? 'Enter your email address' : 'E-posta adresinizi girin' ?>">
                </div>
                
                <?php if (shouldShowCaptcha('login')): ?>
                <div class="flex justify-center">
                    <?php echo getCaptchaScript(); ?>
                </div>
                <?php endif; ?>
                
                <div>
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                        <?= $language == 'en' ? 'Send Reset Link' : 'Sıfırlama Bağlantısı Gönder' ?>
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <!-- Giriş Yap Linki -->
            <div class="mt-6">
                <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <?= $language == 'en' ? "Remember your password?" : 'Şifrenizi hatırladınız mı?' ?>
                    <a href="/login" class="text-blue-600 dark:text-blue-400 hover:underline">
                        <?= $language == 'en' ? 'Login' : 'Giriş Yap' ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
