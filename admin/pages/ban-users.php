<?php
/**
 * Admin paneli - Üye Banlama Sayfası
 */

// Base path tanımı
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__DIR__)));
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ban_functions.php';

// Veritabanı bağlantısı kontrolü
try {
    if (!isset($db) || !$db) {
        $database = new Database();
        $db = $database->pdo;
    }
    
    // URL'den gelen success mesajını al
    $success = isset($_GET['success']) ? $_GET['success'] : null;
    $error = isset($_GET['error']) ? $_GET['error'] : null;
    
    // Ban işlemleri
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ban_user':
                $userId = intval($_POST['user_id']);
                $reason = $_POST['reason'] ?? '';
                $duration = $_POST['duration'] ?? null;
                $expiryDate = null;
                
                // Süresiz ban veya belirli tarihli ban
                if ($duration !== 'permanent') {
                    // Yeni formatta date-time değerini işle
                    if (strpos($duration, ' ') !== false) {
                        // Format: "2025-07-28 14:30" -> Doğrudan datetime olarak kullan
                        $expiryDate = date('Y-m-d H:i:s', strtotime($duration));
                    } else {
                        // Eski format (geriye dönük uyumluluk için)
                        $expiryDate = date('Y-m-d H:i:s', strtotime('+' . $duration));
                    }
                }
                
                // Mevcut kullanıcının kendisini banlamasını engelle
                if ($userId == getCurrentUser()['id']) {
                    $error = "Kendinizi banlayamazsınız.";
                    break;
                }
                
                // Admin kullanıcıların banlanmasını engelle
                $userCheck = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
                $userCheck->execute([$userId]);
                $userToBan = $userCheck->fetch();
                
                if ($userToBan && $userToBan['is_admin']) {
                    $error = "Admin kullanıcılar banlanamaz.";
                    break;
                }
                
                // Ban işlemini gerçekleştir
                try {
                    $currentAdmin = getCurrentUser()['id'];
                    
                    // banUser fonksiyonu yoksa veya hata verirse, direkt veritabanına ekleme yapalım
                    if (!function_exists('banUser')) {
                        // Tablodaki mevcut alanları kontrol et
                        $checkTable = $db->prepare("SHOW COLUMNS FROM banned_users");
                        $checkTable->execute();
                        $columns = $checkTable->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (in_array('created_at', $columns)) {
                            $insertBan = $db->prepare("INSERT INTO banned_users (user_id, reason, expiry_date, banned_by, created_at, is_active) VALUES (?, ?, ?, ?, NOW(), 1)");
                            $result = $insertBan->execute([$userId, $reason, $expiryDate, $currentAdmin]);
                        } else {
                            $insertBan = $db->prepare("INSERT INTO banned_users (user_id, reason, expiry_date, banned_by, is_active) VALUES (?, ?, ?, ?, 1)");
                            $result = $insertBan->execute([$userId, $reason, $expiryDate, $currentAdmin]);
                        }
                    } else {
                        $result = banUser($userId, $reason, $expiryDate, $currentAdmin);
                    }
                    
                    if ($result) {
                        $success = "Kullanıcı başarıyla banlandı.";
                    } else {
                        $error = "Kullanıcı banlanırken bir hata oluştu.";
                    }
                } catch (Exception $e) {
                    $error = "Ban işlemi sırasında hata: " . $e->getMessage();
                }
                break;
        }
    }
    
    // Üye arama
    $search = $_GET['search'] ?? '';
    
    // Kullanıcıları getir
    $userQuery = "SELECT id, username, email, created_at
                FROM users 
                WHERE is_admin = 0";
                
    if (!empty($search)) {
        $userQuery .= " AND (username LIKE :search_username OR email LIKE :search_email)";
    }
    
    $userQuery .= " ORDER BY username ASC";
    
    $userStmt = $db->prepare($userQuery);
    
    if (!empty($search)) {
        $searchParam = "%{$search}%";
        $userStmt->bindParam(':search_username', $searchParam);
        $userStmt->bindParam(':search_email', $searchParam);
    }
    
    $userStmt->execute();
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Banlı kullanıcılar listesi kaldırıldı
    
} catch (Exception $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Üye Banla</h2>
                <p class="text-gray-600 dark:text-gray-400">Burada siteye erişmesini istemediğiniz kullanıcıları yasaklayabilirsiniz. Yasaklanan kullanıcılar siteye giriş yapamaz ve içerik ekleyemezler.</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Sol Kolon: Üye Arama ve Banlama -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kullanıcı Yasakla</h2>
                
                <!-- Kullanıcı Arama Formu -->
                <form action="" method="GET" class="mb-6">
                    <div class="flex gap-4">
                        <div class="flex-grow">
                            <input type="hidden" name="page" value="ban-users">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                   placeholder="Kullanıcı adı, email veya isim ara..." 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Ara</button>
                    </div>
                </form>
                
                <!-- Kullanıcı Listesi (Arama Sonuçları) -->
                <?php if (!empty($search)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kullanıcı Adı</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if (isset($users) && count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php 
                                        $isBanned = isUserBanned($user['id']);
                                        if ($isBanned) continue; // Zaten banlı kullanıcıları gösterme
                                    ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="openBanModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                <i class="fas fa-ban mr-1"></i> Yasakla
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-search text-4xl mb-4"></i>
                                        <p>Hiç kullanıcı bulunamadı.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sağ Kolon: Kullanıcı Seçme Dropdown -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kullanıcı Seçin</h2>
                
                <?php
                // Tüm aktif kullanıcıları getir (banlanmamış için filtre ekle)
                try {
                    // Kullanıcıları getir - status sütunu olmadığı için kaldırıldı
                    $allUsersQuery = "SELECT u.id, u.username, u.email, u.created_at,
                                  (SELECT COUNT(*) FROM banned_users WHERE user_id = u.id AND is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())) as is_banned
                                  FROM users u
                                  WHERE u.is_admin = 0
                                  ORDER BY u.username ASC";
                                  
                    $allUsersStmt = $db->prepare($allUsersQuery);
                    $allUsersStmt->execute();
                    $allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    echo '<div class="p-4 text-red-600 dark:text-red-400 text-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Kullanıcılar yüklenirken hata oluştu: ' . htmlspecialchars($e->getMessage()) . '
                        </div>';
                    $allUsers = [];
                }
                ?>
                
                <!-- Kullanıcı Seçim Dropdown -->
                <div class="relative">
                    <select id="userSelect" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm dark:bg-gray-700 dark:text-white appearance-none cursor-pointer">
                        <option value="">Kullanıcı Seçin</option>
                        <?php if (isset($allUsers) && count($allUsers) > 0): ?>
                            <?php foreach ($allUsers as $user): ?>
                                <?php if (!$user['is_banned']): // Sadece banlı olmayan kullanıcıları göster ?>
                                    <option value="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                        <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-500"></i>
                    </div>
                </div>
                
                <!-- Seçilen Kullanıcı Bilgileri -->
                <div id="selectedUserInfo" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-md shadow-sm hidden">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Seçili Kullanıcı</h3>
                    <div class="grid grid-cols-1 gap-2">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Kullanıcı Adı:</span>
                            <span id="selectedUsername" class="font-medium ml-2 text-gray-900 dark:text-white"></span>
                        </div>
                        <div class="mt-4">
                            <button id="selectedUserBanButton" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md w-full transition-colors">
                                <i class="fas fa-ban mr-1"></i> Bu Kullanıcıyı Yasakla
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<!-- Ban Modal -->
<div id="banUserModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kullanıcı Yasakla</h3>
            <button onclick="closeBanModal()" class="text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="banUserForm">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="user_id" id="banUserId">
            
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kullanıcı:</label>
                <div id="banUsername" class="text-gray-900 dark:text-white font-medium"></div>
            </div>
            
            <div class="mb-4">
                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ban Sebebi:</label>
                <textarea name="reason" id="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required></textarea>
            </div>
            
            <div class="mb-4">
                <label for="duration_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ban Süresi:</label>
                
                <div class="grid grid-cols-1 gap-3">
                    <!-- Süresiz Ban Seçeneği -->
                    <div class="flex items-center">
                        <input type="checkbox" id="permanent_ban" name="permanent_ban" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-blue-400 h-5 w-5">
                        <label for="permanent_ban" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Süresiz Ban</label>
                    </div>
                    
                    <!-- Tarih ve Saat Seçici -->
                    <div id="duration_datetime_container">
                        <label for="duration_date" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Ban Bitiş Zamanı:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" id="duration_date" name="duration_date" 
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm w-full"
                                min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            <input type="time" id="duration_time" name="duration_time" 
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm w-full"
                                value="<?php echo date('H:i'); ?>">
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ban işlemi belirtilen zamana kadar sürecektir.</p>
                    </div>
                </div>
                
                <!-- Gizli input - JavaScript ile doldurulacak -->
                <input type="hidden" name="duration" id="duration" value="">
            </div>
            
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeBanModal()" class="px-4 py-2 bg-gray-300 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 transition-colors">
                    İptal
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 transition-colors">
                    <i class="fas fa-ban mr-1"></i> Yasakla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openBanModal(userId, username) {
    document.getElementById('banUserId').value = userId;
    document.getElementById('banUsername').textContent = username;
    document.getElementById('banUserModal').classList.remove('hidden');
}

function closeBanModal() {
    document.getElementById('banUserModal').classList.add('hidden');
    document.getElementById('reason').value = '';
    document.getElementById('permanent_ban').checked = false;
    document.getElementById('duration_date').value = '';
    document.getElementById('duration_time').value = '';
    toggleDurationFields(false);
}

// ESC tuşu ile modal'ı kapatma
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeBanModal();
    }
});

// Modal dışına tıklandığında kapatma
document.getElementById('banUserModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeBanModal();
    }
});

// Ban süresi alanları arasındaki geçişleri yönet
function toggleDurationFields(isPermanent) {
    const durationDatetimeContainer = document.getElementById('duration_datetime_container');
    
    if (isPermanent) {
        durationDatetimeContainer.classList.add('opacity-50', 'pointer-events-none');
        document.getElementById('duration').value = 'permanent';
    } else {
        durationDatetimeContainer.classList.remove('opacity-50', 'pointer-events-none');
        updateDurationValue();
    }
}

// Seçilen tarih ve saat değerlerini hidden input'a aktar
function updateDurationValue() {
    const dateInput = document.getElementById('duration_date');
    const timeInput = document.getElementById('duration_time');
    const permanentCheckbox = document.getElementById('permanent_ban');
    
    if (permanentCheckbox.checked) {
        document.getElementById('duration').value = 'permanent';
        return;
    }
    
    // Eğer tarih veya saat boşsa, değeri güncelleme
    if (!dateInput.value || !timeInput.value) {
        return;
    }
    
    // Manuel girilen zaman formatını kontrol et ve düzelt
    let timeValue = timeInput.value;
    // Eğer : içermiyorsa ve 4 haneli ise (örn: 2230), otomatik olarak format ekleyelim
    if (!timeValue.includes(':') && timeValue.length === 4) {
        timeValue = timeValue.substring(0, 2) + ':' + timeValue.substring(2);
        timeInput.value = timeValue;
    }
    
    // Tarih ve saati birleştirerek ISO formatında sunucuya gönder
    // Girilen değerin geçerli bir tarih-saat olup olmadığını kontrol et
    try {
        const selectedDate = new Date(`${dateInput.value}T${timeValue}`);
        const now = new Date();
        
        // Eğer geçersiz bir tarih ise (Invalid Date)
        if (isNaN(selectedDate.getTime())) {
            // Şu anki saatten 1 saat sonrasını ayarla
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const nextHour = (currentHour + 1) % 24;
            timeInput.value = nextHour.toString().padStart(2, '0') + ':' + currentMinute.toString().padStart(2, '0');
            return;
        }
        
        // Bugünün tarihi seçilmişse sadece saati kontrol et, farklı bir gün seçilmişse direkt kabul et
        const todayStr = now.toISOString().split('T')[0];
        const isSameDay = dateInput.value === todayStr;
        
        // Sadece aynı gün seçildiyse ve saat geçmiş ise, uyarı vermeden düzelt
        if (isSameDay && selectedDate < now) {
            // Şu anki saati ekleyelim
            const currentHour = now.getHours().toString().padStart(2, '0');
            const currentMinute = now.getMinutes().toString().padStart(2, '0');
            
            // Varsayılan olarak şu anki saati ayarla
            timeInput.value = `${currentHour}:${currentMinute}`;
            return;
        }
    } catch(e) {
        console.error("Tarih-saat değeri işlenirken hata: ", e);
        return;
    }
    
    // Farklı formata çevirip duration alanına yazalım
    document.getElementById('duration').value = `${dateInput.value} ${timeInput.value}`;
}

// Başarılı ban işleminden sonra sayfayı yenile
document.getElementById('banUserForm').addEventListener('submit', function(event) {
    // Eğer süresiz değilse ve tarih/saat seçilmemişse
    const permanentBan = document.getElementById('permanent_ban');
    const dateInput = document.getElementById('duration_date');
    const timeInput = document.getElementById('duration_time');
    
    if (!permanentBan.checked && (!dateInput.value || !timeInput.value)) {
        event.preventDefault();
        alert('Lütfen bir ban bitiş tarihi ve saati seçin veya süresiz ban işaretleyin.');
        return;
    }
    
    // Form gönderilmeden önce bir kez daha manuel kontrol için
    if (!permanentBan.checked) {
        try {
            // Zaman formatını kontrol et
            let timeValue = timeInput.value;
            if (!timeValue.includes(':') && timeValue.length === 4) {
                timeValue = timeValue.substring(0, 2) + ':' + timeValue.substring(2);
                timeInput.value = timeValue;
            }
            
            const selectedDate = new Date(`${dateInput.value}T${timeInput.value}`);
            const now = new Date();
            
            // Bugünün tarihi seçilmişse ve saat geçmişte kaldıysa otomatik düzelt
            if (dateInput.value === now.toISOString().split('T')[0] && selectedDate < now) {
                const currentHour = now.getHours().toString().padStart(2, '0');
                const currentMinute = now.getMinutes().toString().padStart(2, '0');
                timeInput.value = `${currentHour}:${currentMinute}`;
                updateDurationValue();
                return;
            }
        } catch(e) {
            event.preventDefault();
            alert('Geçersiz tarih veya saat formatı. Lütfen kontrol ediniz.');
            return;
        }
    }
    
    // Form gönderilmeden önce duration değerini güncelle
    updateDurationValue();
    
    // Form normal şekilde submit edilsin, başarılı olunca sayfayı yenileyecek
    localStorage.setItem('banSuccess', 'true');
});

// Sayfaya ilk girişte kullanıcı tablosunu düzenle
document.addEventListener('DOMContentLoaded', function() {
    // Ban süresi seçim alanlarını ayarla
    const permanentBanCheckbox = document.getElementById('permanent_ban');
    const dateInput = document.getElementById('duration_date');
    const timeInput = document.getElementById('duration_time');
    
    // Bugünün tarihini varsayılan olarak ayarla
    const today = new Date();
    
    // Şu anki tarih ve saat ayarları
    dateInput.value = today.toISOString().split('T')[0];
    
    // Şu anki saati varsayılan olarak ayarla
    const hours = today.getHours();
    const minutes = today.getMinutes();
    
    timeInput.value = hours.toString().padStart(2, '0') + ':' + minutes.toString().padStart(2, '0');
    
    // Süresiz ban seçeneği değiştiğinde
    permanentBanCheckbox.addEventListener('change', function() {
        toggleDurationFields(this.checked);
    });
    
    // Tarih veya saat değiştiğinde (hem change hem input olayları için)
    dateInput.addEventListener('change', updateDurationValue);
    dateInput.addEventListener('input', updateDurationValue);
    
    timeInput.addEventListener('change', updateDurationValue);
    timeInput.addEventListener('input', updateDurationValue);
    timeInput.addEventListener('blur', updateDurationValue); // Odak kaybedildiğinde
    
    // İlk yüklemede duration değerini ayarla
    updateDurationValue();
    
    // Kullanıcı dropdown işlevselliği
    const userSelect = document.getElementById('userSelect');
    const selectedUserInfo = document.getElementById('selectedUserInfo');
    const selectedUsername = document.getElementById('selectedUsername');
    const selectedUserBanButton = document.getElementById('selectedUserBanButton');

    if (userSelect) {
        // Dropdown değişikliğinde
        userSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const userId = this.value;
            
            if (userId) {
                const username = selectedOption.getAttribute('data-username');
                
                // Seçilen kullanıcı bilgilerini göster
                selectedUsername.textContent = username;
                selectedUserInfo.classList.remove('hidden');
                
                // Ban butonuna tıklandığında ban modal'ını aç
                selectedUserBanButton.onclick = function() {
                    openBanModal(userId, username);
                };
            } else {
                // Seçilen kullanıcı bilgilerini gizle
                selectedUserInfo.classList.add('hidden');
            }
        });
    }
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const userTable = document.querySelector('.user-table tbody');
            if (!userTable) return;
            
            const rows = userTable.querySelectorAll('tr');
            
            rows.forEach(row => {
                const username = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                const email = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                
                if (username.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Zaten banlı kullanıcıları farklı renkte göster - bu artık otomatik olarak eklendiği için kaldırıldı
    // Sayfa yüklendiğinde, seçilen kullanıcı bilgilerini gizle
    document.addEventListener('DOMContentLoaded', function() {
        const selectedUserInfo = document.getElementById('selectedUserInfo');
        if (selectedUserInfo) {
            selectedUserInfo.classList.add('hidden');
        }
        
        // Ban işlemi başarılı olduysa ve localStorage'da işaret varsa
        if (localStorage.getItem('banSuccess') === 'true') {
            // İşareti temizle
            localStorage.removeItem('banSuccess');
            
            // Başarılı mesajını göster
            const successAlert = document.createElement('div');
            successAlert.className = 'bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4';
            successAlert.innerHTML = '<p class="font-bold">Başarılı!</p><p>Kullanıcı başarıyla yasaklandı.</p>';
            
            // Sayfanın başına ekle
            const contentContainer = document.querySelector('.content-container');
            if (contentContainer) {
                contentContainer.insertBefore(successAlert, contentContainer.firstChild);
                
                // 5 saniye sonra mesajı kaldır
                setTimeout(() => {
                    successAlert.remove();
                }, 5000);
            }
        }
    });
});
</script>
