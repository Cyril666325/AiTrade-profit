<?php
require_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

define('DBSERVER',   getenv('DB_SERVER')   ?: 'localhost');
define('DBUSERNAME', getenv('DB_USERNAME') ?: 'root');
define('DBPASSWORD', getenv('DB_PASSWORD') ?: '');
define('DBNAME',     getenv('DB_NAME')     ?: 'aitrading_db');

// Mail constants (used by functions.php)
define('MAIL_HOST',     getenv('MAIL_HOST')     ?: '');
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_PORT',     (int)(getenv('MAIL_PORT') ?: 465));
define('MAIL_SECURE',   getenv('MAIL_SECURE')   ?: 'ssl');

$conn = $link = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD, DBNAME);

if (!$conn) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed.']));
}

mysqli_set_charset($conn, 'utf8mb4');
