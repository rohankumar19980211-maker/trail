<?php
require_once __DIR__ . '/helpers.php';

$currentUser = get_auth_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit();
}

if (isset($currentUser['role']) && $currentUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$users = get_collection('users');
$id = isset($_GET['id']) ? $_GET['id'] : '';

// 1. GET /api/auth/users
if ($method === 'GET') {
    $safeUsers = array_map(function($u) {
        return [
            '_id' => isset($u['_id']) ? $u['_id'] : $u['id'],
            'username' => isset($u['username']) ? $u['username'] : '',
            'name' => isset($u['name']) ? $u['name'] : '',
            'role' => isset($u['role']) ? $u['role'] : 'employee',
            'createdAt' => isset($u['createdAt']) ? $u['createdAt'] : date('c')
        ];
    }, $users);
    echo json_encode($safeUsers);
    exit();
}

// 2. POST /api/auth/users
if ($method === 'POST') {
    $input = get_json_input();
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    $name = isset($input['name']) ? trim($input['name']) : '';
    $role = isset($input['role']) && $input['role'] === 'admin' ? 'admin' : 'employee';

    if (!$username || !$password || !$name) {
        http_response_code(400);
        echo json_encode(['message' => 'Name, username, and password are required']);
        exit();
    }

    $cleanUsername = strtolower($username);
    foreach ($users as $u) {
        if (isset($u['username']) && strtolower(trim($u['username'])) === $cleanUsername) {
            http_response_code(400);
            echo json_encode(['message' => 'Username is already taken']);
            exit();
        }
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $newUser = [
        '_id' => 'user_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
        'username' => $cleanUsername,
        'password' => $hashedPassword,
        'name' => $name,
        'role' => $role,
        'createdAt' => date('c'),
        'updatedAt' => date('c')
    ];

    $users[] = $newUser;
    save_collection('users', $users);

    http_response_code(201);
    echo json_encode([
        '_id' => $newUser['_id'],
        'username' => $newUser['username'],
        'name' => $newUser['name'],
        'role' => $newUser['role'],
        'createdAt' => $newUser['createdAt']
    ]);
    exit();
}

// 3. PUT /api/auth/users/:id/password
if ($method === 'PUT') {
    $input = get_json_input();
    $oldPassword = isset($input['oldPassword']) ? $input['oldPassword'] : '';
    $newPassword = isset($input['newPassword']) ? $input['newPassword'] : '';

    if (!$id) {
        $id = isset($input['id']) ? $input['id'] : '';
    }

    if (!$oldPassword || !$newPassword) {
        http_response_code(400);
        echo json_encode(['message' => 'Both Old Password and New Password are required']);
        exit();
    }

    if (strlen($newPassword) < 4) {
        http_response_code(400);
        echo json_encode(['message' => 'New password must be at least 4 characters long']);
        exit();
    }

    $foundIndex = -1;
    foreach ($users as $index => $u) {
        $uId = isset($u['_id']) ? $u['_id'] : (isset($u['id']) ? $u['id'] : '');
        if ($uId == $id) {
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === -1) {
        http_response_code(404);
        echo json_encode(['message' => 'Employee user account not found']);
        exit();
    }

    $targetUser = $users[$foundIndex];
    $userPass = isset($targetUser['password']) ? (string)$targetUser['password'] : '';
    $isOldMatch = false;

    if (strpos($userPass, '$2') === 0) {
        $isOldMatch = password_verify($oldPassword, $userPass);
    } else {
        $isOldMatch = ($userPass === $oldPassword);
    }

    if (!$isOldMatch) {
        http_response_code(400);
        echo json_encode(['message' => 'Old password does not match current password']);
        exit();
    }

    $users[$foundIndex]['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
    $users[$foundIndex]['updatedAt'] = date('c');
    save_collection('users', $users);

    echo json_encode(['message' => 'Password updated successfully']);
    exit();
}

// 4. DELETE /api/auth/users/:id
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['message' => 'User ID is required']);
        exit();
    }

    $foundIndex = -1;
    foreach ($users as $index => $u) {
        $uId = isset($u['_id']) ? $u['_id'] : (isset($u['id']) ? $u['id'] : '');
        if ($uId == $id) {
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === -1) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
        exit();
    }

    $userToDelete = $users[$foundIndex];
    if ($userToDelete['username'] === 'admin' || (isset($userToDelete['role']) && $userToDelete['role'] === 'admin')) {
        http_response_code(400);
        echo json_encode(['message' => 'Primary administrator account cannot be deleted']);
        exit();
    }

    array_splice($users, $foundIndex, 1);
    save_collection('users', $users);

    echo json_encode(['message' => 'Employee account deleted successfully']);
    exit();
}

http_response_code(405);
echo json_encode(['message' => 'Method Not Allowed']);
