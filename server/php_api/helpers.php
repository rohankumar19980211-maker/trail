<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$DB_DIR = __DIR__ . '/../data';
if (!file_exists($DB_DIR)) {
    mkdir($DB_DIR, 0777, true);
}

function get_collection($name) {
    global $DB_DIR;
    $filePath = $DB_DIR . '/' . $name . '.json';
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode([]));
        return [];
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function save_collection($name, $data) {
    global $DB_DIR;
    $filePath = $DB_DIR . '/' . $name . '.json';
    file_put_contents($filePath, json_encode(array_values($data), JSON_PRETTY_PRINT));
}

function get_json_input() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

function get_auth_user() {
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
    if (!$auth || strpos($auth, 'Bearer ') !== 0) {
        return null;
    }
    $token = substr($auth, 7);
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        return $payload;
    }
    return null;
}

function generate_token($user) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'id' => isset($user['_id']) ? $user['_id'] : $user['id'],
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
