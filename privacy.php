<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$settings = getSettings();

$language = getCurrentLanguage();

// Sayfa başlığı
$pageTitle = $language == 'en' ? "Privacy Policy" : "Gizlilik Politikası";
$metaDescription = $language == 'en' ? "Information about our privacy policy and cookie usage." : "Gizlilik politikamız ve çerez kullanımı hakkında bilgi.";

// Header'ı dahil et
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">
            <?php echo $language == 'en' ? 'Privacy Policy' : 'Gizlilik Politikası'; ?>
        </h1>
        
        <div class="prose dark:prose-invert max-w-none">
            <?php if ($language == 'en'): ?>
            <!-- İNGİLİZCE İÇERİK -->
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Your Personal Data</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                By using this website, you agree to the processing of your personal data in accordance with this privacy policy. We collect and use your personal data only to provide you with services, improve the site, and fulfill our legal obligations.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Information We Collect</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                When you register on our site, fill out a form, or leave a comment, we may collect information such as your name, email address, and IP address. We also collect information about how you use our site.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Cookies</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Our site uses cookies to enhance your experience. Cookies are small text files placed on your device by your browser. Cookies help us understand how you use our site, personalize content, and protect your session information.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Types of cookies we use:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li><strong>Necessary Cookies:</strong> Required for the proper functioning of our site.</li>
                <li><strong>Preference Cookies:</strong> Allow us to remember your settings such as language preference.</li>
                <li><strong>Statistics Cookies:</strong> Help us understand how our site is used.</li>
                <li><strong>Marketing Cookies:</strong> Used to show you advertisements according to your interests.</li>
            </ul>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Most browsers allow you to block cookies or send you a notification when cookies are placed on your device. However, if you block cookies, some features of our site may not work properly.
            </p>
            <?php else: ?>
            <!-- TÜRKÇE İÇERİK -->
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Kişisel Verileriniz</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu web sitesini kullanarak, kişisel verilerinizin bu gizlilik politikasına uygun olarak işlenmesini kabul etmiş olursunuz. Kişisel verilerinizi yalnızca size hizmet sunmak, siteyi geliştirmek ve yasal yükümlülüklerimizi yerine getirmek için toplarız ve kullanırız.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Topladığımız Bilgiler</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Sitemize kaydolduğunuzda, bir form doldurduğunuzda veya yorum yaptığınızda, adınız, e-posta adresiniz ve IP adresiniz gibi bilgileri toplayabiliriz. Ayrıca, sitemizi nasıl kullandığınıza dair bilgileri de toplarız.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Çerezler</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Sitemiz, deneyiminizi geliştirmek için çerezler kullanmaktadır. Çerezler, tarayıcınız tarafından cihazınıza yerleştirilen küçük metin dosyalarıdır. Çerezler, sitemizi nasıl kullandığınızı anlamamıza, içeriği kişiselleştirmemize ve oturum bilgilerinizi korumamıza yardımcı olur.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Kullandığımız çerez türleri:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li><strong>Zorunlu Çerezler:</strong> Sitemizin düzgün çalışması için gereklidir.</li>
                <li><strong>Tercih Çerezleri:</strong> Dil tercihi gibi ayarlarınızı hatırlamamızı sağlar.</li>
                <li><strong>İstatistik Çerezleri:</strong> Sitemizin nasıl kullanıldığını anlamamıza yardımcı olur.</li>
                <li><strong>Pazarlama Çerezleri:</strong> Size ilgi alanlarınıza göre reklamlar göstermek için kullanılır.</li>
            </ul>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Çoğu tarayıcı, çerezleri engellemenize veya çerezlerin cihazınıza yerleştirildiğinde size bildirim göndermesine olanak tanır. Ancak, çerezleri engellerseniz, sitemizin bazı özelliklerinin düzgün çalışmayabileceğini unutmayın.
            </p>
            <?php endif; ?>
            
            <?php if ($language == 'en'): ?>
            <!-- İNGİLİZCE İÇERİK DEVAMI -->
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Google Analytics and Advertisements</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Our site uses Google Analytics to analyze visitor traffic. Google Analytics collects information about how visitors use our site through cookies. This information helps us improve our site.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                In addition, Google AdSense advertisements may be displayed on our site. Google AdSense uses cookies to show advertisements according to your interests. For more information on how Google uses advertising cookies, you can review the <a href="https://policies.google.com/technologies/ads" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Advertising Policy</a>.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Sharing Your Data</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                We do not share your personal data with third parties unless we have a legal obligation to do so. However, our service providers (hosting, analytics, etc.) that we use to operate our site may have access to your data.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Your Rights</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                You have the following rights regarding your personal data:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li>The right to access your data and obtain a copy of it</li>
                <li>The right to correct incorrect or incomplete data</li>
                <li>The right to request deletion of your data</li>
                <li>The right to restrict processing of your data</li>
                <li>The right to request transfer of your data</li>
                <li>The right to object to data processing</li>
            </ul>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                To exercise these rights, please contact us.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Privacy Policy Changes</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                We may update this privacy policy from time to time. Changes will be published on this page. In the case of significant changes, we will display a prominent notification on our site.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Contact</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                If you have any questions about our privacy policy or your personal data, please contact us:
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Email: info@<?= $_SERVER['HTTP_HOST'] ?><br>
                Web: <?= getSiteUrl() ?>
            </p>
            
            <?php else: ?>
            <!-- TÜRKÇE İÇERİK DEVAMI -->
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Google Analytics ve Reklamlar</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Sitemiz, ziyaretçi trafiğini analiz etmek için Google Analytics kullanmaktadır. Google Analytics, çerezler kullanarak ziyaretçilerin sitemizi nasıl kullandığına dair bilgileri toplar. Bu bilgiler, sitemizi geliştirmemize yardımcı olur.
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Ayrıca, sitemizde Google AdSense reklamları görüntülenebilir. Google AdSense, ilgi alanlarınıza göre reklamlar göstermek için çerezler kullanır. Google'ın reklam çerezlerini nasıl kullandığı hakkında daha fazla bilgi için <a href="https://policies.google.com/technologies/ads" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Google Reklam Politikası</a>'nı inceleyebilirsiniz.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Verilerinizin Paylaşılması</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Kişisel verilerinizi, yasal bir yükümlülüğümüz olmadığı sürece üçüncü taraflarla paylaşmayız. Ancak, sitemizi işletmek için kullandığımız hizmet sağlayıcılarımız (hosting, analitik vb.) verilerinize erişebilir.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Haklarınız</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Kişisel verilerinizle ilgili olarak aşağıdaki haklara sahipsiniz:
            </p>
            
            <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300">
                <li>Verilerinize erişme ve bir kopyasını alma hakkı</li>
                <li>Yanlış veya eksik verilerinizi düzeltme hakkı</li>
                <li>Verilerinizin silinmesini talep etme hakkı</li>
                <li>Verilerinizin işlenmesini kısıtlama hakkı</li>
                <li>Verilerinizin taşınmasını talep etme hakkı</li>
                <li>Veri işlemeye itiraz etme hakkı</li>
            </ul>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu haklarınızı kullanmak için lütfen bizimle iletişime geçin.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Gizlilik Politikası Değişiklikleri</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu gizlilik politikasını zaman zaman güncelleyebiliriz. Değişiklikler bu sayfada yayınlanacaktır. Önemli değişiklikler olması durumunda, sitemizde belirgin bir bildirim yayınlayacağız.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">İletişim</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Gizlilik politikamız veya kişisel verilerinizle ilgili herhangi bir sorunuz varsa, lütfen bizimle iletişime geçin:
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