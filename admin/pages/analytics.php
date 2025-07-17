<?php
// Hata kontrolü
if (!defined('BASE_PATH')) {
    header('Location: /admin/');
    exit;
}

// Form gönderildiğinde ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'analytics_settings') {
    $google_analytics = $_POST['google_analytics'] ?? '';
    updateSetting('google_analytics', $google_analytics);
    $success = "Google Analytics ayarları başarıyla güncellendi.";
}

// Analitik veri silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_analytics') {
    $clearType = $_POST['clear_type'] ?? 'all';
    $days = $_POST['days'] ?? null;
    
    if (clearAnalyticsData($clearType, $days)) {
        $success = "Analitik veriler başarıyla silindi.";
    } else {
        $error = "Analitik veriler silinirken bir hata oluştu.";
    }
}

// Gerçek analitik verileri
$analyticsData = [
    'today' => getAnalyticsData('today'),
    'this_week' => getAnalyticsData('week'),
    'this_month' => getAnalyticsData('month')
];

// En popüler sayfalar (son 7 gün)
$popularPages = getPopularPages(10, 7);

// Trafik kaynakları (son 7 gün)
$trafficSources = getTrafficSources(7);

// Cihaz türleri (son 7 gün)
$deviceTypes = getDeviceStatistics(7);

// Tarayıcı istatistikleri (son 7 gün)
$browserStats = getBrowserStatistics(7);

// En çok okunan makaleler (son 7 gün)
$mostReadArticles = getMostReadArticles(10, 7);

// Grafik için ziyaretçi verileri (son 7 gün)
$visitorChartData = getVisitorChart(7);

// Analitik veri sayıları
$dataCounts = getAnalyticsDataCount();

// Başarı mesajı göster
if (isset($success)):
?>
<div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php
// Hata mesajı göster
if (isset($error)):
?>
<div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<!-- Başlık -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Analitik</h2>
        <p class="text-gray-600 dark:text-gray-400">Site trafiği ve kullanıcı davranışları</p>
    </div>
    
    <div class="flex space-x-3 mt-4 lg:mt-0">
        <select class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <option>Son 7 Gün</option>
            <option>Son 30 Gün</option>
            <option>Son 3 Ay</option>
            <option>Son 1 Yıl</option>
        </select>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-download mr-2"></i>Rapor İndir
        </button>
        <button id="clear-data-btn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-trash mr-2"></i>Verileri Sil
        </button>
    </div>
</div>

<!-- İstatistik Kartları -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Bugün Ziyaretçi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Bugün Ziyaretçi</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($analyticsData['today']['visitors']); ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Benzersiz ziyaretçi</p>
            </div>
        </div>
    </div>

    <!-- Bugün Sayfa Görüntülenme -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-eye text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Bugün Sayfa Görüntülenme</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($analyticsData['today']['pageviews']); ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Toplam görüntülenme</p>
            </div>
        </div>
    </div>

    <!-- Bu Hafta Ziyaretçi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Bu Hafta</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($analyticsData['this_week']['visitors']); ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Haftalık ziyaretçi</p>
            </div>
        </div>
    </div>

    <!-- Bounce Rate -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <i class="fas fa-percentage text-yellow-600 dark:text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Bounce Rate</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $analyticsData['today']['bounce_rate']; ?>%</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Bugün</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Ziyaretçi İstatistikleri -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ziyaretçi İstatistikleri</h3>
            <select id="visitor-period" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm">
                <option value="today">Bugün</option>
                <option value="week">Bu Hafta</option>
                <option value="month">Bu Ay</option>
            </select>
        </div>
        <div class="h-[300px]">
            <canvas id="visitorChart"></canvas>
        </div>
    </div>

    <!-- Sayfa Görüntülenmeleri -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sayfa Görüntülenmeleri</h3>
            <select id="pageview-period" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm">
                <option value="today">Bugün</option>
                <option value="week">Bu Hafta</option>
                <option value="month">Bu Ay</option>
            </select>
        </div>
        <div class="h-[300px]">
            <canvas id="pageviewChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 mb-6">
    <!-- En Çok Okunan Makaleler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">En Çok Okunan Makaleler</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3">Başlık</th>
                        <th class="px-4 py-3">Görüntülenme</th>
                        <th class="px-4 py-3">Ortalama Okuma Süresi</th>
                        <th class="px-4 py-3">Yorum Sayısı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mostReadArticles)): ?>
                    <tr class="border-b dark:border-gray-600">
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-line text-4xl mb-4"></i>
                            <p>Henüz yeterli veri bulunmuyor.</p>
                            <p class="text-sm mt-1">Makaleler okundukça burada görünecek.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($mostReadArticles as $article): ?>
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3">
                                <a href="/article.php?id=<?php echo $article['id']; ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium"><?php echo number_format($article['recent_views'] ?: $article['views_count']); ?></span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">
                                    (<?php echo number_format($article['unique_recent_views']); ?> benzersiz)
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $readingTime = $article['avg_reading_time'] ? gmdate('i:s', $article['avg_reading_time'] * 60) : '-';
                                echo $readingTime;
                                ?>
                            </td>
                            <td class="px-4 py-3"><?php echo number_format($article['comment_count']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Tarayıcı Dağılımı -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tarayıcı Dağılımı</h3>
        <div class="h-[250px]">
            <canvas id="browserChart"></canvas>
        </div>
    </div>

    <!-- Cihaz Dağılımı -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cihaz Dağılımı</h3>
        <div class="h-[250px]">
            <canvas id="deviceChart"></canvas>
        </div>
    </div>

    <!-- Trafik Kaynakları -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Trafik Kaynakları</h3>
        <div class="h-[250px]">
            <canvas id="trafficChart"></canvas>
        </div>
    </div>
</div>

<!-- Google Analytics Entegrasyonu -->
<div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <i class="fas fa-chart-line text-blue-600 dark:text-blue-400 text-xl"></i>
        </div>
        <div>
            <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200">Google Analytics Entegrasyonu</h3>
            <p class="text-blue-700 dark:text-blue-300 mt-2">
                Daha detaylı analitik veriler için Google Analytics entegrasyonu yapabilirsiniz. 
                Bu sayede gerçek zamanlı veriler, detaylı raporlar ve gelişmiş filtreleme seçeneklerine erişebilirsiniz.
            </p>
            <div class="mt-4">
                <button id="google-analytics-connect" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors mr-3">
                    <i class="fab fa-google mr-2"></i>Google Analytics Bağla
                </button>
                <button id="google-analytics-settings" class="bg-white hover:bg-gray-50 text-blue-600 px-4 py-2 rounded-lg border border-blue-200 transition-colors">
                    <i class="fas fa-cog mr-2"></i>Ayarlar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Google Analytics Modal -->
<div id="analytics-settings-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Google Analytics Ayarları</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="analytics-settings-form" method="POST" action="" class="space-y-4">
                <input type="hidden" name="form_type" value="analytics_settings">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Google Analytics Kodu</label>
                    <textarea name="google_analytics" rows="6" placeholder="<!-- Google tag (gtag.js) --> kodunu buraya yapıştırın" 
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"><?php echo htmlspecialchars(getSiteSetting('google_analytics')); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Google Analytics'ten aldığınız JavaScript takip kodunu yapıştırın.</p>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="close-modal bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition-colors mr-2">
                        İptal
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Google Analytics Bağlantı Modal -->
<div id="analytics-connect-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Google Analytics'e Bağlan</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <p class="text-gray-700 dark:text-gray-300">
                    Google Analytics hesabınıza bağlanmak için aşağıdaki adımları izleyin:
                </p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                    <li>Google Analytics hesabınıza giriş yapın</li>
                    <li>Yönetici > Özellik Ayarları > Takip Bilgisi > Takip Kodu bölümüne gidin</li>
                    <li>Görüntülenen JavaScript kodunu kopyalayın</li>
                    <li>"Ayarlar" bölümüne yapıştırın ve kaydedin</li>
                </ol>
                <div class="flex justify-end mt-4">
                    <button type="button" class="close-modal bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition-colors mr-2">
                        Kapat
                    </button>
                    <a href="https://analytics.google.com/analytics/web/" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fab fa-google mr-2"></i>Google Analytics'e Git
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Veri Silme Modal -->
<div id="clear-data-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Analitik Verilerini Sil</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Mevcut Veri Bilgisi -->
            <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Mevcut Veri Durumu</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Sayfa Görüntülenmeleri:</span>
                        <span class="font-medium"><?php echo number_format($dataCounts['page_views']); ?></span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Site İstatistikleri:</span>
                        <span class="font-medium"><?php echo number_format($dataCounts['site_statistics']); ?></span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Makale İstatistikleri:</span>
                        <span class="font-medium"><?php echo number_format($dataCounts['article_statistics']); ?></span>
                    </div>
                    <div>
                        <span class="text-blue-700 dark:text-blue-300">Trafik Kaynakları:</span>
                        <span class="font-medium"><?php echo number_format($dataCounts['traffic_sources']); ?></span>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-t border-blue-200 dark:border-blue-700">
                    <span class="text-blue-700 dark:text-blue-300">Toplam Boyut:</span>
                    <span class="font-medium"><?php echo $dataCounts['total_size_mb']; ?> MB</span>
                </div>
            </div>

            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="clear_analytics">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Silme Türü</label>
                    <select name="clear_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">Tüm Analitik Veriler</option>
                        <option value="page_views">Sadece Sayfa Görüntülenmeleri</option>
                        <option value="old_data">90 Günden Eski Veriler</option>
                        <option value="site_statistics">Sadece Site İstatistikleri</option>
                        <option value="article_statistics">Sadece Makale İstatistikleri</option>
                        <option value="traffic_sources">Sadece Trafik Kaynakları</option>
                    </select>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-medium text-yellow-800 dark:text-yellow-200">Dikkat!</h4>
                            <p class="text-yellow-700 dark:text-yellow-300 text-sm mt-1">
                                Bu işlem geri alınamaz. Seçilen analitik veriler kalıcı olarak silinecek.
                                Devam etmek istediğinizden emin misiniz?
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" class="close-modal bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition-colors">
                        İptal
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>Verileri Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Grafik renkleri
const colors = {
    blue: 'rgb(59, 130, 246)',
    green: 'rgb(16, 185, 129)',
    yellow: 'rgb(245, 158, 11)',
    red: 'rgb(239, 68, 68)',
    purple: 'rgb(139, 92, 246)'
};

// Gerçek veri - PHP'den gelen veriler
const analyticsData = <?php echo json_encode($analyticsData); ?>;
const visitorChartData = <?php echo json_encode($visitorChartData); ?>;
const trafficSources = <?php echo json_encode($trafficSources); ?>;
const deviceTypes = <?php echo json_encode($deviceTypes); ?>;
const browserStats = <?php echo json_encode($browserStats); ?>;

// Ziyaretçi grafiği için veri hazırla
const visitorLabels = visitorChartData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('tr-TR', { weekday: 'short', day: 'numeric', month: 'numeric' });
});
const visitorData = visitorChartData.map(item => parseInt(item.visitors));
const pageviewData = visitorChartData.map(item => parseInt(item.page_views));

// Trafik kaynakları için veri hazırla
const trafficLabels = trafficSources.map(item => item.source);
const trafficData = trafficSources.map(item => parseFloat(item.percentage));

// Cihaz türleri için veri hazırla
const deviceLabels = deviceTypes.map(item => {
    const deviceNames = {
        'desktop': 'Masaüstü',
        'mobile': 'Mobil',
        'tablet': 'Tablet'
    };
    return deviceNames[item.device] || item.device;
});
const deviceData = deviceTypes.map(item => parseFloat(item.percentage));

// Tarayıcı için veri hazırla
const browserLabels = browserStats.map(item => item.browser);
const browserData = browserStats.map(item => parseFloat(item.percentage));

// Grafik ayarları
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 20,
                usePointStyle: true
            }
        }
    }
};

// Ziyaretçi grafiği
const visitorCtx = document.getElementById('visitorChart').getContext('2d');
new Chart(visitorCtx, {
    type: 'line',
    data: {
        labels: visitorLabels.length > 0 ? visitorLabels : ['Veri Yok'],
        datasets: [{
            label: 'Ziyaretçi Sayısı',
            data: visitorData.length > 0 ? visitorData : [0],
            borderColor: colors.blue,
            tension: 0.4,
            fill: false
        }]
    },
    options: chartOptions
});

// Sayfa görüntülenme grafiği
const pageviewCtx = document.getElementById('pageviewChart').getContext('2d');
new Chart(pageviewCtx, {
    type: 'bar',
    data: {
        labels: visitorLabels.length > 0 ? visitorLabels : ['Veri Yok'],
        datasets: [{
            label: 'Sayfa Görüntülenmeleri',
            data: pageviewData.length > 0 ? pageviewData : [0],
            backgroundColor: colors.green
        }]
    },
    options: chartOptions
});

// Tarayıcı dağılımı grafiği
const browserCtx = document.getElementById('browserChart').getContext('2d');
new Chart(browserCtx, {
    type: 'doughnut',
    data: {
        labels: browserLabels.length > 0 ? browserLabels : ['Veri Yok'],
        datasets: [{
            data: browserData.length > 0 ? browserData : [100],
            backgroundColor: browserLabels.length > 0 ? Object.values(colors).slice(0, browserLabels.length) : [colors.blue]
        }]
    },
    options: chartOptions
});

// Cihaz dağılımı grafiği
const deviceCtx = document.getElementById('deviceChart').getContext('2d');
new Chart(deviceCtx, {
    type: 'pie',
    data: {
        labels: deviceLabels.length > 0 ? deviceLabels : ['Veri Yok'],
        datasets: [{
            data: deviceData.length > 0 ? deviceData : [100],
            backgroundColor: deviceLabels.length > 0 ? [colors.blue, colors.green, colors.yellow].slice(0, deviceLabels.length) : [colors.blue]
        }]
    },
    options: chartOptions
});

// Trafik kaynakları grafiği
const trafficCtx = document.getElementById('trafficChart').getContext('2d');
new Chart(trafficCtx, {
    type: 'pie',
    data: {
        labels: trafficLabels.length > 0 ? trafficLabels : ['Veri Yok'],
        datasets: [{
            data: trafficData.length > 0 ? trafficData : [100],
            backgroundColor: trafficLabels.length > 0 ? Object.values(colors).slice(0, trafficLabels.length) : [colors.blue]
        }]
    },
    options: chartOptions
});

// Dönem seçimi değişikliklerini dinle
document.getElementById('visitor-period').addEventListener('change', function() {
    // API'den yeni veri al ve grafiği güncelle
});

document.getElementById('pageview-period').addEventListener('change', function() {
    // API'den yeni veri al ve grafiği güncelle
});

// Google Analytics modal işlemleri
document.addEventListener('DOMContentLoaded', function() {
    // Modal açma butonları
    const settingsButton = document.getElementById('google-analytics-settings');
    const connectButton = document.getElementById('google-analytics-connect');
    const clearDataButton = document.getElementById('clear-data-btn');
    
    // Modal elementleri
    const settingsModal = document.getElementById('analytics-settings-modal');
    const connectModal = document.getElementById('analytics-connect-modal');
    const clearDataModal = document.getElementById('clear-data-modal');
    
    // Kapatma butonları
    const closeButtons = document.querySelectorAll('.close-modal');
    
    // Ayarlar modalını aç
    settingsButton.addEventListener('click', function() {
        settingsModal.classList.remove('hidden');
    });
    
    // Bağlantı modalını aç
    connectButton.addEventListener('click', function() {
        connectModal.classList.remove('hidden');
    });
    
    // Veri silme modalını aç
    clearDataButton.addEventListener('click', function() {
        clearDataModal.classList.remove('hidden');
    });
    
    // Tüm kapatma butonlarını dinle
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            settingsModal.classList.add('hidden');
            connectModal.classList.add('hidden');
            clearDataModal.classList.add('hidden');
        });
    });
    
    // Modal dışına tıklandığında kapat
    window.addEventListener('click', function(event) {
        if (event.target === settingsModal || event.target === connectModal || event.target === clearDataModal) {
            settingsModal.classList.add('hidden');
            connectModal.classList.add('hidden');
            clearDataModal.classList.add('hidden');
        }
    });
});
</script>