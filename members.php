<?php
require_once 'includes/header.php';

$language = getCurrentLanguage();

// Ay isimlerini dil seçimine göre ayarlama
function getLocalizedMonth($month, $language) {
    if ($language == 'en') {
        return $month; // İngilizce aylar için doğrudan kullan
    } else {
        // Türkçe ay isimleri
        $months = [
            'Jan' => 'Ocak',
            'Feb' => 'Şubat',
            'Mar' => 'Mart',
            'Apr' => 'Nisan',
            'May' => 'Mayıs',
            'Jun' => 'Haziran',
            'Jul' => 'Temmuz',
            'Aug' => 'Ağustos',
            'Sep' => 'Eylül',
            'Oct' => 'Ekim',
            'Nov' => 'Kasım',
            'Dec' => 'Aralık'
        ];
        return $months[$month] ?? $month;
    }
}

function formatLocalizedDate($date, $language) {
    $month = date('M', strtotime($date));
    $year = date('Y', strtotime($date));
    return getLocalizedMonth($month, $language) . ' ' . $year;
}

// Geriye uyumluluk için türkçe fonksiyonları
function getTurkishMonth($month) {
    return getLocalizedMonth($month, 'tr');
}

function formatTurkishDate($date) {
    return formatLocalizedDate($date, 'tr');
}

$pageTitle = ($language == 'en' ? 'Members - ' : 'Üyeler - ') . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'Platform members and authors.' : 'Platform üyeleri ve yazarları.';

// Debug modu
$debug = false;
$errors = [];

// Sayfalama parametreleri
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// Arama parametresi
$search = sanitizeInput($_GET['search'] ?? '');
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "AND (u.username LIKE ? OR u.email LIKE ?)";
    $searchParams = ["%$search%", "%$search%"];
}

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Veritabanı bağlantı kontrolü
    if (!$database->isConnected()) {
        throw new Exception("Veritabanı bağlantısı kurulamadı.");
    }
    
    // Toplam üye sayısı (sadece onaylanmış üyeler)
    $countStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM users u 
        WHERE u.is_approved = 1 $searchCondition
    ");
    $countStmt->execute($searchParams);
    $totalMembers = $countStmt->fetchColumn();
    
    if ($debug) {
        echo "<div class='bg-yellow-100 dark:bg-yellow-900 p-4 mb-4 rounded-lg'>";
        echo "<p>Toplam Üye Sayısı: $totalMembers</p>";
        echo "<p>SQL Sorgusu: SELECT COUNT(*) FROM users u WHERE u.is_approved = 1 $searchCondition</p>";
        echo "<p>Arama Parametreleri: " . print_r($searchParams, true) . "</p>";
        echo "</div>";
    }
    
    // Üyeleri çek
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.profile_image,
            u.is_approved,
            u.is_premium,
            u.created_at,
            COUNT(DISTINCT a.id) as article_count,
            SUM(CASE WHEN a.status = 'published' THEN a.views_count ELSE 0 END) as total_views,
            COUNT(DISTINCT c.id) as comment_count
        FROM users u
        LEFT JOIN articles a ON u.id = a.user_id AND a.status = 'published'
        LEFT JOIN comments c ON u.id = c.user_id AND c.is_approved = 1
        WHERE u.is_approved = 1 $searchCondition
        GROUP BY u.id, u.username, u.email, u.profile_image, u.is_approved, u.is_premium, u.created_at
        ORDER BY article_count DESC, u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params = array_merge($searchParams, [$limit, $offset]);
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($debug) {
        echo "<div class='bg-yellow-100 dark:bg-yellow-900 p-4 mb-4 rounded-lg'>";
        echo "<p>Bulunan Üye Sayısı: " . count($members) . "</p>";
        if (!empty($members)) {
            echo "<p>İlk Üye Bilgileri:</p>";
            echo "<pre>" . print_r($members[0], true) . "</pre>";
        }
        echo "</div>";
    }
    
    // Sayfa sayısı hesapla
    $totalPages = ceil($totalMembers / $limit);
    
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    $members = [];
    $totalMembers = 0;
    $totalPages = 1;
    
    if ($debug) {
        echo "<div class='bg-red-100 dark:bg-red-900 p-4 mb-4 rounded-lg'>";
        echo "<p>Hata: " . $e->getMessage() . "</p>";
        echo "<p>Hata Detayı: " . $e->getTraceAsString() . "</p>";
        echo "</div>";
    }
}

// Hata mesajlarını göster
if (!empty($errors)) {
    echo "<div class='bg-red-100 dark:bg-red-900 p-4 mb-4 rounded-lg'>";
    foreach ($errors as $error) {
        echo "<p class='text-red-700 dark:text-red-200'><i class='fas fa-exclamation-circle mr-2'></i>$error</p>";
    }
    echo "</div>";
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Başlık ve Arama -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        <?php echo $language == 'en' ? 'Members' : 'Üyeler'; ?>
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?php echo $language == 'en' ? 'Meet our platform members and authors' : 'Platform üyelerimiz ve yazarlarımızla tanışın'; ?>
                    </p>
                </div>
                
                <!-- Arama Formu -->
                <div class="w-full md:w-auto">
                    <form method="GET" class="flex gap-2">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="<?php echo $language == 'en' ? 'Search members...' : 'Üye ara...'; ?>" 
                            class="flex-1 md:w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="/members" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            <?= number_format($totalMembers) ?>
                        </div>
                        <div class="text-gray-600 dark:text-gray-400">
                            <?php echo $language == 'en' ? 'Total Members' : 'Toplam Üye'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pen-alt text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            <?php
                            try {
                                $authorCount = $db->query("SELECT COUNT(DISTINCT u.user_id) FROM articles u JOIN users usr ON u.user_id = usr.id WHERE u.status = 'published' AND usr.is_approved = 1")->fetchColumn();
                                echo number_format($authorCount);
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="text-gray-600 dark:text-gray-400">
                            <?php echo $language == 'en' ? 'Active Authors' : 'Aktif Yazar'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-newspaper text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            <?php
                            try {
                                $articleCount = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
                                echo number_format($articleCount);
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="text-gray-600 dark:text-gray-400">
                            <?php echo $language == 'en' ? 'Total Articles' : 'Toplam Makale'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arama Sonucu Bilgisi -->
        <?php if (!empty($search)): ?>
        <div class="mb-6">
            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                <p class="text-blue-800 dark:text-blue-200">
                    <i class="fas fa-search mr-2"></i>
                    <?php if ($language == 'en'): ?>
                    <?= $totalMembers ?> member(s) found for "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php else: ?>
                    "<strong><?= htmlspecialchars($search) ?></strong>" araması için <?= $totalMembers ?> üye bulundu.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Üye Listesi -->
        <?php if (empty($members)): ?>
        <div class="text-center py-12">
            <i class="fas fa-user-friends text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">
                <?php if ($language == 'en'): ?>
                    <?= !empty($search) ? 'No search results found' : 'No members yet' ?>
                <?php else: ?>
                    <?= !empty($search) ? 'Arama sonucu bulunamadı' : 'Henüz üye bulunmuyor' ?>
                <?php endif; ?>
            </h3>
            <p class="text-gray-500 dark:text-gray-500">
                <?php if ($language == 'en'): ?>
                    <?= !empty($search) ? 'Try different keywords.' : 'Members will appear here when they join.' ?>
                <?php else: ?>
                    <?= !empty($search) ? 'Farklı anahtar kelimeler deneyin.' : 'İlk üyelerimiz katıldığında burada görünecekler.' ?>
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($members as $member): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                <div class="text-center mb-4">
                    <?php if (!empty($member['profile_image'])): ?>
                        <?php
                        // Base64 kodlamasıyla resim gösterme (dosya erişim sorunu çözümü)
                        $profileImageUrl = '';
                        $imagePath = ltrim($member['profile_image'], '/');
                        
                        if (file_exists($imagePath) && is_readable($imagePath)) {
                            $imageData = file_get_contents($imagePath);
                            $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                            $base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                            $profileImageUrl = $base64;
                        ?>
                        <img src="<?= $profileImageUrl ?>" 
                             alt="<?= htmlspecialchars($member['username']) ?>" 
                             class="w-16 h-16 rounded-full mx-auto mb-3 object-cover">
                        <?php } else { ?>
                        <!-- Varsayılan profil resmi (dosya okunamadı) -->
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-blue-600 dark:text-blue-400 font-medium">
                                <?= strtoupper(substr($member['username'], 0, 1)) ?>
                            </span>
                        </div>
                        <?php } ?>
                    <?php else: ?>
                    <!-- Profil resmi yok -->
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-blue-600 dark:text-blue-400 font-medium">
                            <?= strtoupper(substr($member['username'], 0, 1)) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <a href="/uye/<?= urlencode($member['username']) ?>" class="hover:text-blue-600 dark:hover:text-blue-400">
                            <?= htmlspecialchars($member['username']) ?>
                        </a>
                    </h3>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                        <span><?= number_format($member['article_count']) ?> <?php echo $language == 'en' ? 'articles' : 'makale'; ?></span>
                        <span>•</span>
                        <span><?= number_format($member['comment_count']) ?> <?php echo $language == 'en' ? 'comments' : 'yorum'; ?></span>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= number_format($member['article_count']) ?>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo $language == 'en' ? 'Articles' : 'Makale'; ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= number_format($member['total_views'] ?? 0) ?>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo $language == 'en' ? 'Views' : 'Görüntüleme'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Üyelik Tarihi -->
                <div class="mt-4 text-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <?php echo $language == 'en' ? 'Member since: ' : 'Üyelik: '; ?>
                        <?= $language == 'en' ? date('M Y', strtotime($member['created_at'])) : formatTurkishDate($member['created_at']) ?>
                    </span>
                </div>

                <!-- Profil Butonu -->
                <div class="mt-4">
                    <a href="/profile?username=<?= urlencode($member['username']) ?>" 
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition-colors flex items-center justify-center">
                        <i class="fas fa-user mr-2"></i><?php echo $language == 'en' ? 'View Profile' : 'Profili Gör'; ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Sayfalama -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center">
            <nav class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                   class="px-3 py-2 border rounded-lg <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 