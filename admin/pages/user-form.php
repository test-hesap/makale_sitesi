<?php
// Kullanıcı ekleme/düzenleme formu

// Varsayılan değerler
$formData = [
    'id' => 0,
    'username' => '',
    'email' => '',
    'bio' => '',
    'is_admin' => false,
    'is_approved' => true
];

$isEdit = false;
$pageTitle = 'Yeni Kullanıcı Ekle';

// Düzenleme modu için kullanıcı bilgilerini al
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $formData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'bio' => $user['bio'] ?? '',
                'is_admin' => (bool)$user['is_admin'],
                'is_approved' => (bool)$user['is_approved']
            ];
            
            $isEdit = true;
            $pageTitle = 'Kullanıcıyı Düzenle: ' . htmlspecialchars($user['username']);
        }
    } catch (Exception $e) {
        $error = "Kullanıcı bilgileri alınırken hata oluştu: " . $e->getMessage();
    }
}
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
    <!-- Başlık -->
    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-user-plus mr-2"></i>
                <?= $pageTitle ?>
            </h2>
            <a href="?page=users" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri Dön
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="p-6">
        <?php if (isset($error)): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="?page=users">
            <input type="hidden" name="action" value="<?= $isEdit ? 'edit_user' : 'add_user' ?>">
            <?php if ($isEdit): ?>
            <input type="hidden" name="user_id" value="<?= $formData['id'] ?>">
            <?php endif; ?>
            
            <div class="space-y-4 max-w-md">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kullanıcı Adı <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($formData['username']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="Kullanıcı adını girin">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        E-posta <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($formData['email']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="E-posta adresini girin">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Şifre <?php echo $isEdit ? '' : '<span class="text-red-500">*</span>'; ?>
                    </label>
                    <input type="password" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?>
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                           placeholder="<?php echo $isEdit ? 'Değiştirmek için yeni şifre girin' : 'Şifreyi girin'; ?>" minlength="6">
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo $isEdit ? 'Şifreyi değiştirmek istiyorsanız doldurun, boş bırakırsanız şifre değişmez' : 'En az 6 karakter olmalıdır'; ?>
                    </p>
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Biyografi
                    </label>
                    <textarea id="bio" name="bio" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              placeholder="Kısa biyografi..."><?= htmlspecialchars($formData['bio']) ?></textarea>
                </div>
            </div>

            <!-- Yetkiler -->
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Yetkilendirme</h3>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_approved" name="is_approved" 
                               <?= $formData['is_approved'] ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="is_approved" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Onaylı kullanıcı (siteye giriş yapabilir)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_admin" name="is_admin" 
                               <?= $formData['is_admin'] ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="is_admin" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Admin yetkisi (yönetim paneline erişim)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Butonlar -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="?page=users" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
                    İptal
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo $isEdit ? 'Değişiklikleri Kaydet' : 'Kullanıcıyı Ekle'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validasyonu
document.querySelector('form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const isEditMode = document.querySelector('input[name="action"]').value === 'edit_user';
    
    if (username.length < 3) {
        e.preventDefault();
        alert('Kullanıcı adı en az 3 karakter olmalıdır.');
        return;
    }
    
    // Şifre kontrolü - düzenleme modunda şifre boş bırakılabilir
    if (!isEditMode && password.length < 6) {
        e.preventDefault();
        alert('Şifre en az 6 karakter olmalıdır.');
        return;
    }
    
    // Şifre dolu girilmişse uzunluk kontrolü
    if (isEditMode && password.length > 0 && password.length < 6) {
        e.preventDefault();
        alert('Şifre en az 6 karakter olmalıdır.');
        return;
    }
    
    // E-posta formatı kontrolü
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Geçerli bir e-posta adresi girin.');
        return;
    }
});
</script>
