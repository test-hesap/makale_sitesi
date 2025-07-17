<?php
// Output buffering başlat ve temizle
ob_start();
ob_clean();

require_once 'config/database.php';
require_once 'includes/functions.php';

// Tüm çıktıyı temizle
ob_clean();

header('Content-Type: application/xml; charset=utf-8');

// Sitemap etkin mi kontrol et
$sitemap_enabled = getSiteSetting('sitemap_enabled', 1);
if (!$sitemap_enabled) {
    http_response_code(404);
    exit('Sitemap disabled');
}

$database = new Database();
$db = $database->pdo;

$baseUrl = getCurrentDomain();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Ana sayfa
echo '  <url>' . "\n";
echo '    <loc>' . $baseUrl . '/</loc>' . "\n";
echo '    <lastmod>' . date('c') . '</lastmod>' . "\n";
echo '    <changefreq>' . getSiteSetting('sitemap_changefreq_homepage', 'daily') . '</changefreq>' . "\n";
echo '    <priority>' . getSiteSetting('sitemap_priority_homepage', '1.0') . '</priority>' . "\n";
echo '  </url>' . "\n";

// Sabit sayfalar
$staticPages = [
    '/premium' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), getSiteSetting('sitemap_priority_static', '0.5')],
    '/search' => ['weekly', '0.6'],
    '/contact' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), getSiteSetting('sitemap_priority_static', '0.5')],
    '/about' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), getSiteSetting('sitemap_priority_static', '0.5')],
    '/privacy' => [getSiteSetting('sitemap_changefreq_static', 'yearly'), '0.3']
];

foreach ($staticPages as $page => $info) {
    echo '  <url>' . "\n";
    echo '    <loc>' . $baseUrl . $page . '</loc>' . "\n";
    echo '    <lastmod>' . date('c') . '</lastmod>' . "\n";
    echo '    <changefreq>' . $info[0] . '</changefreq>' . "\n";
    echo '    <priority>' . $info[1] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// Kategoriler
$categoriesQuery = "SELECT slug, updated_at FROM categories WHERE is_active = 1";
$categoriesStmt = $db->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $category) {
    echo '  <url>' . "\n";
    echo '    <loc>' . $baseUrl . '/kategori/' . $category['slug'] . '</loc>' . "\n";
    echo '    <lastmod>' . date('c', strtotime($category['updated_at'] ?? 'now')) . '</lastmod>' . "\n";
    echo '    <changefreq>' . getSiteSetting('sitemap_changefreq_categories', 'weekly') . '</changefreq>' . "\n";
    echo '    <priority>' . getSiteSetting('sitemap_priority_categories', '0.7') . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// Makaleler
$articlesQuery = "SELECT slug, updated_at, created_at FROM articles 
                  WHERE status = 'published' 
                  ORDER BY updated_at DESC";
$articlesStmt = $db->prepare($articlesQuery);
$articlesStmt->execute();
$articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($articles as $article) {
    $lastmod = $article['updated_at'] ?: $article['created_at'];
    echo '  <url>' . "\n";
    echo '    <loc>' . $baseUrl . '/makale/' . $article['slug'] . '</loc>' . "\n";
    echo '    <lastmod>' . date('c', strtotime($lastmod)) . '</lastmod>' . "\n";
    echo '    <changefreq>' . getSiteSetting('sitemap_changefreq_articles', 'monthly') . '</changefreq>' . "\n";
    echo '    <priority>' . getSiteSetting('sitemap_priority_articles', '0.8') . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// Kullanıcı profilleri (opsiyonel)
if (getSiteSetting('sitemap_include_users', 0)) {
    $usersQuery = "SELECT id, username, updated_at FROM users 
                   WHERE is_approved = 1 AND is_admin = 0 
                   ORDER BY updated_at DESC";
    $usersStmt = $db->prepare($usersQuery);
    $usersStmt->execute();
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo '  <url>' . "\n";
        echo '    <loc>' . $baseUrl . '/uye/' . $user['username'] . '</loc>' . "\n";
        echo '    <lastmod>' . date('c', strtotime($user['updated_at'] ?? 'now')) . '</lastmod>' . "\n";
        echo '    <changefreq>monthly</changefreq>' . "\n";
        echo '    <priority>0.4</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}

echo '</urlset>';

// Output buffer'ı temizle ve sonlandır
ob_end_flush();
?>