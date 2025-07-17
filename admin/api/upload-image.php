<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isUserAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Yetkisiz erişim']);
    exit;
}

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Geçersiz istek metodu']);
    exit;
}

// CSRF token kontrolü (eğer varsa)
if (function_exists('validateCSRFToken')) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token geçersiz']);
        exit;
    }
}

// Dosya kontrolü
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz dosya yükleme']);
    exit;
}

$file = $_FILES['file'];

// Hata kontrolü
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Dosya çok büyük (server limiti)',
        UPLOAD_ERR_FORM_SIZE => 'Dosya çok büyük (form limiti)',
        UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
        UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi',
        UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin bulunamadı',
        UPLOAD_ERR_CANT_WRITE => 'Dosya yazılamadı',
        UPLOAD_ERR_EXTENSION => 'PHP uzantısı dosyayı engelledi'
    ];
    
    $errorMessage = $errorMessages[$file['error']] ?? 'Bilinmeyen yükleme hatası';
    http_response_code(400);
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Dosya boyutu kontrolü
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize || $file['size'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Dosya boyutu 0 ile 5MB arasında olmalıdır']);
    exit;
}

// Dosya adı güvenlik kontrolü
$fileName = basename($file['name']);
if (empty($fileName) || strlen($fileName) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz dosya adı']);
    exit;
}

// Dosya uzantısı kontrolü
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Sadece JPG, PNG, GIF ve WebP dosyaları kabul edilir']);
    exit;
}

// MIME type kontrolü (browser tarafından gönderilen)
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedMimeTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz dosya türü']);
    exit;
}

// Gerçek dosya içeriği kontrolü
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($realMimeType, $allowedMimeTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Dosya içeriği resim formatında değil']);
    exit;
}

// Getimagesize ile ek güvenlik kontrolü
$imageInfo = getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Dosya geçerli bir resim değil']);
    exit;
}

// Resim boyutları kontrolü
if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
    http_response_code(400);
    echo json_encode(['error' => 'Resim boyutları çok büyük (maksimum 5000x5000 piksel)']);
    exit;
}

if ($imageInfo[0] < 10 || $imageInfo[1] < 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Resim boyutları çok küçük (minimum 10x10 piksel)']);
    exit;
}

try {
    // uploadImage fonksiyonunu kullan (daha güvenli)
    $fileUrl = uploadImage($file, 'uploads');
    
    // Başarılı response
    echo json_encode([
        'location' => $fileUrl,
        'success' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 