<?php
require_once __DIR__ . '/../../config/database.php';

// Abonelik işlemleri
$success = null;
$error = null;

// Süresi dolmuş abonelikleri kontrol et ve kullanıcı durumlarını güncelle
function updateExpiredSubscriptions() {
    try {
        $database = new Database();
        
        // Süresi dolmuş ve expired olarak işaretlenmemiş abonelikleri bul
        $expiredQuery = $database->query(
            "SELECT * FROM user_subscriptions 
             WHERE end_date < NOW() AND status IN ('active', 'cancelled')"
        );
        
        $expiredSubscriptions = $expiredQuery->fetchAll();
        
        foreach ($expiredSubscriptions as $sub) {
            // Aboneliği süresi dolmuş olarak işaretle
            $database->query(
                "UPDATE user_subscriptions 
                 SET status = 'expired', updated_at = NOW() 
                 WHERE id = ?",
                [$sub['id']]
            );
            
            // Kullanıcının hala aktif başka bir aboneliği var mı kontrol et
            $activeQuery = $database->query(
                "SELECT COUNT(*) FROM user_subscriptions 
                 WHERE user_id = ? AND status IN ('active', 'cancelled') AND end_date > NOW()",
                [$sub['user_id']]
            );
            
            $hasActiveSubscription = $activeQuery->fetchColumn() > 0;
            
            // Kullanıcının premium durumunu güncelle
            if (!$hasActiveSubscription) {
                $database->query(
                    "UPDATE users 
                     SET is_premium = 0, 
                         premium_expires_at = NULL 
                     WHERE id = ?",
                    [$sub['user_id']]
                );
            }
        }
        
        return count($expiredSubscriptions);
    } catch (Exception $e) {
        return 0;
    }
}

// Her sayfa yüklendiğinde süresi dolmuş abonelikleri kontrol et
$expiredCount = updateExpiredSubscriptions();

if (isset($_POST['action'])) {
    $database = new Database();
    
    switch ($_POST['action']) {
        case 'cancel_subscription':
            $subscriptionId = intval($_POST['subscription_id'] ?? 0);
            $immediateTermination = isset($_POST['immediate_termination']) && $_POST['immediate_termination'] == 1;
            
            if ($subscriptionId > 0) {
                try {
                    // Önce abonelik bilgilerini al
                    $subQuery = $database->query(
                        "SELECT * FROM user_subscriptions WHERE id = ?",
                        [$subscriptionId]
                    );
                    $subscription = $subQuery->fetch();
                    
                    if ($subscription) {
                        // Aboneliği iptal et
                        $updateFields = [
                            "status = 'cancelled'",
                            "cancelled_at = NOW()",
                            "updated_at = NOW()"
                        ];
                        
                        // Eğer hemen sonlandırma seçeneği seçildiyse, bitiş tarihini şimdi olarak ayarla
                        if ($immediateTermination) {
                            $updateFields[] = "end_date = NOW()";
                        }
                        
                        $stmt = $database->query(
                            "UPDATE user_subscriptions 
                             SET " . implode(", ", $updateFields) . "
                             WHERE id = ?",
                            [$subscriptionId]
                        );
                        
                        // Kullanıcının premium_expires_at alanını da güncelle
                        if ($immediateTermination) {
                            // Hemen sonlandırma durumunda, eğer başka aktif abonelik yoksa premium durumunu hemen kaldır
                            $userQuery = $database->query(
                                "UPDATE users 
                                 SET premium_expires_at = (
                                    SELECT CASE 
                                        WHEN COUNT(*) > 0 THEN MAX(end_date)
                                        ELSE NULL
                                    END
                                    FROM user_subscriptions 
                                    WHERE user_id = ? AND status = 'active' AND end_date > NOW()
                                 ),
                                 is_premium = IF((SELECT COUNT(*) FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW()) > 0, 1, 0)
                                 WHERE id = ?",
                                [$subscription['user_id'], $subscription['user_id'], $subscription['user_id']]
                            );
                            
                            $success = "Abonelik başarıyla iptal edildi ve hemen sonlandırıldı. Kullanıcının premium erişimi kaldırıldı.";
                        } else {
                            // Normal iptal durumunda, kullanıcı dönem sonuna kadar premium özelliklere erişebilir
                            $userQuery = $database->query(
                                "UPDATE users 
                                 SET premium_expires_at = (
                                    SELECT MAX(end_date) 
                                    FROM user_subscriptions 
                                    WHERE user_id = ? AND (status = 'active' OR status = 'cancelled') AND end_date > NOW()
                                 ),
                                 is_premium = IF((SELECT COUNT(*) FROM user_subscriptions WHERE user_id = ? AND (status = 'active' OR status = 'cancelled') AND end_date > NOW()) > 0, 1, 0)
                                 WHERE id = ?",
                                [$subscription['user_id'], $subscription['user_id'], $subscription['user_id']]
                            );
                            
                            $success = "Abonelik başarıyla iptal edildi. Kullanıcı dönem sonuna kadar premium özelliklerden yararlanmaya devam edebilir.";
                        }
                    } else {
                        $error = "Abonelik bulunamadı.";
                    }
                } catch (Exception $e) {
                    $error = "Abonelik iptal edilirken bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $error = "Geçersiz abonelik ID.";
            }
            break;
            
        case 'extend_subscription':
            $subscriptionId = intval($_POST['subscription_id'] ?? 0);
            $extendDays = intval($_POST['extend_days'] ?? 0);
            
            if ($subscriptionId > 0 && $extendDays > 0) {
                try {
                    // Önce abonelik bilgilerini al
                    $subQuery = $database->query(
                        "SELECT * FROM user_subscriptions WHERE id = ?",
                        [$subscriptionId]
                    );
                    $subscription = $subQuery->fetch();
                    
                    if ($subscription) {
                        // Aboneliği uzat
                        $stmt = $database->query(
                            "UPDATE user_subscriptions 
                             SET end_date = DATE_ADD(end_date, INTERVAL ? DAY),
                                 updated_at = NOW()
                             WHERE id = ?",
                            [$extendDays, $subscriptionId]
                        );
                        
                        // Kullanıcının premium_expires_at alanını ve is_premium alanlarını da güncelle
                        $userQuery = $database->query(
                            "UPDATE users 
                             SET premium_expires_at = (
                                SELECT MAX(end_date) 
                                FROM user_subscriptions 
                                WHERE user_id = ? AND (status = 'active' OR status = 'cancelled') AND end_date > NOW()
                             ),
                             is_premium = IF((SELECT COUNT(*) FROM user_subscriptions WHERE user_id = ? AND (status = 'active' OR status = 'cancelled') AND end_date > NOW()) > 0, 1, 0)
                             WHERE id = ?",
                            [$subscription['user_id'], $subscription['user_id'], $subscription['user_id']]
                        );
                        
                        $success = "Abonelik $extendDays gün uzatıldı.";
                    } else {
                        $error = "Abonelik bulunamadı.";
                    }
                } catch (Exception $e) {
                    $error = "Abonelik uzatılırken bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $error = "Geçersiz abonelik ID veya uzatma süresi.";
            }
            break;
            
        case 'change_plan':
            $subscriptionId = intval($_POST['subscription_id'] ?? 0);
            $newPlanId = intval($_POST['new_plan_id'] ?? 0);
            
            if ($subscriptionId > 0 && $newPlanId > 0) {
                try {
                    // Önce abonelik bilgilerini ve yeni plan bilgilerini al
                    $subQuery = $database->query(
                        "SELECT us.*, sp.duration_months as current_duration 
                         FROM user_subscriptions us
                         JOIN subscription_plans sp ON us.plan_id = sp.id
                         WHERE us.id = ?",
                        [$subscriptionId]
                    );
                    $subscription = $subQuery->fetch();
                    
                    $planQuery = $database->query(
                        "SELECT * FROM subscription_plans WHERE id = ?",
                        [$newPlanId]
                    );
                    $newPlan = $planQuery->fetch();
                    
                    if ($subscription && $newPlan) {
                        // Plan değişikliğini yap
                        $stmt = $database->query(
                            "UPDATE user_subscriptions 
                             SET plan_id = ?,
                                 end_date = DATE_ADD(start_date, INTERVAL ? DAY),
                                 updated_at = NOW()
                             WHERE id = ?",
                            [$newPlanId, $newPlan['duration_months'] * 30, $subscriptionId]
                        );
                        
                        // Kullanıcının premium_expires_at alanını ve is_premium alanlarını da güncelle
                        $userQuery = $database->query(
                            "UPDATE users 
                             SET premium_expires_at = (
                                SELECT MAX(end_date) 
                                FROM user_subscriptions 
                                WHERE user_id = ? AND (status = 'active' OR status = 'cancelled') AND end_date > NOW()
                             ),
                             is_premium = IF((SELECT COUNT(*) FROM user_subscriptions WHERE user_id = ? AND (status = 'active' OR status = 'cancelled') AND end_date > NOW()) > 0, 1, 0)
                             WHERE id = ?",
                            [$subscription['user_id'], $subscription['user_id'], $subscription['user_id']]
                        );
                        
                        $success = "Abonelik planı başarıyla değiştirildi.";
                    } else {
                        $error = "Abonelik veya plan bulunamadı.";
                    }
                } catch (Exception $e) {
                    $error = "Plan değiştirilirken bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $error = "Geçersiz abonelik ID veya plan ID.";
            }
            break;
    }
}

// Filtreler
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $database = new Database();
    
    // WHERE koşulları oluştur
    $whereConditions = [];
    $whereParams = [];
    
    if ($status !== 'all') {
        $whereConditions[] = "us.status = ?";
        $whereParams[] = $status;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
        $whereParams[] = "%$search%";
        $whereParams[] = "%$search%";
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // ORDER BY koşulu belirle
    $orderBy = match($sort) {
        'oldest' => "us.created_at ASC",
        'price_high' => "sp.price DESC",
        'price_low' => "sp.price ASC",
        'end_date' => "us.end_date ASC",
        default => "us.created_at DESC", // newest
    };
    
    // Toplam kayıt sayısını hesapla
    $countQuery = "
        SELECT COUNT(*) 
        FROM user_subscriptions us
        JOIN users u ON u.id = us.user_id
        JOIN subscription_plans sp ON sp.id = us.plan_id
        $whereClause
    ";
    $countStmt = $database->query($countQuery, $whereParams);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);
    
    // Abonelikleri getir
    $subscriptionsQuery = "
        SELECT us.*, 
               u.username, u.email, u.is_premium,
               sp.name as plan_name, sp.price as plan_price
        FROM user_subscriptions us
        JOIN users u ON u.id = us.user_id
        JOIN subscription_plans sp ON sp.id = us.plan_id
        $whereClause
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset
    ";
    $subscriptionsStmt = $database->query($subscriptionsQuery, $whereParams);
    $subscriptions = $subscriptionsStmt->fetchAll();
    
    // Tüm planları getir (plan değiştirme seçeneği için)
    $plansQuery = "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC";
    $plansStmt = $database->query($plansQuery);
    $allPlans = $plansStmt->fetchAll();

} catch (Exception $e) {
    $error = "Veriler alınırken bir hata oluştu: " . $e->getMessage();
    $subscriptions = [];
    $totalItems = 0;
    $totalPages = 1;
    $allPlans = [];
}
?>

<!-- Başlık ve Filtreleme -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kullanıcı Abonelikleri</h2>
        <p class="text-gray-600 dark:text-gray-400">Premium üye aboneliklerini yönetin</p>
    </div>
</div>

<?php if ($success): ?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($expiredCount > 0): ?>
<div class="bg-blue-100 dark:bg-blue-900 border border-blue-400 text-blue-700 dark:text-blue-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-info-circle mr-2"></i>Süresi dolmuş <?php echo $expiredCount; ?> abonelik güncellendi ve kullanıcı premium durumları otomatik olarak kontrol edildi.
</div>
<?php endif; ?>

<!-- Filtreleme Alanı -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arama</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kullanıcı adı veya e-posta..." 
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
        </div>
        
        <div class="w-full sm:w-auto">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tümü</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Aktif</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>İptal Edilmiş</option>
                <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Süresi Dolmuş</option>
            </select>
        </div>
        
        <div class="w-full sm:w-auto">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sıralama</label>
            <select name="sort" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>En Yeni</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>En Eski</option>
                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Fiyat (Yüksekten)</option>
                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Fiyat (Düşükten)</option>
                <option value="end_date" <?php echo $sort === 'end_date' ? 'selected' : ''; ?>>Bitiş Tarihi</option>
            </select>
        </div>
        
        <div class="mt-4 sm:mt-0 sm:self-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
                <i class="fas fa-search mr-2"></i>Filtrele
            </button>
        </div>
    </form>
</div>

<!-- İstatistik Özeti -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php 
    try {
        $statsDb = new Database();
        
        // Aktif abonelik sayısı
        $activeCount = $statsDb->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active'")->fetchColumn();
        
        // İptal edilmiş abonelik sayısı
        $cancelledCount = $statsDb->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'cancelled'")->fetchColumn();
        
        // Toplam aylık gelir
        $monthlyRevenue = $statsDb->query("
            SELECT COALESCE(SUM(
                CASE 
                    WHEN sp.duration_months >= 1 THEN sp.price / sp.duration_months
                    ELSE sp.price
                END
            ), 0) as revenue
            FROM user_subscriptions us
            JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.status = 'active'
        ")->fetchColumn();
        
        // Süresi yakında dolacak abonelik sayısı
        $expiringCount = $statsDb->query("
            SELECT COUNT(*) 
            FROM user_subscriptions 
            WHERE status = 'active' AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ")->fetchColumn();
    } catch (Exception $e) {
        $activeCount = 0;
        $cancelledCount = 0;
        $monthlyRevenue = 0;
        $expiringCount = 0;
    }
    ?>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-full">
                <i class="fas fa-user-check text-green-600 dark:text-green-400"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktif Abonelikler</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($activeCount); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="p-2 bg-red-100 dark:bg-red-900 rounded-full">
                <i class="fas fa-user-times text-red-600 dark:text-red-400"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">İptal Edilenler</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($cancelledCount); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-full">
                <i class="fas fa-lira-sign text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aylık Gelir</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($monthlyRevenue, 2); ?> ₺</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-full">
                <i class="fas fa-hourglass-end text-orange-600 dark:text-orange-400"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Süresi Yakında Dolacak</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($expiringCount); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Abonelikler Tablosu -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kullanıcı</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Başlangıç</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bitiş</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($subscriptions)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        <?php echo !empty($search) || $status !== 'all' ? 'Filtrelere uygun abonelik bulunamadı.' : 'Henüz abonelik kaydı bulunmuyor.'; ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($subscriptions as $sub): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($sub['username']); ?></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($sub['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($sub['plan_name']); ?></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo number_format($sub['plan_price'], 2); ?> ₺</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                switch($sub['status']) {
                                    case 'active':
                                        echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                        break;
                                    case 'cancelled':
                                        echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                        break;
                                    case 'expired':
                                        echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                }
                                ?>">
                                <?php 
                                switch($sub['status']) {
                                    case 'active':
                                        echo 'Aktif';
                                        break;
                                    case 'cancelled':
                                        echo 'İptal Edildi';
                                        break;
                                    case 'expired':
                                        echo 'Süresi Doldu';
                                        break;
                                    default:
                                        echo ucfirst($sub['status']);
                                }
                                ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo date('d.m.Y', strtotime($sub['start_date'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $endDate = strtotime($sub['end_date']);
                            $now = time();
                            $daysLeft = round(($endDate - $now) / (60 * 60 * 24));
                            
                            echo '<div class="text-sm text-gray-500 dark:text-gray-400">' . date('d.m.Y', $endDate) . '</div>';
                            
                            if ($sub['status'] === 'active') {
                                if ($daysLeft <= 7 && $daysLeft > 0) {
                                    echo '<div class="text-xs text-orange-500 dark:text-orange-400 mt-1">' . $daysLeft . ' gün kaldı</div>';
                                } else if ($daysLeft > 0) {
                                    echo '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">' . $daysLeft . ' gün kaldı</div>';
                                }
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <div class="flex items-center space-x-2">
                                <button onclick="openActionsModal(<?php echo htmlspecialchars(json_encode($sub)); ?>)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <?php if ($sub['status'] === 'active'): ?>
                                <button onclick="confirmCancelSubscription(<?php echo $sub['id']; ?>)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <?php endif; ?>
                                <a href="?page=users&action=edit&id=<?php echo $sub['user_id']; ?>" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                                    <i class="fas fa-user-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sayfalama -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-center">
    <div class="flex items-center space-x-1">
        <?php if ($page > 1): ?>
        <a href="?page=user_subscriptions&p=<?php echo $page-1; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        if ($startPage > 1) {
            echo '<a href="?page=user_subscriptions&p=1&status='.$status.'&sort='.$sort.'&search='.urlencode($search).'" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">1</a>';
            if ($startPage > 2) {
                echo '<span class="px-2 py-1 text-gray-500 dark:text-gray-400">...</span>';
            }
        }
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $page) {
                echo '<span class="px-3 py-1 rounded-md bg-blue-600 text-white text-sm">'.$i.'</span>';
            } else {
                echo '<a href="?page=user_subscriptions&p='.$i.'&status='.$status.'&sort='.$sort.'&search='.urlencode($search).'" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">'.$i.'</a>';
            }
        }
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                echo '<span class="px-2 py-1 text-gray-500 dark:text-gray-400">...</span>';
            }
            echo '<a href="?page=user_subscriptions&p='.$totalPages.'&status='.$status.'&sort='.$sort.'&search='.urlencode($search).'" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">'.$totalPages.'</a>';
        }
        ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=user_subscriptions&p=<?php echo $page+1; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- İşlemler Modal -->
<div id="actionsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Abonelik İşlemleri
                </h3>
            </div>
            
            <div class="p-6">
                <div id="subscriptionDetails" class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div id="username" class="text-lg font-semibold text-gray-900 dark:text-white">username</div>
                            <div id="email" class="text-sm text-gray-500 dark:text-gray-400">email</div>
                        </div>
                        <div id="statusBadge" class="px-2 py-1 text-xs font-semibold rounded-full"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Plan</div>
                            <div id="planName" class="text-sm font-medium text-gray-900 dark:text-white">Plan Name</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Tutar</div>
                            <div id="planPrice" class="text-sm font-medium text-gray-900 dark:text-white">Price</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Başlangıç Tarihi</div>
                            <div id="startDate" class="text-sm font-medium text-gray-900 dark:text-white">Start Date</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Bitiş Tarihi</div>
                            <div id="endDate" class="text-sm font-medium text-gray-900 dark:text-white">End Date</div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <!-- Süre Uzatma -->
                    <form method="POST" id="extendForm" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <input type="hidden" name="action" value="extend_subscription">
                        <input type="hidden" name="subscription_id" id="extend_subscription_id" value="">
                        
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Abonelik Süresini Uzat</h4>
                        
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <button type="button" class="extend-option py-1 px-2 border border-gray-300 dark:border-gray-600 rounded text-sm" data-days="30">+30 gün</button>
                            <button type="button" class="extend-option py-1 px-2 border border-gray-300 dark:border-gray-600 rounded text-sm" data-days="90">+90 gün</button>
                            <button type="button" class="extend-option py-1 px-2 border border-gray-300 dark:border-gray-600 rounded text-sm" data-days="365">+365 gün</button>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <input type="number" name="extend_days" min="1" value="30" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            </div>
                            <div>
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm">
                                    Uzat
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Plan Değiştirme -->
                    <form method="POST" id="changePlanForm" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <input type="hidden" name="action" value="change_plan">
                        <input type="hidden" name="subscription_id" id="change_subscription_id" value="">
                        
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Plan Değiştir</h4>
                        
                        <div class="grid grid-cols-1 gap-2 mb-3">
                            <select name="new_plan_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <?php foreach ($allPlans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo htmlspecialchars($plan['name']); ?> - <?php echo number_format($plan['price'], 2); ?> ₺
                                    <?php
                                    if ($plan['duration_months'] == 1) {
                                        echo '(aylık)';
                                    } elseif ($plan['duration_months'] == 12) {
                                        echo '(yıllık)';
                                    } else {
                                        echo '(' . $plan['duration_months'] . ' ay)';
                                    }
                                    ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
                                Planı Değiştir
                            </button>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Not: Plan değişikliği aboneliğin başlangıç tarihinden itibaren yeniden hesaplanır.</p>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-right">
                <button type="button" onclick="closeActionsModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm">
                    Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Abonelik İptal Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    Aboneliği İptal Et
                </h3>
            </div>
            
            <form method="POST" id="cancelForm">
                <div class="p-6">
                    <input type="hidden" name="action" value="cancel_subscription">
                    <input type="hidden" name="subscription_id" id="cancel_subscription_id" value="">
                    
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        Bu aboneliği iptal etmek istediğinizden emin misiniz? İptal edilmesi halinde kullanıcının premium özelliklere erişimi abonelik süresi sonuna kadar devam edecektir.
                    </p>
                    
                    <div class="mt-4 flex items-start">
                        <div class="flex items-center h-5">
                            <input id="immediate_termination" name="immediate_termination" type="checkbox" value="1" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="immediate_termination" class="font-medium text-gray-700 dark:text-gray-300">Aboneliği hemen sonlandır</label>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">Bu seçenek işaretlenirse, kullanıcının premium erişimi hemen kaldırılacaktır.</p>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button type="button" onclick="closeCancelModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm">
                        Vazgeç
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm">
                        İptal Et
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// İşlemler Modal
function openActionsModal(subscription) {
    // Detayları doldur
    document.getElementById('username').textContent = subscription.username;
    document.getElementById('email').textContent = subscription.email;
    
    const statusBadge = document.getElementById('statusBadge');
    statusBadge.textContent = subscription.status === 'active' ? 'Aktif' : 
                             (subscription.status === 'cancelled' ? 'İptal Edildi' : 'Süresi Doldu');
    
    if (subscription.status === 'active') {
        statusBadge.classList.add('bg-green-100', 'text-green-800', 'dark:bg-green-900', 'dark:text-green-200');
    } else if (subscription.status === 'cancelled') {
        statusBadge.classList.add('bg-yellow-100', 'text-yellow-800', 'dark:bg-yellow-900', 'dark:text-yellow-200');
    } else {
        statusBadge.classList.add('bg-red-100', 'text-red-800', 'dark:bg-red-900', 'dark:text-red-200');
    }
    
    document.getElementById('planName').textContent = subscription.plan_name;
    document.getElementById('planPrice').textContent = subscription.plan_price + ' ₺';
    
    const startDate = new Date(subscription.start_date);
    const endDate = new Date(subscription.end_date);
    document.getElementById('startDate').textContent = startDate.toLocaleDateString('tr-TR');
    document.getElementById('endDate').textContent = endDate.toLocaleDateString('tr-TR');
    
    // Form değerlerini ayarla
    document.getElementById('extend_subscription_id').value = subscription.id;
    document.getElementById('change_subscription_id').value = subscription.id;
    
    // Modalı aç
    document.getElementById('actionsModal').classList.remove('hidden');
}

function closeActionsModal() {
    document.getElementById('actionsModal').classList.add('hidden');
}

// Süre uzatma seçenekleri
document.querySelectorAll('.extend-option').forEach(button => {
    button.addEventListener('click', function() {
        const days = this.getAttribute('data-days');
        document.querySelector('input[name="extend_days"]').value = days;
    });
});

// Abonelik İptal Modal
function confirmCancelSubscription(subscriptionId) {
    document.getElementById('cancel_subscription_id').value = subscriptionId;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}

// Tüm modallar için ESC tuşu ile kapatma
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeActionsModal();
        closeCancelModal();
    }
});
</script>
