<?php
// api.php - Fast JSON AJAX Endpoint for Wholesale Operations
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$action = $_GET['action'] ?? '';

// Employees and admins only. JSON 401 rather than require_login()'s redirect.
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please sign in.']);
    exit();
}

// 1. Health check & DB diagnostic
if ($action === 'health' || empty($action)) {
    echo json_encode([
        'status' => $db_connected ? 'connected' : 'error',
        'time' => date('c'),
        'message' => $db_connected ? 'Native PHP 8.x + MySQL PDO connection healthy!' : ($db_error_msg ?? 'Database disconnected')
    ]);
    exit();
}

// 2. Search Box Products
if ($action === 'search_products') {
    $q = trim($_GET['q'] ?? '');
    $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));

    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, sku, title, box_size AS boxSize, category, size_category AS sizeCategory, price_inr AS unitPrice, stock_qty AS availableQuantity, image_url AS image
                FROM `products`
                WHERE `title` LIKE ? OR `sku` LIKE ? OR `box_size` LIKE ?
                LIMIT ?
            ");
            $like = "%{$q}%";
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['products' => $stmt->fetchAll()]);
            exit();
        } catch (\Exception $e) {}
    }

    echo json_encode(['products' => []]);
    exit();
}

// 3. Get Active User Profile
if ($action === 'me') {
    echo json_encode(['user' => get_logged_in_user()]);
    exit();
}

http_response_code(404);
echo json_encode(['error' => 'Unknown action']);
exit();
