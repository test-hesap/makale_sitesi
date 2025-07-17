<?php
require_once 'includes/header.php';

$language = getCurrentLanguage();
$pageTitle = ($language == 'en' ? 'About Us - ' : 'Hakkımızda - ') . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'Information about our site, our mission and vision.' : 'Sitemiz hakkında bilgi, misyonumuz ve vizyonumuz.';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Başlık -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                <?php echo $language == 'en' ? 'About Us' : 'Hakkımızda'; ?>
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                <?php echo $language == 'en' ? 'About our modern article platform and community' : 'Modern makale platformu ve topluluğumuz hakkında'; ?>
            </p>
        </div>

        <!-- Ana İçerik -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Ana Metin -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <?php echo $language == 'en' ? 'Our Mission' : 'Misyonumuz'; ?>
                    </h2>
                    <div class="prose dark:prose-invert max-w-none">
                        <?php if ($language == 'en'): ?>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            <?= getSiteSetting('site_title') ?> aims to be a leading platform for quality content production and sharing.
                            Our goal is to provide a modern environment where writers can freely express their ideas and readers can
                            easily access valuable information.
                        </p>
                        
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            On our platform, you can find articles by expert writers in technology, health, education,
                            culture, and many other fields. By offering both free and premium content, we aim to appeal to readers of all levels.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-8 mb-4">
                            Our Vision
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Being the most reliable and comprehensive article platform, raising standards in knowledge sharing,
                            and contributing to the development of digital reading culture form the foundation of our vision.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-8 mb-4">
                            Our Values
                        </h3>
                        <ul class="text-gray-600 dark:text-gray-300 space-y-2">
                            <li>• <strong>Quality:</strong> We only share verified and valuable content</li>
                            <li>• <strong>Freedom:</strong> We support writers in freely expressing their views</li>
                            <li>• <strong>Community:</strong> We build strong connections between readers and writers</li>
                            <li>• <strong>Innovation:</strong> We follow technological developments and continuously improve the platform experience</li>
                            <li>• <strong>Security:</strong> Protection of user data is our priority</li>
                        </ul>
                        <?php else: ?>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            <?= getSiteSetting('site_title') ?>, kaliteli içerik üretimi ve paylaşımı konusunda 
                            öncü bir platform olmayı hedefler. Amacımız, yazarların fikirlerini özgürce 
                            ifade edebileceği, okuyucuların ise değerli bilgilere kolayca ulaşabileceği 
                            modern bir ortam sağlamaktır.
                        </p>
                        
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Platformumuzda teknoloji, sağlık, eğitim, kültür ve daha birçok alanda 
                            uzman yazarların makalelerini bulabilirsiniz. Hem ücretsiz hem de premium 
                            içerikler sunarak, her seviyeden okuyucuya hitap etmeyi amaçlıyoruz.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-8 mb-4">
                            Vizyonumuz
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Türkiye'nin en güvenilir ve kapsamlı makale platformu olmak, bilgi 
                            paylaşımında standartları yükseltmek ve dijital okuma kültürünün 
                            gelişmesine katkıda bulunmak vizyonumuzun temelini oluşturur.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-8 mb-4">
                            Değerlerimiz
                        </h3>
                        <ul class="text-gray-600 dark:text-gray-300 space-y-2">
                            <li>• <strong>Kalite:</strong> Yalnızca doğrulanmış ve değerli içerikleri paylaşırız</li>
                            <li>• <strong>Özgürlük:</strong> Yazarların görüşlerini özgürce ifade etmelerini destekleriz</li>
                            <li>• <strong>Topluluk:</strong> Okuyucu ve yazarlar arasında güçlü bağlar kurarız</li>
                            <li>• <strong>İnovasyon:</strong> Teknolojik gelişmeleri takip eder, platform deneyimini sürekli iyileştiririz</li>
                            <li>• <strong>Güvenlik:</strong> Kullanıcı verilerinin korunması önceliğimizdir</li>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <?php echo $language == 'en' ? 'Our Platform in Numbers' : 'Rakamlarla Platformumuz'; ?>
                    </h2>
                    
                    <?php
                    // İstatistikleri çek
                    try {
                        $database = new Database();
                        $db = $database->pdo;
                        
                        $articleCount = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
                        $userCount = $db->query("SELECT COUNT(*) FROM users WHERE is_approved = 1")->fetchColumn();
                        $categoryCount = $db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
                        $commentCount = $db->query("SELECT COUNT(*) FROM comments WHERE is_approved = 1")->fetchColumn();
                    } catch (Exception $e) {
                        $articleCount = 0;
                        $userCount = 0;
                        $categoryCount = 0;
                        $commentCount = 0;
                    }
                    ?>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600 mb-2"><?= number_format($articleCount) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo $language == 'en' ? 'Articles' : 'Makale'; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600 mb-2"><?= number_format($userCount) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo $language == 'en' ? 'Users' : 'Kullanıcı'; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-purple-600 mb-2"><?= number_format($categoryCount) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo $language == 'en' ? 'Categories' : 'Kategori'; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600 mb-2"><?= number_format($commentCount) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo $language == 'en' ? 'Comments' : 'Yorum'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Takım -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-users text-blue-500 mr-2"></i><?php echo $language == 'en' ? 'Our Team' : 'Takımımız'; ?>
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">A</span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Admin</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo $language == 'en' ? 'Platform Administrator' : 'Platform Yöneticisi'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İletişim -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-envelope text-green-500 mr-2"></i><?php echo $language == 'en' ? 'Contact' : 'İletişim'; ?>
                    </h3>
                    <div class="space-y-3 text-sm">
                        <?php
                        $contact_email = getSiteSetting('contact_email');
                        $contact_phone = getSiteSetting('contact_phone');
                        $contact_address = getSiteSetting('contact_address');
                        $site_url = getSiteSetting('site_url');
                        ?>
                        
                        <?php if (!empty($contact_email)): ?>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-envelope text-gray-400"></i>
                            <span class="text-gray-600 dark:text-gray-300"><?= htmlspecialchars($contact_email) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($site_url)): ?>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-globe text-gray-400"></i>
                            <span class="text-gray-600 dark:text-gray-300"><?= htmlspecialchars($site_url) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($contact_phone)): ?>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-phone text-gray-400"></i>
                            <span class="text-gray-600 dark:text-gray-300"><?= htmlspecialchars($contact_phone) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($contact_address)): ?>
                        <div class="flex items-start space-x-2">
                            <i class="fas fa-map-marker-alt text-gray-400 mt-1"></i>
                            <span class="text-gray-600 dark:text-gray-300"><?= htmlspecialchars($contact_address) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="/contact" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i><?php echo $language == 'en' ? 'Write to Us' : 'Bize Yazın'; ?>
                        </a>
                    </div>
                </div>

                <!-- Sosyal Medya -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-share-alt text-purple-500 mr-2"></i><?php echo $language == 'en' ? 'Social Media' : 'Sosyal Medya'; ?>
                    </h3>
                    <div class="flex space-x-3">
                        <?php
                        $twitter_url = getSiteSetting('social_twitter');
                        $facebook_url = getSiteSetting('social_facebook');
                        $instagram_url = getSiteSetting('social_instagram');
                        $youtube_url = getSiteSetting('social_youtube');
                        $linkedin_url = getSiteSetting('social_linkedin');
                        $github_url = getSiteSetting('social_github');
                        ?>
                        
                        <?php if (!empty($twitter_url)): ?>
                        <a href="<?php echo htmlspecialchars($twitter_url); ?>" target="_blank" class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white hover:bg-blue-700 transition-colors" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($facebook_url)): ?>
                        <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank" class="w-10 h-10 bg-blue-800 rounded-lg flex items-center justify-center text-white hover:bg-blue-900 transition-colors" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($instagram_url)): ?>
                        <a href="<?php echo htmlspecialchars($instagram_url); ?>" target="_blank" class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center text-white hover:bg-red-700 transition-colors" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($youtube_url)): ?>
                        <a href="<?php echo htmlspecialchars($youtube_url); ?>" target="_blank" class="w-10 h-10 bg-red-700 rounded-lg flex items-center justify-center text-white hover:bg-red-800 transition-colors" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($linkedin_url)): ?>
                        <a href="<?php echo htmlspecialchars($linkedin_url); ?>" target="_blank" class="w-10 h-10 bg-blue-700 rounded-lg flex items-center justify-center text-white hover:bg-blue-800 transition-colors" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($github_url)): ?>
                        <a href="<?php echo htmlspecialchars($github_url); ?>" target="_blank" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center text-white hover:bg-gray-900 transition-colors" title="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 