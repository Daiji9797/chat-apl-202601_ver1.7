<?php
/**
 * アカウント削除 API エンドポイント
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';
require_once $backendBasePath . '/src/models/User.php';

// CORS設定
Cors::setHeaders();

// ユーザー認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

$method = $_SERVER['REQUEST_METHOD'];

// ユーザー情報を取得（GET）
if ($method === 'GET') {
    try {
        $userModel = new User();
        
        // ユーザー情報を取得
        $user = $userModel->findById($userId);
        
        if (!$user) {
            ApiResponse::error('User not found', 404);
        }
        
        // ポイント情報を取得
        $pointsInfo = $userModel->getPoints($userId);
        
        // stalker 画像を取得
        $stalkerImage = $userModel->getStalkerImage($userId);
        
        ApiResponse::success([
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'is_admin' => isset($user['is_admin']) ? (bool)$user['is_admin'] : false,
                'points' => intval($pointsInfo['points']),
                'last_login_date' => $pointsInfo['last_login_date'],
                'stalker_image' => $stalkerImage
            ]
        ], 'User info retrieved successfully', 200);
    } catch (Exception $e) {
        error_log('User info error: ' . $e->getMessage());
        ApiResponse::error('Internal server error', 500);
    }
    exit;
}

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

if ($method === 'POST' && isset($inputData['_method'])) {
    $method = strtoupper($inputData['_method']);
}

if ($method !== 'DELETE') {
    ApiResponse::error('Method not allowed', 405);
}

// パスワード確認（本人確認）
$password = $inputData['password'] ?? '';
if (empty($password)) {
    ApiResponse::error('Password is required for account deletion', 400);
}

try {
    $userModel = new User();
    $user = $userModel->findById($userId);
    
    if (!$user) {
        ApiResponse::notFound('User not found');
    }

    // パスワード確認
    require_once '../../src/utils/Auth.php';
    if (!Auth::verifyPassword($password, $user['password'])) {
        ApiResponse::error('Invalid password', 401);
    }

    // ユーザーを削除（ユーザー削除時は紐付く全てのルームも削除）
    $result = $userModel->delete($userId);
    if (!$result) {
        ApiResponse::error('Failed to delete account', 500);
    }

    ApiResponse::success(null, 'Account deleted successfully');
} catch (Exception $e) {
    error_log('Account deletion error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
