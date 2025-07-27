<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Dil ayarını al
$language = getCurrentLanguage();

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /premium.php?error=invalid_request');
    exit;
}

$planId = intval($_POST['plan_id'] ?? 0);
$cardName = trim($_POST['card_name'] ?? '');
$cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
$expiry = trim($_POST['expiry'] ?? '');
$cvv = trim($_POST['cvv'] ?? '');

// Basit doğrulama
if ($planId <= 0 || empty($cardName) || strlen($cardNumber) !== 16 || !preg_match('/^\d{2}\/\d{2}$/', $expiry) || !preg_match('/^\d{3,4}$/', $cvv)) {
    header('Location: /payment/checkout.php?plan=' . $planId . '&error=invalid_form');
    exit;
}

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Plan bilgilerini getir
    $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Plan adını dil seçimine göre ayarla
    if ($language == 'en' && !empty($plan)) {
        // Türkçe plan adlarını İngilizce'ye çevir
        $planName = $plan['name'];
        if ($planName == 'Aylık Premium') {
            $plan['name_translated'] = 'Monthly Premium';
        } elseif ($planName == 'Yıllık Premium') {
            $plan['name_translated'] = 'Annual Premium';
        } elseif ($planName == '3 Aylık Premium') {
            $plan['name_translated'] = '3 Month Premium';
        } elseif ($planName == '6 Aylık Premium') {
            $plan['name_translated'] = '6 Month Premium';
        } else {
            $plan['name_translated'] = $plan['name']; // Çevirisi olmayan plan adları için orijinali kullan
        }
    } else {
        $plan['name_translated'] = $plan['name']; // Türkçe dil seçiminde orijinal adı kullan
    }
    
    if (!$plan) {
        header('Location: /premium.php?error=plan_not_found');
        exit;
    }
    
    // Kullanıcı bilgilerini al
    $currentUser = getCurrentUser();
    
    // Aktif abonelik kontrolü
    $stmt = $db->prepare("
        SELECT us.*, sp.price as plan_price, sp.name as plan_name
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.end_date > NOW() 
        AND us.status = 'active'
        ORDER BY us.end_date DESC LIMIT 1
    ");
    $stmt->execute([$currentUser['id']]);
    $activeSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

    // Eğer aktif abonelik varsa ve seçilen plan daha düşük veya eşit fiyatlıysa
    if ($activeSubscription) {
        $currentPlanPrice = floatval($activeSubscription['plan_price']);
        $selectedPlanPrice = floatval($plan['price']);
        
        if ($selectedPlanPrice <= $currentPlanPrice) {
            header('Location: /premium.php?error=cannot_downgrade');
            exit;
        }
    }
    
    // Ödeme işlemi simülasyonu
    $paymentSuccessful = true;
    
    if ($paymentSuccessful) {
        $db->beginTransaction();
        
        try {
            // Abonelik başlangıç ve bitiş tarihlerini hesapla
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $plan['duration_months'] . ' months'));
            
            // Ödeme kaydını oluştur
            $stmt = $db->prepare("
                INSERT INTO payments (user_id, plan_id, amount, payment_date, status, payment_method) 
                VALUES (?, ?, ?, NOW(), 'completed', 'credit_card')
            ");
            $stmt->execute([$currentUser['id'], $plan['id'], $plan['price']]);
            
            $paymentId = $db->lastInsertId();
            
            // Varolan aktif ve iptal edilmiş abonelikleri sonlandır
            $stmt = $db->prepare("
                UPDATE user_subscriptions 
                SET status = 'expired', end_date = NOW()
                WHERE user_id = ? AND (status = 'active' OR status = 'cancelled')
            ");
            $stmt->execute([$currentUser['id']]);
            
            // Yeni aboneliği ekle
            $stmt = $db->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, payment_id, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$currentUser['id'], $plan['id'], $paymentId, $startDate, $endDate]);
            
            // Kullanıcıyı premium yap
            $stmt = $db->prepare("
                UPDATE users 
                SET is_premium = 1,
                    premium_expires_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$endDate, $currentUser['id']]);
            
            $db->commit();
            
            // Başarılı sayfasına yönlendir
            header('Location: /premium.php?success=payment_completed');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
            header('Location: /payment/checkout.php?plan=' . $planId . '&error=system_error');
            exit;
        }
    } else {
        header('Location: /payment/checkout.php?plan=' . $planId . '&error=payment_failed');
        exit;
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /payment/checkout.php?plan=' . $planId . '&error=system_error');
    exit;
} 