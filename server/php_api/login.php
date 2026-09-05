<?php
require_once __DIR__ . '/helpers.php';

$input = get_json_input();
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['message' => 'Username and password are required']);
    exit();
}

$users = get_collection('users');
$cleanUser = strtolower($username);

$found = null;
foreach ($users as $u) {
    if (isset($u['username']) && strtolower(trim($u['username'])) === $cleanUser) {
        $found = $u;
        break;
    }
}

if (!$found) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid credentials. User not found.']);
    exit();
}

$userPass = isset($found['password']) ? (string)$found['password'] : '';
$isMatch = false;

if (strpos($userPass, '$2') === 0) {
    $isMatch = password_verify($password, $userPass);
} else {
    $isMatch = ($userPass === $password);
}

if (!$isMatch) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid credentials. Incorrect password.']);
    exit();
}

$token = generate_token($found);
$userRes = [
    'id' => isset($found['_id']) ? $found['_id'] : $found['id'],
    'username' => $found['username'],
    'name' => $found['name'],
    'role' => isset($found['role']) ? $found['role'] : 'employee'
];

echo json_encode(['token' => $token, 'user' => $userRes]);
