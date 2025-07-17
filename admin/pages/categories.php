<?php
require_once __DIR__ . '/../../config/database.php';

// Kategori işlemleri
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $slug = generateSlug($name);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (!empty($name)) {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $is_active]);
                
                // Otomatik sitemap güncellemesi
                autoGenerateSitemap();
                
                $success = "Kategori başarıyla eklendi.";
            } else {
                $error = "Kategori adı gereklidir.";
            }
            break;
            
        case 'edit':
            $id = intval($_POST['category_id']);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $slug = generateSlug($name);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (!empty($name)) {
                $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $is_active, $id]);
                
                // Otomatik sitemap güncellemesi
                autoGenerateSitemap();
                
                $success = "Kategori başarıyla güncellendi.";
            } else {
                $error = "Kategori adı gereklidir.";
            }
            break;
            
        case 'delete':
            $id = intval($_POST['category_id']);
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            // Otomatik sitemap güncellemesi
            autoGenerateSitemap();
            
            $success = "Kategori silindi.";
            break;
            
        case 'bulk_delete':
            if (!empty($_POST['selected_categories']) && is_array($_POST['selected_categories'])) {
                $selectedIds = array_map('intval', $_POST['selected_categories']);
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("DELETE FROM categories WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $deletedCount = $stmt->rowCount();
                
                // Otomatik sitemap güncellemesi
                autoGenerateSitemap();
                
                $success = "$deletedCount kategori başarıyla silindi.";
            } else {
                $error = "Silinecek kategori seçilmedi.";
            }
            break;
            
        case 'bulk_activate':
            if (!empty($_POST['selected_categories']) && is_array($_POST['selected_categories'])) {
                $selectedIds = array_map('intval', $_POST['selected_categories']);
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("UPDATE categories SET is_active = 1 WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $updatedCount = $stmt->rowCount();
                
                // Otomatik sitemap güncellemesi
                autoGenerateSitemap();
                
                $success = "$updatedCount kategori başarıyla aktif edildi.";
            } else {
                $error = "Aktif edilecek kategori seçilmedi.";
            }
            break;
            
        case 'bulk_deactivate':
            if (!empty($_POST['selected_categories']) && is_array($_POST['selected_categories'])) {
                $selectedIds = array_map('intval', $_POST['selected_categories']);
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("UPDATE categories SET is_active = 0 WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $updatedCount = $stmt->rowCount();
                
                // Otomatik sitemap güncellemesi
                autoGenerateSitemap();
                
                $success = "$updatedCount kategori başarıyla pasif edildi.";
            } else {
                $error = "Pasif edilecek kategori seçilmedi.";
            }
            break;
    }
}

// Kategorileri al
$categoriesQuery = "SELECT c.*, COUNT(a.id) as article_count 
                    FROM categories c 
                    LEFT JOIN articles a ON c.id = a.category_id 
                    GROUP BY c.id 
                    ORDER BY c.created_at DESC";
$categoriesStmt = $db->query($categoriesQuery);
$categories = $categoriesStmt->fetchAll();

// Düzenlenecek kategori
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}
?>

<!-- Başlık ve Butonlar -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kategoriler</h2>
        <p class="text-gray-600 dark:text-gray-400">Makale kategorilerini yönetin • Toplu işlemler için kategori seçin</p>
    </div>
    <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus mr-2"></i>Yeni Kategori
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

<!-- Kategori Listesi -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- Toplu İşlemler -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <label class="flex items-center">
                    <input type="checkbox" id="selectAll" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tümünü Seç</span>
                </label>
                <span id="selectedCount" class="text-sm text-gray-500 dark:text-gray-400">0 kategori seçildi</span>
            </div>
            <div id="bulkActions" class="flex items-center space-x-2" style="display: none;">
                <button type="button" onclick="performBulkAction('bulk_activate')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-check mr-1"></i>Aktif Et
                </button>
                <button type="button" onclick="performBulkAction('bulk_deactivate')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-times mr-1"></i>Pasif Et
                </button>
                <button type="button" onclick="performBulkAction('bulk_delete')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-trash mr-1"></i>Sil
                </button>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">
                        <input type="checkbox" id="selectAllHeader" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Açıklama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Makale Sayısı</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="category_checkbox" value="<?php echo $category['id']; ?>" 
                                   class="category-checkbox form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Slug: <?php echo htmlspecialchars($category['slug']); ?>
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($category['description'] ?? '-'); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full text-xs">
                                <?php echo $category['article_count']; ?> makale
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $category['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                <?php echo $category['is_active'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-tags text-4xl mb-4"></i>
                            <p>Henüz kategori bulunmuyor.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 dark:text-white">
                    Yeni Kategori Ekle
                </h3>
            </div>
            
            <form id="categoryForm" method="POST" class="px-6 py-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="category_id" id="categoryId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kategori Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="categoryName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Açıklama
                        </label>
                        <textarea name="description" id="categoryDescription" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="categoryActive" value="1" checked
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
                        <span id="submitText">Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gizli Form - Toplu İşlemler İçin -->
<form id="bulkActionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="bulkActionType">
    <div id="selectedCategoriesInputs"></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllHeaderCheckbox = document.getElementById('selectAllHeader');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const selectedCountElement = document.getElementById('selectedCount');
    const bulkActionsElement = document.getElementById('bulkActions');
    
    // Tümünü seç/seçimi kaldır
    function updateSelectAll() {
        const checkedBoxes = document.querySelectorAll('.category-checkbox:checked');
        const totalBoxes = document.querySelectorAll('.category-checkbox');
        
        selectAllCheckbox.checked = checkedBoxes.length === totalBoxes.length && totalBoxes.length > 0;
        selectAllHeaderCheckbox.checked = selectAllCheckbox.checked;
        
        // Seçili kategori sayısını güncelle
        selectedCountElement.textContent = `${checkedBoxes.length} kategori seçildi`;
        
        // Toplu işlem butonlarını göster/gizle
        if (checkedBoxes.length > 0) {
            bulkActionsElement.style.display = 'flex';
        } else {
            bulkActionsElement.style.display = 'none';
        }
    }
    
    // Tümünü seç butonları
    selectAllCheckbox.addEventListener('change', function() {
        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        selectAllHeaderCheckbox.checked = this.checked;
        updateSelectAll();
    });
    
    selectAllHeaderCheckbox.addEventListener('change', function() {
        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        selectAllCheckbox.checked = this.checked;
        updateSelectAll();
    });
    
    // Tekil checkbox'lar
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAll);
    });
    
    // İlk yüklemede durumu kontrol et
    updateSelectAll();
});

// Toplu işlem gerçekleştir
function performBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.category-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Lütfen en az bir kategori seçin.');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'bulk_delete':
            confirmMessage = `Seçili ${checkedBoxes.length} kategoriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!`;
            break;
        case 'bulk_activate':
            confirmMessage = `Seçili ${checkedBoxes.length} kategoriyi aktif etmek istediğinizden emin misiniz?`;
            break;
        case 'bulk_deactivate':
            confirmMessage = `Seçili ${checkedBoxes.length} kategoriyi pasif etmek istediğinizden emin misiniz?`;
            break;
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Form verilerini hazırla
    const form = document.getElementById('bulkActionForm');
    const actionInput = document.getElementById('bulkActionType');
    const selectedInputsContainer = document.getElementById('selectedCategoriesInputs');
    
    actionInput.value = action;
    
    // Seçili kategori ID'lerini ekle
    selectedInputsContainer.innerHTML = '';
    checkedBoxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_categories[]';
        input.value = checkbox.value;
        selectedInputsContainer.appendChild(input);
    });
    
    // Formu gönder
    form.submit();
}

// Mevcut modal fonksiyonları
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Yeni Kategori Ekle';
    document.getElementById('formAction').value = 'add';
    document.getElementById('submitText').textContent = 'Kaydet';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryActive').checked = true;
    document.getElementById('categoryModal').classList.remove('hidden');
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Kategori Düzenle';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('submitText').textContent = 'Güncelle';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('categoryActive').checked = category.is_active == 1;
    document.getElementById('categoryModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

// Modal dışına tıklandığında kapat
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
