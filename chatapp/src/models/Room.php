<?php
/**
 * Room モデルクラス
 */

require_once __DIR__ . '/../utils/Database.php';

class Room {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * ルームを作成
     */
    public function create($userId, $name = 'New Chat') {
        try {
            $stmt = $this->db->prepare('INSERT INTO rooms (user_id, name, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('is', $userId, $name);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Room creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * IDでルームを検索（削除済みを除外）
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare('SELECT id, user_id, name, is_completed, delete_flag, created_at, updated_at FROM rooms WHERE id = ? AND delete_flag = 0 LIMIT 1');
            
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
            error_log('Room lookup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ユーザーのすべてのルームを取得（削除済みを除外）
     */
    public function findByUserId($userId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare('SELECT id, user_id, name, is_completed, created_at, updated_at FROM rooms WHERE user_id = ? AND delete_flag = 0 ORDER BY updated_at DESC LIMIT ? OFFSET ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('iii', $userId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $rooms = [];
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
            
            return $rooms;
        } catch (Exception $e) {
            error_log('Room lookup error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ルームを更新
     */
    public function update($id, $userId, $data) {
        try {
            // ユーザーがこのルームの所有者かを確認
            $room = $this->findById($id);
            if (!$room || $room['user_id'] != $userId) {
                throw new Exception('Unauthorized');
            }

            $updates = [];
            $params = [];
            $types = '';

            if (isset($data['name'])) {
                $updates[] = 'name = ?';
                $params[] = $data['name'];
                $types .= 's';
            }

            if (isset($data['is_completed'])) {
                $updates[] = 'is_completed = ?';
                $params[] = intval($data['is_completed']);
                $types .= 'i';
            }

            if (empty($updates)) {
                return true;
            }

            $params[] = $id;
            $types .= 'i';

            $sql = 'UPDATE rooms SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Room update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ルームを削除（ソフト削除）
     */
    public function delete($id, $userId) {
        try {
            // ユーザーがこのルームの所有者かを確認
            $room = $this->findById($id);
            if (!$room || $room['user_id'] != $userId) {
                throw new Exception('Unauthorized');
            }

            $stmt = $this->db->prepare('UPDATE rooms SET delete_flag = 1, updated_at = NOW() WHERE id = ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Room deletion error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
