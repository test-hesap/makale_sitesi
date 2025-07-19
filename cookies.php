<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$settings = getSettings();

$language = getCurrentLanguage();

// Sayfa başlığı
$pageTitle = $language == 'en' ? "Cookie Policy" : "Çerez Politikası";
$metaDescription = $language == 'en' ? "Detailed information about our cookie policy and cookie usage." : "Çerez politikamız ve çerez kullanımı hakkında detaylı bilgi.";

// Header'ı dahil et
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">
            <?php echo $language == 'en' ? 'Cookie Policy' : 'Çerez Politikası'; ?>
        </h1>
        
        <div class="prose dark:prose-invert max-w-none">
            <?php if ($language == 'en'): ?>
            <!-- İNGİLİZCE İÇERİK -->
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                This Cookie Policy explains how we use cookies on our website. When you visit our website, we recommend that you allow your browser to accept cookies so that we can provide you with the best online experience.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">What is a Cookie?</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                A cookie is a small text file that is placed on your computer, tablet, or mobile device when you visit a website. These cookies collect and store information when you visit our website or navigate between pages.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">How We Use Cookies</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                We use cookies for the following purposes:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li><strong>Necessary Cookies:</strong> These cookies are required for our website to function properly and cannot be turned off in our systems. They are usually set only in response to actions you make which amount to a request for services, such as setting your privacy preferences, logging in, or filling in forms.</li>
                <li><strong>Preference Cookies:</strong> These cookies are designed to provide a better experience when using our website. For example, they remember your language preferences or region.</li>
                <li><strong>Statistics Cookies:</strong> These cookies help us understand how visitors use our website. They allow us to see which pages are the most popular and how visitors navigate through the site.</li>
                <li><strong>Marketing Cookies:</strong> These cookies are used to show you more relevant ads on our website. They also help us measure the effectiveness of our advertising campaigns.</li>
            </ul>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Types of Cookies</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Cookies used on our website can be divided into the following categories:
            </p>
            
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Session Cookies</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                These cookies are temporary and are deleted when you close your browser. These cookies are used to maintain your session information as you navigate through our website.
            </p>
            <?php else: ?>
            <!-- TÜRKÇE İÇERİK -->
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu Çerez Politikası, web sitemizde çerezleri nasıl kullandığımızı açıklamaktadır. Web sitemizi ziyaret ettiğinizde, tarayıcınızın çerezleri kabul etmesine izin vermenizi öneririz, böylece size en iyi çevrimiçi deneyimi sunabiliriz.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Çerez Nedir?</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çerez, web sitesini ziyaret ettiğinizde bilgisayarınıza, tabletinize veya mobil cihazınıza yerleştirilen küçük bir metin dosyasıdır. Bu çerezler, web sitemizi her ziyaret ettiğinizde veya sayfalar arasında gezindiğinizde bilgi toplar ve saklar.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Çerezleri Nasıl Kullanıyoruz?</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çerezleri aşağıdaki amaçlar için kullanıyoruz:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li><strong>Zorunlu Çerezler:</strong> Bu çerezler, web sitemizin düzgün çalışması için gereklidir ve sistemlerimizde kapatılamazlar. Genellikle yalnızca sizin gerçekleştirdiğiniz ve gizlilik tercihlerinizi ayarlama, oturum açma veya form doldurma gibi hizmet taleplerine karşılık olarak ayarlanırlar.</li>
                <li><strong>Tercih Çerezleri:</strong> Bu çerezler, web sitemizi kullanırken daha iyi bir deneyim sağlamak için tasarlanmıştır. Örneğin, dil tercihlerinizi veya bölgenizi hatırlarlar.</li>
                <li><strong>İstatistik Çerezleri:</strong> Bu çerezler, ziyaretçilerin web sitemizi nasıl kullandığını anlamamıza yardımcı olur. Hangi sayfaların en popüler olduğunu ve ziyaretçilerin sitede nasıl dolaştığını görmemizi sağlarlar.</li>
                <li><strong>Pazarlama Çerezleri:</strong> Bu çerezler, web sitemizde size daha alakalı reklamlar göstermek için kullanılır. Ayrıca, reklam kampanyalarımızın etkinliğini ölçmemize yardımcı olurlar.</li>
            </ul>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Çerez Türleri</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Web sitemizde kullanılan çerezler aşağıdaki kategorilere ayrılabilir:
            </p>
            
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Oturum Çerezleri</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu çerezler geçicidir ve tarayıcınızı kapattığınızda silinirler. Bu çerezler, web sitemizde gezinirken oturum bilgilerinizi korumak için kullanılır.
            </p>
            <?php endif; ?>
            
            <?php if ($language == 'en'): ?>
            <!-- İNGİLİZCE İÇERİK DEVAMI -->
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Persistent Cookies</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                These cookies remain on your device even after you close your browser. They help us recognize you when you visit our website next time.
            </p>
            
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">First-Party Cookies</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                These cookies are placed directly by our website.
            </p>
            
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Third-Party Cookies</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                These cookies are placed by third-party services that we use on our website. For example, Google Analytics and Google AdSense.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">How Can You Control Cookies?</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Most web browsers are set to accept cookies automatically. However, you can change your browser settings to not accept cookies or to notify you when a new cookie is placed.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                For more information on how to manage cookies, you can visit your browser's help pages:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Chrome</a></li>
                <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Mozilla Firefox</a></li>
                <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Microsoft Edge</a></li>
                <li><a href="https://support.apple.com/guide/safari/manage-cookies-and-website-data-sfri11471/mac" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Safari</a></li>
            </ul>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Please note that if you disable cookies, some features of our website may not work properly.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Google Analytics and AdSense</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Our website uses Google Analytics and Google AdSense services. These services collect information about how visitors use our website using cookies and show you advertisements based on your interests.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                For more information about Google Analytics' use of cookies, you can visit <a href="https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Analytics Cookie Usage</a> page.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                For more information about Google AdSense's use of cookies, you can visit <a href="https://policies.google.com/technologies/ads" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Advertising Policies</a> page.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Cookie Policy Changes</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                We may update this Cookie Policy from time to time. Changes will be published on this page. In the case of significant changes, we will display a prominent notification on our site.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Contact</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                If you have any questions about our cookie policy or our use of cookies, please contact us:
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Email: info@<?= $_SERVER['HTTP_HOST'] ?><br>
                Web: <?= getSiteUrl() ?>
            </p>
            
            <?php else: ?>
            <!-- TÜRKÇE İÇERİK DEVAMI -->
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Kalıcı Çerezler</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu çerezler, tarayıcınızı kapattıktan sonra bile cihazınızda kalır. Web sitemizi bir sonraki ziyaretinizde sizi tanımamıza yardımcı olurlar.
            </p>
            
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Birinci Taraf Çerezleri</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu çerezler, doğrudan web sitemiz tarafından yerleştirilir.
            </p>
            
            <h3 class="text-xl font-semibold mb-3 mt-4 text-gray-800 dark:text-gray-200">Üçüncü Taraf Çerezleri</h3>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu çerezler, web sitemizde kullandığımız üçüncü taraf hizmetler tarafından yerleştirilir. Örneğin, Google Analytics ve Google AdSense gibi.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Çerezleri Nasıl Kontrol Edebilirsiniz?</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çoğu web tarayıcısı, çerezleri otomatik olarak kabul edecek şekilde ayarlanmıştır. Ancak, tarayıcı ayarlarınızı değiştirerek çerezleri kabul etmemeyi veya yeni bir çerez yerleştirildiğinde bildirim almayı seçebilirsiniz.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çerezleri nasıl yöneteceğiniz hakkında daha fazla bilgi için tarayıcınızın yardım sayfalarını ziyaret edebilirsiniz:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Chrome</a></li>
                <li><a href="https://support.mozilla.org/tr/kb/cerezler-web-sitelerinin-bilgisayarinizda-depoladigi-bilgiler" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Mozilla Firefox</a></li>
                <li><a href="https://support.microsoft.com/tr-tr/microsoft-edge/microsoft-edge-de-%C3%A7erezleri-silme-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Microsoft Edge</a></li>
                <li><a href="https://support.apple.com/tr-tr/guide/safari/sfri11471/mac" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Safari</a></li>
            </ul>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çerezleri devre dışı bırakırsanız, web sitemizin bazı özelliklerinin düzgün çalışmayabileceğini unutmayın.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Google Analytics ve AdSense</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Web sitemiz, Google Analytics ve Google AdSense hizmetlerini kullanmaktadır. Bu hizmetler, çerezler kullanarak ziyaretçilerin web sitemizi nasıl kullandığına dair bilgileri toplar ve size ilgi alanlarınıza göre reklamlar gösterir.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Google Analytics'in çerez kullanımı hakkında daha fazla bilgi için <a href="https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Analytics Çerez Kullanımı</a> sayfasını ziyaret edebilirsiniz.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Google AdSense'in çerez kullanımı hakkında daha fazla bilgi için <a href="https://policies.google.com/technologies/ads" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Reklam Politikası</a> sayfasını ziyaret edebilirsiniz.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Çerez Politikası Değişiklikleri</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu Çerez Politikasını zaman zaman güncelleyebiliriz. Değişiklikler bu sayfada yayınlanacaktır. Önemli değişiklikler olması durumunda, sitemizde belirgin bir bildirim yayınlayacağız.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">İletişim</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çerez politikamız veya çerez kullanımımızla ilgili herhangi bir sorunuz varsa, lütfen bizimle iletişime geçin:
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                E-posta: info@<?= $_SERVER['HTTP_HOST'] ?><br>
                Web: <?= getSiteUrl() ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?> 