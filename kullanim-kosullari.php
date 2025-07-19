<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$settings = getSettings();

$language = getCurrentLanguage();

// Sayfa başlığı
$pageTitle = $language == 'en' ? "Terms of Service" : "Kullanım Koşulları";
$metaDescription = $language == 'en' ? "Information about our terms of service and site usage conditions." : "Kullanım koşullarımız ve site kullanım şartları hakkında bilgi.";

// Header'ı dahil et
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">
            <?php echo $language == 'en' ? 'Terms of Service' : 'Kullanım Koşulları'; ?>
        </h1>
        
        <div class="prose dark:prose-invert max-w-none">
            <?php if ($language == 'en'): ?>
            <!-- İNGİLİZCE İÇERİK -->
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Acceptance of Terms</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                By accessing or using this website, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our site.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">User Accounts</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                When you create an account on our site, you are responsible for maintaining the security of your account and password. The site cannot and will not be liable for any loss or damage from your failure to comply with this security obligation.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Content and Conduct</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Users may post content to our site as long as it complies with our guidelines. You are solely responsible for the content you post. Content that is illegal, offensive, threatening, defamatory, or that infringes on intellectual property rights is prohibited.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Subscriptions and Payments</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Some features of our site may require a subscription. By subscribing, you agree to pay the specified fees. Subscriptions will automatically renew unless canceled before the renewal date. Refunds are provided according to our refund policy.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Intellectual Property</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                The content on this site, including text, graphics, logos, and software, is the property of this site or its content suppliers and is protected by copyright laws. You may not reproduce, modify, or distribute the content without our permission.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Limitation of Liability</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                To the maximum extent permitted by law, this site shall not be liable for any direct, indirect, incidental, special, or consequential damages resulting from the use or inability to use the service, even if we have been advised of the possibility of such damages.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Changes to Terms</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting to the site. Your continued use of the site after changes are posted constitutes your acceptance of the modified terms.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Contact</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                If you have any questions about these Terms of Service, please contact us at:
            </p>
            
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Email: info@<?= $_SERVER['HTTP_HOST'] ?><br>
                Web: <?= getSiteUrl() ?>
            </p>
            
            <?php else: ?>
            <!-- TÜRKÇE İÇERİK -->
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Koşulların Kabulü</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu web sitesine erişerek veya siteyi kullanarak, bu Kullanım Koşulları'na bağlı kalmayı kabul etmiş olursunuz. Bu koşulları kabul etmiyorsanız, lütfen sitemizi kullanmayın.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Kullanıcı Hesapları</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Sitemizde bir hesap oluşturduğunuzda, hesabınızın ve şifrenizin güvenliğini sağlamakla sorumlusunuz. Site, bu güvenlik yükümlülüğünüze uymamanızdan kaynaklanan herhangi bir kayıp veya zarardan sorumlu tutulamaz.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">İçerik ve Davranış</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Kullanıcılar, yönergelerimize uyduğu sürece sitemize içerik gönderebilirler. Paylaştığınız içerikten yalnızca siz sorumlusunuz. Yasa dışı, saldırgan, tehdit edici, karalayıcı veya fikri mülkiyet haklarını ihlal eden içerik yasaktır.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Abonelikler ve Ödemeler</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Sitemizin bazı özellikleri abonelik gerektirebilir. Abone olarak belirtilen ücretleri ödemeyi kabul etmiş olursunuz. Abonelikler, yenileme tarihinden önce iptal edilmedikçe otomatik olarak yenilenir. İadeler, iade politikamıza göre sağlanır.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Fikri Mülkiyet</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu sitedeki metin, grafik, logo ve yazılım dahil olmak üzere tüm içerik, bu sitenin veya içerik sağlayıcılarının mülkiyetindedir ve telif hakkı yasaları tarafından korunmaktadır. İçeriği iznimiz olmadan çoğaltamaz, değiştiremez veya dağıtamazsınız.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Sorumluluk Sınırlaması</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Yasaların izin verdiği azami ölçüde, bu site, hizmetin kullanımından veya kullanılamamasından kaynaklanan doğrudan, dolaylı, arızi, özel veya sonuçsal zararlardan, bu tür zararların olasılığı konusunda bilgilendirilmiş olsa bile sorumlu tutulamaz.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">Koşullardaki Değişiklikler</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu koşulları herhangi bir zamanda değiştirme hakkını saklı tutarız. Değişiklikler siteye yayınlandıktan hemen sonra geçerli olur. Değişiklikler yayınlandıktan sonra siteyi kullanmaya devam etmeniz, değiştirilen koşulları kabul ettiğiniz anlamına gelir.
            </p>
            
            <h2 class="text-2xl font-semibold mb-4 mt-6 text-gray-800 dark:text-gray-200">İletişim</h2>
            <p class="mb-4 text-gray-700 dark:text-gray-300">
                Bu Kullanım Koşulları hakkında herhangi bir sorunuz varsa, lütfen bizimle iletişime geçin:
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
