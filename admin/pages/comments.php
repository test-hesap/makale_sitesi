<?php
// Sayfa ve filtreleme ayarları
$page = intval($_GET['p'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Yorum işlemleri
if (isset($_POST['action'])) {
    try {
        $db->beginTransaction();
        
        switch ($_POST['action']) {
            case 'approve':
                $id = intval($_POST['comment_id']);
                $stmt = $db->prepare("UPDATE comments SET is_approved = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Yorum onaylandı.";
                break;
                
            case 'reject':
                $id = intval($_POST['comment_id']);
                $stmt = $db->prepare("UPDATE comments SET is_approved = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Yorum reddedildi.";
                break;
                
            case 'delete':
                $id = intval($_POST['comment_id']);
                $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Yorum silindi.";
                break;
                
            case 'bulk_approve':
                if (isset($_POST['comment_ids']) && is_array($_POST['comment_ids'])) {
                    $ids = array_map('intval', $_POST['comment_ids']);
                    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                    $stmt = $db->prepare("UPDATE comments SET is_approved = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    $success = count($ids) . " yorum onaylandı.";
                }
                break;
                
            case 'bulk_delete':
                if (isset($_POST['comment_ids']) && is_array($_POST['comment_ids'])) {
                    $ids = array_map('intval', $_POST['comment_ids']);
                    if (!empty($ids)) {
                        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                        $stmt = $db->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $success = count($ids) . " yorum başarıyla silindi.";
                    }
                }
                break;
        }
        
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
    }
}

// Where koşulları oluştur
$whereConditions = [];
$params = [];

if ($filter === 'approved') {
    $whereConditions[] = "c.is_approved = 1";
} elseif ($filter === 'pending') {
    $whereConditions[] = "c.is_approved = 0";
}

if (!empty($search)) {
    $whereConditions[] = "(c.content LIKE ? OR c.author_name LIKE ? OR a.title LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Toplam yorum sayısı
$countQuery = "SELECT COUNT(*) FROM comments c 
               LEFT JOIN articles a ON c.article_id = a.id 
               $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalComments = $countStmt->fetchColumn();
$totalPages = ceil($totalComments / $perPage);

// Yorumları al
$commentsQuery = "SELECT c.*, a.title as article_title, a.slug as article_slug,
                         u.username, u.email as user_email
                  FROM comments c 
                  LEFT JOIN articles a ON c.article_id = a.id
                  LEFT JOIN users u ON c.user_id = u.id
                  $whereClause
                  ORDER BY c.created_at DESC 
                  LIMIT $perPage OFFSET $offset";
$commentsStmt = $db->prepare($commentsQuery);
$commentsStmt->execute($params);
$comments = $commentsStmt->fetchAll();

// İstatistikler
$statsQuery = "SELECT 
    COALESCE(COUNT(*), 0) as total,
    COALESCE(SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END), 0) as approved,
    COALESCE(SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END), 0) as pending
    FROM comments";
$statsStmt = $db->query($statsQuery);
$stats = $statsStmt->fetch();
?>

<!-- Başlık ve Filtreler -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Yorumlar</h2>
        <p class="text-gray-600 dark:text-gray-400">Kullanıcı yorumlarını yönetin</p>
    </div>
    
    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4 lg:mt-0">
        <form method="GET" class="flex space-x-2">
            <input type="hidden" name="page" value="comments">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Yorum ara..." 
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

<!-- İstatistik Kartları -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-comments text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Yorum</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format((float)$stats['total']); ?></p>
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
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format((float)$stats['approved']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format((float)$stats['pending']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Butonları -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="?page=comments&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Tümü (<?php echo $stats['total']; ?>)
        </a>
        <a href="?page=comments&filter=approved&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Onaylı (<?php echo $stats['approved']; ?>)
        </a>
        <a href="?page=comments&filter=pending&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Bekleyen (<?php echo $stats['pending']; ?>)
        </a>
    </div>
</div>

<!-- Toplu İşlemler -->
<form id="bulkForm" method="POST" class="mb-4">
    <input type="hidden" name="action" id="bulkAction" value="">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tümünü Seç</span>
                </label>
                <span id="selectedCount" class="text-sm text-gray-500 dark:text-gray-400">0 seçili</span>
            </div>
            
            <div class="flex space-x-2 mt-3 sm:mt-0">
                <button type="button" 
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm transition-colors disabled:opacity-50" 
                        id="bulkApprove" disabled onclick="submitBulkForm('bulk_approve')">
                    <i class="fas fa-check mr-1"></i>Onayla
                </button>
                <button type="button" 
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm transition-colors disabled:opacity-50" 
                        id="bulkDelete" disabled onclick="submitBulkForm('bulk_delete')">
                    <i class="fas fa-trash mr-1"></i>Sil
                </button>
            </div>
        </div>
    </div>

    <!-- Yorum Listesi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-4">
        <?php if (!empty($comments)): ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($comments as $comment): ?>
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <input type="checkbox" name="comment_ids[]" value="<?php echo $comment['id']; ?>" 
                               class="comment-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 mt-1">
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($comment['username'] ?? $comment['author_name']); ?>
                                    </p>
                                    <?php if ($comment['user_email']): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($comment['user_email']); ?>
                                    </p>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $comment['is_approved'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'; ?>">
                                        <?php echo $comment['is_approved'] ? 'Onaylı' : 'Bekliyor'; ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <?php if (!$comment['is_approved']): ?>
                                    <button type="button" onclick="approveComment(<?php echo $comment['id']; ?>)" 
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Onayla">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" onclick="rejectComment(<?php echo $comment['id']; ?>)"
                                            class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" title="Reddet">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" onclick="deleteComment(<?php echo $comment['id']; ?>)"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </p>
                            </div>
                            
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-4">
                                    <span><i class="fas fa-calendar mr-1"></i><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></span>
                                    <?php if ($comment['article_title']): ?>
                                    <a href="../makale/<?php echo $comment['article_slug']; ?>" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <i class="fas fa-external-link-alt mr-1"></i><?php echo htmlspecialchars($comment['article_title']); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-comments text-4xl mb-4"></i>
                <p>Henüz yorum bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- Sayfalama -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-center mt-6">
    <nav class="flex items-center space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=comments&p=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const bulkForm = document.getElementById('bulkForm');
    const bulkDelete = document.getElementById('bulkDelete');
    const bulkApprove = document.getElementById('bulkApprove');
    const bulkAction = document.getElementById('bulkAction');

    // Tümünü seç işlemi
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.comment-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkButtons();
        });
    }

    // Tekli seçim işlemleri
    document.querySelectorAll('.comment-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkButtons);
    });

    // Butonları güncelle
    function updateBulkButtons() {
        const checkboxes = document.querySelectorAll('.comment-checkbox:checked');
        const count = checkboxes.length;
        
        document.getElementById('selectedCount').textContent = count + ' seçili';
        if (bulkApprove) bulkApprove.disabled = count === 0;
        if (bulkDelete) bulkDelete.disabled = count === 0;
        
        // Tümünü seç checkbox'ını güncelle
        const allCheckboxes = document.querySelectorAll('.comment-checkbox');
        if (selectAll) {
            selectAll.checked = count === allCheckboxes.length && count > 0;
            selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
        }
    }
});

// Form gönderme işlemi
function submitBulkForm(action) {
    const form = document.getElementById('bulkForm');
    const checkedBoxes = document.querySelectorAll('.comment-checkbox:checked');
    const checkedIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (checkedIds.length === 0) {
        alert('Lütfen en az bir yorum seçin.');
        return;
    }

    let confirmMessage = '';
    if (action === 'bulk_delete') {
        confirmMessage = `${checkedIds.length} yorumu silmek istediğinizden emin misiniz?`;
    } else if (action === 'bulk_approve') {
        confirmMessage = `${checkedIds.length} yorumu onaylamak istediğinizden emin misiniz?`;
    }

    if (confirm(confirmMessage)) {
        // Form içindeki mevcut input'ları temizle
        form.querySelectorAll('input[name="comment_ids[]"]').forEach(input => input.remove());
        
        // Seçili ID'leri form'a ekle
        checkedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'comment_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.getElementById('bulkAction').value = action;
        form.submit();
    }
}

// Tekli yorum işlemleri için fonksiyonlar
function approveComment(commentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="approve">
        <input type="hidden" name="comment_id" value="${commentId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function rejectComment(commentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="comment_id" value="${commentId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteComment(commentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="comment_id" value="${commentId}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script> 