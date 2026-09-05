<?php
$suppress_db_check = true;
require_once __DIR__ . '/db.php';


if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'SuperSecretEnterpriseKey_BoxRetail_2026#');

// 1. Universal Password Hash Generator & Verifier
function generate_universal_hash($password, $salt = null) {
    if (!$salt) {
        $salt = bin2hex(random_bytes(16)); // Generates 32-character hex salt
    }
    $hash = hash_hmac('sha256', $password, $salt);
    return "sha256$" . $salt . "$" . $hash;
}

function verify_universal_hash($password, $stored_hash) {
    if (!$stored_hash) return false;
    
    $parts = explode('$', $stored_hash);
    
    // Check if the hash uses the Universal Salted HMAC SHA-256 Protocol
    if (count($parts) === 3 && $parts[0] === 'sha256') {
        $salt = $parts[1];
        $computed_hash = generate_universal_hash($password, $salt);
        return hash_equals($stored_hash, $computed_hash);
    }
    
    // Legacy Salted SHA256 format check (sha256$hash)
    if (count($parts) === 2 && $parts[0] === 'sha256') {
        $legacyHash = 'sha256$' . hash('sha256', $password . 'box_salt_2026');
        if (hash_equals($stored_hash, $legacyHash)) return true;
    }
    
    // Fallback verification for native PHP / Node bcrypt hashes
    if (password_verify($password, $stored_hash)) return true;
    if (password_verify($password, str_replace('$2a$', '$2y$', $stored_hash))) return true;
    if (password_verify($password, str_replace('$2y$', '$2a$', $stored_hash))) return true;
    
    // Fallback direct match for initial seed default passwords
    if ($stored_hash === (string)$password) return true;

    return false;
}

// 2. Pure PHP JWT Implementation (Zero Third-Party Library Overhead)
function generate_jwt($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
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

// 3. API Action Router
$input = get_json_input();
$action = $_GET['action'] ?? $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Handle direct URI paths like /api/auth/login or /api/auth/users
if (!$action) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, 'login') !== false) $action = 'login';
    elseif (strpos($uri, 'users') !== false) {
        if ($method === 'GET') $action = 'get_users';
        elseif ($method === 'POST') $action = 'create_user';
        elseif ($method === 'PUT') $action = 'reset_password';
        elseif ($method === 'DELETE') $action = 'delete_user';
    }
}

// ACTION: Login
if ($action === 'login' || ($method === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', 'login') !== false)) {
    $username = strtolower(trim($input['username'] ?? ''));
    $password = $input['password'] ?? '';

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
        exit();
    }

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && verify_universal_hash($password, $user['password_hash'])) {
            $token = generate_jwt([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'] ?? $user['username'],
                'role' => $user['role'],
                'exp' => time() + (24 * 3600)
            ]);

            echo json_encode([
                'status' => 'success',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'name' => $user['name'] ?? $user['username'],
                    'role' => $user['role']
                ]
            ]);
            exit();
        }
    } else {
        // Fallback JSON DB
        $users = get_json_collection('users');
        $found = null;
        foreach ($users as $u) {
            if (isset($u['username']) && strtolower(trim($u['username'])) === $username) {
                $userPass = isset($u['password']) ? (string)$u['password'] : (isset($u['password_hash']) ? (string)$u['password_hash'] : '');
                if (verify_universal_hash($password, $userPass)) {
                    $found = $u;
                    break;
                }
            }
        }

        if ($found) {
            $token = generate_jwt([
                'user_id' => $found['_id'] ?? $found['id'] ?? 1,
                'username' => $found['username'],
                'name' => $found['name'] ?? $found['username'],
                'role' => $found['role'] ?? 'employee',
                'exp' => time() + (24 * 3600)
            ]);

            echo json_encode([
                'status' => 'success',
                'token' => $token,
                'user' => [
                    'id' => $found['_id'] ?? $found['id'] ?? 1,
                    'username' => $found['username'],
                    'name' => $found['name'] ?? $found['username'],
                    'role' => $found['role'] ?? 'employee'
                ]
            ]);
            exit();
        }
    }

    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials. Incorrect username or password.']);
    exit();
}

// ACTION: Create or Upsert User
if ($action === 'create_user' || ($method === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', 'users') !== false)) {
    $currentUser = get_auth_user();
    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
        exit();
    }

    $username = strtolower(trim($input['username'] ?? ''));
    $password = $input['password'] ?? '';
    $name = trim($input['name'] ?? $username);
    $role = ($input['role'] ?? 'employee') === 'admin' ? 'admin' : 'employee';

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
        exit();
    }

    $hash = generate_universal_hash($password);

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), name = VALUES(name), role = VALUES(role)");
        $stmt->execute([$username, $hash, $name, $role]);

        echo json_encode(['status' => 'success', 'message' => 'User account created successfully']);
        exit();
    } else {
        $users = get_json_collection('users');
        $existingIndex = -1;

        foreach ($users as $index => $u) {
            if (isset($u['username']) && strtolower(trim($u['username'])) === $username) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== -1) {
            $users[$existingIndex]['password'] = $hash;
            $users[$existingIndex]['password_hash'] = $hash;
            $users[$existingIndex]['name'] = $name;
            $users[$existingIndex]['role'] = $role;
            $users[$existingIndex]['updatedAt'] = date('c');
            save_json_collection('users', $users);

            echo json_encode(['status' => 'success', 'message' => 'User account updated successfully']);
            exit();
        }

        $newUser = [
            '_id' => 'user_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
            'username' => $username,
            'password' => $hash,
            'password_hash' => $hash,
            'name' => $name,
            'role' => $role,
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ];
        $users[] = $newUser;
        save_json_collection('users', $users);

        echo json_encode(['status' => 'success', 'message' => 'User account created successfully']);
        exit();
    }
}

// ACTION: Get All Users
if ($action === 'get_users' || ($method === 'GET' && strpos($_SERVER['REQUEST_URI'] ?? '', 'users') !== false)) {
    $currentUser = get_auth_user();
    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
        exit();
    }

    if ($use_mysql && $pdo) {
        $stmt = $pdo->query("SELECT id AS _id, id, username, name, role, created_at AS createdAt FROM users ORDER BY id DESC");
        $all = $stmt->fetchAll();
        echo json_encode($all);
        exit();
    } else {
        $users = get_json_collection('users');
        $safe = array_map(function($u) {
            return [
                '_id' => $u['_id'] ?? $u['id'] ?? 1,
                'id' => $u['_id'] ?? $u['id'] ?? 1,
                'username' => $u['username'] ?? '',
                'name' => $u['name'] ?? $u['username'] ?? '',
                'role' => $u['role'] ?? 'employee',
                'createdAt' => $u['createdAt'] ?? date('c')
            ];
        }, $users);
        echo json_encode(array_values($safe));
        exit();
    }
}

// ACTION: Reset Password
if ($action === 'reset_password' || ($method === 'PUT' && strpos($_SERVER['REQUEST_URI'] ?? '', 'users') !== false)) {
    $currentUser = get_auth_user();
    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
        exit();
    }

    $id = $_GET['id'] ?? $input['id'] ?? '';
    $oldPassword = $input['oldPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';

    if (!$oldPassword || !$newPassword) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Both Old Password and New Password are required']);
        exit();
    }

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if (!$target || !verify_universal_hash($oldPassword, $target['password_hash'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Old password does not match current password']);
            exit();
        }

        $newHash = generate_universal_hash($newPassword);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        exit();
    } else {
        $users = get_json_collection('users');
        $foundIndex = -1;
        foreach ($users as $idx => $u) {
            $uId = $u['_id'] ?? $u['id'] ?? '';
            if ($uId == $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === -1) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit();
        }

        $userPass = $users[$foundIndex]['password_hash'] ?? $users[$foundIndex]['password'] ?? '';
        if (!verify_universal_hash($oldPassword, $userPass)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Old password does not match current password']);
            exit();
        }

        $newHash = generate_universal_hash($newPassword);
        $users[$foundIndex]['password'] = $newHash;
        $users[$foundIndex]['password_hash'] = $newHash;
        save_json_collection('users', $users);

        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        exit();
    }
}

// ACTION: Delete User
if ($action === 'delete_user' || ($method === 'DELETE' && strpos($_SERVER['REQUEST_URI'] ?? '', 'users') !== false)) {
    $currentUser = get_auth_user();
    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
        exit();
    }

    $id = $_GET['id'] ?? '';

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if ($target && ($target['username'] === 'admin' || $target['role'] === 'admin')) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Primary administrator account cannot be deleted']);
            exit();
        }

        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Employee account deleted successfully']);
        exit();
    } else {
        $users = get_json_collection('users');
        $foundIndex = -1;
        foreach ($users as $idx => $u) {
            $uId = $u['_id'] ?? $u['id'] ?? '';
            if ($uId == $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === -1) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit();
        }

        if (($users[$foundIndex]['username'] ?? '') === 'admin' || ($users[$foundIndex]['role'] ?? '') === 'admin') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Primary administrator account cannot be deleted']);
            exit();
        }

        array_splice($users, $foundIndex, 1);
        save_json_collection('users', $users);

        echo json_encode(['status' => 'success', 'message' => 'Employee account deleted successfully']);
        exit();
    }
}
