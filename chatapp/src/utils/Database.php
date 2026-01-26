<?php
/**
 * Database接続クラス
 */

class Database {
    private $connection;
    private static $instance;

    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed: ' . $this->connection->connect_error);
            }
            
            $this->connection->set_charset('utf8mb4');
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('データベースに接続できません');
        }
    }

    /**
     * シングルトンインスタンスを取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 接続を取得
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * クエリ実行
     */
    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result && $this->connection->error) {
            error_log('Query error: ' . $this->connection->error . ' - SQL: ' . $sql);
            throw new Exception('Query error: ' . $this->connection->error);
        }
        return $result;
    }

    /**
     * プリペアドステートメントを準備
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    /**
     * 最後に挿入されたIDを取得
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * トランザクション開始
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    /**
     * コミット
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * ロールバック
     */
    public function rollback() {
        $this->connection->rollback();
    }

    /**
     * エスケープ文字列
     */
    public function escape($str) {
        return $this->connection->real_escape_string($str);
    }

    private function __clone() {}
}
?>
