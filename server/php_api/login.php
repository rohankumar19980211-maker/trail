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

$foundUser = null;
$usernameFound = false;

foreach ($users as $u) {
    if (isset($u['username']) && strtolower(trim($u['username'])) === $cleanUser) {
        $usernameFound = true;
        $userPass = isset($u['password']) ? (string)$u['password'] : '';
        if (box_verify_password($password, $userPass)) {
            $foundUser = $u;
            break;
        }
    }
}

if (!$usernameFound) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid credentials. User not found.']);
    exit();
}

if (!$foundUser) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid credentials. Incorrect password.']);
    exit();
}

$token = generate_token($foundUser);
$userRes = [
    'id' => isset($foundUser['_id']) ? $foundUser['_id'] : (isset($foundUser['id']) ? $foundUser['id'] : ''),
    'username' => $foundUser['username'],
    'name' => $foundUser['name'],
    'role' => isset($foundUser['role']) ? $foundUser['role'] : 'employee'
];

echo json_encode(['token' => $token, 'user' => $userRes]);
