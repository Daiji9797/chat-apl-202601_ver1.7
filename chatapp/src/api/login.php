<?php
/**
 * ログイン API エンドポイント
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

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

// リクエストボディを取得
$input = json_decode(file_get_contents('php://input'), true);

// バリデーション
if (!isset($input['email']) || !isset($input['password'])) {
    ApiResponse::error('Email and password are required', 400);
}

$email = trim($input['email']);
$password = trim($input['password']);

try {
    $userModel = new User();
    
    // ユーザーを検索
    $user = $userModel->findByEmail($email);
    
    if (!$user) {
        ApiResponse::error('メールアドレスまたはパスワードが正しくありません', 400);
    }
    
    // パスワードを検証
    if (!Auth::verifyPassword($password, $user['password'])) {
        ApiResponse::error('メールアドレスまたはパスワードが正しくありません', 400);
    }
    
    // ログイン時にポイントを付与
    $userModel->addLoginPoints($user['id'], 10);
    
    // トークンを生成
    $token = Auth::generateToken($user['id']);
    
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'is_admin' => isset($user['is_admin']) ? (bool)$user['is_admin'] : false
        ],
        'token' => $token
    ], 'Logged in successfully', 200);
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
