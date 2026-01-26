<?php
/**
 * 認証ユーティリティクラス
 */

class Auth {
    /**
     * パスワードをハッシュ化
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * パスワードを検証
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * JWT トークンを生成
     */
    public static function generateToken($userId) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24時間有効
            'userId' => $userId
        ]);
        
        $base64Header = self::base64urlEncode($header);
        $base64Payload = self::base64urlEncode($payload);
        $signature = self::base64urlEncode(
            hash_hmac('sha256', $base64Header . '.' . $base64Payload, SESSION_SECRET, true)
        );
        
        return $base64Header . '.' . $base64Payload . '.' . $signature;
    }

    /**
     * JWT トークンを検証
     */
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // シグネチャを検証
        $expectedSignature = self::base64urlEncode(
            hash_hmac('sha256', $header . '.' . $payload, SESSION_SECRET, true)
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        // ペイロードをデコード
        $decodedPayload = json_decode(self::base64urlDecode($payload), true);
        
        if (!$decodedPayload) {
            return false;
        }
        
        // 有効期限を確認
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }
        
        return $decodedPayload;
    }

    /**
     * Base64 URL エンコード
     */
    private static function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL デコード
     */
    private static function base64urlDecode($data) {
        $data = strtr($data, '-_', '+/');
        return base64_decode($data . str_repeat('=', 4 - strlen($data) % 4));
    }

    /**
     * リクエストからトークンを取得
     */
    public static function getTokenFromRequest() {
        // getallheaders() の代替実装（nginx/さくら対応）
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * 現在のユーザーIDを取得
     */
    public static function getCurrentUserId() {
        $token = self::getTokenFromRequest();
        
        if (!$token) {
            return null;
        }
        
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return null;
        }
        
        return $payload['userId'] ?? null;
    }

    /**
     * ユーザーがログインしているか確認
     */
    public static function isLoggedIn() {
        return self::getCurrentUserId() !== null;
    }
}
?>
