<?php
// Sayfalama parametreleri
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtreleme parametreleri
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Gelişmiş aktivite sorgusu
$where_conditions = [];
$params = [];

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(activity_time) = ?";
    $params[] = $date_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Toplam aktivite sayısı
    $countQuery = "
        SELECT COUNT(*) as total FROM (
            (SELECT 'article' as type, a.created_at as activity_time
             FROM articles a)
            UNION ALL
            (SELECT 'user' as type, u.created_at as activity_time
             FROM users u WHERE u.is_admin != 1)
            UNION ALL
            (SELECT 'comment' as type, c.created_at as activity_time
             FROM comments c)
        ) as all_activities $where_sql";
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalActivities = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalActivities / $limit);

    // Aktiviteleri çek
    $activitiesQuery = "
        SELECT * FROM (
            (SELECT 'article' as type, a.id, a.title as title, a.created_at as activity_time, 
                    u.username as user_name, 'Yeni makale eklendi' as activity_text, 
                    a.status as status, a.slug as slug
             FROM articles a 
             LEFT JOIN users u ON a.user_id = u.id)
            UNION ALL
            (SELECT 'user' as type, u.id, u.username as title, u.created_at as activity_time, 
                    u.username as user_name, 'Yeni kullanıcı kaydı' as activity_text, 
                    CASE WHEN u.is_approved = 1 THEN 'approved' ELSE 'pending' END as status,
                    NULL as slug
             FROM users u WHERE u.is_admin != 1)
            UNION ALL
            (SELECT 'comment' as type, c.id, SUBSTRING(c.content, 1, 100) as title, 
                    c.created_at as activity_time, u.username as user_name, 
                    'Yeni yorum eklendi' as activity_text, 
                    CASE WHEN c.is_approved = 1 THEN 'approved' ELSE 'pending' END as status,
                    a.slug as slug
             FROM comments c 
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN articles a ON c.article_id = a.id)
        ) as all_activities 
        $where_sql
        ORDER BY activity_time DESC 
        LIMIT $limit OFFSET $offset";
    
    $activitiesStmt = $db->prepare($activitiesQuery);
    $activitiesStmt->execute($params);
    $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $activities = [];
    $totalActivities = 0;
    $totalPages = 0;
    $error_message = "Aktiviteler yüklenirken hata oluştu: " . $e->getMessage();
}
?>

<!-- Aktiviteler Sayfası -->
<div class="space-y-6">
    <!-- Başlık ve Filtreler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tüm Aktiviteler</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Toplam <?php echo number_format($totalActivities); ?> aktivite
                </p>
            </div>
            
            <!-- Filtreler -->
            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="page" value="activities">
                    
                    <!-- Tür Filtresi -->
                    <select name="type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <option value="">Tüm Türler</option>
                        <option value="article" <?php echo $type_filter === 'article' ? 'selected' : ''; ?>>Makaleler</option>
                        <option value="user" <?php echo $type_filter === 'user' ? 'selected' : ''; ?>>Kullanıcılar</option>
                        <option value="comment" <?php echo $type_filter === 'comment' ? 'selected' : ''; ?>>Yorumlar</option>
                    </select>
                    
                    <!-- Tarih Filtresi -->
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i>Filtrele
                    </button>
                    
                    <?php if ($type_filter || $date_filter): ?>
                    <a href="?page=activities" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Temizle
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Aktiviteler Listesi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <?php if (!empty($activities)): ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($activities as $activity): ?>
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-start space-x-4">
                        <!-- Aktivite İkonu -->
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 <?php 
                                echo $activity['type'] == 'article' ? 'bg-blue-100 dark:bg-blue-900' : 
                                     ($activity['type'] == 'user' ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900'); 
                            ?> rounded-full flex items-center justify-center">
                                <i class="fas <?php 
                                    echo $activity['type'] == 'article' ? 'fa-file-alt text-blue-600 dark:text-blue-400' : 
                                         ($activity['type'] == 'user' ? 'fa-user text-green-600 dark:text-green-400' : 'fa-comment text-yellow-600 dark:text-yellow-400'); 
                                ?>"></i>
                            </div>
                        </div>
                        
                        <!-- Aktivite Detayları -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($activity['activity_text']); ?>
                                    <?php if (!empty($activity['user_name'])): ?>
                                        <span class="text-blue-600 dark:text-blue-400">- <?php echo htmlspecialchars($activity['user_name']); ?></span>
                                    <?php endif; ?>
                                </p>
                                
                                <!-- Durum Etiketi -->
                                <?php if (isset($activity['status'])): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    <?php echo $activity['status'] == 'published' || $activity['status'] == 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'; ?>">
                                    <?php echo $activity['status'] == 'published' ? 'Yayında' : 
                                               ($activity['status'] == 'approved' ? 'Onaylı' : 'Bekliyor'); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Aktivite İçeriği -->
                            <?php if (!empty($activity['title'])): ?>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 truncate">
                                <?php if ($activity['type'] == 'comment'): ?>
                                    "<?php echo htmlspecialchars($activity['title']); ?>..."
                                <?php else: ?>
                                    <?php echo htmlspecialchars($activity['title']); ?>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <!-- Zaman ve Aksiyonlar -->
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo timeAgo($activity['activity_time']); ?>
                                    <span class="ml-2 text-gray-400">
                                        (<?php echo date('d.m.Y H:i', strtotime($activity['activity_time'])); ?>)
                                    </span>
                                </p>
                                
                                <!-- Hızlı Aksiyonlar -->
                                <div class="flex items-center space-x-2">
                                    <?php if ($activity['type'] == 'article'): ?>
                                        <a href="?page=articles&action=edit&id=<?php echo $activity['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                            <i class="fas fa-edit mr-1"></i>Düzenle
                                        </a>
                                        <?php if ($activity['slug']): ?>
                                        <a href="/article.php?slug=<?php echo $activity['slug']; ?>" target="_blank"
                                           class="text-green-600 hover:text-green-800 text-xs">
                                            <i class="fas fa-external-link-alt mr-1"></i>Görüntüle
                                        </a>
                                        <?php endif; ?>
                                    <?php elseif ($activity['type'] == 'user'): ?>
                                        <a href="?page=users&action=edit&id=<?php echo $activity['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                            <i class="fas fa-user-edit mr-1"></i>Profil
                                        </a>
                                    <?php elseif ($activity['type'] == 'comment'): ?>
                                        <a href="?page=comments&filter=all#comment-<?php echo $activity['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                            <i class="fas fa-eye mr-1"></i>Görüntüle
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-clock text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aktivite Bulunamadı</h3>
                <p class="text-gray-500 dark:text-gray-400">
                    <?php if ($type_filter || $date_filter): ?>
                        Seçtiğiniz filtrelere uygun aktivite bulunmuyor.
                    <?php else: ?>
                        Henüz hiçbir aktivite gerçekleştirilmemiş.
                    <?php endif; ?>
                </p>
                <?php if ($type_filter || $date_filter): ?>
                <a href="?page=activities" class="inline-flex items-center px-4 py-2 mt-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Filtreleri Temizle
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sayfalama -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Sayfa <span class="font-medium"><?php echo $page; ?></span> / 
                <span class="font-medium"><?php echo $totalPages; ?></span>
                (Toplam <span class="font-medium"><?php echo number_format($totalActivities); ?></span> aktivite)
            </div>
            
            <div class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=activities&p=<?php echo $page - 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" 
                   class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($totalPages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=activities&p=<?php echo $i; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" 
                   class="px-3 py-2 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'; ?> rounded-lg transition-colors">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=activities&p=<?php echo $page + 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>" 
                   class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
        <div class="flex">
            <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
            <div class="text-red-700 dark:text-red-200">
                <h3 class="text-sm font-medium">Hata</h3>
                <p class="text-sm mt-1"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
