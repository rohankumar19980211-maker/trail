<?php
// config/auth.php - Session Authentication & Universal Password Engine
require_once __DIR__ . '/db.php';

function hash_password_secure($password, $salt = null) {
    if (!$salt) {
        $salt = bin2hex(random_bytes(16));
    }
    $hash = hash_hmac('sha256', $password, $salt);
    return "sha256$" . $salt . "$" . $hash;
}

function verify_password_secure($password, $stored_hash) {
    if (!$stored_hash) return false;
    
    $parts = explode('$', $stored_hash);
    
    // 1. Universal HMAC SHA-256 format: sha256$<salt>$<hash>
    if (count($parts) === 3 && $parts[0] === 'sha256') {
        $salt = $parts[1];
        $computed = hash_hmac('sha256', $password, $salt);
        if (hash_equals($parts[2], $computed)) return true;
        if (hash_equals($parts[2], hash('sha256', $password . $salt))) return true;
    }
    
    // 2. Legacy Salted SHA-256: sha256$<hash>
    if (count($parts) === 2 && $parts[0] === 'sha256') {
        $legacy = 'sha256$' . hash('sha256', $password . 'box_salt_2026');
        if (hash_equals($stored_hash, $legacy)) return true;
    }
    
    // 3. Standard PHP Bcrypt verification
    if (password_verify($password, $stored_hash)) return true;
    if (password_verify($password, str_replace('$2a$', '$2y$', $stored_hash))) return true;
    if (password_verify($password, str_replace('$2y$', '$2a$', $stored_hash))) return true;
    
    // 4. Plaintext comparison for setup fallback
    if ($stored_hash === (string)$password) return true;
    
    return false;
}

function get_logged_in_user() {
    return $_SESSION['box_user'] ?? null;
}

function is_logged_in() {
    return !empty($_SESSION['box_user']);
}

function is_admin() {
    return !empty($_SESSION['box_user']) && ($_SESSION['box_user']['role'] ?? '') === 'admin';
}

function is_employee() {
    return !empty($_SESSION['box_user']) && in_array($_SESSION['box_user']['role'] ?? '', ['admin', 'employee']);
}

function require_login($role = null) {
    if (!is_logged_in()) {
        header("Location: index.php?msg=login_required");
        exit();
    }
    if ($role === 'admin' && !is_admin()) {
        header("Location: index.php?error=admin_only");
        exit();
    }
}

// Strict Role-Enforced Login Helper
function attempt_login($username, $password, $target_portal = 'employee') {
    global $pdo, $db_connected;

    if (!$username || !$password) {
        return ['success' => false, 'error' => 'Username and password are required.'];
    }

    if (!$db_connected || !$pdo) {
        return ['success' => false, 'error' => 'Database connection failed. Please run install.php'];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE LOWER(username) = ? LIMIT 1");
        $stmt->execute([strtolower(trim($username))]);
        $userRow = $stmt->fetch();

        if (!$userRow) {
            return ['success' => false, 'error' => 'Invalid credentials. User not found.'];
        }

        $storedHash = $userRow['password_hash'] ?? '';
        if (!verify_password_secure($password, $storedHash)) {
            return ['success' => false, 'error' => 'Invalid credentials. Incorrect password.'];
        }

        // Strict Portal Role Check
        $userRole = $userRow['role'] ?? 'employee';

        if ($target_portal === 'admin' && $userRole !== 'admin') {
            return [
                'success' => false, 
                'error' => '⛔ Access Denied: Employee accounts cannot access the Admin Portal. Please use Employee Sign-In.'
            ];
        }

        // Store user in session
        $userData = [
            'id' => $userRow['id'],
            'username' => $userRow['username'],
            'name' => $userRow['name'] ?? $userRow['username'],
            'role' => $userRole
        ];
        $_SESSION['box_user'] = $userData;

        return ['success' => true, 'user' => $userData];

    } catch (\Exception $e) {
        return ['success' => false, 'error' => 'Authentication error: ' . $e->getMessage()];
    }
}
