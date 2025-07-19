<?php
// Hata kontrolü
if (!defined('BASE_PATH')) {
    header('Location: /admin/');
    exit;
}

// Gerekli dosyaları dahil et
require_once '../includes/functions.php';
require_once '../config/database.php';

// Yedekleme dizini kontrolü
$backup_dir = '../database/backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Otomatik yedekleme ayarları
if (isset($_POST['save_backup_settings'])) {
    try {
        $auto_backup_enabled = isset($_POST['auto_backup_enabled']) ? '1' : '0';
        $backup_frequency = sanitizeInput($_POST['backup_frequency'] ?? 'daily');
        $backup_retention = sanitizeInput($_POST['backup_retention'] ?? '7');
        
        // Debug için değerleri logla
        error_log("Backup settings: enabled=$auto_backup_enabled, frequency=$backup_frequency, retention=$backup_retention");
        
        // Ayarları kaydet
        updateSetting('auto_backup_enabled', $auto_backup_enabled);
        updateSetting('backup_frequency', $backup_frequency);
        updateSetting('backup_retention', $backup_retention);
        
        // Cache'i temizle ve yeniden yükle
        refreshSettings();
        
        // Kaydedildikten sonra tekrar kontrol et
        $saved_enabled = getSetting('auto_backup_enabled', '0');
        $saved_frequency = getSetting('backup_frequency', 'daily');
        $saved_retention = getSetting('backup_retention', '7');
        
        error_log("Saved values: enabled=$saved_enabled, frequency=$saved_frequency, retention=$saved_retention");
        
        $success_message = 'Otomatik yedekleme ayarları başarıyla kaydedildi. (Etkin: ' . ($auto_backup_enabled == '1' ? 'Evet' : 'Hayır') . ', Sıklık: ' . $backup_frequency . ', Saklama: ' . $backup_retention . ' gün)';
        
    } catch (Exception $e) {
        $error_message = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
    }
}

// Son yedekleme zamanını sıfırla
if (isset($_POST['reset_last_backup'])) {
    try {
        updateSetting('last_auto_backup', '');
        $success_message = 'Son yedekleme zamanı sıfırlandı. Artık hemen yedekleme yapılabilir.';
    } catch (Exception $e) {
        $error_message = 'Son yedekleme zamanı sıfırlanırken hata oluştu: ' . $e->getMessage();
    }
}

// Yedekleme listesini al
$backups = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '/*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => formatBytes(filesize($file)),
            'date' => date('d.m.Y H:i:s', filemtime($file)),
            'path' => $file
        ];
    }
    // Tarihe göre sırala (en yeni en üstte)
    usort($backups, function($a, $b) {
        return filemtime($b['path']) - filemtime($a['path']);
    });
}

// Yedekleme işlemi
if (isset($_POST['create_backup'])) {
    try {
        $database = new Database();
        $pdo = $database->pdo;
        
        $date = date('Y-m-d_H-i-s');
        $backup_filename = 'backup_' . $date . '.sql';
        $backup_file = $backup_dir . '/' . $backup_filename;
        
        // Tüm tabloları al
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $return = '';
        
        // Her tablo için yapıyı ve verileri yedekle
        foreach ($tables as $table) {
            // Tablo yapısını al
            $result = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch(PDO::FETCH_NUM);
            $return .= "\n\n" . $row[1] . ";\n\n";
            
            // Tablo verilerini al
            $result = $pdo->query("SELECT * FROM `$table`");
            $num_fields = $result->columnCount();
            
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    if (isset($row[$j])) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= 'NULL';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
            $return .= "\n";
        }
        
        // Yedeği dosyaya kaydet
        if (file_put_contents($backup_file, $return)) {
            $success_message = 'Yedekleme başarıyla oluşturuldu.';
            
            // Yeni oluşturulan yedeği listeye ekle
            $new_backup = [
                'name' => $backup_filename,
                'size' => formatBytes(filesize($backup_file)),
                'date' => date('d.m.Y H:i:s', filemtime($backup_file)),
                'path' => $backup_file
            ];
            
            // Yedeği listenin başına ekle
            array_unshift($backups, $new_backup);
            
            // Sayfanın yeniden yüklenmesinde yeniden yedek oluşturulmasını önlemek için
            // JavaScript ile form gönderimini önleme
            echo '<script>
            // Tarayıcı geçmişine yeni bir durum ekleyerek sayfa yenileme durumunu önler
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            </script>';
        } else {
            $error_message = 'Yedekleme dosyası oluşturulamadı.';
        }
        
    } catch (Exception $e) {
        $error_message = 'Yedekleme işlemi başarısız: ' . $e->getMessage();
    }
}

// Mevcut ayarları al (her zaman fresh data)
$auto_backup_enabled = getSetting('auto_backup_enabled', '0');
$backup_frequency = getSetting('backup_frequency', 'daily');
$backup_retention = getSetting('backup_retention', '7');

// Debug: mevcut değerleri logla
error_log("Current settings: enabled=$auto_backup_enabled, frequency=$backup_frequency, retention=$backup_retention");

// Dosya boyutu formatı
function formatBytes($bytes) {
    if ($bytes > 0) {
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return sprintf('%.2f', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }
    return '0 B';
}
?>

<div class="space-y-6">
    <!-- Üst Bilgi ve Yedekleme Butonu -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Veritabanı Yedekleri</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Veritabanınızın yedeğini alın ve yönetin</p>
        </div>
        <form method="post" class="flex items-center space-x-3">
            <button type="submit" name="create_backup" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-download mr-2"></i>
                Yedek Oluştur
            </button>
        </form>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo $success_message; ?></span>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo $error_message; ?></span>
    </div>
    <?php endif; ?>

    <!-- Yedekleme Listesi -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dosya Adı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Boyut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            Henüz yedekleme bulunmuyor
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                    <tr data-backup="<?php echo htmlspecialchars($backup['name']); ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($backup['name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo $backup['size']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo $backup['date']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="<?php echo $backup['path']; ?>" download class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="#" onclick="deleteBackup('<?php echo $backup['name']; ?>')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Otomatik Yedekleme Ayarları -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Otomatik Yedekleme Ayarları</h3>
        <form method="post" class="space-y-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="auto_backup_enabled" value="1" 
                           <?php echo $auto_backup_enabled == '1' ? 'checked' : ''; ?>
                           class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Otomatik yedeklemeyi etkinleştir</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yedekleme Sıklığı</label>
                    <select name="backup_frequency" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="daily" <?php echo $backup_frequency == 'daily' ? 'selected' : ''; ?>>Günlük</option>
                        <option value="weekly" <?php echo $backup_frequency == 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                        <option value="monthly" <?php echo $backup_frequency == 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Saklama Süresi</label>
                    <select name="backup_retention" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="7" <?php echo $backup_retention == '7' ? 'selected' : ''; ?>>7 gün</option>
                        <option value="30" <?php echo $backup_retention == '30' ? 'selected' : ''; ?>>30 gün</option>
                        <option value="90" <?php echo $backup_retention == '90' ? 'selected' : ''; ?>>90 gün</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-2">
                <form method="post" class="inline">
                    <button type="submit" name="reset_last_backup" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" onclick="return confirm('Son yedekleme zamanını sıfırlamak istediğinizden emin misiniz?')">
                        Zamanı Sıfırla
                    </button>
                </form>
                <button type="submit" name="save_backup_settings" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- Cron Job Kurulum Bilgileri -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Cron Job Kurulumu</h3>
        
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-1"></i>
                <div>
                    <h4 class="font-medium text-yellow-800 dark:text-yellow-200">Önemli Not</h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Otomatik yedeklemenin çalışması için sunucunuzda aşağıdaki cron job'u kurmanız gerekiyor.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cron Job Komutu:</label>
                <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg font-mono text-sm flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <button onclick="copyCronCommand()" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <code id="backup-cron-code">0 2 * * * /usr/bin/wget -q -O /dev/null <?php echo getSiteUrl(); ?>backup-cron.php</code>
                </div>
                
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Bu komut her gece saat 02:00'da otomatik yedeklemeyi çalıştıracaktır.
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Cron Bilgileri -->
            <div class="space-y-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Cron Job Bilgileri</h4>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Script URL:</span>
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?php echo getSiteUrl(); ?>backup-cron.php</code>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Son Çalışma:</span>
                        <span class="text-gray-900 dark:text-white">
                            <?php 
                            $last_run = getSetting('last_auto_backup', '');
                            echo $last_run ? date('d.m.Y H:i:s', strtotime($last_run)) : 'Henüz çalışmadı';
                            ?>
                        </span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Log Dosyası:</span>
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">/logs/backup_cron.log</code>
                    </div>
                </div>
            </div>
            
            <!-- Cron Test -->
            <div class="space-y-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Test Cron Job</h4>
                
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Cron job'u test etmek için aşağıdaki butona tıklayın:
                </p>
                
                <form method="GET" action="<?php echo getSiteUrl(); ?>backup-cron.php?test=1" target="_blank">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-play mr-2"></i>
                        Cron Job'u Test Et
                    </button>
                </form>
                
                <form method="GET" action="<?php echo getSiteUrl(); ?>backup-cron.php?force=1" target="_blank" class="mt-2">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-bolt mr-2"></i>
                        Zorla Yedekle
                    </button>
                </form>
                
                <form method="GET" action="<?php echo getSiteUrl(); ?>test-backup.php" target="_blank" class="mt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-bug mr-2"></i>
                        Debug Test
                    </button>
                </form>
                
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Test sonucu yeni sekmede açılacaktır.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteBackup(filename) {
    if (confirm('Bu yedeği silmek istediğinizden emin misiniz?')) {
        // AJAX ile silme işlemi
        fetch('api/delete-backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                filename: filename
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Sayfayı yenilemek yerine DOM'dan yedeği kaldır
                const backupRow = document.querySelector(`tr[data-backup="${filename}"]`);
                if (backupRow) {
                    backupRow.remove();
                    // Eğer tüm yedekler silindiyse "henüz yedekleme bulunmuyor" mesajını göster
                    const remainingBackups = document.querySelectorAll('tbody tr[data-backup]');
                    if (remainingBackups.length === 0) {
                        const tbody = document.querySelector('tbody');
                        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Henüz yedekleme bulunmuyor</td></tr>';
                    }
                } else {
                    // Eğer DOM'dan bulunamazsa sayfayı yenile
                    location.reload();
                }
            } else {
                alert('Yedek silinirken bir hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    }
}

function copyCronCommand() {
    const cronCode = document.getElementById('backup-cron-code').textContent;
    navigator.clipboard.writeText(cronCode).then(function() {
        // Başarı mesajı göster
        const button = event.target;
        const originalIcon = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.className = 'text-green-600';
        
        setTimeout(() => {
            button.innerHTML = originalIcon;
            button.className = 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300';
        }, 2000);
    }).catch(function(err) {
        console.error('Kopyalama hatası: ', err);
        alert('Kopyalama başarısız oldu');
    });
}
</script> 