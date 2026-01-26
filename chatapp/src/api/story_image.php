<?php
/**
 * Future Story Image Generation Endpoint
 */

$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';
require_once $backendBasePath . '/src/utils/EnvConfig.php';

// Load environment variables
EnvConfig::load();

Cors::setHeaders();

$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$noteText = isset($input['note_text']) ? trim($input['note_text']) : '';
$imageComment = isset($input['image_comment']) ? trim($input['image_comment']) : '';
$provider = isset($input['provider']) ? trim($input['provider']) : 'openai';

if ($noteText === '') {
    ApiResponse::error('note_text is required', 400);
}

// Function to translate and enhance prompt for image generation
function buildImagePrompt($japaneseText, $imageComment = '') {
    // Keywords mapping for common goal-related terms
    $keywords = [
        '成長' => 'personal growth, people achieving goals',
        '成功' => 'success, achievement, celebration',
        '達成' => 'accomplishment, reaching goals',
        '未来' => 'bright future, hope, optimism',
        '幸せ' => 'happiness, joy, smiling people',
        '家族' => 'family together, warm atmosphere',
        '仕事' => 'professional work environment, office',
        '健康' => 'healthy lifestyle, wellness, fitness',
        '学習' => 'learning, studying, education',
        '目標' => 'achieving goals, success moment'
    ];
    
    // Build visual scene description based on keywords
    $visualElements = [];
    foreach ($keywords as $jpWord => $enDescription) {
        if (mb_strpos($japaneseText, $jpWord) !== false) {
            $visualElements[] = $enDescription;
        }
    }
    
    // Default to positive achievement scene if no keywords matched
    if (empty($visualElements)) {
        $visualElements[] = 'person achieving personal goals';
    }
    
    // Build the prompt in English
    $promptParts = [];
    
    // Main scene description in anime style
    $promptParts[] = "Anime style illustration showing " . implode(', ', $visualElements);
    
    // Add emotional and atmospheric details
    $promptParts[] = "warm lighting, bright and positive atmosphere";
    $promptParts[] = "Japanese anime art style";
    $promptParts[] = "smiling characters showing happiness and hope";
    $promptParts[] = "modern and clean composition";
    $promptParts[] = "colorful and vibrant illustration";
    
    // Add user's image comment if provided
    if ($imageComment !== '') {
        $promptParts[] = $imageComment;
    }
    
    // Combine all parts
    $prompt = implode(', ', $promptParts);
    
    // Add quality instructions for anime style
    $prompt .= ". High quality anime illustration, detailed digital art, bright colors, inspirational mood, manga style.";
    
    // Limit prompt length (DALL-E has ~1000 char limit)
    if (strlen($prompt) > 900) {
        $prompt = substr($prompt, 0, 900);
    }
    
    return $prompt;
}

// Build enhanced English prompt from Japanese text
$prompt = buildImagePrompt($noteText, $imageComment);

// Route to appropriate provider
if ($provider === 'gemini') {
    generateImageWithGemini($prompt);
} else {
    generateImageWithOpenAI($prompt);
}

/**
 * Generate image using OpenAI DALL-E
 */
function generateImageWithOpenAI($prompt) {
    $apiKey = EnvConfig::get('OPENAI_API_KEY');
    if (!$apiKey) {
        ApiResponse::error('OpenAI API key is not configured', 500);
    }

    try {
        $payload = [
            'model' => 'dall-e-2',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024'
        ];

        $ch = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        if ($statusCode >= 400 || !$decoded || !isset($decoded['data'][0]['url'])) {
            $message = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Failed to generate image';
            ApiResponse::error($message, 500);
        }

        // Download image from URL and convert to base64
        $imageUrl = $decoded['data'][0]['url'];
        $imageData = file_get_contents($imageUrl);
        if ($imageData === false) {
            throw new Exception('Failed to download generated image');
        }
        $base64Image = base64_encode($imageData);

        ApiResponse::success([
            'image_base64' => $base64Image,
            'prompt' => $prompt,
            'provider' => 'openai'
        ], 'Image generated successfully with OpenAI', 200);
    } catch (Exception $e) {
        error_log('Error generating image with OpenAI: ' . $e->getMessage());
        ApiResponse::error('Failed to generate image with OpenAI', 500);
    }
}

/**
 * Generate image using Google Gemini (Currently not supported for direct image generation)
 */
function generateImageWithGemini($prompt) {
    // Gemini API does not support direct image generation via generateContent endpoint
    // Image generation with Gemini requires Imagen API which needs Google Cloud Vertex AI setup
    
    ApiResponse::error(
        '申し訳ございません。Geminiでの画像生成は現在サポートされていません。' . "\n" .
        'Google Gemini APIは直接画像を返すことができません（text/jsonのみ対応）。' . "\n" .
        '画像生成にはOpenAI (DALL-E)をご利用ください。',
        400
    );
}
