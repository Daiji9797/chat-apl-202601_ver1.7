<?php
/**
 * ガチャ状態 API エンドポイント
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';

// CORS設定
Cors::setHeaders();

// ユーザー認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ガチャ状態を取得（GET）
if ($method === 'GET') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // gacha_statusテーブルがなければ作成
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS gacha_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            gacha_id INT NOT NULL,
            stage INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_gacha (user_id, gacha_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $conn->query($createTableSQL);

        // ユーザーのガチャ状態を取得
        $stmt = $conn->prepare('SELECT gacha_id, stage FROM gacha_status WHERE user_id = ? ORDER BY gacha_id');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $gachaStatuses = [];
        while ($row = $result->fetch_assoc()) {
            $gachaStatuses[] = [
                'gacha_id' => intval($row['gacha_id']),
                'stage' => intval($row['stage'])
            ];
        }

        ApiResponse::success($gachaStatuses, 'Gacha statuses retrieved successfully', 200);
        exit;

    } catch (Exception $e) {
        error_log('Gacha status API error: ' . $e->getMessage());
        ApiResponse::error('Internal server error', 500);
        exit;
    }
} else {
    ApiResponse::error('Method not allowed', 405);
    exit;
}
