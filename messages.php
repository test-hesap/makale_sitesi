<?php
require_once 'includes/header.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$pageTitle = 'Mesajlarım - ' . getSiteSetting('site_title');
$metaDescription = 'Özel mesajlarınız ve yazışmalarınız.';

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Sayfalama parametreleri
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Toplam mesaj sayısı
    $countStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM messages 
        WHERE receiver_id = ? OR sender_id = ?
    ");
    $countStmt->execute([$userId, $userId]);
    $totalMessages = $countStmt->fetchColumn();
    
    // Mesajları çek
    $stmt = $db->prepare("
        SELECT 
            m.*,
            sender.username as sender_username,
            receiver.username as receiver_username
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        WHERE m.receiver_id = ? OR m.sender_id = ?
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$userId, $userId, $limit, $offset]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sayfa sayısı hesapla
    $totalPages = ceil($totalMessages / $limit);
    
} catch (Exception $e) {
    $error = 'Mesajlar yüklenirken bir hata oluştu: ' . $e->getMessage();
    $messages = [];
    $totalMessages = 0;
    $totalPages = 1;
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Başlık -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Mesajlarım
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Özel mesajlarınız ve yazışmalarınız
            </p>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <!-- Yeni Mesaj Butonu -->
        <div class="mb-6">
            <?php if ($currentUser['is_approved']): ?>
            <button onclick="showNewMessageModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>Yeni Mesaj
            </button>
            <?php else: ?>
            <div class="flex items-center mb-4">
                <button disabled 
                        class="bg-gray-400 cursor-not-allowed text-white px-6 py-3 rounded-lg mr-3">
                    <i class="fas fa-paper-plane mr-2"></i>Yeni Mesaj
                </button>
                <span class="text-sm text-yellow-600 dark:text-yellow-400">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <?= $language == 'en' ? 'Your account needs to be approved to send messages' : 'Mesaj gönderebilmek için hesabınızın onaylanması gerekiyor' ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Mesaj Listesi -->
        <?php if (empty($messages)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">
                Henüz mesajınız yok
            </h3>
            <p class="text-gray-500 dark:text-gray-500">
                Yeni bir mesaj göndermek için "Yeni Mesaj" butonunu kullanın.
            </p>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($messages as $message): ?>
            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <?php if ($message['sender_id'] == $userId): ?>
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-paper-plane text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <?php else: ?>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-inbox text-green-600 dark:text-green-400"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($message['subject']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <?php if ($message['sender_id'] == $userId): ?>
                                Alıcı: <?= htmlspecialchars($message['receiver_username']) ?>
                                <?php else: ?>
                                Gönderen: <?= htmlspecialchars($message['sender_username']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <?= date('d.m.Y H:i', strtotime($message['created_at'])) ?>
                        </div>
                        <?php if ($message['receiver_id'] == $userId && !$message['is_read']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Yeni
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="text-gray-600 dark:text-gray-300 line-clamp-2">
                        <?= htmlspecialchars(substr($message['content'], 0, 150)) ?>
                        <?= strlen($message['content']) > 150 ? '...' : '' ?>
                    </p>
                </div>
                <div class="mt-4 flex justify-end space-x-2">
                    <button onclick="showMessageDetail(<?= $message['id'] ?>)"
                            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        <i class="fas fa-eye mr-1"></i>Görüntüle
                    </button>
                    <?php if ($message['receiver_id'] == $userId): ?>
                    <button onclick="showReplyModal(<?= $message['id'] ?>)"
                            class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300">
                        <i class="fas fa-reply mr-1"></i>Yanıtla
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Sayfalama -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" 
                   class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>" 
                   class="px-3 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' ?> rounded-lg">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" 
                   class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Yeni Mesaj Modal -->
<div id="newMessageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full mx-4">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Yeni Mesaj
        </h3>
        <form id="newMessageForm" onsubmit="sendMessage(event)">
            <div class="space-y-4">
                <div>
                    <label for="recipient" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Alıcı
                    </label>
                    <input type="text" id="recipient" name="recipient" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="Kullanıcı adı">
                </div>
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Konu
                    </label>
                    <input type="text" id="subject" name="subject" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="Mesaj konusu">
                </div>
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Mesaj
                    </label>
                    <textarea id="content" name="content" required rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              placeholder="Mesajınızı yazın..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="hideNewMessageModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    İptal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Gönder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mesaj Detay Modal -->
<div id="messageDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full mx-4">
        <div id="messageDetailContent">
            <!-- JavaScript ile doldurulacak -->
        </div>
        <div class="mt-6 flex justify-end">
            <button onclick="hideMessageDetail()"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                Kapat
            </button>
        </div>
    </div>
</div>

<script>
// Modal işlemleri
function showNewMessageModal() {
    <?php if (!$currentUser['is_approved']): ?>
    alert('<?= $language == 'en' ? 'Your account needs to be approved before you can send messages.' : 'Mesaj gönderebilmek için hesabınızın onaylanması gerekiyor.' ?>');
    return;
    <?php endif; ?>
    
    document.getElementById('newMessageModal').classList.remove('hidden');
    document.getElementById('newMessageModal').classList.add('flex');
}

function hideNewMessageModal() {
    document.getElementById('newMessageModal').classList.add('hidden');
    document.getElementById('newMessageModal').classList.remove('flex');
}

function showMessageDetail(messageId) {
    // AJAX ile mesaj detayını getir
    fetch(`/api/get-message-detail.php?id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('messageDetailContent').innerHTML = `
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        ${data.message.subject}
                    </h3>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Gönderen: ${data.message.sender_username}<br>
                            Tarih: ${data.message.created_at}
                        </p>
                    </div>
                    <div class="prose dark:prose-invert max-w-none">
                        ${data.message.content}
                    </div>
                `;
                document.getElementById('messageDetailModal').classList.remove('hidden');
                document.getElementById('messageDetailModal').classList.add('flex');
            } else {
                alert('Mesaj detayı yüklenirken bir hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Mesaj detayı yüklenirken bir hata oluştu.');
        });
}

function hideMessageDetail() {
    document.getElementById('messageDetailModal').classList.add('hidden');
    document.getElementById('messageDetailModal').classList.remove('flex');
}

function sendMessage(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('/api/send-message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideNewMessageModal();
            window.location.reload();
        } else {
            alert(data.error || 'Mesaj gönderilirken bir hata oluştu.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Mesaj gönderilirken bir hata oluştu.');
    });
}

function showReplyModal(messageId) {
    // AJAX ile orijinal mesajı getir
    fetch(`/api/get-message-detail.php?id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('recipient').value = data.message.sender_username;
                document.getElementById('subject').value = `Re: ${data.message.subject}`;
                showNewMessageModal();
            } else {
                alert('Mesaj detayı yüklenirken bir hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Mesaj detayı yüklenirken bir hata oluştu.');
        });
}
</script>

<?php require_once 'includes/footer.php'; ?> 