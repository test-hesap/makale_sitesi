<?php
require_once __DIR__ . '/../../config/database.php';

// Abonelik işlemleri
if (isset($_POST['action'])) {
    $database = new Database();
    
    switch ($_POST['action']) {
        case 'add_plan':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $duration_days = intval($_POST['duration_days'] ?? 30);
            $features = sanitizeInput($_POST['features'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (!empty($name) && $price >= 0) {
                try {
                    $stmt = $database->query(
                        "INSERT INTO subscription_plans (name, description, price, duration_months, features, is_active) 
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$name, $description, $price, $duration_days/30, $features, $is_active]
                    );
                    $success = "Abonelik planı başarıyla eklendi.";
                } catch (Exception $e) {
                    $error = "Plan eklenirken bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $error = "Plan adı ve fiyat gereklidir.";
            }
            break;
            
        case 'edit_plan':
            $plan_id = intval($_POST['plan_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $duration_days = intval($_POST['duration_days'] ?? 30);
            $features = sanitizeInput($_POST['features'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($plan_id > 0 && !empty($name) && $price >= 0) {
                try {
                    $stmt = $database->query(
                        "UPDATE subscription_plans 
                         SET name = ?, description = ?, price = ?, 
                             duration_months = ?, features = ?, is_active = ?,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?",
                        [$name, $description, $price, $duration_days/30, $features, $is_active, $plan_id]
                    );
                    $success = "Abonelik planı başarıyla güncellendi.";
                } catch (Exception $e) {
                    $error = "Plan güncellenirken bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $error = "Plan adı ve fiyat gereklidir.";
            }
            break;
            
        case 'delete_plan':
            $plan_id = intval($_POST['plan_id'] ?? 0);
            
            if ($plan_id > 0) {
                try {
                    $stmt = $database->query(
                        "DELETE FROM subscription_plans WHERE id = ?",
                        [$plan_id]
                    );
                    $success = "Abonelik planı başarıyla silindi.";
                } catch (Exception $e) {
                    $error = "Plan silinirken bir hata oluştu: " . $e->getMessage();
                }
            } else {
                $error = "Geçersiz plan ID.";
            }
            break;
    }
}

// Demo abonelik planları
$demoPlans = [
    [
        'id' => 1,
        'name' => 'Temel Plan',
        'description' => 'Temel özellikler ile başlayın',
        'price' => 0.00,
        'duration_days' => 0,
        'features' => 'Ücretsiz makalelere erişim\nTemel yorum sistemi\nSınırlı içerik',
        'is_active' => 1,
        'subscriber_count' => 150,
        'created_at' => '2024-01-01 00:00:00'
    ],
    [
        'id' => 2,
        'name' => 'Premium Aylık',
        'description' => 'Tüm özelliklere aylık erişim',
        'price' => 29.99,
        'duration_days' => 30,
        'features' => 'Tüm premium makalelere erişim\nReklamsız deneyim\nÖncelikli destek\nPDF indirme',
        'is_active' => 1,
        'subscriber_count' => 85,
        'created_at' => '2024-01-01 00:00:00'
    ],
    [
        'id' => 3,
        'name' => 'Premium Yıllık',
        'description' => 'En avantajlı yıllık plan',
        'price' => 299.99,
        'duration_days' => 365,
        'features' => 'Tüm premium makalelere erişim\nReklamsız deneyim\nÖncelikli destek\nPDF indirme\n%17 indirim',
        'is_active' => 1,
        'subscriber_count' => 45,
        'created_at' => '2024-01-01 00:00:00'
    ]
];

// İstatistikler
$totalSubscribers = array_sum(array_column($demoPlans, 'subscriber_count'));
$activePlans = count(array_filter($demoPlans, fn($p) => $p['is_active']));
$monthlyRevenue = 29.99 * 85 + (299.99 / 12) * 45; // Tahmini aylık gelir

// Gerçek veritabanından planları çek
try {
    $database = new Database();
    
    // Her plan için abone sayısını hesapla
    $plans = $database->query("
        SELECT 
            p.*,
            COUNT(DISTINCT us.user_id) as subscriber_count
        FROM subscription_plans p
        LEFT JOIN user_subscriptions us ON us.plan_id = p.id 
            AND us.status = 'active' 
            AND us.end_date > NOW()
        GROUP BY p.id
        ORDER BY p.price ASC
    ")->fetchAll();

    // Toplam aktif abone sayısını hesapla
    $totalSubscribers = $database->query("
        SELECT COUNT(DISTINCT user_id) as total 
        FROM user_subscriptions 
        WHERE status = 'active' AND end_date > NOW()
    ")->fetch()['total'] ?? 0;

    // Aktif plan sayısını hesapla
    $activePlans = count(array_filter($plans, fn($p) => $p['is_active']));

    // Aylık geliri hesapla
    $monthlyRevenue = $database->query("
        SELECT COALESCE(SUM(
            CASE 
                WHEN p.duration_months >= 1 THEN p.price / p.duration_months
                ELSE p.price
            END
        ), 0) as monthly_revenue
        FROM user_subscriptions us
        JOIN subscription_plans p ON p.id = us.plan_id
        WHERE us.status = 'active' AND us.end_date > NOW()
    ")->fetch()['monthly_revenue'] ?? 0;

} catch (Exception $e) {
    // Hata durumunda demo planları göster
    $plans = $demoPlans;
    $totalSubscribers = array_sum(array_column($demoPlans, 'subscriber_count'));
    $activePlans = count(array_filter($demoPlans, fn($p) => $p['is_active']));
    $monthlyRevenue = 29.99 * 85 + (299.99 / 12) * 45;
}
?>

<!-- Başlık ve Butonlar -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Abonelik Planları</h2>
        <p class="text-gray-600 dark:text-gray-400">Premium planları yönetin</p>
    </div>
    <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors mt-4 sm:mt-0">
        <i class="fas fa-plus mr-2"></i>Yeni Plan
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
                <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Abone</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($totalSubscribers); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-crown text-green-600 dark:text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Plan</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $activePlans; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-lira-sign text-purple-600 dark:text-purple-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aylık Gelir</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($monthlyRevenue, 2); ?> ₺</p>
            </div>
        </div>
    </div>
</div>

<!-- Plan Kartları -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <?php foreach ($plans as $plan): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <?php echo htmlspecialchars($plan['name']); ?>
                </h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    <?php echo $plan['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo $plan['is_active'] ? 'Aktif' : 'Pasif'; ?>
                </span>
            </div>
            
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                <?php echo htmlspecialchars($plan['description']); ?>
            </p>
            
            <div class="mb-4">
                <span class="text-3xl font-bold text-gray-900 dark:text-white">
                    <?php if ($plan['price'] > 0): ?>
                        <?php echo number_format($plan['price'], 2); ?> ₺
                    <?php else: ?>
                        Ücretsiz
                    <?php endif; ?>
                </span>
                <?php if ($plan['duration_months'] > 0): ?>
                <span class="text-gray-500 dark:text-gray-400">
                    / <?php 
                    if ($plan['duration_months'] == 1) {
                        echo 'aylık';
                    } elseif ($plan['duration_months'] == 12) {
                        echo 'yıllık';
                    } else {
                        echo $plan['duration_months'] . ' ay';
                    }
                    ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Özellikler:</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <?php foreach (explode("\n", $plan['features']) as $feature): ?>
                    <li class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                        <?php echo htmlspecialchars(trim($feature)); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">
                        <i class="fas fa-users mr-1"></i>
                        <?php echo number_format($plan['subscriber_count']); ?> abone
                    </span>
                    <div class="flex space-x-2">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($plan, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Düzenle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deletePlan(<?php echo $plan['id']; ?>)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Sil">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Plan Ekleme Modal -->
<div id="planModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Yeni Abonelik Planı
                </h3>
            </div>
            
            <form method="POST" class="px-6 py-4">
                <input type="hidden" name="action" value="add_plan">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Plan Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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
                                Fiyat (₺) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="price" step="0.01" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Süre (Gün)
                            </label>
                            <input type="number" name="duration_days" min="1" value="30"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Özellikler (Her satırda bir özellik)
                        </label>
                        <textarea name="features" rows="4" placeholder="Tüm makalelere erişim&#10;Reklamsız deneyim&#10;Öncelikli destek"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
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
                    <button type="button" onclick="closeAddModal()" 
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

<!-- Plan Düzenleme Modal -->
<div id="editPlanModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Abonelik Planını Düzenle
                </h3>
            </div>
            
            <form method="POST" class="px-6 py-4">
                <input type="hidden" name="action" value="edit_plan">
                <input type="hidden" name="plan_id" id="edit_plan_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Plan Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="edit_name" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Açıklama
                        </label>
                        <textarea name="description" id="edit_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fiyat (₺) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="price" id="edit_price" step="0.01" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Süre (Gün)
                            </label>
                            <input type="number" name="duration_days" id="edit_duration_days" min="1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Özellikler (Her satırda bir özellik)
                        </label>
                        <textarea name="features" id="edit_features" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit_is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Plan aktif
                        </label>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition-colors">
                        İptal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Plan ekleme modalını aç
function openAddModal() {
    document.getElementById('planModal').classList.remove('hidden');
}

// Plan ekleme modalını kapat
function closeAddModal() {
    document.getElementById('planModal').classList.add('hidden');
}

// Plan düzenleme modalını aç
function openEditModal(plan) {
    const modal = document.getElementById('editPlanModal');
    
    // Form alanlarını doldur
    document.getElementById('edit_plan_id').value = plan.id;
    document.getElementById('edit_name').value = plan.name;
    document.getElementById('edit_description').value = plan.description;
    document.getElementById('edit_price').value = plan.price;
    document.getElementById('edit_duration_days').value = plan.duration_months * 30; // Ay -> Gün dönüşümü
    document.getElementById('edit_features').value = plan.features ? plan.features.replace(/\\n/g, '\n') : '';
    document.getElementById('edit_is_active').checked = parseInt(plan.is_active) === 1;
    
    // Modalı göster
    modal.classList.remove('hidden');
}

// Plan düzenleme modalını kapat
function closeEditModal() {
    document.getElementById('editPlanModal').classList.add('hidden');
}

// Plan silme işlemi
function deletePlan(planId) {
    if (confirm('Bu planı silmek istediğinizden emin misiniz?')) {
        // Form oluştur ve gönder
        const form = document.createElement('form');
        form.method = 'POST';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_plan';
        
        const planIdInput = document.createElement('input');
        planIdInput.type = 'hidden';
        planIdInput.name = 'plan_id';
        planIdInput.value = planId;
        
        form.appendChild(actionInput);
        form.appendChild(planIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// ESC tuşu ile modalları kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddModal();
        closeEditModal();
    }
});

// Modal dışına tıklayınca kapat
window.onclick = function(e) {
    const addModal = document.getElementById('planModal');
    const editModal = document.getElementById('editPlanModal');
    
    if (e.target === addModal) {
        closeAddModal();
    }
    if (e.target === editModal) {
        closeEditModal();
    }
}
</script> 