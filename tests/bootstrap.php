<?php
/**
 * PHPUnit Bootstrap
 * Sets up environment for testing without starting sessions or output.
 */

// Prevent session_start() issues in testing
if (session_status() === PHP_SESSION_NONE) {
    // Suppress session start in tests
    define('TESTING', true);
}

// Override session functions for testing
$_SESSION = [];

// Set test environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';

// Define constants that config.php would define
function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

define('APP_NAME', 'HAZINA ASILI');
define('APP_FULL_NAME', 'Mfumo wa Hifadhidata ya Misombo ya Asili ya Kikaboni');
define('APP_VERSION', '3.0.0');
define('BASE_URL', 'http://localhost/DB/project/');
define('APP_ENV', 'testing');
define('APP_DEBUG', true);
define('SESSION_LIFETIME', 3600);
define('RECORDS_PER_PAGE', 15);
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ROLE_ADMIN', 'admin');
define('ROLE_RESEARCHER', 'researcher');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('FORCE_HTTPS', false);
define('MAIL_ENABLED', false);

// Load helpers
require_once __DIR__ . '/../helpers/functions.php';

// Load database (use test DB)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', env('DB_NAME', 'natural_compounds_db_test'));
define('DB_CHARSET', 'utf8mb4');
require_once __DIR__ . '/../config/database.php';
