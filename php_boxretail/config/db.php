<?php
// config/db.php - Native PDO MySQL Singleton Connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'livetea113398_boxretail';
$db_user = getenv('DB_USER') ?: 'livetea113398_boxuser';
$db_pass = getenv('DB_PASS') ?: 'KUM^ar@1122';
$db_port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$db_connected = false;
$db_error_msg = null;

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $db_connected = true;
} catch (\PDOException $e) {
    $db_connected = false;
    $db_error_msg = $e->getMessage();
}

function get_db_connection() {
    global $pdo;
    return $pdo;
}
