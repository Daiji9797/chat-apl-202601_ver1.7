<?php
/**
 * Rooms API エンドポイント
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';
require_once $backendBasePath . '/src/models/Room.php';

// CORS設定
Cors::setHeaders();

// ユーザーを認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

$method = $_SERVER['REQUEST_METHOD'];
$roomModel = new Room();

try {
    if ($method === 'GET') {
        // ユーザーのすべてのルームを取得
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $rooms = $roomModel->findByUserId($userId, $limit, $offset);
        
        ApiResponse::success($rooms, 'Rooms retrieved successfully');
        
    } elseif ($method === 'POST') {
        // 新しいルームを作成
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? 'New Chat');
        
        if (empty($name)) {
            $name = 'New Chat';
        }
        
        $roomId = $roomModel->create($userId, $name);
        
        if (!$roomId) {
            ApiResponse::error('Failed to create room', 500);
        }
        
        $room = $roomModel->findById($roomId);
        ApiResponse::success($room, 'Room created successfully', 201);
        
    } else {
        ApiResponse::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('Rooms API error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
