<?php
// Veritabanı bağlantısını al
global $db;

// Promosyon kodu işlemleri
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_promo':
            $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
            $description = sanitizeInput($_POST['description'] ?? '');
            $discount_type = sanitizeInput($_POST['discount_type'] ?? 'percent');
            $discount_value = floatval($_POST['discount_value'] ?? 0);
            $max_uses = intval($_POST['max_uses'] ?? 0);
            $expires_at = sanitizeInput($_POST['expires_at'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (!empty($code) && $discount_value > 0) {
                try {
                    // Kod zaten var mı kontrol et
                    $checkStmt = $db->prepare("SELECT id FROM promo_codes WHERE code = ?");
                    $checkStmt->execute([$code]);
                    
                    if ($checkStmt->rowCount() > 0) {
                        $error = "Bu promosyon kodu zaten mevcut.";
                    } else {
                        // Yeni promosyon kodu ekle
                        $insertStmt = $db->prepare("INSERT INTO promo_codes (code, description, discount_type, discount_value, max_uses, expires_at, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        if ($insertStmt->execute([$code, $description, $discount_type, $discount_value, $max_uses ?: null, $expires_at ?: null, $is_active])) {
                            $success = "Promosyon kodu başarıyla eklendi.";
                        } else {
                            $error = "Promosyon kodu eklenirken bir hata oluştu.";
                        }
                    }
                } catch (Exception $e) {
                    $error = "Veritabanı hatası: " . $e->getMessage();
                }
            } else {
                $error = "Kod ve indirim miktarı gereklidir.";
            }
            break;
            
        case 'toggle_status':
            $id = intval($_POST['promo_id']);
            if ($id > 0) {
                try {
                    // Mevcut durumu al ve tersine çevir
                    $getStmt = $db->prepare("SELECT is_active FROM promo_codes WHERE id = ?");
                    $getStmt->execute([$id]);
                    $currentStatus = $getStmt->fetchColumn();
                    
                    if ($currentStatus !== false) {
                        $newStatus = $currentStatus ? 0 : 1;
                        $updateStmt = $db->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
                        if ($updateStmt->execute([$newStatus, $id])) {
                            $success = "Promosyon kodu durumu güncellendi.";
                        } else {
                            $error = "Durum güncellenirken bir hata oluştu.";
                        }
                    } else {
                        $error = "Promosyon kodu bulunamadı.";
                    }
                } catch (Exception $e) {
                    $error = "Veritabanı hatası: " . $e->getMessage();
                }
            } else {
                $error = "Geçersiz promosyon kodu ID'si.";
            }
            break;
            
        case 'delete':
            $id = intval($_POST['promo_id']);
            if ($id > 0) {
                try {
                    // Gerçek sistemde promo_codes tablosundan sil
                    $deleteStmt = $db->prepare("DELETE FROM promo_codes WHERE id = ?");
                    if ($deleteStmt->execute([$id])) {
                        $success = "Promosyon kodu başarıyla silindi.";
                    } else {
                        $error = "Promosyon kodu silinirken bir hata oluştu.";
                    }
                } catch (Exception $e) {
                    $error = "Veritabanı hatası: " . $e->getMessage();
                }
            } else {
                $error = "Geçersiz promosyon kodu ID'si.";
            }
            break;
    }
}

// Promosyon kodlarını veritabanından çek
$promoCodes = [];
try {
    // Önce promo_codes tablosu var mı kontrol et
    $tableCheck = $db->query("SHOW TABLES LIKE 'promo_codes'");
    if ($tableCheck->rowCount() == 0) {
        // Tablo yoksa oluştur
        $createTable = "CREATE TABLE IF NOT EXISTS promo_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            discount_type ENUM('percent', 'fixed') DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL,
            max_uses INT DEFAULT NULL,
            used_count INT DEFAULT 0,
            expires_at DATE DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($createTable);
        
        // Demo veriler ekle
        $insertDemo = "INSERT INTO promo_codes (code, description, discount_type, discount_value, max_uses, used_count, expires_at, is_active) VALUES
            ('WELCOME50', 'Yeni üyelere özel %50 indirim', 'percent', 50, 100, 23, '2025-12-31', 1),
            ('STUDENT20', 'Öğrencilere özel indirim', 'percent', 20, 50, 12, '2025-06-30', 1),
            ('FLASH10', 'Flash kampanya 10₺ indirim', 'fixed', 10, 25, 25, '2025-02-15', 0)";
        $db->exec($insertDemo);
    }
    
    // Promosyon kodlarını çek
    $stmt = $db->prepare("SELECT * FROM promo_codes ORDER BY created_at DESC");
    $stmt->execute();
    $promoCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Hata durumunda demo kodları kullan
    $promoCodes = [
        [
            'id' => 1,
            'code' => 'WELCOME50',
            'description' => 'Yeni üyelere özel %50 indirim',
            'discount_type' => 'percent',
            'discount_value' => 50,
            'max_uses' => 100,
            'used_count' => 23,
            'expires_at' => '2025-12-31',
            'is_active' => 1,
            'created_at' => '2025-01-01 00:00:00'
        ]
    ];
}

// Demo promosyon kodları (geriye uyumluluk için)
$demoCodes = $promoCodes;

// İstatistikler
$totalCodes = count($demoCodes);
$activeCodes = count(array_filter($demoCodes, fn($c) => $c['is_active']));
$totalUses = array_sum(array_column($demoCodes, 'used_count'));
?>

<!-- Başlık ve Butonlar -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Promosyon Kodları</h2>
        <p class="text-gray-600 dark:text-gray-400">İndirim kodlarını yönetin</p>
    </div>
    <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors mt-4 sm:mt-0">
        <i class="fas fa-plus mr-2"></i>Yeni Kod
    </button>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<!-- İstatistik Kartları -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-ticket-alt text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kod</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $totalCodes; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Kod</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $activeCodes; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-chart-line text-purple-600 dark:text-purple-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kullanım</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $totalUses; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Kod Listesi -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kod</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Açıklama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İndirim</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kullanım</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Son Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($demoCodes as $code): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-6 py-4">
                        <div class="font-mono text-sm font-medium text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                            <?php echo htmlspecialchars($code['code']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <?php echo htmlspecialchars($code['description']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                        <?php if ($code['discount_type'] === 'percent'): ?>
                            %<?php echo $code['discount_value']; ?>
                        <?php else: ?>
                            <?php echo $code['discount_value']; ?> ₺
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                        <?php echo $code['used_count']; ?> / <?php echo $code['max_uses'] ?: '∞'; ?>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <?php 
                            $percentage = $code['max_uses'] > 0 ? ($code['used_count'] / $code['max_uses']) * 100 : 0;
                            $color = $percentage >= 80 ? 'bg-red-500' : ($percentage >= 60 ? 'bg-yellow-500' : 'bg-green-500');
                            ?>
                            <div class="<?php echo $color; ?> h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <?php 
                        $expiry = strtotime($code['expires_at']);
                        $isExpired = $expiry < time();
                        ?>
                        <span class="<?php echo $isExpired ? 'text-red-600 dark:text-red-400' : ''; ?>">
                            <?php echo date('d.m.Y', $expiry); ?>
                        </span>
                        <?php if ($isExpired): ?>
                            <br><small class="text-red-500">Süresi doldu</small>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $isExpired = strtotime($code['expires_at']) < time();
                        $isMaxedOut = $code['max_uses'] > 0 && $code['used_count'] >= $code['max_uses'];
                        $canUse = $code['is_active'] && !$isExpired && !$isMaxedOut;
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php echo $canUse ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                            <?php echo $canUse ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="promo_id" value="<?php echo $code['id']; ?>">
                                <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Durumu Değiştir">
                                    <i class="fas fa-toggle-<?php echo $code['is_active'] ? 'on' : 'off'; ?>"></i>
                                </button>
                            </form>
                            <button class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirmDelete('<?php echo htmlspecialchars($code['code']); ?>')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="promo_id" value="<?php echo $code['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Kod Ekleme Modal -->
<div id="promoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Yeni Promosyon Kodu
                </h3>
            </div>
            
            <form method="POST" class="px-6 py-4">
                <input type="hidden" name="action" value="add_promo">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kod <span class="text-red-500">*</span>
                        </label>
                        <div class="flex space-x-2">
                            <input type="text" name="code" id="promoCode" required
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono uppercase">
                            <button type="button" onclick="generateCode()" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg">
                                <i class="fas fa-random"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Açıklama
                        </label>
                        <textarea name="description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                İndirim Türü
                            </label>
                            <select name="discount_type" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="percent">Yüzde (%)</option>
                                <option value="fixed">Sabit Tutar (₺)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                İndirim Miktarı <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="discount_value" step="0.01" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maksimum Kullanım
                            </label>
                            <input type="number" name="max_uses" min="0" placeholder="Sınırsız için boş bırakın"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Son Kullanma Tarihi
                            </label>
                            <input type="date" name="expires_at"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        İptal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('promoModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('promoModal').classList.add('hidden');
}

function generateCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('promoCode').value = code;
}

function confirmDelete(promoCode) {
    return confirm('Bu promosyon kodunu silmek istediğinizden emin misiniz?\n\nKod: ' + promoCode + '\n\nBu işlem geri alınamaz!');
}

// Modal dışına tıklandığında kapat
document.getElementById('promoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Sayfa yüklendikten sonra başarı/hata mesajlarını otomatik gizle
setTimeout(function() {
    var successAlert = document.querySelector('.bg-green-100');
    var errorAlert = document.querySelector('.bg-red-100');
    
    if (successAlert) {
        successAlert.style.opacity = '0';
        setTimeout(() => successAlert.remove(), 300);
    }
    
    if (errorAlert) {
        errorAlert.style.opacity = '0';
        setTimeout(() => errorAlert.remove(), 300);
    }
}, 5000);
</script> 