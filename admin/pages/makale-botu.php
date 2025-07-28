<?php
/**
 * Makale Botu - Admin Panel
 * Bu sayfa, AI kullanarak otomatik makale üretmeyi sağlar
 */

// Debug: Current page kontrolü
// echo "<!-- Debug: Current page is: " . ($current_page ?? 'undefined') . " -->";

// Çıktı tamponlamasını başlat
ob_start();

// Güvenlik kontrolü
if (!defined('BASE_PATH')) {
    exit('Doğrudan erişim engellendi');
}

// AI fonksiyonlarını dahil et
require_once 'includes/ai_functions.php';

// Form gönderilmiş mi kontrol et
$message = '';
$messageType = '';
$generatedContent = null;
$generatedTitle = '';
$generatedImages = [];
$formData = [];

// Kategorileri veritabanından çek
$categories = [];
try {
    $categoryQuery = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Kategoriler yüklenirken bir hata oluştu: ' . $e->getMessage();
    $messageType = 'error';
}

// Form işleme - İçerik Üretme Adımı
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_content'])) {
    // CSRF kontrolü
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
        $messageType = 'error';
    } else {
        // Form verilerini al ve temizle
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $aiType = $_POST['ai_type'] ?? 'gemini';
        $wordCount = (int)($_POST['word_count'] ?? 500);
        $formData = [
            'category_id' => $categoryId,
            'ai_type' => $aiType,
            'word_count' => $wordCount
        ];
        
        // Gerekli alanları kontrol et
        if ($categoryId <= 0) {
            $message = 'Lütfen bir kategori seçin.';
            $messageType = 'error';
        } elseif (!in_array($aiType, ['gemini', 'chatgpt', 'grok'])) {
            $message = 'Geçersiz AI türü seçildi.';
            $messageType = 'error';
        } elseif ($wordCount < 100 || $wordCount > 5000) {
            $message = 'Kelime sayısı 100-5000 arasında olmalıdır.';
            $messageType = 'error';
        } else {
            // Kategori adını al
            $categoryName = '';
            foreach ($categories as $category) {
                if ($category['id'] == $categoryId) {
                    $categoryName = $category['name'];
                    break;
                }
            }
            
            if (empty($categoryName)) {
                $message = 'Seçilen kategori bulunamadı.';
                $messageType = 'error';
            } else {
                // AI prompt'unu hazırla
                // Benzersiz başlık ve içerik oluşturmak için rastgele varyasyon faktörleri ekle
                $currentTime = time();
                $randomSeed = rand(1000, 9999);
                $uniqueId = substr(md5($currentTime . $randomSeed . $categoryName), 0, 8);
                
                $prompt = "Kategori: \"$categoryName\" için tamamen özgün ve benzersiz bir makale yaz. ";
                $prompt .= "Makale başlığı kesinlikle daha önce hiç kullanılmamış, çarpıcı ve dikkat çekici olmalı. ";
                $prompt .= "Başlık oluştururken şu teknikleri kullan: sorular sor, rakamlar kullan, güçlü sıfatlar ekle, ";
                $prompt .= "merak uyandır ve duygusal tepki yaratacak ifadeler kullan. Başlıklarda klişelerden kaçın. ";
                $prompt .= "Yaklaşık {$wordCount} kelime uzunluğunda olsun. ";
                $prompt .= "SEO uyumlu, bilgilendirici ve ilgi çekici benzersiz bir başlık oluştur. ";
                $prompt .= "Başlıkta beklenmedik kelime kombinasyonları, orijinal fikirler ve çarpıcı ifadeler kullan. ";
                $prompt .= "Farklı başlık yapıları dene: 'Nasıl...', 'X Adımda...', '... Hakkında Bilmeniz Gereken X Şey', '...nın Sırları' gibi kalıplar yerine daha yaratıcı ifadeler kullan. ";
                $prompt .= "Türkçe dil bilgisi kurallarına uygun, akıcı ve anlaşılır bir dil kullan. ";
                $prompt .= "İçeriği HTML formatında döndür, başlık için <h1> etiketi kullan, alt başlıklar için <h2> ve <h3> kullan. ";
                $prompt .= "Makalenin sonunda okuyucuyu harekete geçiren bir çağrı ifadesi ekle. ";
                $prompt .= "Önemli: Her üretim tamamen benzersiz olmalıdır. ";
                $prompt .= "Benzersiz Kimlik: $uniqueId, Rastgele Faktör: $randomSeed, Tarih: $currentTime.";
                
                // AI isteğini gönder (maksimum yaratıcılık için parametreleri optimize et)
                $aiResponse = generateContentWithAI($aiType, $prompt, [
                    'temperature' => 1.0, // Maksimum yaratıcılık için temperature değerini en yüksek seviyeye çıkar
                    'max_tokens' => $wordCount * 6, // Daha fazla içerik için token sayısını artır
                    'top_p' => 0.98, // Kelime seçiminde çeşitliliği artır
                    'top_k' => 60, // Daha geniş bir kelime havuzu kullan
                    'presence_penalty' => 0.9, // Tekrarları büyük ölçüde azaltmak için presence penalty'yi artır
                    'frequency_penalty' => 0.9 // Kelime tekrarlarını büyük ölçüde azaltmak için frequency penalty'yi artır
                ]);
                
                if (isset($aiResponse['error'])) {
                    $message = 'İçerik üretilirken bir hata oluştu: ' . $aiResponse['error'];
                    $messageType = 'error';
                } else {
                    // Üretilen içeriği ayrıştır
                    $parsedContent = parseAIContent($aiResponse['content']);
                    $generatedTitle = $parsedContent['title'];
                    $generatedContent = $parsedContent['content'];
                    
                    // İçerikten anahtar kelimeleri çıkar
                    $plainContent = getPlainTextFromHtml($generatedContent);
                    $keywords = extractKeywords($plainContent, $categoryName);
                    
                    // Başlık ve anahtar kelimelerle resimleri getir
                    $imageResponse = searchUnsplashImages($generatedTitle, 3, $keywords);
                    
                    if (isset($imageResponse['error'])) {
                        $message = 'İçerik üretildi ancak resimler getirilirken bir hata oluştu: ' . $imageResponse['error'];
                        $messageType = 'warning';
                    } elseif (empty($imageResponse['images'])) {
                        $message = 'İçerik üretildi ancak uygun resim bulunamadı.';
                        $messageType = 'warning';
                    } else {
                        $generatedImages = $imageResponse['images'];
                        $message = 'İçerik ve resimler başarıyla üretildi. Kaydetmek için aşağıdaki butona tıklayın.';
                        $messageType = 'success';
                    }
                }
            }
        }
    }
}

// Form işleme - Makale Kaydetme Adımı
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_article'])) {
    // CSRF kontrolü
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
        $messageType = 'error';
    } else {
        // Form verilerini al ve temizle
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $aiType = $_POST['ai_type'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $coverImage = $_POST['cover_image'] ?? '';
        $image1 = $_POST['image1'] ?? '';
        $image2 = $_POST['image2'] ?? '';
        $wordCount = (int)($_POST['word_count'] ?? 0);
        $processingTime = (float)($_POST['processing_time'] ?? 0);
        
        // Makale içeriğinden başlıkları çıkar
        $headings = [];
        preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/is', $content, $matches);
        if (!empty($matches[1])) {
            $headings = $matches[1];
        }
        
        // Resimleri içeriğe akıllı yerleştirme
        if (!empty($coverImage)) {
            $coverImageAlt = htmlspecialchars($title . ' - ' . $title);
            $coverImageTitle = htmlspecialchars($title);
            $coverImageHtml = "<figure class=\"image featured-image\"><img src=\"$coverImage\" alt=\"$coverImageAlt\" title=\"$coverImageTitle\"><figcaption>$title</figcaption></figure>";
            
            // İçeriği kapak resmi ile başlat
            $content = $coverImageHtml . "\n\n" . $content;
        }
        
        if (!empty($image1) || !empty($image2)) {
            // İçeriği paragraflarına böl
            $contentParts = explode('</p>', $content);
            
            // İlk resmi uygun bir alt başlık sonrasına yerleştir
            if (!empty($image1) && count($contentParts) > 3) {
                // Başlıklara göre resim açıklaması oluştur
                $image1Caption = !empty($headings[0]) ? strip_tags($headings[0]) : $title;
                $image1Alt = htmlspecialchars($image1Caption);
                $image1Title = htmlspecialchars($image1Caption);
                $image1Html = "<figure class=\"image content-image\"><img src=\"$image1\" alt=\"$image1Alt\" title=\"$image1Title\"><figcaption>$image1Caption</figcaption></figure>";
                
                // İlk başlıktan sonraki paragrafı bul
                $headingPos = -1;
                foreach ($contentParts as $index => $part) {
                    if (strpos($part, '<h2') !== false || strpos($part, '<h3') !== false) {
                        $headingPos = $index;
                        break;
                    }
                }
                
                // Eğer başlık bulunamazsa, içeriğin 1/3'üne ekle
                $firstPartCount = ($headingPos > 0) ? $headingPos + 1 : ceil(count($contentParts) / 3);
                if ($firstPartCount >= count($contentParts)) {
                    $firstPartCount = min(3, count($contentParts) - 1);
                }
                
                // İlk resmi ekle
                $contentParts[$firstPartCount] .= "\n\n" . $image1Html;
            }
            
            // İkinci resmi başka bir alt başlık sonrasına yerleştir
            if (!empty($image2) && count($contentParts) > 6) {
                // Başlıklara göre resim açıklaması oluştur
                $image2Caption = !empty($headings[1]) ? strip_tags($headings[1]) : 
                              (!empty($headings[0]) ? strip_tags($headings[0]) : $title);
                $image2Alt = htmlspecialchars($image2Caption);
                $image2Title = htmlspecialchars($image2Caption);
                $image2Html = "<figure class=\"image content-image\"><img src=\"$image2\" alt=\"$image2Alt\" title=\"$image2Title\"><figcaption>$image2Caption</figcaption></figure>";
                
                // İkinci başlıktan sonraki paragrafı bul
                $headingCount = 0;
                $secondHeadingPos = -1;
                foreach ($contentParts as $index => $part) {
                    if (strpos($part, '<h2') !== false || strpos($part, '<h3') !== false) {
                        $headingCount++;
                        if ($headingCount == 2) {
                            $secondHeadingPos = $index;
                            break;
                        }
                    }
                }
                
                // Eğer ikinci başlık bulunamazsa, içeriğin 2/3'üne ekle
                $secondPartCount = ($secondHeadingPos > 0) ? $secondHeadingPos + 1 : ceil(count($contentParts) * 2 / 3);
                if ($secondPartCount >= count($contentParts)) {
                    $secondPartCount = min(ceil(count($contentParts) * 2/3), count($contentParts) - 1);
                }
                
                // İkinci resmi ekle
                $contentParts[$secondPartCount] .= "\n\n" . $image2Html;
            }
            
            $content = implode('</p>', $contentParts);
        }
        
        // SEO için meta açıklaması oluştur
        $excerpt = getPlainTextFromHtml($content);
        $excerpt = substr($excerpt, 0, 320);
        if (strlen($excerpt) == 320) {
            $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')) . '...';
        }
        
        // Slug oluştur
        $slug = generateSlug($title);
        
        try {
            // İşlemi başlat
            $db->beginTransaction();
            
            // Makaleyi kaydet
            $articleSql = "INSERT INTO articles (title, slug, content, excerpt, category_id, user_id, status, meta_title, meta_description, featured_image, created_at, updated_at) 
                            VALUES (:title, :slug, :content, :excerpt, :category_id, :user_id, :status, :meta_title, :meta_description, :featured_image, NOW(), NOW())";
                            
            $articleParams = [
                ':title' => $title,
                ':slug' => $slug,
                ':content' => $content,
                ':excerpt' => $excerpt,
                ':category_id' => $categoryId,
                ':user_id' => $_SESSION['user_id'],
                ':status' => 'published',
                ':meta_title' => $title,
                ':meta_description' => $excerpt,
                ':featured_image' => $coverImage
            ];
            
            $articleStmt = $db->prepare($articleSql);
            $articleStmt->execute($articleParams);
            
            $articleId = $db->lastInsertId();
            
            // AI makale bilgilerini kaydet
            $aiArticleSql = "INSERT INTO ai_articles (article_id, ai_type, prompt, word_count, processing_time, cover_image_url, image1_url, image2_url, created_at) 
                            VALUES (:article_id, :ai_type, :prompt, :word_count, :processing_time, :cover_image_url, :image1_url, :image2_url, NOW())";
                            
            $aiArticleParams = [
                ':article_id' => $articleId,
                ':ai_type' => $aiType,
                ':prompt' => $_POST['prompt'] ?? '',
                ':word_count' => $wordCount,
                ':processing_time' => $processingTime,
                ':cover_image_url' => $coverImage,
                ':image1_url' => $image1,
                ':image2_url' => $image2
            ];
            
            $aiArticleStmt = $db->prepare($aiArticleSql);
            $aiArticleStmt->execute($aiArticleParams);
            
            // İşlemi tamamla
            $db->commit();
            
            $message = 'Makale başarıyla kaydedildi.';
            $messageType = 'success';
            
            // Makaleler listesi sayfasına yönlendir
            header("Location: ?page=articles&success=1&message=".urlencode('Makale başarıyla oluşturuldu'));
            exit;
            
        } catch (Exception $e) {
            // Hata durumunda işlemi geri al
            $db->rollBack();
            
            $message = 'Makale kaydedilirken bir hata oluştu: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">Makale Botu</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-300">
            AI teknolojilerini kullanarak otomatik makale oluşturun. Kategori seçin ve AI tipini belirleyerek hızlıca içerik üretin.
        </p>
    </div>

    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?php 
        echo $messageType === 'success' ? 'bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200' : 
             ($messageType === 'warning' ? 'bg-yellow-100 dark:bg-yellow-800 text-yellow-700 dark:text-yellow-200' : 
             'bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200'); ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <?php if ($generatedContent === null): ?>
    <!-- İçerik Üretme Formu -->
    <form method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="category_id" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kategori <span class="text-red-500">*</span>
                </label>
                <select id="category_id" name="category_id" required 
                    class="block w-full p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500">
                    <option value="">Kategori Seçin</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($formData['category_id']) && $formData['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="ai_type" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    AI Tipi <span class="text-red-500">*</span>
                </label>
                <?php
                global $AI_CONFIG; 
                // Gemini modeli bilgisini göster
                $gemini_model_info = '';
                if (isset($AI_CONFIG['gemini']['model'])) {
                    $gemini_model_info = ' (' . $AI_CONFIG['gemini']['model'] . ')';
                }
                ?>
                <select id="ai_type" name="ai_type" required 
                    class="block w-full p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500">
                    <option value="gemini" <?php echo (isset($formData['ai_type']) && $formData['ai_type'] == 'gemini') ? 'selected' : ''; ?>>Google Gemini<?php echo $gemini_model_info; ?></option>
                    <option value="chatgpt" <?php echo (isset($formData['ai_type']) && $formData['ai_type'] == 'chatgpt') ? 'selected' : ''; ?>>OpenAI ChatGPT (<?php echo $AI_CONFIG['chatgpt']['model']; ?>)</option>
                    <option value="grok" <?php echo (isset($formData['ai_type']) && $formData['ai_type'] == 'grok') ? 'selected' : ''; ?>>xAI Grok (<?php echo $AI_CONFIG['grok']['model']; ?>)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Gemini modelinin versiyonu API anahtarınıza göre otomatik algılanır.
                </p>
            </div>
        </div>

        <div>
            <label for="word_count" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                Kelime Sayısı <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center">
                <input type="range" id="word_count_range" min="100" max="5000" step="100" value="<?php echo $formData['word_count'] ?? 500; ?>" 
                    class="w-full h-2 bg-blue-100 dark:bg-blue-900 rounded-lg appearance-none cursor-pointer"
                    oninput="document.getElementById('word_count').value = this.value;">
                <input type="number" id="word_count" name="word_count" min="100" max="5000" step="100" value="<?php echo $formData['word_count'] ?? 500; ?>" 
                    class="ml-4 w-24 p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500"
                    oninput="document.getElementById('word_count_range').value = this.value;">
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">100-5000 arası bir değer girin</p>
        </div>

        <div>
            <button type="submit" name="generate_content" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition duration-300 flex items-center">
                <i class="fas fa-magic mr-2"></i> İçerik Üret
            </button>
        </div>
    </form>
    <?php else: ?>
    <!-- Üretilen İçeriği Göster ve Kaydet -->
    <form method="post" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="category_id" value="<?php echo $formData['category_id']; ?>">
        <input type="hidden" name="ai_type" value="<?php echo $formData['ai_type']; ?>">
        <input type="hidden" name="word_count" value="<?php echo $formData['word_count']; ?>">
        <input type="hidden" name="processing_time" value="<?php echo $aiResponse['processing_time'] ?? 0; ?>">
        <input type="hidden" name="prompt" value="<?php echo htmlspecialchars($prompt ?? ''); ?>">
        
        <!-- Başlık -->
        <div>
            <label for="title" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                Başlık <span class="text-red-500">*</span>
            </label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($generatedTitle); ?>" required
                class="block w-full p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500">
        </div>
        
        <!-- Resimler -->
        <?php if (!empty($generatedImages)): ?>
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                Kapak Görseli
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <?php foreach ($generatedImages as $index => $image): ?>
                <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-2 relative">
                    <img src="<?php echo htmlspecialchars($image['thumb']); ?>" alt="<?php echo htmlspecialchars($image['description'] ?? 'Makale Görseli'); ?>" 
                        class="w-full h-40 object-cover rounded">
                    <div class="mt-2 flex justify-center">
                        <input type="radio" id="cover_image_<?php echo $index; ?>" name="cover_image" value="<?php echo htmlspecialchars($image['url']); ?>" 
                            <?php echo $index === 0 ? 'checked' : ''; ?>
                            class="mr-2">
                        <label for="cover_image_<?php echo $index; ?>" class="text-sm text-gray-700 dark:text-gray-300">
                            Kapak Görseli Olarak Kullan
                        </label>
                    </div>
                    <?php if ($index === 0): ?>
                    <input type="hidden" name="image1" value="<?php echo htmlspecialchars($image['url']); ?>">
                    <?php elseif ($index === 1): ?>
                    <input type="hidden" name="image2" value="<?php echo htmlspecialchars($image['url']); ?>">
                    <?php endif; ?>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Fotoğraf: <a href="<?php echo htmlspecialchars($image['user_url']); ?>" target="_blank" rel="noopener" class="underline"><?php echo htmlspecialchars($image['user']); ?></a> / Unsplash
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- İçerik -->
        <div>
            <label for="content" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                İçerik <span class="text-red-500">*</span>
            </label>
            <textarea id="content" name="content" rows="15" required
                class="block w-full p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($generatedContent); ?></textarea>
        </div>
        
        <div class="flex space-x-4">
            <button type="button" onclick="window.location.href='?page=makale-botu'" 
                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg shadow-sm transition duration-300 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Geri Dön
            </button>
            
            <button type="submit" name="save_article" 
                class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition duration-300 flex items-center">
                <i class="fas fa-save mr-2"></i> Makaleyi Kaydet
            </button>
        </div>
    </form>
    <?php endif; ?>
    
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">API Yapılandırma Durumu</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Google Gemini API Durumu -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center">
                    <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center">
                        <i class="fab fa-google"></i>
                    </span>
                    <h3 class="ml-2 text-lg font-medium text-gray-800 dark:text-white">Google Gemini</h3>
                </div>
                <div class="mt-3">
                    <?php
                    $geminiConfigured = !empty($AI_CONFIG['gemini']['api_key']) && $AI_CONFIG['gemini']['api_key'] !== 'YOUR_GEMINI_API_KEY';
                    $statusColor = $geminiConfigured ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $statusIcon = $geminiConfigured ? 'fas fa-check-circle' : 'fas fa-times-circle';
                    $statusText = $geminiConfigured ? 'Yapılandırıldı' : 'Yapılandırılmadı';
                    ?>
                    <p class="<?php echo $statusColor; ?> flex items-center">
                        <i class="<?php echo $statusIcon; ?> mr-2"></i> <?php echo $statusText; ?>
                    </p>
                    <?php if (!$geminiConfigured): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        API anahtarını <code>config/api_keys.php</code> dosyasında yapılandırın.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- OpenAI ChatGPT API Durumu -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center">
                    <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot"></i>
                    </span>
                    <h3 class="ml-2 text-lg font-medium text-gray-800 dark:text-white">OpenAI ChatGPT</h3>
                </div>
                <div class="mt-3">
                    <?php
                    $chatgptConfigured = !empty($AI_CONFIG['chatgpt']['api_key']) && $AI_CONFIG['chatgpt']['api_key'] !== 'YOUR_OPENAI_API_KEY';
                    $statusColor = $chatgptConfigured ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $statusIcon = $chatgptConfigured ? 'fas fa-check-circle' : 'fas fa-times-circle';
                    $statusText = $chatgptConfigured ? 'Yapılandırıldı' : 'Yapılandırılmadı';
                    ?>
                    <p class="<?php echo $statusColor; ?> flex items-center">
                        <i class="<?php echo $statusIcon; ?> mr-2"></i> <?php echo $statusText; ?>
                    </p>
                    <?php if (!$chatgptConfigured): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        API anahtarını <code>config/api_keys.php</code> dosyasında yapılandırın.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- xAI Grok API Durumu -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center">
                    <span class="w-8 h-8 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-brain"></i>
                    </span>
                    <h3 class="ml-2 text-lg font-medium text-gray-800 dark:text-white">xAI Grok</h3>
                </div>
                <div class="mt-3">
                    <?php
                    $grokConfigured = !empty($AI_CONFIG['grok']['api_key']) && $AI_CONFIG['grok']['api_key'] !== 'YOUR_GROK_API_KEY';
                    $statusColor = $grokConfigured ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $statusIcon = $grokConfigured ? 'fas fa-check-circle' : 'fas fa-times-circle';
                    $statusText = $grokConfigured ? 'Yapılandırıldı' : 'Yapılandırılmadı';
                    ?>
                    <p class="<?php echo $statusColor; ?> flex items-center">
                        <i class="<?php echo $statusIcon; ?> mr-2"></i> <?php echo $statusText; ?>
                    </p>
                    <?php if (!$grokConfigured): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        API anahtarını <code>config/api_keys.php</code> dosyasında yapılandırın.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Unsplash API Durumu -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center">
                    <span class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-camera"></i>
                    </span>
                    <h3 class="ml-2 text-lg font-medium text-gray-800 dark:text-white">Unsplash API</h3>
                </div>
                <div class="mt-3">
                    <?php
                    $unsplashConfigured = !empty($UNSPLASH_ACCESS_KEY) && $UNSPLASH_ACCESS_KEY !== 'YOUR_UNSPLASH_ACCESS_KEY';
                    $statusColor = $unsplashConfigured ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $statusIcon = $unsplashConfigured ? 'fas fa-check-circle' : 'fas fa-times-circle';
                    $statusText = $unsplashConfigured ? 'Yapılandırıldı' : 'Yapılandırılmadı';
                    ?>
                    <p class="<?php echo $statusColor; ?> flex items-center">
                        <i class="<?php echo $statusIcon; ?> mr-2"></i> <?php echo $statusText; ?>
                    </p>
                    <?php if (!$unsplashConfigured): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        API anahtarını <code>config/api_keys.php</code> dosyasında yapılandırın.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-4">
        <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-2">API Anahtarlarını Yapılandırma</h3>
        <p class="mb-2">
            Makale botunu kullanmak için gereken API anahtarlarını <code>config/api_keys.php</code> dosyasında yapılandırmanız gerekmektedir:
        </p>
        <ol class="list-decimal list-inside ml-4 space-y-1">
            <li><a href="https://ai.google.dev/" target="_blank" class="text-blue-500 hover:underline">Google AI Studio</a> üzerinden Gemini API anahtarı alın.</li>
            <li><a href="https://platform.openai.com/" target="_blank" class="text-blue-500 hover:underline">OpenAI</a> üzerinden ChatGPT API anahtarı alın.</li>
            <li><a href="https://api.xai.com/" target="_blank" class="text-blue-500 hover:underline">xAI Developer Platform</a> üzerinden Grok API anahtarı alın.</li>
            <li><a href="https://unsplash.com/developers" target="_blank" class="text-blue-500 hover:underline">Unsplash Developer</a> üzerinden Unsplash API anahtarı alın.</li>
        </ol>
    </div>
</div>

<script>
// TinyMCE için editör
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('content')) {
        tinymce.init({
            selector: '#content',
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    }
});
</script>