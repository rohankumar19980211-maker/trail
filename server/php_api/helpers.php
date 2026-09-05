<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Native MySQL PDO Database Connection
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'livetea113398_boxretail';
$user = getenv('DB_USER') ?: 'livetea113398_boxuser';
$pass = getenv('DB_PASS') ?: 'KUM^ar@1122';
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

// 2. JSON Fallback Storage
$DB_DIR = __DIR__ . '/../data';
if (!file_exists($DB_DIR)) {
    @mkdir($DB_DIR, 0777, true);
}

function get_collection($name) {
    global $pdo, $use_mysql, $DB_DIR;
    
    // Read from MySQL if connected
    if ($use_mysql && $pdo) {
        try {
            if ($name === 'users') {
                $stmt = $pdo->query("SELECT id AS _id, id, username, name, role, password_hash, password_hash AS password, created_at AS createdAt FROM users ORDER BY id ASC");
                return $stmt->fetchAll();
            } elseif ($name === 'products') {
                $stmt = $pdo->query("SELECT id AS _id, id, sku, title, box_size AS boxSize, category, size_category AS sizeCategory, length, width, height, description, price_inr AS unitPrice, stock_qty AS availableQuantity, image_url AS image, discount_tiers_json FROM products ORDER BY id ASC");
                $rows = $stmt->fetchAll();
                return array_map(function($r) {
                    $r['discountTiers'] = json_decode($r['discount_tiers_json'] ?? '[]', true) ?: [];
                    return $r;
                }, $rows);
            }
        } catch (\Exception $e) {
            // fallback to JSON if table error
        }
    }

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

function save_collection($name, $data) {
    global $pdo, $use_mysql, $DB_DIR;
    
    // Also mirror into JSON for fallback backup
    if (!file_exists($DB_DIR)) {
        @mkdir($DB_DIR, 0777, true);
    }
    $filePath = $DB_DIR . '/' . $name . '.json';
    @file_put_contents($filePath, json_encode(array_values($data), JSON_PRETTY_PRINT), LOCK_EX);
    @chmod($filePath, 0666);
    return true;
}

function get_json_input() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// 3. Universal HMAC SHA-256 Hash Generator & Verifier
function box_hash_password($password, $salt = null) {
    if (!$salt) {
        $salt = bin2hex(random_bytes(16));
    }
    $hash = hash_hmac('sha256', $password, $salt);
    return "sha256$" . $salt . "$" . $hash;
}

function box_verify_password($password, $hash) {
    if (!$hash) return false;
    
    $parts = explode('$', $hash);
    
    // Universal HMAC SHA-256 format: sha256$<salt>$<hash>
    if (count($parts) === 3 && $parts[0] === 'sha256') {
        $salt = $parts[1];
        $computed = hash_hmac('sha256', $password, $salt);
        if (hash_equals($parts[2], $computed)) return true;
        // Check standard hash with salt
        if (hash_equals($parts[2], hash('sha256', $password . $salt))) return true;
    }
    
    // Legacy Salted SHA-256 format: sha256$<hash>
    if (count($parts) === 2 && $parts[0] === 'sha256') {
        $legacy = 'sha256$' . hash('sha256', $password . 'box_salt_2026');
        if (hash_equals($hash, $legacy)) return true;
    }
    
    // Bcrypt verifications
    if (password_verify($password, $hash)) return true;
    if (password_verify($password, str_replace('$2a$', '$2y$', $hash))) return true;
    if (password_verify($password, str_replace('$2y$', '$2a$', $hash))) return true;
    
    // Fallback direct plaintext match for initial setup
    if ($hash === (string)$password) return true;
    
    return false;
}

// 4. JWT Helpers
if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'SuperSecretEnterpriseKey_BoxRetail_2026#');

function get_auth_user() {
    $auth = '';
    
    if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && $_SERVER['REDIRECT_HTTP_AUTHORIZATION']) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization']) && $headers['Authorization']) {
            $auth = $headers['Authorization'];
        } elseif (isset($headers['authorization']) && $headers['authorization']) {
            $auth = $headers['authorization'];
        }
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization']) && $headers['Authorization']) {
            $auth = $headers['Authorization'];
        } elseif (isset($headers['authorization']) && $headers['authorization']) {
            $auth = $headers['authorization'];
        }
    }

    if (!$auth || strpos($auth, 'Bearer ') !== 0) {
        return null;
    }

    $token = trim(substr($auth, 7));
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    // Reject any token we did not sign, and any token past its exp.
    $expected = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(
        hash_hmac('sha256', $parts[0] . '.' . $parts[1], JWT_SECRET, true)
    ));
    if (!hash_equals($expected, $parts[2])) return null;

    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    if (!is_array($payload)) return null;
    if (!isset($payload['exp']) || time() >= $payload['exp']) return null;

    return $payload;
}

function generate_token($user) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'id' => isset($user['id']) ? $user['id'] : (isset($user['_id']) ? $user['_id'] : 1),
        'username' => $user['username'],
        'name' => $user['name'] ?? $user['username'],
        'role' => isset($user['role']) ? $user['role'] : 'employee',
        'exp' => time() + 86400
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
