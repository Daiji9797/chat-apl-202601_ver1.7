<?php
/**
 * MessageLike モデルクラス
 */

require_once __DIR__ . '/../utils/Database.php';

class MessageLike {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * いいね状態を設定（true で追加、false で削除）
     */
    public function setLike($messageId, $userId, $like = true) {
        try {
            if ($like) {
                $stmt = $this->db->prepare('INSERT IGNORE INTO message_likes (message_id, user_id, created_at) VALUES (?, ?, NOW())');
            } else {
                $stmt = $this->db->prepare('DELETE FROM message_likes WHERE message_id = ? AND user_id = ?');
            }

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param('ii', $messageId, $userId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('MessageLike setLike error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 単一メッセージの統計を取得
     */
    public function getStats($messageId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT 
                    COALESCE(cnt.cnt, 0) AS like_count,
                    CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
                 FROM messages m
                 LEFT JOIN (
                    SELECT message_id, COUNT(*) AS cnt 
                    FROM message_likes 
                    WHERE message_id = ?
                 ) cnt ON cnt.message_id = m.id
                 LEFT JOIN message_likes ul ON ul.message_id = m.id AND ul.user_id = ?
                 WHERE m.id = ?
                 LIMIT 1'
            );

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param('iii', $messageId, $userId, $messageId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            return [
                'like_count' => isset($row['like_count']) ? intval($row['like_count']) : 0,
                'liked_by_me' => isset($row['liked_by_me']) ? boolval($row['liked_by_me']) : false,
            ];
        } catch (Exception $e) {
            error_log('MessageLike getStats error: ' . $e->getMessage());
            return [
                'like_count' => 0,
                'liked_by_me' => false,
            ];
        }
    }

    /**
     * ルーム内全メッセージのいいね情報をまとめて取得
     */
    public function getSummaryByRoom($roomId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT 
                    m.id AS message_id,
                    COALESCE(cnt.cnt, 0) AS like_count,
                    CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
                 FROM messages m
                 LEFT JOIN (
                    SELECT message_id, COUNT(*) AS cnt 
                    FROM message_likes 
                    GROUP BY message_id
                 ) cnt ON cnt.message_id = m.id
                 LEFT JOIN message_likes ul ON ul.message_id = m.id AND ul.user_id = ?
                 WHERE m.room_id = ? AND m.delete_flag = 0'
            );

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param('ii', $userId, $roomId);
            $stmt->execute();
            $result = $stmt->get_result();

            $map = [];
            while ($row = $result->fetch_assoc()) {
                $map[intval($row['message_id'])] = [
                    'like_count' => intval($row['like_count']),
                    'liked_by_me' => boolval($row['liked_by_me'])
                ];
            }

            return $map;
        } catch (Exception $e) {
            error_log('MessageLike getSummaryByRoom error: ' . $e->getMessage());
            return [];
        }
    }
}
?>