<?php
/**
 * ガチャ画像管理 API（管理者専用）
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/utils/Database.php';
require_once $backendBasePath . '/utils/Auth.php';
require_once $backendBasePath . '/utils/ApiResponse.php';
require_once $backendBasePath . '/utils/Cors.php';

// CORS設定
Cors::setHeaders();

// ユーザー認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

// 管理者チェック
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !$user['is_admin']) {
    ApiResponse::error('Admin access required', 403);
}

$method = $_SERVER['REQUEST_METHOD'];

// gacha_images テーブルがなければ作成
$createTableSQL = "
CREATE TABLE IF NOT EXISTS gacha_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gacha_type INT NOT NULL,
    stage INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    image_path TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_gacha (gacha_type, stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createTableSQL);

// GET: 画像一覧を取得
if ($method === 'GET') {
    try {
        $stmt = $conn->prepare('SELECT id, gacha_id, stage, filename, image_path, created_at FROM gacha_images ORDER BY gacha_id, stage');
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = [
                'id' => $row['id'],
                'gacha_id' => $row['gacha_id'],
                'stage' => $row['stage'],
                'filename' => $row['filename'],
                'image_path' => $row['image_path'],
                'image_url' => $row['image_path'], // Base64データをそのまま返す
                'created_at' => $row['created_at']
            ];
        }
        
        ApiResponse::success($images);
        
    } catch (Exception $err) {
        error_log('Get gacha images error: ' . $err->getMessage());
        ApiResponse::error('Failed to get gacha images', 500);
    }
}

// POST: 画像をアップロード
elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['gacha_id']) || !isset($data['stage']) || !isset($data['image_data'])) {
            ApiResponse::error('Missing required parameters', 400);
        }
        
        $gachaId = intval($data['gacha_id']);
        $stage = intval($data['stage']);
        $imageData = $data['image_data']; // Base64形式
        $filename = isset($data['filename']) ? $data['filename'] : 'image.png';
        
        // 既存の画像を削除して置き換え
        $stmt = $conn->prepare('DELETE FROM gacha_images WHERE gacha_id = ? AND stage = ?');
        $stmt->bind_param('ii', $gachaId, $stage);
        $stmt->execute();
        
        // 新しい画像を保存
        $stmt = $conn->prepare('INSERT INTO gacha_images (gacha_id, stage, filename, image_path) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $gachaId, $stage, $filename, $imageData);
        
        if ($stmt->execute()) {
            ApiResponse::success([
                'id' => $stmt->insert_id,
                'gacha_id' => $gachaId,
                'stage' => $stage,
                'filename' => $filename
            ], 'Image uploaded successfully');
        } else {
            throw new Exception('Failed to insert image');
        }
        
    } catch (Exception $err) {
        error_log('Upload gacha image error: ' . $err->getMessage());
        ApiResponse::error('Failed to upload image: ' . $err->getMessage(), 500);
    }
}

// DELETE: 画像を削除
elseif ($method === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['image_id'])) {
            ApiResponse::error('Missing image_id parameter', 400);
        }
        
        $imageId = intval($data['image_id']);
        
        $stmt = $conn->prepare('DELETE FROM gacha_images WHERE id = ?');
        $stmt->bind_param('i', $imageId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            ApiResponse::success(null, 'Image deleted successfully');
        } else {
            ApiResponse::error('Image not found', 404);
        }
        
    } catch (Exception $err) {
        error_log('Delete gacha image error: ' . $err->getMessage());
        ApiResponse::error('Failed to delete image', 500);
    }
}

else {
    ApiResponse::error('Method not allowed', 405);
}
?>
