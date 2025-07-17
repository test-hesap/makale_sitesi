<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Giriş durumunu kontrol et
if (isLoggedIn()) {
    $user = getCurrentUser();
    echo json_encode([
        'loggedIn' => true,
        'username' => $user['username'],
        'isAdmin' => $user['is_admin'],
        'isPremium' => isPremium()
    ]);
} else {
    echo json_encode([
        'loggedIn' => false
    ]);
}
?> 