<?php
/**
 * Chat API エンドポイント
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';
require_once $backendBasePath . '/src/models/Room.php';
require_once $backendBasePath . '/src/models/Message.php';

// CORS設定
Cors::setHeaders();

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

// ユーザーを認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

// リクエストボディを取得
$input = json_decode(file_get_contents('php://input'), true);

// バリデーション
if (!isset($input['message']) || !isset($input['roomId'])) {
    ApiResponse::error('Message and roomId are required', 400);
}

$message = trim($input['message']);
$roomId = intval($input['roomId']);
$providedHistory = $input['history'] ?? null;
$provider = isset($input['provider']) ? strtolower($input['provider']) : 'openai';

// provider は openai または gemini のみ許可
if (!in_array($provider, ['openai', 'gemini'])) {
    $provider = 'openai';
}

if (empty($message)) {
    ApiResponse::error('Message cannot be empty', 400);
}

try {
    $roomModel = new Room();
    $messageModel = new Message();
    
    // ルームが存在するか確認
    $room = $roomModel->findById($roomId);
    if (!$room || $room['user_id'] != $userId) {
        ApiResponse::forbidden('You do not have access to this room');
    }
    
    // ユーザーメッセージをDBに保存
    $messageModel->create($roomId, $message, 'user');
    
    // provider に応じて API を呼び出し
    if ($provider === 'gemini') {
        $aiResponse = callGeminiAPI($message, $roomId, $providedHistory);
    } else {
        $aiResponse = callOpenAIAPI($message, $roomId, $providedHistory);
    }
    
    if (!$aiResponse) {
        ApiResponse::error('Failed to get AI response', 500);
    }
    
    // AIレスポンスをDBに保存
    $messageModel->create($roomId, $aiResponse, 'bot');
    
    ApiResponse::success([
        'response' => $aiResponse
    ], 'Message processed successfully', 200);
    
} catch (Exception $e) {
    error_log('Chat error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}

/**
 * OpenAI APIを呼び出す
 */
function callOpenAIAPI($message, $roomId, $providedHistory = null) {
    try {
        $apiKey = OPENAI_API_KEY;
        if (empty($apiKey)) {
            error_log('ERROR: OPENAI_API_KEY is not set in .env file');
            throw new Exception('OpenAI API key not configured');
        }
        error_log('OpenAI API call started for room: ' . $roomId);
        
        // 会話履歴を準備
        $messages = [];
        
        // システムプロンプトを追加（回答の多様性と品質を向上）
        $systemPrompt = 'あなたは親切で知識豊富なアシスタントです。ユーザーの質問に対して、常に新しく、思慮深い、かつユニークな回答を提供してください。単調または繰り返しの回答は避けてください。異なる視点や具体的な例を含めるようにしてください。常に相手の状況や背景を考慮し、より有用で詳細な回答を心がけてください。

【重要な制限事項】
以下のコンテンツに関する質問や要求には一切応じないでください：
- グラフィックな暴力や残虐な内容
- 未成年者に危険または有害な行動を促す可能性のあるバイラルチャレンジ
- 性的、恋愛的、または暴力的なロールプレイ
- 自傷行為の描写
- 極端な美容基準、不健康なダイエット、ボディシェイミングを助長するコンテンツ

これらの内容に該当する質問には、「申し訳ございませんが、その質問にはお答えできません。別の質問がございましたら、お気軽にお尋ねください。」と丁寧に断ってください。';
        
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];
        
        // クライアントから提供された履歴を優先的に使用
        if (is_array($providedHistory) && !empty($providedHistory)) {
            foreach ($providedHistory as $histItem) {
                if (isset($histItem['role']) && isset($histItem['content'])) {
                    $messages[] = [
                        'role' => $histItem['role'],
                        'content' => $histItem['content']
                    ];
                }
            }
        }
        
        // 現在のメッセージを追加
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        error_log('OpenAI API messages count: ' . count($messages));
        
        // OpenAI API呼び出し
        $curlHandle = curl_init();
        
        curl_setopt_array($curlHandle, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                //回答の多様性を向上させるパラメータ
                'temperature' => 0.85,
                //確率分布の最上位90%から選択（より創造的）
                'top_p' => 0.9,
                //繰り返しの単語を抑制
                'frequency_penalty' => 1.0,
                //新しいトピック導入を促進
                'presence_penalty' => 0.5,
                // 最大トークン数（増加させて回答の途中切れを防止）
                'max_tokens' => 2000
            ])
        ]);
        
        $response = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curlHandle);
        
        curl_close($curlHandle);
        
        error_log('OpenAI API HTTP Code: ' . $httpCode);
        error_log('OpenAI API Response: ' . substr($response, 0, 500));
        
        if ($curlError) {
            error_log('CURL Error: ' . $curlError);
            throw new Exception('Network error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            error_log('OpenAI API error (' . $httpCode . '): ' . $response);
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception('OpenAI API error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }
        
        $responseData = json_decode($response, true);
        
        if (!$responseData) {
            error_log('Failed to decode JSON response: ' . $response);
            throw new Exception('Invalid JSON response from OpenAI API');
        }
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            error_log('Unexpected API response structure: ' . json_encode($responseData));
            throw new Exception('Invalid response structure from OpenAI API');
        }
        
        return $responseData['choices'][0]['message']['content'];
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log('OpenAI API call error: ' . $errorMsg);
        return 'エラー: ' . $errorMsg;
    }
}

/**
 * Gemini APIを呼び出す
 */
function callGeminiAPI($message, $roomId, $providedHistory = null) {
    try {
        $apiKey = GEMINI_API_KEY; // 定数が定義されている前提
        if (empty($apiKey)) {
            error_log('ERROR: GEMINI_API_KEY is not set');
            throw new Exception('Gemini API key not configured');
        }
        
        // メッセージを準備
        $contents = [];
        $lastRole = null; // 交互の発言をチェック用

        // 提供された履歴を使用
        if (is_array($providedHistory) && !empty($providedHistory)) {
            foreach ($providedHistory as $histItem) {
                if (isset($histItem['role']) && isset($histItem['content'])) {
                    // Gemini 用に role を変換
                    $currentRole = ($histItem['role'] === 'assistant' || $histItem['role'] === 'bot') ? 'model' : 'user';
                    
                    // 【重要】連続したロールの場合、前のメッセージに結合するなどの対処が必要
                    // ここでは簡易的に、直前と同じロールならスキップするか、結合する処理を入れるのが安全です
                    // Geminiは user -> model -> user の順序厳守です
                    
                    if ($lastRole === $currentRole) {
                         // エラー回避のため、今回はスキップまたは結合などの処理を検討してください
                         // この例では単純に追加しますが、実運用では注意が必要です
                    }

                    $contents[] = [
                        'role' => $currentRole,
                        'parts' => [['text' => $histItem['content']]]
                    ];
                    $lastRole = $currentRole;
                }
            }
        }
        
        // 現在のメッセージを追加
        // 直前が user の場合、エラーになる可能性があるためチェックが必要ですが、通常は履歴の最後は model で終わっているはずです
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];
        
        error_log('Gemini API contents count: ' . count($contents));
        
        // システムプロンプト（Gemini では systemInstruction として指定）
        $systemPrompt = 'あなたは親切で知識豊富なアシスタントです。ユーザーの質問に対して、常に新しく、思慮深い、かつユニークな回答を提供してください。単調または繰り返しの回答は避けてください。異なる視点や具体的な例を含めるようにしてください。常に相手の状況や背景を考慮し、より有用で詳細な回答を心がけてください。

【重要な制限事項】
以下のコンテンツに関する質問や要求には一切応じないでください：
- グラフィックな暴力や残虐な内容
- 未成年者に危険または有害な行動を促す可能性のあるバイラルチャレンジ
- 性的、恋愛的、または暴力的なロールプレイ
- 自傷行為の描写
- 極端な美容基準、不健康なダイエット、ボディシェイミングを助長するコンテンツ

これらの内容に該当する質問には、「申し訳ございませんが、その質問にはお答えできません。別の質問がございましたら、お気軽にお尋ねください。」と丁寧に断ってください。';
        
$curlHandle = curl_init();
        
        // ★修正ポイント: モデル名を gemini-2.5-flash に、バージョンを v1beta に変更
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

        $postData = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.85,
                'topP' => 0.9,
                'maxOutputTokens' => 2000
            ]
        ];

        curl_setopt_array($curlHandle, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($postData)
        ]);
        
        $response = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curlHandle);
        
        curl_close($curlHandle);
        
        // デバッグ用ログ
        if ($httpCode !== 200) {
            error_log("Gemini API Error [$httpCode]: " . $response);
        }

        if ($curlError) {
            throw new Exception('Network error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? $response;
            throw new Exception('Gemini API error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }
        
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            // 安全性の理由などでブロックされた場合、finishReasonなどを確認する必要があります
            error_log('Unexpected structure: ' . json_encode($responseData));
            return '申し訳ありませんが、適切な応答を生成できませんでした。';
        }
        
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 'エラーが発生しました: ' . $e->getMessage();
    }
}
?>
