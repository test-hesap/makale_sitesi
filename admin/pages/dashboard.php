<!-- İstatistik Kartları -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Toplam Makaleler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Makale</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['articles']['total']; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <?php echo $stats['articles']['published']; ?> yayında, <?php echo $stats['articles']['draft']; ?> taslak
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-alt text-xl text-blue-600 dark:text-blue-400"></i>
            </div>
        </div>
        <?php if ($stats['articles']['pending'] > 0): ?>
        <div class="mt-4 bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-300 p-2 rounded text-sm">
            <i class="fas fa-clock mr-1"></i><?php echo $stats['articles']['pending']; ?> makale onay bekliyor
        </div>
        <?php endif; ?>
    </div>

    <!-- Toplam Kullanıcılar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kullanıcı</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['users']['total']; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <?php echo $stats['users']['active']; ?> aktif, <?php echo $stats['users']['premium']; ?> premium
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-xl text-green-600 dark:text-green-400"></i>
            </div>
        </div>
        <?php if ($stats['users']['pending'] > 0): ?>
        <div class="mt-4 bg-orange-50 dark:bg-orange-900 text-orange-700 dark:text-orange-300 p-2 rounded text-sm">
            <i class="fas fa-user-clock mr-1"></i><?php echo $stats['users']['pending']; ?> kullanıcı onay bekliyor
        </div>
        <?php endif; ?>
    </div>

    <!-- Toplam Yorumlar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Yorum</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stats['comments']['total']; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <?php echo $stats['comments']['approved']; ?> onaylı
                </p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-comments text-xl text-yellow-600 dark:text-yellow-400"></i>
            </div>
        </div>
        <?php if ($stats['comments']['pending'] > 0): ?>
        <div class="mt-4 bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-300 p-2 rounded text-sm">
            <i class="fas fa-clock mr-1"></i><?php echo $stats['comments']['pending']; ?> yorum onay bekliyor
        </div>
        <?php endif; ?>
    </div>

    <!-- Toplam Gelir -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Gelir</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['payments']['revenue'] ?? 0, 2); ?> ₺</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <?php echo $stats['payments']['success'] ?? 0; ?> başarılı ödeme
                </p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-xl text-purple-600 dark:text-purple-400"></i>
            </div>
        </div>
    </div>
</div>

<!-- Hızlı Aksiyonlar ve Yönetim Paneli -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Hızlı İşlemler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Hızlı İşlemler</h3>
        <div class="grid grid-cols-2 gap-4">
            <a href="?page=articles&action=add" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg text-center transition-colors group">
                <i class="fas fa-plus text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <div class="text-sm font-medium">Yeni Makale</div>
            </a>
            <a href="?page=categories" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg text-center transition-colors group">
                <i class="fas fa-tags text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <div class="text-sm font-medium">Kategori Ekle</div>
            </a>
            <a href="?page=users" class="bg-yellow-600 hover:bg-yellow-700 text-white p-4 rounded-lg text-center transition-colors group">
                <i class="fas fa-users text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <div class="text-sm font-medium">Kullanıcılar</div>
            </a>
            <a href="?page=comments" class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-lg text-center transition-colors group">
                <i class="fas fa-comments text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                <div class="text-sm font-medium">Yorumlar</div>
            </a>
        </div>
    </div>

    <!-- Sistem Durumu -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Sistem Durumu</h3>
        <div class="space-y-4">
            <?php
            // Veritabanı durumunu kontrol et
            $db_status = isset($database_error) ? false : true;
            
            // Cache sistemini kontrol et
            $cache_dir = BASE_PATH . '/cache';
            $cache_status = is_dir($cache_dir) && is_writable($cache_dir);
            
            // Yedekleme durumunu kontrol et
            $backup_dir = BASE_PATH . '/database/backups';
            $backup_status = is_dir($backup_dir) && is_writable($backup_dir);
            $backup_files = glob($backup_dir . '/*.sql');
            $backup_time = !empty($backup_files) ? max(array_map('filemtime', $backup_files)) : 0;
            $backup_age = time() - $backup_time;
            $backup_warning = $backup_time > 0 && $backup_age > (7 * 24 * 60 * 60); // 7 günden eski yedek
            
            // Mail servisini kontrol et
            $mail_settings = [
                'smtp_host' => getSiteSetting('smtp_host'),
                'smtp_port' => getSiteSetting('smtp_port'),
                'smtp_username' => getSiteSetting('smtp_username'),
                'smtp_password' => getSiteSetting('smtp_password')
            ];
            $mail_status = !empty($mail_settings['smtp_host']) && !empty($mail_settings['smtp_port']) && 
                          !empty($mail_settings['smtp_username']) && !empty($mail_settings['smtp_password']);
            ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 <?php echo $db_status ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Veritabanı</span>
                </div>
                <span class="text-sm font-medium <?php echo $db_status ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $db_status ? 'Aktif' : 'Bağlantı Hatası'; ?>
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 <?php echo $cache_status ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Cache Sistemi</span>
                </div>
                <span class="text-sm font-medium <?php echo $cache_status ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $cache_status ? 'Çalışıyor' : 'Hata'; ?>
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 <?php echo $backup_status ? ($backup_warning ? 'bg-yellow-500' : 'bg-green-500') : 'bg-red-500'; ?> rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Yedekleme</span>
                </div>
                <span class="text-sm font-medium <?php echo $backup_status ? ($backup_warning ? 'text-yellow-600' : 'text-green-600') : 'text-red-600'; ?>">
                    <?php 
                    if (!$backup_status) {
                        echo 'Hata';
                    } elseif ($backup_time == 0) {
                        echo 'Yedek Yok';
                    } elseif ($backup_warning) {
                        echo 'Güncel Değil (' . timeAgo($backup_time) . ')';
                    } else {
                        echo 'Güncel (' . timeAgo($backup_time) . ')';
                    }
                    ?>
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 <?php echo $mail_status ? 'bg-green-500' : 'bg-yellow-500'; ?> rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Mail Servisi</span>
                </div>
                <span class="text-sm font-medium <?php echo $mail_status ? 'text-green-600' : 'text-yellow-600'; ?>">
                    <?php echo $mail_status ? 'Aktif' : 'Yapılandırılmamış'; ?>
                </span>
            </div>
        </div>
        <div class="mt-6">
            <a href="?page=settings" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Sistem Ayarları → 
            </a>
        </div>
    </div>

    <!-- Son Aktiviteler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Son Aktiviteler</h3>
        <div class="space-y-4">
            <?php if (!empty($recentActivities)): ?>
                <?php foreach ($recentActivities as $activity): ?>
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 <?php 
                        echo $activity['type'] == 'article' ? 'bg-blue-100 dark:bg-blue-900' : 
                             ($activity['type'] == 'user' ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900'); 
                    ?> rounded-full flex items-center justify-center">
                        <i class="fas <?php 
                            echo $activity['type'] == 'article' ? 'fa-file-alt text-blue-600 dark:text-blue-400' : 
                                 ($activity['type'] == 'user' ? 'fa-user text-green-600 dark:text-green-400' : 'fa-comment text-yellow-600 dark:text-yellow-400'); 
                        ?> text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($activity['activity_text']); ?>
                            <?php if (!empty($activity['user_name'])): ?>
                                <span class="text-gray-500 dark:text-gray-400">- <?php echo htmlspecialchars($activity['user_name']); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if ($activity['type'] == 'article' && !empty($activity['title'])): ?>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate"><?php echo htmlspecialchars($activity['title']); ?></p>
                        <?php elseif ($activity['type'] == 'comment' && !empty($activity['title'])): ?>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">"<?php echo htmlspecialchars($activity['title']); ?>..."</p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?php echo timeAgo($activity['activity_time']); ?>
                        </p>
                    </div>
                    <?php if (isset($activity['status'])): ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        <?php echo $activity['status'] == 'published' || $activity['status'] == 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'; ?>">
                        <?php echo $activity['status'] == 'published' ? 'Yayında' : 
                                   ($activity['status'] == 'approved' ? 'Onaylı' : 'Bekliyor'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-clock text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Henüz aktivite bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-6">
            <a href="?page=activities" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Tüm Aktiviteler → 
            </a>
        </div>
    </div>
</div>

<!-- İçerik Yönetimi ve Genel Bakış -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Son Eklenen Makaleler -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Son Eklenen Makaleler</h3>
                <a href="?page=articles" class="text-blue-600 hover:text-blue-800 text-sm">Tümünü gör</a>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php if (!empty($recentArticles)): ?>
                    <?php foreach ($recentArticles as $article): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 mt-2 rounded-full <?php echo $article['status'] == 'published' ? 'bg-green-500' : ($article['status'] == 'pending' ? 'bg-yellow-500' : 'bg-gray-500'); ?>"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo $article['username']; ?> • <?php echo $article['category_name']; ?>
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                <?php echo timeAgo($article['created_at']); ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            <?php echo $article['status'] == 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                      ($article['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                       'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'); ?>">
                            <?php echo $article['status'] == 'published' ? 'Yayında' : ($article['status'] == 'pending' ? 'Bekliyor' : 'Taslak'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz makale bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- İstatistiksel Özet -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bu Ayın Özeti</h3>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <!-- Makale İstatistikleri -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Yayınlanan Makaleler</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $stats['articles']['published']; ?></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $stats['articles']['total'] > 0 ? ($stats['articles']['published'] / $stats['articles']['total']) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <!-- Kullanıcı İstatistikleri -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Kullanıcılar</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $stats['users']['active']; ?></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $stats['users']['total'] > 0 ? ($stats['users']['active'] / $stats['users']['total']) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <!-- Yorum İstatistikleri -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Onaylanan Yorumlar</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $stats['comments']['approved']; ?></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: <?php echo $stats['comments']['total'] > 0 ? ($stats['comments']['approved'] / $stats['comments']['total']) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <!-- Gelir İstatistikleri -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Aylık Gelir</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['payments']['revenue'] ?? 0, 2); ?> ₺</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: 75%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alt Bölüm: Bekleyen İşlemler ve Yeni Kullanıcılar -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Bekleyen Yorumlar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bekleyen Yorumlar</h3>
                <a href="?page=comments" class="text-blue-600 hover:text-blue-800 text-sm">Tümünü gör</a>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php if (!empty($pendingComments)): ?>
                    <?php foreach ($pendingComments as $comment): ?>
                    <div class="border-l-4 border-yellow-400 pl-4">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars(substr($comment['content'], 0, 80)) . '...'; ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong> • 
                            <?php echo htmlspecialchars(substr($comment['article_title'], 0, 30)) . '...'; ?>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            <?php echo timeAgo($comment['created_at']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Bekleyen yorum bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Son Kayıt Olan Kullanıcılar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Kullanıcılar</h3>
                <a href="?page=users" class="text-blue-600 hover:text-blue-800 text-sm">Tümünü gör</a>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php if (!empty($newUsers)): ?>
                    <?php foreach ($newUsers as $user): ?>
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-medium"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                <?php echo timeAgo($user['created_at']); ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            <?php echo $user['is_approved'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                            <?php echo $user['is_approved'] ? 'Onaylı' : 'Bekliyor'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz kullanıcı bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 