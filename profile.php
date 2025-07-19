<?php
require_once 'includes/header.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

// Dil ayarını al
$language = getCurrentLanguage();

$pageTitle = $language == 'en' ? 'My Profile - ' . getSiteSetting('site_title') : 'Profilim - ' . getSiteSetting('site_title');
$metaDescription = $language == 'en' ? 'User profile and settings.' : 'Kullanıcı profili ve ayarları.';

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Profil resmini düzeltme işlemi (bir kereliğine çalışacak)
if (!empty($currentUser['profile_image'])) {
    $profileImage = $currentUser['profile_image'];
    
    // Eğer yolda çift // varsa, tekli / ile değiştir
    if (strpos($profileImage, '//') !== false) {
        $profileImage = str_replace('//', '/', $profileImage);
        
        try {
            $database = new Database();
            $db = $database->pdo;
            $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$profileImage, $currentUser['id']]);
            $currentUser = getCurrentUser(); // Güncel bilgileri çek
        } catch (Exception $e) {
            error_log('Profil resmi düzeltme hatası: ' . $e->getMessage());
        }
    }
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_profile_image') {
        // Hata ayıklama bilgisi
        error_log('Profil resmi yükleme işlemi başladı');
        // Profil resmi yükleme işlemi
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                // Dosya adını benzersiz yap
                $newFilename = uniqid() . '_' . time() . '.' . $ext;
                $uploadDir = 'assets/images/profiles/';
                $uploadPath = $uploadDir . $newFilename;
                
                // Klasör yoksa oluştur
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    try {
                        $database = new Database();
                        $db = $database->pdo;
                        
                        // Eski profil resmini sil
                        if (!empty($currentUser['profile_image'])) {
                            $oldImagePath = $currentUser['profile_image'];
                            // Eğer başında / varsa kaldır
                            if (substr($oldImagePath, 0, 1) === '/') {
                                $oldImagePath = substr($oldImagePath, 1);
                            }
                            
                            if (file_exists($oldImagePath) && $oldImagePath != 'assets/images/her-bilgi-logo.png') {
                                @unlink($oldImagePath);
                            }
                        }
                        
                        // Veritabanını güncelle - Dosya yolunu düzgün şekilde kaydet
                        $cleanPath = str_replace('\\', '/', $uploadPath); // Windows için yol düzeltme
                        $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $stmt->execute([$cleanPath, $currentUser['id']]);
                        
                        $success = $language == 'en' ? 'Your profile image has been successfully updated.' : 'Profil resminiz başarıyla güncellendi.';
                        $currentUser = getCurrentUser(); // Güncel bilgileri çek
                    } catch (Exception $e) {
                        $error = $language == 'en' ? 'An error occurred while updating your profile image.' : 'Profil resmi güncellenirken bir hata oluştu.';
                    }
                } else {
                    $error = $language == 'en' ? 'An error occurred while uploading the file.' : 'Dosya yüklenirken bir hata oluştu.';
                }
            } else {
                $error = $language == 'en' ? 'Only JPG, JPEG, PNG and GIF formats are allowed.' : 'Sadece JPG, JPEG, PNG ve GIF formatları izin veriliyor.';
            }
        } else {
            $error = $language == 'en' ? 'Please select an image.' : 'Lütfen bir resim seçin.';
        }
    }
    else if ($action === 'update_profile') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        if (empty($username) || empty($email)) {
            $error = $language == 'en' ? 'Username and email fields are required.' : 'Kullanıcı adı ve e-posta alanları zorunludur.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $language == 'en' ? 'Please enter a valid email address.' : 'Geçerli bir e-posta adresi girin.';
        } else {
            try {
                $database = new Database();
                $db = $database->pdo;
                
                // E-posta çakışması kontrolü
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $currentUser['id']]);
                if ($stmt->fetch()) {
                    $error = $language == 'en' ? 'This email address is already in use.' : 'Bu e-posta adresi zaten kullanılıyor.';
                } else {
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $currentUser['id']]);
                    
                    $success = $language == 'en' ? 'Your profile information has been successfully updated.' : 'Profil bilgileriniz başarıyla güncellendi.';
                    $currentUser = getCurrentUser(); // Güncel bilgileri çek
                }
            } catch (Exception $e) {
                $error = 'Profil güncellenirken bir hata oluştu.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = $language == 'en' ? 'Please fill in all password fields.' : 'Tüm şifre alanlarını doldurun.';
        } elseif (strlen($newPassword) < 6) {
            $error = $language == 'en' ? 'New password must be at least 6 characters.' : 'Yeni şifre en az 6 karakter olmalıdır.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = $language == 'en' ? 'New passwords do not match.' : 'Yeni şifreler eşleşmiyor.';
        } elseif (!password_verify($currentPassword, $currentUser['password'])) {
            $error = $language == 'en' ? 'Your current password is incorrect.' : 'Mevcut şifreniz yanlış.';
        } else {
            try {
                $database = new Database();
                $db = $database->pdo;
                
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $currentUser['id']]);
                
                $success = $language == 'en' ? 'Your password has been successfully changed.' : 'Şifreniz başarıyla değiştirildi.';
            } catch (Exception $e) {
                $error = 'Şifre değiştirilirken bir hata oluştu.';
            }
        }
    } elseif ($action === 'update_social_media') {
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $website = sanitizeInput($_POST['website'] ?? '');
        $twitter = sanitizeInput($_POST['twitter'] ?? '');
        $facebook = sanitizeInput($_POST['facebook'] ?? '');
        $instagram = sanitizeInput($_POST['instagram'] ?? '');
        $linkedin = sanitizeInput($_POST['linkedin'] ?? '');
        $tiktok = sanitizeInput($_POST['tiktok'] ?? '');
        $youtube = sanitizeInput($_POST['youtube'] ?? '');
        $github = sanitizeInput($_POST['github'] ?? '');
        
        try {
            $database = new Database();
            $db = $database->pdo;
            
            $stmt = $db->prepare("UPDATE users SET 
                bio = ?, 
                location = ?, 
                website = ?,
                twitter = ?,
                facebook = ?,
                instagram = ?,
                linkedin = ?,
                tiktok = ?,
                youtube = ?,
                github = ?
                WHERE id = ?");
            
            $stmt->execute([
                $bio, 
                $location, 
                $website,
                $twitter,
                $facebook,
                $instagram,
                $linkedin,
                $tiktok,
                $youtube,
                $github,
                $currentUser['id']
            ]);
            
            $success = $language == 'en' ? 'Your social media and biography information have been successfully updated.' : 'Sosyal medya ve biyografi bilgileriniz başarıyla güncellendi.';
            $currentUser = getCurrentUser(); // Güncel bilgileri çek
        } catch (Exception $e) {
            $error = $language == 'en' ? 'An error occurred while updating your information.' : 'Bilgileriniz güncellenirken bir hata oluştu.';
        }
    }
}

// Hata ayıklama bilgisi ekleyerek profil resmi gösterme durumunu kontrol et
error_log('Profil resmi: ' . ($currentUser['profile_image'] ?? 'Yok'));

// Kullanıcı bilgilerini çek ve sosyal medya alanlarını kontrol et
try {
    $database = new Database();
    $db = $database->pdo;
    
    // Veritabanında sosyal medya alanlarının var olup olmadığını kontrol et
    $checkColumnsQuery = "SHOW COLUMNS FROM users LIKE 'twitter'";
    $checkColumns = $db->query($checkColumnsQuery);
    
    // Eğer twitter sütunu yoksa diğer sosyal medya alanlarını ekle
    if ($checkColumns->rowCount() == 0) {
        $alterTable = $db->prepare("
            ALTER TABLE users 
            ADD COLUMN location VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN website VARCHAR(255) NULL DEFAULT NULL,
            ADD COLUMN twitter VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN facebook VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN instagram VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN linkedin VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN tiktok VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN youtube VARCHAR(100) NULL DEFAULT NULL,
            ADD COLUMN github VARCHAR(100) NULL DEFAULT NULL
        ");
        $alterTable->execute();
    }
    
    // Profil resmi sütununu kontrol et
    $checkProfileImageQuery = "SHOW COLUMNS FROM users LIKE 'profile_image'";
    $checkProfileImage = $db->query($checkProfileImageQuery);
    
    // Eğer profile_image sütunu yoksa ekle
    if ($checkProfileImage->rowCount() == 0) {
        $alterTable = $db->prepare("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL DEFAULT NULL");
        $alterTable->execute();
    }
    
    // Kullanıcının makalelerini çek
    $stmt = $db->prepare("
        SELECT a.*, c.name as category_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.user_id = ? 
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$currentUser['id']]);
    $userArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İstatistikler
    $articleStats = $db->prepare("
        SELECT 
            COUNT(*) as total_articles,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_articles,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_articles,
            SUM(views) as total_views
        FROM articles 
        WHERE user_id = ?
    ");
    $articleStats->execute([$currentUser['id']]);
    $stats = $articleStats->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $userArticles = [];
    $stats = ['total_articles' => 0, 'published_articles' => 0, 'draft_articles' => 0, 'total_views' => 0];
}
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
        <?php if ($success): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?= $success ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Sol Sidebar - Profil Kartı -->
            <div class="md:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <div class="text-center">
                        <div class="w-24 h-24 bg-blue-500 rounded-full mx-auto mb-4 flex items-center justify-center overflow-hidden">
                            <?php
                            // Profil resmi görüntüleme - varsayılan resim
                            $profileImageUrl = '/assets/images/her-bilgi-logo.png';
                            
                            if (!empty($currentUser['profile_image'])) {
                                // Base64 kodlamasıyla resim gösterme (dosya erişim sorunu çözümü)
                                $imagePath = ltrim($currentUser['profile_image'], '/');
                                if (file_exists($imagePath) && is_readable($imagePath)) {
                                    $imageData = file_get_contents($imagePath);
                                    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                                    $base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                                    $profileImageUrl = $base64;
                                } else {
                                    // Varsayılan profil resmi
                                    $profileImageUrl = '/assets/images/her-bilgi-logo.png';
                                }
                            }
                            
                            // Hata ayıklama için yorum satırı
                            echo "<!-- Profil resmi: Base64 olarak kodlanmış -->";
                            ?>
                            <img src="<?= $profileImageUrl ?>" 
                                alt="<?= htmlspecialchars($currentUser['username']) ?>" 
                                class="w-24 h-24 object-cover rounded-full">
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                            <a href="/uye/<?= generateSlug($currentUser['username']) ?>" class="hover:text-primary-600">
                                <?= htmlspecialchars($currentUser['username']) ?>
                            </a>
                            <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin']): ?>
                            <span class="inline-block w-3 h-3 bg-green-500 rounded-full ml-2"></span>
                            <?php endif; ?>
                        </h2>
                        <p class="text-gray-500 dark:text-gray-400 mb-3">
                            <?= htmlspecialchars($currentUser['email']) ?>
                        </p>
                        
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 mt-4">
                            <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin']): ?>
                            <h3 class="font-medium text-blue-600 dark:text-blue-400 mb-2">
                                <i class="fas fa-user-shield mr-2"></i><?= $language == 'en' ? 'Administrator Account' : 'Yönetici Hesabı' ?>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                <?= $language == 'en' ? 'You have access to all features' : 'Tüm özelliklere erişiminiz var' ?>
                            </p>
                            <?php elseif (isset($currentUser['is_premium']) && $currentUser['is_premium']): ?>
                            <h3 class="font-medium text-yellow-600 dark:text-yellow-400 mb-2">
                                <i class="fas fa-crown mr-2"></i><?= $language == 'en' ? 'Premium Subscriber' : 'Premium Abone' ?>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                <?= $language == 'en' ? 'You have access to premium features' : 'Premium özelliklere erişiminiz var' ?>
                            </p>
                            <?php else: ?>
                            <h3 class="font-medium text-gray-600 dark:text-gray-400 mb-2">
                                <i class="fas fa-user mr-2"></i><?= $language == 'en' ? 'Member Account' : 'Üye Hesabı' ?>
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                <?= $language == 'en' ? 'You have access to standard features' : 'Standart özelliklere erişiminiz var' ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Profil Resmi Yükleme Formu -->
                        <form method="POST" enctype="multipart/form-data" class="mt-4" id="profile_image_form">
                            <input type="hidden" name="action" value="upload_profile_image">
                            <div id="file-selected" class="text-center text-sm text-gray-500 mb-2">
                                <?= $language == 'en' ? 'No file selected' : 'Seçilen dosya yok' ?>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition-colors flex items-center justify-center">
                                <i class="fas fa-upload mr-2"></i> <?= $language == 'en' ? 'Upload Image' : 'Resmi Yükle' ?>
                            </button>
                        </form>
                        
                        <div class="mt-4 space-y-2">
                            <label for="profile_image" class="flex items-center justify-center p-2 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-700 cursor-pointer">
                                <i class="fas fa-user-circle mr-2"></i> <?= $language == 'en' ? 'Select Profile Image' : 'Profil Resmi Seç' ?>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" form="profile_image_form" onchange="updateFileLabel(this)">
                            </label>
                            <a href="/makale_ekle" class="flex items-center justify-center p-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-file-alt mr-2"></i> <?= $language == 'en' ? 'Add Article' : 'Makale Ekle' ?>
                            </a>
                        </div>
                        
                        <script>
                        function updateFileLabel(input) {
                            const fileSelectedDiv = document.getElementById('file-selected');
                            const language = '<?= $language ?>';
                            if (input.files.length > 0) {
                                fileSelectedDiv.textContent = input.files[0].name;
                            } else {
                                fileSelectedDiv.textContent = language == 'en' ? 'No file selected' : 'Seçilen dosya yok';
                            }
                        }
                        </script>
                    </div>
                </div>
            </div>
            
            <!-- Sağ İçerik -->
            <div class="md:col-span-2">
                <!-- Profil Bilgileri -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                        <?= $language == 'en' ? 'Update Your Profile Information' : 'Profil Bilgilerinizi Güncelleyin' ?>
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'Username' : 'Kullanıcı Adı' ?>
                            </label>
                            <input type="text" id="username" name="username" required
                                   value="<?= htmlspecialchars($currentUser['username']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'Email Address' : 'E-posta Adresi' ?>
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="<?= htmlspecialchars($currentUser['email']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div class="text-right">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <i class="fas fa-save mr-2"></i> <?= $language == 'en' ? 'Update Email Address' : 'E-posta Adresini Güncelle' ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Şifre Değiştir -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                        <?= $language == 'en' ? 'Change Password' : 'Şifre Değiştir' ?>
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'Current Password' : 'Mevcut Şifre' ?>
                            </label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'New Password' : 'Yeni Şifre' ?>
                            </label>
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'New Password (Repeat)' : 'Yeni Şifre (Tekrar)' ?>
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div class="text-right">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <i class="fas fa-key mr-2"></i> <?= $language == 'en' ? 'Change Password' : 'Şifreyi Değiştir' ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Sosyal Medya ve Biyografi -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                        <?= $language == 'en' ? 'Social Media and Biography Information' : 'Sosyal Medya ve Biyografi Bilgileri' ?>
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_social_media">
                        
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'Biography' : 'Biyografi' ?>
                            </label>
                            <textarea id="bio" name="bio" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                      placeholder="<?= $language == 'en' ? 'Brief information about yourself...' : 'Kendiniz hakkında kısa bilgi...' ?>"><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'Location' : 'Konum' ?>
                            </label>
                            <input type="text" id="location" name="location" 
                                   value="<?= htmlspecialchars($currentUser['location'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                   placeholder="<?= $language == 'en' ? 'Istanbul, Turkey' : 'İstanbul, Türkiye' ?>">
                        </div>
                        
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= $language == 'en' ? 'Website' : 'Web Sitesi' ?>
                            </label>
                            <input type="url" id="website" name="website" 
                                   value="<?= htmlspecialchars($currentUser['website'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                   placeholder="https://example.com">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="twitter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-twitter text-blue-400 mr-1"></i> Twitter
                                </label>
                                <input type="text" id="twitter" name="twitter" 
                                       value="<?= htmlspecialchars($currentUser['twitter'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="@kullaniciadi">
                            </div>
                            
                            <div>
                                <label for="facebook" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook
                                </label>
                                <input type="text" id="facebook" name="facebook" 
                                       value="<?= htmlspecialchars($currentUser['facebook'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="kullaniciadi">
                            </div>
                            
                            <div>
                                <label for="instagram" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-instagram text-pink-600 mr-1"></i> Instagram
                                </label>
                                <input type="text" id="instagram" name="instagram" 
                                       value="<?= htmlspecialchars($currentUser['instagram'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="kullaniciadi">
                            </div>
                            
                            <div>
                                <label for="linkedin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-linkedin text-blue-700 mr-1"></i> LinkedIn
                                </label>
                                <input type="text" id="linkedin" name="linkedin" 
                                       value="<?= htmlspecialchars($currentUser['linkedin'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="kullaniciadi">
                            </div>
                            
                            <div>
                                <label for="youtube" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-youtube text-red-600 mr-1"></i> YouTube
                                </label>
                                <input type="text" id="youtube" name="youtube" 
                                       value="<?= htmlspecialchars($currentUser['youtube'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="kanal-adi">
                            </div>
                            
                            <div>
                                <label for="tiktok" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-tiktok text-gray-800 mr-1"></i> TikTok
                                </label>
                                <input type="text" id="tiktok" name="tiktok" 
                                       value="<?= htmlspecialchars($currentUser['tiktok'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="@kullaniciadi">
                            </div>
                            
                            <div>
                                <label for="github" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fab fa-github text-gray-900 dark:text-white mr-1"></i> GitHub
                                </label>
                                <input type="text" id="github" name="github" 
                                       value="<?= htmlspecialchars($currentUser['github'] ?? '') ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="kullaniciadi">
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i> <?= $language == 'en' ? 'Save Profile Information' : 'Profil Bilgilerini Kaydet' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>