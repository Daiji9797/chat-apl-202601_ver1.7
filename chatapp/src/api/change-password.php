<?php
/**
 * パスワード変更 API エンドポイント
 */

// XAMPP/開発環境両対応：パス解決
$backendBasePath = dirname(dirname(dirname(__FILE__)));

require_once $backendBasePath . '/config/config.php';
require_once $backendBasePath . '/src/utils/Database.php';
require_once $backendBasePath . '/src/utils/Auth.php';
require_once $backendBasePath . '/src/utils/ApiResponse.php';
require_once $backendBasePath . '/src/utils/Cors.php';
require_once $backendBasePath . '/src/models/User.php';

// パスワードの追加バリデーション（新規登録と同一基準）
function hasSequentialNumbers($password, $minLen = 3) {
    $len = strlen($password);
    $run = 1;
    for ($i = 1; $i < $len; $i++) {
        if (ctype_digit($password[$i - 1]) && ctype_digit($password[$i])) {
            $diff = intval($password[$i]) - intval($password[$i - 1]);
            if ($diff === 1 || $diff === -1) {
                $run++;
                if ($run >= $minLen) {
                    return true;
                }
                continue;
            }
        }
        $run = 1;
    }
    return false;
}

function hasRepeatedChars($password, $minLen = 3) {
    $len = strlen($password);
    $run = 1;
    for ($i = 1; $i < $len; $i++) {
        if (strcasecmp($password[$i], $password[$i - 1]) === 0) {
            $run++;
            if ($run >= $minLen) {
                return true;
            }
        } else {
            $run = 1;
        }
    }
    return false;
}

function hasKeyboardSequence($password, $minLen = 3) {
    $rows = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1234567890'];
    $lower = strtolower($password);
    foreach ($rows as $row) {
        $rev = strrev($row);
        $rowLen = strlen($row);
        for ($i = 0; $i <= $rowLen - $minLen; $i++) {
            $chunk = substr($row, $i, $minLen);
            if (strpos($lower, $chunk) !== false || strpos($lower, substr($rev, $i, $minLen)) !== false) {
                return true;
            }
        }
    }
    return false;
}

// CORS設定
Cors::setHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
    exit;
}

// ユーザー認証
$userId = Auth::getCurrentUserId();
if (!$userId) {
    ApiResponse::unauthorized('Authentication required');
    exit;
}

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

// 入力値チェック
$currentPassword = $inputData['current_password'] ?? '';
$newPassword = $inputData['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    ApiResponse::error('Current password and new password are required', 400);
    exit;
}

// パスワード強度チェック（新規登録と同じ基準）
if (strlen($newPassword) < 8) {
    ApiResponse::error('Password must be at least 8 characters', 400);
    exit;
}

if (!preg_match('/[a-z]/', $newPassword)) {
    ApiResponse::error('Password must contain at least one lowercase letter', 400);
    exit;
}

if (!preg_match('/[A-Z]/', $newPassword)) {
    ApiResponse::error('Password must contain at least one uppercase letter', 400);
    exit;
}

if (!preg_match('/[0-9]/', $newPassword)) {
    ApiResponse::error('Password must contain at least one number', 400);
    exit;
}

// 連続同一文字（3文字以上）は禁止
if (hasRepeatedChars($newPassword, 3)) {
    ApiResponse::error('Password cannot contain the same character repeated 3 or more times in a row', 400);
    exit;
}

// 数字の昇順・降順が3桁以上続く場合は禁止
if (hasSequentialNumbers($newPassword, 3)) {
    ApiResponse::error('Password cannot contain 3 or more consecutive numbers in ascending or descending order', 400);
    exit;
}

// キーボード横並び（qwerty, asdf, zxc など）が3文字以上続く場合は禁止
if (hasKeyboardSequence($newPassword, 3)) {
    ApiResponse::error('Password cannot contain 3 or more consecutive keyboard-adjacent characters', 400);
    exit;
}

try {
    $userModel = new User();
    $user = $userModel->findById($userId);
    
    if (!$user) {
        ApiResponse::notFound('User not found');
        exit;
    }

    // 現在のパスワード確認
    if (!Auth::verifyPassword($currentPassword, $user['password'])) {
        ApiResponse::error('Current password is incorrect', 401);
        exit;
    }

    // 新しいパスワードと現在のパスワードが同じでないか確認
    if ($currentPassword === $newPassword) {
        ApiResponse::error('New password must be different from current password', 400);
        exit;
    }

    // データベース接続取得
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // パスワードをハッシュ化
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // パスワード更新
    $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        ApiResponse::error('Database error', 500);
        exit;
    }

    $stmt->bind_param('si', $hashedPassword, $userId);
    $result = $stmt->execute();

    if (!$result) {
        error_log('Execute failed: ' . $stmt->error);
        ApiResponse::error('Failed to update password', 500);
        exit;
    }

    ApiResponse::success(['message' => 'Password changed successfully'], 'Password changed successfully', 200);
    exit;

} catch (Exception $e) {
    error_log('Change password error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
    exit;
}

