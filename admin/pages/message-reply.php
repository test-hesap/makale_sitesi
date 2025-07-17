<?php
// Veritabanı bağlantısı kontrolü
try {
    if (!isset($db) || !$db) {
        throw new Exception("Veritabanı bağlantısı yok");
    }
    
    // Mesaj ID kontrolü
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "<script>window.location.href = '?page=messages';</script>";
        exit;
    }
    
    $message_id = intval($_GET['id']);
    
    // Mesaj bilgilerini al
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo "<script>window.location.href = '?page=messages';</script>";
        exit;
    }
    
    // Mesajı okundu olarak işaretle
    if (!$message['is_read']) {
        $updateStmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $updateStmt->execute([$message_id]);
    }
    
    // Yanıt gönderildiyse
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
        $reply = trim($_POST['reply']);
        
        if (empty($reply)) {
            $error = "Yanıt metni boş olamaz.";
        } else {
            // Yanıtı veritabanına kaydet
            $stmt = $db->prepare("UPDATE contact_messages SET reply = ?, replied_at = NOW(), replied_by = ? WHERE id = ?");
            $stmt->execute([$reply, $_SESSION['user_id'], $message_id]);
            
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
                $mail->addAddress($message['email'], $message['name']);
                
                // İçerik
                $mail->isHTML(true);
                $mail->Subject = "RE: " . $message['subject'];
                
                $emailContent = "<html><body>";
                $emailContent .= "<p>Sayın " . htmlspecialchars($message['name']) . ",</p>";
                $emailContent .= "<p>Mesajınıza yanıtımız aşağıdadır:</p>";
                $emailContent .= "<blockquote style='border-left: 2px solid #ccc; margin: 10px 0; padding: 10px;'>";
                $emailContent .= nl2br(htmlspecialchars($reply));
                $emailContent .= "</blockquote>";
                $emailContent .= "<p>Saygılarımızla,<br>" . getSiteSetting('site_title', 'Site Yönetimi') . "</p>";
                $emailContent .= "</body></html>";
                
                $mail->Body = $emailContent;
                
                $mail->send();
                $success = "Mesaj yanıtlandı ve e-posta gönderildi.";
                
                // Başarılı ise mesaj listesine yönlendir
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '?page=messages';
                    }, 2000);
                </script>";
                
            } catch (Exception $e) {
                error_log("E-posta gönderme hatası: " . $e->getMessage());
                $error = "Mesaj yanıtlandı fakat e-posta gönderilemedi. Hata: " . $e->getMessage();
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Mesaj Yanıtla</h2>
    <a href="?page=messages" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
        <i class="fas fa-arrow-left mr-1"></i> Geri Dön
    </a>
</div>

<?php if (isset($error)): ?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<?php if (isset($success)): ?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<!-- Mesaj Detayı -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <div class="flex justify-between items-start">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            <?php echo htmlspecialchars($message['subject']); ?>
        </h3>
        
        <div class="flex items-center">
            <span class="px-3 py-1 text-xs rounded-full <?php echo $message['is_read'] ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'; ?>">
                <?php echo $message['is_read'] ? 'Okundu' : 'Okunmadı'; ?>
            </span>
        </div>
    </div>
    
    <div class="flex items-start space-x-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
        <div><strong>Gönderen:</strong> <?php echo htmlspecialchars($message['name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</div>
    </div>
    
    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-4">
        <i class="far fa-clock mr-1"></i> 
        <time datetime="<?php echo $message['created_at']; ?>"><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></time>
    </div>
    
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
        <div class="prose dark:prose-invert max-w-none">
            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
        </div>
    </div>
    
    <?php if (!empty($message['reply'])): ?>
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Yanıtınız:</h4>
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <?php echo nl2br(htmlspecialchars($message['reply'])); ?>
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <i class="far fa-clock mr-1"></i> Yanıtlandı: <?php echo date('d.m.Y H:i', strtotime($message['replied_at'])); ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Yanıt Formu -->
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Yanıt Ver</h4>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Yanıtınız
                </label>
                <textarea name="reply" rows="8" 
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 
                                 text-gray-900 dark:text-white"
                          placeholder="Mesaj yanıtınızı buraya yazın..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="?page=messages" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 
                          bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 
                          dark:hover:bg-gray-600 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 
                               rounded-md hover:bg-blue-700 transition-colors shadow-sm 
                               border border-blue-700 flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>Yanıtı Gönder
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Silme İşlemi için Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Mesajı Sil
        </h3>
        
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Bu mesajı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
        </p>
        
        <form method="POST" action="?page=messages">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 
                               bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 
                               dark:hover:bg-gray-600 transition-colors">
                    İptal
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 
                               rounded-lg hover:bg-red-700 transition-colors">
                    Evet, Sil
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mesaj İşlem Butonları -->
<div class="flex justify-between mb-6">
    <div>
        <a href="?page=messages" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i> Mesaj Listesine Dön
        </a>
    </div>
    <div class="space-x-2">
        <form method="POST" action="?page=messages" class="inline">
            <input type="hidden" name="action" value="<?php echo $message['is_read'] ? 'mark_unread' : 'mark_read'; ?>">
            <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-<?php echo $message['is_read'] ? 'envelope' : 'envelope-open'; ?> mr-1"></i>
                <?php echo $message['is_read'] ? 'Okunmadı İşaretle' : 'Okundu İşaretle'; ?>
            </button>
        </form>
        
        <button onclick="showDeleteModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-trash mr-1"></i> Mesajı Sil
        </button>
    </div>
</div>

<script>
function showDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
