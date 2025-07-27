<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Bakım modu kontrolü - index.php'de kontrol edilmemiş sayfalar için
if (!defined('MAINTENANCE_CHECKED') && isMaintenanceMode()) {
    showMaintenancePage();
}
if (!defined('MAINTENANCE_CHECKED')) {
    define('MAINTENANCE_CHECKED', true);
}

try {
    $db = new Database();
    $settings = getSettings();
    $currentTheme = getCurrentTheme();
    $currentUser = getCurrentUser(); 
} catch (Exception $e) {
    error_log("Header hatası: " . $e->getMessage());
    
    // Banlı kullanıcı kontrolü
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        require_once __DIR__ . '/ban_functions.php';
        
        if (isUserBanned($userId)) {
            header('Location: /banned.php');
            exit;
        }
    }
    
    // Diğer hatalar için oturumu sonlandır ve ana sayfaya yönlendir
    if (function_exists('logout')) {
        logout('/');
    } else {
        session_destroy();
        header('Location: /');
        exit;
    }
}

// Sayfa görüntülenme takibi
$currentPage = $_SERVER['REQUEST_URI'];
$pageTitle_for_tracking = $pageTitle ?? $settings['site_title'];

// Admin sayfaları ve API'ları hariç tut
$excludePages = ['/admin/', '/api/', '/auth/', '/ajax/', '.css', '.js', '.png', '.jpg', '.gif', '.ico'];
$shouldTrack = true;

foreach ($excludePages as $exclude) {
    if (strpos($currentPage, $exclude) !== false) {
        $shouldTrack = false;
        break;
    }
}

// Sayfa görüntülenme takibini yap
if ($shouldTrack) {
    trackPageView($currentPage, $pageTitle_for_tracking);
}

// Kategori bilgisini al (eğer category.php sayfasındaysak)
$currentCategory = null;
if (isset($GLOBALS['current_category'])) {
    $currentCategory = $GLOBALS['current_category'];
}

// Çevrimiçi kullanıcıları güncelle
updateOnlineUsers();
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>" class="<?= $currentTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? $settings['site_title'] ?></title>
    <meta name="description" content="<?= $metaDescription ?? $settings['site_description'] ?? getSiteSetting('seo_description') ?>">
    <?php if (isset($metaKeywords)): ?>
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <?php elseif (getSiteSetting('seo_tags')): ?>
    <meta name="keywords" content="<?= getSiteSetting('seo_tags') ?>">
    <?php endif; ?>
    
    <!-- Canonical URL -->
    <?php if (getSiteSetting('canonical_tag')): ?>
    <link rel="canonical" href="<?= getSiteSetting('canonical_tag') . $_SERVER['REQUEST_URI'] ?>">
    <?php endif; ?>
    
    <!-- SEO Meta Tags -->
    <meta property="og:title" content="<?= $pageTitle ?? $settings['site_title'] ?>">
    <meta property="og:description" content="<?= $metaDescription ?? $settings['site_description'] ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= getSiteUrl($_SERVER['REQUEST_URI']) ?>">
    <?php if (isset($ogImage)): ?>
    <meta property="og:image" content="<?= getSiteUrl($ogImage) ?>">
    <?php endif; ?>
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $pageTitle ?? $settings['site_title'] ?>">
    <meta name="twitter:description" content="<?= $metaDescription ?? $settings['site_description'] ?>">
    
    <!-- Favicon -->
    <?php 
    $favicon = getSiteSetting('site_favicon');
    // Önbellek kırıcı olarak timestamp parametresi ekle
    $cache_param = '?v=' . time();
    
    if (!empty($favicon)): 
    ?>
    <link rel="icon" type="image/x-icon" href="/<?= ltrim($favicon, '/') . $cache_param ?>">
    <link rel="shortcut icon" href="/<?= ltrim($favicon, '/') . $cache_param ?>">
    <link rel="apple-touch-icon" href="/<?= ltrim($favicon, '/') . $cache_param ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="/favicon.png<?= $cache_param ?>">
    <?php endif; ?>
    
    <!-- Dil Değiştirme Script -->
    <script src="/assets/js/language.js"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
        }
        
        .slide {
            display: none;
            animation: fadeIn 0.5s;
        }
        
        .slide.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .hamburger {
            cursor: pointer;
            width: 24px;
            height: 24px;
            transition: all 0.25s;
            position: relative;
        }
        
        .hamburger-top,
        .hamburger-middle,
        .hamburger-bottom {
            position: absolute;
            top: 0;
            left: 0;
            width: 24px;
            height: 2px;
            background: currentColor;
            transform: rotate(0);
            transition: all 0.5s;
        }
        
        .hamburger-middle {
            transform: translateY(7px);
        }
        
        .hamburger-bottom {
            transform: translateY(14px);
        }
        
        .open .hamburger-top {
            transform: rotate(45deg) translateY(6px) translateX(6px);
        }
        
        .open .hamburger-middle {
            display: none;
        }
        
        .open .hamburger-bottom {
            transform: rotate(-45deg) translateY(6px) translateX(-6px);
        }
    </style>
    
    <!-- Google Analytics -->
    <?php if (!empty($settings['google_analytics'])): ?>
    <?= $settings['google_analytics'] ?>
    <?php endif; ?>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white transition-colors duration-300">
    <?php if (isLoggedIn() && !$currentUser['is_approved']): 
        $language = getCurrentLanguage();
    ?>
    <div class="bg-yellow-100 border-b border-yellow-200 text-yellow-800 px-4 py-2">
        <div class="container mx-auto">
            <p class="text-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= $language == 'en' 
                    ? 'Your membership is pending admin approval. You can add articles after approval.'
                    : 'Üyeliğiniz admin onayı bekliyor. Onaylandıktan sonra makale ekleyebileceksiniz.' ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50 transition-colors duration-300">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <a href="/" class="flex items-center space-x-2">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <?php if (!empty($settings['site_logo_dark']) && $currentTheme === 'dark'): ?>
                                <img src="<?= getSiteUrl($settings['site_logo_dark']) ?>" alt="<?= $settings['site_title'] ?>" class="h-8 w-auto header-logo">
                            <?php else: ?>
                                <img src="<?= getSiteUrl($settings['site_logo']) ?>" alt="<?= $settings['site_title'] ?>" class="h-8 w-auto header-logo">
                            <?php endif; ?>
                        <?php else: ?>
                        <span class="text-xl font-bold text-primary-600 dark:text-primary-400"><?= $settings['site_title'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="/" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('home') ?></a>
                    
                    <!-- Kategoriler Dropdown -->
                    <div class="relative group">
                        <button class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors flex items-center">
                            <?= t('categories') ?> <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <?php
                            $categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll();
                            foreach ($categories as $cat):
                            ?>
                            <a href="/kategori/<?= $cat['slug'] ?>" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors <?= ($currentCategory && $currentCategory['slug'] === $cat['slug']) ? 'bg-primary-50 dark:bg-primary-900 text-primary-600 dark:text-primary-400' : '' ?>">
                                <?= $cat['name'] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <a href="/iletisim" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('contact') ?></a>
                    <a href="/hakkinda" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('about') ?></a>
                    <a href="/uyeler" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('members') ?></a>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if ($currentUser['is_approved']): ?>
                        <a href="/makale_ekle" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('add_article') ?></a>
                        <?php endif; ?>
                        
                        <?php if (!isPremium() && !isAdmin()): ?>
                        <a href="/premium" class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-4 py-2 rounded-lg hover:from-yellow-500 hover:to-orange-600 transition-all"><?= t('become_premium') ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>
                
                <!-- User Menu & Controls -->
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                    <!-- Mesaj Bildirimi -->
                    <a href="/mesajlar" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors relative">
                        <i class="fas fa-envelope"></i>
                        <?php $unreadCount = getUnreadMessageCount(); if ($unreadCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
                            <?= $unreadCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-sun dark:hidden"></i>
                        <i class="fas fa-moon hidden dark:inline"></i>
                    </button>
                    
                    <!-- Language Toggle - Tarayıcı uyumluluğu için güncellendi -->
                    <div class="relative language-dropdown">
                        <button id="language-toggle-btn" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" aria-label="Dil Seçimi">
                            <i class="fas fa-globe"></i>
                            <?php $currentLang = getCurrentLanguage(); ?>
                            <?= $currentLang === 'tr' ? 'TR' : 'EN' ?>
                        </button>
                        <div id="language-dropdown-menu" class="absolute top-full right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded-lg shadow-lg hidden transition-all duration-300 z-50">
                            <a href="#" onclick="setLanguage('tr'); return false;" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors <?= $currentLang === 'tr' ? 'font-bold text-primary-600' : '' ?>">Türkçe</a>
                            <a href="#" onclick="setLanguage('en'); return false;" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors <?= $currentLang === 'en' ? 'font-bold text-primary-600' : '' ?>">English</a>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                    <!-- User Dropdown - Hem touch hem hover desteği için güncellendi -->
                    <div class="relative user-dropdown">
                        <button id="user-menu-btn" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <?php 
                            // Profil resmi görüntüleme - aynı profil.php'deki gibi
                            $profileImageUrl = '';
                            
                            if (!empty($currentUser['profile_image'])) {
                                // Base64 kodlamasıyla resim gösterme (dosya erişim sorunu çözümü)
                                $imagePath = ltrim($currentUser['profile_image'], '/');
                                if (file_exists($imagePath) && is_readable($imagePath)) {
                                    $imageData = file_get_contents($imagePath);
                                    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                                    $base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                                    $profileImageUrl = $base64;
                                    echo '<img src="'.$profileImageUrl.'" alt="Profil" class="w-8 h-8 rounded-full object-cover">';
                                } else {
                                    // Varsayılan profil resmi
                                    echo '<div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white text-sm">';
                                    echo strtoupper(substr($currentUser['username'], 0, 1));
                                    echo '</div>';
                                }
                            } else {
                                // Profil resmi yok
                                echo '<div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white text-sm">';
                                echo strtoupper(substr($currentUser['username'], 0, 1));
                                echo '</div>';
                            }
                            ?>
                            <span class="hidden sm:inline"><?= $currentUser['username'] ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="user-menu-dropdown" class="absolute top-full right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg hidden transition-all duration-300">
                            <a href="/profil" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-user mr-2"></i> <?= t('profile') ?>
                            </a>
                            <a href="/mesajlar" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors relative">
                                <i class="fas fa-envelope mr-2"></i> <?= t('messages') ?>
                                <?php $unreadCount = getUnreadMessageCount(); if ($unreadCount > 0): ?>
                                <span class="absolute right-2 top-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                                    <?= $unreadCount ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <?php if (!isPremium() && !isAdmin()): ?>
                            <a href="/premium" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-yellow-600">
                                <i class="fas fa-crown mr-2"></i> <?= t('become_premium') ?>
                            </a>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                            <hr class="my-1 border-gray-200 dark:border-gray-600">
                            <a href="/admin" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-cog mr-2"></i> <?= t('admin_panel') ?>
                            </a>
                            <?php endif; ?>
                            <hr class="my-1 border-gray-200 dark:border-gray-600">
                            <a href="/auth/logout.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-red-600">
                                <i class="fas fa-sign-out-alt mr-2"></i> <?= t('logout') ?>
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Login/Register -->
                    <div class="flex items-center space-x-2">
                        <a href="/login" class="px-4 py-2 text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"><?= t('login') ?></a>
                        <a href="/register" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"><?= t('register') ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mobile Menu Button -->
                    <button class="md:hidden hamburger" onclick="toggleMobileMenu()">
                        <div class="hamburger-top"></div>
                        <div class="hamburger-middle"></div>
                        <div class="hamburger-bottom"></div>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="md:hidden hidden pb-4">
                <nav class="flex flex-col space-y-2">
                    <a href="/" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('home') ?></a>
                    
                    <!-- Mobile Categories -->
                    <div class="py-2">
                        <button onclick="toggleMobileCategories()" class="w-full text-left hover:text-primary-600 dark:hover:text-primary-400 transition-colors flex items-center justify-between">
                            <?= t('categories') ?> <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="mobile-categories" class="hidden mt-2 ml-4 space-y-1">
                            <?php foreach ($categories as $cat): ?>
                            <a href="/kategori/<?= $cat['slug'] ?>" class="block py-1 text-sm hover:text-primary-600 dark:hover:text-primary-400 transition-colors <?= ($currentCategory && $currentCategory['slug'] === $cat['slug']) ? 'text-primary-600 dark:text-primary-400 font-semibold' : '' ?>">
                                <?= $cat['name'] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <a href="/iletisim" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('contact') ?></a>
                    <a href="/hakkinda" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('about') ?></a>
                    <a href="/uyeler" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('members') ?></a>
                    
                    <!-- Mobil Dil Seçenekleri -->
                    <div class="py-2">
                        <button onclick="toggleMobileLanguages()" class="w-full text-left hover:text-primary-600 dark:hover:text-primary-400 transition-colors flex items-center justify-between">
                            <?php $currentLang = getCurrentLanguage(); ?>
                            <span><i class="fas fa-globe mr-2"></i> <?= $currentLang === 'tr' ? 'Türkçe' : 'English' ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="mobile-languages" class="hidden mt-2 ml-4 space-y-1">
                            <a href="#" onclick="setLanguage('tr'); return false;" class="block py-1 text-sm hover:text-primary-600 dark:hover:text-primary-400 transition-colors <?= $currentLang === 'tr' ? 'text-primary-600 dark:text-primary-400 font-semibold' : '' ?>">
                                Türkçe
                            </a>
                            <a href="#" onclick="setLanguage('en'); return false;" class="block py-1 text-sm hover:text-primary-600 dark:hover:text-primary-400 transition-colors <?= $currentLang === 'en' ? 'text-primary-600 dark:text-primary-400 font-semibold' : '' ?>">
                                English
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if ($currentUser['is_approved']): ?>
                        <a href="/makale_ekle" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('add_article') ?></a>
                        <?php endif; ?>
                        
                        <?php if (!isPremium() && !isAdmin()): ?>
                        <a href="/premium" class="py-2 text-yellow-600 hover:text-yellow-700 transition-colors"><?= t('become_premium') ?></a>
                        <?php endif; ?>
                        
                        <hr class="my-2 border-gray-200 dark:border-gray-600">
                        <a href="/profil" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('profile') ?></a>
                        <a href="/mesajlar" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('messages') ?></a>
                        
                        <?php if (isAdmin()): ?>
                        <a href="/admin" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('admin_panel') ?></a>
                        <?php endif; ?>
                        
                        <a href="/auth/logout.php" class="py-2 text-red-600 hover:text-red-700 transition-colors"><?= t('logout') ?></a>
                    <?php else: ?>
                        <hr class="my-2 border-gray-200 dark:border-gray-600">
                        <a href="/login" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('login') ?></a>
                        <a href="/register" class="py-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= t('register') ?></a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Header Reklamları (Premium olmayanlara göster) -->
    <?php if (!isPremium() && !isAdmin()): ?>
    <?php
    $headerAds = $db->query("SELECT * FROM ads WHERE position = 'header' AND is_active = 1 ORDER BY display_order")->fetchAll();
    foreach ($headerAds as $ad):
    ?>
    <div class="bg-white dark:bg-gray-800 py-4">
        <div class="container mx-auto px-4">
            <div class="text-center">
            <div class="inline-block">
            
                    <?= $ad['code'] ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dil menüsü tıklama kontrolü
            const languageToggleBtn = document.getElementById('language-toggle-btn');
            const languageDropdownMenu = document.getElementById('language-dropdown-menu');
            
            if (languageToggleBtn) {
                languageToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Kullanıcı menüsü açıksa kapat
                    if (userMenuDropdown && !userMenuDropdown.classList.contains('hidden')) {
                        userMenuDropdown.classList.add('hidden');
                        userMenuDropdown.classList.remove('menu-open');
                    }
                    
                    // Dil menüsünü toggle
                    languageDropdownMenu.classList.toggle('hidden');
                    languageDropdownMenu.classList.toggle('menu-open');
                    e.stopPropagation();
                });
                
                // Kullanıcı menüsü tıklama kontrolü - tüm cihazlar için
                const userMenuBtn = document.getElementById('user-menu-btn');
                const userMenuDropdown = document.getElementById('user-menu-dropdown');
                
                if (userMenuBtn && userMenuDropdown) {
                    userMenuBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Eğer dil menüsü açıksa kapat
                        if (languageDropdownMenu && !languageDropdownMenu.classList.contains('hidden')) {
                            languageDropdownMenu.classList.add('hidden');
                        }
                        
                        // Kullanıcı menüsünü toggle
                        userMenuDropdown.classList.toggle('hidden');
                        userMenuDropdown.classList.toggle('menu-open');
                        e.stopPropagation();
                    });
                }
                
                // Sayfa herhangi bir yerine tıklandığında açık menüleri kapat (hem tıklama hem dokunmatik için)
                function closeMenusOnOutsideClick(e) {
                    if (languageToggleBtn && languageDropdownMenu && !languageToggleBtn.contains(e.target) && !languageDropdownMenu.contains(e.target)) {
                        languageDropdownMenu.classList.add('hidden');
                        languageDropdownMenu.classList.remove('menu-open');
                    }
                    
                    if (userMenuBtn && userMenuDropdown && !userMenuBtn.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                        userMenuDropdown.classList.add('hidden');
                        userMenuDropdown.classList.remove('menu-open');
                    }
                }
                
                document.addEventListener('click', closeMenusOnOutsideClick);
                document.addEventListener('touchend', closeMenusOnOutsideClick);
            }
            
            // Hover davranışını kaldırıyoruz, tamamıyla tıklama kontrolüne geçiyoruz
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    /* Tüm cihazlarda sadece tıklama ile açılsın */
                    .menu-open {
                        display: block !important;
                    }
                </style>
            `);
        });
        
        // Theme toggle
        function toggleTheme() {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            fetch('/api/set-theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: currentTheme })
            }).then(() => {
                document.documentElement.classList.remove('light', 'dark');
                document.documentElement.classList.add(currentTheme);
                updateThemeIcons(currentTheme);
                updateSiteLogo(currentTheme);
            });
        }
        
        function updateThemeIcons(theme) {
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            if (darkIcon && lightIcon) {
                if (theme === 'dark') {
                    darkIcon.classList.add('hidden');
                    lightIcon.classList.remove('hidden');
                } else {
                    darkIcon.classList.remove('hidden');
                    lightIcon.classList.add('hidden');
                }
            }
        }
        
        function updateSiteLogo(theme) {
            const siteLogo = document.querySelector('.header-logo');
            if (!siteLogo) return;
            
            // PHP'den değerleri al
            const lightLogoUrl = "<?= !empty($settings['site_logo']) ? getSiteUrl($settings['site_logo']) : '' ?>";
            const darkLogoUrl = "<?= !empty($settings['site_logo_dark']) ? getSiteUrl($settings['site_logo_dark']) : '' ?>";
            const siteTitle = "<?= $settings['site_title'] ?>";
            
            // Eğer karanlık tema logosu varsa, tema değişimine göre logoyu güncelle
            if (darkLogoUrl) {
                siteLogo.src = (theme === 'dark') ? darkLogoUrl : lightLogoUrl;
            }
        }
        
        // Language toggle
        function setLanguage(lang) {
            fetch('/api/set-language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ language: lang })
            }).then(() => {
                location.reload();
            });
        }
        
        // Mobile menu toggle - geliştirilmiş sürüm
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const hamburger = document.querySelector('.hamburger');
            
            // Diğer açık menüleri kapat
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            const languageDropdownMenu = document.getElementById('language-dropdown-menu');
            
            if (userMenuDropdown) {
                userMenuDropdown.classList.add('hidden');
                userMenuDropdown.classList.remove('menu-open');
            }
            
            if (languageDropdownMenu) {
                languageDropdownMenu.classList.add('hidden');
                languageDropdownMenu.classList.remove('menu-open');
            }
            
            menu.classList.toggle('hidden');
            hamburger.classList.toggle('open');
        }
        
        // Mobile categories toggle
        function toggleMobileCategories() {
            const categories = document.getElementById('mobile-categories');
            categories.classList.toggle('hidden');
        }
        
        // Mobile languages toggle
        function toggleMobileLanguages() {
            const languages = document.getElementById('mobile-languages');
            languages.classList.toggle('hidden');
        }
        
        // Update user menu after login
        function updateUserMenu() {
            fetch('/api/get-user-status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.loggedIn) {
                        location.reload(); // Sayfa yenile
                    }
                });
        }
        
        // Check login status periodically
        if (window.location.pathname.includes('/auth/login.php')) {
            setInterval(updateUserMenu, 2000);
        }
    </script> 
</body>
</html>