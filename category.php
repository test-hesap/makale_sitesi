<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// URL'den kategori slug'ını al
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /');
    exit;
}

try {
    $database = new Database();
    $db = $database->pdo;
} catch (Exception $e) {
    // Database bağlantı hatası
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    die('<h1>Veritabanı Bağlantısı Hatası</h1><p>Lütfen daha sonra tekrar deneyin. MySQL servisi çalışmıyor olabilir.</p><p><strong>Çözüm:</strong> XAMPP Control Panel\'den MySQL\'i başlatın.</p>');
}

// Kategoriyi getir
$categoryQuery = "SELECT * FROM categories WHERE slug = ? AND is_active = 1";
$categoryStmt = $db->prepare($categoryQuery);
$categoryStmt->execute([$slug]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Global değişken olarak ayarla (header.php için)
$GLOBALS['current_category'] = $category;

// Sayfalama
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Toplam makale sayısı
$countQuery = "SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published'";
$countStmt = $db->prepare($countQuery);
$countStmt->execute([$category['id']]);
$total_articles = $countStmt->fetchColumn();

$total_pages = ceil($total_articles / $per_page);

// Makaleleri getir
$articlesQuery = "SELECT a.*, u.username
                  FROM articles a 
                  LEFT JOIN users u ON a.user_id = u.id 
                  WHERE a.category_id = ? AND a.status = 'published' 
                  ORDER BY a.created_at DESC 
                  LIMIT ? OFFSET ?";
$articlesStmt = $db->prepare($articlesQuery);
$articlesStmt->execute([$category['id'], $per_page, $offset]);
$articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

// SEO Meta bilgileri
$pageTitle = $category['name'] . ' Kategorisi - ' . getSiteSetting('site_title');
$metaDescription = $category['description'] ?: $category['name'] . ' kategorisindeki tüm makaleler';
$metaKeywords = $category['name'] . ', makaleler, ' . getSiteSetting('site_title');

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm breadcrumbs mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li><a href="/" class="text-blue-600 hover:text-blue-800">Ana Sayfa</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-600"><?php echo htmlspecialchars($category['name']); ?></li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Ana İçerik -->
        <div class="lg:col-span-2">
            <!-- Kategori Başlığı -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
                <div class="flex items-center space-x-4">
                    <?php if (isset($category['icon']) && $category['icon']): ?>
                    <i class="<?php echo $category['icon']; ?> text-3xl text-blue-600"></i>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h1>
                        <?php if ($category['description']): ?>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                        <?php endif; ?>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                            <span><?php echo $total_articles; ?> makale</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Makaleler -->
            <?php if (!empty($articles)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <?php foreach ($articles as $article): ?>
                <article class="article-card">
                    <?php if (isset($article['image']) && $article['image']): ?>
                    <div class="aspect-video overflow-hidden">
                        <img src="/assets/images/<?php echo $article['image']; ?>" 
                             alt="<?php echo htmlspecialchars($article['title']); ?>"
                             class="w-full h-full object-cover">
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <!-- Premium Badge -->
                        <?php if ($article['is_premium']): ?>
                        <div class="mb-3">
                            <span class="premium-badge"><i class="fas fa-crown mr-1"></i> Premium</span>
                        </div>
                        <?php endif; ?>

                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3 line-clamp-2">
                            <a href="/makale/<?php echo $article['slug']; ?>" class="hover:text-blue-600 transition-colors">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h2>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                            <?php echo isset($article['excerpt']) ? htmlspecialchars($article['excerpt']) : ''; ?>
                        </p>

                        <!-- Meta Bilgiler -->
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center space-x-2">
                                <?php if (isset($article['profile_image']) && $article['profile_image']): ?>
                                    <?php
                                    // Base64 kodlamasıyla resim gösterme (dosya erişim sorunu çözümü)
                                    $profileImageUrl = '';
                                    $imagePath = 'assets/images/profiles/' . $article['profile_image'];
                                    
                                    if (file_exists($imagePath) && is_readable($imagePath)) {
                                        $imageData = file_get_contents($imagePath);
                                        $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                                        $profileImageUrl = $base64;
                                    ?>
                                    <img src="<?= $profileImageUrl ?>" 
                                         alt="<?php echo htmlspecialchars($article['username']); ?>"
                                         class="w-6 h-6 rounded-full object-cover">
                                    <?php } else { ?>
                                    <!-- Varsayılan profil resmi (dosya okunamadı) -->
                                    <div class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600 dark:text-gray-400 text-xs"></i>
                                    </div>
                                    <?php } ?>
                                <?php else: ?>
                                <!-- Profil resmi yok -->
                                <div class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 dark:text-gray-400 text-xs"></i>
                                </div>
                                <?php endif; ?>
                                <span><?php echo $article['username']; ?></span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span><?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                                <span><?php echo isset($article['views']) ? $article['views'] : 0; ?> görüntülenme</span>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- Sayfalama -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" 
                       class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="px-3 py-2 text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> border border-gray-300 dark:border-gray-600 rounded-lg transition-colors">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" 
                       class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Makale Bulunamadı -->
            <div class="text-center py-12">
                <i class="fas fa-folder-open text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Bu kategoride henüz makale yok</h3>
                <p class="text-gray-600 dark:text-gray-400">Yakında bu kategoriye yeni makaleler eklenecek.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <?php include 'includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "<?php echo addslashes($category['name']); ?>",
    "description": "<?php echo addslashes($metaDescription); ?>",
    "url": "<?php echo isset($canonical_url) ? $canonical_url : getCurrentDomain() . '/kategori/' . $category['slug']; ?>",
    "mainEntity": {
        "@type": "ItemList",
        "numberOfItems": <?php echo $total_articles; ?>,
        "itemListElement": [
            <?php foreach ($articles as $index => $article): ?>
            {
                "@type": "Article",
                "position": <?php echo $index + 1; ?>,
                "name": "<?php echo addslashes($article['title']); ?>",
                "url": "<?php echo getCurrentDomain(); ?>/makale/<?php echo $article['slug']; ?>"
            }<?php echo $index < count($articles) - 1 ? ',' : ''; ?>
            <?php endforeach; ?>
        ]
    }
}
</script>

<?php include 'includes/footer.php'; ?> 