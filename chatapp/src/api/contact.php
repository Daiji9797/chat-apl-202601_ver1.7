<?php
require_once __DIR__ . '/../utils/Cors.php';
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Auth.php';

Cors::handle();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Invalid request method', 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // バリデーション
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    
    if (empty($name) || strlen($name) < 2) {
        ApiResponse::error('お名前は2文字以上で入力してください', 400);
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('有効なメールアドレスを入力してください', 400);
    }
    
    if (empty($subject) || strlen($subject) < 3) {
        ApiResponse::error('件名は3文字以上で入力してください', 400);
    }
    
    if (empty($message) || strlen($message) < 10) {
        ApiResponse::error('お問い合わせ内容は10文字以上で入力してください', 400);
    }
    
    // ログインユーザーIDを取得（任意）
    $userId = null;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!empty($authHeader)) {
        try {
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = Auth::verifyToken($token);
            $userId = $decoded['user_id'] ?? null;
        } catch (Exception $e) {
            // トークンが無効でも問い合わせは受け付ける
        }
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO contact_messages (user_id, name, email, subject, message, status)
        VALUES (?, ?, ?, ?, ?, 'new')
    ");
    
    $stmt->execute([$userId, $name, $email, $subject, $message]);
    
    ApiResponse::success([
        'message' => 'お問い合わせを送信しました。ご連絡ありがとうございます。',
        'id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log('Contact error: ' . $e->getMessage());
    ApiResponse::error('お問い合わせの送信に失敗しました', 500);
}
