<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// UTF-8 karakter kodlamasını ayarla
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// header('Content-Type: application/json; charset=utf-8'); // JSON yanıtı vermiyoruz artık

// Yanıt fonksiyonu
function redirectWithMessage($success, $message, $return_url = null) {
    $_SESSION['comment_status'] = $success;
    $_SESSION['comment_message'] = $message;
    
    // Referrer'ı al
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    if (!empty($referer)) {
        header('Location: ' . $referer);
    } else if (!empty($return_url)) {
        header('Location: ' . $return_url);
    } else {
        header('Location: /');
    }
    exit;
}

// Sadece POST isteğini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage(false, 'Sadece POST istekleri kabul edilir');
}

// Debug bilgisi
error_log("POST verileri: " . print_r($_POST, true));
error_log("Session bilgisi: " . print_r($_SESSION, true));

// Return URL'i al
$return_url = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'];

// Giriş kontrolü
if (!isLoggedIn()) {
    error_log("Kullanıcı giriş yapmamış");
    redirectWithMessage(false, 'Yorum yapmak için giriş yapmanız gerekiyor', $return_url);
}

// CSRF token kontrolü
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWithMessage(false, 'Geçersiz CSRF token', $return_url);
}

// Veri doğrulama
$article_id = (int)($_POST['article_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($article_id <= 0) {
    redirectWithMessage(false, 'Geçersiz makale ID', $return_url);
}

if (empty($content)) {
    redirectWithMessage(false, 'Yorum içeriği boş olamaz', $return_url);
}

if (strlen($content) < 10) {
    redirectWithMessage(false, 'Yorum en az 10 karakter olmalıdır', $return_url);
}

if (strlen($content) > 1000) {
    redirectWithMessage(false, 'Yorum en fazla 1000 karakter olabilir', $return_url);
}

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Makale varlığını kontrol et
    $articleQuery = "SELECT id, title FROM articles WHERE id = ? AND status = 'published'";
    $articleStmt = $db->prepare($articleQuery);
    $articleStmt->execute([$article_id]);
    $article = $articleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        redirectWithMessage(false, 'Makale bulunamadı', $return_url);
    }
    
    // Kullanıcının son yorumunu kontrol et (spam kontrolü)
    $user_id = $_SESSION['user_id'] ?? 0;
    if (!$user_id) {
        redirectWithMessage(false, 'Oturum bilgisi bulunamadı', $return_url);
    }

    // Admin değilse spam kontrolü yap
    if (!isAdmin()) {
        $lastCommentQuery = "SELECT created_at FROM comments 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 1";
        $lastCommentStmt = $db->prepare($lastCommentQuery);
        $lastCommentStmt->execute([$user_id]);
        $lastComment = $lastCommentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastComment) {
            $lastCommentTime = strtotime($lastComment['created_at']);
            $currentTime = time();
            $timeDiff = $currentTime - $lastCommentTime;
            
            // Son yorumdan sonra en az 30 saniye geçmiş olmalı
            if ($timeDiff < 30) {
                            redirectWithMessage(false, 'Çok hızlı yorum yapıyorsunuz. Lütfen 30 saniye bekleyin', $return_url);
            }
        }
    }
    
    // İçerik filtresi (basit kötü kelime kontrolü)
    $badWords = ['spam', 'reklam', 'fuck', 'shit']; // Gerçek uygulamada daha kapsamlı olmalı
    $contentLower = strtolower($content);
    foreach ($badWords as $word) {
        if (strpos($contentLower, $word) !== false) {
            redirectWithMessage(false, 'Yorumunuz uygunsuz içerik barındırıyor', $return_url);
        }
    }
    
    // Yorumu veritabanına ekle
    // Admin yorumları otomatik onaylanır
    $is_approved = isAdmin() ? 1 : 0;
    $insertQuery = "INSERT INTO comments (article_id, user_id, content, is_approved, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
    $insertStmt = $db->prepare($insertQuery);
    
    try {
        $insertStmt->execute([$article_id, $user_id, $content, $is_approved]);
        $comment_id = $db->lastInsertId();
    } catch (PDOException $e) {
        error_log('Comment insert error: ' . $e->getMessage());
        redirectWithMessage(false, 'Yorum kaydedilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.', $return_url);
    }
    
    // Admin bildirim e-postası gönder (opsiyonel)
    try {
        $adminEmail = getSiteSetting('admin_email');
        if (!isAdmin() && $adminEmail && getSiteSetting('notify_new_comments') == '1') {
            $subject = 'Yeni Yorum - ' . getSiteSetting('site_name');
            $message = "Yeni bir yorum onay bekliyor:\n\n";
            $message .= "Makale: " . $article['title'] . "\n";
            $message .= "Kullanıcı: " . ($_SESSION['username'] ?? 'Bilinmiyor') . "\n";
            $message .= "İçerik: " . $content . "\n\n";
            $message .= "Yorumu onaylamak için admin paneline giriş yapın.";
            
            sendEmail($adminEmail, $subject, $message);
        }
    } catch (Exception $e) {
        error_log('Comment notification email error: ' . $e->getMessage());
        // E-posta gönderimi başarısız olsa bile yorumu kaydettik, devam edebiliriz
    }
    
    $successMessage = isAdmin() ? 'Yorumunuz başarıyla gönderildi.' : 'Yorumunuz başarıyla gönderildi. Admin onayından sonra yayınlanacak.';
    redirectWithMessage(true, $successMessage, $return_url);
    
} catch (PDOException $e) {
    error_log('Database error in add-comment.php: ' . $e->getMessage());
    redirectWithMessage(false, 'Veritabanı işlemi sırasında bir hata oluştu', $return_url);
} catch (Exception $e) {
    error_log('General error in add-comment.php: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    redirectWithMessage(false, 'Yorum gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin', $return_url);
} 