<?php
/**
 * Database Configuration — uses .env values with fallback defaults.
 * Singleton pattern for connection reuse.
 */

if (!defined('DB_HOST'))    define('DB_HOST',    env('DB_HOST', 'localhost'));
if (!defined('DB_PORT'))    define('DB_PORT',    (int)env('DB_PORT', 3306));
if (!defined('DB_USER'))    define('DB_USER',    env('DB_USER', 'root'));
if (!defined('DB_PASS'))    define('DB_PASS',    env('DB_PASS', ''));
if (!defined('DB_NAME'))    define('DB_NAME',    env('DB_NAME', 'natural_compounds_db'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
if (!defined('DB_SSL'))     define('DB_SSL',     filter_var(env('DB_SSL', false), FILTER_VALIDATE_BOOLEAN));

class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct() {
        // Include port in DSN — required for TiDB Serverless (port 4000)
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Enable SSL for cloud databases (TiDB Serverless requires SSL)
        if (DB_SSL) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            // TiDB uses server-side SSL — no client cert needed
            $options[PDO::MYSQL_ATTR_SSL_CA]                 = '';
        }

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:2rem;color:#842029;background:#f8d7da;border-radius:8px;margin:2rem"><h2>Database Error</h2><p>Could not connect to the database. Please check your configuration.</p></div>');
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    private function __clone() {}
}
