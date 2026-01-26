<?php
/**
 * 登録 API エンドポイント
 */

require_once '../../config/config.php';
require_once '../../src/utils/Database.php';
require_once '../../src/utils/Auth.php';
require_once '../../src/utils/ApiResponse.php';
require_once '../../src/utils/Cors.php';
require_once '../../src/models/User.php';

// パスワードの追加バリデーション
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

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 405);
}

// リクエストボディを取得
$input = json_decode(file_get_contents('php://input'), true);

// バリデーション
if (!isset($input['email']) || !isset($input['password'])) {
    ApiResponse::error('Email and password are required', 400);
}

$email = trim($input['email']);
$password = trim($input['password']);
$name = trim($input['name'] ?? '');

// メールアドレスのバリデーション
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ApiResponse::error('Invalid email address', 400);
}

// パスワード強度チェック
if (strlen($password) < 8) {
    ApiResponse::error('Password must be at least 8 characters', 400);
}

if (!preg_match('/[a-z]/', $password)) {
    ApiResponse::error('Password must contain at least one lowercase letter', 400);
}

if (!preg_match('/[A-Z]/', $password)) {
    ApiResponse::error('Password must contain at least one uppercase letter', 400);
}

if (!preg_match('/[0-9]/', $password)) {
    ApiResponse::error('Password must contain at least one number', 400);
}

// 連続同一文字（3文字以上）は禁止
if (hasRepeatedChars($password, 3)) {
    ApiResponse::error('Password cannot contain the same character repeated 3 or more times in a row', 400);
}

// 数字の昇順・降順が3桁以上続く場合は禁止
if (hasSequentialNumbers($password, 3)) {
    ApiResponse::error('Password cannot contain 3 or more consecutive numbers in ascending or descending order', 400);
}

// キーボード横並び（qwerty, asdf, zxc など）が3文字以上続く場合は禁止
if (hasKeyboardSequence($password, 3)) {
    ApiResponse::error('Password cannot contain 3 or more consecutive keyboard-adjacent characters', 400);
}

// パスワードがメールアドレスと同じ、または含まれているかチェック
$emailParts = explode('@', strtolower($email));
$passwordLower = strtolower($password);
if ($passwordLower === strtolower($email) || strpos($passwordLower, $emailParts[0]) !== false) {
    ApiResponse::error('Password cannot be the same as or contain your email address', 400);
}

try {
    $userModel = new User();
    
    // メールアドレスが既に存在するか確認
    if ($userModel->emailExists($email)) {
        ApiResponse::error('Email already exists', 400);
    }
    
    // ユーザーを作成
    $userId = $userModel->create($email, $password, $name);
    
    if (!$userId) {
        ApiResponse::error('Failed to create user', 500);
    }

    // 新規登録後に初回ログインポイントを付与（同日の重複付与はaddLoginPoints側で防止）
    $userModel->addLoginPoints($userId, 10);
    
    // トークンを生成
    $token = Auth::generateToken($userId);
    
    // ユーザー情報を取得（ポイント込み）
    $user = $userModel->findById($userId);
    
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'is_admin' => isset($user['is_admin']) ? (bool)$user['is_admin'] : false,
            'points' => isset($user['points']) ? intval($user['points']) : 0,
            'last_login_date' => $user['last_login_date'] ?? null
        ],
        'token' => $token
    ], 'User registered successfully', 201);
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}
?>
