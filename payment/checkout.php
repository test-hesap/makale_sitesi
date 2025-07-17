<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Dil ayarını al
$language = getCurrentLanguage();

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Plan ID kontrolü
$planId = intval($_GET['plan'] ?? 0);
if ($planId <= 0) {
    header('Location: ../premium.php?error=invalid_plan');
    exit;
}

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Plan bilgilerini getir
    $stmt = $db->prepare("
        SELECT * FROM subscription_plans 
        WHERE id = ? AND is_active = 1
    ");
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
        header('Location: ../premium.php?error=plan_not_found');
        exit;
    }
    
    // Kullanıcı bilgilerini al
    $currentUser = getCurrentUser();
    
    // Aktif abonelik kontrolü
    $stmt = $db->prepare("
        SELECT us.*, sp.price as plan_price 
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.end_date > NOW() AND us.status = 'active'
        ORDER BY us.end_date DESC LIMIT 1
    ");
    $stmt->execute([$currentUser['id']]);
    $activeSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eğer aktif abonelik varsa ve seçilen plan daha düşük veya eşit fiyatlıysa
    if ($activeSubscription) {
        $currentPlanPrice = floatval($activeSubscription['plan_price']);
        $selectedPlanPrice = floatval($plan['price']);
        
        if ($selectedPlanPrice <= $currentPlanPrice) {
            header('Location: ../premium.php?error=cannot_downgrade');
            exit;
        }
    }
    
    $pageTitle = ($language == 'en' ? 'Payment - ' : 'Ödeme - ') . $plan['name_translated'];
    
    require_once '../includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <?= htmlspecialchars($plan['name_translated']) ?> <?= $language == 'en' ? 'Plan - Payment' : 'Plan - Ödeme' ?>
            </h1>
            
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400"><?= $language == 'en' ? 'Plan:' : 'Plan:' ?></span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        <?= htmlspecialchars($plan['name_translated']) ?>
                    </span>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400"><?= $language == 'en' ? 'Duration:' : 'Süre:' ?></span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        <?= $plan['duration_months'] ?> <?= $language == 'en' ? ($plan['duration_months'] > 1 ? 'Months' : 'Month') : 'Ay' ?>
                    </span>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400"><?= $language == 'en' ? 'Amount:' : 'Tutar:' ?></span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        <?= number_format($plan['price'], 2) ?> TL
                    </span>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                            <?= $language == 'en' ? 'Total:' : 'Toplam:' ?>
                        </span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            <?= number_format($plan['price'], 2) ?> TL
                        </span>
                    </div>
                </div>
            </div>
            
            <form action="/payment/process.php" method="POST" class="space-y-6">
                <input type="hidden" name="plan_id" value="<?= $planId ?>">
                
                <div>
                    <label for="card_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Name on Card' : 'Kart Üzerindeki İsim' ?>
                    </label>
                    <input type="text" id="card_name" name="card_name" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?= $language == 'en' ? 'Enter the name on your card' : 'Kart üzerindeki ismi yazın' ?>">
                </div>
                
                <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= $language == 'en' ? 'Card Number' : 'Kart Numarası' ?>
                    </label>
                    <input type="text" id="card_number" name="card_number" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="1234 5678 9012 3456"
                           pattern="\d{4}\s?\d{4}\s?\d{4}\s?\d{4}"
                           maxlength="19">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="expiry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= $language == 'en' ? 'Expiry Date' : 'Son Kullanma Tarihi' ?>
                        </label>
                        <input type="text" id="expiry" name="expiry" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="<?= $language == 'en' ? 'MM/YY' : 'AA/YY' ?>"
                               pattern="\d{2}/\d{2}"
                               maxlength="5">
                    </div>
                    
                    <div>
                        <label for="cvv" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= $language == 'en' ? 'Security Code (CVV)' : 'Güvenlik Kodu (CVV)' ?>
                        </label>
                        <input type="text" id="cvv" name="cvv" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="123"
                               pattern="\d{3,4}"
                               maxlength="4">
                    </div>
                </div>
                
                <div class="pt-4">
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                        <?= $language == 'en' ? 'Pay ' . number_format($plan['price'], 2) . ' TL' : number_format($plan['price'], 2) . ' TL Öde' ?>
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                <p>
                    <i class="fas fa-lock mr-1"></i>
                    <?= $language == 'en' ? 'Your payment is securely encrypted with 256-bit SSL.' : 'Ödemeniz 256-bit SSL ile güvenle şifrelenmektedir.' ?>
                </p>
            </div>
        </div>
    </div>
</main>

<script>
// Kart numarası formatı
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formattedValue = '';
    
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) {
            formattedValue += ' ';
        }
        formattedValue += value[i];
    }
    
    e.target.value = formattedValue;
});

// Son kullanma tarihi formatı
document.getElementById('expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length > 2) {
        value = value.substr(0, 2) + '/' + value.substr(2);
    }
    
    e.target.value = value;
});
</script>

<?php
    require_once '../includes/footer.php';
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /premium.php?error=system_error');
    exit;
}
?> 