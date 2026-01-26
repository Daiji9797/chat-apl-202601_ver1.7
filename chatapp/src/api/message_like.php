<?php
/**
 * Message Like API エンドポイント
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
require_once $backendBasePath . '/src/models/MessageLike.php';

// CORS設定
Cors::setHeaders();

// ユーザー認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

// POST のみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$messageId = intval($input['messageId'] ?? 0);
$roomId    = intval($input['roomId'] ?? 0);
$likeFlag  = isset($input['like']) ? boolval($input['like']) : true;

if ($messageId <= 0 || $roomId <= 0) {
    ApiResponse::error('Invalid parameters', 400);
}

$roomModel = new Room();
$messageModel = new Message();
$likeModel = new MessageLike();

try {
    // ルーム所有確認
    $room = $roomModel->findById($roomId);
    if (!$room || $room['user_id'] != $userId) {
        ApiResponse::forbidden('You do not have access to this room');
    }

    // メッセージ存在確認
    $msg = $messageModel->findById($messageId);
    if (!$msg || $msg['room_id'] != $roomId || intval($msg['delete_flag']) === 1) {
        ApiResponse::notFound('Message not found');
    }

    $result = $likeModel->setLike($messageId, $userId, $likeFlag);
    if (!$result) {
        ApiResponse::error('Failed to update like', 500);
    }

    $stats = $likeModel->getStats($messageId, $userId);

    ApiResponse::success($stats, 'Like status updated');
} catch (Exception $e) {
    error_log('Message like API error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>