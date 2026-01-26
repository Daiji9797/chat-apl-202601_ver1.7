<?php
/**
 * User モデルクラス
 */

require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../utils/Auth.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * ユーザーを作成
     */
    public function create($email, $password, $name = '') {
        try {
            $stmt = $this->db->prepare('INSERT INTO users (email, password, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $hashedPassword = Auth::hashPassword($password);
            $stmt->bind_param('sss', $email, $hashedPassword, $name);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('User creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * メールアドレスでユーザーを検索
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare('SELECT id, email, password, name, is_admin, created_at, updated_at FROM users WHERE email = ? LIMIT 1');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log('User lookup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * IDでユーザーを検索
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare('SELECT id, email, password, name, is_admin, points, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
            
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
            error_log('User lookup error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * メールアドレスが既に存在するか確認
     */
    public function emailExists($email) {
        $user = $this->findByEmail($email);
        return $user !== null;
    }

    /**
     * ユーザーを更新
     */
    public function update($id, $data) {
        try {
            $updates = [];
            $params = [];
            $types = '';

            if (isset($data['name'])) {
                $updates[] = 'name = ?';
                $params[] = $data['name'];
                $types .= 's';
            }

            if (empty($updates)) {
                return true;
            }

            $params[] = $id;
            $types .= 'i';

            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }

            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('User update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ログイン時にポイントを付与
     * 同じ日に既にログインしている場合はポイントを付与しない
     */
    public function addLoginPoints($id, $points = 10) {
        try {
            $today = date('Y-m-d');
            
            // 現在のユーザー情報を取得
            $stmt = $this->db->prepare('SELECT points, last_login_date FROM users WHERE id = ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
            
            $user = $result->fetch_assoc();
            $lastLoginDate = $user['last_login_date'];
            
            // 今日が初回ログインの場合、ポイントを付与
            if ($lastLoginDate !== $today) {
                $newPoints = $user['points'] + $points;
                
                $updateStmt = $this->db->prepare('UPDATE users SET points = ?, last_login_date = ?, updated_at = NOW() WHERE id = ?');
                
                if (!$updateStmt) {
                    throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
                }
                
                $updateStmt->bind_param('isi', $newPoints, $today, $id);
                return $updateStmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Add login points error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーのポイント情報を取得
     */
    public function getPoints($id) {
        try {
            $stmt = $this->db->prepare('SELECT points, last_login_date FROM users WHERE id = ?');
            
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
            error_log('Get points error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ユーザーの stalker 画像を取得
     */
    public function getStalkerImage($id) {
        try {
            $stmt = $this->db->prepare('SELECT stalker_image FROM users WHERE id = ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            $row = $result->fetch_assoc();
            return $row['stalker_image'];
        } catch (Exception $e) {
            error_log('Get stalker image error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ユーザーの stalker 画像を保存（Base64形式）
     */
    public function setStalkerImage($id, $imageData) {
        try {
            $stmt = $this->db->prepare('UPDATE users SET stalker_image = ?, updated_at = NOW() WHERE id = ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('si', $imageData, $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Set stalker image error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーの stalker 画像を削除
     */
    public function deleteStalkerImage($id) {
        try {
            $stmt = $this->db->prepare('UPDATE users SET stalker_image = NULL, updated_at = NOW() WHERE id = ?');
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Delete stalker image error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーとそれに紐づく全てのデータを物理削除
     * 
     * 削除されるデータ:
     * - ユーザーアカウント本体
     * - ユーザーが作成した全てのルーム（CASCADE で messages, goal_notes も削除される）
     * - ユーザーの gacha_status
     * - ユーザーの message_likes
     * - ユーザーの goal_notes（直接関連）
     */
    public function delete($id) {
        try {
            $conn = $this->db->getConnection();
            
            // トランザクション開始
            $conn->begin_transaction();
            
            // 1. message_likes を削除（CASCADE では削除されないため手動削除）
            $stmt = $conn->prepare('DELETE FROM message_likes WHERE user_id = ?');
            if (!$stmt) {
                throw new Exception('Prepare failed for message_likes: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete message_likes: ' . $stmt->error);
            }
            
            // 2. gacha_status を削除（CASCADE では削除されないため手動削除）
            $stmt = $conn->prepare('DELETE FROM gacha_status WHERE user_id = ?');
            if (!$stmt) {
                throw new Exception('Prepare failed for gacha_status: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete gacha_status: ' . $stmt->error);
            }
            
            // 3. goal_notes を削除（CASCADE で削除されるが、明示的に削除）
            $stmt = $conn->prepare('DELETE FROM goal_notes WHERE user_id = ?');
            if (!$stmt) {
                throw new Exception('Prepare failed for goal_notes: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete goal_notes: ' . $stmt->error);
            }
            
            // 4. rooms を削除（CASCADE で messages も削除される）
            $stmt = $conn->prepare('DELETE FROM rooms WHERE user_id = ?');
            if (!$stmt) {
                throw new Exception('Prepare failed for rooms: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete rooms: ' . $stmt->error);
            }
            
            // 5. 最後にユーザー本体を削除
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            if (!$stmt) {
                throw new Exception('Prepare failed for users: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete user: ' . $stmt->error);
            }
            
            // トランザクションをコミット
            $conn->commit();
            
            error_log("User account deleted successfully: user_id={$id}");
            return true;
        } catch (Exception $e) {
            // エラー時はロールバック
            if (isset($conn) && $conn) {
                $conn->rollback();
            }
            error_log('User deletion error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
