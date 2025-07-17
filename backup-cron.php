<?php
/**
 * Backup Cron Job Script
 * Bu script cron job ile çalıştırılarak otomatik yedekleme yapar
 */

// Hata ayıklama için
error_reporting(E_ALL);
// Cron job için display_errors kapalı, test için açık
ini_set('display_errors', isset($_GET['test']) ? 1 : 0);

// Base path tanımı
define('APP_ROOT', __DIR__);
define('BASE_PATH', __DIR__);

// Cron job için session başlatmayı atla
$_SESSION = [];

require_once 'config/database.php';
require_once 'includes/functions.php';

// Log dosyası
$log_file = APP_ROOT . '/logs/backup_cron.log';

function writeBackupLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    
    // Logs dizini yoksa oluştur
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

try {
    writeBackupLog("Backup cron job başlatıldı");
    
    // Otomatik yedekleme etkin mi kontrol et
    $auto_backup_enabled = getSetting('auto_backup_enabled', '0');
    if ($auto_backup_enabled != '1') {
        writeBackupLog("Otomatik yedekleme devre dışı - işlem sonlandırıldı");
        echo "Backup disabled\n";
        exit(0);
    }
    
    // Yedekleme sıklığını al
    $backup_frequency = getSetting('backup_frequency', 'daily');
    $backup_retention = getSetting('backup_retention', '7');
    
    writeBackupLog("Yedekleme ayarları: Sıklık=$backup_frequency, Saklama=$backup_retention gün");
    
    // Son yedekleme zamanını kontrol et
    $last_backup = getSetting('last_auto_backup', '');
    $should_backup = false;
    
    writeBackupLog("Debug: last_backup değeri: '$last_backup'");
    
    if (empty($last_backup)) {
        $should_backup = true;
        writeBackupLog("İlk otomatik yedekleme yapılacak");
    } else {
        $last_backup_time = strtotime($last_backup);
        $current_time = time();
        $time_diff = $current_time - $last_backup_time;
        
        writeBackupLog("Debug: last_backup_time: $last_backup_time, current_time: $current_time, time_diff: $time_diff");
        
        switch ($backup_frequency) {
            case 'daily':
                $should_backup = $time_diff >= 86400; // 24 saat
                $required_time = 86400;
                break;
            case 'weekly':
                $should_backup = $time_diff >= 604800; // 7 gün
                $required_time = 604800;
                break;
            case 'monthly':
                $should_backup = $time_diff >= 2592000; // 30 gün
                $required_time = 2592000;
                break;
        }
        
        writeBackupLog("Debug: required_time: $required_time, should_backup: " . ($should_backup ? 'true' : 'false'));
        writeBackupLog("Son yedekleme: $last_backup, Geçen süre: " . round($time_diff/3600, 2) . " saat");
    }
    
    // Test modunda zorla yedekleme yap
    if (isset($_GET['test']) || isset($_GET['force'])) {
        $should_backup = true;
        writeBackupLog("Test/Force modu - yedekleme zorlanıyor");
    }
    
    if (!$should_backup) {
        writeBackupLog("Henüz yedekleme zamanı gelmedi - işlem sonlandırıldı");
        echo "Backup not needed yet\n";
        exit(0);
    }
    
    // Yedekleme dizini kontrolü
    $backup_dir = APP_ROOT . '/database/backups';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
        writeBackupLog("Yedekleme dizini oluşturuldu: $backup_dir");
    }
    
    // Yedekleme oluştur
    if (createAutomaticBackup()) {
        writeBackupLog("Yedekleme başarıyla oluşturuldu");
        
        // Son yedekleme zamanını kaydet
        updateSetting('last_auto_backup', date('Y-m-d H:i:s'));
        
        // Eski yedekleri temizle
        cleanOldBackups($backup_retention);
        
        echo "SUCCESS: Backup created successfully at " . date('Y-m-d H:i:s') . "\n";
        writeBackupLog("Otomatik yedekleme başarıyla tamamlandı");
        
    } else {
        writeBackupLog("HATA: Yedekleme oluşturulamadı");
        echo "ERROR: Failed to create backup\n";
        exit(1);
    }
    
} catch (Exception $e) {
    $error_message = "Backup cron job hatası: " . $e->getMessage();
    writeBackupLog($error_message);
    echo "ERROR: " . $error_message . "\n";
    exit(1);
}

/**
 * Otomatik yedekleme oluşturur
 */
function createAutomaticBackup() {
    try {
        $database = new Database();
        $pdo = $database->pdo;
        
        $date = date('Y-m-d_H-i-s');
        $backup_file = APP_ROOT . '/database/backups/auto_backup_' . $date . '.sql';
        
        // Tüm tabloları al
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $return = "-- Otomatik Yedekleme\n";
        $return .= "-- Oluşturulma Tarihi: " . date('Y-m-d H:i:s') . "\n";
        $return .= "-- Yedeklenen Tablo Sayısı: " . count($tables) . "\n\n";
        
        // Her tablo için yapıyı ve verileri yedekle
        foreach ($tables as $table) {
            $return .= "\n-- Tablo: $table\n";
            
            // Tablo yapısını al
            $result = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch(PDO::FETCH_NUM);
            $return .= "DROP TABLE IF EXISTS `$table`;\n";
            $return .= $row[1] . ";\n\n";
            
            // Tablo verilerini al
            $result = $pdo->query("SELECT * FROM `$table`");
            $num_fields = $result->columnCount();
            
            if ($num_fields > 0) {
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
            }
            $return .= "\n";
        }
        
        // Yedeği dosyaya kaydet
        if (file_put_contents($backup_file, $return)) {
            writeBackupLog("Yedek dosyası oluşturuldu: " . basename($backup_file) . " (" . formatBytes(filesize($backup_file)) . ")");
            return true;
        } else {
            writeBackupLog("HATA: Yedek dosyası yazılamadı: $backup_file");
            return false;
        }
        
    } catch (Exception $e) {
        writeBackupLog("HATA: Yedekleme sırasında hata: " . $e->getMessage());
        return false;
    }
}

/**
 * Eski yedekleri temizler
 */
function cleanOldBackups($retention_days) {
    try {
        $backup_dir = APP_ROOT . '/database/backups';
        $cutoff_time = time() - ($retention_days * 24 * 60 * 60);
        
        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . '/auto_backup_*.sql');
            $deleted_count = 0;
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    if (unlink($file)) {
                        $deleted_count++;
                        writeBackupLog("Eski yedek silindi: " . basename($file));
                    }
                }
            }
            
            if ($deleted_count > 0) {
                writeBackupLog("$deleted_count adet eski yedek temizlendi");
            } else {
                writeBackupLog("Silinecek eski yedek bulunamadı");
            }
        }
        
    } catch (Exception $e) {
        writeBackupLog("HATA: Eski yedekler temizlenirken hata: " . $e->getMessage());
    }
}

/**
 * Dosya boyutunu formatlar
 */
function formatBytes($bytes) {
    if ($bytes > 0) {
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return sprintf('%.2f', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }
    return '0 B';
}
?>
