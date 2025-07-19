<!-- Sidebar -->
<aside class="lg:col-span-1 space-y-6">
    <!-- Üst Reklam -->
    <?php if (!isPremium() && !isAdmin()): ?>
    <?php
    $sidebarTopAd = $db->query("SELECT * FROM ads WHERE position = 'sidebar' AND is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
    if ($sidebarTopAd):
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="text-center">
            <small class="text-gray-500 dark:text-gray-400 text-xs"><?= t('advertisement') ?></small>
            <div class="mt-2">
                <?= $sidebarTopAd['code'] ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Arama -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('search') ?></h3>
        <form action="/search.php" method="GET" class="space-y-3">
            <div class="relative">
                <input type="text" name="q" value="<?= sanitizeInput($_GET['q'] ?? '') ?>" 
                       placeholder="<?= t('article_search') ?>" 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            <button type="submit" class="w-full bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-search mr-2"></i> <?= t('search_button') ?>
            </button>
        </form>
    </div>

    <!-- Kategoriler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('categories') ?></h3>
        <ul class="space-y-2">
            <?php
            $categories = $db->query("
                SELECT c.*, COUNT(a.id) as article_count 
                FROM categories c 
                LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'published'
                WHERE c.is_active = 1 
                GROUP BY c.id 
                ORDER BY c.sort_order, c.name
            ")->fetchAll();
            
            foreach ($categories as $category):
            ?>
            <li>
                <a href="/kategori/<?= $category['slug'] ?>" 
                   class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300">
                    <span><?= $category['name'] ?></span>
                    <span class="text-sm bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded-full"><?= $category['article_count'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Popüler Makaleler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('popular_articles') ?></h3>
        <?php
        $popularArticles = $db->query("
            SELECT a.*, u.username, c.name as category_name, c.slug as category_slug
            FROM articles a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.views_count DESC
            LIMIT 5
        ")->fetchAll();
        ?>
        <div class="space-y-4">
            <?php foreach ($popularArticles as $article): ?>
            <div class="flex space-x-3">
                <?php if ($article['featured_image']): ?>
                <img src="<?= $article['featured_image'] ?>" alt="<?= $article['title'] ?>" 
                     class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                <?php else: ?>
                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex-shrink-0 flex items-center justify-center">
                    <i class="fas fa-image text-gray-400"></i>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 mb-1">
                        <a href="/makale/<?= $article['slug'] ?>" class="hover:text-primary-600 dark:hover:text-primary-400">
                            <?= $article['title'] ?>
                        </a>
                    </h4>
                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 space-x-2">
                        <span><i class="fas fa-eye mr-1"></i><?= number_format($article['views_count']) ?></span>
                        <span>•</span>
                        <span><?= timeAgo($article['published_at']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Çevrimiçi Üyeler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('online_members') ?></h3>
        <?php
        $onlineData = getOnlineUsers();
        ?>
        
        <div class="space-y-3">
            <div class="mt-2">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <?php 
                    $allNames = [];
                    
                    // Üye adlarını ekle
                    foreach ($onlineData['users'] as $user) {
                        $allNames[] = '<a href="/uye/' . $user['username'] . '" class="text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400">' . $user['username'] . '</a>';
                    }
                    
                    // Bot adlarını ekle
                    foreach ($onlineData['bot_names'] as $botName) {
                        $allNames[] = '<span class="font-medium">' . $botName . '</span>';
                    }
                    
                    if (empty($allNames)) {
                        echo t('no_online_members');
                    } else {
                        echo implode(', ', $allNames);
                    }
                    ?>
                </p>
            </div>
            
            <div class="mt-2">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <?= t('total') ?>: <?= $onlineData['total'] ?> (<?= t('member') ?>: <?= count($onlineData['users']) ?>, <?= t('guest') ?>: <?= $onlineData['guests'] ?>, <?= t('bot') ?>: <?= $onlineData['bots'] ?>)
                </p>
            </div>
        </div>
    </div>

    <!-- Çevrimiçi İstatistikler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('statistics') ?></h3>
        <?php
        $onlineCount = getOnlineCount();
        $totalUsers = $db->query("SELECT COUNT(*) as count FROM users WHERE is_approved = 1")->fetch()['count'];
        $totalArticles = $db->query("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")->fetch()['count'];
        $onlineUsers = $db->query("
            SELECT COUNT(*) as count 
            FROM online_users ou 
            LEFT JOIN users u ON ou.user_id = u.id 
            WHERE ou.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND u.id IS NOT NULL
        ")->fetch()['count'];
        $onlineGuests = $onlineCount - $onlineUsers;
        ?>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400"><?= t('total_members') ?></span>
                <span class="font-semibold text-primary-600"><?= number_format($totalUsers) ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400"><?= t('total_articles') ?></span>
                <span class="font-semibold text-purple-600"><?= number_format($totalArticles) ?></span>
            </div>
        </div>
    </div>

    <!-- Son Yorumlar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('latest_comments') ?></h3>
        <?php
        $recentComments = $db->query("
            SELECT c.*, u.username, a.title as article_title, a.slug as article_slug
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN articles a ON c.article_id = a.id
            WHERE c.is_approved = 1 AND a.status = 'published'
            ORDER BY c.created_at DESC
            LIMIT 5
        ")->fetchAll();
        ?>
        <div class="space-y-4">
            <?php foreach ($recentComments as $comment): ?>
            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white text-xs">
                        <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white"><?= $comment['username'] ?></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block"><?= timeAgo($comment['created_at']) ?></span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                    <?= createExcerpt($comment['content'], 80) ?>
                </p>
                <a href="/makale/<?= $comment['article_slug'] ?>#comment-<?= $comment['id'] ?>" 
                   class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                    "<?= createExcerpt($comment['article_title'], 30) ?>" <?= t('in_article') ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sosyal Medya -->
    <?php 
    $socialLinks = [
        'facebook' => getSetting('social_facebook'),
        'twitter' => getSetting('social_twitter'),
        'instagram' => getSetting('social_instagram'),
        'youtube' => getSetting('social_youtube')
    ];
    $hasSocialLinks = array_filter($socialLinks);
    ?>
    
    <?php if ($hasSocialLinks): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white"><?= t('follow_us') ?></h3>
        <div class="flex space-x-3">
            <?php if ($socialLinks['facebook']): ?>
            <a href="<?= $socialLinks['facebook'] ?>" target="_blank" 
               class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                <i class="fab fa-facebook-f"></i>
            </a>
            <?php endif; ?>
            
            <?php if ($socialLinks['twitter']): ?>
            <a href="<?= $socialLinks['twitter'] ?>" target="_blank" 
               class="w-10 h-10 bg-sky-500 text-white rounded-full flex items-center justify-center hover:bg-sky-600 transition-colors">
                <i class="fab fa-twitter"></i>
            </a>
            <?php endif; ?>
            
            <?php if ($socialLinks['instagram']): ?>
            <a href="<?= $socialLinks['instagram'] ?>" target="_blank" 
               class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full flex items-center justify-center hover:from-purple-600 hover:to-pink-600 transition-all">
                <i class="fab fa-instagram"></i>
            </a>
            <?php endif; ?>
            
            <?php if ($socialLinks['youtube']): ?>
            <a href="<?= $socialLinks['youtube'] ?>" target="_blank" 
               class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors">
                <i class="fab fa-youtube"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alt Reklam -->
    <?php if (!isPremium() && !isAdmin()): ?>
    <?php
    $sidebarBottomAd = $db->query("SELECT * FROM ads WHERE position = 'sidebar_bottom' AND is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
    if ($sidebarBottomAd):
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="text-center">
            <small class="text-gray-500 dark:text-gray-400 text-xs"><?= t('advertisement') ?></small>
            <div class="mt-2">
                <?= $sidebarBottomAd['code'] ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</aside> 