<?php
// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum kontrolü
session_start();

// Gerekli dosyaları dahil et
require_once 'config/database.php';
require_once 'includes/functions.php';

try {
    // Dil ayarını al
    $language = getCurrentLanguage();
    
    // URL'den kullanıcı adını al
    $requestUri = $_SERVER['REQUEST_URI'];
    $urlPath = parse_url($requestUri, PHP_URL_PATH);
    $username = basename($urlPath);
    $username = trim(str_replace('uye/', '', $username));

    if (empty($username)) {
        throw new Exception($language == 'en' ? "Username is not specified." : "Kullanıcı adı belirtilmedi.");
    }

    $username = sanitizeInput($username);
    
    // Veritabanı bağlantısı
    $database = new Database();
    
    // Bağlantı kontrolü
    if (!$database->isConnected()) {
        throw new Exception($language == 'en' ? "Database connection failed. Please check configuration settings." : "Veritabanı bağlantısı kurulamadı. Lütfen yapılandırma ayarlarını kontrol edin.");
    }
    
    $db = $database->pdo;
    
    // Debug bilgisi
    error_log("Bağlantı başarılı. Kullanıcı sorgulanıyor: " . $username);
    
    // Kullanıcı bilgilerini çek
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.profile_image,
            u.is_approved,
            u.is_premium,
            u.premium_expires_at,
            u.created_at,
            u.bio,
            u.location,
            u.website,
            u.twitter,
            u.facebook,
            u.instagram,
            u.linkedin,
            u.tiktok,
            u.youtube,
            u.github,
            u.is_admin,
            COUNT(DISTINCT a.id) as article_count,
            COUNT(DISTINCT c.id) as comment_count,
            COALESCE(SUM(CASE WHEN a.status = 'published' THEN a.views_count ELSE 0 END), 0) as total_views
        FROM users u
        LEFT JOIN articles a ON u.id = a.user_id AND a.status = 'published'
        LEFT JOIN comments c ON u.id = c.user_id AND c.is_approved = 1
        WHERE u.username = :username AND u.is_approved = 1
        GROUP BY u.id, u.username, u.email, u.profile_image, u.is_approved, u.is_premium, u.premium_expires_at, u.created_at
    ");
    
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    
    if (!$stmt->execute()) {
        $error = $stmt->errorInfo();
        throw new Exception("Sorgu hatası: " . $error[2]);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception($language == 'en' ? 
            "User not found or not approved: " . htmlspecialchars($username) : 
            "Kullanıcı bulunamadı veya henüz onaylanmadı: " . htmlspecialchars($username));
    }
    
    // Show all parametresini kontrol et
    $showAll = isset($_GET['show']) && $_GET['show'] === 'all';
    $articleLimit = $showAll ? 1000 : 10; // Tüm makaleleri göster veya sadece 10 tane
    
    // Kullanıcının makalelerini çek
    $articlesStmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.excerpt,
            a.views_count,
            a.created_at,
            a.featured_image, -- EKLENDİ
            c.name as category_title,
            COUNT(DISTINCT cm.id) as comment_count
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN comments cm ON a.id = cm.article_id AND cm.is_approved = 1
        WHERE a.user_id = :user_id AND a.status = 'published'
        GROUP BY a.id, a.title, a.slug, a.excerpt, a.views_count, a.created_at, a.featured_image, c.name
        ORDER BY a.created_at DESC
        LIMIT :limit
    ");
    
    $articlesStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
    $articlesStmt->bindParam(':limit', $articleLimit, PDO::PARAM_INT);
    $articlesStmt->execute();
    $articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcının son yorumlarını çek
    $commentsStmt = $db->prepare("
        SELECT 
            c.id,
            c.content,
            c.created_at,
            a.title as article_title,
            a.slug as article_slug
        FROM comments c
        JOIN articles a ON c.article_id = a.id
        WHERE c.user_id = :user_id AND c.is_approved = 1
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    
    $commentsStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
    $commentsStmt->execute();
    $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sayfa başlığını ayarla
    $pageTitle = htmlspecialchars($user['username']) . ' - ' . getSiteSetting('site_title');
    
    // Header'ı dahil et
    require_once 'includes/header.php';
    
} catch (PDOException $e) {
    error_log("PDO Hatası: " . $e->getMessage() . "\nHata Kodu: " . $e->getCode());
    
    http_response_code(500);
    require_once 'includes/header.php';
    
    $errorTitle = $language == 'en' ? 'Database Error' : 'Veritabanı Hatası';
    $errorMessage = $language == 'en' ? 'A problem occurred while accessing the database.' : 'Veritabanına erişim sırasında bir sorun oluştu.';
    $errorCode = $language == 'en' ? 'Error Code: ' : 'Hata Kodu: ';
    $homeButton = $language == 'en' ? 'Return to Home Page' : 'Ana Sayfaya Dön';
    
    echo "<div class='container mx-auto px-4 py-8'>
            <div class='text-center'>
                <h1 class='text-3xl font-bold text-gray-900 dark:text-white mb-4'>{$errorTitle}</h1>
                <p class='text-gray-600 dark:text-gray-400'>{$errorMessage}</p>
                <p class='text-sm text-gray-500 mt-2'>{$errorCode}" . $e->getCode() . "</p>
                <a href='/' class='inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700'>
                    {$homeButton}
                </a>
            </div>
          </div>";
    require_once 'includes/footer.php';
    exit;
} catch (Exception $e) {
    error_log("Genel Hata: " . $e->getMessage());
    
    http_response_code(404);
    require_once 'includes/header.php';
    
    $errorTitle = $language == 'en' ? 'Member Not Found' : 'Üye Bulunamadı';
    $returnText = $language == 'en' ? 'Return to Members' : 'Üyelere Dön';
    
    echo "<div class='container mx-auto px-4 py-8'>
            <div class='text-center'>
                <h1 class='text-3xl font-bold text-gray-900 dark:text-white mb-4'>{$errorTitle}</h1>
                <p class='text-gray-600 dark:text-gray-400'>" . htmlspecialchars($e->getMessage()) . "</p>
                <a href='/members' class='inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700'>
                    {$returnText}
                </a>
            </div>
          </div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<!-- Ana içerik - Profil bilgileri -->
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Profil Başlığı -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex flex-col md:flex-row items-center">
                <!-- Profil Resmi -->
                <div class="mb-4 md:mb-0 md:mr-6">
                    <?php if (!empty($user['profile_image'])): ?>
                        <?php
                        // Base64 kodlamasıyla resim gösterme (dosya erişim sorunu çözümü)
                        $profileImageUrl = '';
                        $imagePath = ltrim($user['profile_image'], '/');
                        
                        if (file_exists($imagePath) && is_readable($imagePath)) {
                            $imageData = file_get_contents($imagePath);
                            $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                            $base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                            $profileImageUrl = $base64;
                        ?>
                        <img src="<?= $profileImageUrl ?>" 
                             alt="<?= htmlspecialchars($user['username']) ?>" 
                             class="w-24 h-24 rounded-full object-cover border-4 border-blue-500">
                        <?php } else { ?>
                        <!-- Varsayılan profil resmi (dosya okunamadı) -->
                        <div class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center border-4 border-blue-600">
                            <span class="text-3xl text-white font-bold">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </span>
                        </div>
                        <?php } ?>
                    <?php else: ?>
                    <!-- Profil resmi yok -->
                    <div class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center border-4 border-blue-600">
                        <span class="text-3xl text-white font-bold">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Kullanıcı Bilgileri -->
                <div class="text-center md:text-left flex-1">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                <?= htmlspecialchars($user['username']) ?>                        <?php if (isset($user['is_admin']) && $user['is_admin']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-2">
                            <i class="fas fa-shield-alt mr-1"></i><?= $language == 'en' ? 'Administrator' : 'Yönetici' ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($user['is_premium']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 ml-2">
                            <i class="fas fa-crown mr-1"></i>Premium
                        </span>
                        <?php endif; ?>
                            </h1>
                        </div>
                        <?php if (isLoggedIn() && getCurrentUser()['id'] != $user['id']): 
                            $currentUser = getCurrentUser();
                            $isApproved = $currentUser['is_approved'];
                        ?>
                        <div class="mt-4 md:mt-0">
                            <?php if ($isApproved && (isAdmin() || isPremium())): ?>
                            <button onclick="showMessageModal('<?= htmlspecialchars($user['username']) ?>')" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                <i class="far fa-envelope mr-2"></i>
                                <?= $language == 'en' ? 'Send Message' : 'Mesaj Gönder' ?>
                            </button>
                            <?php else: ?>
                            <button disabled 
                                    class="inline-flex items-center px-4 py-2 bg-gray-400 cursor-not-allowed text-white rounded-lg" 
                                    title="<?php 
                                    if (!$isApproved) {
                                        echo $language == 'en' ? 'Your account needs to be approved to send messages' : 'Mesaj gönderebilmek için hesabınızın onaylanması gerekiyor';
                                    } else {
                                        echo $language == 'en' ? 'Only premium members and administrators can send messages' : 'Sadece premium üyeler ve yöneticiler mesaj gönderebilir';
                                    }
                                    ?>">
                                <i class="far fa-envelope mr-2"></i>
                                <?= $language == 'en' ? 'Send Message' : 'Mesaj Gönder' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4 text-sm">
                        <div>
                            <p class="flex items-center text-gray-600 dark:text-gray-400">
                                <span class="w-24 text-gray-500"><?= $language == 'en' ? 'Registration:' : 'Kayıt Tarihi:' ?></span> 
                                <?php
                                $registrationDate = strtotime($user['created_at']);
                                $now = time();
                                $daysDiff = round(($now - $registrationDate) / 86400);
                                $daysText = $daysDiff > 0 ? ($language == 'en' ? $daysDiff . ' days ago' : $daysDiff . ' gün önce') : ($language == 'en' ? 'Today' : 'Bugün');
                                ?>
                                <span><?= date('d.m.Y', $registrationDate) ?> (<?= $daysText ?>)</span>
                            </p>
                            <p class="flex items-center text-gray-600 dark:text-gray-400">
                                <span class="w-24 text-gray-500"><?= $language == 'en' ? 'Last Login:' : 'Son Giriş:' ?></span>
                                <span><?= date('d.m.Y H:i', strtotime($user['last_login'] ?? $user['created_at'])) ?></span>
                            </p>
                        </div>
                        <div>
                            <p class="flex items-center text-gray-600 dark:text-gray-400">
                                <span class="w-24 text-gray-500"><?= $language == 'en' ? 'Location:' : 'Konum:' ?></span>
                                <span><?= htmlspecialchars($user['location'] ?? ($language == 'en' ? 'Not specified' : 'Belirtilmemiş')) ?></span>
                            </p>
                            <p class="flex items-center text-gray-600 dark:text-gray-400">
                                <span class="w-24 text-gray-500"><?= $language == 'en' ? 'Email:' : 'E-posta:' ?></span>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mt-4 mb-4">
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg text-center">
                            <div class="text-blue-500 font-bold text-2xl">
                                <i class="fas fa-file-alt"></i>
                                <span class="ml-2"><?= number_format($user['article_count']) ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?= $language == 'en' ? 'Articles' : 'Makale Sayısı' ?>
                            </div>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg text-center">
                            <div class="text-blue-500 font-bold text-2xl">
                                <i class="fas fa-eye"></i>
                                <span class="ml-2"><?= number_format($user['total_views']) ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?= $language == 'en' ? 'Total Views' : 'Toplam Görüntülenme' ?>
                            </div>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg text-center">
                            <div class="text-blue-500 font-bold text-2xl">
                                <i class="fas fa-calendar-alt"></i>
                                <?php
                                $registrationDate = strtotime($user['created_at']);
                                $now = time();
                                $daysDiff = ceil(($now - $registrationDate) / 86400);
                                ?>
                                <span class="ml-2"><?= number_format($daysDiff) ?> <?= $language == 'en' ? 'days' : 'gün' ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?= $language == 'en' ? 'Member Since' : 'Üyelik Süresi' ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 mt-4">
                        <?php if (isset($user['is_admin']) && $user['is_admin']): ?>
                        <h3 class="font-medium text-blue-600 dark:text-blue-400 mb-2">
                            <i class="fas fa-user-shield mr-2"></i><?= $language == 'en' ? 'Administrator Account' : 'Yönetici Hesabı' ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            <?= $language == 'en' ? 'Has access to all features' : 'Tüm özelliklere erişiminiz var' ?>
                        </p>
                        <?php elseif ($user['is_premium']): ?>
                        <h3 class="font-medium text-yellow-600 dark:text-yellow-400 mb-2">
                            <i class="fas fa-crown mr-2"></i><?= $language == 'en' ? 'Premium Subscriber' : 'Premium Abone' ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            <?= $language == 'en' ? 'Has access to premium features' : 'Premium özelliklere erişiminiz var' ?>
                        </p>
                        <?php else: ?>
                        <h3 class="font-medium text-gray-600 dark:text-gray-400 mb-2">
                            <i class="fas fa-user mr-2"></i><?= $language == 'en' ? 'Member' : 'Üye' ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            <?= $language == 'en' ? 'Has access to standard features' : 'Standart özelliklere erişiminiz var' ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mesaj Gönderme Modal -->
        <div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full mx-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    <?= $language == 'en' ? 'Send Message' : 'Mesaj Gönder' ?>
                </h3>
                <form id="sendMessageForm" onsubmit="sendMessage(event)">
                    <input type="hidden" id="recipient" name="recipient">
                    <div class="mb-4">
                        <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= $language == 'en' ? 'Subject' : 'Konu' ?>
                        </label>
                        <input type="text" id="subject" name="subject" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="<?= $language == 'en' ? 'Message subject' : 'Mesaj konusu' ?>">
                    </div>
                    <div class="mb-4">
                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= $language == 'en' ? 'Message' : 'Mesaj' ?>
                        </label>
                        <textarea id="content" name="content" required rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  placeholder="<?= $language == 'en' ? 'Write your message...' : 'Mesajınızı yazın...' ?>"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideMessageModal()"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">
                            <?= $language == 'en' ? 'Cancel' : 'İptal' ?>
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                            <?= $language == 'en' ? 'Send' : 'Gönder' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function showMessageModal(username) {
            <?php if (!isAdmin() && !isPremium()): ?>
                alert('<?= $language == 'en' ? 'Only premium members and administrators can send messages.' : 'Sadece premium üyeler ve yöneticiler mesaj gönderebilir.' ?>');
                return;
            <?php endif; ?>
            document.getElementById('recipient').value = username;
            document.getElementById('messageModal').classList.remove('hidden');
            document.getElementById('messageModal').classList.add('flex');
        }

        function hideMessageModal() {
            document.getElementById('messageModal').classList.remove('flex');
            document.getElementById('messageModal').classList.add('hidden');
        }

        function sendMessage(event) {
            event.preventDefault();
            
            <?php if (!$currentUser['is_approved']): ?>
            alert('<?= $language == 'en' ? 'Your account needs to be approved before you can send messages.' : 'Mesaj gönderebilmek için hesabınızın onaylanması gerekiyor.' ?>');
            return;
            <?php endif; ?>
            
            const form = event.target;
            const formData = new FormData(form);
            
            fetch('/api/send-message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.headers.get('content-type')?.includes('application/json')) {
                    return response.json().then(data => {
                        throw new Error(data.error);
                    });
                }
                return response.text();
            })
            .then(html => {
                // HTML yanıtını sayfaya yerleştir
                document.body.innerHTML = html;
            })
            .catch(error => {
                alert('<?= $language == 'en' ? 'Error: ' : 'Hata: ' ?>' + error.message);
            });
        }
        </script>

        <!-- Hakkında -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-user mr-2 text-blue-500"></i><?= $language == 'en' ? 'About' : 'Hakkında' ?>
            </h2>
            <div class="text-gray-600 dark:text-gray-400">
                <p><?= htmlspecialchars($user['bio'] ?? ($language == 'en' ? 'No biography added yet.' : 'Henüz bir biyografi eklenmemiş.')) ?></p>
            </div>
            <div class="text-right mt-2">
                <span class="text-sm text-gray-500"><?= number_format(13) ?></span>
            </div>
        </div>
        
        <!-- Sosyal Medya -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-share-alt mr-2 text-blue-500"></i><?= $language == 'en' ? 'Social Media' : 'Sosyal Medya' ?>
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php if (!empty($user['website'])): ?>
                <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-globe text-gray-800 dark:text-gray-200"></i>
                    <span class="ml-2"><?= $language == 'en' ? 'Website' : 'Website' ?></span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['twitter'])): ?>
                <a href="<?= htmlspecialchars($user['twitter']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-blue-50 dark:bg-blue-900 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition">
                    <svg class="w-5 h-5 text-[#1DA1F2]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                    <span class="ml-2">Twitter</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['facebook'])): ?>
                <a href="<?= htmlspecialchars($user['facebook']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-blue-50 dark:bg-blue-900 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition">
                    <svg class="w-5 h-5 text-[#1877F2]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span class="ml-2">Facebook</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['instagram'])): ?>
                <a href="<?= htmlspecialchars($user['instagram']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-pink-50 dark:bg-pink-900 rounded-lg hover:bg-pink-100 dark:hover:bg-pink-800 transition">
                    <svg class="w-5 h-5 text-[#E4405F]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                    </svg>
                    <span class="ml-2">Instagram</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['linkedin'])): ?>
                <a href="<?= htmlspecialchars($user['linkedin']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-blue-50 dark:bg-blue-900 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition">
                    <svg class="w-5 h-5 text-[#0A66C2]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    <span class="ml-2">LinkedIn</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['github'])): ?>
                <a href="<?= htmlspecialchars($user['github']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-gray-800 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
                    </svg>
                    <span class="ml-2">GitHub</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['youtube'])): ?>
                <a href="<?= htmlspecialchars($user['youtube']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-red-50 dark:bg-red-900 rounded-lg hover:bg-red-100 dark:hover:bg-red-800 transition">
                    <svg class="w-5 h-5 text-[#FF0000]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="ml-2">YouTube</span>
                </a>
                <?php endif; ?>

                <?php if (!empty($user['tiktok'])): ?>
                <a href="<?= htmlspecialchars($user['tiktok']) ?>" target="_blank" class="flex items-center justify-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-black dark:text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                    </svg>
                    <span class="ml-2">TikTok</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Makaleler -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-newspaper mr-2 text-blue-500"></i>
                <?php 
                if ($showAll) {
                    echo $language == 'en' ? 'All Articles by ' . htmlspecialchars($user['username']) : htmlspecialchars($user['username']) . ' Tarafından Yazılmış Tüm Makaleler';
                } else {
                    echo $language == 'en' ? 'Recent Articles by ' . htmlspecialchars($user['username']) : htmlspecialchars($user['username']) . ' Tarafından Yazılmış Son Makaleler';
                }
                ?>
            </h2>
            <?php if (empty($articles)): ?>
            <p class="text-gray-600 dark:text-gray-400 text-center py-4">
                <?= $language == 'en' ? 'No articles published yet.' : 'Henüz makale yayınlanmamış.' ?>
            </p>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($articles as $article): ?>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden">
                    <a href="/makale/<?= $article['slug'] ?>">
                        <div class="h-40 bg-gray-200 dark:bg-gray-600 relative">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-image text-4xl text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <?php if (!empty($article['featured_image'])): ?>
                            <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="w-full h-full object-cover">
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <div class="mb-2">
                                <span class="inline-block bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs px-2 py-1 rounded">
                                    <?= htmlspecialchars($article['category_title']) ?>
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">
                                <?= htmlspecialchars($article['title']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <?= htmlspecialchars(substr($article['excerpt'] ?? '', 0, 100)) ?>...
                            </p>
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= date('d.m.Y', strtotime($article['created_at'])) ?>
                                </span>
                                <span>
                                    <i class="far fa-eye mr-1"></i>
                                    <?= number_format($article['views_count']) ?> <?= $language == 'en' ? 'views' : 'görüntüleme' ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-6">
                <?php if (!$showAll && $user['article_count'] > 10): ?>
                <a href="/uye/<?= htmlspecialchars($user['username']) ?>?show=all" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <?= $language == 'en' ? 'View all articles' : 'Tüm makaleleri görüntüle' ?> <span class="ml-1">(<?= number_format($user['article_count']) ?>)</span>
                </a>
                <?php elseif ($showAll): ?>
                <a href="/uye/<?= htmlspecialchars($user['username']) ?>" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    <?= $language == 'en' ? 'Show less' : 'Daha az göster' ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>