<?php
require_once 'includes/functions.php';

// Bakım modu kontrolü
if (isMaintenanceMode()) {
    showMaintenancePage();
}
define('MAINTENANCE_CHECKED', true);

require_once 'includes/header.php';

// Ana sayfa meta bilgileri
$pageTitle = $settings['site_title'];
$metaDescription = $settings['site_description'];

// Veritabanından gerçek makaleleri çek
$featuredArticles = [];
$recentArticles = [];
$popularArticles = [];

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Öne çıkan makaleler (is_featured = 1 olanlar veya son yayınlananlar)
    $featuredQuery = "SELECT a.*, c.name as category_name, u.username 
                      FROM articles a 
                      LEFT JOIN categories c ON a.category_id = c.id 
                      LEFT JOIN users u ON a.user_id = u.id 
                      WHERE a.status = 'published' 
                      ORDER BY a.is_featured DESC, a.published_at DESC, a.created_at DESC 
                      LIMIT 6";
    $featuredStmt = $db->prepare($featuredQuery);
    $featuredStmt->execute();
    $featuredArticles = $featuredStmt->fetchAll();
    
    // Son eklenen makaleler
    $recentQuery = "SELECT a.*, c.name as category_name, u.username 
                    FROM articles a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    LEFT JOIN users u ON a.user_id = u.id 
                    WHERE a.status = 'published' 
                    ORDER BY a.created_at DESC 
                    LIMIT 8";
    $recentStmt = $db->prepare($recentQuery);
    $recentStmt->execute();
    $recentArticles = $recentStmt->fetchAll();
    
    // Popüler makaleler (en çok görüntülenen)
    $popularQuery = "SELECT a.*, c.name as category_name, u.username 
                     FROM articles a 
                     LEFT JOIN categories c ON a.category_id = c.id 
                     LEFT JOIN users u ON a.user_id = u.id 
                     WHERE a.status = 'published' 
                     ORDER BY a.views_count DESC, a.created_at DESC 
                     LIMIT 8";
    $popularStmt = $db->prepare($popularQuery);
    $popularStmt->execute();
    $popularArticles = $popularStmt->fetchAll();
    
} catch (Exception $e) {
    // Hata durumunda boş array'ler kullan
    error_log("Index.php makale çekme hatası: " . $e->getMessage());
}

// Manşet için ilk makaleyi al
$headlineArticle = !empty($featuredArticles) ? $featuredArticles[0] : null;

// Manşet gösterim türünü kontrol et
$headlineDisplayType = getSiteSetting('headline_display_type', 'static');
$headlineAutoChange = getSiteSetting('headline_auto_change', 0);
$headlineChangeInterval = getSiteSetting('headline_change_interval', 5000);

// Döngüsel gösterim için birden fazla makale al
$headlineArticles = [];
if ($headlineDisplayType === 'carousel') {
    $headlineArticles = array_slice($featuredArticles, 0, 5); // İlk 5 öne çıkan makaleyi al
    if (count($headlineArticles) < 2) {
        // Eğer yeterli öne çıkan makale yoksa son makalelerden de al
        $additionalArticles = array_slice($recentArticles, 0, 5 - count($headlineArticles));
        $headlineArticles = array_merge($headlineArticles, $additionalArticles);
    }
} elseif ($headlineDisplayType === 'custom') {
    // Seçilmiş makaleleri al
    try {
        $customQuery = "SELECT a.*, c.name as category_name, u.username 
                        FROM headline_articles ha 
                        LEFT JOIN articles a ON ha.article_id = a.id 
                        LEFT JOIN categories c ON a.category_id = c.id 
                        LEFT JOIN users u ON a.user_id = u.id 
                        WHERE ha.is_active = 1 AND a.status = 'published'
                        ORDER BY ha.display_order";
        $customStmt = $db->prepare($customQuery);
        $customStmt->execute();
        $headlineArticles = $customStmt->fetchAll();
        
        // Eğer seçilmiş makale yoksa varsayılan davranış
        if (empty($headlineArticles)) {
            $headlineArticles = [$headlineArticle];
        }
    } catch (Exception $e) {
        $headlineArticles = [$headlineArticle];
    }
} else {
    $headlineArticles = [$headlineArticle];
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Ana İçerik -->
        <div class="lg:col-span-3">
            <!-- Manşet Slider -->
            <section class="mb-12">
                <div class="slideshow-container bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden relative">
                    <?php if (in_array($headlineDisplayType, ['carousel', 'custom']) && count($headlineArticles) > 1): ?>
                        <!-- Carousel Mode - Multiple Slides -->
                        <?php foreach ($headlineArticles as $index => $article): ?>
                        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                            <div class="relative h-96 md:h-[500px]">
                                <?php if ($article && $article['featured_image']): ?>
                                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                                         alt="<?= $article['title'] ?>" 
                                         class="w-full h-full object-contain object-center">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                                        <i class="fas fa-newspaper text-6xl text-white opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                
                                <!-- Content -->
                                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8 text-white">
                                    <div class="max-w-4xl">
                                        <!-- Category -->
                                        <span class="inline-block px-3 py-1 bg-primary-600 text-white text-xs rounded-full mb-3">
                                            <?= $article ? htmlspecialchars($article['category_name'] ?? t('general')) : t('welcome') ?>
                                        </span>
                                        
                                        <!-- Title -->
                                        <h2 class="text-2xl md:text-4xl font-bold mb-3 leading-tight">
                                            <?php if ($article): ?>
                                                <a href="/makale/<?= htmlspecialchars($article['slug']) ?>" class="hover:text-primary-200 transition-colors">
                                                    <?= $article['title'] ?>
                                                </a>
                                            <?php else: ?>
                                                <?= t('welcome') ?> <?= t('articles') ?>
                                            <?php endif; ?>
                                        </h2>
                                        
                                        <!-- Excerpt -->
                                        <p class="text-gray-200 text-sm md:text-base mb-4">
                                            <?php if ($article && $article['excerpt']): ?>
                                                <?= createExcerpt($article['excerpt'], 150) ?>...
                                            <?php else: ?>
                                                Modern, SEO uyumlu ve kullanıcı dostu makale platformumuzda bilgi paylaşımının keyfini çıkarın.
                                            <?php endif; ?>
                                        </p>
                                        
                                        <!-- Meta -->
                                        <div class="flex items-center space-x-4 text-sm text-gray-300">
                                            <span><i class="fas fa-user mr-1"></i><?= $article ? htmlspecialchars($article['username']) : 'Admin' ?></span>
                                            <span><i class="fas fa-calendar mr-1"></i><?= $article ? date('d.m.Y', strtotime($article['created_at'])) : date('d.m.Y') ?></span>
                                            <span><i class="fas fa-eye mr-1"></i><?= $article ? number_format($article['views_count']) : '0' ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Navigation Arrows -->
                        <button class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors z-10" onclick="prevSlide()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors z-10" onclick="nextSlide()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <!-- Dots Indicator -->
                        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-10">
                            <?php foreach ($headlineArticles as $index => $article): ?>
                            <button class="dot w-3 h-3 rounded-full bg-white/50 hover:bg-white/80 transition-colors <?php echo $index === 0 ? 'active bg-white' : ''; ?>" 
                                    onclick="currentSlide(<?php echo $index + 1; ?>)" data-slide="<?php echo $index; ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Static Mode - Single Slide -->
                        <div class="slide active">
                            <div class="relative h-96 md:h-[500px]">
                                <?php if ($headlineArticle && $headlineArticle['featured_image']): ?>
                                    <img src="<?= htmlspecialchars($headlineArticle['featured_image']) ?>" 
                                         alt="<?= $headlineArticle['title'] ?>" 
                                         class="w-full h-full object-contain object-center">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                                        <i class="fas fa-newspaper text-6xl text-white opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                
                                <!-- Content -->
                                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8 text-white">
                                    <div class="max-w-4xl">
                                        <!-- Category -->
                                        <span class="inline-block px-3 py-1 bg-primary-600 text-white text-xs rounded-full mb-3">
                                            <?= $headlineArticle ? htmlspecialchars($headlineArticle['category_name'] ?? t('general')) : t('welcome') ?>
                                        </span>
                                        
                                        <!-- Title -->
                                        <h2 class="text-2xl md:text-4xl font-bold mb-3 leading-tight">
                                            <?php if ($headlineArticle): ?>
                                                <a href="/makale/<?= htmlspecialchars($headlineArticle['slug']) ?>" class="hover:text-primary-200 transition-colors">
                                                    <?= $headlineArticle['title'] ?>
                                                </a>
                                            <?php else: ?>
                                                <?= t('welcome') ?> <?= t('articles') ?>
                                            <?php endif; ?>
                                        </h2>
                                        
                                        <!-- Excerpt -->
                                        <p class="text-gray-200 text-sm md:text-base mb-4">
                                            <?php if ($headlineArticle && $headlineArticle['excerpt']): ?>
                                                <?= createExcerpt($headlineArticle['excerpt'], 150) ?>...
                                            <?php else: ?>
                                                Modern, SEO uyumlu ve kullanıcı dostu makale platformumuzda bilgi paylaşımının keyfini çıkarın.
                                            <?php endif; ?>
                                        </p>
                                        
                                        <!-- Meta -->
                                        <div class="flex items-center space-x-4 text-sm text-gray-300">
                                            <span><i class="fas fa-user mr-1"></i><?= $headlineArticle ? htmlspecialchars($headlineArticle['username']) : 'Admin' ?></span>
                                            <span><i class="fas fa-calendar mr-1"></i><?= $headlineArticle ? date('d.m.Y', strtotime($headlineArticle['created_at'])) : date('d.m.Y') ?></span>
                                            <span><i class="fas fa-eye mr-1"></i><?= $headlineArticle ? number_format($headlineArticle['views_count']) : '0' ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Öne Çıkan Makaleler -->
            <section class="mb-12" id="featured-articles">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-star text-yellow-500 mr-2"></i><?= t('featured_articles') ?>
                </h2>
                <?php 
                $featuredPerPage = getSiteSetting('featured_articles_per_page', 6);
                $featuredPaginationType = getSiteSetting('featured_articles_pagination_type', 'pagination');
                $currentFeaturedPage = isset($_GET['featured_page']) ? intval($_GET['featured_page']) : 1;
                $featuredOffset = ($currentFeaturedPage - 1) * $featuredPerPage;
                
                // Toplam öne çıkan makale sayısını al
                $totalFeaturedQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published' AND is_featured = 1";
                $totalFeaturedStmt = $db->prepare($totalFeaturedQuery);
                $totalFeaturedStmt->execute();
                $totalFeatured = $totalFeaturedStmt->fetch()['total'];
                
                // Eğer öne çıkan makale yoksa normal makalelerden al
                if ($totalFeatured == 0) {
                    $totalFeaturedQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
                    $totalFeaturedStmt = $db->prepare($totalFeaturedQuery);
                    $totalFeaturedStmt->execute();
                    $totalFeatured = $totalFeaturedStmt->fetch()['total'];
                    
                    $featuredDisplayQuery = "SELECT a.*, c.name as category_name, u.username 
                                           FROM articles a 
                                           LEFT JOIN categories c ON a.category_id = c.id 
                                           LEFT JOIN users u ON a.user_id = u.id 
                                           WHERE a.status = 'published' 
                                           ORDER BY a.published_at DESC, a.created_at DESC 
                                           LIMIT $featuredPerPage OFFSET $featuredOffset";
                } else {
                    $featuredDisplayQuery = "SELECT a.*, c.name as category_name, u.username 
                                           FROM articles a 
                                           LEFT JOIN categories c ON a.category_id = c.id 
                                           LEFT JOIN users u ON a.user_id = u.id 
                                           WHERE a.status = 'published' AND a.is_featured = 1
                                           ORDER BY a.published_at DESC, a.created_at DESC 
                                           LIMIT $featuredPerPage OFFSET $featuredOffset";
                }
                
                $featuredDisplayStmt = $db->prepare($featuredDisplayQuery);
                $featuredDisplayStmt->execute();
                $displayFeatured = $featuredDisplayStmt->fetchAll();
                
                $totalFeaturedPages = ceil($totalFeatured / $featuredPerPage);
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="featured-articles-container">
                    <?php foreach ($displayFeatured as $article): ?>
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow group">
                        <div class="relative">
                            <?php if ($article && $article['featured_image']): ?>
                                <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                                     alt="<?= $article['title'] ?>" 
                                     class="w-full h-48 object-contain object-center">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                                    <i class="fas fa-image text-3xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category -->
                            <span class="absolute top-2 left-2 px-2 py-1 bg-primary-600 text-white text-xs rounded-full">
                                <?= $article ? htmlspecialchars($article['category_name'] ?? 'Genel') : 'Örnek Kategori' ?>
                            </span>
                            
                            <!-- Premium Badge -->
                            <?php if ($article && isset($article['is_premium']) && $article['is_premium']): ?>
                            <span class="absolute top-2 right-2 premium-badge">
                                <i class="fas fa-crown"></i> Premium
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                <?php if ($article): ?>
                                    <a href="/makale/<?= htmlspecialchars($article['slug']) ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                        <?= $article['title'] ?>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                        Örnek Makale Başlığı
                                    </a>
                                <?php endif; ?>
                            </h3>
                            
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                                <?php if ($article && $article['excerpt']): ?>
                                    <?= createExcerpt($article['excerpt'], 100) ?>...
                                <?php else: ?>
                                    Bu bir örnek makale açıklamasıdır. Gerçek içerik eklendiğinde burada makale özeti görünecektir.
                                <?php endif; ?>
                            </p>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-3">
                                    <span><i class="fas fa-user mr-1"></i><?= $article ? htmlspecialchars($article['username']) : 'Admin' ?></span>
                                    <span><i class="fas fa-eye mr-1"></i><?= $article ? number_format($article['views_count']) : '0' ?></span>
                                </div>
                                <span><?= $article ? timeAgo($article['created_at']) : 'Az önce' ?></span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Öne Çıkan Makaleler Sayfalama -->
                <?php if ($totalFeaturedPages > 1): ?>
                <div class="mt-8 flex justify-center" id="featured-pagination">
                    <?php if ($featuredPaginationType === 'pagination'): ?>
                        <!-- Klasik Sayfalama -->
                        <nav class="flex items-center space-x-2">
                            <?php if ($currentFeaturedPage > 1): ?>
                            <a href="?featured_page=<?= $currentFeaturedPage - 1 ?>#featured-articles" 
                               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                Önceki
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalFeaturedPages; $i++): ?>
                                <?php if ($i == $currentFeaturedPage): ?>
                                    <span class="px-3 py-2 bg-blue-500 text-white rounded"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?featured_page=<?= $i ?>#featured-articles" 
                                       class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($currentFeaturedPage < $totalFeaturedPages): ?>
                            <a href="?featured_page=<?= $currentFeaturedPage + 1 ?>#featured-articles" 
                               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                Sonraki
                            </a>
                            <?php endif; ?>
                        </nav>
                    <?php else: ?>
                        <!-- Sonsuz Kaydırma -->
                        <?php if ($currentFeaturedPage < $totalFeaturedPages): ?>
                        <button onclick="loadMoreFeatured()" id="load-more-featured" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
                                data-page="<?= $currentFeaturedPage + 1 ?>">
                            <i class="fas fa-plus mr-2"></i>Daha Fazla Yükle
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Son Eklenen Makaleler -->
            <section class="mb-12" id="recent-articles">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-clock text-blue-500 mr-2"></i><?= t('recent_articles') ?>
                </h2>
                
                <?php 
                $recentPerPage = getSiteSetting('recent_articles_per_page', 6);
                $recentPaginationType = getSiteSetting('recent_articles_pagination_type', 'pagination');
                $currentRecentPage = isset($_GET['recent_page']) ? intval($_GET['recent_page']) : 1;
                $recentOffset = ($currentRecentPage - 1) * $recentPerPage;
                
                // Toplam son makale sayısını al
                $totalRecentQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
                $totalRecentStmt = $db->prepare($totalRecentQuery);
                $totalRecentStmt->execute();
                $totalRecent = $totalRecentStmt->fetch()['total'];
                
                $recentDisplayQuery = "SELECT a.*, c.name as category_name, u.username 
                                     FROM articles a 
                                     LEFT JOIN categories c ON a.category_id = c.id 
                                     LEFT JOIN users u ON a.user_id = u.id 
                                     WHERE a.status = 'published' 
                                     ORDER BY a.created_at DESC 
                                     LIMIT $recentPerPage OFFSET $recentOffset";
                
                $recentDisplayStmt = $db->prepare($recentDisplayQuery);
                $recentDisplayStmt->execute();
                $displayRecent = $recentDisplayStmt->fetchAll();
                
                $totalRecentPages = ceil($totalRecent / $recentPerPage);
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="recent-articles-container">
                    <?php foreach ($displayRecent as $article): ?>
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow group">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <?php if ($article && $article['featured_image']): ?>
                                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($article['title']) ?>" 
                                         class="w-24 h-24 md:w-32 md:h-24 object-contain object-center">
                                <?php else: ?>
                                    <div class="w-24 h-24 md:w-32 md:h-24 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-1 p-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="px-2 py-1 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-xs rounded-full">
                                        <?= $article ? htmlspecialchars($article['category_name'] ?? 'Genel') : 'Örnek Kategori' ?>
                                    </span>
                                    
                                    <?php if ($article && isset($article['is_premium']) && $article['is_premium']): ?>
                                    <span class="premium-badge">
                                        <i class="fas fa-crown"></i> Premium
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="text-sm md:text-base font-semibold text-gray-900 dark:text-white mb-2">
                                    <?php if ($article): ?>
                                        <a href="/makale/<?= htmlspecialchars($article['slug']) ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                            Son Makale Başlığı
                                        </a>
                                    <?php endif; ?>
                                </h3>
                                
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span><i class="fas fa-user mr-1"></i><?= $article ? htmlspecialchars($article['username']) : 'Admin' ?></span>
                                    <span><?= $article ? timeAgo($article['created_at']) : 'Az önce' ?></span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Son Eklenen Makaleler Sayfalama -->
                <?php if ($totalRecentPages > 1): ?>
                <div class="mt-8 flex justify-center" id="recent-pagination">
                    <?php if ($recentPaginationType === 'pagination'): ?>
                        <!-- Klasik Sayfalama -->
                        <nav class="flex items-center space-x-2">
                            <?php if ($currentRecentPage > 1): ?>
                            <a href="?recent_page=<?= $currentRecentPage - 1 ?>#recent-articles" 
                               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                Önceki
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalRecentPages; $i++): ?>
                                <?php if ($i == $currentRecentPage): ?>
                                    <span class="px-3 py-2 bg-blue-500 text-white rounded"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?recent_page=<?= $i ?>#recent-articles" 
                                       class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($currentRecentPage < $totalRecentPages): ?>
                            <a href="?recent_page=<?= $currentRecentPage + 1 ?>#recent-articles" 
                               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                Sonraki
                            </a>
                            <?php endif; ?>
                        </nav>
                    <?php else: ?>
                        <!-- Sonsuz Kaydırma -->
                        <?php if ($currentRecentPage < $totalRecentPages): ?>
                        <button onclick="loadMoreRecent()" id="load-more-recent" 
                                class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors"
                                data-page="<?= $currentRecentPage + 1 ?>">
                            <i class="fas fa-plus mr-2"></i>Daha Fazla Yükle
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Popüler Makaleler -->
            <section class="mb-12" id="popular-articles">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-fire text-red-500 mr-2"></i><?= t('popular_articles') ?>
                </h2>
                
                <?php 
                $popularPerPage = getSiteSetting('popular_articles_per_page', 6);
                $popularPaginationType = getSiteSetting('popular_articles_pagination_type', 'pagination');
                $currentPopularPage = isset($_GET['popular_page']) ? intval($_GET['popular_page']) : 1;
                $popularOffset = ($currentPopularPage - 1) * $popularPerPage;
                
                // Toplam popüler makale sayısını al
                $totalPopularQuery = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
                $totalPopularStmt = $db->prepare($totalPopularQuery);
                $totalPopularStmt->execute();
                $totalPopular = $totalPopularStmt->fetch()['total'];
                
                $popularDisplayQuery = "SELECT a.*, c.name as category_name, u.username 
                                      FROM articles a 
                                      LEFT JOIN categories c ON a.category_id = c.id 
                                      LEFT JOIN users u ON a.user_id = u.id 
                                      WHERE a.status = 'published' 
                                      ORDER BY a.views_count DESC, a.created_at DESC 
                                      LIMIT $popularPerPage OFFSET $popularOffset";
                
                $popularDisplayStmt = $db->prepare($popularDisplayQuery);
                $popularDisplayStmt->execute();
                $displayPopular = $popularDisplayStmt->fetchAll();
                
                $totalPopularPages = ceil($totalPopular / $popularPerPage);
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="popular-articles-container">
                    <?php foreach ($displayPopular as $article): ?>
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow group">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <?php if ($article && $article['featured_image']): ?>
                                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($article['title']) ?>" 
                                         class="w-24 h-24 md:w-32 md:h-24 object-contain object-center">
                                <?php else: ?>
                                    <div class="w-24 h-24 md:w-32 md:h-24 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-1 p-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="px-2 py-1 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-xs rounded-full">
                                        <?= $article ? htmlspecialchars($article['category_name'] ?? 'Genel') : 'Örnek Kategori' ?>
                                    </span>
                                    
                                    <?php if ($article && isset($article['is_premium']) && $article['is_premium']): ?>
                                    <span class="premium-badge">
                                        <i class="fas fa-crown"></i> Premium
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="text-sm md:text-base font-semibold text-gray-900 dark:text-white mb-2">
                                    <?php if ($article): ?>
                                        <a href="/makale/<?= htmlspecialchars($article['slug']) ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                            Popüler Makale Başlığı
                                        </a>
                                    <?php endif; ?>
                                </h3>
                                
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span><i class="fas fa-eye mr-1"></i><?= $article ? number_format($article['views_count']) : rand(100, 1000) ?> görüntüleme</span>
                                    <span><?= $article ? timeAgo($article['created_at']) : 'Az önce' ?></span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Popüler Makaleler Sayfalama -->
                <?php if ($totalPopularPages > 1): ?>
                <div class="mt-8 flex justify-center" id="popular-pagination">
                    <?php if ($popularPaginationType === 'pagination'): ?>
                        <!-- Klasik Sayfalama -->
                        <nav class="flex items-center space-x-2">
                            <?php if ($currentPopularPage > 1): ?>
                            <a href="?popular_page=<?= $currentPopularPage - 1 ?>#popular-articles" 
                               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                Önceki
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPopularPages; $i++): ?>
                                <?php if ($i == $currentPopularPage): ?>
                                    <span class="px-3 py-2 bg-blue-500 text-white rounded"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?popular_page=<?= $i ?>#popular-articles" 
                                       class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($currentPopularPage < $totalPopularPages): ?>
                            <a href="?popular_page=<?= $currentPopularPage + 1 ?>#popular-articles" 
                               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                Sonraki
                            </a>
                            <?php endif; ?>
                        </nav>
                    <?php else: ?>
                        <!-- Sonsuz Kaydırma -->
                        <?php if ($currentPopularPage < $totalPopularPages): ?>
                        <button onclick="loadMorePopular()" id="load-more-popular" 
                                class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors"
                                data-page="<?= $currentPopularPage + 1 ?>">
                            <i class="fas fa-plus mr-2"></i>Daha Fazla Yükle
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

<style>
.slide {
    display: none;
    animation: fadeIn 0.5s ease-in-out;
}

.slide.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.dot.active {
    background-color: white !important;
}
</style>

<script>
// Slider için global değişkenler
<?php if (in_array($headlineDisplayType, ['carousel', 'custom']) && count($headlineArticles) > 1): ?>
let currentSlideIndex = 0;
const totalSlides = <?php echo count($headlineArticles); ?>;
const autoChange = <?php echo $headlineAutoChange ? 'true' : 'false'; ?>;
const changeInterval = <?php echo $headlineChangeInterval; ?>;
let slideInterval;

// Slide gösterme fonksiyonu
function showSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    
    // Tüm slide'ları gizle
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Index'i sınırlar içinde tut
    if (index >= totalSlides) currentSlideIndex = 0;
    if (index < 0) currentSlideIndex = totalSlides - 1;
    
    // Aktif slide'ı göster
    slides[currentSlideIndex].classList.add('active');
    dots[currentSlideIndex].classList.add('active');
}

// Sonraki slide
function nextSlide() {
    currentSlideIndex++;
    showSlide(currentSlideIndex);
    resetAutoSlide();
}

// Önceki slide
function prevSlide() {
    currentSlideIndex--;
    showSlide(currentSlideIndex);
    resetAutoSlide();
}

// Belirli slide'a git
function currentSlide(index) {
    currentSlideIndex = index - 1;
    showSlide(currentSlideIndex);
    resetAutoSlide();
}

// Otomatik slide değişimi
function startAutoSlide() {
    if (autoChange) {
        slideInterval = setInterval(() => {
            currentSlideIndex++;
            showSlide(currentSlideIndex);
        }, changeInterval);
    }
}

// Otomatik slide'ı sıfırla
function resetAutoSlide() {
    if (slideInterval) {
        clearInterval(slideInterval);
    }
    startAutoSlide();
}

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function() {
    startAutoSlide();
    
    // Fareyle üzerine gelindiğinde durdur
    const sliderContainer = document.querySelector('.slideshow-container');
    sliderContainer.addEventListener('mouseenter', () => {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
    });
    
    // Fare ayrıldığında devam et
    sliderContainer.addEventListener('mouseleave', () => {
        startAutoSlide();
    });
});

// Klavye desteği
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        prevSlide();
    } else if (e.key === 'ArrowRight') {
        nextSlide();
    }
});
<?php endif; ?>

// Sonsuz kaydırma fonksiyonları
function loadMoreFeatured() {
    const button = document.getElementById('load-more-featured');
    const container = document.getElementById('featured-articles-container');
    const page = parseInt(button.getAttribute('data-page'));
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Yükleniyor...';
    button.disabled = true;
    
    fetch(`/api/load-more-articles.php?type=featured&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.insertAdjacentHTML('beforeend', data.html);
                
                if (data.hasMore) {
                    button.setAttribute('data-page', page + 1);
                    button.innerHTML = '<i class="fas fa-plus mr-2"></i>Daha Fazla Yükle';
                    button.disabled = false;
                } else {
                    button.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Hata oluştu';
        });
}

function loadMoreRecent() {
    const button = document.getElementById('load-more-recent');
    const container = document.getElementById('recent-articles-container');
    const page = parseInt(button.getAttribute('data-page'));
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Yükleniyor...';
    button.disabled = true;
    
    fetch(`/api/load-more-articles.php?type=recent&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.insertAdjacentHTML('beforeend', data.html);
                
                if (data.hasMore) {
                    button.setAttribute('data-page', page + 1);
                    button.innerHTML = '<i class="fas fa-plus mr-2"></i>Daha Fazla Yükle';
                    button.disabled = false;
                } else {
                    button.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Hata oluştu';
        });
}

function loadMorePopular() {
    const button = document.getElementById('load-more-popular');
    const container = document.getElementById('popular-articles-container');
    const page = parseInt(button.getAttribute('data-page'));
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Yükleniyor...';
    button.disabled = true;
    
    fetch(`/api/load-more-articles.php?type=popular&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.insertAdjacentHTML('beforeend', data.html);
                
                if (data.hasMore) {
                    button.setAttribute('data-page', page + 1);
                    button.innerHTML = '<i class="fas fa-plus mr-2"></i>Daha Fazla Yükle';
                    button.disabled = false;
                } else {
                    button.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Hata oluştu';
        });
}
</script>