<?php
/**
 * アプリケーション設定ファイル
 */

// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッション設定（既に開始されている場合は変更しない）
if (session_status() === PHP_SESSION_NONE) {
    // HTTPS環境でのセッションクッキーを安全に設定
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('chatapp_session');
    session_start();
}

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 環境設定を読み込む（EnvConfig）
require_once __DIR__ . '/../src/utils/EnvConfig.php';
require_once __DIR__ . '/../src/utils/PathHelper.php';

// .env ファイルを読み込む
try {
    EnvConfig::load();
} catch (Exception $e) {
    // .env が見つからない場合はエラーを記録
    error_log('Warning: .env file not found: ' . $e->getMessage());
}

// データベース設定
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'chatapp');

// OpenAI API キー
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');

// Google Gemini API キー
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

// セッションシークレット
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'default_secret_key');

// API基本URL
define('API_BASE_URL', '/api/');

// キャッシュ設定
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);
