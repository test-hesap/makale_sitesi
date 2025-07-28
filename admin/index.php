<?php
// Çıktı tamponlamasını başlat
ob_start();

// Base path tanımı
define('BASE_PATH', dirname(__DIR__));

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isUserAdmin()) {
    header('Location: /auth/login.php?redirect=' . urlencode('/admin'));
    exit;
}

// Veritabanı bağlantısı ve istatistik hesaplama
$db = null;
$database_error = null;
$stats = [
    'articles' => ['total' => 0, 'published' => 0, 'draft' => 0, 'pending' => 0],
    'users' => ['total' => 0, 'active' => 0, 'premium' => 0, 'pending' => 0], 
    'comments' => ['total' => 0, 'approved' => 0, 'pending' => 0],
    'payments' => ['total' => 0, 'success' => 0, 'revenue' => 0],
    'messages' => ['total' => 0, 'read' => 0, 'unread' => 0]
];

try {
    $database = new Database();
    $db = $database->pdo;

    // Dashboard istatistikleri

// Toplam makale sayısı
$articleQuery = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                 FROM articles";
$articleStmt = $db->prepare($articleQuery);
$articleStmt->execute();
$stats['articles'] = $articleStmt->fetch(PDO::FETCH_ASSOC);

// Toplam kullanıcı sayısı
$userQuery = "SELECT COUNT(*) as total,
                     SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as active,
                     SUM(CASE WHEN is_premium = 1 THEN 1 ELSE 0 END) as premium,
                     SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending
              FROM users WHERE is_admin != 1";
$userStmt = $db->prepare($userQuery);
$userStmt->execute();
$stats['users'] = $userStmt->fetch(PDO::FETCH_ASSOC);

// Toplam yorum sayısı
$commentQuery = "SELECT COUNT(*) as total,
                        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending
                 FROM comments";
$commentStmt = $db->prepare($commentQuery);
$commentStmt->execute();
$stats['comments'] = $commentStmt->fetch(PDO::FETCH_ASSOC);

// Ödeme istatistikleri
$paymentQuery = "SELECT COUNT(*) as total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as success,
                        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue
                 FROM payments";
$paymentStmt = $db->prepare($paymentQuery);
$paymentStmt->execute();
$stats['payments'] = $paymentStmt->fetch(PDO::FETCH_ASSOC);

// Mesaj istatistikleri
$messageQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as `read`,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
    FROM contact_messages";
$messageStmt = $db->prepare($messageQuery);
$messageStmt->execute();
$stats['messages'] = $messageStmt->fetch(PDO::FETCH_ASSOC);

// Son eklenen makaleler
$recentArticlesQuery = "SELECT a.id, a.title, a.status, a.created_at, u.username, c.name as category_name
                        FROM articles a
                        LEFT JOIN users u ON a.user_id = u.id 
                        LEFT JOIN categories c ON a.category_id = c.id
                        ORDER BY a.created_at DESC LIMIT 5";
$recentArticlesStmt = $db->prepare($recentArticlesQuery);
$recentArticlesStmt->execute();
$recentArticles = $recentArticlesStmt->fetchAll(PDO::FETCH_ASSOC);

// Bekleyen yorumlar
$pendingCommentsQuery = "SELECT c.id, c.content, c.created_at, a.title as article_title, u.username
                         FROM comments c
                         LEFT JOIN articles a ON c.article_id = a.id
                         LEFT JOIN users u ON c.user_id = u.id
                         WHERE c.is_approved = 0
                         ORDER BY c.created_at DESC LIMIT 5";
$pendingCommentsStmt = $db->prepare($pendingCommentsQuery);
$pendingCommentsStmt->execute();
$pendingComments = $pendingCommentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Son kayıt olan kullanıcılar
$newUsersQuery = "SELECT id, username, email, created_at, is_approved 
                  FROM users 
                  WHERE is_admin != 1 
                  ORDER BY created_at DESC LIMIT 5";
$newUsersStmt = $db->prepare($newUsersQuery);
$newUsersStmt->execute();
$newUsers = $newUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Son aktiviteler için karma sorgu
$recentActivitiesQuery = "
    (SELECT 'article' as type, a.title as title, a.created_at as activity_time, u.username as user_name, 'Yeni makale eklendi' as activity_text, a.status as status
     FROM articles a 
     LEFT JOIN users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'user' as type, u.username as title, u.created_at as activity_time, u.username as user_name, 'Yeni kullanıcı kaydı' as activity_text, CASE WHEN u.is_approved = 1 THEN 'approved' ELSE 'pending' END as status
     FROM users u 
     WHERE u.is_admin != 1 
     ORDER BY u.created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'comment' as type, SUBSTRING(c.content, 1, 50) as title, c.created_at as activity_time, u.username as user_name, 'Yeni yorum eklendi' as activity_text, CASE WHEN c.is_approved = 1 THEN 'approved' ELSE 'pending' END as status
     FROM comments c 
     LEFT JOIN users u ON c.user_id = u.id 
     ORDER BY c.created_at DESC LIMIT 2)
    ORDER BY activity_time DESC LIMIT 5";
$recentActivitiesStmt = $db->prepare($recentActivitiesQuery);
$recentActivitiesStmt->execute();
$recentActivities = $recentActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $database_error = $e->getMessage();
    $recentArticles = [];
    $pendingComments = [];
    $newUsers = [];
    $recentActivities = [];
}

// Aktif sayfa
$current_page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="tr" class="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - <?php echo getSiteSetting('site_title'); ?></title>
    
    <!-- Favicon -->
    <?php 
    $favicon = getSiteSetting('site_favicon');
    if ($favicon && file_exists('../' . $favicon)): 
    ?>
    <link rel="icon" type="image/x-icon" href="../<?= ltrim($favicon, '/') ?>">
    <link rel="shortcut icon" href="../<?= ltrim($favicon, '/') ?>">
    <link rel="apple-touch-icon" href="../<?= ltrim($favicon, '/') ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="../favicon.png">
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.0.1/dist/chart.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
        .menu-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .menu-items.open {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }
        .menu-header {
            cursor: pointer;
        }
        .menu-header i.fa-chevron-down {
            transition: transform 0.3s ease;
        }
        .menu-header.open i.fa-chevron-down {
            transform: rotate(180deg);
        }
        @media (max-width: 768px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            .sidebar-overlay {
                display: none;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Mobil Menü Overlay -->
    <div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar-mobile md:translate-x-0 fixed left-0 top-0 w-64 h-full bg-white dark:bg-gray-800 shadow-lg z-40 sidebar-transition overflow-y-auto">
        <!-- Logo Area -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-sm"></i>
                </div>
                <span class="text-lg font-bold text-gray-900 dark:text-white">Admin Panel</span>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Menu Items -->
        <nav class="p-4 space-y-2">
            <!-- Dashboard -->
            <a href="?page=dashboard" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'dashboard' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                <i class="fas fa-tachometer-alt w-5"></i>
                <span>Dashboard</span>
            </a>

            <!-- İçerik Yönetimi -->
            <div class="space-y-1">
                <div class="menu-header flex items-center justify-between text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2" onclick="toggleMenu('content-menu')">
                    <span>İçerik Yönetimi</span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
                <div id="content-menu" class="menu-items">
                    <a href="?page=articles" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'articles' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-file-alt w-5"></i>
                        <span>Makaleler</span>
                        <?php if ($stats['articles']['pending'] > 0): ?>
                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['articles']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?page=categories" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'categories' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-tags w-5"></i>
                        <span>Kategoriler</span>
                    </a>
                    <a href="?page=comments" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'comments' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-comments w-5"></i>
                        <span>Yorumlar</span>
                        <?php if ($stats['comments']['pending'] > 0): ?>
                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['comments']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?page=headline-display" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'headline-display' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-newspaper w-5"></i>
                        <span>Manşet Gösterimi</span>
                    </a>
                    <a href="?page=article-display" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'article-display' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-th-list w-5"></i>
                        <span>Makale Gösterimi</span>
                    </a>
                    <a href="?page=makale-botu" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'makale-botu' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-robot w-5"></i>
                        <span>Makale Botu</span>
                    </a>
                </div>
            </div>

            <!-- Kullanıcı Yönetimi -->
            <div class="space-y-1">
                <div class="menu-header flex items-center justify-between text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2" onclick="toggleMenu('user-menu')">
                    <span>Kullanıcı Yönetimi</span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
                <div id="user-menu" class="menu-items">
                    <a href="?page=users" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'users' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-users w-5"></i>
                        <span>Kullanıcılar</span>
                        <?php if ($stats['users']['pending'] > 0): ?>
                        <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['users']['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?page=ban-users" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'ban-users' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-gavel w-5"></i>
                        <span>Üye Banla</span>
                    </a>
                    <a href="?page=banned-users" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'banned-users' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-user-slash w-5"></i>
                        <span>Banlı Üyeler</span>
                        <?php 
                            $bannedCount = 0;
                            try {
                                $banQuery = "SELECT COUNT(*) FROM banned_users WHERE is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())";
                                $banStmt = $db->prepare($banQuery);
                                $banStmt->execute();
                                $bannedCount = $banStmt->fetchColumn();
                            } catch (Exception $e) {}
                            
                            if ($bannedCount > 0):
                        ?>
                        <!-- Uyarı simgesi kaldırıldı -->
                        <?php endif; ?>
                    </a>
                    <a href="?page=messages" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'messages' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-envelope w-5"></i>
                        <span>Mesajlar</span>
                        <?php if ($stats['messages']['unread'] > 0): ?>
                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['messages']['unread']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Ödeme Sistemi -->
            <div class="space-y-1">
                <div class="menu-header flex items-center justify-between text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2" onclick="toggleMenu('payment-menu')">
                    <span>Ödeme Sistemi</span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
                <div id="payment-menu" class="menu-items">
                    <a href="?page=payments" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'payments' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-credit-card w-5"></i>
                        <span>Ödemeler</span>
                    </a>
                    <a href="?page=subscriptions" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'subscriptions' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-crown w-5"></i>
                        <span>Abonelik Planları</span>
                    </a>
                    <a href="?page=user_subscriptions" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'user_subscriptions' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-users-cog w-5"></i>
                        <span>Kullanıcı Abonelikleri</span>
                        <?php 
                            try {
                                $expiring_count = 0;
                                if ($db) {
                                    $expiringQuery = "SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
                                    $expiringStmt = $db->prepare($expiringQuery);
                                    $expiringStmt->execute();
                                    $expiring_count = $expiringStmt->fetchColumn();
                                }
                                
                                if ($expiring_count > 0): 
                            ?>
                            <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $expiring_count; ?></span>
                            <?php endif; 
                            } catch (Exception $e) {} 
                            ?>
                    </a>
                    <a href="?page=promo-codes" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'promo-codes' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-ticket-alt w-5"></i>
                        <span>Promosyon Kodları</span>
                    </a>
                </div>
            </div>

            <!-- Site Yönetimi -->
            <div class="space-y-1">
                <div class="menu-header flex items-center justify-between text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2" onclick="toggleMenu('site-menu')">
                    <span>Site Yönetimi</span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </div>
                <div id="site-menu" class="menu-items">
                    <a href="?page=settings" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'settings' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-cog w-5"></i>
                        <span>Site Ayarları</span>
                    </a>
                    <a href="?page=cloudflare" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'cloudflare' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-shield-alt w-5"></i>
                        <span>Cloudflare</span>
                    </a>
                    <a href="?page=ads" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'ads' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-ad w-5"></i>
                        <span>Reklamlar</span>
                    </a>
                    <a href="?page=analytics" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'analytics' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Analitik</span>
                    </a>
                    <a href="?page=backups" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'backups' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-database w-5"></i>
                        <span>Yedekleme</span>
                    </a>
                    <a href="?page=cookies" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'cookies' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-cookie w-5"></i>
                        <span>Çerezler</span>
                    </a>
                    <a href="?page=sitemap" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'sitemap' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <i class="fas fa-sitemap w-5"></i>
                        <span>Sitemap</span>
                    </a>
                    <a href="?page=maintenance-mode" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 <?php echo $current_page === 'maintenance-mode' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : ''; ?>">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-tools w-5"></i>
                            <span>Bakım Modu</span>
                        </div>
                        <?php 
                        $maintenance_active = getSiteSetting('maintenance_mode', 0);
                        if ($maintenance_active): 
                        ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Aktif</span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Pasif</span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Alt Menu -->
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-1">
                <a href="/" target="_blank" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-external-link-alt w-5"></i>
                    <span>Siteyi Görüntüle</span>
                </a>
                <a href="/auth/logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-red-100 dark:hover:bg-red-900 text-red-600 dark:text-red-400">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Çıkış Yap</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="md:ml-64 min-h-screen p-4 content-transition">
        <!-- Mobil Menü Butonu -->
        <div class="md:hidden mb-4">
            <button onclick="toggleSidebar()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Top Bar -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?php 
                        $page_titles = [
                            'dashboard' => 'Dashboard',
                            'activities' => 'Tüm Aktiviteler',
                            'articles' => 'Makaleler',
                            'categories' => 'Kategoriler', 
                            'comments' => 'Yorumlar',
                            'headline-display' => 'Manşet Gösterimi',
                            'article-display' => 'Makale Gösterimi',
                            'users' => 'Kullanıcılar',
                            'messages' => 'Mesajlar',
                            'payments' => 'Ödemeler',
                            'subscriptions' => 'Abonelik Planları',
                            'user_subscriptions' => 'Kullanıcı Abonelikleri',
                            'promo-codes' => 'Promosyon Kodları',
                            'settings' => 'Site Ayarları',
                            'ads' => 'Reklamlar',
                            'analytics' => 'Analitik',
                            'backups' => 'Yedekleme',
                            'cloudflare' => 'Cloudflare CAPTCHA',
                            'sitemap' => 'Sitemap Yönetimi',
                            'maintenance-mode' => 'Bakım Modu',
                            'cookies' => 'Çerez Ayarları',
                            'makale-botu' => 'Makale Botu'
                        ];
                        echo $page_titles[$current_page] ?? 'Dashboard';
                        ?>
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Bildirimler -->
                    <div class="relative">
                        <button onclick="toggleNotifications()" class="p-2 text-gray-400 hover:text-gray-600 relative">
                            <i class="fas fa-bell text-xl"></i>
                            <?php 
                            $totalNotifications = $stats['articles']['pending'] + $stats['comments']['pending'] + $stats['users']['pending'] + $stats['messages']['unread'];
                            if ($totalNotifications > 0): 
                            ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                <?php echo $totalNotifications; ?>
                            </span>
                            <?php endif; ?>
                        </button>

                        <!-- Bildirim Dropdown -->
                        <div id="notificationsDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Bildirimler</h3>
                                <div class="space-y-3">
                                    <?php if ($stats['comments']['pending'] > 0): ?>
                                    <a href="?page=comments&filter=pending" class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fas fa-comments text-yellow-500 mr-3"></i>
                                            <span class="text-gray-700 dark:text-gray-300">Bekleyen Yorumlar</span>
                                        </div>
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['comments']['pending']; ?></span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($stats['articles']['pending'] > 0): ?>
                                    <a href="?page=articles&filter=pending" class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                                            <span class="text-gray-700 dark:text-gray-300">Bekleyen Makaleler</span>
                                        </div>
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['articles']['pending']; ?></span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($stats['users']['pending'] > 0): ?>
                                    <a href="?page=users&filter=pending" class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fas fa-users text-green-500 mr-3"></i>
                                            <span class="text-gray-700 dark:text-gray-300">Bekleyen Kullanıcılar</span>
                                        </div>
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['users']['pending']; ?></span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($stats['messages']['unread'] > 0): ?>
                                    <a href="?page=messages&filter=unread" class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fas fa-envelope text-purple-500 mr-3"></i>
                                            <span class="text-gray-700 dark:text-gray-300">Okunmamış Mesajlar</span>
                                        </div>
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['messages']['unread']; ?></span>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($totalNotifications === 0): ?>
                                    <div class="text-center text-gray-500 dark:text-gray-400 py-3">
                                        <i class="fas fa-check-circle text-green-500 text-xl mb-2"></i>
                                        <p>Bekleyen bildirim yok</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Karanlık/Aydınlık Mod Butonu -->
                    <div class="relative">
                        <button onclick="toggleTheme()" class="p-2 text-gray-400 hover:text-gray-600">
                            <i id="theme-toggle-dark-icon" class="fas fa-moon text-xl hidden"></i>
                            <i id="theme-toggle-light-icon" class="fas fa-sun text-xl hidden"></i>
                        </button>
                    </div>
                    
                    <!-- Kullanıcı Profili -->
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium"><?php echo strtoupper(substr(getCurrentUser()['username'], 0, 1)); ?></span>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo getCurrentUser()['username']; ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            <?php
            // Sayfa içeriklerini yükle
            switch($current_page) {
                case 'dashboard':
                default:
                    include 'pages/dashboard.php';
                    break;
                case 'activities':
                    include 'pages/activities.php';
                    break;
                case 'articles':
                    include 'pages/articles.php';
                    break;
                case 'categories':
                    include 'pages/categories.php';
                    break;
                case 'comments':
                    include 'pages/comments.php';
                    break;
                case 'headline-display':
                    include 'pages/headline-display.php';
                    break;
                case 'article-display':
                    include 'pages/article-display.php';
                    break;
                case 'users':
                    include 'pages/users.php';
                    break;
                case 'ban-users':
                    include 'pages/ban-users.php';
                    break;
                case 'banned-users':
                    include 'pages/banned-users/index.php';
                    break;
                case 'messages':
                    include 'pages/messages.php';
                    break;
                case 'message-reply':
                    include 'pages/message-reply.php';
                    break;
                case 'payments':
                    include 'pages/payments.php';
                    break;
                case 'subscriptions':
                    include 'pages/subscriptions.php';
                    break;
                case 'user_subscriptions':
                    include 'pages/user_subscriptions.php';
                    break;
                case 'promo-codes':
                    include 'pages/promo-codes.php';
                    break;
                case 'settings':
                    include 'pages/settings.php';
                    break;
                case 'ads':
                    include 'pages/ads.php';
                    break;
                case 'analytics':
                    include 'pages/analytics.php';
                    break;
                case 'backups':
                    include 'pages/backups.php';
                    break;
                case 'cookies':
                    include 'pages/cookies.php';
                    break;
                case 'sitemap':
                    include 'pages/sitemap.php';
                    break;
                case 'maintenance-mode':
                    include 'pages/maintenance-mode.php';
                    break;
                case 'cloudflare':
                    include 'pages/cloudflare.php';
                    break;
                case 'makale-botu':
                    include 'pages/makale-botu.php';
                    break;
            }
            ?>
        </main>
    </div>

    <script>
    // Menü durumlarını localStorage'da saklamak için
    const menuStates = JSON.parse(localStorage.getItem('menuStates')) || {};

    // Sayfa yüklendiğinde menü durumlarını geri yükle
    document.addEventListener('DOMContentLoaded', () => {
        Object.keys(menuStates).forEach(menuId => {
            const menu = document.getElementById(menuId);
            const header = menu.previousElementSibling;
            if (menuStates[menuId]) {
                menu.classList.add('open');
                header.classList.add('open');
            }
        });

        // Aktif sayfanın menüsünü otomatik aç
        const currentPage = '<?php echo $current_page; ?>';
        const activeLink = document.querySelector(`a[href="?page=${currentPage}"]`);
        if (activeLink) {
            const parentMenu = activeLink.closest('.menu-items');
            if (parentMenu) {
                toggleMenu(parentMenu.id, true);
            }
        }

        // Ekran boyutu değiştiğinde sidebar durumunu kontrol et
        window.addEventListener('resize', checkScreenSize);
        checkScreenSize();
    });

    function toggleMenu(menuId, forceOpen = false) {
        const menu = document.getElementById(menuId);
        const header = menu.previousElementSibling;
        
        if (forceOpen && !menu.classList.contains('open')) {
            menu.classList.add('open');
            header.classList.add('open');
            menuStates[menuId] = true;
        } else if (!forceOpen) {
            menu.classList.toggle('open');
            header.classList.toggle('open');
            menuStates[menuId] = menu.classList.contains('open');
        }
        
        localStorage.setItem('menuStates', JSON.stringify(menuStates));
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.toggle('sidebar-mobile');
        overlay.classList.toggle('active');
        
        // Sidebar açıkken scrollu engelle
        if (overlay.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    function checkScreenSize() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('sidebar-mobile');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        } else {
            sidebar.classList.add('sidebar-mobile');
        }
    }

    // Tema değiştirme fonksiyonu
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

    // Sayfa yüklendiğinde ikonları güncelle
    document.addEventListener('DOMContentLoaded', () => {
        updateThemeIcons('<?php echo getCurrentTheme(); ?>');
    });

    // Bildirim dropdown'ını aç/kapat
    function toggleNotifications() {
        const dropdown = document.getElementById('notificationsDropdown');
        dropdown.classList.toggle('hidden');

        // Dropdown dışına tıklandığında kapat
        document.addEventListener('click', function closeDropdown(e) {
            const dropdown = document.getElementById('notificationsDropdown');
            const button = e.target.closest('button');
            
            if (!dropdown.contains(e.target) && !button?.contains(e.target)) {
                dropdown.classList.add('hidden');
                document.removeEventListener('click', closeDropdown);
            }
        });
    }
    </script>
</body>
</html> 