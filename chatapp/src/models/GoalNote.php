<?php
/**
 * GoalNote モデルクラス
 */

require_once __DIR__ . '/../utils/Database.php';

class GoalNote {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 目標達成メモを作成
     */
    public function create($userId, $roomId, $noteText, $messageId = null) {
        try {
            $stmt = $this->db->prepare('INSERT INTO goal_notes (user_id, room_id, message_id, note_text, created_at) VALUES (?, ?, ?, ?, NOW())');

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param('iiis', $userId, $roomId, $messageId, $noteText);

            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('GoalNote creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 既存メモを更新、または新規作成し、ルーム内を1件に正規化
     */
    public function saveSingleForRoom($userId, $roomId, $noteText, $messageId = null) {
        try {
            $latest = $this->findLatestByUserAndRoom($userId, $roomId);

            if ($latest) {
                $stmt = $this->db->prepare('UPDATE goal_notes SET note_text = ?, message_id = ?, created_at = NOW() WHERE id = ?');

                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
                }

                $stmt->bind_param('sii', $noteText, $messageId, $latest['id']);

                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }

                $this->deleteOtherNotes($userId, $roomId, $latest['id']);
                return $latest['id'];
            }

            $newId = $this->create($userId, $roomId, $noteText, $messageId);
            if ($newId) {
                $this->deleteOtherNotes($userId, $roomId, $newId);
            }

            return $newId;
        } catch (Exception $e) {
            error_log('GoalNote save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーのメモ一覧を取得（任意でルーム絞り込み）
     */
    public function findByUser($userId, $roomId = null, $limit = 50, $offset = 0) {
        try {
            if ($roomId) {
                $latest = $this->findLatestByUserAndRoom($userId, $roomId);
                if ($latest) {
                    $this->deleteOtherNotes($userId, $roomId, $latest['id']);
                    return [$latest];
                }
                return [];
            } else {
                $stmt = $this->db->prepare('SELECT id, user_id, room_id, message_id, note_text, created_at FROM goal_notes WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
                }
                $stmt->bind_param('iii', $userId, $limit, $offset);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $notes = [];
            while ($row = $result->fetch_assoc()) {
                $notes[] = $row;
            }

            return $notes;
        } catch (Exception $e) {
            error_log('GoalNote lookup error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 指定ルームの最新メモを取得
     */
    private function findLatestByUserAndRoom($userId, $roomId) {
        $stmt = $this->db->prepare('SELECT id, user_id, room_id, message_id, note_text, created_at FROM goal_notes WHERE user_id = ? AND room_id = ? ORDER BY created_at DESC LIMIT 1');

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
        }

        $stmt->bind_param('ii', $userId, $roomId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * 最新以外の重複メモを削除して1件に揃える
     */
    private function deleteOtherNotes($userId, $roomId, $keepId) {
        $stmt = $this->db->prepare('DELETE FROM goal_notes WHERE user_id = ? AND room_id = ? AND id != ?');

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
        }

        $stmt->bind_param('iii', $userId, $roomId, $keepId);
        $stmt->execute();
    }
}
?>
