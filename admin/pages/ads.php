<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Admin kontrolü
if (!isAdmin()) {
    header('Location: /');
    exit;
}

$db = new Database();
$success = '';
$error = '';

// Reklam işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $adId = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $name = trim($_POST['name']);
                $code = trim($_POST['code']);
                $position = $_POST['position'];
                $type = $_POST['type'];
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name) || empty($code)) {
                    $error = "Reklam adı ve HTML içerik gereklidir.";
                } else {
                    try {
                        if ($_POST['action'] === 'edit' && $adId > 0) {
                            // Güncelleme
                            $stmt = $db->prepare("
                                UPDATE ads 
                                SET name = ?, code = ?, position = ?, type = ?, is_active = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([$name, $code, $position, $type, $isActive, $adId]);
                            $success = "Reklam başarıyla güncellendi.";
                        } else {
                            // Yeni ekleme
                            $stmt = $db->prepare("
                                INSERT INTO ads (name, code, position, type, is_active)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$name, $code, $position, $type, $isActive]);
                            $success = "Reklam başarıyla eklendi.";
                        }
                    } catch (Exception $e) {
                        $error = "Bir hata oluştu: " . $e->getMessage();
                    }
                }
                break;

            case 'delete':
                try {
                    $stmt = $db->prepare("DELETE FROM ads WHERE id = ?");
                    $stmt->execute([$adId]);
                    $success = "Reklam başarıyla silindi.";
                } catch (Exception $e) {
                    $error = "Silme işlemi başarısız: " . $e->getMessage();
                }
                break;

            case 'toggle_status':
                try {
                    $stmt = $db->prepare("UPDATE ads SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$adId]);
                    $success = "Reklam durumu güncellendi.";
                } catch (Exception $e) {
                    $error = "Durum güncelleme başarısız: " . $e->getMessage();
                }
                break;
        }
    }
}

// Reklamları listele
try {
    $stmt = $db->prepare("
        SELECT a.*, COALESCE(s.impressions, 0) as impressions, COALESCE(s.clicks, 0) as clicks,
        CASE 
            WHEN COALESCE(s.impressions, 0) > 0 
            THEN ROUND((COALESCE(s.clicks, 0) / COALESCE(s.impressions, 0)) * 100, 2)
            ELSE 0 
        END as ctr
        FROM ads a
        LEFT JOIN ad_statistics s ON a.id = s.ad_id
        ORDER BY a.position
    ");
    $stmt->execute();
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Reklamlar yüklenirken bir hata oluştu: " . $e->getMessage();
    $ads = [];
}

// İstatistikler
$totalAds = count($ads);
$activeAds = count(array_filter($ads, fn($a) => $a['is_active']));

// Konum ve tür seçenekleri
$positions = [
    'header' => 'Üst (Header)',
    'sidebar' => 'Sidebar Üst',
    'sidebar_bottom' => 'Sidebar Alt',
    'content_top' => 'İçerik Üstü',
    'content_middle' => 'İçerik Ortası',
    'content_bottom' => 'İçerik Altı',
    'mobile_fixed_bottom' => 'Mobil Sabit Alt',
    'footer' => 'Alt (Footer)'
];

$types = [
    'banner' => 'Banner',
    'widget' => 'Widget',
    'popup' => 'Popup',
    'text' => 'Metin'
];
?>

<!-- Başlık -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Reklamlar</h1>
    <button onclick="openAdModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center gap-2">
        <i class="fas fa-plus"></i>
        <span>Yeni Reklam Ekle</span>
    </button>
</div>

<?php if ($success): ?>
<div class="bg-green-50 text-green-800 rounded-lg p-4 mb-6">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 text-red-800 rounded-lg p-4 mb-6">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<!-- Reklam Listesi -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="text-left p-4">REKLAM</th>
                    <th class="text-left p-4">KONUM</th>
                    <th class="text-left p-4">TÜR</th>
                    <th class="text-center p-4">GÖSTERİM</th>
                    <th class="text-center p-4">TIKLAMA</th>
                    <th class="text-center p-4">CTR</th>
                    <th class="text-center p-4">DURUM</th>
                    <th class="text-right p-4">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $ad): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-4">
                        <div class="flex items-center">
                            <div class="ml-3">
                                <p class="font-medium"><?= htmlspecialchars($ad['name']) ?></p>
                                <p class="text-sm text-gray-500"><?= date('d.m.Y', strtotime($ad['created_at'])) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                            <?= $positions[$ad['position']] ?? $ad['position'] ?>
                        </span>
                    </td>
                    <td class="p-4">
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                            <?= $types[$ad['type']] ?? $ad['type'] ?>
                        </span>
                    </td>
                    <td class="p-4 text-center"><?= number_format($ad['impressions']) ?></td>
                    <td class="p-4 text-center"><?= number_format($ad['clicks']) ?></td>
                    <td class="p-4 text-center"><?= $ad['ctr'] ?>%</td>
                    <td class="p-4 text-center">
                        <span class="px-3 py-1 <?= $ad['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> rounded-full text-sm">
                            <?= $ad['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </td>
                    <td class="p-4 text-right space-x-2">
                        <button onclick="openAdModal(<?= htmlspecialchars(json_encode($ad)) ?>)" 
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Bu reklamı silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="adModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-xl mx-4">
        <div class="flex justify-between items-center mb-6">
            <h2 id="modalTitle" class="text-xl font-semibold">Yeni Reklam Ekle</h2>
            <button onclick="closeAdModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="ad_id" id="adId">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reklam Adı <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="adName" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konum</label>
                    <select name="position" id="adPosition" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($positions as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tür</label>
                    <select name="type" id="adType" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($types as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    HTML İçerik <span class="text-red-500">*</span>
                </label>
                <textarea name="code" id="adCode" required rows="6"
                          placeholder="Reklam HTML kodunu buraya yapıştırın..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                <p class="text-sm text-gray-500 mt-1">
                    Google AdSense, banner reklamları veya özel HTML kodları kullanabilirsiniz.
                </p>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="adActive" checked
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="adActive" class="ml-2 text-sm text-gray-700">Aktif</label>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeAdModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    İptal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdModal(ad = null) {
    const modal = document.getElementById('adModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const form = modal.querySelector('form');
    
    form.reset();
    
    if (ad) {
        modalTitle.textContent = 'Reklamı Düzenle';
        formAction.value = 'edit';
        document.getElementById('adId').value = ad.id;
        document.getElementById('adName').value = ad.name;
        document.getElementById('adCode').value = ad.code;
        document.getElementById('adPosition').value = ad.position;
        document.getElementById('adType').value = ad.type;
        document.getElementById('adActive').checked = ad.is_active == 1;
    } else {
        modalTitle.textContent = 'Yeni Reklam Ekle';
        formAction.value = 'add';
        document.getElementById('adId').value = '';
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAdModal() {
    const modal = document.getElementById('adModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAdModal();
    }
});
</script> 