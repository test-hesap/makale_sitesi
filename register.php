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

// IP kontrolü - Banlı IP ise kayıt olmayı engelle
$clientIP = alternativeGetClientIP();
if (isIPBanned($clientIP)) {
    $error = getCurrentLanguage() == 'en' ? 'Registration is not available from your IP address.' : 'IP adresinizden kayıt yapılamıyor.';
    // Logla
    error_log("Banned IP attempted registration: $clientIP");
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isIPBanned($clientIP)) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    // Kayıt denemesini takip et
    trackRegistrationAttempt($username, $email, false);
    
    try {
        // Dil kontrolü
        $language = getCurrentLanguage();
        
        // Hata mesajları
        $errorMessages = [
            'empty_fields' => $language == 'en' ? 'Please fill in all fields.' : 'Lütfen tüm alanları doldurun.',
            'invalid_email' => $language == 'en' ? 'Invalid email address.' : 'Geçersiz e-posta adresi.',
            'short_password' => $language == 'en' ? 'Password must be at least 6 characters.' : 'Şifre en az 6 karakter olmalıdır.',
            'passwords_not_match' => $language == 'en' ? 'Passwords do not match.' : 'Şifreler eşleşmiyor.'
        ];
        
        // Validasyonlar
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception($errorMessages['empty_fields']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception($errorMessages['invalid_email']);
        }
        
        if (strlen($password) < 6) {
            throw new Exception($errorMessages['short_password']);
        }
        
        if ($password !== $passwordConfirm) {
            throw new Exception($errorMessages['passwords_not_match']);
        }
        
        // Kullanıcı adı ve e-posta kontrolü
        $stmt = $database->query(
            "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        if ($stmt->fetchColumn() > 0) {
            $errorMessage = $language == 'en' 
                ? 'This username or email is already in use.'
                : 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.';
            throw new Exception($errorMessage);
        }
        
        // Kullanıcıyı kaydet
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $database->query(
            "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
            [$username, $email, $hashedPassword]
        );
        
        // Kullanıcı ID'sini al
        $userId = $database->pdo->lastInsertId();
        
        // Ücretsiz planı bul
        $freePlan = $database->query(
            "SELECT * FROM subscription_plans WHERE price = 0 AND is_active = 1 LIMIT 1"
        )->fetch();
        
        if ($freePlan) {
            $database->pdo->beginTransaction();
            
            try {
                // Önce ücretsiz ödeme kaydı oluştur
                $database->query(
                    "INSERT INTO payments (user_id, plan_id, amount, payment_date, status, payment_method) 
                     VALUES (?, ?, 0, NOW(), 'completed', 'free')",
                    [$userId, $freePlan['id']]
                );
                
                $paymentId = $database->pdo->lastInsertId();
                
                // Sonra ücretsiz aboneliği ekle (100 yıllık süre)
                $database->query(
                    "INSERT INTO user_subscriptions (user_id, plan_id, payment_id, start_date, end_date, status) 
                     VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 100 YEAR), 'active')",
                    [$userId, $freePlan['id'], $paymentId]
                );
                
                $database->pdo->commit();
            } catch (Exception $e) {
                $database->pdo->rollBack();
                $errorMessage = $language == 'en'
                    ? 'An error occurred while creating the membership: ' . $e->getMessage()
                    : 'Üyelik oluşturulurken bir hata oluştu: ' . $e->getMessage();
                throw new Exception($errorMessage);
            }
        }
        
        // Kayıt denemesini başarılı olarak kaydet
        trackRegistrationAttempt($username, $email, true);
        
        // Kullanıcının IP adresini kaydet
        $clientIP = getClientIP();
        $database->query(
            "UPDATE users SET last_ip = ? WHERE id = ?",
            [$clientIP, $userId]
        );
        
        // Direkt giriş yap
        $_SESSION['user_id'] = $userId;
        
        // Ana sayfaya yönlendir
        header('Location: /');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$language = getCurrentLanguage();
$pageTitle = ($language == 'en' ? 'Register - ' : 'Kayıt Ol - ') . $settings['site_title'];
$metaDescription = $language == 'en' ? 'Create a new account and access content.' : 'Yeni hesap oluşturun ve içeriklere erişin.';

require_once 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <?= $language == 'en' ? 'Register' : 'Kayıt Ol' ?>
            </h1>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                // Hata mesajlarını çevir
                $errorMessage = $error;
                if ($language == 'en') {
                    $errorMessages = [
                        'Lütfen tüm alanları doldurun.' => 'Please fill in all fields.',
                        'Geçersiz e-posta adresi.' => 'Invalid email address.',
                        'Şifre en az 6 karakter olmalıdır.' => 'Password must be at least 6 characters.',
                        'Şifreler eşleşmiyor.' => 'Passwords do not match.',
                        'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.' => 'This username or email is already in use.',
                        'Üyelik oluşturulurken bir hata oluştu:' => 'An error occurred while creating the membership:',
                    ];
                    foreach ($errorMessages as $tr => $en) {
                        $errorMessage = str_replace($tr, $en, $errorMessage);
                    }
                }
                echo htmlspecialchars($errorMessage);
                ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Username' : 'Kullanıcı Adı' ?>
                    </label>
                    <input type="text" name="username" required 
                           placeholder="<?= $language == 'en' ? 'Enter username' : 'Kullanıcı adı girin' ?>"
                           class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-md shadow-sm 
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                           dark:text-white placeholder-gray-400 dark:placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Email' : 'E-posta' ?>
                    </label>
                    <input type="email" name="email" required 
                           placeholder="<?= $language == 'en' ? 'Enter email address' : 'E-posta adresini girin' ?>"
                           class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-md shadow-sm 
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                           dark:text-white placeholder-gray-400 dark:placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Password' : 'Şifre' ?>
                    </label>
                    <input type="password" name="password" required 
                           placeholder="<?= $language == 'en' ? 'Enter password' : 'Şifre girin' ?>"
                           autocomplete="new-password"
                           class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-md shadow-sm 
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                           dark:text-white placeholder-gray-400 dark:placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Confirm Password' : 'Şifre Tekrar' ?>
                    </label>
                    <input type="password" name="password_confirm" required 
                           placeholder="<?= $language == 'en' ? 'Confirm password' : 'Şifreyi tekrar girin' ?>"
                           autocomplete="new-password"
                           class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-md shadow-sm 
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400
                           dark:text-white placeholder-gray-400 dark:placeholder-gray-400">
                </div>
                <?php if (shouldShowCaptcha('register')): ?>
                <div class="flex justify-center">
                    <?php echo getCaptchaScript(); ?>
                </div>
                <?php endif; ?>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <?= $language == 'en' ? 'Register' : 'Kayıt Ol' ?>
                </button>
            </form>
            
            <!-- Giriş Linki -->
            <div class="mt-6">
                <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <?= $language == 'en' ? 'Already have an account?' : 'Zaten hesabınız var mı?' ?> 
                    <a href="/login" class="text-blue-600 dark:text-blue-400 hover:underline">
                        <?= $language == 'en' ? 'Login' : 'Giriş yapın' ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 