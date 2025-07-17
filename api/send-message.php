<?php
// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/functions.php';

// Gelen verileri logla
error_log('POST verileri: ' . print_r($_POST, true));

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Oturum açmanız gerekiyor'
    ]);
    exit;
}

// POST verilerini al
$recipient = trim($_POST['recipient'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$content = trim($_POST['content'] ?? '');
$replyToId = intval($_POST['replyToId'] ?? 0);

// Gelen verileri kontrol et
error_log("Alıcı: $recipient");
error_log("Konu: $subject");
error_log("İçerik: $content");

if (empty($recipient) || empty($subject) || empty($content)) {
    header('Content-Type: application/json');
    $error = [];
    if (empty($recipient)) $error[] = 'alıcı';
    if (empty($subject)) $error[] = 'konu';
    if (empty($content)) $error[] = 'mesaj';
    
    echo json_encode([
        'success' => false,
        'error' => 'Lütfen şu alanları doldurun: ' . implode(', ', $error)
    ]);
    exit;
}

$currentUser = getCurrentUser();
$senderId = $currentUser['id'];

// Kullanıcının onaylanmış olup olmadığını kontrol et
if (!$currentUser['is_approved']) {
    $language = getCurrentLanguage();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $language == 'en' ? 'You cannot send messages until your account is approved.' : 'Hesabınız onaylanana kadar mesaj gönderemezsiniz.'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Alıcı kullanıcıyı bul
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ?");
    $stmt->execute([$recipient]);
    $receiverUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $receiverId = $receiverUser['id'] ?? null;
    
    error_log("Alıcı ID: " . ($receiverId ?: 'bulunamadı'));
    
    if (!$receiverId) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => "\"$recipient\" adlı kullanıcı bulunamadı"
        ]);
        exit;
    }
    
    // Kendine mesaj göndermeyi engelle
    if ($senderId == $receiverId) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Kendinize mesaj gönderemezsiniz'
        ]);
        exit;
    }
    
    // Yanıtlanan mesajın bilgilerini al
    if ($replyToId) {
        $stmt = $db->prepare("
            SELECT sender_id, receiver_id 
            FROM messages 
            WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
        ");
        $stmt->execute([$replyToId, $senderId, $senderId]);
        $originalMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$originalMessage) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Yanıtlanacak mesaj bulunamadı'
            ]);
            exit;
        }
    }
    
    // Yeni mesajı kaydet
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, subject, content, reply_to_id, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $senderId,
        $receiverId,
        $subject,
        $content,
        $replyToId ?: null
    ]);
    
    if ($result) {
        // HTML başlıkları
        require_once '../includes/header.php';
        $language = getCurrentLanguage();
        ?>
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    <?= $language == 'en' ? 'Message Sent: ' : 'Mesaj Gönder: ' ?><?= htmlspecialchars($receiverUser['username']) ?>
                </h1>
                
                <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
                    <p class="text-green-700 dark:text-green-200">
                        <?= $language == 'en' ? 'Your message has been sent successfully.' : 'Mesajınız başarıyla gönderildi.' ?>
                    </p>
                </div>
                
                <div class="flex gap-4">
                    <a href="/mesajlar" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <?= $language == 'en' ? 'Go to my messages' : 'Mesajlarım sayfasına git' ?>
                    </a>
                    <a href="/uye/<?= htmlspecialchars($receiverUser['username']) ?>" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                        <?= $language == 'en' ? 'Return to ' . htmlspecialchars($receiverUser['username']) . '\'s profile' : htmlspecialchars($receiverUser['username']) . ' profiline dön' ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        require_once '../includes/footer.php';
    } else {
        $error = $stmt->errorInfo();
        error_log('SQL Hatası: ' . print_r($error, true));
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Veritabanı hatası: ' . $error[2]
        ]);
    }
    
} catch (Exception $e) {
    error_log('Hata: ' . $e->getMessage());
    error_log('Hata detayı: ' . print_r($e->getTrace(), true));
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Mesaj gönderilirken bir hata oluştu: ' . $e->getMessage()
    ]);
}
?> 