<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$DB_DIR = __DIR__ . '/../data';
if (!file_exists($DB_DIR)) {
    @mkdir($DB_DIR, 0777, true);
}

function get_collection($name) {
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

function save_collection($name, $data) {
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

function box_hash_password($password) {
    return 'sha256$' . hash('sha256', $password . 'box_salt_2026');
}

function box_verify_password($password, $hash) {
    if (!$hash) return false;
    
    // 1. Universal Salted SHA256 Match (100% reliable across all shared hosting PHP builds)
    if (strpos($hash, 'sha256$') === 0) {
        return $hash === ('sha256$' . hash('sha256', $password . 'box_salt_2026'));
    }
    
    // 2. Bcrypt checks for legacy/node generated hashes
    if (password_verify($password, $hash)) return true;
    if (password_verify($password, str_replace('$2a$', '$2y$', $hash))) return true;
    if (password_verify($password, str_replace('$2y$', '$2a$', $hash))) return true;
    
    // 3. Fallback direct match for initial seed default passwords
    if ($hash === (string)$password) return true;
    
    return false;
}

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
    if (count($parts) === 3) {
        $payloadRaw = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        $payload = json_decode($payloadRaw, true);
        return is_array($payload) ? $payload : null;
    }
    return null;
}

function generate_token($user) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'id' => isset($user['_id']) ? $user['_id'] : (isset($user['id']) ? $user['id'] : 'usr_' . time()),
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => isset($user['role']) ? $user['role'] : 'employee',
        'exp' => time() + 86400
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'super_secret_box_retailer_key_2026', true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
