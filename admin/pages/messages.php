<?php
// Veritabanı bağlantısı kontrolü
try {
    if (!isset($db) || !$db) {
        throw new Exception("Veritabanı bağlantısı yok");
    }
    
    // Sayfa ve filtreleme ayarları
    $page = intval($_GET['p'] ?? 1);
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';

    // Mesaj işlemleri
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                $id = intval($_POST['message_id']);
                $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Mesaj okundu olarak işaretlendi.";
                break;
                
            case 'mark_unread':
                $id = intval($_POST['message_id']);
                $stmt = $db->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Mesaj okunmadı olarak işaretlendi.";
                break;
                
            case 'delete':
                $id = intval($_POST['message_id']);
                $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Mesaj silindi.";
                break;
                
            case 'reply':
                $id = intval($_POST['message_id']);
                $reply = trim($_POST['reply']);
                $email = $_POST['email'];
                
                if (empty($reply)) {
                    $error = "Yanıt metni boş olamaz.";
                    break;
                }
                
                // Yanıtı veritabanına kaydet
                $stmt = $db->prepare("UPDATE contact_messages SET reply = ?, replied_at = NOW(), replied_by = ? WHERE id = ?");
                $stmt->execute([$reply, $_SESSION['user_id'], $id]);
                
                // E-posta gönder
                require __DIR__ . '/../../vendor/autoload.php';
                
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    
                    // SMTP ayarları
                    $mail->isSMTP();
                    $mail->Host = getSiteSetting('smtp_host');
                    $mail->SMTPAuth = true;
                    $mail->Username = getSiteSetting('smtp_username');
                    $mail->Password = getSiteSetting('smtp_password');
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = getSiteSetting('smtp_port');
                    $mail->CharSet = 'UTF-8';
                    
                    // Gönderici ve alıcı
                    $mail->setFrom(getSiteSetting('smtp_username'), getSiteSetting('site_title', 'Site Yönetimi'));
                    $mail->addAddress($email, $_POST['name']);
                    
                    // İçerik
                    $mail->isHTML(true);
                    $mail->Subject = "RE: " . $_POST['subject'];
                    
                    $message = "<html><body>";
                    $message .= "<p>Sayın " . htmlspecialchars($_POST['name']) . ",</p>";
                    $message .= "<p>Mesajınıza yanıtımız aşağıdadır:</p>";
                    $message .= "<blockquote style='border-left: 2px solid #ccc; margin: 10px 0; padding: 10px;'>";
                    $message .= nl2br(htmlspecialchars($reply));
                    $message .= "</blockquote>";
                    $message .= "<p>Saygılarımızla,<br>" . getSiteSetting('site_title', 'Site Yönetimi') . "</p>";
                    $message .= "</body></html>";
                    
                    $mail->Body = $message;
                    
                    $mail->send();
                    $success = "Mesaj yanıtlandı ve e-posta gönderildi.";
                } catch (Exception $e) {
                    error_log("E-posta gönderme hatası: " . $e->getMessage());
                    $success = "Mesaj yanıtlandı fakat e-posta gönderilemedi. Hata: " . $e->getMessage();
                }
                
                break;
                
            case 'bulk_read':
                if (isset($_POST['message_ids']) && is_array($_POST['message_ids'])) {
                    $ids = array_map('intval', $_POST['message_ids']);
                    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    $success = count($ids) . " mesaj okundu olarak işaretlendi.";
                }
                break;
                
            case 'bulk_unread':
                if (isset($_POST['message_ids']) && is_array($_POST['message_ids'])) {
                    $ids = array_map('intval', $_POST['message_ids']);
                    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    $success = count($ids) . " mesaj okunmadı olarak işaretlendi.";
                }
                break;
                
            case 'bulk_delete':
                if (isset($_POST['message_ids']) && is_array($_POST['message_ids'])) {
                    $ids = array_map('intval', $_POST['message_ids']);
                    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                    $stmt = $db->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    $success = count($ids) . " mesaj silindi.";
                }
                break;
        }
    }

    // Where koşulları oluştur
    $whereConditions = [];
    $params = [];

    if ($filter === 'read') {
        $whereConditions[] = "is_read = 1";
    } elseif ($filter === 'unread') {
        $whereConditions[] = "is_read = 0";
    }

    if (!empty($search)) {
        $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Toplam mesaj sayısı
    $countQuery = "SELECT COUNT(*) FROM contact_messages $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalMessages = $countStmt->fetchColumn();
    $totalPages = ceil($totalMessages / $perPage);

    // Mesajları al
    $messagesQuery = "SELECT * FROM contact_messages 
                      $whereClause
                      ORDER BY created_at DESC 
                      LIMIT $perPage OFFSET $offset";
    $messagesStmt = $db->prepare($messagesQuery);
    $messagesStmt->execute($params);
    $messages = $messagesStmt->fetchAll();

    // İstatistikler
    $statsQuery = "SELECT 
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END), 0) as `read`,
        COALESCE(SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END), 0) as unread
        FROM contact_messages";
    $statsStmt = $db->query($statsQuery);
    $stats = $statsStmt->fetch();
    
    // Null değerleri 0'a çevir
    $stats['total'] = intval($stats['total']);
    $stats['read'] = intval($stats['read']);
    $stats['unread'] = intval($stats['unread']);
    
} catch (Exception $e) {
    // Hata durumunda demo verileri göster
    $error = "Veritabanı hatası: " . $e->getMessage();
    $messages = [];
    $stats = ['total' => 0, 'read' => 0, 'unread' => 0];
    $totalPages = 1;
    $totalMessages = 0;
}
?>

<?php if (isset($error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
    <p class="mt-2 text-sm">MySQL servisini başlatın ve contact_messages tablosunda is_read sütununun olduğundan emin olun.</p>
</div>
<?php endif; ?>

<!-- Başlık ve Filtreler -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Mesajlar</h2>
        <p class="text-gray-600 dark:text-gray-400">İletişim mesajlarını yönetin • Toplu işlemler için mesaj seçin</p>
    </div>
    
    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4 lg:mt-0">
        <form method="GET" class="flex space-x-2">
            <input type="hidden" name="page" value="messages">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Mesaj ara..." 
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
                <i class="fas fa-envelope text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Mesaj</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['total']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-envelope-open text-green-600 dark:text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Okundu</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['read']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                <i class="fas fa-envelope text-red-600 dark:text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Okunmadı</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['unread']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Butonları -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="?page=messages&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Tümü (<?php echo $stats['total']; ?>)
        </a>
        <a href="?page=messages&filter=unread&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'unread' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Okunmadı (<?php echo $stats['unread']; ?>)
        </a>
        <a href="?page=messages&filter=read&search=<?php echo urlencode($search); ?>" 
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter === 'read' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
            Okundu (<?php echo $stats['read']; ?>)
        </a>
    </div>
</div>

<!-- Toplu İşlemler -->
<form id="bulkForm" method="POST" class="mb-4">
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
                        id="bulkRead" disabled>
                    <i class="fas fa-check mr-1"></i>Okundu İşaretle
                </button>
                <button type="button"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition-colors disabled:opacity-50" 
                        id="bulkUnread" disabled>
                    <i class="fas fa-envelope mr-1"></i>Okunmadı İşaretle
                </button>
                <button type="button"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm transition-colors disabled:opacity-50" 
                        id="bulkDelete" disabled>
                    <i class="fas fa-trash mr-1"></i>Sil
                </button>
            </div>
        </div>
    </div>

    <!-- Mesaj Listesi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-4">
        <?php if (!empty($messages)): ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($messages as $message): ?>
                <div class="p-6 <?php echo !$message['is_read'] ? 'bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                    <div class="flex items-start space-x-4">
                        <input type="checkbox" name="message_ids[]" value="<?php echo $message['id']; ?>" 
                               class="message-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 mt-1">
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($message['name']); ?>
                                        <?php if (!$message['is_read']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-2">
                                            Yeni
                                        </span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($message['email']); ?>
                                    </p>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <?php if (!$message['is_read']): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Okundu İşaretle">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="mark_unread">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Okunmadı İşaretle">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                onclick="return confirm('Bu mesajı silmek istediğinizden emin misiniz?')" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Konu: <?php echo htmlspecialchars($message['subject']); ?>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </p>
                            </div>
                            
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span><i class="fas fa-calendar mr-1"></i><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></span>
                                <?php if (!$message['reply']): ?>
                                <a href="?page=message-reply&id=<?php echo $message['id']; ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors shadow-sm">
                                    <i class="fas fa-paper-plane mr-2"></i>Yanıtla
                                </a>
                                <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1.5 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-sm font-medium rounded-md">
                                    <i class="fas fa-check mr-1.5"></i>Yanıtlandı
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($message['reply']): ?>
                            <div class="mt-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yanıtınız:</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo nl2br(htmlspecialchars($message['reply'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-envelope text-4xl mb-4"></i>
                <p>Henüz mesaj bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- Sayfalama -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-center mt-6">
    <nav class="flex items-center space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=messages&p=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
</div>
<?php endif; ?>

<script>
// Toplu seçim işlemleri
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.message-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkButtons();
});

document.querySelectorAll('.message-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkButtons);
});

function updateBulkButtons() {
    const checkboxes = document.querySelectorAll('.message-checkbox:checked');
    const count = checkboxes.length;
    
    document.getElementById('selectedCount').textContent = count + ' seçili';
    document.getElementById('bulkRead').disabled = count === 0;
    document.getElementById('bulkUnread').disabled = count === 0;
    document.getElementById('bulkDelete').disabled = count === 0;
    
    // Tümünü seç checkbox'ını güncelle
    const allCheckboxes = document.querySelectorAll('.message-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    selectAllCheckbox.checked = count === allCheckboxes.length;
    selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
}

// Toplu işlem butonları için olay dinleyicileri ekle
document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.getElementById('bulkForm');
    
    // Bulk Delete butonu için özel bir işleyici ekle
    document.getElementById('bulkDelete').addEventListener('click', function(e) {
        e.preventDefault(); // Formun normal gönderimini engelle
        
        // Seçili mesajları kontrol et
        const checkboxes = document.querySelectorAll('.message-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Lütfen en az bir mesaj seçin.');
            return;
        }
        
        // Kullanıcıya onay sor
        if (confirm('Seçili mesajları silmek istediğinizden emin misiniz?')) {
            // Action değerini belirle ve formu gönder
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'bulk_delete';
            bulkForm.appendChild(actionInput);
            bulkForm.submit();
        }
    });
    
    // Diğer toplu işlem butonları için benzer işleyiciler
    document.getElementById('bulkRead').addEventListener('click', function(e) {
        e.preventDefault();
        const checkboxes = document.querySelectorAll('.message-checkbox:checked');
        if (checkboxes.length > 0) {
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'bulk_read';
            bulkForm.appendChild(actionInput);
            bulkForm.submit();
        }
    });
    
    document.getElementById('bulkUnread').addEventListener('click', function(e) {
        e.preventDefault();
        const checkboxes = document.querySelectorAll('.message-checkbox:checked');
        if (checkboxes.length > 0) {
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'bulk_unread';
            bulkForm.appendChild(actionInput);
            bulkForm.submit();
        }
    });
});

// Mesaj yönetimi fonksiyonları
</script> 