<?php
/**
 * PathHelper - Reliable Path Resolution
 * 相対パスの解決を堅牢にするヘルパークラス
 */

class PathHelper
{
    // バックエンド（非公開）ディレクトリのベースパス
    private static ?string $backendBasePath = null;

    /**
     * バックエンドベースパスを取得
     * @return string
     */
    public static function getBackendBasePath(): string
    {
        if (self::$backendBasePath === null) {
            // init_db.php や config.php から見ると __DIR__ は chatapp/
            // API ファイルから見ると __DIR__ は chatapp/src/api/
            // config.php が呼ばれた時点では定義される
            self::$backendBasePath = dirname(dirname(dirname(__FILE__)));
        }
        return self::$backendBasePath;
    }

    /**
     * バックエンド内の相対パスを解決
     * @param string $relativePath 相対パス (例: 'src/utils/Database.php')
     * @return string 絶対パス
     */
    public static function resolve(string $relativePath): string
    {
        return self::getBackendBasePath() . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
    }

    /**
     * ファイルが存在するかチェック
     * @param string $path パス
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * ファイルを require_once
     * @param string $relativePath 相対パス
     * @return void
     */
    public static function require(string $relativePath): void
    {
        $path = self::resolve($relativePath);
        if (!self::exists($path)) {
            throw new Exception("Required file not found: {$path}");
        }
        require_once $path;
    }

    /**
     * Frontend（公開）ディレクトリへのベースURL取得
     * @return string
     */
    public static function getFrontendBaseUrl(): string
    {
        return EnvConfig::get('FRONTEND_BASE_URL', 'http://localhost:5173');
    }

    /**
     * API Base URL取得
     * @return string
     */
    public static function getApiBaseUrl(): string
    {
        return EnvConfig::get('API_URL', 'http://localhost/chatapp/src/api');
    }
}
