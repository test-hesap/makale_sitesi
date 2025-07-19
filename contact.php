<?php
require_once 'includes/header.php';

$language = getCurrentLanguage();
$pageTitle = ($language == 'en' ? 'Contact - ' : 'İletişim - ') . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'Contact us. Write to us for your questions and suggestions.' : 'Bizimle iletişime geçin. Sorularınız ve önerileriniz için bize yazın.';

// Form işleme
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = $language == 'en' ? 'Please fill in all fields.' : 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $language == 'en' ? 'Please enter a valid email address.' : 'Geçerli bir e-posta adresi girin.';
    } else {
        try {
            $database = new Database();
            $db = $database->pdo;
            
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            
            $success = true;
            
            // E-posta gönderme (opsiyonel)
            $adminEmail = getSiteSetting('admin_email', 'admin@example.com');
            $emailSubject = ($language == 'en' ? 'Contact Form: ' : 'İletişim Formu: ') . $subject;
            $emailBody = ($language == 'en' ? "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message" : "İsim: $name\nE-posta: $email\nKonu: $subject\n\nMesaj:\n$message");
            
            // E-posta gönderme işlemi burada yapılabilir
            // sendEmail($adminEmail, $emailSubject, $emailBody, false);
            
        } catch (Exception $e) {
            $error = $language == 'en' ? 'An error occurred while sending your message. Please try again.' : 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Başlık -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                <?php echo $language == 'en' ? 'Contact' : 'İletişim'; ?>
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                <?php echo $language == 'en' ? 'Write to us for your questions, suggestions or feedback' : 'Sorularınız, önerileriniz veya geri bildirimleriniz için bize yazın'; ?>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- İletişim Formu -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <i class="fas fa-envelope text-blue-500 mr-3"></i>
                        <?php echo $language == 'en' ? 'Write to Us' : 'Bize Yazın'; ?>
                    </h2>

                    <?php if ($success): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $language == 'en' ? 'Your message has been successfully sent! We will get back to you as soon as possible.' : 'Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.'; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= $error ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo $language == 'en' ? 'Full Name' : 'Ad Soyad'; ?>
                            </label>
                            <input type="text" name="name" required 
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all duration-200"
                                   placeholder="<?php echo $language == 'en' ? 'Enter your full name' : 'Ad soyadınızı girin'; ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo $language == 'en' ? 'Email' : 'E-posta'; ?>
                            </label>
                            <input type="email" name="email" required 
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all duration-200"
                                   placeholder="<?php echo $language == 'en' ? 'Enter your email address' : 'E-posta adresinizi girin'; ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo $language == 'en' ? 'Subject' : 'Konu'; ?>
                            </label>
                            <input type="text" name="subject" required 
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all duration-200"
                                   placeholder="<?php echo $language == 'en' ? 'Enter the subject of your message' : 'Mesajınızın konusunu girin'; ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo $language == 'en' ? 'Message' : 'Mesaj'; ?>
                            </label>
                            <textarea name="message" required rows="5" 
                                      class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all duration-200 resize-vertical"
                                      placeholder="<?php echo $language == 'en' ? 'Write your message here...' : 'Mesajınızı buraya yazın...'; ?>"></textarea>
                        </div>
                        <?php if (shouldShowCaptcha('contact')): ?>
                        <div class="flex justify-center">
                            <?php echo getCaptchaScript(); ?>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <?php echo $language == 'en' ? 'Send Message' : 'Mesaj Gönder'; ?>
                        </button>
                    </form>
                </div>
            </div>

                <!-- İletişim Bilgileri -->
            <div class="space-y-6">
                <!-- İletişim Detayları -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i><?php echo $language == 'en' ? 'Contact Information' : 'İletişim Bilgileri'; ?>
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-envelope text-gray-400 mt-1"></i>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white"><?php echo $language == 'en' ? 'Email' : 'E-posta'; ?></div>
                                <div class="text-gray-600 dark:text-gray-300">info@example.com</div>
                            </div>
                        </div>
                    </div>
                </div>                <!-- SSS -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-question-circle text-green-500 mr-2"></i><?php echo $language == 'en' ? 'Frequently Asked Questions' : 'Sık Sorulan Sorular'; ?>
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white mb-1">
                                <?php echo $language == 'en' ? 'How can I write an article?' : 'Nasıl makale yazabilirim?'; ?>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                <?php echo $language == 'en' ? 'You can write articles after creating an account and logging in.' : 'Hesap oluşturup giriş yaptıktan sonra makale yazabilirsiniz.'; ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white mb-1">
                                <?php echo $language == 'en' ? 'What is premium membership?' : 'Premium üyelik nedir?'; ?>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                <?php echo $language == 'en' ? 'It provides access to exclusive content and an ad-free experience.' : 'Özel içeriklere erişim ve reklamsız deneyim sunar.'; ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white mb-1">
                                <?php echo $language == 'en' ? 'How soon will I get a response?' : 'Ne kadar sürede yanıt alırım?'; ?>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300">
                                <?php echo $language == 'en' ? 'We usually respond within 24-48 hours.' : 'Genellikle 24-48 saat içinde yanıtlıyoruz.'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sosyal Medya -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-share-alt text-purple-500 mr-2"></i><?php echo $language == 'en' ? 'Follow Us' : 'Bizi Takip Edin'; ?>
                    </h3>
                    <div class="flex space-x-3">
                        <?php
                        $twitter_url = getSiteSetting('social_twitter');
                        $facebook_url = getSiteSetting('social_facebook');
                        $instagram_url = getSiteSetting('social_instagram');
                        $youtube_url = getSiteSetting('social_youtube');
                        ?>
                        
                        <?php if (!empty($twitter_url)): ?>
                        <a href="<?php echo htmlspecialchars($twitter_url); ?>" target="_blank" class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($facebook_url)): ?>
                        <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank" class="w-10 h-10 bg-blue-800 rounded-lg flex items-center justify-center text-white hover:bg-blue-900 transition-colors">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($instagram_url)): ?>
                        <a href="<?php echo htmlspecialchars($instagram_url); ?>" target="_blank" class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center text-white hover:bg-red-700 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($youtube_url)): ?>
                        <a href="<?php echo htmlspecialchars($youtube_url); ?>" target="_blank" class="w-10 h-10 bg-red-700 rounded-lg flex items-center justify-center text-white hover:bg-red-800 transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 