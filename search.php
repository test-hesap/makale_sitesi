<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->pdo;

// Arama terimi
$search_query = trim($_GET['q'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$tag_slug = trim($_GET['tag'] ?? '');

// Sayfalama
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$articles = [];
$total_articles = 0;
$total_pages = 0;
$searching_by_tag = false;
$tag_name = '';

// Etiket araması
if (!empty($tag_slug)) {
    $searching_by_tag = true;
    
    // Etiket bilgilerini al
    $tagQuery = "SELECT * FROM tags WHERE slug = ?";
    $tagStmt = $db->prepare($tagQuery);
    $tagStmt->execute([$tag_slug]);
    $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tag) {
        $tag_name = $tag['name'];
        
        // Kategori filtresi
        $category_filter = '';
        $params = [$tag['id']];
        
        if ($category_id > 0) {
            $category_filter = 'AND a.category_id = ?';
            $params[] = $category_id;
        }
        
        // Etiketle ilişkili makaleleri bul
        $countQuery = "SELECT COUNT(*) FROM articles a 
                       JOIN article_tags at ON a.id = at.article_id 
                       LEFT JOIN categories c ON a.category_id = c.id 
                       WHERE a.status = 'published' 
                       AND at.tag_id = ? 
                       $category_filter";
        
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $total_articles = $countStmt->fetchColumn();
        
        $total_pages = ceil($total_articles / $per_page);
        
        // Etiket arama sonuçları
        if ($total_articles > 0) {
            $searchParams = $params;
            $searchParams[] = $per_page;
            $searchParams[] = $offset;
            
            $searchQuery = "SELECT a.*, c.name as category_name, c.slug as category_slug, 
                                   u.username, u.username as author_name, u.profile_image 
                            FROM articles a 
                            JOIN article_tags at ON a.id = at.article_id 
                            LEFT JOIN categories c ON a.category_id = c.id 
                            LEFT JOIN users u ON a.user_id = u.id 
                            WHERE a.status = 'published' 
                            AND at.tag_id = ? 
                            $category_filter
                            ORDER BY a.created_at DESC 
                            LIMIT ? OFFSET ?";
            
            $searchStmt = $db->prepare($searchQuery);
            $searchStmt->execute($searchParams);
            $articles = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Arama terimini etiket adı olarak ayarla (vurgulama için)
            $search_query = $tag_name;
        }
    }
}
// Normal metin araması
else if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    
    // Kategori filtresi
    $category_filter = '';
    $params = [$search_term, $search_term];
    
    if ($category_id > 0) {
        $category_filter = 'AND a.category_id = ?';
        $params[] = $category_id;
    }
    
    // Toplam sonuç sayısı
    $countQuery = "SELECT COUNT(*) FROM articles a 
                   LEFT JOIN categories c ON a.category_id = c.id 
                   WHERE a.status = 'published' 
                   AND (a.title LIKE ? OR a.content LIKE ?) 
                   $category_filter";
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total_articles = $countStmt->fetchColumn();
    
    $total_pages = ceil($total_articles / $per_page);
    
    // Arama sonuçları
    if ($total_articles > 0) {
        $searchParams = $params;
        $searchParams[] = $per_page;
        $searchParams[] = $offset;
        
        $searchQuery = "SELECT a.*, c.name as category_name, c.slug as category_slug, 
                               u.username, u.username as author_name, u.profile_image 
                        FROM articles a 
                        LEFT JOIN categories c ON a.category_id = c.id 
                        LEFT JOIN users u ON a.user_id = u.id 
                        WHERE a.status = 'published' 
                        AND (a.title LIKE ? OR a.content LIKE ?) 
                        $category_filter
                        ORDER BY a.created_at DESC 
                        LIMIT ? OFFSET ?";
        
        $searchStmt = $db->prepare($searchQuery);
        $searchStmt->execute($searchParams);
        $articles = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Kategoriler
$categoriesQuery = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
$categoriesStmt = $db->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// SEO Meta bilgileri
$page_title = !empty($search_query) ? 
    '"' . htmlspecialchars($search_query) . '" Arama Sonuçları - ' . getSiteSetting('site_name') : 
    'Arama - ' . getSiteSetting('site_name');
$page_description = !empty($search_query) ? 
    '"' . htmlspecialchars($search_query) . '" için arama sonuçları' : 
    'Site içinde arama yapın';

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm breadcrumbs mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li><a href="/" class="text-blue-600 hover:text-blue-800">Ana Sayfa</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-600">Arama</li>
            <?php if (!empty($search_query)): ?>
            <li class="text-gray-400">/</li>
            <li class="text-gray-600 truncate"><?php echo htmlspecialchars($search_query); ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Ana İçerik -->
        <div class="lg:col-span-2">
            <!-- Arama Formu -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <input type="text" 
                                   name="q" 
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   placeholder="Arama terimi girin..." 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <select name="category" 
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">Tüm Kategoriler</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" 
                            class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Ara
                    </button>
                </form>
            </div>

            <?php if (!empty($search_query) || !empty($tag_slug)): ?>
            <!-- Arama Sonuçları Başlığı -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    <?php if ($searching_by_tag): ?>
                    <span class="flex items-center">
                        <i class="fas fa-tag text-blue-500 mr-2"></i>
                        #<?php echo htmlspecialchars($tag_name); ?> Etiketli İçerikler
                    </span>
                    <?php else: ?>
                    "<?php echo htmlspecialchars($search_query); ?>" Arama Sonuçları
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    <?php echo $total_articles; ?> sonuç bulundu
                </p>
            </div>

            <!-- Arama Sonuçları -->
            <?php if (!empty($articles)): ?>
            <div class="space-y-6 mb-8">
                <?php foreach ($articles as $article): ?>
                <article class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                    <div class="md:flex">
                        <?php if (!empty($article['featured_image'])): ?>
                        <div class="md:w-48 h-48 md:h-auto">
                            <img src="<?php echo $article['featured_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($article['title']); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-6 flex-1">
                            <!-- Kategori ve Premium Badge -->
                            <div class="flex items-center gap-2 mb-3">
                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-medium px-2.5 py-0.5 rounded">
                                    <?php echo $article['category_name']; ?>
                                </span>
                                <?php if ($article['is_premium']): ?>
                                <span class="premium-badge"><i class="fas fa-crown"></i> Premium</span>
                                <?php endif; ?>
                            </div>

                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                                <a href="/makale/<?php echo $article['slug']; ?>" class="hover:text-blue-600 transition-colors">
                                    <?php 
                                    // Başlıkta arama terimini vurgula
                                    $highlighted_title = str_ireplace($search_query, '<mark class="bg-yellow-200 dark:bg-yellow-800">' . $search_query . '</mark>', htmlspecialchars($article['title']));
                                    echo $highlighted_title;
                                    ?>
                                </a>
                            </h2>

                            <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                <?php 
                                // Excerpt'te arama terimini vurgula
                                $content = isset($article['content']) ? $article['content'] : '';
                                $excerpt = isset($article['excerpt']) && !empty($article['excerpt']) ? 
                                            $article['excerpt'] : substr(strip_tags($content), 0, 200) . '...';
                                $highlighted_excerpt = str_ireplace($search_query, '<mark class="bg-yellow-200 dark:bg-yellow-800">' . $search_query . '</mark>', htmlspecialchars($excerpt));
                                echo $highlighted_excerpt;
                                ?>
                            </p>

                            <!-- Meta Bilgiler -->
                            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <?php if (!empty($article['profile_image'])): ?>
                                    <img src="<?php echo $article['profile_image']; ?>" 
                                         alt="<?php echo $article['author_name']; ?>"
                                         class="w-6 h-6 rounded-full">
                                    <?php else: ?>
                                    <div class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600 dark:text-gray-400 text-xs"></i>
                                    </div>
                                    <?php endif; ?>
                                    <span><?php echo $article['author_name']; ?></span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span><?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                                    <span><?php echo isset($article['views_count']) ? $article['views_count'] : (isset($article['views']) ? $article['views'] : 0); ?> görüntülenme</span>
                                </div>
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
                    <a href="?<?php echo $searching_by_tag ? 'tag=' . urlencode($tag_slug) : 'q=' . urlencode($search_query); ?>&category=<?php echo $category_id; ?>&page=<?php echo $page - 1; ?>" 
                       class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?<?php echo $searching_by_tag ? 'tag=' . urlencode($tag_slug) : 'q=' . urlencode($search_query); ?>&category=<?php echo $category_id; ?>&page=<?php echo $i; ?>" 
                       class="px-3 py-2 text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> border border-gray-300 dark:border-gray-600 rounded-lg transition-colors">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo $searching_by_tag ? 'tag=' . urlencode($tag_slug) : 'q=' . urlencode($search_query); ?>&category=<?php echo $category_id; ?>&page=<?php echo $page + 1; ?>" 
                       class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Sonuç Bulunamadı -->
            <div class="text-center py-12">
                <i class="fas fa-search text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Sonuç bulunamadı</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    <?php if ($searching_by_tag): ?>
                    <span class="font-medium">#<?php echo htmlspecialchars($tag_name); ?></span> etiketli herhangi bir içerik bulunamadı.
                    <?php else: ?>
                    "<?php echo htmlspecialchars($search_query); ?>" için herhangi bir sonuç bulunamadı.
                    <?php endif; ?>
                </p>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 text-left max-w-md mx-auto">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Arama önerileri:</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Yazım hatası olup olmadığını kontrol edin</li>
                        <li>• Daha genel terimler kullanın</li>
                        <li>• Farklı kelimeler deneyin</li>
                        <li>• Kategori filtresi olmadan arayın</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- İlk Arama -->
            <div class="text-center py-12">
                <i class="fas fa-search text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Site içinde arama yapın</h3>
                <p class="text-gray-600 dark:text-gray-400">Makale, kategori veya etiket arayabilirsiniz.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <?php include 'includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 