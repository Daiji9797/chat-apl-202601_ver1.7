<?php
/**
 * Message delete API エンドポイント
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

// ユーザー認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

// 入力取得
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($inputData['_method'])) {
    $method = strtoupper($inputData['_method']);
}

if ($method !== 'DELETE') {
    ApiResponse::error('Method not allowed', 405);
}

$messageId = intval($_GET['messageId'] ?? 0);
$roomId    = intval($_GET['roomId'] ?? 0);

if ($messageId <= 0 || $roomId <= 0) {
    ApiResponse::error('Invalid parameters', 400);
}

$roomModel = new Room();
$messageModel = new Message();

try {
    // ルーム所有確認
    $room = $roomModel->findById($roomId);
    if (!$room || $room['user_id'] != $userId) {
        ApiResponse::forbidden('You do not have access to this room');
    }

    // メッセージ存在確認
    $msg = $messageModel->findById($messageId);
    if (!$msg || $msg['room_id'] != $roomId) {
        ApiResponse::notFound('Message not found');
    }

    // 削除フラグを立てる
    $result = $messageModel->delete($messageId);
    if (!$result) {
        ApiResponse::error('Failed to delete message', 500);
    }

    ApiResponse::success(null, 'Message deleted');
} catch (Exception $e) {
    error_log('Message delete API error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
