<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// SEO Meta bilgileri
$page_title = '404 - Sayfa Bulunamadı - ' . getSiteSetting('site_name');
$page_description = 'Aradığınız sayfa bulunamadı. Ana sayfaya dönün veya arama yapın.';

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-16">
    <div class="max-w-2xl mx-auto text-center">
        <!-- 404 İkonu -->
        <div class="mb-8">
            <div class="text-9xl font-bold text-blue-600 mb-4">404</div>
            <i class="fas fa-exclamation-triangle text-6xl text-yellow-500"></i>
        </div>

        <!-- Başlık ve Açıklama -->
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Sayfa Bulunamadı
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">
            Üzgünüz, aradığınız sayfa mevcut değil veya taşınmış olabilir.
        </p>

        <!-- Eylem Butonları -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <a href="/" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-home mr-2"></i>
                Ana Sayfaya Dön
            </a>
            <button onclick="history.back()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri Git
            </button>
        </div>

        <!-- Arama Formu -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Aradığınızı bulamadınız mı?
            </h3>
            <form action="/search.php" method="GET" class="flex flex-col sm:flex-row gap-4">
                <input type="text" 
                       name="q" 
                       placeholder="Arama terimi girin..." 
                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Ara
                </button>
            </form>
        </div>

        <!-- Popüler Sayfalar -->
        <?php
        $database = new Database();
        $db = $database->getConnection();
        
        // En popüler makaleleri getir
        $popularQuery = "SELECT id, title, slug, views FROM articles 
                         WHERE status = 'published' 
                         ORDER BY views DESC 
                         LIMIT 5";
        $popularStmt = $db->prepare($popularQuery);
        $popularStmt->execute();
        $popularArticles = $popularStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (!empty($popularArticles)): ?>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Popüler Makaleler
            </h3>
            <ul class="space-y-3 text-left">
                <?php foreach ($popularArticles as $article): ?>
                <li>
                    <a href="/makale/<?php echo $article['slug']; ?>" 
                       class="flex items-center justify-between text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                        <span class="truncate"><?php echo htmlspecialchars($article['title']); ?></span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2 flex-shrink-0">
                            <?php echo number_format($article['views']); ?> görüntülenme
                        </span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Yardımcı Bilgiler -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <i class="fas fa-list text-2xl text-blue-600 mb-3"></i>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Kategoriler</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Tüm makale kategorilerimizi inceleyin.
                </p>
                <a href="/" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Kategorileri Görüntüle →
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <i class="fas fa-envelope text-2xl text-green-600 mb-3"></i>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">İletişim</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Yardıma mı ihtiyacınız var? Bizimle iletişime geçin.
                </p>
                <a href="/contact" class="text-green-600 hover:text-green-800 text-sm font-medium">
                    İletişime Geç →
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <i class="fas fa-home text-2xl text-purple-600 mb-3"></i>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Ana Sayfa</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    En son makaleleri ve haberleri keşfedin.
                </p>
                <a href="/" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                    Ana Sayfaya Git →
                </a>
            </div>
        </div>

        <!-- Teknik Bilgi -->
        <div class="mt-8 text-sm text-gray-500 dark:text-gray-400">
            <p>Hata Kodu: 404 | <?php echo date('Y-m-d H:i:s'); ?></p>
            <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
            <p>Önceki Sayfa: <?php echo htmlspecialchars($_SERVER['HTTP_REFERER']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "404 - Sayfa Bulunamadı",
    "description": "<?php echo addslashes($page_description); ?>",
    "url": "<?php echo getCurrentUrl(); ?>",
    "mainEntity": {
        "@type": "Thing",
        "name": "404 Error"
    }
}
</script>

<?php include 'includes/footer.php'; ?> 