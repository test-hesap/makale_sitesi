<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$db = new Database();
$settings = getSettings();

// Sosyal medya bağlantılarını ayarlardan al
$socialLinks = [
    'facebook' => $settings['social_facebook'] ?? '',
    'twitter' => $settings['social_twitter'] ?? '',
    'instagram' => $settings['social_instagram'] ?? '',
    'youtube' => $settings['social_youtube'] ?? ''
];

// En az bir sosyal medya bağlantısı var mı kontrol et
$hasSocialLinks = !empty(array_filter($socialLinks));

?>
    <!-- Footer -->
    <footer class="bg-gray-800 dark:bg-gray-900 text-white mt-12">
        <!-- Footer Reklamları (Premium olmayanlara göster) -->
        <?php if (!isPremium() && !isAdmin()): ?>
        <?php
        $footerAds = $db->query("SELECT * FROM ads WHERE position = 'footer' AND is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
        if ($footerAds):
        ?>
        <div class="bg-gray-700 dark:bg-gray-800 py-4">
            <div class="container mx-auto px-4 text-center">
                <small class="text-gray-400 text-xs block mb-2"><?= t('advertisement') ?></small>
                <?= $footerAds['code'] ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Site Bilgileri -->
                <div>
                    <div class="mb-4">
                        <h3 class="text-xl font-bold"><?= $settings['site_title'] ?></h3>
                    </div>
                    <p class="text-gray-300 mb-4"><?= $settings['site_description'] ?></p>
                    
                    <?php if ($hasSocialLinks): ?>
                    <div class="flex space-x-3">
                        <?php if ($socialLinks['facebook']): ?>
                        <a href="<?= $socialLinks['facebook'] ?>" target="_blank" 
                           class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($socialLinks['twitter']): ?>
                        <a href="<?= $socialLinks['twitter'] ?>" target="_blank" 
                           class="w-8 h-8 bg-sky-500 text-white rounded-full flex items-center justify-center hover:bg-sky-600 transition-colors">
                            <i class="fab fa-twitter text-sm"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($socialLinks['instagram']): ?>
                        <a href="<?= $socialLinks['instagram'] ?>" target="_blank" 
                           class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center hover:from-purple-600 hover:to-pink-600 transition-all">
                            <i class="fab fa-instagram text-sm"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($socialLinks['youtube']): ?>
                        <a href="<?= $socialLinks['youtube'] ?>" target="_blank" 
                           class="w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors">
                            <i class="fab fa-youtube text-sm"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Hızlı Linkler -->
                <div>
                    <h4 class="text-lg font-semibold mb-4"><?= t('quick_links') ?></h4>
                    <ul class="space-y-2">
                        <li><a href="/" class="text-gray-300 hover:text-white transition-colors"><?= t('home') ?></a></li>
                        <li><a href="/hakkinda" class="text-gray-300 hover:text-white transition-colors"><?= t('about') ?></a></li>
                        <li><a href="/iletisim" class="text-gray-300 hover:text-white transition-colors"><?= t('contact') ?></a></li>
                        <li><a href="/uyeler" class="text-gray-300 hover:text-white transition-colors"><?= t('members') ?></a></li>
                        <li><a href="/gizlilik-politikasi" class="text-gray-300 hover:text-white transition-colors"><?= t('privacy_policy') ?></a></li>
                        <li><a href="/cerezler" class="text-gray-300 hover:text-white transition-colors"><?= t('cookie_policy') ?></a></li>
                        <?php if (!isLoggedIn()): ?>
                        <li><a href="/auth/login.php" class="text-gray-300 hover:text-white transition-colors"><?= t('login') ?></a></li>
                        <li><a href="/auth/register.php" class="text-gray-300 hover:text-white transition-colors"><?= t('register') ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Kategoriler -->
                <div>
                    <h4 class="text-lg font-semibold mb-4"><?= t('categories') ?></h4>
                    <ul class="space-y-2">
                        <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                        <li>
                            <a href="/kategori/<?= $category['slug'] ?>" class="text-gray-300 hover:text-white transition-colors">
                                <?= $category['name'] ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Premium Button -->
                <div>
                    <?php if (!isPremium() && !isAdmin()): ?>
                    <div class="mt-4">
                        <a href="<?= isLoggedIn() ? '/premium' : '/login' ?>" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-lg hover:from-yellow-500 hover:to-orange-600 transition-all">
                            <i class="fas fa-crown mr-2"></i>
                            <?= t('premium_become') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Alt Footer -->
        <div class="border-t border-gray-700 py-4">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                    <div class="text-gray-400 text-sm">
                        © <?= date('Y') ?> <?= $settings['site_title'] ?>. <?= t('all_rights_reserved') ?>
                    </div>
                    <div class="flex space-x-4 text-sm">
                        <a href="/gizlilik-politikasi" class="text-gray-400 hover:text-white transition-colors"><?= t('privacy_policy') ?></a>
                        <a href="/cerezler" class="text-gray-400 hover:text-white transition-colors"><?= t('cookie_policy') ?></a>
                        <a href="/kullanim-kosullari" class="text-gray-400 hover:text-white transition-colors"><?= t('terms_of_use') ?></a>
                        <a href="/sitemap.xml" class="text-gray-400 hover:text-white transition-colors">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="fixed bottom-4 right-4 w-12 h-12 bg-primary-600 text-white rounded-full shadow-lg hover:bg-primary-700 transition-all duration-300 opacity-0 invisible">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Scripts -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/ad-tracking.js"></script>

    <?php if (!isPremium() && !isAdmin()): ?>
    <?php
    $mobileFixedAd = $db->query("SELECT * FROM ads WHERE position = 'mobile_fixed_bottom' AND is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
    if ($mobileFixedAd):
    ?>
    <!-- Mobil Sabit Alt Reklam -->
    <div id="mobileFixedAd" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-2 md:hidden z-50 transition-all duration-300">
        <div class="flex justify-between items-center mb-1">
            <small class="text-gray-500 dark:text-gray-400 text-xs"><?= t('advertisement') ?></small>
            <button id="closeFixedAd" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div data-ad-id="<?= $mobileFixedAd['id'] ?>"><?= $mobileFixedAd['code'] ?></div>
    </div>
    <!-- Mobil cihazlarda reklam alanı için padding ekleme -->
    <div id="mobileFixedAdSpacer" class="h-24 md:hidden"></div>
    
    <script>
    // Mobil sabit reklam kapatma işlevi
    document.getElementById('closeFixedAd').addEventListener('click', function() {
        document.getElementById('mobileFixedAd').classList.add('hidden');
        document.getElementById('mobileFixedAdSpacer').classList.add('hidden');
        
        // Kullanıcı tercihi olarak saklayalım (24 saat boyunca göstermeyelim)
        const expiryDate = new Date();
        expiryDate.setTime(expiryDate.getTime() + (24 * 60 * 60 * 1000));
        document.cookie = "hideFixedAd=1; expires=" + expiryDate.toUTCString() + "; path=/; SameSite=Lax";
    });
    
    // Sayfa yüklendiğinde çerez kontrolü
    (function() {
        const hideCookie = document.cookie.split(';').some((item) => item.trim().startsWith('hideFixedAd='));
        if (hideCookie) {
            document.getElementById('mobileFixedAd').classList.add('hidden');
            document.getElementById('mobileFixedAdSpacer').classList.add('hidden');
        }
    })();
    </script>
    <?php endif; ?>
    <?php endif; ?>

    <script>
        // Scroll to top functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.remove('opacity-0', 'invisible');
                scrollToTopBtn.classList.add('opacity-100', 'visible');
            } else {
                scrollToTopBtn.classList.add('opacity-0', 'invisible');
                scrollToTopBtn.classList.remove('opacity-100', 'visible');
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Slideshow functionality
        function initSlideshow() {
            const slides = document.querySelectorAll('.slide');
            let currentSlide = 0;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            if (slides.length > 0) {
                showSlide(0);
                setInterval(nextSlide, 5000); // 5 saniyede bir geçiş
            }
        }

        // Initialize slideshow when page loads
        document.addEventListener('DOMContentLoaded', initSlideshow);

        // Line clamp CSS için fallback
        if (!CSS.supports('display', '-webkit-box')) {
            const clampElements = document.querySelectorAll('.line-clamp-2, .line-clamp-3');
            clampElements.forEach(element => {
                const lines = element.classList.contains('line-clamp-2') ? 2 : 3;
                const lineHeight = parseInt(getComputedStyle(element).lineHeight);
                const maxHeight = lineHeight * lines;
                
                if (element.scrollHeight > maxHeight) {
                    element.style.height = maxHeight + 'px';
                    element.style.overflow = 'hidden';
                }
            });
        }
    </script>
</body>
</html> 