<?php
require_once 'includes/functions.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login?redirect=' . urlencode('/premium'));
    exit;
}

// Admin kontrolü
if (isAdmin()) {
    header('Location: /');
    exit;
}

// Dil ayarını al
$language = getCurrentLanguage();

require_once 'includes/header.php';

$pageTitle = ($language == 'en' ? 'Premium Membership - ' : 'Premium Üyelik - ') . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'Access special content with premium membership, enjoy ad-free experience and discover more.' : 'Premium üyelik ile özel içeriklere erişin, reklamsız deneyim yaşayın ve daha fazlasını keşfedin.';

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Abonelik planlarını getir
    $stmt = $db->prepare("
        SELECT * 
        FROM subscription_plans 
        WHERE is_active = 1 
        AND price > 0
        ORDER BY price ASC
    ");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Plan adlarını dil seçimine göre ayarla
    if ($language == 'en') {
        foreach ($plans as &$plan) {
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
        }
        unset($plan); // Referansı temizle
    } else {
        foreach ($plans as &$plan) {
            $plan['name_translated'] = $plan['name']; // Türkçe dil seçiminde orijinal adı kullan
        }
        unset($plan); // Referansı temizle
    }
    
    // Premium içerik sayısı
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM articles 
        WHERE is_premium = 1 AND status = 'published'
    ");
    $stmt->execute();
    $premiumArticleCount = $stmt->fetchColumn();

    // Kullanıcının aktif ücretli aboneliğini kontrol et
    $currentUser = getCurrentUser();
    if ($currentUser['is_premium']) {
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
    }
    
} catch (Exception $e) {
    $error = 'Abonelik planları yüklenirken bir hata oluştu: ' . $e->getMessage();
    $plans = [];
    $premiumArticleCount = 0;
}
?>

<main class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2">
            <li><a href="/" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"><?= $language == 'en' ? 'Home' : 'Ana Sayfa' ?></a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-600 dark:text-gray-300"><?= $language == 'en' ? 'Premium Membership' : 'Premium Üyelik' ?></li>
        </ol>
    </nav>

    <?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php
        switch ($_GET['error']) {
            case 'cannot_downgrade':
                echo $language == 'en' ? 'You cannot switch to a plan lower than your current one.' : 'Mevcut planınızdan daha düşük bir plana geçiş yapamazsınız.';
                break;
            case 'active_subscription':
                echo $language == 'en' ? 'You already have an active subscription. You can choose a higher plan to upgrade your current subscription.' : 'Zaten aktif bir aboneliğiniz var. Mevcut aboneliğinizi yükseltmek için daha yüksek bir plan seçebilirsiniz.';
                break;
            case 'plan_not_found':
                echo $language == 'en' ? 'Selected subscription plan not found.' : 'Seçilen abonelik planı bulunamadı.';
                break;
            case 'payment_failed':
                echo $language == 'en' ? 'Payment process failed. Please try again.' : 'Ödeme işlemi başarısız oldu. Lütfen tekrar deneyin.';
                break;
            case 'system_error':
                echo $language == 'en' ? 'System error occurred. Please try again later.' : 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.';
                break;
            default:
                echo $language == 'en' ? 'An error occurred. Please try again.' : 'Bir hata oluştu. Lütfen tekrar deneyin.';
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Hero Bölümü -->
    <div class="text-center mb-12">
        <div class="mb-6">
            <i class="fas fa-crown text-6xl text-yellow-500 mb-4"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            <?= $language == 'en' ? 'Premium Membership' : 'Premium Üyelik' ?>
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-6 max-w-3xl mx-auto">
            <?= $language == 'en' ? 'Access special content, enjoy an ad-free experience and take advantage of more benefits.' : 'Özel içeriklere erişin, reklamsız deneyim yaşayın ve daha fazla avantajın keyfini çıkarın.' ?>
        </p>
        
        <?php if ($currentUser['is_premium']): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-6 py-4 rounded-lg inline-block">
            <i class="fas fa-check-circle mr-2"></i>
            <?= $language == 'en' ? 'You have an active premium membership!' : 'Aktif premium üyeliğiniz var!' ?>
            <?php
            $expiryDate = strtotime($currentUser['premium_expires_at']);
            $hundredYearsFromNow = strtotime('+100 years');
            if ($expiryDate >= $hundredYearsFromNow): ?>
                <div class="text-sm mt-1">
                    <i class="fas fa-infinity mr-1"></i><?= $language == 'en' ? 'Lifetime Premium Membership' : 'Süresiz Premium Üyelik' ?>
                </div>
            <?php else: ?>
                <div class="text-sm mt-1">
                    <i class="fas fa-clock mr-1"></i><?= $language == 'en' ? 'Membership expires: ' : 'Üyelik bitiş: ' ?><?= date('d.m.Y', $expiryDate) ?>
                </div>
            <?php endif; ?>
            <div class="text-sm mt-2">
                <?= $language == 'en' ? 'You can switch to a longer plan or renew your current plan.' : 'Daha uzun süreli bir plana geçiş yapabilir veya mevcut planınızı yenileyebilirsiniz.' ?>
            </div>
            <div class="mt-3">
                <a href="/subscription" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                    <i class="fas fa-cog mr-2"></i> <?= $language == 'en' ? 'Manage My Subscription' : 'Aboneliğimi Yönet' ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Özellikler -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock-open text-2xl text-blue-600 dark:text-blue-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= $language == 'en' ? 'Exclusive Content' : 'Özel İçerikler' ?></h3>
            <p class="text-gray-600 dark:text-gray-400">
                <?= $language == 'en' ? 'Full access to ' . number_format($premiumArticleCount) . ' premium articles' : number_format($premiumArticleCount) . ' adet premium makaleye tam erişim' ?>
            </p>
        </div>

        <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-ad text-2xl text-green-600 dark:text-green-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= $language == 'en' ? 'Ad-Free Experience' : 'Reklamsız Deneyim' ?></h3>
            <p class="text-gray-600 dark:text-gray-400">
                <?= $language == 'en' ? 'Uninterrupted reading pleasure without any ads' : 'Hiçbir reklam olmadan kesintisiz okuma keyfi' ?>
            </p>
        </div>

        <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-star text-2xl text-purple-600 dark:text-purple-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= $language == 'en' ? 'Priority Support' : 'Öncelikli Destek' ?></h3>
            <p class="text-gray-600 dark:text-gray-400">
                <?= $language == 'en' ? 'Priority customer support for premium members' : 'Premium üyeler için öncelikli müşteri desteği' ?>
            </p>
        </div>
    </div>

    <!-- Fiyatlandırma Planları -->
    <?php if (!empty($plans)): ?>
    <div class="mb-12">
        <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-8">
            <?= $language == 'en' ? 'Pricing Plans' : 'Fiyatlandırma Planları' ?>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-<?= min(count($plans), 3) ?> gap-8 max-w-5xl mx-auto">
            <?php foreach ($plans as $plan): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <?php if (isset($plan['sort_order']) && $plan['sort_order'] == 1): ?>
                <div class="bg-yellow-500 text-white text-center py-2 text-sm font-medium">
                    <?= $language == 'en' ? 'Most Popular' : 'En Popüler' ?>
                </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white text-center mb-4">
                        <?= htmlspecialchars($plan['name_translated']) ?>
                    </h3>
                    
                    <div class="text-center mb-6">
                        <div class="text-4xl font-bold text-gray-900 dark:text-white">
                            <?= number_format($plan['price'], 2) ?> ₺
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <?php
                            if ($plan['duration_months'] == 1) {
                                echo $language == 'en' ? 'monthly' : 'aylık';
                            } elseif ($plan['duration_months'] == 12) {
                                echo $language == 'en' ? 'yearly' : 'yıllık';
                            } else {
                                echo $language == 'en' ? $plan['duration_months'] . ' months' : $plan['duration_months'] . ' ay';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($plan['features'])): ?>
                    <ul class="space-y-3 mb-6">
                        <?php 
                        $features = explode("\n", $plan['features']);
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
                        <li class="flex items-center text-gray-600 dark:text-gray-400">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <?= htmlspecialchars($translatedFeature) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <?php if ($currentUser['is_premium'] && isset($activeSubscription) && is_array($activeSubscription)): ?>
                        <?php
                        $currentPlanPrice = floatval($activeSubscription['plan_price']);
                        
                        if ($plan['price'] > $currentPlanPrice): ?>
                            <a href="payment/checkout.php?plan=<?= $plan['id'] ?>" 
                               class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-medium transition-colors">
                                <i class="fas fa-arrow-up mr-1"></i><?= $language == 'en' ? 'Upgrade Plan' : 'Plana Yükselt' ?>
                            </a>
                        <?php else: ?>
                            <button disabled class="w-full bg-gray-400 text-white py-3 rounded-lg font-medium cursor-not-allowed">
                                <?= $language == 'en' ? 'Current/Lower Plan' : 'Mevcut/Düşük Plan' ?>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="payment/checkout.php?plan=<?= $plan['id'] ?>" 
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-medium transition-colors">
                            <?= $language == 'en' ? 'Start Now' : 'Hemen Başla' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- SSS -->
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-8">
            <?= $language == 'en' ? 'Frequently Asked Questions' : 'Sıkça Sorulan Sorular' ?>
        </h2>
        
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    <?= $language == 'en' ? 'When does premium membership become active?' : 'Premium üyelik ne zaman aktif olur?' ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    <?= $language == 'en' ? 'Your premium membership becomes active immediately after your payment is confirmed, and you can instantly access all premium content.' : 'Ödemeniz onaylandıktan hemen sonra premium üyeliğiniz aktif olur ve tüm premium içeriklere anında erişebilirsiniz.' ?>
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    <?= $language == 'en' ? 'Can I cancel my membership?' : 'Üyeliğimi iptal edebilir miyim?' ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    <?= $language == 'en' ? 'Yes, you can cancel your membership anytime. After cancellation, you can continue to enjoy premium features until the end of your current period.' : 'Evet, dilediğiniz zaman üyeliğinizi iptal edebilirsiniz. İptal işleminden sonra mevcut dönem sonuna kadar premium özelliklerden yararlanmaya devam edersiniz.' ?>
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    <?= $language == 'en' ? 'What are premium contents?' : 'Premium içerikler nelerdir?' ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    <?= $language == 'en' ? 'Premium contents are in-depth analyses, special reports prepared by our expert writers, and articles exclusive to premium members.' : 'Premium içerikler, uzman yazarlarımız tarafından hazırlanan derinlemesine analizler, özel raporlar ve sadece premium üyelere özel makalelerdir.' ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 