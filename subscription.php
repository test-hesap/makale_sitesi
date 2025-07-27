<?php
// Giriş kontrolü
session_start();
require_once 'includes/functions.php';  // functions.php dosyasını içe aktar (isLoggedIn gibi fonksiyonlar için)

if (!isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

$currentUser = getCurrentUser();

// Kullanıcının premium üye olup olmadığını kontrol et
if (!$currentUser['is_premium']) {
    header('Location: /premium');
    exit;
}

// Tüm yönlendirme kontrolleri tamamlandıktan sonra header'ı içe aktar
$language = getCurrentLanguage();
$pageTitle = ($language == 'en' ? 'My Subscription - ' : 'Aboneliğim - ') . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'Manage your premium subscription, cancel or upgrade.' : 'Premium aboneliğinizi yönetin, iptal edin veya yükseltin.';

require_once 'includes/header.php';

$userId = $currentUser['id'];
$success = '';
$error = '';

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Aktif aboneliği al
    $stmt = $db->prepare("
        SELECT us.*, sp.name as plan_name, sp.price as plan_price, 
               sp.duration_months, sp.features
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.status = 'active' 
        AND us.end_date > NOW()
        ORDER BY us.end_date DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Plan adını dil seçimine göre ayarla
    if ($language == 'en' && !empty($subscription)) {
        // Türkçe plan adlarını İngilizce'ye çevir
        $planName = $subscription['plan_name'];
        if ($planName == 'Aylık Premium') {
            $subscription['name_translated'] = 'Monthly Premium';
        } elseif ($planName == 'Yıllık Premium') {
            $subscription['name_translated'] = 'Annual Premium';
        } elseif ($planName == '3 Aylık Premium') {
            $subscription['name_translated'] = '3 Month Premium';
        } elseif ($planName == '6 Aylık Premium') {
            $subscription['name_translated'] = '6 Month Premium';
        } else {
            $subscription['name_translated'] = $planName; // Çevirisi olmayan plan adları için orijinali kullan
        }
    } else if (!empty($subscription)) {
        $subscription['name_translated'] = $subscription['plan_name']; // Türkçe dil seçiminde orijinal adı kullan
    }
    
    // Diğer plan seçeneklerini getir (üst planları)
    if (!empty($subscription)) {
        $stmt = $db->prepare("
            SELECT sp.*
            FROM subscription_plans sp
            WHERE sp.is_active = 1 
            AND sp.price > ? 
            ORDER BY sp.price ASC
        ");
        $stmt->execute([$subscription['plan_price']]);
        $upgradePlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Plan adlarını dil seçimine göre ayarla
        if ($language == 'en') {
            foreach ($upgradePlans as &$plan) {
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
                    $plan['name_translated'] = $planName;
                }
            }
            unset($plan); // Referansı temizle
        } else {
            foreach ($upgradePlans as &$plan) {
                $plan['name_translated'] = $plan['name'];
            }
            unset($plan); // Referansı temizle
        }
    }
    
    // Abonelik iptali işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'cancel_subscription') {
            if (!$subscription) {
                $error = $language == 'en' ? 'You do not have an active subscription.' : 'Aktif bir aboneliğiniz bulunmuyor.';
            } else {
                // Doğrulama kontrolü (kullanıcı şifresini ister)
                $password = $_POST['password'] ?? '';
                
                if (empty($password)) {
                    $error = $language == 'en' ? 'Please enter your password to confirm cancellation.' : 'İptal işlemini onaylamak için şifrenizi girin.';
                } elseif (!password_verify($password, $currentUser['password'])) {
                    $error = $language == 'en' ? 'Password is incorrect.' : 'Şifre yanlış.';
                } else {
                    // İptal işlemini gerçekleştir
                    $db->beginTransaction();
                    
                    try {
                        // Hemen sonlandırma seçeneği işaretlendi mi?
                        $immediateTermination = isset($_POST['immediate_termination']) && $_POST['immediate_termination'] == 1;
                        
                        $updateFields = [
                            "status = 'cancelled'",
                            "cancelled_at = NOW()",
                            "updated_at = NOW()"
                        ];
                        
                        // Eğer hemen sonlandırma seçeneği seçildiyse, bitiş tarihini şimdi olarak ayarla
                        if ($immediateTermination) {
                            $updateFields[] = "end_date = NOW()";
                        }
                        
                        // Aboneliği iptal et
                        $stmt = $db->prepare("
                            UPDATE user_subscriptions 
                            SET " . implode(", ", $updateFields) . "
                            WHERE id = ?
                        ");
                        $stmt->execute([$subscription['id']]);
                        
                        // Kullanıcının premium durumunu güncelle
                        if ($immediateTermination) {
                            // Kullanıcının başka aktif aboneliği var mı kontrol et
                            $checkStmt = $db->prepare("
                                SELECT COUNT(*) FROM user_subscriptions 
                                WHERE user_id = ? AND status = 'active' AND end_date > NOW()
                            ");
                            $checkStmt->execute([$userId]);
                            $hasActiveSubscription = $checkStmt->fetchColumn() > 0;
                            
                            if (!$hasActiveSubscription) {
                                // Başka aktif abonelik yoksa premium durumunu kaldır
                                $userUpdateStmt = $db->prepare("
                                    UPDATE users 
                                    SET is_premium = 0, 
                                        premium_expires_at = NULL 
                                    WHERE id = ?
                                ");
                                $userUpdateStmt->execute([$userId]);
                            }
                            
                            $success = $language == 'en' ? 
                                'Your subscription has been cancelled and terminated immediately. Premium access has been removed.' : 
                                'Aboneliğiniz iptal edildi ve hemen sonlandırıldı. Premium erişiminiz kaldırıldı.';
                        } else {
                            $success = $language == 'en' ? 
                                'Your subscription has been cancelled. You can continue to use premium features until ' . date('d.m.Y', strtotime($subscription['end_date'])) . '.' : 
                                'Aboneliğiniz iptal edildi. ' . date('d.m.Y', strtotime($subscription['end_date'])) . ' tarihine kadar premium özellikleri kullanmaya devam edebilirsiniz.';
                        }
                        
                        $db->commit();
                        
                        // İşlem başarılı olduğunda abonelik durumunu güncelle
                        // Böylece sayfa yenilenmeden mevcut durumu görebiliriz
                        $subscription['status'] = 'cancelled';
                        if ($immediateTermination) {
                            $subscription['end_date'] = date('Y-m-d H:i:s');
                        }
                        
                        // Başarı mesajı zaten ayarlanmış durumda, çıktıya ekleyeceğiz
                        // Yönlendirme yapmak yerine JavaScript ile sayfayı yenileme işaretini ayarla
                        $showRefreshNotice = true;
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = $language == 'en' ? 'An error occurred while cancelling your subscription.' : 'Aboneliğiniz iptal edilirken bir hata oluştu.';
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    $error = $language == 'en' ? 'An error occurred while retrieving subscription information.' : 'Abonelik bilgileri alınırken bir hata oluştu.';
    $subscription = null;
    $upgradePlans = [];
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="text-sm breadcrumbs mb-6">
            <ol class="flex items-center space-x-2">
                <li><a href="/" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"><?= $language == 'en' ? 'Home' : 'Ana Sayfa' ?></a></li>
                <li class="text-gray-400">/</li>
                <li><a href="/profile" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"><?= $language == 'en' ? 'My Profile' : 'Profilim' ?></a></li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-600 dark:text-gray-300"><?= $language == 'en' ? 'My Subscription' : 'Aboneliğim' ?></li>
            </ol>
        </nav>

        <?php if ($success): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?= $success ?>
            <?php if (isset($showRefreshNotice) && $showRefreshNotice): ?>
            <div class="mt-2 text-sm">
                <p><?= $language == 'en' ? 'The page will refresh automatically in 3 seconds...' : 'Sayfa 3 saniye içinde otomatik olarak yenilenecek...' ?></p>
                <script>
                    setTimeout(function() {
                        window.location.href = '/subscription.php';
                    }, 3000);
                </script>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-crown text-yellow-500 mr-2"></i>
                <?= $language == 'en' ? 'My Premium Subscription' : 'Premium Aboneliğim' ?>
            </h1>
            
            <?php if ($subscription): ?>
            <div class="mb-6">
                <div class="flex items-center justify-center p-4 bg-blue-50 dark:bg-blue-900 rounded-lg mb-4">
                    <div class="text-center">
                        <h2 class="text-xl font-semibold text-blue-600 dark:text-blue-400 mb-1">
                            <?= htmlspecialchars($subscription['name_translated']) ?>
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            <?= $language == 'en' ? 'Active Plan' : 'Aktif Plan' ?>
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                            <?= $language == 'en' ? 'Subscription Details' : 'Abonelik Detayları' ?>
                        </h3>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                            <li>
                                <span class="font-medium"><?= $language == 'en' ? 'Plan:' : 'Plan:' ?></span> 
                                <?= htmlspecialchars($subscription['name_translated']) ?>
                            </li>
                            <li>
                                <span class="font-medium"><?= $language == 'en' ? 'Price:' : 'Ücret:' ?></span> 
                                <?= number_format($subscription['plan_price'], 2) ?> ₺
                                <?php if ($subscription['duration_months'] > 0): ?>
                                    / <?= $subscription['duration_months'] == 1 ? 
                                        ($language == 'en' ? 'month' : 'ay') : 
                                        ($language == 'en' ? $subscription['duration_months'] . ' months' : $subscription['duration_months'] . ' ay') ?>
                                <?php endif; ?>
                            </li>
                            <li>
                                <span class="font-medium"><?= $language == 'en' ? 'Start Date:' : 'Başlangıç Tarihi:' ?></span> 
                                <?= date('d.m.Y', strtotime($subscription['start_date'])) ?>
                            </li>
                            <li>
                                <span class="font-medium"><?= $language == 'en' ? 'End Date:' : 'Bitiş Tarihi:' ?></span> 
                                <?= date('d.m.Y', strtotime($subscription['end_date'])) ?>
                            </li>
                            <li>
                                <span class="font-medium"><?= $language == 'en' ? 'Status:' : 'Durum:' ?></span> 
                                <?php if ($subscription['status'] == 'active'): ?>
                                    <span class="text-green-600 dark:text-green-400">
                                        <?= $language == 'en' ? 'Active' : 'Aktif' ?>
                                    </span>
                                <?php elseif ($subscription['status'] == 'cancelled'): ?>
                                    <span class="text-yellow-600 dark:text-yellow-400">
                                        <?= $language == 'en' ? 'Cancelled' : 'İptal Edildi' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <?= $language == 'en' ? 'Unknown' : 'Bilinmiyor' ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                            <?= $language == 'en' ? 'Plan Features' : 'Plan Özellikleri' ?>
                        </h3>
                        <ul class="space-y-2">
                            <?php 
                            if (!empty($subscription['features'])) {
                                $features = explode("\n", $subscription['features']);
                                foreach ($features as $feature): 
                                    // Feature çevirilerini tanımla
                                    $featureText = trim($feature);
                                    $translatedFeature = $featureText;
                                    
                                    // Türkçe feature'ları İngilizce'ye çevir
                                    if ($language == 'en') {
                                        if ($featureText == 'Tüm premium makalelere erişim') {
                                            $translatedFeature = 'Access to all premium articles';
                                        } elseif ($featureText == 'Reklamsız deneyim') {
                                            $translatedFeature = 'Ad-free experience';
                                        } elseif ($featureText == 'Öncelikli destek') {
                                            $translatedFeature = 'Priority support';
                                        } elseif ($featureText == 'PDF indirme') {
                                            $translatedFeature = 'PDF downloads';
                                        } elseif ($featureText == '%17 indirim') {
                                            $translatedFeature = '17% discount';
                                        }
                                    }
                                ?>
                                <li class="flex items-center text-gray-600 dark:text-gray-300">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    <?= htmlspecialchars($translatedFeature) ?>
                                </li>
                            <?php endforeach; 
                            } ?>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-6">
                    <?php if ($subscription['status'] == 'active'): ?>
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= $language == 'en' ? 'Cancel Subscription' : 'Aboneliği İptal Et' ?>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                <?= $language == 'en' ? 
                                    'If you cancel your subscription, you will continue to have access to premium features until ' . date('d.m.Y', strtotime($subscription['end_date'])) . '.' : 
                                    'Aboneliğinizi iptal ederseniz, ' . date('d.m.Y', strtotime($subscription['end_date'])) . ' tarihine kadar premium özelliklere erişiminiz devam edecektir.' ?>
                            </p>
                            <button onclick="showCancellationModal()" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <i class="fas fa-times-circle mr-2"></i> <?= $language == 'en' ? 'Cancel Subscription' : 'Aboneliği İptal Et' ?>
                            </button>
                        </div>
                        
                        <?php if (!empty($upgradePlans)): ?>
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= $language == 'en' ? 'Upgrade Your Plan' : 'Planınızı Yükseltin' ?>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                <?= $language == 'en' ? 
                                    'You can upgrade to a higher plan at any time. You will only pay the difference.' : 
                                    'Dilediğiniz zaman daha yüksek bir plana geçiş yapabilirsiniz. Sadece aradaki farkı ödersiniz.' ?>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($upgradePlans as $plan): ?>
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                                        <?= htmlspecialchars($plan['name_translated']) ?>
                                    </h4>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                        <?= number_format($plan['price'], 2) ?> ₺
                                    </div>
                                    <a href="/payment/checkout.php?plan=<?= $plan['id'] ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg">
                                        <?= $language == 'en' ? 'Upgrade' : 'Yükselt' ?>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($subscription['status'] == 'cancelled'): ?>
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= $language == 'en' ? 'Subscription Status' : 'Abonelik Durumu' ?>
                            </h3>
                            <div class="bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 text-yellow-700 dark:text-yellow-200 px-4 py-3 rounded">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?= $language == 'en' ? 
                                    'Your subscription has been cancelled. You can continue to use premium features until ' . date('d.m.Y', strtotime($subscription['end_date'])) . '. After this date, your account will be converted to a standard account.' : 
                                    'Aboneliğiniz iptal edilmiştir. ' . date('d.m.Y', strtotime($subscription['end_date'])) . ' tarihine kadar premium özellikleri kullanmaya devam edebilirsiniz. Bu tarihten sonra hesabınız standart hesaba dönüştürülecektir.' ?>
                            </div>
                            <a href="/premium" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-arrow-right mr-2"></i> <?= $language == 'en' ? 'View Subscription Plans' : 'Abonelik Planlarını Görüntüle' ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center p-6">
                <i class="fas fa-exclamation-circle text-yellow-500 text-4xl mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    <?= $language == 'en' ? 
                        'No active subscription found. If you believe this is an error, please contact support.' : 
                        'Aktif abonelik bulunamadı. Bunun bir hata olduğunu düşünüyorsanız, lütfen destek ekibiyle iletişime geçin.' ?>
                </p>
                <a href="/premium" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-right mr-2"></i> <?= $language == 'en' ? 'View Subscription Plans' : 'Abonelik Planlarını Görüntüle' ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <?= $language == 'en' ? 'Frequently Asked Questions' : 'Sıkça Sorulan Sorular' ?>
            </h2>
            
            <div class="space-y-4">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                        <?= $language == 'en' ? 'What happens if I cancel my subscription?' : 'Aboneliğimi iptal edersem ne olur?' ?>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?= $language == 'en' ? 
                            'If you cancel your subscription, you will still have access to premium features until the end of your current billing period. After that, your account will be converted to a standard account.' : 
                            'Aboneliğinizi iptal ederseniz, mevcut fatura döneminin sonuna kadar premium özelliklere erişiminiz devam edecektir. Bu sürenin sonunda hesabınız standart hesaba dönüştürülecektir.' ?>
                    </p>
                </div>
                
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                        <?= $language == 'en' ? 'Can I get a refund after cancellation?' : 'İptal sonrası geri ödeme alabilir miyim?' ?>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?= $language == 'en' ? 
                            'According to our refund policy, we do not offer refunds for cancelled subscriptions. You will continue to have access until the end of the billing period.' : 
                            'İade politikamıza göre, iptal edilen abonelikler için geri ödeme yapmıyoruz. Fatura döneminin sonuna kadar erişiminiz devam edecektir.' ?>
                    </p>
                </div>
                
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                        <?= $language == 'en' ? 'How can I upgrade my subscription?' : 'Aboneliğimi nasıl yükseltebilirim?' ?>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?= $language == 'en' ? 
                            'You can upgrade your subscription at any time by selecting a higher-tier plan from this page. You will only be charged the difference between your current plan and the new plan.' : 
                            'Bu sayfadan daha yüksek bir plan seçerek aboneliğinizi dilediğiniz zaman yükseltebilirsiniz. Sadece mevcut planınız ile yeni plan arasındaki fark kadar ücretlendirilirsiniz.' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- İptal Modal -->
<div id="cancellationModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <?= $language == 'en' ? 'Cancel Subscription' : 'Abonelik İptali' ?>
        </h3>
        
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            <?= $language == 'en' ? 
                'Are you sure you want to cancel your subscription? You will still have access to premium features until ' . date('d.m.Y', strtotime($subscription['end_date'] ?? 'now')) . '.' : 
                'Aboneliğinizi iptal etmek istediğinize emin misiniz? ' . date('d.m.Y', strtotime($subscription['end_date'] ?? 'now')) . ' tarihine kadar premium özelliklere erişiminiz devam edecektir.' ?>
        </p>
        
        <form method="POST" class="mb-4">
            <input type="hidden" name="action" value="cancel_subscription">
            
            <div class="mb-4">
                <div class="flex items-start mb-4">
                    <div class="flex items-center h-5">
                        <input id="immediate_termination" name="immediate_termination" type="checkbox" value="1" 
                               class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="immediate_termination" class="font-medium text-gray-700 dark:text-gray-300">
                            <?= $language == 'en' ? 'Terminate subscription immediately' : 'Aboneliği hemen sonlandır' ?>
                        </label>
                        <p class="text-gray-500 dark:text-gray-400">
                            <?= $language == 'en' ? 'If checked, your premium access will be removed immediately.' : 'Bu seçenek işaretlenirse, premium erişiminiz hemen kaldırılacaktır.' ?>
                        </p>
                    </div>
                </div>
            
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <?= $language == 'en' ? 'Enter your password to confirm:' : 'Onaylamak için şifrenizi girin:' ?>
                </label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-red-500 focus:border-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <style>
                    /* Firefox tarayıcısında otomatik doldurma sarı arka plan rengini engelleme */
                    input:-webkit-autofill,
                    input:-webkit-autofill:hover,
                    input:-webkit-autofill:focus,
                    input:-webkit-autofill:active {
                        -webkit-box-shadow: 0 0 0 30px white inset !important;
                    }
                    
                    @media (prefers-color-scheme: dark) {
                        input:-webkit-autofill,
                        input:-webkit-autofill:hover,
                        input:-webkit-autofill:focus,
                        input:-webkit-autofill:active {
                            -webkit-box-shadow: 0 0 0 30px #374151 inset !important;
                            -webkit-text-fill-color: white !important;
                        }
                    }
                </style>
            </div>
            
            <div class="flex items-center justify-between">
                <button type="button" onclick="hideCancellationModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md">
                    <?= $language == 'en' ? 'Cancel' : 'Vazgeç' ?>
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                    <?= $language == 'en' ? 'Confirm Cancellation' : 'İptali Onayla' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCancellationModal() {
    document.getElementById('cancellationModal').classList.remove('hidden');
}

function hideCancellationModal() {
    document.getElementById('cancellationModal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer.php'; ?>
