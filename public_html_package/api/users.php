<?php
require_once __DIR__ . '/helpers.php';

$currentUser = get_auth_user();
if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = get_json_input();

if ($method === 'GET') {
    // 1. Try MySQL
    if ($use_mysql && $pdo) {
        try {
            $stmt = $pdo->query("SELECT id AS _id, id, username, name, role, created_at AS createdAt FROM users ORDER BY id ASC");
            $mysqlUsers = $stmt->fetchAll();
            if (!empty($mysqlUsers)) {
                echo json_encode($mysqlUsers);
                exit();
            }
        } catch (\Exception $e) {}
    }

    // 2. Fallback JSON
    $users = get_collection('users');
    $safe = array_map(function($u) {
        return [
            '_id' => $u['id'] ?? $u['_id'] ?? 1,
            'id' => $u['id'] ?? $u['_id'] ?? 1,
            'username' => $u['username'],
            'name' => $u['name'] ?? $u['username'],
            'role' => $u['role'] ?? 'employee',
            'createdAt' => $u['createdAt'] ?? date('c')
        ];
    }, $users);
    echo json_encode(array_values($safe));
    exit();
}

if ($method === 'POST') {
    $username = strtolower(trim($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $name = trim($input['name'] ?? $username);
    $role = ($input['role'] ?? 'employee') === 'admin' ? 'admin' : 'employee';

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['message' => 'Username and password are required']);
        exit();
    }

    $hash = box_hash_password($password);

    // 1. Save directly into MySQL users table
    if ($use_mysql && $pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), name = VALUES(name), role = VALUES(role)");
            $stmt->execute([$username, $hash, $name, $role]);
            $newId = $pdo->lastInsertId() ?: 1;

            http_response_code(201);
            echo json_encode([
                '_id' => $newId,
                'id' => $newId,
                'username' => $username,
                'name' => $name,
                'role' => $role,
                'createdAt' => date('c')
            ]);
            exit();
        } catch (\Exception $e) {}
    }

    // 2. JSON fallback
    $users = get_collection('users');
    $newUser = [
        '_id' => 'user_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
        'username' => $username,
        'password' => $hash,
        'name' => $name,
        'role' => $role,
        'createdAt' => date('c')
    ];
    $users[] = $newUser;
    save_collection('users', $users);

    http_response_code(201);
    echo json_encode($newUser);
    exit();
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? $input['id'] ?? '';
    if ($use_mysql && $pdo && is_numeric($id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Employee account deleted successfully']);
            exit();
        } catch (\Exception $e) {}
    }

    $users = get_collection('users');
    $filtered = array_filter($users, function($u) use ($id) {
        return ($u['id'] ?? $u['_id'] ?? '') != $id;
    });
    save_collection('users', $filtered);
    echo json_encode(['message' => 'Employee account deleted successfully']);
    exit();
}
