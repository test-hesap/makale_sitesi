<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$messageId = intval($_GET['id'] ?? 0);
$currentUser = getCurrentUser();
$language = getCurrentLanguage();

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Mesaj detaylarını çek
    $stmt = $db->prepare("
        SELECT 
            m.*,
            sender.username as sender_username,
            sender.profile_image as sender_avatar,
            receiver.username as receiver_username,
            receiver.profile_image as receiver_avatar
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    
    $stmt->execute([$messageId, $currentUser['id'], $currentUser['id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        throw new Exception('Mesaj bulunamadı.');
    }
    
    // Mesajı okundu olarak işaretle
    if ($message['receiver_id'] == $currentUser['id'] && !$message['is_read']) {
        $updateStmt = $db->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = ?");
        $updateStmt->execute([$messageId]);
    }
    
    // Header'ı dahil et
    require_once '../includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Üst Bar -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <?= htmlspecialchars($message['subject']) ?>
            </h1>
            <a href="/mesajlar" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i> <?= $language == 'en' ? 'Back to Messages' : 'Mesajlara Dön' ?>
            </a>
        </div>

        <!-- Mesaj Detayı -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start space-x-4">
                <!-- Gönderen Profil -->
                <div class="flex-shrink-0">
                    <?php if (empty($message['sender_avatar'])): ?>
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <span class="text-lg text-blue-600 dark:text-blue-400 font-medium">
                            <?= strtoupper(substr($message['sender_username'], 0, 1)) ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <img src="<?= htmlspecialchars($message['sender_avatar']) ?>" 
                         alt="<?= htmlspecialchars($message['sender_username']) ?>" 
                         class="w-12 h-12 rounded-full">
                    <?php endif; ?>
                </div>
                
                <!-- Mesaj İçeriği -->
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($message['sender_username']) ?>
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                <?= date('d.m.Y H:i', strtotime($message['created_at'])) ?>
                            </span>
                        </div>
                        <?php if ($message['is_read']): ?>
                        <span class="text-sm text-green-600 dark:text-green-400">
                            <i class="fas fa-check-double mr-1"></i> <?= $language == 'en' ? 'Read' : 'Okundu' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="prose dark:prose-invert max-w-none">
                        <?= nl2br(htmlspecialchars($message['content'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yanıt Formu -->
        <?php if ($message['receiver_id'] == $currentUser['id'] && (isAdmin() || isPremium())): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= $language == 'en' ? 'Reply' : 'Yanıtla' ?></h2>
            <form action="/api/send-message.php" method="POST" class="space-y-4">
                <input type="hidden" name="recipient" value="<?= htmlspecialchars($message['sender_username']) ?>">
                <input type="hidden" name="replyToId" value="<?= $message['id'] ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Subject' : 'Konu' ?>
                    </label>
                    <input type="text" name="subject" 
                           value="RE: <?= htmlspecialchars(preg_replace('/^RE:\s*/', '', $message['subject'])) ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Message' : 'Mesaj' ?>
                    </label>
                    <textarea name="content" rows="4" 
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                              required></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i> <?= $language == 'en' ? 'Send Reply' : 'Yanıt Gönder' ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="hideMessageDetail()" 
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded">
                <?= $language == 'en' ? 'Close' : 'Kapat' ?>
            </button>
        </div>
    </div>
</main>

<?php
    require_once '../includes/footer.php';
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 