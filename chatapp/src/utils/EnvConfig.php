<?php
/**
 * Environment Configuration Parser
 * .env ファイルを読み込んで環境変数を設定
 */

class EnvConfig {
    private static $config = [];
    private static $loaded = false;

    /**
     * .env ファイルを読み込んで設定を初期化
     */
    public static function load($envFilePath = null) {
        if (self::$loaded) {
            return;
        }

        // .env ファイルのパスを決定
        if (!$envFilePath) {
            $possiblePaths = [
                __DIR__ . '/../../.env',
                __DIR__ . '/../../.env.local',
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $envFilePath = $path;
                    break;
                }
            }
        }

        if (!$envFilePath || !file_exists($envFilePath)) {
            throw new Exception(".env file not found at: " . ($envFilePath ?: 'unknown location'));
        }

        // ファイルを読み込んで解析
        $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // コメント行をスキップ
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // キー=値の形式で解析
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 前後のクォートを削除
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                self::$config[$key] = $value;
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * 環境変数を取得
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? getenv($key) ?: $default;
    }

    /**
     * すべての設定を取得
     */
    public static function all() {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * 設定を確認
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$config[$key]) || getenv($key) !== false;
    }
}
