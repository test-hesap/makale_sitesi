<?php
/**
 * AI Makaleleri Tablosu Kurulumu
 * Bu betik, AI ile oluşturulan makalelerin izlenmesi için gerekli veritabanı tablosunu oluşturur
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Sadece yönetici erişimi
if (!isLoggedIn() || !isUserAdmin()) {
    echo "<h1>Yetkisiz Erişim</h1>";
    echo "<p>Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>";
    exit;
}

// Veritabanı bağlantısı
try {
    $database = new Database();
    $db = $database->pdo;
    
    echo "<h1>AI Makaleleri Tablosu Kurulumu</h1>";
    
    // Tablo varsa kontrol et
    $tableExists = false;
    $checkQuery = "SHOW TABLES LIKE 'ai_articles'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        $tableExists = true;
        echo "<p>✓ 'ai_articles' tablosu zaten mevcut.</p>";
    } else {
        // Tabloyu oluştur
        $createTableQuery = "CREATE TABLE `ai_articles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `article_id` int(11) NOT NULL,
            `ai_type` enum('gemini', 'chatgpt', 'grok') NOT NULL,
            `prompt` text DEFAULT NULL,
            `word_count` int(11) DEFAULT 0,
            `processing_time` float DEFAULT 0,
            `cover_image_url` varchar(255) DEFAULT NULL,
            `image1_url` varchar(255) DEFAULT NULL,
            `image2_url` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `article_id` (`article_id`),
            CONSTRAINT `ai_articles_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $createStmt = $db->prepare($createTableQuery);
        $createStmt->execute();
        
        echo "<p>✓ 'ai_articles' tablosu başarıyla oluşturuldu.</p>";
    }
    
    // API anahtarlarını içeren konfigürasyon dosyasını kontrol et
    if (!file_exists(__DIR__ . '/config/api_keys.php')) {
        // Dosya yoksa oluştur
        $apiKeysContent = '<?php
// Bu dosya API anahtarlarını saklar
// .gitignore dosyasına eklenmeli ve sunucuya manuel yüklenmeli

// AI API anahtarları
$GEMINI_API_KEY = \'YOUR_GEMINI_API_KEY\';
$OPENAI_API_KEY = \'YOUR_OPENAI_API_KEY\';
$GROK_API_KEY = \'YOUR_GROK_API_KEY\';

// Resim API anahtarları
$UNSPLASH_ACCESS_KEY = \'YOUR_UNSPLASH_ACCESS_KEY\';

/**
 * Gemini API anahtarına göre model tipini otomatik algılayan fonksiyon
 * @param string $api_key Gemini API anahtarı
 * @return array Algılanan model bilgileri [api_url, model]
 */
function detectGeminiModel($api_key) {
    // Varsayılan olarak gemini-1.5-flash kullan
    return [
        \'api_url\' => \'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent\',
        \'model\' => \'gemini-1.5-flash\'
    ];
}

/**
 * OpenAI API anahtarına göre model tipini otomatik algılayan fonksiyon
 * @param string $api_key OpenAI API anahtarı
 * @return array Algılanan model bilgileri [api_url, model]
 */
function detectOpenAIModel($api_key) {
    // Varsayılan olarak gpt-4o kullan
    return [
        \'api_url\' => \'https://api.openai.com/v1/chat/completions\',
        \'model\' => \'gpt-4o\'
    ];
}

/**
 * xAI Grok API anahtarına göre model tipini otomatik algılayan fonksiyon
 * @param string $api_key Grok API anahtarı
 * @return array Algılanan model bilgileri [api_url, model]
 */
function detectGrokModel($api_key) {
    // Varsayılan olarak grok-2 kullan
    return [
        \'api_url\' => \'https://api.xai.com/v1/chat/completions\',
        \'model\' => \'grok-2\'
    ];
}

// API konfigürasyonları
$AI_CONFIG = [
    \'gemini\' => [
        \'api_url\' => \'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent\',
        \'api_key\' => $GEMINI_API_KEY,
        \'model\' => \'gemini-1.5-flash\'
    ],
    \'chatgpt\' => [
        \'api_url\' => \'https://api.openai.com/v1/chat/completions\',
        \'api_key\' => $OPENAI_API_KEY,
        \'model\' => \'gpt-4o\'
    ],
    \'grok\' => [
        \'api_url\' => \'https://api.xai.com/v1/chat/completions\',
        \'api_key\' => $GROK_API_KEY,
        \'model\' => \'grok-2\'
    ]
];
?>';

        file_put_contents(__DIR__ . '/config/api_keys.php', $apiKeysContent);
        echo "<p>✓ API anahtarları için konfigürasyon dosyası oluşturuldu: <code>/config/api_keys.php</code></p>";
    } else {
        echo "<p>✓ API anahtarları konfigürasyon dosyası zaten mevcut.</p>";
    }
    
    echo "<h2>Kurulum Tamamlandı!</h2>";
    echo "<p>Artık Makale Botu özelliğini kullanabilirsiniz.</p>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='/admin/?page=makale-botu' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Makale Botunu Kullan</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h1>Hata!</h1>";
    echo "<p>Veritabanı hatası: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 20px;
}
h1 {
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}
h2 {
    color: #4CAF50;
    margin-top: 30px;
}
p {
    margin: 15px 0;
}
code {
    background-color: #f5f5f5;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
</style>
