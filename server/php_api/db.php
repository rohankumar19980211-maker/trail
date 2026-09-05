<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'livetea113398_boxretail';
$user = getenv('DB_USER') ?: 'livetea113398_boxuser';
$pass = getenv('DB_PASS') ?: 'BoxStorePass2026!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = null;
$use_mysql = false;
$db_error = null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $use_mysql = true;
} catch (\PDOException $e) {
    $use_mysql = false;
    $db_error = $e->getMessage();
}

// Fallback JSON DB directory helper
$DB_DIR = __DIR__ . '/../data';
if (!file_exists($DB_DIR)) {
    @mkdir($DB_DIR, 0777, true);
}

function get_json_collection($name) {
    global $DB_DIR;
    $filePath = $DB_DIR . '/' . $name . '.json';
    if (!file_exists($filePath)) {
        @file_put_contents($filePath, json_encode([], JSON_PRETTY_PRINT));
        @chmod($filePath, 0666);
        return [];
    }
    $content = @file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function save_json_collection($name, $data) {
    global $DB_DIR;
    if (!file_exists($DB_DIR)) {
        @mkdir($DB_DIR, 0777, true);
    }
    $filePath = $DB_DIR . '/' . $name . '.json';
    $json = json_encode(array_values($data), JSON_PRETTY_PRINT);
    $res = @file_put_contents($filePath, $json, LOCK_EX);
    @chmod($filePath, 0666);
    return $res !== false;
}

function get_json_input() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// Auto Diagnostic Check if db.php is accessed directly in browser / API call
if (empty($suppress_db_check)) {
    if ($use_mysql) {
        echo json_encode([
            'status' => 'connected',
            'database' => $db,
            'user' => $user,
            'message' => 'Successfully connected to MySQL database!'
        ]);
    } else {
        echo json_encode([
            'status' => 'fallback_json',
            'database' => $db,
            'user' => $user,
            'error' => $db_error,
            'message' => 'MySQL connection failed. Running in JSON fallback mode. Update DB_NAME, DB_USER, DB_PASS in api/db.php to match cPanel MySQL credentials.'
        ]);
    }
    exit();
}



