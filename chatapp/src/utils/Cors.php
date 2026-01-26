<?php
/**
 * CORS設定
 */

class Cors {
    /**
     * CORSヘッダーを設定
     */
    public static function setHeaders() {
        // 開発環境・本番環境用のCORS設定（HTTP/HTTPS両対応）
        $allowedOrigins = [
            'http://localhost:5173',
            'https://localhost:5173',
            'https://silvercow67.sakura.ne.jp',
            'https://silvercow67.sakura.ne.jp/chatapp-react'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // フォールバック（本番環境の場合）
            header('Access-Control-Allow-Origin: https://silvercow67.sakura.ne.jp');
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        
        // OPTIONSリクエスト（プリフライト）への対応
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
