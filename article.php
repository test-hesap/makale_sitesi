<?php
// Hata ayıklama için hata raporlamayı aktifleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

// URL'den makale slug'ını al
$slug = $_GET['slug'] ?? '';

// Slug değerini kontrol et ve logla
error_log("Requested article slug: " . $slug);

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

// Makaleyi getir - full_name sütunu hatası düzeltildi
$query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username
          FROM articles a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE a.slug = ? AND a.status = 'published'";

$stmt = $db->prepare($query);
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

// Etiketleri getir
if ($article) {
    $tagQuery = "SELECT t.name, t.slug 
                FROM tags t 
                JOIN article_tags at ON t.id = at.tag_id 
                WHERE at.article_id = ?";
    $tagStmt = $db->prepare($tagQuery);
    $tagStmt->execute([$article['id']]);
    $tags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Makale bulunamadıysa debug bilgisi ekle
if (!$article) {
    error_log("Article not found with slug: " . $slug);
    http_response_code(404);
    include '404.php';
    exit;
}

// Premium kontrolü
if ($article['is_premium'] && !isPremium()) {
    $premium_required = true;
} else {
    $premium_required = false;
}

// Görüntülenme sayısını artır
if (!isset($_SESSION['viewed_articles'])) {
    $_SESSION['viewed_articles'] = [];
}

if (!in_array($article['id'], $_SESSION['viewed_articles'])) {
    // views sütunu yerine views_count sütununu kullan
    $updateQuery = "UPDATE articles SET views_count = views_count + 1 WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$article['id']]);
    $_SESSION['viewed_articles'][] = $article['id'];
}

// İlgili makaleler
$relatedQuery = "SELECT id, title, slug, featured_image as image, excerpt, created_at 
                 FROM articles 
                 WHERE category_id = ? AND id != ? AND status = 'published' 
                 ORDER BY created_at DESC 
                 LIMIT 4";
$relatedStmt = $db->prepare($relatedQuery);
$relatedStmt->execute([$article['category_id'], $article['id']]);
$relatedArticles = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

// Yorumlar - full_name sütunu hatası düzeltildi
$commentsQuery = "SELECT c.*, u.username
                  FROM comments c 
                  LEFT JOIN users u ON c.user_id = u.id 
                  WHERE c.article_id = ? AND c.is_approved = 1 
                  ORDER BY c.created_at ASC";
$commentsStmt = $db->prepare($commentsQuery);
$commentsStmt->execute([$article['id']]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

// SEO Meta bilgileri
$pageTitle = $article['title'] . ' - ' . getSiteSetting('site_title');
$metaDescription = $article['excerpt'] ?: substr(strip_tags($article['content']), 0, 160);
$canonical_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Ana İçerik -->
        <div class="lg:col-span-2">
            <!-- Breadcrumb -->
            <nav class="text-sm breadcrumbs mb-6" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="/" class="text-blue-600 hover:text-blue-800">Ana Sayfa</a></li>
                    <li class="text-gray-400">/</li>
                    <li><a href="/kategori/<?php echo $article['category_slug']; ?>" class="text-blue-600 hover:text-blue-800"><?php echo $article['category_name']; ?></a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-600 truncate"><?php echo $article['title']; ?></li>
                </ol>
            </nav>

            <!-- Makale -->
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <!-- Makale Resmi -->
                <?php if (isset($article['featured_image']) && $article['featured_image']): ?>
                <div class="aspect-video overflow-hidden">
                    <img src="<?php echo $article['featured_image']; ?>" 
                         alt="<?php echo $article['title']; ?>"
                         class="w-full h-full object-cover">
                </div>
                <?php endif; ?>

                <div class="p-6">
                    <!-- Kategori ve Premium Badge -->
                    <div class="flex items-center gap-2 mb-4">
                        <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-medium px-2.5 py-0.5 rounded">
                            <?php echo $article['category_name']; ?>
                        </span>
                        <?php if ($article['is_premium']): ?>
                        <span class="premium-badge"><i class="fas fa-crown"></i> Premium</span>
                        <?php endif; ?>
                    </div>

                    <!-- Başlık -->
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        <?php echo $article['title']; ?>
                    </h1>

                    <!-- Meta Bilgiler -->
                    <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mb-6">
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
                                     alt="<?php echo $article['username']; ?>"
                                     class="w-8 h-8 rounded-full object-cover">
                                <?php } else { ?>
                                <!-- Varsayılan profil resmi (dosya okunamadı) -->
                                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 dark:text-gray-400"></i>
                                </div>
                                <?php } ?>
                            <?php else: ?>
                            <!-- Profil resmi yok -->
                            <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-600 dark:text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <span><?php echo $article['username']; ?></span>
                        </div>
                        <span>•</span>
                        <span><?php echo date('d.m.Y H:i', strtotime($article['created_at'])); ?></span>
                        <span>•</span>
                        <span><?php echo $article['views_count']; ?> görüntülenme</span>
                        <?php if (isset($article['read_time']) && $article['read_time']): ?>
                        <span>•</span>
                        <span><?php echo $article['read_time']; ?> dk okuma</span>
                        <?php endif; ?>
                    </div>

                    <!-- Sosyal Paylaşım -->
                    <div class="flex items-center space-x-2 mb-6 no-print">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Paylaş:</span>
                        <button onclick="socialShare('facebook', '<?php echo $canonical_url; ?>', '<?php echo addslashes($article['title']); ?>')" 
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button onclick="socialShare('twitter', '<?php echo $canonical_url; ?>', '<?php echo addslashes($article['title']); ?>')" 
                                class="text-blue-400 hover:text-blue-600">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button onclick="socialShare('linkedin', '<?php echo $canonical_url; ?>', '<?php echo addslashes($article['title']); ?>')" 
                                class="text-blue-700 hover:text-blue-900">
                            <i class="fab fa-linkedin-in"></i>
                        </button>
                        <button onclick="socialShare('whatsapp', '<?php echo $canonical_url; ?>', '<?php echo addslashes($article['title']); ?>')" 
                                class="text-green-600 hover:text-green-800">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button onclick="copyToClipboard('<?php echo $canonical_url; ?>')" 
                                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>

                    <!-- İçerik -->
                    <div class="article-content prose max-w-none dark:prose-dark">
                        <style>
                            .article-content {
                                font-size: 1rem;
                                line-height: 1.6;
                            }
                            .article-content p {
                                margin-bottom: 1rem;
                                line-height: 1.6;
                            }
                            .article-content h1 {
                                font-size: 2rem; /* 32px - daha büyük değer */
                                font-weight: 700;
                                margin-top: 1.75rem;
                                margin-bottom: 1rem;
                                color: #111827; /* dark gray-900 */
                            }
                            .dark .article-content h1 {
                                color: #f9fafb; /* dark mode: gray-50 */
                            }
                            .article-content h2 {
                                font-size: 1.5rem; /* 24px */
                                font-weight: 700;
                                margin-top: 1.5rem;
                                margin-bottom: 0.875rem;
                                color: #1f2937; /* gray-800 */
                            }
                            .dark .article-content h2 {
                                color: #f3f4f6; /* dark mode: gray-100 */
                            }
                            .article-content h3, .article-content h4 {
                                margin-top: 1.5rem;
                                margin-bottom: 0.75rem;
                                font-weight: 600;
                                color: #374151; /* gray-700 */
                            }
                            .dark .article-content h3, .dark .article-content h4 {
                                color: #e5e7eb; /* dark mode: gray-200 */
                            }
                            .article-content h3 {
                                font-size: 1.25rem; /* 20px */
                            }
                            .article-content h4 {
                                font-size: 1.125rem; /* 18px */
                            }
                            .article-content ul, .article-content ol {
                                margin-bottom: 1.5rem;
                            }
                            .article-content li {
                                margin-bottom: 0.5rem;
                            }
                            .article-content img {
                                margin: 1.5rem 0;
                            }
                        </style>
                        <?php if ($premium_required): ?>
                        <!-- Premium İçerik Uyarısı -->
                        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900 dark:to-yellow-800 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6 text-center">
                            <i class="fas fa-crown text-yellow-500 text-3xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Premium İçerik</h3>
                            <p class="text-yellow-700 dark:text-yellow-300 mb-4">Bu makaleyi okumak için premium üyeliğe sahip olmanız gerekiyor.</p>
                            <div class="space-x-4">
                                <?php if (isLoggedIn()): ?>
                                    <?php if (!isAdmin()): ?>
                                    <a href="/premium" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                        Premium Üye Ol
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="/login" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                        Giriş Yap
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                            <?php if (!isPremium() && !isAdmin()): ?>
                            <!-- İçerik Üstü Reklamı -->
                            <?php
                            $topAd = $db->query("SELECT * FROM ads WHERE position = 'content_top' AND is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
                            if ($topAd):
                            ?>
                            <div class="mb-8 text-center">
                                <div class="inline-block">
                                    <?= $topAd['code'] ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php
                            // İçerik Ortası Reklamı - Debug
                            $middleAdQuery = "SELECT * FROM ads WHERE position = 'content_middle' AND is_active = 1 ORDER BY display_order LIMIT 1";
                            $middleAd = $db->query($middleAdQuery)->fetch();
                            
                            // Debug bilgisi
                            if (isAdmin()) {
                                echo '<!-- Debug: Orta Reklam Sorgusu: ' . $middleAdQuery . ' -->';
                                echo '<!-- Debug: Reklam Bulundu mu? ' . ($middleAd ? 'Evet' : 'Hayır') . ' -->';
                                if ($middleAd) {
                                    echo '<!-- Debug: Reklam ID: ' . $middleAd['id'] . ' -->';
                                }
                            }
                            
                            if ($middleAd) {
                                // İçeriği paragraflarına ayır
                                $parts = explode('</p>', $article['content']);
                                $totalParts = count($parts);
                                $middleIndex = floor($totalParts / 2);
                                
                                // İçeriği HTML olarak çözümle
                                $dom = new DOMDocument();
                                // HTML5 etiketleri için hataları engelle
                                libxml_use_internal_errors(true);
                                $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $article['content'] . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                                libxml_clear_errors();
                                
                                // Tüm elemanları al
                                $elements = $dom->getElementsByTagName('body')->item(0)->childNodes->item(0)->childNodes;
                                
                                // Toplam eleman sayısı
                                $totalElements = $elements->length;
                                $middleIdx = floor($totalElements / 2);
                                
                                // İlk yarıyı göster
                                for ($i = 0; $i < $middleIdx; $i++) {
                                    echo $dom->saveHTML($elements->item($i));
                                }
                                
                                // Ortada reklamı göster
                                echo '<div class="my-8 text-center">
                                        <div class="inline-block">
                                            ' . $middleAd['code'] . '
                                        </div>
                                    </div>';
                                
                                // Kalan içeriği göster
                                for ($i = $middleIdx; $i < $totalElements; $i++) {
                                    echo $dom->saveHTML($elements->item($i));
                                }
                            } else {
                                // Reklam yoksa içeriği olduğu gibi göster, ancak paragraf formatını düzelt
                                $content = $article['content'];
                                // İçeriği olduğu gibi göster, HTML etiketleri düzgün işlenecektir
                                echo $content;
                            }
                            ?>

                            <!-- İçerik Altı Reklamı -->
                            <?php
                            $bottomAd = $db->query("SELECT * FROM ads WHERE position = 'content_bottom' AND is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
                            if ($bottomAd):
                            ?>
                            <div class="mt-8 text-center">
                                <div class="inline-block">
                                    <?= $bottomAd['code'] ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                                <?= $article['content'] ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Etiketler -->
                    <?php if (isset($tags) && !empty($tags)): ?>
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-tags mr-2 text-blue-500"></i>
                            <span>Etiketler</span>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                            <a href="/search.php?tag=<?php echo urlencode($tag['slug']); ?>" 
                               class="bg-blue-50 dark:bg-gray-700 hover:bg-blue-100 
                                     dark:hover:bg-gray-600 text-blue-700 dark:text-blue-300 
                                     text-xs font-medium px-3 py-1.5 rounded-md transition-all duration-200
                                     border-l-2 border-blue-500 dark:border-blue-400
                                     hover:translate-x-0.5 hover:-translate-y-0.5">
                                <span class="text-blue-500 mr-1">#</span><?php echo htmlspecialchars($tag['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </article>

            <!-- Yorumlar Bölümü -->
            <?php if (!$premium_required): ?>
            <div class="mt-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    Yorumlar (<?php echo count($comments); ?>)
                </h3>

                <!-- Yorum Formu -->
                <?php if (isLoggedIn()): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Yorum Yap</h4>
                    
                    <?php if (isset($_SESSION['comment_message'])): ?>
                        <div class="mb-4 p-4 rounded-lg <?php echo $_SESSION['comment_status'] ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200'; ?>">
                            <?php echo $_SESSION['comment_message']; ?>
                        </div>
                        <?php unset($_SESSION['comment_message'], $_SESSION['comment_status']); ?>
                    <?php endif; ?>
                    
                    <form action="../api/add-comment.php" method="POST" class="space-y-4">
                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                        
                        <textarea name="content" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white" 
                                  placeholder="Yorumunuzu yazın..." required></textarea>
                        
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Yorumu Gönder
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6 text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Yorum yapmak için giriş yapmanız gerekiyor.</p>
                    <a href="/login" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Giriş Yap
                    </a>
                </div>
                <?php endif; ?>

                <!-- Yorumlar Listesi -->
                <?php if (!empty($comments)): ?>
                <div class="space-y-6 mt-6">
                    <?php foreach ($comments as $comment): ?>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex space-x-4">
                            <div class="flex-shrink-0">
                                <?php if (isset($comment['profile_image']) && $comment['profile_image']): ?>
                                    <?php
                                    // Base64 kodlamasıyla resim gösterme (dosya erişim sorunu çözümü)
                                    $profileImageUrl = '';
                                    $imagePath = 'assets/images/profiles/' . $comment['profile_image'];
                                    
                                    if (file_exists($imagePath) && is_readable($imagePath)) {
                                        $imageData = file_get_contents($imagePath);
                                        $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                                        $profileImageUrl = $base64;
                                    ?>
                                    <img src="<?= $profileImageUrl ?>" 
                                         alt="<?php echo $comment['username']; ?>"
                                         class="w-10 h-10 rounded-full object-cover">
                                    <?php } else { ?>
                                    <!-- Varsayılan profil resmi (dosya okunamadı) -->
                                    <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600 dark:text-gray-400"></i>
                                    </div>
                                    <?php } ?>
                                <?php else: ?>
                                <!-- Profil resmi yok -->
                                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 dark:text-gray-400"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h5 class="font-medium text-gray-900 dark:text-white">
                                        <?php echo $comment['username']; ?>
                                    </h5>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="text-gray-700 dark:text-gray-300">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="far fa-comments text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- İlgili Makaleler -->
            <?php if (!empty($relatedArticles)): ?>
            <div class="mt-12">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">İlgili Makaleler</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($relatedArticles as $relatedArticle): ?>
                    <article class="article-card">
                        <?php if ($relatedArticle['image']): ?>
                        <div class="aspect-video overflow-hidden">
                            <img src="<?php echo $relatedArticle['image']; ?>" 
                                 alt="<?php echo $relatedArticle['title']; ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                <a href="/makale/<?php echo $relatedArticle['slug']; ?>" class="hover:text-blue-600">
                                    <?php echo $relatedArticle['title']; ?>
                                </a>
                            </h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">
                                <?php echo htmlspecialchars($relatedArticle['excerpt']); ?>
                            </p>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo date('d.m.Y', strtotime($relatedArticle['created_at'])); ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
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

<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "<?php echo addslashes($article['title']); ?>",
    "description": "<?php echo addslashes($page_description); ?>",
    "image": "<?php echo getCurrentDomain() . $page_image; ?>",
    "author": {
        "@type": "Person",
        "name": "<?php echo $article['first_name'] . ' ' . $article['last_name']; ?>"
    },
    "publisher": {
        "@type": "Organization",
        "name": "<?php echo getSiteSetting('site_name'); ?>",
        "logo": {
            "@type": "ImageObject",
            "url": "<?php echo getCurrentDomain(); ?>/assets/images/logo.png"
        }
    },
    "datePublished": "<?php echo date('c', strtotime($article['created_at'])); ?>",
    "dateModified": "<?php echo date('c', strtotime($article['updated_at'] ?: $article['created_at'])); ?>",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "<?php echo $canonical_url; ?>"
    }
}
</script>

<!-- Yorum Gönderme Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('commentForm');
    const commentMessage = document.getElementById('commentMessage');
    
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Form verilerini al
            const formData = new FormData(commentForm);
            
            // Debug için form verilerini konsola yazdır
            console.log('Form verileri:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // AJAX isteği gönder
            fetch('api/add-comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('API Yanıtı (ham):', response);
                return response.json();
            })
            .then(data => {
                console.log('API Yanıtı (JSON):', data);
                // Mesaj kutusunu göster
                commentMessage.classList.remove('hidden');
                
                if (data.success) {
                    // Başarılı mesajı
                    commentMessage.className = 'mb-4 p-4 rounded-lg bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200';
                    commentMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    
                    // Formu temizle
                    commentForm.reset();
                } else {
                    // Hata mesajı
                    commentMessage.className = 'mb-4 p-4 rounded-lg bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200';
                    commentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                }
                
                // 5 saniye sonra mesajı gizle
                setTimeout(() => {
                    commentMessage.classList.add('hidden');
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                commentMessage.classList.remove('hidden');
                commentMessage.className = 'mb-4 p-4 rounded-lg bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200';
                commentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
            });
        });
    }
});
</script>