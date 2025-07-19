<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'] ?? null;

// Oturumu sonlandır
session_destroy();

// Çerezleri temizle
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// "Beni hatırla" tokenını temizle
if (isset($_COOKIE['remember_token'])) {
    // Veritabanından token kaydını sil
    if ($userId) {
        try {
            $database = new Database();
            $database->query(
                "DELETE FROM remember_tokens WHERE user_id = ? OR token = ?",
                [$userId, $_COOKIE['remember_token']]
            );
        } catch (Exception $e) {
            error_log('Çıkış yapılırken token silme hatası: ' . $e->getMessage());
        }
    }
    
    // Çerezi temizle
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Ana sayfaya yönlendir
header('Location: /');
exit;
?>