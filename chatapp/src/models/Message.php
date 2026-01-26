<?php
/**
 * Message モデルクラス
 */

require_once __DIR__ . '/../utils/Database.php';

class Message {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * メッセージを作成
     */
    public function create($roomId, $text, $sender = 'user') {
        try {
            $stmt = $this->db->prepare('INSERT INTO messages (room_id, text, sender, delete_flag, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('iss', $roomId, $text, $sender);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Message creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * IDでメッセージを検索
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare('SELECT id, room_id, text, sender, delete_flag, created_at, updated_at FROM messages WHERE id = ? LIMIT 1');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log('Message lookup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ルームのメッセージを取得
     */
    public function findByRoomId($roomId, $limit = 50, $offset = 0) {
        try {
            // 削除フラグが0のメッセージのみを取得
            $stmt = $this->db->prepare('SELECT id, room_id, text, sender, delete_flag, created_at, updated_at FROM messages WHERE room_id = ? AND delete_flag = 0 ORDER BY created_at ASC LIMIT ? OFFSET ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('iii', $roomId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            return $messages;
        } catch (Exception $e) {
            error_log('Message lookup error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * メッセージを更新
     */
    public function update($id, $data) {
        try {
            $updates = [];
            $params = [];
            $types = '';

            if (isset($data['text'])) {
                $updates[] = 'text = ?';
                $params[] = $data['text'];
                $types .= 's';
            }

            if (empty($updates)) {
                return true;
            }

            $params[] = $id;
            $types .= 'i';

            $sql = 'UPDATE messages SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Message update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * メッセージを削除（フラグを立てる）
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare('UPDATE messages SET delete_flag = 1, updated_at = NOW() WHERE id = ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Message deletion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ルームの最新メッセージを取得（削除フラグを除外）
     */
    public function getLastMessages($roomId, $limit = 50) {
        try {
            $stmt = $this->db->prepare('SELECT id, room_id, text, sender, delete_flag, created_at, updated_at FROM messages WHERE room_id = ? AND delete_flag = 0 ORDER BY created_at DESC LIMIT ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('ii', $roomId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            return array_reverse($messages); // 古い順に
        } catch (Exception $e) {
            error_log('Message lookup error: ' . $e->getMessage());
            return [];
        }
    }
}
?>
