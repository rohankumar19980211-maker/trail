<?php
require_once __DIR__ . '/helpers.php';

$input = get_json_input();
$username = strtolower(trim($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['message' => 'Username and password are required']);
    exit();
}

$foundUser = null;

// 1. Direct MySQL Query
if ($use_mysql && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ? LIMIT 1");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch();
        if ($userRow) {
            $userPass = (string)($userRow['password_hash'] ?? $userRow['password'] ?? '');
            if (box_verify_password($password, $userPass)) {
                $foundUser = [
                    'id' => $userRow['id'],
                    'username' => $userRow['username'],
                    'name' => $userRow['name'] ?? $userRow['username'],
                    'role' => $userRow['role'] ?? 'employee'
                ];
            } else {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid credentials. Incorrect password.']);
                exit();
            }
        }
    } catch (\Exception $e) {}
}

// 2. Fallback JSON Check if not found in MySQL
if (!$foundUser) {
    $allUsers = get_collection('users');
    $userMatched = false;
    foreach ($allUsers as $u) {
        if (isset($u['username']) && strtolower(trim($u['username'])) === $username) {
            $userMatched = true;
            $userPass = (string)($u['password'] ?? $u['password_hash'] ?? '');
            if (box_verify_password($password, $userPass)) {
                $foundUser = [
                    'id' => $u['_id'] ?? $u['id'] ?? 1,
                    'username' => $u['username'],
                    'name' => $u['name'] ?? $u['username'],
                    'role' => $u['role'] ?? 'employee'
                ];
                break;
            }
        }
    }
    
    if (!$foundUser) {
        http_response_code(401);
        if ($userMatched) {
            echo json_encode(['message' => 'Invalid credentials. Incorrect password.']);
        } else {
            echo json_encode(['message' => 'Invalid credentials. User not found.']);
        }
        exit();
    }
}

$token = generate_token($foundUser);
echo json_encode([
    'status' => 'success',
    'token' => $token,
    'user' => $foundUser
]);
exit();
