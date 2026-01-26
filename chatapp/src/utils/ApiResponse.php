<?php
/**
 * API レスポンスハンドラー
 */

class ApiResponse {
    /**
     * JSON レスポンスを送信
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * 成功レスポンスを送信
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * エラーレスポンスを送信
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = null) {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * 未認証レスポンスを送信
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * 権限なしレスポンスを送信
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }

    /**
     * 見つからないレスポンスを送信
     */
    public static function notFound($message = 'Not Found') {
        self::error($message, 404);
    }
}
?>
