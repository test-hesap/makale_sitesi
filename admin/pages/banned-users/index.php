<?php
// Admin girişi kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

// Eylem işlemleri
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Ban kaldırma işlemi
    if ($action === 'unban_user' && isset($_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        if (unbanUser($userId)) {
            addAlert('success', 'Kullanıcı banı başarıyla kaldırıldı.');
        } else {
            addAlert('error', 'Kullanıcı banı kaldırılırken bir hata oluştu.');
        }
    }
    
    // IP ban kaldırma işlemi
    elseif ($action === 'unban_ip' && isset($_POST['ip_id'])) {
        $ipId = intval($_POST['ip_id']);
        
        try {
            $database = new Database();
            $db = $database->pdo;
            
            // IP adresini al
            $stmt = $db->prepare("SELECT ip_address FROM ip_bans WHERE id = ?");
            $stmt->execute([$ipId]);
            $result = $stmt->fetch();
            
            if ($result && unbanIP($result['ip_address'])) {
                addAlert('success', 'IP banı başarıyla kaldırıldı.');
            } else {
                addAlert('error', 'IP banı kaldırılırken bir hata oluştu.');
            }
        } catch (Exception $e) {
            addAlert('error', 'Hata: ' . $e->getMessage());
        }
    }
}

// Tab seçimi
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
?>

<div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
    <h1 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
        Banlı Üyeler ve IP Adresleri
    </h1>
    <div>
        <a href="?page=users" class="btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Tüm Üyeler
        </a>
    </div>
</div>

<div class="p-4">
    <ul class="flex border-b border-gray-200 mb-4">
        <li class="mr-1">
            <a href="?page=banned-users&tab=users" class="inline-block py-2 px-4 <?php echo $activeTab === 'users' ? 'bg-blue-600 text-white rounded-t-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-t-lg'; ?>">
                <i class="fas fa-user-slash"></i> Banlı Üyeler
            </a>
        </li>
        <li class="mr-1">
            <a href="?page=banned-users&tab=ips" class="inline-block py-2 px-4 <?php echo $activeTab === 'ips' ? 'bg-blue-600 text-white rounded-t-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-t-lg'; ?>">
                <i class="fas fa-ban"></i> Banlı IP Adresleri
            </a>
        </li>
        <li class="mr-1">
            <a href="?page=banned-users&tab=attempts" class="inline-block py-2 px-4 <?php echo $activeTab === 'attempts' ? 'bg-blue-600 text-white rounded-t-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-t-lg'; ?>">
                <i class="fas fa-chart-line"></i> Başarısız Denemeler
            </a>
        </li>
    </ul>
    
    <?php if ($activeTab === 'users'): ?>
    
    <!-- Banlı Kullanıcılar -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Banlı Kullanıcılar</h2>
        </div>
        
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kullanıcı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ban Nedeni</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Süre Sonu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Banlayan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php
                        try {
                            $database = new Database();
                            $db = $database->pdo;
                            
                            $query = "SELECT b.*, u.username, u.email, a.username as banned_by_username
                                     FROM banned_users b
                                     LEFT JOIN users u ON b.user_id = u.id
                                     LEFT JOIN users a ON b.banned_by = a.id
                                     WHERE b.is_active = 1
                                     ORDER BY b.ban_date DESC";
                            
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $bannedUsers = $stmt->fetchAll();
                            
                            if (count($bannedUsers) > 0):
                                foreach ($bannedUsers as $user):
                                    // Süre sonu formatı
                                    $expiryText = empty($user['expiry_date']) 
                                        ? '<span class="text-red-500">Süresiz</span>' 
                                        : date('d.m.Y H:i', strtotime($user['expiry_date']));
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo $user['user_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($user['reason']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('d.m.Y H:i', strtotime($user['ban_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo $expiryText; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($user['banned_by_username'] ?? 'Sistem'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form method="post" class="inline-block" onsubmit="return confirm('Bu kullanıcının banını kaldırmak istediğinizden emin misiniz?');">
                                    <input type="hidden" name="action" value="unban_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200">
                                        <i class="fas fa-unlock"></i> Banı Kaldır
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Banlı kullanıcı bulunamadı.
                            </td>
                        </tr>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Hata: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'ips'): ?>
    
    <!-- Banlı IP Adresleri -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Banlı IP Adresleri</h2>
            
            <button type="button" id="banIPBtn" class="btn-sm btn-primary">
                <i class="fas fa-plus"></i> Yeni IP Banla
            </button>
        </div>
        
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Adresi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ban Nedeni</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Süre Sonu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Banlayan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php
                        try {
                            $database = new Database();
                            $db = $database->pdo;
                            
                            $query = "SELECT i.*, u.username as banned_by_username
                                     FROM ip_bans i
                                     LEFT JOIN users u ON i.banned_by = u.id
                                     WHERE i.is_active = 1
                                     ORDER BY i.ban_date DESC";
                            
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $bannedIPs = $stmt->fetchAll();
                            
                            if (count($bannedIPs) > 0):
                                foreach ($bannedIPs as $ip):
                                    // Süre sonu formatı
                                    $expiryText = empty($ip['expiry_date']) 
                                        ? '<span class="text-red-500">Süresiz</span>' 
                                        : date('d.m.Y H:i', strtotime($ip['expiry_date']));
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo $ip['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($ip['ip_address']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($ip['reason']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('d.m.Y H:i', strtotime($ip['ban_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo $expiryText; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($ip['banned_by_username'] ?? 'Sistem'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form method="post" class="inline-block" onsubmit="return confirm('Bu IP adresinin banını kaldırmak istediğinizden emin misiniz?');">
                                    <input type="hidden" name="action" value="unban_ip">
                                    <input type="hidden" name="ip_id" value="<?php echo $ip['id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200">
                                        <i class="fas fa-unlock"></i> Banı Kaldır
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Banlı IP adresi bulunamadı.
                            </td>
                        </tr>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Hata: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php elseif ($activeTab === 'attempts'): ?>
    
    <!-- Başarısız Giriş Denemeleri -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Son Başarısız Giriş Denemeleri</h2>
        </div>
        
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Adresi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kullanıcı Adı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php
                        try {
                            $database = new Database();
                            $db = $database->pdo;
                            
                            // Son 50 başarısız deneme
                            $query = "SELECT * FROM login_attempts 
                                     WHERE success = 0
                                     ORDER BY attempt_time DESC 
                                     LIMIT 50";
                            
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $attempts = $stmt->fetchAll();
                            
                            if (count($attempts) > 0):
                                foreach ($attempts as $attempt):
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('d.m.Y H:i:s', strtotime($attempt['attempt_time'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($attempt['ip_address']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($attempt['username']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Başarısız
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button" class="ban-ip-btn text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" data-ip="<?php echo htmlspecialchars($attempt['ip_address']); ?>">
                                    <i class="fas fa-ban"></i> IP'yi Banla
                                </button>
                            </td>
                        </tr>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Başarısız giriş denemesi bulunamadı.
                            </td>
                        </tr>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Hata: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Başarısız Kayıt Denemeleri -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Son Başarısız Kayıt Denemeleri</h2>
        </div>
        
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Adresi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kullanıcı Adı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">E-posta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php
                        try {
                            // Son 50 başarısız kayıt denemesi
                            $query = "SELECT * FROM registration_attempts 
                                     WHERE success = 0
                                     ORDER BY attempt_time DESC 
                                     LIMIT 50";
                            
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $attempts = $stmt->fetchAll();
                            
                            if (count($attempts) > 0):
                                foreach ($attempts as $attempt):
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('d.m.Y H:i:s', strtotime($attempt['attempt_time'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($attempt['ip_address']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($attempt['username']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($attempt['email']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                <?php echo htmlspecialchars(substr($attempt['user_agent'], 0, 50) . (strlen($attempt['user_agent']) > 50 ? '...' : '')); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button" class="ban-ip-btn text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" data-ip="<?php echo htmlspecialchars($attempt['ip_address']); ?>">
                                    <i class="fas fa-ban"></i> IP'yi Banla
                                </button>
                            </td>
                        </tr>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Başarısız kayıt denemesi bulunamadı.
                            </td>
                        </tr>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Hata: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<!-- IP Ban Modal -->
<div id="ipBanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">IP Adresini Banla</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" id="closeIpBanModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="ipBanForm" action="/api/ban-ip.php" method="post">
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <div class="mb-4">
                <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP Adresi</label>
                <input type="text" id="ip_address" name="ip_address" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>
            
            <div class="mb-4">
                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ban Nedeni</label>
                <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></textarea>
            </div>
            
            <div class="mb-4">
                <label for="ban_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ban Türü</label>
                <select id="ban_type" name="ban_type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="permanent">Süresiz</option>
                    <option value="temporary">Süreli</option>
                </select>
            </div>
            
            <div id="expiryDateContainer" class="mb-4 hidden">
                <label for="expiry_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Süre Sonu</label>
                <input type="datetime-local" id="expiry_date" name="expiry_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelIpBan" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    İptal
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    IP'yi Banla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // IP Ban Modal açma/kapama işlemleri
    const ipBanModal = document.getElementById('ipBanModal');
    const banIPBtn = document.getElementById('banIPBtn');
    const closeIpBanModal = document.getElementById('closeIpBanModal');
    const cancelIpBan = document.getElementById('cancelIpBan');
    
    // Ban türü değişimi
    const banType = document.getElementById('ban_type');
    const expiryDateContainer = document.getElementById('expiryDateContainer');
    
    // IP ban butonu tıklandığında
    if (banIPBtn) {
        banIPBtn.addEventListener('click', function() {
            document.getElementById('ip_address').value = '';
            document.getElementById('reason').value = '';
            ipBanModal.classList.remove('hidden');
        });
    }
    
    // Ban IP butonları
    const banIpBtns = document.querySelectorAll('.ban-ip-btn');
    banIpBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            document.getElementById('ip_address').value = ip;
            document.getElementById('reason').value = 'Şüpheli aktivite';
            ipBanModal.classList.remove('hidden');
        });
    });
    
    // Modal kapatma
    if (closeIpBanModal) {
        closeIpBanModal.addEventListener('click', function() {
            ipBanModal.classList.add('hidden');
        });
    }
    
    if (cancelIpBan) {
        cancelIpBan.addEventListener('click', function() {
            ipBanModal.classList.add('hidden');
        });
    }
    
    // Ban türü değiştiğinde
    if (banType) {
        banType.addEventListener('change', function() {
            if (this.value === 'temporary') {
                expiryDateContainer.classList.remove('hidden');
                // Varsayılan olarak 24 saat sonrasını ayarla
                const now = new Date();
                now.setHours(now.getHours() + 24);
                document.getElementById('expiry_date').value = now.toISOString().slice(0, 16);
            } else {
                expiryDateContainer.classList.add('hidden');
            }
        });
    }
    
    // IP Ban formu gönderildiğinde
    const ipBanForm = document.getElementById('ipBanForm');
    if (ipBanForm) {
        ipBanForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api/ban-ip.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('IP adresi başarıyla banlandı.');
                    window.location.reload();
                } else {
                    alert('Hata: ' + (result.message || 'Bilinmeyen bir hata oluştu.'));
                }
            } catch (error) {
                alert('İstek gönderilirken bir hata oluştu: ' + error.message);
            }
        });
    }
});
</script>
