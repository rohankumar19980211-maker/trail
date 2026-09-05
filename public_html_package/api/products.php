<?php
$suppress_db_check = true;
require_once __DIR__ . '/db.php';

require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// 1. POST action=seed (re-seed database)
if ($method === 'POST' && $action === 'seed') {
    $seedScript = __DIR__ . '/seed.php';
    if (file_exists($seedScript)) {
        require_once $seedScript;
        $res = seed_database_all();
        echo json_encode(array_merge(['message' => 'Database successfully seeded!'], $res));
        exit();
    }
}

// 2. GET (list products with search and pagination)
if ($method === 'GET') {
    $search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $sizeCategory = isset($_GET['sizeCategory']) ? trim($_GET['sizeCategory']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

    if ($use_mysql && $pdo) {
        $where = [];
        $params = [];

        if ($category) {
            $where[] = "category = ?";
            $params[] = $category;
        }
        if ($sizeCategory) {
            $where[] = "size_category = ?";
            $params[] = $sizeCategory;
        }
        if ($search) {
            $where[] = "(LOWER(title) LIKE ? OR LOWER(box_size) LIKE ? OR LOWER(category) LIKE ? OR LOWER(description) LIKE ?)";
            $s = "%$search%";
            $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $limit;
        $stmt = $pdo->prepare("SELECT id AS _id, id, sku, title, box_size AS boxSize, length, width, height, category, size_category AS sizeCategory, description, price_inr AS unitPrice, stock_qty AS availableQuantity, image_url AS image, discount_tiers_json FROM products $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $formatted = array_map(function($r) {
            $tiers = json_decode($r['discount_tiers_json'] ?? '', true);
            if (!is_array($tiers) || empty($tiers)) {
                $tiers = [
                    ['minQuantity' => 100, 'discountPercent' => 5],
                    ['minQuantity' => 300, 'discountPercent' => 10],
                    ['minQuantity' => 500, 'discountPercent' => 18],
                    ['minQuantity' => 600, 'discountPercent' => 20]
                ];
            }
            $r['discountTiers'] = $tiers;
            unset($r['discount_tiers_json']);
            return $r;
        }, $rows);

        echo json_encode([
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'count' => count($formatted),
            'products' => $formatted
        ]);
        exit();
    } else {
        // Fallback JSON DB
        $products = get_json_collection('products');
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
}

// Admin Authentication required for CUD operations
$currentUser = get_auth_user();
if (!$currentUser || (($currentUser['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden: Admin access required']);
    exit();
}

// 3. POST (create product)
if ($method === 'POST') {
    $input = get_json_input();
    $title = $input['title'] ?? '';
    $boxSize = $input['boxSize'] ?? '';
    $unitPrice = isset($input['unitPrice']) ? (float)$input['unitPrice'] : 45.00;
    $availableQuantity = isset($input['availableQuantity']) ? (int)$input['availableQuantity'] : 500;
    $category = $input['category'] ?? 'Corrugated Cartons';

    if (!$title || !$boxSize) {
        http_response_code(400);
        echo json_encode(['message' => 'Title and Box Size are required']);
        exit();
    }

    $sku = 'BOX-' . strtoupper(substr(str_replace([' ', '/', '-'], '', $category), 0, 3)) . '-' . time();
    $discountTiers = $input['discountTiers'] ?? [
        ['minQuantity' => 100, 'discountPercent' => 5],
        ['minQuantity' => 300, 'discountPercent' => 10],
        ['minQuantity' => 500, 'discountPercent' => 18],
        ['minQuantity' => 600, 'discountPercent' => 20]
    ];

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("INSERT INTO products (sku, title, box_size, category, size_category, length, width, height, description, price_inr, stock_qty, image_url, discount_tiers_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $sku,
            $title,
            $boxSize,
            $category,
            $input['sizeCategory'] ?? 'Medium',
            (float)($input['length'] ?? 12),
            (float)($input['width'] ?? 12),
            (float)($input['height'] ?? 12),
            $input['description'] ?? '',
            $unitPrice,
            $availableQuantity,
            $input['image'] ?? 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
            json_encode($discountTiers)
        ]);
        $newId = $pdo->lastInsertId();

        echo json_encode([
            '_id' => $newId,
            'id' => $newId,
            'sku' => $sku,
            'title' => $title,
            'boxSize' => $boxSize,
            'category' => $category,
            'sizeCategory' => $input['sizeCategory'] ?? 'Medium',
            'unitPrice' => $unitPrice,
            'availableQuantity' => $availableQuantity,
            'image' => $input['image'] ?? 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
            'discountTiers' => $discountTiers
        ]);
        exit();
    } else {
        $products = get_json_collection('products');
        $newP = [
            '_id' => 'id_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
            'sku' => $sku,
            'title' => $title,
            'boxSize' => $boxSize,
            'length' => (float)($input['length'] ?? 12),
            'width' => (float)($input['width'] ?? 12),
            'height' => (float)($input['height'] ?? 12),
            'category' => $category,
            'sizeCategory' => $input['sizeCategory'] ?? 'Medium',
            'description' => $input['description'] ?? '',
            'unitPrice' => $unitPrice,
            'availableQuantity' => $availableQuantity,
            'image' => $input['image'] ?? 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
            'discountTiers' => $discountTiers,
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ];
        $products[] = $newP;
        save_json_collection('products', $products);
        http_response_code(201);
        echo json_encode($newP);
        exit();
    }
}

// 4. PATCH /products/:id/stock
if ($method === 'PATCH') {
    $input = get_json_input();
    $qty = (int)($input['availableQuantity'] ?? 0);

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
        $stmt->execute([$qty, $id]);
        echo json_encode(['message' => 'Stock updated', 'id' => $id, 'availableQuantity' => $qty]);
        exit();
    } else {
        $products = get_json_collection('products');
        foreach ($products as &$p) {
            if (($p['_id'] ?? '') == $id || ($p['id'] ?? '') == $id) {
                $p['availableQuantity'] = $qty;
                $p['updatedAt'] = date('c');
                save_json_collection('products', $products);
                echo json_encode(['message' => 'Stock updated', 'product' => $p]);
                exit();
            }
        }
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit();
    }
}

// 5. PUT /products/:id
if ($method === 'PUT') {
    $input = get_json_input();

    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("UPDATE products SET title = ?, box_size = ?, category = ?, price_inr = ?, stock_qty = ? WHERE id = ?");
        $stmt->execute([
            $input['title'] ?? '',
            $input['boxSize'] ?? '',
            $input['category'] ?? 'Corrugated Cartons',
            (float)($input['unitPrice'] ?? 45.00),
            (int)($input['availableQuantity'] ?? 500),
            $id
        ]);
        echo json_encode(array_merge(['_id' => $id, 'id' => $id], $input));
        exit();
    } else {
        $products = get_json_collection('products');
        foreach ($products as &$p) {
            if (($p['_id'] ?? '') == $id || ($p['id'] ?? '') == $id) {
                $p = array_merge($p, $input, ['updatedAt' => date('c')]);
                save_json_collection('products', $products);
                echo json_encode($p);
                exit();
            }
        }
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit();
    }
}

// 6. DELETE /products/:id
if ($method === 'DELETE') {
    if ($use_mysql && $pdo) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['message' => 'Product deleted successfully']);
        exit();
    } else {
        $products = get_json_collection('products');
        $foundIndex = -1;
        foreach ($products as $idx => $p) {
            if (($p['_id'] ?? '') == $id || ($p['id'] ?? '') == $id) {
                $foundIndex = $idx;
                break;
            }
        }
        if ($foundIndex !== -1) {
            array_splice($products, $foundIndex, 1);
            save_json_collection('products', $products);
            echo json_encode(['message' => 'Product deleted successfully']);
            exit();
        }
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit();
    }
}
