<?php
/**
 * Goal Note API エンドポイント
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';
require_once $backendBasePath . '/src/models/Room.php';
require_once $backendBasePath . '/src/models/GoalNote.php';
require_once $backendBasePath . '/src/models/Message.php';

// CORS設定
Cors::setHeaders();

// ユーザーを認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

$method = $_SERVER['REQUEST_METHOD'];
$roomModel = new Room();
$goalModel = new GoalNote();
$messageModel = new Message();

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

// POST + _method でメソッドを上書き（必要時）
if ($method === 'POST' && isset($inputData['_method'])) {
    $method = strtoupper($inputData['_method']);
}

try {
    if ($method === 'GET') {
        $roomId = isset($_GET['roomId']) ? intval($_GET['roomId']) : null;
        if ($roomId) {
            // ルームアクセス権を確認
            $room = $roomModel->findById($roomId);
            if (!$room || $room['user_id'] != $userId) {
                ApiResponse::forbidden('You do not have access to this room');
            }
        }
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        $notes = $goalModel->findByUser($userId, $roomId, $limit, $offset);
        ApiResponse::success($notes, 'Goal notes retrieved successfully');
    } elseif ($method === 'POST') {
        $roomId = intval($inputData['roomId'] ?? 0);
        $noteText = trim($inputData['note_text'] ?? '');
        $messageId = isset($inputData['messageId']) ? intval($inputData['messageId']) : null;

        if ($roomId <= 0 || $noteText === '') {
            ApiResponse::error('roomId and note_text are required', 400);
        }

        // ルームアクセス権を確認
        $room = $roomModel->findById($roomId);
        if (!$room || $room['user_id'] != $userId) {
            ApiResponse::forbidden('You do not have access to this room');
        }

        // メッセージ紐付けチェック（任意）
        if ($messageId) {
            $message = $messageModel->findById($messageId);
            if (!$message || intval($message['room_id']) !== $roomId) {
                ApiResponse::error('Invalid messageId for this room', 400);
            }
        }

        $noteId = $goalModel->saveSingleForRoom($userId, $roomId, $noteText, $messageId);
        if (!$noteId) {
            ApiResponse::error('Failed to save goal note', 500);
        }

        ApiResponse::success(['id' => $noteId], 'Goal note saved', 200);
    } else {
        ApiResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('Goal API error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
