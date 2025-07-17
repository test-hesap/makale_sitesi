<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Sitemap Ayarları Kurulumu</h1>";

try {
    $database = new Database();
    $db = $database->pdo;
    
    // Sitemap ayarlarını ekle
    $settings = [
        'sitemap_enabled' => '1',
        'sitemap_auto_generate' => '1',
        'sitemap_include_images' => '0',
        'sitemap_include_users' => '0',
        'sitemap_priority_homepage' => '1.0',
        'sitemap_priority_articles' => '0.8',
        'sitemap_priority_categories' => '0.7',
        'sitemap_priority_static' => '0.5',
        'sitemap_changefreq_homepage' => 'daily',
        'sitemap_changefreq_articles' => 'monthly',
        'sitemap_changefreq_categories' => 'weekly',
        'sitemap_changefreq_static' => 'yearly'
    ];
    
    foreach ($settings as $key => $value) {
        // Önce kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            // Güncelle
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
            echo "<p>✓ $key güncellendi: $value</p>";
        } else {
            // Yeni ekle
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
            echo "<p>✓ $key eklendi: $value</p>";
        }
    }
    
    echo "<h2>Sitemap Oluştur:</h2>";
    if (generateSitemapXML()) {
        echo "<p style='color: green;'>✓ Sitemap başarıyla oluşturuldu!</p>";
    } else {
        echo "<p style='color: red;'>✗ Sitemap oluşturulamadı!</p>";
    }
    
    echo "<h2>Test Linkler:</h2>";
    echo "<p><a href='/sitemap.xml' target='_blank'>Sitemap XML</a></p>";
    echo "<p><a href='/admin?page=sitemap'>Admin Sitemap Yönetimi</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
h1, h2 {
    color: #333;
}
</style>
