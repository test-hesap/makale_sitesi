<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Makale Gösterim Ayarları Ekleniyor...</h2>";

try {
    // Ayarları ekle
    updateSetting('featured_articles_per_page', '6');
    echo "✓ featured_articles_per_page eklendi<br>";
    
    updateSetting('featured_articles_pagination_type', 'pagination');
    echo "✓ featured_articles_pagination_type eklendi<br>";
    
    updateSetting('recent_articles_per_page', '6');
    echo "✓ recent_articles_per_page eklendi<br>";
    
    updateSetting('recent_articles_pagination_type', 'pagination');
    echo "✓ recent_articles_pagination_type eklendi<br>";
    
    updateSetting('popular_articles_per_page', '6');
    echo "✓ popular_articles_per_page eklendi<br>";
    
    updateSetting('popular_articles_pagination_type', 'pagination');
    echo "✓ popular_articles_pagination_type eklendi<br>";
    
    echo "<br><strong>Tüm ayarlar başarıyla eklendi!</strong><br>";
    echo '<a href="/admin/?page=article-display">Admin Paneline Git</a>';
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
