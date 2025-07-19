<?php
require_once 'includes/header.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$language = getCurrentLanguage();
$pageTitle = t('messages') . ' - ' . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'Your private messages and conversations.' : 'Özel mesajlarınız ve yazışmalarınız.';

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Mesaj kutusu seçimi (gelen/gönderilen)
$messageBox = isset($_GET['box']) ? $_GET['box'] : 'gelen';

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Okunmamış mesaj sayısını al
    $unreadStmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $unreadStmt->execute([$userId]);
    $unreadCount = $unreadStmt->fetchColumn();
    
    // Mesajları çek
    if ($messageBox == 'gelen') {
        $stmt = $db->prepare("
            SELECT 
                m.*,
                u.username as sender_username,
                u.profile_image as sender_avatar,
                NULL as receiver_username,
                NULL as receiver_avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ? 
            AND m.is_deleted_by_receiver = FALSE
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                m.*,
                NULL as sender_username,
                NULL as sender_avatar,
                u.username as receiver_username,
                u.profile_image as receiver_avatar
            FROM messages m
            JOIN users u ON m.receiver_id = u.id
            WHERE m.sender_id = ? 
            AND m.is_deleted_by_sender = FALSE
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Mesajlar yüklenirken bir hata oluştu: ' . $e->getMessage();
    $messages = [];
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Başlık -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <?= t('messages') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                <?= $language == 'en' ? 'Your private messages and conversations.' : 'Özel mesajlarınız ve yazışmalarınız' ?>
            </p>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <!-- Mesaj Kutusu Seçimi -->
        <div class="flex mb-6 border-b">
            <a href="?box=gelen" class="px-4 py-2 <?= $messageBox == 'gelen' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600' ?> font-medium">
                <?= $language == 'en' ? 'Inbox' : 'Gelen Kutusu' ?> <?= $unreadCount > 0 ? "<span class='bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-1'>$unreadCount</span>" : '' ?>
            </a>
            <a href="?box=gonderilen" class="px-4 py-2 <?= $messageBox == 'gonderilen' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-600' ?> font-medium">
                <?= $language == 'en' ? 'Sent Messages' : 'Gönderilen Mesajlar' ?>
            </a>
        </div>

        <!-- Mesaj Listesi -->
        <?php if (empty($messages)): ?>
        <div class="text-center py-12">
            <div class="text-gray-400 mb-4">
                <i class="far fa-envelope text-6xl"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-600 mb-2"><?= $language == 'en' ? 'No Messages Found' : 'Mesaj Bulunamadı' ?></h3>
            <p class="text-gray-500">
                <?= $messageBox == 'gelen' 
                    ? ($language == 'en' ? 'You have no messages yet.' : 'Henüz hiç mesajınız bulunmuyor.') 
                    : ($language == 'en' ? 'You haven\'t sent any messages yet.' : 'Henüz hiç mesaj göndermediniz.') ?>
            </p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow divide-y">
            <?php foreach ($messages as $message): 
                $username = $messageBox == 'gelen' ? $message['sender_username'] : $message['receiver_username'];
                $avatarUrl = $messageBox == 'gelen' ? $message['sender_avatar'] : $message['receiver_avatar'];
            ?>
            <div class="p-4 hover:bg-gray-50 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <?php if (empty($avatarUrl)): ?>
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <span class="text-lg text-blue-600 dark:text-blue-400 font-medium">
                                <?= strtoupper(substr($username, 0, 1)) ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>" 
                             alt="<?= htmlspecialchars($username) ?>" 
                             class="w-10 h-10 rounded-full bg-gray-200">
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="font-medium">
                            <?= htmlspecialchars($username) ?>
                        </div>
                        <div class="text-sm text-gray-600">
                            <?= htmlspecialchars($message['subject']) ?>
                            <span class="mx-2">•</span>
                            <?= htmlspecialchars(substr($message['content'], 0, 50)) ?>...
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        <?= date('d.m.Y H:i', strtotime($message['created_at'])) ?>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="showMessageDetail(<?= $message['id'] ?>)" 
                                class="text-gray-400 hover:text-gray-600">
                            <i class="far fa-eye"></i>
                        </button>
                        <button onclick="deleteMessage(<?= $message['id'] ?>)"
                                class="text-gray-400 hover:text-red-600">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Mesaj Detay Modal -->
<div id="messageDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <div id="messageDetailContent"></div>
        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="hideMessageDetail()" 
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded">
                <?= $language == 'en' ? 'Close' : 'Kapat' ?>
            </button>
        </div>
    </div>
</div>

<script>
function showMessageDetail(messageId) {
    window.location.href = `/api/get-message-detail.php?id=${messageId}`;
}

function deleteMessage(messageId) {
    const currentLanguage = '<?= $language ?>';
    const confirmText = currentLanguage === 'en' ? 'Are you sure you want to delete this message?' : 'Bu mesajı silmek istediğinizden emin misiniz?';
    const errorText = currentLanguage === 'en' ? 'Message could not be deleted: ' : 'Mesaj silinemedi: ';
    
    if (confirm(confirmText)) {
        fetch(`/api/delete-message.php?id=${messageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(errorText + data.error);
                }
            });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 