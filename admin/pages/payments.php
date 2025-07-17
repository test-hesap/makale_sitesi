<?php
require_once __DIR__ . '/../../config/database.php';

// Veritabanından ödemeleri çek
try {
    $database = new Database();
    $db = $database->pdo;
    
    // Ödemeleri kullanıcı ve plan bilgileriyle birlikte getir
    $stmt = $db->prepare("
        SELECT 
            p.*,
            u.username as user_name,
            u.email as user_email,
            sp.name as plan_name,
            sp.price as plan_price
        FROM payments p
        JOIN users u ON p.user_id = u.id
        JOIN subscription_plans sp ON p.plan_id = sp.id
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // İstatistikler
    $totalRevenue = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'completed'), 'amount'));
    $completedCount = count(array_filter($payments, fn($p) => $p['status'] === 'completed'));
    $pendingCount = count(array_filter($payments, fn($p) => $p['status'] === 'pending'));
    $failedCount = count(array_filter($payments, fn($p) => $p['status'] === 'failed'));

} catch (Exception $e) {
    error_log($e->getMessage());
    $payments = [];
    $totalRevenue = 0;
    $completedCount = 0;
    $pendingCount = 0;
    $failedCount = 0;
}
?>

<!-- Başlık -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ödemeler</h2>
        <p class="text-gray-600 dark:text-gray-400">Ödeme işlemlerini ve gelir raporlarını görüntüleyin</p>
    </div>
    
    <div class="flex space-x-3 mt-4 lg:mt-0">
        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-download mr-2"></i>Rapor İndir
        </button>
    </div>
</div>

<!-- İstatistik Kartları -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-lira-sign text-green-600 dark:text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Gelir</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($totalRevenue, 2); ?> ₺</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-check-circle text-blue-600 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Başarılı</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $completedCount; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $pendingCount; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                <i class="fas fa-times-circle text-red-600 dark:text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Başarısız</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $failedCount; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Ödeme Listesi -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Son Ödemeler</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kullanıcı</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tutar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ödeme Yöntemi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlem ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        Henüz hiç ödeme kaydı bulunmuyor.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($payment['user_name']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($payment['user_email']); ?>
                            </p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($payment['plan_name']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                        <?php echo number_format($payment['amount'], 2); ?> TL
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <?php
                        $methodNames = [
                            'credit_card' => 'Kredi Kartı',
                            'bank_transfer' => 'Banka Havalesi',
                            'paypal' => 'PayPal'
                        ];
                        echo $methodNames[$payment['payment_method']] ?? $payment['payment_method'];
                        ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $statusClasses = [
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        ];
                        $statusNames = [
                            'completed' => 'Tamamlandı',
                            'pending' => 'Bekliyor',
                            'failed' => 'Başarısız'
                        ];
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClasses[$payment['status']]; ?>">
                            <?php echo $statusNames[$payment['status']]; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">
                        <?php echo htmlspecialchars($payment['transaction_id'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <?php echo date('d.m.Y H:i', strtotime($payment['payment_date'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Ödeme Entegrasyonu Bilgisi -->
<div class="mt-6 bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                Ödeme Sistemi Entegrasyonu
            </h3>
            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <p>Gerçek bir ödeme sistemi entegre etmek için aşağıdaki seçenekleri değerlendirebilirsiniz:</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    <li>İyzico - Türkiye'nin önde gelen ödeme çözümü</li>
                    <li>PayTR - Kolay entegrasyon ve düşük komisyon</li>
                    <li>Stripe - Uluslararası ödeme sistemi</li>
                    <li>PayPal - Global ödeme çözümü</li>
                </ul>
            </div>
        </div>
    </div>
</div> 