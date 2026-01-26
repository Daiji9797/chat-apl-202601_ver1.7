<?php
require_once __DIR__ . '/../../utils/Cors.php';
require_once __DIR__ . '/../../utils/Database.php';
require_once __DIR__ . '/../../utils/ApiResponse.php';
require_once __DIR__ . '/../../utils/Auth.php';

Cors::handle();

header('Content-Type: application/json');

// 管理者認証
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader)) {
    ApiResponse::error('認証が必要です', 401);
}

try {
    $token = str_replace('Bearer ', '', $authHeader);
    $decoded = Auth::verifyToken($token);
    
    $db = Database::getInstance()->getConnection();
    
    // 管理者チェック
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$decoded['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_admin']) {
        ApiResponse::error('管理者権限が必要です', 403);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // お問い合わせ一覧取得
        $status = $_GET['status'] ?? 'all';
        
        $query = "
            SELECT 
                cm.*,
                u.username as user_username
            FROM contact_messages cm
            LEFT JOIN users u ON cm.user_id = u.id
        ";
        
        if ($status !== 'all') {
            $query .= " WHERE cm.status = ?";
        }
        
        $query .= " ORDER BY cm.created_at DESC";
        
        $stmt = $db->prepare($query);
        
        if ($status !== 'all') {
            $stmt->execute([$status]);
        } else {
            $stmt->execute();
        }
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ApiResponse::success(['messages' => $messages]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // ステータス更新
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $newStatus = $data['status'] ?? null;
        
        if (!$id || !in_array($newStatus, ['new', 'in_progress', 'resolved'])) {
            ApiResponse::error('無効なリクエストです', 400);
        }
        
        $stmt = $db->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        
        ApiResponse::success(['message' => 'ステータスを更新しました']);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // お問い合わせ削除
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        
        if (!$id) {
            ApiResponse::error('IDが必要です', 400);
        }
        
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        
        ApiResponse::success(['message' => 'お問い合わせを削除しました']);
        
    } else {
        ApiResponse::error('Invalid request method', 405);
    }
    
} catch (Exception $e) {
    error_log('Contact messages admin error: ' . $e->getMessage());
    ApiResponse::error('処理に失敗しました', 500);
}
