<?php
/**
 * Stalker 画像管理 API エンドポイント
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

// stalker 画像をアップロード（POST）
if ($method === 'POST') {
    if (!isset($_FILES['stalkerImage'])) {
        ApiResponse::error('stalkerImage file is required', 400);
    }

    $file = $_FILES['stalkerImage'];

    // ファイルサイズチェック（5MB以下）
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        ApiResponse::error('File size must be less than 5MB', 400);
    }

    // MIME タイプチェック
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        ApiResponse::error('Only image files are allowed', 400);
    }

    try {
        // 画像をBase64エンコード
        $imageData = file_get_contents($file['tmp_name']);
        $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

        $userModel = new User();
        
        // DB に保存
        if (!$userModel->setStalkerImage($userId, $base64Image)) {
            ApiResponse::error('Failed to save stalker image', 500);
        }

        ApiResponse::success([
            'stalker_image' => $base64Image
        ], 'Stalker image uploaded successfully', 200);

    } catch (Exception $e) {
        error_log('Stalker upload error: ' . $e->getMessage());
        ApiResponse::error('Internal server error', 500);
    }
    exit;
}

// stalker 画像を削除（DELETE）
if ($method === 'DELETE') {
    try {
        $userModel = new User();
        
        if (!$userModel->deleteStalkerImage($userId)) {
            ApiResponse::error('Failed to delete stalker image', 500);
        }

        ApiResponse::success(null, 'Stalker image deleted successfully', 200);

    } catch (Exception $e) {
        error_log('Stalker delete error: ' . $e->getMessage());
        ApiResponse::error('Internal server error', 500);
    }
    exit;
}

ApiResponse::error('Method not allowed', 405);
?>
