<?php
/**
 * ガチャ実行 API エンドポイント
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

// ガチャを実行（POST）
if ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // リクエストパラメータの検証
        if (!isset($data['gacha_type']) || !isset($data['points_used'])) {
            ApiResponse::error('Missing required parameters: gacha_type, points_used', 400);
        }

        $gachaType = intval($data['gacha_type']);
        $pointsUsed = intval($data['points_used']);
        $gachaId = isset($data['gacha_id']) ? intval($data['gacha_id']) : 0;
        $resultStage = isset($data['result_stage']) ? intval($data['result_stage']) : 1;

        // ポイント検証（10ポイントまたは1000ポイント）
        if (!in_array($pointsUsed, [10, 1000])) {
            ApiResponse::error('Invalid points value. Must be 10 or 1000', 400);
        }

        // データベース接続
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

        // ユーザーの現在のポイントを取得
        $stmt = $conn->prepare('SELECT points FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            ApiResponse::error('User not found', 404);
        }

        $currentPoints = intval($user['points']);

        // ポイント不足チェック
        if ($currentPoints < $pointsUsed) {
            ApiResponse::error('Insufficient points', 400);
        }

        // ユーザーのポイントを更新（消費）
        $newPoints = $currentPoints - $pointsUsed;
        
        $updateStmt = $conn->prepare('UPDATE users SET points = ? WHERE id = ?');
        $updateStmt->bind_param('ii', $newPoints, $userId);
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update user points');
        }

        // ガチャのstageをDBに保存（upsert）
        $upsertStmt = $conn->prepare('
            INSERT INTO gacha_status (user_id, gacha_id, stage) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE stage = ?
        ');
        $upsertStmt->bind_param('iiii', $userId, $gachaId, $resultStage, $resultStage);
        if (!$upsertStmt->execute()) {
            throw new Exception('Failed to update gacha status');
        }

        // ガチャ実行ログを記録（オプション）
        // TODO: gacha_logs テーブルに記録する場合はここに実装

        // 成功レスポンス
        ApiResponse::success([
            'success' => true,
            'message' => 'Gacha executed successfully',
            'gacha_type' => $gachaType,
            'gacha_id' => $gachaId,
            'points_used' => $pointsUsed,
            'result_stage' => $resultStage,
            'previous_points' => $currentPoints,
            'current_points' => $newPoints,
            'user' => [
                'id' => $userId,
                'points' => $newPoints,
            ]
        ]);

    } catch (Exception $err) {
        error_log('Gacha API Error: ' . $err->getMessage());
        ApiResponse::error('Gacha execution failed: ' . $err->getMessage(), 500);
    }
} else {
    ApiResponse::error('Method not allowed', 405);
}
?>
