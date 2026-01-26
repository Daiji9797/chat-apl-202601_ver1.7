<?php
/**
 * 未来ストーリー（Future Story）API エンドポイント
 * 
 * 目標達成ジャーニーを時間軸で管理し、
 * 過去→現在→未来のストーリー進行を可視化します。
 */

$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';

// CORS設定
Cors::setHeaders();

// ユーザーを認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
}

$db = Database::getInstance();

// リクエストメソッド別の処理
$method = $_SERVER['REQUEST_METHOD'];

// クエリパラメータで処理を分岐
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($method === 'GET') {
    if ($action === 'room_goals') {
        // ルームの目標一覧を取得
        handleGetRoomGoals($db, $userId);
    } else {
        // ストーリー一覧を取得
        handleGetStories($db, $userId);
    }
} elseif ($method === 'POST') {
    // 新規ストーリーを作成
    handleCreateStory($db, $userId);
} elseif ($method === 'PUT') {
    // ストーリーを更新
    handleUpdateStory($db, $userId);
} elseif ($method === 'DELETE') {
    // ストーリーを削除
    handleDeleteStory($db, $userId);
} else {
    ApiResponse::error('Method not allowed', 405);
}

/**
 * ルーム目標一覧を取得（未来Story画面で選択用）
 * クエリパラメータ:
 * - action: 'room_goals'（必須）
 */
function handleGetRoomGoals($db, $userId) {
    try {
        // ユーザーのすべてのルームの目標を取得
        $query = "SELECT 
                    gn.id,
                    gn.room_id,
                    r.name as room_name,
                    gn.note_text,
                    gn.created_at
                  FROM goal_notes gn
                  INNER JOIN rooms r ON gn.room_id = r.id
                  WHERE gn.user_id = ? AND gn.story_date IS NULL
                  ORDER BY gn.created_at DESC";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        // bind_result と fetch を使用（mysqlnd不要）
        $stmt->bind_result($id, $room_id, $room_name, $note_text, $created_at);
        
        $goals = [];
        while ($stmt->fetch()) {
            $goals[] = [
                'id' => $id,
                'room_id' => $room_id,
                'room_name' => $room_name,
                'note_text' => $note_text,
                'created_at' => $created_at
            ];
        }
        $stmt->close();
        
        ApiResponse::success($goals, 'Room goals retrieved successfully', 200);
    } catch (Exception $e) {
        error_log('Error fetching room goals: ' . $e->getMessage());
        ApiResponse::error('Failed to retrieve room goals', 500);
    }
}

/**
 * ストーリー一覧を取得
 * クエリパラメータ:
 * - roomId: ルームID（指定時）
 * - storyType: 'past'|'future'（指定時）
 */
function handleGetStories($db, $userId) {
    $roomId = isset($_GET['roomId']) ? intval($_GET['roomId']) : null;
    $storyType = isset($_GET['storyType']) ? $_GET['storyType'] : null;
    
    try {
        $conditions = ["gn.user_id = ?"];
        $types = 'i';
        $params = [$userId];
        
        if ($roomId) {
            $conditions[] = "gn.room_id = ?";
            $types .= 'i';
            $params[] = $roomId;
        }
        
        if ($storyType === 'future') {
            $conditions[] = "gn.story_date >= CURDATE()";
        } elseif ($storyType === 'past') {
            $conditions[] = "gn.story_date < CURDATE() AND gn.story_date IS NOT NULL";
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $query = "SELECT 
                    gn.id,
                    gn.room_id,
                    gn.note_text,
                    gn.story_date,
                    gn.image_comment,
                    gn.story_image,
                    gn.created_at,
                    r.name as room_name
                  FROM goal_notes gn
                  LEFT JOIN rooms r ON gn.room_id = r.id
                  $whereClause
                  ORDER BY 
                    CASE 
                        WHEN gn.story_date IS NULL THEN 0
                        WHEN gn.story_date < CURDATE() THEN 1
                        WHEN gn.story_date = CURDATE() THEN 2
                        ELSE 3
                    END,
                    gn.story_date ASC,
                    gn.created_at DESC";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $stmt->bind_result($id, $room_id, $note_text, $story_date, $image_comment, $story_image, $created_at, $room_name);
        
        $stories = [];
        while ($stmt->fetch()) {
            $stories[] = [
                'id' => $id,
                'room_id' => $room_id,
                'note_text' => $note_text,
                'story_date' => $story_date,
                'image_comment' => $image_comment,
                'story_image' => $story_image,
                'created_at' => $created_at,
                'room_name' => $room_name
            ];
        }
        $stmt->close();
        
        ApiResponse::success($stories, 'Stories retrieved successfully', 200);
    } catch (Exception $e) {
        error_log('Error fetching stories: ' . $e->getMessage());
        ApiResponse::error('Failed to retrieve stories', 500);
    }
}

/**
 * 新規ストーリーを作成
 * リクエストボディ:
 * - roomId: ルームID（nullの場合は新規作成）
 * - note_text: ストーリーテキスト
 * - story_date: ストーリーの日付（YYYY-MM-DD）
 * - story_image: 生成した画像（Base64）
 * - image_comment: 画像補足コメント
 */
function handleCreateStory($db, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['note_text'])) {
        ApiResponse::error('note_text is required', 400);
    }
    
    $roomId = isset($input['roomId']) ? intval($input['roomId']) : null;
    $noteText = trim($input['note_text']);
    $storyDate = isset($input['story_date']) ? $input['story_date'] : null;
    $storyImage = isset($input['story_image']) ? $input['story_image'] : null;
    $imageComment = isset($input['image_comment']) ? trim($input['image_comment']) : null;
    
    if (empty($noteText)) {
        ApiResponse::error('note_text cannot be empty', 400);
    }
    
    try {
        // roomIdがnullの場合は、デフォルトルームを作成または使用
        if ($roomId === null) {
            // "未来Story" という名前のデフォルトルームを取得または作成
            $defaultRoomQuery = "SELECT id FROM rooms WHERE user_id = ? AND name = '未来Story' LIMIT 1";
            $stmt = $db->prepare($defaultRoomQuery);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->bind_result($existingRoomId);
            if ($stmt->fetch()) {
                $roomId = intval($existingRoomId);
            }
            $stmt->close();

            if ($roomId === null) {
                // デフォルトルームを作成
                $createRoomQuery = "INSERT INTO rooms (user_id, name, created_at) VALUES (?, '未来Story', NOW())";
                $stmt = $db->prepare($createRoomQuery);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $roomId = $db->lastInsertId();
                $stmt->close();
            }
        } else {
            // ルームへのアクセス権限を確認
            $roomCheck = $db->prepare("SELECT id FROM rooms WHERE id = ? AND user_id = ?");
            $roomCheck->bind_param('ii', $roomId, $userId);
            $roomCheck->execute();
            $roomCheck->store_result();
            if ($roomCheck->num_rows === 0) {
                $roomCheck->close();
                ApiResponse::forbidden('You do not have access to this room');
            }
            $roomCheck->close();
        }
        
        // ストーリーを作成
        $query = "INSERT INTO goal_notes 
                  (user_id, room_id, note_text, story_date, image_comment, story_image, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare insert');
        }
        $stmt->bind_param('iissss', $userId, $roomId, $noteText, $storyDate, $imageComment, $storyImage);
        $stmt->execute();
        
        $storyId = $db->lastInsertId();

        ApiResponse::success(
            ['id' => $storyId, 'created_at' => date('Y-m-d H:i:s')],
            'Story created successfully',
            201
        );
    } catch (Exception $e) {
        error_log('Error creating story: ' . $e->getMessage());
        ApiResponse::error('Failed to create story', 500);
    }
}

/**
 * ストーリーを更新
 * リクエストボディ:
 * - storyId: ストーリーID
 * - note_text: 更新後のテキスト
 * - story_date: 更新後の日付
 * - story_image: 生成画像（Base64）
 */
function handleUpdateStory($db, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['storyId'])) {
        ApiResponse::error('storyId is required', 400);
    }
    
    $storyId = intval($input['storyId']);
    $noteText = isset($input['note_text']) ? trim($input['note_text']) : null;
    $storyDate = isset($input['story_date']) ? $input['story_date'] : null;
    $imageComment = isset($input['image_comment']) ? trim($input['image_comment']) : null;
    $storyImage = isset($input['story_image']) ? $input['story_image'] : null;
    
    try {
        // ストーリーの所有権を確認
        $storyCheck = $db->prepare("SELECT id FROM goal_notes WHERE id = ? AND user_id = ?");
        $storyCheck->execute([$storyId, $userId]);
        $result = $storyCheck->getResult();
        if (!$result->fetch_assoc()) {
            ApiResponse::forbidden('You do not have access to this story');
        }
        
        // 更新クエリを作成
        $updates = [];
        $params = [];
        
        if ($noteText !== null) {
            $updates[] = "note_text = ?";
            $params[] = $noteText;
        }
        if ($storyDate !== null) {
            $updates[] = "story_date = ?";
            $params[] = $storyDate;
        }
        if ($imageComment !== null) {
            $updates[] = "image_comment = ?";
            $params[] = $imageComment;
        }
        if ($storyImage !== null) {
            $updates[] = "story_image = ?";
            $params[] = $storyImage;
        }
        
        if (empty($updates)) {
            ApiResponse::error('Nothing to update', 400);
        }
        
        $params[] = $storyId;
        $params[] = $userId;
        
        $query = "UPDATE goal_notes SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare update');
        }
        $types = str_repeat('s', count($updates)) . 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        ApiResponse::success([], 'Story updated successfully', 200);
    } catch (Exception $e) {
        error_log('Error updating story: ' . $e->getMessage());
        ApiResponse::error('Failed to update story', 500);
    }
}

/**
 * ストーリーを削除
 * クエリパラメータ:
 * - storyId: ストーリーID
 */
function handleDeleteStory($db, $userId) {
    $storyId = isset($_GET['storyId']) ? intval($_GET['storyId']) : null;
    
    if (!$storyId) {
        ApiResponse::error('storyId is required', 400);
    }
    
    try {
        // ストーリーの所有権を確認
        $storyCheck = $db->prepare("SELECT id FROM goal_notes WHERE id = ? AND user_id = ?");
        $storyCheck->execute([$storyId, $userId]);
        if (!$storyCheck->fetch()) {
            ApiResponse::forbidden('You do not have access to this story');
        }
        
        // ストーリーを削除
        $query = "DELETE FROM goal_notes WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$storyId, $userId]);
        
        ApiResponse::success([], 'Story deleted successfully', 200);
    } catch (Exception $e) {
        error_log('Error deleting story: ' . $e->getMessage());
        ApiResponse::error('Failed to delete story', 500);
    }
}
?>
