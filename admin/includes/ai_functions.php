<?php
/**
 * AI Hizmetleri - API Fonksiyonları
 * Bu dosya, çeşitli AI API'leri ile entegrasyon sağlayan fonksiyonları içerir
 */

// AI API konfigürasyonlarını yükle
require_once __DIR__ . '/../../config/api_keys.php';

/**
 * Google Gemini API'yi kullanarak içerik üretir
 * 
 * @param string $prompt API'ye gönderilecek prompt metni
 * @param array $options Ek seçenekler
 * @return array|false Başarılı olursa yanıt array, değilse false
 */
function generateContentWithGemini($prompt, $options = []) {
    global $AI_CONFIG;
    
    $apiKey = $AI_CONFIG['gemini']['api_key'];
    
    if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY') {
        return ['error' => 'Gemini API anahtarı yapılandırılmamış'];
    }
    
    // API anahtarına göre model tipini algıla (eğer fonksiyon mevcutsa)
    if (function_exists('detectGeminiModel')) {
        $detected_model = detectGeminiModel($apiKey);
        $apiUrl = $detected_model['api_url'];
        
        // Log dosyasına algılanan model bilgisini kaydet
        $log_message = date('Y-m-d H:i:s') . " - Algılanan Gemini modeli: " . $detected_model['model'] . PHP_EOL;
        @file_put_contents(__DIR__ . '/../../logs/gemini_model_' . date('Y-m-d') . '.log', $log_message, FILE_APPEND);
    } else {
        $apiUrl = $AI_CONFIG['gemini']['api_url'];
    }
    
    // Gemini API'si için parametreleri yapılandır
    $temperature = $options['temperature'] ?? 0.7;
    $maxTokens = $options['max_tokens'] ?? 4096;
    
    $requestData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => $temperature,
            'maxOutputTokens' => $maxTokens,
            'topP' => $options['top_p'] ?? 0.8,
            'topK' => $options['top_k'] ?? 40
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_NONE'
            ]
        ]
    ];
    
    // API'ye istek gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $processingTime = microtime(true) - $startTime;
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !empty($error)) {
        return [
            'error' => 'API isteği başarısız: ' . $error,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Geçersiz API yanıtı: ' . json_last_error_msg()];
    }
    
    // Gemini yanıt formatını standart formata dönüştür
    $content = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    return [
        'content' => $content,
        'processing_time' => $processingTime,
        'raw_response' => $responseData
    ];
}

/**
 * OpenAI GPT API'yi kullanarak içerik üretir
 * 
 * @param string $prompt API'ye gönderilecek prompt metni
 * @param array $options Ek seçenekler
 * @return array|false Başarılı olursa yanıt array, değilse false
 */
function generateContentWithChatGPT($prompt, $options = []) {
    global $AI_CONFIG;
    
    $apiKey = $AI_CONFIG['chatgpt']['api_key'];
    
    if (empty($apiKey) || $apiKey === 'YOUR_OPENAI_API_KEY') {
        return ['error' => 'OpenAI API anahtarı yapılandırılmamış'];
    }
    
    // API anahtarına göre model tipini algıla (eğer fonksiyon mevcutsa)
    if (function_exists('detectOpenAIModel')) {
        $detected_model = detectOpenAIModel($apiKey);
        $apiUrl = $detected_model['api_url'];
        $model = $detected_model['model'];
        
        // Log dosyasına algılanan model bilgisini kaydet
        $log_message = date('Y-m-d H:i:s') . " - Algılanan OpenAI modeli: " . $detected_model['model'] . PHP_EOL;
        @file_put_contents(__DIR__ . '/../../logs/openai_model_' . date('Y-m-d') . '.log', $log_message, FILE_APPEND);
    } else {
        $apiUrl = $AI_CONFIG['chatgpt']['api_url'];
        $model = $AI_CONFIG['chatgpt']['model'];
    }
    
    // ChatGPT API'si için parametreleri yapılandır
    $temperature = $options['temperature'] ?? 0.7;
    $maxTokens = $options['max_tokens'] ?? 4000;
    
    $requestData = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Sen uzman bir içerik yazarısın. SEO dostu, bilgilendirici ve ilgi çekici makaleler yazarsın.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'top_p' => $options['top_p'] ?? 1,
        'frequency_penalty' => $options['frequency_penalty'] ?? 0,
        'presence_penalty' => $options['presence_penalty'] ?? 0
    ];
    
    // API'ye istek gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $processingTime = microtime(true) - $startTime;
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !empty($error)) {
        return [
            'error' => 'API isteği başarısız: ' . $error,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Geçersiz API yanıtı: ' . json_last_error_msg()];
    }
    
    // ChatGPT yanıt formatını standart formata dönüştür
    $content = $responseData['choices'][0]['message']['content'] ?? '';
    
    return [
        'content' => $content,
        'processing_time' => $processingTime,
        'raw_response' => $responseData
    ];
}

/**
 * xAI Grok API'yi kullanarak içerik üretir
 * 
 * @param string $prompt API'ye gönderilecek prompt metni
 * @param array $options Ek seçenekler
 * @return array|false Başarılı olursa yanıt array, değilse false
 */
function generateContentWithGrok($prompt, $options = []) {
    global $AI_CONFIG;
    
    $apiKey = $AI_CONFIG['grok']['api_key'];
    
    if (empty($apiKey) || $apiKey === 'YOUR_GROK_API_KEY') {
        return ['error' => 'Grok API anahtarı yapılandırılmamış'];
    }
    
    // API anahtarına göre model tipini algıla (eğer fonksiyon mevcutsa)
    if (function_exists('detectGrokModel')) {
        $detected_model = detectGrokModel($apiKey);
        $apiUrl = $detected_model['api_url'];
        $model = $detected_model['model'];
        
        // Log dosyasına algılanan model bilgisini kaydet
        $log_message = date('Y-m-d H:i:s') . " - Algılanan Grok modeli: " . $detected_model['model'] . PHP_EOL;
        @file_put_contents(__DIR__ . '/../../logs/grok_model_' . date('Y-m-d') . '.log', $log_message, FILE_APPEND);
    } else {
        $apiUrl = $AI_CONFIG['grok']['api_url'];
        $model = $AI_CONFIG['grok']['model'];
    }
    
    // Grok API'si için parametreleri yapılandır
    $temperature = $options['temperature'] ?? 0.7;
    $maxTokens = $options['max_tokens'] ?? 4000;
    
    $requestData = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Sen uzman bir içerik yazarısın. SEO dostu, bilgilendirici ve ilgi çekici makaleler yazarsın.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'top_p' => $options['top_p'] ?? 1,
        'frequency_penalty' => $options['frequency_penalty'] ?? 0,
        'presence_penalty' => $options['presence_penalty'] ?? 0
    ];
    
    // API'ye istek gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $processingTime = microtime(true) - $startTime;
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !empty($error)) {
        return [
            'error' => 'API isteği başarısız: ' . $error,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Geçersiz API yanıtı: ' . json_last_error_msg()];
    }
    
    // Grok yanıt formatını standart formata dönüştür (OpenAI formatına benzer)
    $content = $responseData['choices'][0]['message']['content'] ?? '';
    
    return [
        'content' => $content,
        'processing_time' => $processingTime,
        'raw_response' => $responseData
    ];
}

/**
 * Belirtilen AI modelini kullanarak içerik üretir
 * 
 * @param string $aiType AI türü ('gemini', 'chatgpt', 'grok')
 * @param string $prompt API'ye gönderilecek prompt metni
 * @param array $options Ek seçenekler
 * @return array|false Başarılı olursa yanıt array, değilse false
 */
function generateContentWithAI($aiType, $prompt, $options = []) {
    switch ($aiType) {
        case 'gemini':
            return generateContentWithGemini($prompt, $options);
        case 'chatgpt':
            return generateContentWithChatGPT($prompt, $options);
        case 'grok':
            return generateContentWithGrok($prompt, $options);
        default:
            return ['error' => 'Desteklenmeyen AI türü: ' . $aiType];
    }
}

/**
 * Unsplash API'sini kullanarak görsel arar
 * Başlığa göre Türkçe içerikli görsellere öncelik verir
 * 
 * @param string $query Ana arama sorgusu (başlık)
 * @param int $count Dönecek resim sayısı
 * @param array $extraKeywords Ek anahtar kelimeler
 * @return array|false Başarılı olursa resimler listesi, değilse false
 */
function searchUnsplashImages($query, $count = 3, $extraKeywords = []) {
    global $UNSPLASH_ACCESS_KEY;
    
    if (empty($UNSPLASH_ACCESS_KEY) || $UNSPLASH_ACCESS_KEY === 'YOUR_UNSPLASH_ACCESS_KEY') {
        return ['error' => 'Unsplash API anahtarı yapılandırılmamış'];
    }
    
    // Türkçe anahtar kelimeler ve "türkçe" kelimesini ekle
    $turkishQuery = $query . " türkçe";
    
    // Eğer ekstra anahtar kelimeler varsa, bunları sorguya ekle
    if (!empty($extraKeywords) && is_array($extraKeywords)) {
        $turkishQuery .= " " . implode(" ", $extraKeywords);
    }
    
    $apiUrl = 'https://api.unsplash.com/search/photos';
    $params = [
        'query' => $turkishQuery,
        'per_page' => $count * 2, // Daha fazla sonuç al, sonra filtreleme yap
        'orientation' => 'landscape',
        'content_filter' => 'high'
    ];
    
    $url = $apiUrl . '?' . http_build_query($params);
    
    // API'ye istek gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Client-ID ' . $UNSPLASH_ACCESS_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !empty($error)) {
        return [
            'error' => 'API isteği başarısız: ' . $error,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Geçersiz API yanıtı: ' . json_last_error_msg()];
    }
    
    $images = [];
    $turkishImages = [];
    $otherImages = [];
    
    foreach ($responseData['results'] as $image) {
        $imageData = [
            'id' => $image['id'],
            'url' => $image['urls']['regular'],
            'thumb' => $image['urls']['thumb'],
            'description' => $image['description'] ?? $image['alt_description'] ?? '',
            'user' => $image['user']['name'],
            'user_url' => $image['user']['links']['html'],
            'download_url' => $image['links']['download'],
            'relevance_score' => 0 // Başlangıçta ilişki skoru 0
        ];
        
        // İlişki skorunu hesapla
        $description = strtolower($imageData['description'] ?? '');
        $title = $image['title'] ?? '';
        $tags = [];
        
        // Eğer tags varsa, bunları al
        if (isset($image['tags']) && is_array($image['tags'])) {
            foreach ($image['tags'] as $tag) {
                if (isset($tag['title'])) {
                    $tags[] = strtolower($tag['title']);
                }
            }
        }
        
        // Başlıkla eşleşen anahtar kelimeler varsa skoru artır
        $queryWords = explode(' ', strtolower($query));
        foreach ($queryWords as $word) {
            if (strlen($word) < 3) continue; // Çok kısa kelimeleri atla
            
            if (strpos($description, $word) !== false) {
                $imageData['relevance_score'] += 3;
            }
            
            if (strpos($title, $word) !== false) {
                $imageData['relevance_score'] += 2;
            }
            
            foreach ($tags as $tag) {
                if (strpos($tag, $word) !== false) {
                    $imageData['relevance_score'] += 1;
                }
            }
        }
        
        // Türkçe içerik kontrolü
        $hasTurkishContent = false;
        $turkishChars = ['ç', 'ğ', 'ı', 'İ', 'ö', 'ş', 'ü'];
        
        foreach ($turkishChars as $char) {
            if (strpos(strtolower($description . ' ' . $title), $char) !== false) {
                $hasTurkishContent = true;
                $imageData['relevance_score'] += 5; // Türkçe içerik varsa ekstra puan
                break;
            }
        }
        
        // Türkçe içeriği önceliklendir
        if ($hasTurkishContent) {
            $turkishImages[] = $imageData;
        } else {
            $otherImages[] = $imageData;
        }
    }
    
    // İlişki skorlarına göre sırala
    usort($turkishImages, function($a, $b) {
        return $b['relevance_score'] - $a['relevance_score'];
    });
    
    usort($otherImages, function($a, $b) {
        return $b['relevance_score'] - $a['relevance_score'];
    });
    
    // Önce Türkçe içerikli görseller, sonra diğerleri
    $images = array_merge($turkishImages, $otherImages);
    
    // İstenilen sayıda görsel döndür
    $images = array_slice($images, 0, $count);
    
    return [
        'images' => $images,
        'total' => count($images),
        'total_pages' => 1,
        'turkish_count' => count($turkishImages)
    ];
}

/**
 * HTML içeriğinden düz metni çıkarır
 * 
 * @param string $html HTML içeriği
 * @return string Düz metin
 */
function getPlainTextFromHtml($html) {
    // HTML etiketlerini temizle
    $text = strip_tags($html);
    
    // Kodlanmış HTML karakterlerini çöz
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Fazla boşlukları temizle
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    return $text;
}

/**
 * Metindeki kelime sayısını hesaplar
 * 
 * @param string $text Metin
 * @return int Kelime sayısı
 */
function countWords($text) {
    $text = getPlainTextFromHtml($text);
    return str_word_count($text, 0, 'àáãâéêíóôõúüçÀÁÃÂÉÊÍÓÔÕÚÜÇ');
}

/**
 * İçerikten anahtar kelimeleri çıkarır
 * 
 * @param string $content İçerik
 * @param string $category Kategori
 * @param int $maxKeywords Maksimum anahtar kelime sayısı
 * @return array Anahtar kelimeler
 */
function extractKeywords($content, $category, $maxKeywords = 5) {
    // Türkçe stopwords (yaygın kullanılan, ayırt edici olmayan kelimeler)
    $stopwords = array('ve', 'veya', 'ile', 'için', 'bu', 'şu', 'bir', 'olarak', 'ama', 'fakat', 'ancak', 
                       'gibi', 'kadar', 'daha', 'çok', 'da', 'de', 'ki', 'ne', 'ya', 'mi', 'mu', 'mı', 'den', 'dan',
                       'ten', 'tan', 'ise', 'idi', 'imiş', 'olan', 'oldu', 'olur', 'olmuş', 'olacak', 'olmalı');
    
    // Metni küçük harfe çevir ve noktalama işaretlerini temizle
    $content = mb_strtolower($content, 'UTF-8');
    $content = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $content);
    
    // Kelimeleri böl
    $words = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    
    // Kelimelerin frekansını say
    $wordFrequency = array_count_values($words);
    
    // Stopwords'leri ve çok kısa kelimeleri çıkar
    foreach ($wordFrequency as $word => $count) {
        if (in_array($word, $stopwords) || mb_strlen($word, 'UTF-8') < 3) {
            unset($wordFrequency[$word]);
        }
    }
    
    // Frekansa göre sırala
    arsort($wordFrequency);
    
    // Kategoriyi ekle (en önemli anahtar kelime)
    $keywords = [$category];
    
    // En sık kullanılan kelimeleri al (kategoriden sonra)
    $counter = 0;
    foreach ($wordFrequency as $word => $count) {
        if ($counter >= $maxKeywords - 1) break; // Kategori zaten eklendi, bir anahtar kelime daha az al
        if (!in_array($word, $keywords)) { // Tekrarları önle
            $keywords[] = $word;
            $counter++;
        }
    }
    
    return $keywords;
}

/**
 * HTML içeriğini ayrıştırarak başlığı ve içeriği ayırır
 * 
 * @param string $content AI'den gelen ham içerik
 * @return array Ayrıştırılmış başlık ve içerik
 */
function parseAIContent($content) {
    // Başlığı içerikten ayır
    // İlk başlık etiketi veya # ile başlayan satırı ara
    if (preg_match('/<h1.*?>(.*?)<\/h1>/is', $content, $matches)) {
        $title = strip_tags($matches[1]);
        $content = preg_replace('/<h1.*?>(.*?)<\/h1>/is', '', $content, 1);
    } elseif (preg_match('/^\s*#\s+(.*?)$/m', $content, $matches)) {
        $title = trim($matches[1]);
        $content = preg_replace('/^\s*#\s+(.*?)$/m', '', $content, 1);
    } elseif (preg_match('/^\s*(.*?)\n={3,}$/m', $content, $matches)) {
        // = işaretleriyle altı çizilen başlık formatı
        $title = trim($matches[1]);
        $content = preg_replace('/^\s*(.*?)\n={3,}$/m', '', $content, 1);
    } else {
        // Eğer başlık bulunamazsa, ilk satırı başlık olarak al
        $lines = explode("\n", $content, 2);
        $title = trim($lines[0]);
        $content = count($lines) > 1 ? trim($lines[1]) : '';
    }
    
    // Başlık ve içeriği temizle
    $title = trim(strip_tags($title));
    $content = trim($content);
    
    // Markdown formatını HTML'e dönüştür (eğer gerekiyorsa)
    // Bu basit bir örnek, gerçek uygulamada bir Markdown kütüphanesi kullanmak daha iyi olur
    if (strpos($content, '##') !== false || strpos($content, '*') !== false) {
        // Basit Markdown dönüşümü, daha gelişmiş bir kütüphane kullanılabilir
        $content = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $content);
    }
    
    return [
        'title' => $title,
        'content' => $content
    ];
}
?>
