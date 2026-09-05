<?php
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. POST action=seed (re-seed database)
if ($method === 'POST' && $action === 'seed') {
    $seedScript = __DIR__ . '/seed.php';
    if (file_exists($seedScript)) {
        require_once $seedScript;
        $res = seed_php_database();
        echo json_encode(array_merge(['message' => 'Database successfully seeded!'], $res));
        exit();
    }
}

$products = get_collection('products');

// 2. GET (list products)
if ($method === 'GET') {
    $search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $sizeCategory = isset($_GET['sizeCategory']) ? trim($_GET['sizeCategory']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    $filtered = [];
    foreach ($products as $p) {
        if ($category && isset($p['category']) && $p['category'] !== $category) continue;
        if ($sizeCategory && isset($p['sizeCategory']) && $p['sizeCategory'] !== $sizeCategory) continue;
        if ($search) {
            $t = isset($p['title']) ? strtolower($p['title']) : '';
            $s = isset($p['boxSize']) ? strtolower($p['boxSize']) : '';
            $c = isset($p['category']) ? strtolower($p['category']) : '';
            $d = isset($p['description']) ? strtolower($p['description']) : '';
            if (strpos($t, $search) === false && strpos($s, $search) === false && strpos($c, $search) === false && strpos($d, $search) === false) {
                continue;
            }
        }
        $filtered[] = $p;
    }

    $total = count($filtered);
    $startIndex = ($page - 1) * $limit;
    $paginated = array_slice($filtered, $startIndex, $limit);

    echo json_encode([
        'total' => $total,
        'page' => $page,
        'totalPages' => ceil($total / $limit),
        'count' => count($paginated),
        'products' => $paginated
    ]);
    exit();
}

// Admin Authentication required for CUD operations
$currentUser = get_auth_user();
if (!$currentUser || (isset($currentUser['role']) && $currentUser['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: Admin access required']);
    exit();
}

// 3. POST (create product)
if ($method === 'POST') {
    $input = get_json_input();
    $title = isset($input['title']) ? $input['title'] : '';
    $boxSize = isset($input['boxSize']) ? $input['boxSize'] : '';
    $unitPrice = isset($input['unitPrice']) ? (float)$input['unitPrice'] : 0;
    $availableQuantity = isset($input['availableQuantity']) ? (int)$input['availableQuantity'] : 0;

    if (!$title || !$boxSize) {
        http_response_code(400);
        echo json_encode(['message' => 'Title and Box Size are required']);
        exit();
    }

    $newP = array_merge([
        '_id' => 'id_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
        'sku' => 'BOX-CUSTOM-' . substr(time(), -6),
        'title' => $title,
        'boxSize' => $boxSize,
        'length' => isset($input['length']) ? (int)$input['length'] : 12,
        'width' => isset($input['width']) ? (int)$input['width'] : 12,
        'height' => isset($input['height']) ? (int)$input['height'] : 12,
        'category' => isset($input['category']) ? $input['category'] : 'Corrugated Cartons',
        'sizeCategory' => isset($input['sizeCategory']) ? $input['sizeCategory'] : 'Medium',
        'description' => isset($input['description']) ? $input['description'] : '',
        'unitPrice' => $unitPrice,
        'availableQuantity' => $availableQuantity,
        'image' => isset($input['image']) ? $input['image'] : 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
        'discountTiers' => isset($input['discountTiers']) ? $input['discountTiers'] : [
            ['minQuantity' => 100, 'discountPercent' => 5],
            ['minQuantity' => 300, 'discountPercent' => 10],
            ['minQuantity' => 500, 'discountPercent' => 18],
            ['minQuantity' => 600, 'discountPercent' => 20]
        ],
        'createdAt' => date('c'),
        'updatedAt' => date('c')
    ]);

    $products[] = $newP;
    save_collection('products', $products);
    http_response_code(201);
    echo json_encode($newP);
    exit();
}

// 4. PATCH (update stock)
if ($method === 'PATCH') {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    $input = get_json_input();
    $qty = isset($input['availableQuantity']) ? (int)$input['availableQuantity'] : 0;

    foreach ($products as &$p) {
        if ((isset($p['_id']) && $p['_id'] == $id) || (isset($p['id']) && $p['id'] == $id)) {
            $p['availableQuantity'] = $qty;
            $p['updatedAt'] = date('c');
            save_collection('products', $products);
            echo json_encode(['message' => 'Stock updated', 'product' => $p]);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(['message' => 'Product not found']);
    exit();
}

// 5. PUT (update product)
if ($method === 'PUT') {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    $input = get_json_input();

    foreach ($products as &$p) {
        if ((isset($p['_id']) && $p['_id'] == $id) || (isset($p['id']) && $p['id'] == $id)) {
            $p = array_merge($p, $input, ['updatedAt' => date('c')]);
            save_collection('products', $products);
            echo json_encode($p);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(['message' => 'Product not found']);
    exit();
}

// 6. DELETE (delete product)
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    $newProds = [];
    $deleted = false;

    foreach ($products as $p) {
        if ((isset($p['_id']) && $p['_id'] == $id) || (isset($p['id']) && $p['id'] == $id)) {
            $deleted = true;
            continue;
        }
        $newProds[] = $p;
    }

    if ($deleted) {
        save_collection('products', $newProds);
        echo json_encode(['message' => 'Product deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
    }
    exit();
}
