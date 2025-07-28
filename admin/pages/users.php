<?php
require_once __DIR__ . '/../../config/database.php';

// Üye ekleme veya düzenleme formu kontrolü
$action = $_GET['action'] ?? '';
if (($action === 'add' || $action === 'edit') && !isset($userAdded)) {
    include 'user-form.php';
    return;
}

// Veritabanı bağlantısı kontrolü
try {
    if (!isset($db) || !$db) {
        throw new Exception("Veritabanı bağlantısı yok");
    }
    
    // Sayfa ve filtreleme ayarları
    $page = intval($_GET['p'] ?? 1);
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    // URL'den gelen success mesajını al
    if (isset($_GET['success'])) {
        $success = $_GET['success'];
    }

    // Kullanıcı işlemleri
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
                    case 'approve':
            $id = intval($_POST['user_id']);
            // Mevcut kullanıcının kendisini onaylamasını engelle (gereksiz ama tutarlılık için)
            if ($id == getCurrentUser()['id']) {
                $error = "Kendi durumunuzu değiştiremezsiniz.";
                break;
            }
            $stmt = $db->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Kullanıcı onaylandı.";
            break;
            
        case 'block':
            $id = intval($_POST['user_id']);
            // Mevcut kullanıcının kendisini engellemesini engelle
            if ($id == getCurrentUser()['id']) {
                $error = "Kendinizi engelleyemezsiniz.";
                break;
            }
            $stmt = $db->prepare("UPDATE users SET is_approved = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Kullanıcı engellendi.";
            break;
                
            case 'make_admin':
                $id = intval($_POST['user_id']);
                $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Kullanıcı admin yapıldı.";
                break;
                
                    case 'remove_admin':
            $id = intval($_POST['user_id']);
            // Mevcut kullanıcının kendi admin yetkisini kaldırmasını engelle
            if ($id == getCurrentUser()['id']) {
                $error = "Kendi admin yetkinizi kaldıramazsınız.";
                break;
            }
            $stmt = $db->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Admin yetkisi kaldırıldı.";
            break;
            
        case 'delete':
            $id = intval($_POST['user_id']);
            
            // Admin kullanıcılarının silinmesini engelle
            $userCheck = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $userCheck->execute([$id]);
            $userToDelete = $userCheck->fetch();
            
            if ($userToDelete && $userToDelete['is_admin']) {
                $error = "Admin kullanıcıları silinemez.";
                break;
            }
            
            // Mevcut kullanıcının kendisini silmesini engelle
            if ($id == getCurrentUser()['id']) {
                $error = "Kendinizi silemezsiniz.";
                break;
            }
            
            // İlişkili kayıtları doğru sırayla sil
            $db->beginTransaction();
            try {
                // Önce yorumları sil
                $db->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$id]);
                
                // Makaleleri sil
                $db->prepare("DELETE FROM articles WHERE user_id = ?")->execute([$id]);
                
                // Kullanıcı abonelikleri ve ödemeleri sil
                $db->prepare("DELETE FROM user_subscriptions WHERE user_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM payments WHERE user_id = ?")->execute([$id]);
                
                // En son kullanıcıyı sil
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                
                $db->commit();
                $success = "Kullanıcı ve ilişkili tüm kayıtları başarıyla silindi.";
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Kullanıcı silinirken bir hata oluştu: " . $e->getMessage();
            }
            $success = "Kullanıcı silindi.";
            break;
            
        case 'bulk_approve':
            if (!empty($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                $selectedIds = array_map('intval', $_POST['selected_users']);
                $currentUserId = getCurrentUser()['id'];
                
                // Mevcut kullanıcıyı listeden çıkar
                $selectedIds = array_filter($selectedIds, function($id) use ($currentUserId) {
                    return $id != $currentUserId;
                });
                
                if (!empty($selectedIds)) {
                    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                    $stmt = $db->prepare("UPDATE users SET is_approved = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $updatedCount = $stmt->rowCount();
                    $success = "$updatedCount kullanıcı başarıyla onaylandı.";
                } else {
                    $error = "Onaylanacak kullanıcı seçilmedi.";
                }
            } else {
                $error = "Onaylanacak kullanıcı seçilmedi.";
            }
            break;
            
        case 'bulk_block':
            if (!empty($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                $selectedIds = array_map('intval', $_POST['selected_users']);
                $currentUserId = getCurrentUser()['id'];
                
                // Mevcut kullanıcıyı listeden çıkar
                $selectedIds = array_filter($selectedIds, function($id) use ($currentUserId) {
                    return $id != $currentUserId;
                });
                
                if (!empty($selectedIds)) {
                    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                    $stmt = $db->prepare("UPDATE users SET is_approved = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $updatedCount = $stmt->rowCount();
                    $success = "$updatedCount kullanıcı başarıyla engellendi.";
                } else {
                    $error = "Engellenecek kullanıcı seçilmedi.";
                }
            } else {
                $error = "Engellenecek kullanıcı seçilmedi.";
            }
            break;
            
        case 'bulk_delete':
            if (!empty($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                $selectedIds = array_map('intval', $_POST['selected_users']);
                $currentUserId = getCurrentUser()['id'];
                
                // Mevcut kullanıcıyı ve admin kullanıcıları listeden çıkar
                $selectedIds = array_filter($selectedIds, function($id) use ($currentUserId, $db) {
                    if ($id == $currentUserId) return false;
                    
                    $userCheck = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
                    $userCheck->execute([$id]);
                    $user = $userCheck->fetch();
                    return !($user && $user['is_admin']);
                });
                
                if (!empty($selectedIds)) {
                    $db->beginTransaction();
                    try {
                        $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                        
                        // İlişkili kayıtları sil
                        $db->prepare("DELETE FROM comments WHERE user_id IN ($placeholders)")->execute($selectedIds);
                        $db->prepare("DELETE FROM articles WHERE user_id IN ($placeholders)")->execute($selectedIds);
                        $db->prepare("DELETE FROM user_subscriptions WHERE user_id IN ($placeholders)")->execute($selectedIds);
                        $db->prepare("DELETE FROM payments WHERE user_id IN ($placeholders)")->execute($selectedIds);
                        
                        // Kullanıcıları sil
                        $stmt = $db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $deletedCount = $stmt->rowCount();
                        
                        $db->commit();
                        $success = "$deletedCount kullanıcı başarıyla silindi.";
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = "Kullanıcılar silinirken bir hata oluştu: " . $e->getMessage();
                    }
                } else {
                    $error = "Silinecek kullanıcı seçilmedi veya seçilen kullanıcılar silinemez.";
                }
            } else {
                $error = "Silinecek kullanıcı seçilmedi.";
            }
            break;
            
        case 'add_user':
            $username = sanitizeInput($_POST['username'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $bio = sanitizeInput($_POST['bio'] ?? '');
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            $is_approved = isset($_POST['is_approved']) ? 1 : 0;
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = "Kullanıcı adı, e-posta ve şifre alanları zorunludur.";
                break;
            }
            
            // E-posta ve kullanıcı adı benzersizlik kontrolü
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $checkStmt->execute([$email, $username]);
            if ($checkStmt->fetch()) {
                $error = "Bu e-posta adresi veya kullanıcı adı zaten kullanımda.";
                break;
            }
            
            // Şifreyi hashle
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Kullanıcıyı ekle
            try {
                $insertStmt = $db->prepare("
                    INSERT INTO users (username, email, password, bio, is_admin, is_approved, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([$username, $email, $hashedPassword, $bio, $is_admin, $is_approved]);
                $success = "Yeni kullanıcı başarıyla eklendi.";
                
                // JavaScript ile yönlendirme
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '?page=users&success=" . urlencode("Yeni kullanıcı başarıyla eklendi.") . "';
                    }, 1000);
                </script>";
                $userAdded = true;
            } catch (Exception $e) {
                $error = "Kullanıcı eklenirken bir hata oluştu: " . $e->getMessage();
            }
            break;
            
        case 'edit_user':
            $user_id = intval($_POST['user_id']);
            $username = sanitizeInput($_POST['username'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $bio = sanitizeInput($_POST['bio'] ?? '');
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            $is_approved = isset($_POST['is_approved']) ? 1 : 0;
            
            if (empty($username) || empty($email)) {
                $error = "Kullanıcı adı ve e-posta alanları zorunludur.";
                break;
            }
            
            // E-posta ve kullanıcı adı benzersizlik kontrolü (mevcut kullanıcı hariç)
            $checkStmt = $db->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $checkStmt->execute([$email, $username, $user_id]);
            if ($checkStmt->fetch()) {
                $error = "Bu e-posta adresi veya kullanıcı adı zaten başka bir kullanıcı tarafından kullanılıyor.";
                break;
            }
            
            try {
                // Güncelleme işlemi için başlangıç sorgusu
                $query = "UPDATE users SET username = ?, email = ?, bio = ?, is_admin = ?, is_approved = ?";
                $params = [$username, $email, $bio, $is_admin, $is_approved];
                
                // Şifre dolu ise hashleyip sorguya ekle
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $query .= ", password = ?";
                    $params[] = $hashedPassword;
                }
                
                // WHERE koşulu ekle
                $query .= " WHERE id = ?";
                $params[] = $user_id;
                
                // Sorguyu çalıştır
                $updateStmt = $db->prepare($query);
                $updateStmt->execute($params);
                
                $success = "Kullanıcı bilgileri başarıyla güncellendi.";
                
                // JavaScript ile yönlendirme
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '?page=users&success=" . urlencode("Kullanıcı bilgileri başarıyla güncellendi.") . "';
                    }, 1000);
                </script>";
                $userAdded = true;
            } catch (Exception $e) {
                $error = "Kullanıcı güncellenirken bir hata oluştu: " . $e->getMessage();
            }
            break;
        }
    }

    // Where koşulları oluştur
    $whereConditions = [];
    $params = [];
    
    // Banlı kullanıcıları gösterme (varsayılan olarak)
    $filter_banned = $_GET['filter_banned'] ?? 'hide';
    if ($filter_banned === 'hide') {
        $whereConditions[] = "NOT EXISTS (SELECT 1 FROM banned_users b WHERE b.user_id = u.id AND b.is_active = 1 AND (b.expiry_date IS NULL OR b.expiry_date > NOW()))";
    } elseif ($filter_banned === 'only') {
        $whereConditions[] = "EXISTS (SELECT 1 FROM banned_users b WHERE b.user_id = u.id AND b.is_active = 1 AND (b.expiry_date IS NULL OR b.expiry_date > NOW()))";
    }

    if ($filter === 'approved') {
        $whereConditions[] = "u.is_approved = 1";
    } elseif ($filter === 'blocked') {
        $whereConditions[] = "u.is_approved = 0";
    } elseif ($filter === 'admin') {
        $whereConditions[] = "u.is_admin = 1";
    }

    if (!empty($search)) {
        $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Toplam kullanıcı sayısı
    $countQuery = "SELECT COUNT(*) FROM users u $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);

    // Kullanıcıları al
    $usersQuery = "SELECT u.*, 
                          COUNT(DISTINCT a.id) as article_count,
                          COUNT(DISTINCT c.id) as comment_count
                   FROM users u 
                   LEFT JOIN articles a ON u.id = a.user_id
                   LEFT JOIN comments c ON u.id = c.user_id
                   $whereClause
                   GROUP BY u.id
                   ORDER BY u.created_at DESC 
                   LIMIT $perPage OFFSET $offset";
    $usersStmt = $db->prepare($usersQuery);
    $usersStmt->execute($params);
    $users = $usersStmt->fetchAll();

    // İstatistikler
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as blocked,
        SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin
        FROM users";
    $statsStmt = $db->query($statsQuery);
    $stats = $statsStmt->fetch();
    
} catch (Exception $e) {
    // Hata durumunda demo verileri göster
    $error = "Veritabanı hatası: " . $e->getMessage();
    $users = [];
    $stats = ['total' => 0, 'approved' => 0, 'blocked' => 0, 'admin' => 0];
    $totalPages = 1;
    $totalUsers = 0;
}
?>

<?php if (isset($error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
    <p class="mt-2 text-sm">MySQL servisini başlatın ve veritabanı bağlantısını kontrol edin.</p>
</div>
<?php endif; ?>

<!-- Başlık ve Filtreler -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kullanıcılar</h2>
        <p class="text-gray-600 dark:text-gray-400">Üye kullanıcıları yönetin • Toplu işlemler için kullanıcı seçin</p>
    </div>
    
    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4 lg:mt-0">
        <a href="?page=users&action=add" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Yeni Üye Ekle
        </a>
        <form method="GET" class="flex space-x-2">
            <input type="hidden" name="page" value="users">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="hidden" name="filter_banned" value="<?php echo htmlspecialchars($filter_banned); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Kullanıcı ara..." 
                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['total']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-check text-green-600 dark:text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Onaylı</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['approved']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Engelli</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['blocked']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-crown text-purple-600 dark:text-purple-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Admin</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['admin']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Butonları -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="?page=users&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Tümü (<?php echo $stats['total']; ?>)
        </a>
        <a href="?page=users&filter=approved&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Onaylı (<?php echo $stats['approved']; ?>)
        </a>
        <a href="?page=users&filter=blocked&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'blocked' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Engelli (<?php echo $stats['blocked']; ?>)
        </a>
        <a href="?page=users&filter=admin&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'admin' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Admin (<?php echo $stats['admin']; ?>)
        </a>
        
        <!-- Banlı Kullanıcı Filtresi -->
        <?php
        // Varsayılan olarak banlı kullanıcıları gizle
        $filter_banned = $_GET['filter_banned'] ?? 'hide';
        
        // Banlı kullanıcı sayısını öğren
        $bannedStmt = $db->query("SELECT COUNT(DISTINCT user_id) as banned_count FROM banned_users WHERE is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())");
        $bannedCount = $bannedStmt->fetch()['banned_count'] ?? 0;
        ?>
        
        <a href="?page=users&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=show" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_banned === 'show' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Banlıları Göster
        </a>
        
        <a href="?page=users&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=only" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_banned === 'only' ? 'bg-orange-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Sadece Banlılar (<?php echo $bannedCount; ?>)
        </a>
        
        <a href="?page=users&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=hide" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_banned === 'hide' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Banlıları Gizle
        </a>
    </div>
</div>

<!-- Toplu İşlemler -->
<form id="bulkActionForm" method="POST" class="mb-4">
    <div id="bulkActions" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span id="selectedCount">0</span> kullanıcı seçildi
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="submitBulkAction('bulk_approve')" 
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm transition-colors inline-flex items-center">
                    <i class="fas fa-check mr-1"></i> Onayla
                </button>
                <button type="button" onclick="submitBulkAction('bulk_block')" 
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm transition-colors inline-flex items-center">
                    <i class="fas fa-ban mr-1"></i> Engelle
                </button>
                <button type="button" onclick="submitBulkAction('bulk_delete')" 
                        class="bg-red-700 hover:bg-red-800 text-white px-3 py-2 rounded-lg text-sm transition-colors inline-flex items-center">
                    <i class="fas fa-trash mr-1"></i> Sil
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Kullanıcı Listesi -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kullanıcı</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">E-posta</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İçerik</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kayıt Tarihi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4">
                            <?php if ($user['id'] != getCurrentUser()['id']): ?>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" 
                                   class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <?php else: ?>
                            <span class="text-gray-400 dark:text-gray-600" title="Kendinizi seçemezsiniz">
                                <i class="fas fa-lock text-xs"></i>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if ($user['is_admin']): ?>
                                        <i class="fas fa-crown text-yellow-500 ml-1" title="Admin"></i>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            <div class="flex space-x-3">
                                <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full text-xs">
                                    <?php echo $user['article_count']; ?> makale
                                </span>
                                <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full text-xs">
                                    <?php echo $user['comment_count']; ?> yorum
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $user['is_approved'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                <?php echo $user['is_approved'] ? 'Aktif' : 'Engelli'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <!-- Düzenle butonu - her kullanıcı için gösterilir -->
                                <a href="?page=users&action=edit&id=<?php echo $user['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($user['id'] != getCurrentUser()['id']): ?>
                                    <?php if ($user['is_approved']): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="block">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                                                onclick="return confirm('Bu kullanıcıyı engellemek istediğinizden emin misiniz?')" title="Engelle">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Onayla">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-600" title="Kendi durumunuzu değiştiremezsiniz">
                                        <i class="fas fa-user-check"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!$user['is_admin']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="make_admin">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300" 
                                            onclick="return confirm('Bu kullanıcıyı admin yapmak istediğinizden emin misiniz?')" title="Admin Yap">
                                        <i class="fas fa-crown"></i>
                                    </button>
                                </form>
                                <?php elseif ($user['id'] != getCurrentUser()['id']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="remove_admin">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" 
                                            onclick="return confirm('Admin yetkisini kaldırmak istediğinizden emin misiniz?')" title="Admin Yetkisini Kaldır">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-600" title="Kendi admin yetkinizi kaldıramazsınız">
                                    <i class="fas fa-user-minus"></i>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!$user['is_admin'] && $user['id'] != getCurrentUser()['id']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            onclick="return confirm('Bu kullanıcıyı ve tüm içeriğini silmek istediğinizden emin misiniz?')" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-600" title="<?php echo $user['is_admin'] ? 'Admin kullanıcıları silinemez' : 'Kendinizi silemezsiniz'; ?>">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>Henüz kullanıcı bulunmuyor.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sayfalama -->
<?php if ($totalPages > 1): ?>
<div class="flex flex-col sm:flex-row justify-between items-center mt-6">
    <div class="text-sm text-gray-700 dark:text-gray-300 mb-4 sm:mb-0">
        Toplam <?php echo $totalUsers; ?> üyeden <?php echo (($page - 1) * $perPage) + 1; ?>-<?php echo min($page * $perPage, $totalUsers); ?> arası gösteriliyor
    </div>
    
    <nav class="flex items-center space-x-1">
        <?php if ($page > 1): ?>
            <a href="?page=users&p=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=<?php echo urlencode($filter_banned); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-chevron-left"></i> Önceki
            </a>
        <?php endif; ?>
        
        <?php
        // Sayfa numaralarını akıllıca göster
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        // İlk sayfa
        if ($startPage > 1): ?>
            <a href="?page=users&p=1&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=<?php echo urlencode($filter_banned); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                1
            </a>
            <?php if ($startPage > 2): ?>
                <span class="px-2 text-gray-400">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=users&p=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=<?php echo urlencode($filter_banned); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php
        // Son sayfa
        if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
                <span class="px-2 text-gray-400">...</span>
            <?php endif; ?>
            <a href="?page=users&p=<?php echo $totalPages; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=<?php echo urlencode($filter_banned); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <?php echo $totalPages; ?>
            </a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=users&p=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&filter_banned=<?php echo urlencode($filter_banned); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                Sonraki <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkActionForm = document.getElementById('bulkActionForm');

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCountSpan.textContent = count;
        
        if (count > 0) {
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
        
        // Tümünü seç checkbox'ının durumunu güncelle
        if (count === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (count === userCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }

    // Tümünü seç/bırak
    selectAllCheckbox.addEventListener('change', function() {
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Bireysel checkbox'lar
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    // Sayfa yüklendiğinde durumu güncelle
    updateBulkActions();
});

document.addEventListener('DOMContentLoaded', function() {
    // İşlem kodları buraya gelecek
});

function submitBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Lütfen işlem yapmak istediğiniz kullanıcıları seçin.');
        return;
    }
    
    let message = '';
    switch(action) {
        case 'bulk_approve':
            message = checkedBoxes.length + ' kullanıcıyı onaylamak istediğinizden emin misiniz?';
            break;
        case 'bulk_block':
            message = checkedBoxes.length + ' kullanıcıyı engellemek istediğinizden emin misiniz?';
            break;
        case 'bulk_delete':
            message = checkedBoxes.length + ' kullanıcıyı ve tüm içeriklerini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!';
            break;
    }
    
    if (confirm(message)) {
        const form = document.getElementById('bulkActionForm');
        
        // Mevcut gizli inputları temizle
        const existingInputs = form.querySelectorAll('input[type="hidden"]');
        existingInputs.forEach(input => input.remove());
        
        // Action input'u ekle
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);
        
        // Seçili kullanıcı ID'lerini ekle
        checkedBoxes.forEach(checkbox => {
            const userInput = document.createElement('input');
            userInput.type = 'hidden';
            userInput.name = 'selected_users[]';
            userInput.value = checkbox.value;
            form.appendChild(userInput);
        });
        
        // Formu gönder
        form.submit();
    }
}
</script>

