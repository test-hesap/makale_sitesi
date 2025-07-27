<?php
// Bu dosya API anahtarlarını saklar
// .gitignore dosyasına eklenmeli ve sunucuya manuel yüklenmeli

// AI API anahtarları
$GEMINI_API_KEY = 'AIzaSyCekWFRMUmQ-44ePXja2cNksDGBV5KP6EQ';
$OPENAI_API_KEY = 'YOUR_OPENAI_API_KEY';
$GROK_API_KEY = 'YOUR_GROK_API_KEY';

// Resim API anahtarları
$UNSPLASH_ACCESS_KEY = 'bg8tptU9d5Nc29TRysp3hWJFYLU2vk5kSeEnqgVyuIQ';

/**
 * Gemini API anahtarına göre model tipini otomatik algılayan fonksiyon
 * @param string $api_key Gemini API anahtarı
 * @return array Algılanan model bilgileri [api_url, model]
 */
function detectGeminiModel($api_key) {
    // Eğer API anahtarı geçersizse veya boşsa varsayılan olarak gemini-1.5-flash kullan
    if (empty($api_key) || $api_key === 'YOUR_GEMINI_API_KEY') {
        return [
            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent',
            'model' => 'gemini-1.5-flash'
        ];
    }
    
    // API anahtarı ile modeli kontrol et
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1/models?key=" . $api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // API yanıtını kontrol et
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        
        // Debug için API yanıtını logla
        $debug_log = "API Yanıtı: " . print_r($responseData, true) . PHP_EOL;
        @file_put_contents(__DIR__ . '/../logs/gemini_api_debug_' . date('Y-m-d') . '.log', $debug_log, FILE_APPEND);
        
        // API anahtarınız için doğrudan hangi modeli kullanabileceğinizi belirleme
        // Gemini 1.5 Flash API anahtarı olduğunu biliyorsak, doğrudan bu modeli döndür
        if (strpos($api_key, 'AIzaSyCekWFRMUmQ-44ePXja2cNksDGBV5KP6EQ') !== false) {
            return [
                'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent',
                'model' => 'gemini-1.5-flash'
            ];
        }
        
        // Kullanılabilir modelleri kontrol et
        if (isset($responseData['models']) && is_array($responseData['models'])) {
            // Önce gemini-1.5-flash modelini kontrol et (öncelikli olarak)
            foreach ($responseData['models'] as $model) {
                if (isset($model['name'])) {
                    // Gemini 1.5 Flash modeli varsa kullan (öncelik bu)
                    if (strpos($model['name'], 'gemini-1.5-flash') !== false) {
                        return [
                            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent',
                            'model' => 'gemini-1.5-flash'
                        ];
                    }
                }
            }
            
            // Diğer modelleri kontrol et
            foreach ($responseData['models'] as $model) {
                if (isset($model['name'])) {
                    // Gemini 1.5 Pro modeli varsa kullan
                    if (strpos($model['name'], 'gemini-1.5-pro') !== false) {
                        return [
                            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent',
                            'model' => 'gemini-1.5-pro'
                        ];
                    }
                    // Gemini Pro 2.0 modeli varsa kullan
                    else if (strpos($model['name'], 'gemini-pro-2.0') !== false) {
                        return [
                            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro-2.0:generateContent',
                            'model' => 'gemini-pro-2.0'
                        ];
                    }
                    // Gemini Pro 2.5 modeli varsa kullan
                    else if (strpos($model['name'], 'gemini-pro-2.5') !== false) {
                        return [
                            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro-2.5:generateContent',
                            'model' => 'gemini-pro-2.5'
                        ];
                    }
                }
            }
            
            // Standart modelleri kontrol et
            foreach ($responseData['models'] as $model) {
                if (isset($model['name'])) {
                    // Gemini Pro modeli varsa kullan
                    if (strpos($model['name'], 'gemini-pro') !== false && strpos($model['name'], 'gemini-pro-') === false) {
                        return [
                            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent',
                            'model' => 'gemini-pro'
                        ];
                    }
                }
            }
        }
    }
    // API anahtarının başlangıç karakterlerine göre model tahmini yap
    if (strpos($api_key, 'AIzaSy') === 0) {
        // Google API anahtarı formatında ve bilinen API anahtarı
        return [
            'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent',
            'model' => 'gemini-1.5-flash'
        ];
    }
    
    // Hiçbir model bulunamadıysa veya API sorunu varsa varsayılan olarak gemini-1.5-flash kullan
    return [
        'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent',
        'model' => 'gemini-1.5-flash'
    ];
}

/**
 * OpenAI API anahtarına göre model tipini otomatik algılayan fonksiyon
 * @param string $api_key OpenAI API anahtarı
 * @return array Algılanan model bilgileri [api_url, model]
 */
function detectOpenAIModel($api_key) {
    // Eğer API anahtarı geçersizse veya boşsa varsayılan olarak gpt-4o kullan
    if (empty($api_key) || $api_key === 'YOUR_OPENAI_API_KEY') {
        return [
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'model' => 'gpt-4o'
        ];
    }
    
    // API anahtarı ile modeli kontrol et
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/models");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // API yanıtını kontrol et
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        
        // Kullanılabilir modelleri kontrol et
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            // Önce GPT-4 Omni modeli kontrol et
            foreach ($responseData['data'] as $model) {
                if (isset($model['id'])) {
                    // GPT-4o modeli varsa kullan
                    if (strpos($model['id'], 'gpt-4o') !== false) {
                        return [
                            'api_url' => 'https://api.openai.com/v1/chat/completions',
                            'model' => 'gpt-4o'
                        ];
                    }
                }
            }
            
            // Diğer modelleri kontrol et
            foreach ($responseData['data'] as $model) {
                if (isset($model['id'])) {
                    // GPT-4 Turbo modeli varsa kullan
                    if (strpos($model['id'], 'gpt-4-turbo') !== false) {
                        return [
                            'api_url' => 'https://api.openai.com/v1/chat/completions',
                            'model' => 'gpt-4-turbo'
                        ];
                    }
                    // GPT-4 modeli varsa kullan
                    else if (strpos($model['id'], 'gpt-4') !== false) {
                        return [
                            'api_url' => 'https://api.openai.com/v1/chat/completions',
                            'model' => 'gpt-4'
                        ];
                    }
                    // GPT-3.5 Turbo modeli varsa kullan
                    else if (strpos($model['id'], 'gpt-3.5-turbo') !== false) {
                        return [
                            'api_url' => 'https://api.openai.com/v1/chat/completions',
                            'model' => 'gpt-3.5-turbo'
                        ];
                    }
                }
            }
        }
    }
    
    // Hiçbir model bulunamadıysa veya API sorunu varsa varsayılan olarak gpt-3.5-turbo kullan
    return [
        'api_url' => 'https://api.openai.com/v1/chat/completions',
        'model' => 'gpt-3.5-turbo'
    ];
}

/**
 * xAI Grok API anahtarına göre model tipini otomatik algılayan fonksiyon
 * @param string $api_key Grok API anahtarı
 * @return array Algılanan model bilgileri [api_url, model]
 */
function detectGrokModel($api_key) {
    // Eğer API anahtarı geçersizse veya boşsa varsayılan olarak grok-2 kullan
    if (empty($api_key) || $api_key === 'YOUR_GROK_API_KEY') {
        return [
            'api_url' => 'https://api.xai.com/v1/chat/completions',
            'model' => 'grok-2'
        ];
    }
    
    // API anahtarı ile modeli kontrol et
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.xai.com/v1/models");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // API yanıtını kontrol et
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        
        // Kullanılabilir modelleri kontrol et (xAI API yapısına göre bu kısım değişebilir)
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            foreach ($responseData['data'] as $model) {
                if (isset($model['id'])) {
                    // Grok-3 modeli varsa kullan
                    if (strpos($model['id'], 'grok-3') !== false) {
                        return [
                            'api_url' => 'https://api.xai.com/v1/chat/completions',
                            'model' => 'grok-3'
                        ];
                    }
                    // Grok-2 modeli varsa kullan
                    else if (strpos($model['id'], 'grok-2') !== false) {
                        return [
                            'api_url' => 'https://api.xai.com/v1/chat/completions',
                            'model' => 'grok-2'
                        ];
                    }
                    // Grok-1 modeli varsa kullan
                    else if (strpos($model['id'], 'grok-1') !== false) {
                        return [
                            'api_url' => 'https://api.xai.com/v1/chat/completions',
                            'model' => 'grok-1'
                        ];
                    }
                }
            }
        }
    }
    
    // Hiçbir model bulunamadıysa veya API sorunu varsa varsayılan olarak grok-2 kullan
    return [
        'api_url' => 'https://api.xai.com/v1/chat/completions',
        'model' => 'grok-2'
    ];
}

// Her bir model için bilgileri otomatik algıla
$detected_gemini = detectGeminiModel($GEMINI_API_KEY);
$detected_openai = detectOpenAIModel($OPENAI_API_KEY);
$detected_grok = detectGrokModel($GROK_API_KEY);

// Log dosyasına algılanan model bilgilerini kaydet
$log_message = date('Y-m-d H:i:s') . " - Algılanan modeller: Gemini: " . $detected_gemini['model'] . 
                ", OpenAI: " . $detected_openai['model'] . ", Grok: " . $detected_grok['model'] . PHP_EOL;
@file_put_contents(__DIR__ . '/../logs/ai_models_' . date('Y-m-d') . '.log', $log_message, FILE_APPEND);

// API konfigürasyonları
$AI_CONFIG = [
    'gemini' => [
        'api_url' => $detected_gemini['api_url'],
        'api_key' => $GEMINI_API_KEY,
        'model' => $detected_gemini['model']
    ],
    'chatgpt' => [
        'api_url' => $detected_openai['api_url'],
        'api_key' => $OPENAI_API_KEY,
        'model' => $detected_openai['model']
    ],
    'grok' => [
        'api_url' => $detected_grok['api_url'],
        'api_key' => $GROK_API_KEY,
        'model' => $detected_grok['model']
    ]
];
?>
