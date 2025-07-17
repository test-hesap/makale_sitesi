<?php
// Makale ekleme/düzenleme sayfası
$action = $_GET['action'] ?? '';
if ($action == 'add' || $action == 'edit') {
    include 'article-form.php';
    return;
}

// Makale işlemleri
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve':
            $articleId = intval($_POST['article_id']);
            $db->prepare("UPDATE articles SET status = 'published' WHERE id = ?")->execute([$articleId]);
            $success = "Makale onaylandı.";
            break;
        case 'reject':
            $articleId = intval($_POST['article_id']);
            $db->prepare("UPDATE articles SET status = 'draft' WHERE id = ?")->execute([$articleId]);
            $success = "Makale reddedildi.";
            break;
        case 'delete':
            $articleId = intval($_POST['article_id']);
            $db->prepare("DELETE FROM articles WHERE id = ?")->execute([$articleId]);
            $success = "Makale silindi.";
            break;
        case 'bulk_delete':
            if (!empty($_POST['selected_articles']) && is_array($_POST['selected_articles'])) {
                $selectedIds = array_map('intval', $_POST['selected_articles']);
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("DELETE FROM articles WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $deletedCount = $stmt->rowCount();
                $success = "$deletedCount makale başarıyla silindi.";
            } else {
                $error = "Silinecek makale seçilmedi.";
            }
            break;
        case 'bulk_approve':
            if (!empty($_POST['selected_articles']) && is_array($_POST['selected_articles'])) {
                $selectedIds = array_map('intval', $_POST['selected_articles']);
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("UPDATE articles SET status = 'published' WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $updatedCount = $stmt->rowCount();
                $success = "$updatedCount makale başarıyla onaylandı.";
            } else {
                $error = "Onaylanacak makale seçilmedi.";
            }
            break;
        case 'bulk_reject':
            if (!empty($_POST['selected_articles']) && is_array($_POST['selected_articles'])) {
                $selectedIds = array_map('intval', $_POST['selected_articles']);
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("UPDATE articles SET status = 'draft' WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $updatedCount = $stmt->rowCount();
                $success = "$updatedCount makale taslağa alındı.";
            } else {
                $error = "İşlem yapılacak makale seçilmedi.";
            }
            break;
    }
}

// Filtreleme
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['p'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Sorgu oluştur
$where_conditions = [];
$params = [];

if ($status_filter != 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Toplam sayı
$countQuery = "SELECT COUNT(*) as total FROM articles a $where_clause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$total_articles = $countStmt->fetch()['total'];

// Makaleler
$articlesQuery = "SELECT a.*, u.username, c.name as category_name 
                  FROM articles a 
                  LEFT JOIN users u ON a.user_id = u.id 
                  LEFT JOIN categories c ON a.category_id = c.id 
                  $where_clause 
                  ORDER BY a.created_at DESC 
                  LIMIT $per_page OFFSET $offset";
$articlesStmt = $db->prepare($articlesQuery);
$articlesStmt->execute($params);
$articles = $articlesStmt->fetchAll();

$total_pages = ceil($total_articles / $per_page);

// Başarı mesajını kontrol et
if (isset($_GET['success'])) {
    $success = "İşlem başarıyla tamamlandı.";
}
?>

<!-- Başlık ve Butonlar -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Makaleler</h2>
        <p class="text-gray-600 dark:text-gray-400">Tüm makaleleri yönetin • Toplu işlemler için makale seçin</p>
    </div>
    <div class="mt-4 sm:mt-0 flex space-x-3">
        <a href="?page=articles&action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i>Yeni Makale
        </a>
    </div>
</div>

<!-- Filtreler -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
    <form method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="hidden" name="page" value="articles">
        
        <!-- Arama -->
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Makale başlığı veya içeriği ara..." 
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        
        <!-- Durum Filtresi -->
        <div>
            <select name="status" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tüm Durumlar</option>
                <option value="published" <?php echo $status_filter == 'published' ? 'selected' : ''; ?>>Yayında</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Bekliyor</option>
                <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Taslak</option>
            </select>
        </div>
        
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
            <i class="fas fa-search mr-2"></i>Filtrele
        </button>
    </form>
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

<!-- İstatistikler -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="text-2xl font-bold text-blue-600"><?php echo $stats['articles']['total']; ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Makale</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="text-2xl font-bold text-green-600"><?php echo $stats['articles']['published']; ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Yayında</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['articles']['pending']; ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Bekliyor</div>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="text-2xl font-bold text-gray-600"><?php echo $stats['articles']['draft']; ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Taslak</div>
    </div>
</div>

<!-- Makale Listesi -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- Toplu İşlemler -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <label class="flex items-center">
                    <input type="checkbox" id="selectAll" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tümünü Seç</span>
                </label>
                <span id="selectedCount" class="text-sm text-gray-500 dark:text-gray-400">0 makale seçildi</span>
            </div>
            <div id="bulkActions" class="flex items-center space-x-2" style="display: none;">
                <button type="button" onclick="performBulkAction('bulk_approve')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-check mr-1"></i>Onayla
                </button>
                <button type="button" onclick="performBulkAction('bulk_reject')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-times mr-1"></i>Reddet
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Makale</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Yazar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (!empty($articles)): ?>
                    <?php foreach ($articles as $article): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="article_checkbox" value="<?php echo $article['id']; ?>" 
                                   class="article-checkbox form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-start space-x-3">
                                <?php if ($article['featured_image']): ?>
                                <img src="<?php echo $article['featured_image']; ?>" alt="" class="w-12 h-12 rounded-lg object-cover">
                                <?php else: ?>
                                <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        <?php echo $article['title']; ?>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                        <?php echo createExcerpt($article['content'], 100); ?>
                                    </p>
                                    <?php if ($article['is_premium']): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 mt-1">
                                        <i class="fas fa-crown mr-1"></i>Premium
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            <?php echo $article['username']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            <?php echo $article['category_name']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $article['status'] == 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                          ($article['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                           'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'); ?>">
                                <?php echo $article['status'] == 'published' ? 'Yayında' : ($article['status'] == 'pending' ? 'Bekliyor' : 'Taslak'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo timeAgo($article['created_at']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <a href="/makale/<?php echo $article['slug']; ?>" target="_blank" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?page=articles&action=edit&id=<?php echo $article['id']; ?>" 
                                   class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($article['status'] == 'pending'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" 
                                            onclick="return confirm('Bu makaleyi onaylamak istediğinizden emin misiniz?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300"
                                            onclick="return confirm('Bu makaleyi reddetmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            onclick="return confirm('Bu makaleyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-file-alt text-4xl mb-4"></i>
                            <p>Henüz makale bulunmuyor.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sayfalama -->
<?php if ($total_pages > 1): ?>
<div class="flex flex-col sm:flex-row justify-between items-center mt-6">
    <div class="text-sm text-gray-700 dark:text-gray-300 mb-4 sm:mb-0">
        Toplam <?php echo $total_articles; ?> makaleden <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_articles); ?> arası gösteriliyor
    </div>
    
    <nav class="flex items-center space-x-1">
        <?php if ($page > 1): ?>
            <a href="?page=articles&p=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-chevron-left"></i> Önceki
            </a>
        <?php endif; ?>
        
        <?php
        // Sayfa numaralarını akıllıca göster
        $startPage = max(1, $page - 2);
        $endPage = min($total_pages, $page + 2);
        
        // İlk sayfa
        if ($startPage > 1): ?>
            <a href="?page=articles&p=1&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                1
            </a>
            <?php if ($startPage > 2): ?>
                <span class="px-2 text-gray-400">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=articles&p=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php
        // Son sayfa
        if ($endPage < $total_pages): ?>
            <?php if ($endPage < $total_pages - 1): ?>
                <span class="px-2 text-gray-400">...</span>
            <?php endif; ?>
            <a href="?page=articles&p=<?php echo $total_pages; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <?php echo $total_pages; ?>
            </a>
        <?php endif; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=articles&p=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                Sonraki <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<!-- Gizli Form - Toplu İşlemler İçin -->
<form id="bulkActionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="bulkActionType">
    <div id="selectedArticlesInputs"></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllHeaderCheckbox = document.getElementById('selectAllHeader');
    const articleCheckboxes = document.querySelectorAll('.article-checkbox');
    const selectedCountElement = document.getElementById('selectedCount');
    const bulkActionsElement = document.getElementById('bulkActions');
    
    // Tümünü seç/seçimi kaldır
    function updateSelectAll() {
        const checkedBoxes = document.querySelectorAll('.article-checkbox:checked');
        const totalBoxes = document.querySelectorAll('.article-checkbox');
        
        selectAllCheckbox.checked = checkedBoxes.length === totalBoxes.length && totalBoxes.length > 0;
        selectAllHeaderCheckbox.checked = selectAllCheckbox.checked;
        
        // Seçili makale sayısını güncelle
        selectedCountElement.textContent = `${checkedBoxes.length} makale seçildi`;
        
        // Toplu işlem butonlarını göster/gizle
        if (checkedBoxes.length > 0) {
            bulkActionsElement.style.display = 'flex';
        } else {
            bulkActionsElement.style.display = 'none';
        }
    }
    
    // Tümünü seç butonları
    selectAllCheckbox.addEventListener('change', function() {
        articleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        selectAllHeaderCheckbox.checked = this.checked;
        updateSelectAll();
    });
    
    selectAllHeaderCheckbox.addEventListener('change', function() {
        articleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        selectAllCheckbox.checked = this.checked;
        updateSelectAll();
    });
    
    // Tekil checkbox'lar
    articleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAll);
    });
    
    // İlk yüklemede durumu kontrol et
    updateSelectAll();
});

// Toplu işlem gerçekleştir
function performBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.article-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Lütfen en az bir makale seçin.');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'bulk_delete':
            confirmMessage = `Seçili ${checkedBoxes.length} makaleyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!`;
            break;
        case 'bulk_approve':
            confirmMessage = `Seçili ${checkedBoxes.length} makaleyi onaylamak istediğinizden emin misiniz?`;
            break;
        case 'bulk_reject':
            confirmMessage = `Seçili ${checkedBoxes.length} makaleyi reddetmek istediğinizden emin misiniz?`;
            break;
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Form verilerini hazırla
    const form = document.getElementById('bulkActionForm');
    const actionInput = document.getElementById('bulkActionType');
    const selectedInputsContainer = document.getElementById('selectedArticlesInputs');
    
    actionInput.value = action;
    
    // Seçili makale ID'lerini ekle
    selectedInputsContainer.innerHTML = '';
    checkedBoxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_articles[]';
        input.value = checkbox.value;
        selectedInputsContainer.appendChild(input);
    });
    
    // Formu gönder
    form.submit();
}
</script> 