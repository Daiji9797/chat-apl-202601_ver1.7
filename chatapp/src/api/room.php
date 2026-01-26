<?php
/**
 * Room詳細 API エンドポイント
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

// ユーザーを認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

// roomIdをURLから取得
$roomId = intval($_GET['roomId'] ?? 0);
if ($roomId <= 0) {
    ApiResponse::error('Invalid room ID', 400);
}

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

$method = $_SERVER['REQUEST_METHOD'];
// POST + _method でメソッドを上書き（ブラウザでの DELETE/PUT 制限回避）
if ($method === 'POST' && isset($inputData['_method'])) {
    $method = strtoupper($inputData['_method']);
}
$roomModel = new Room();
$messageModel = new Message();
$likeModel = new MessageLike();

try {
    // ルームが存在するか、ユーザーがアクセス権を持つか確認
    $room = $roomModel->findById($roomId);
    if (!$room || $room['user_id'] != $userId) {
        ApiResponse::forbidden('You do not have access to this room');
    }
    
    if ($method === 'GET') {
        // ルームのメッセージを取得
        // intvalでSQLインジェクション対策（整数に変換）
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $messages = $messageModel->findByRoomId($roomId, $limit, $offset);

        // いいね情報を付与
        $likeMap = $likeModel->getSummaryByRoom($roomId, $userId);
        foreach ($messages as &$m) {
            $msgId = intval($m['id']);
            $stats = $likeMap[$msgId] ?? ['like_count' => 0, 'liked_by_me' => false];
            $m['like_count'] = $stats['like_count'];
            $m['liked_by_me'] = $stats['liked_by_me'];
        }
        unset($m);
        
        ApiResponse::success([
            'room' => $room,
            'messages' => $messages
        ], 'Room details retrieved successfully');
        
    } elseif ($method === 'PUT') {
        // ルーム情報を更新
        $input = is_array($inputData) ? $inputData : [];
        $data = [];
        
        if (isset($input['name'])) {
            $data['name'] = trim($input['name']);
        }
        
        if (isset($input['is_completed'])) {
            $data['is_completed'] = $input['is_completed'] ? 1 : 0;
        }
        
        if (empty($data)) {
            ApiResponse::error('No data to update', 400);
        }
        
        $result = $roomModel->update($roomId, $userId, $data);
        
        if (!$result) {
            ApiResponse::error('Failed to update room', 500);
        }
        
        $updatedRoom = $roomModel->findById($roomId);
        ApiResponse::success($updatedRoom, 'Room updated successfully');
        
    } elseif ($method === 'DELETE') {
        // ルームを削除
        $result = $roomModel->delete($roomId, $userId);
        
        if (!$result) {
            ApiResponse::error('Failed to delete room', 500);
        }
        
        ApiResponse::success(null, 'Room deleted successfully');
        
    } else {
        ApiResponse::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('Room details API error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
