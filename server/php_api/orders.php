<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$currentUser = get_auth_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. GET /api/orders
if ($method === 'GET') {
    $role = $currentUser['role'] ?? 'employee';
    $username = $currentUser['username'] ?? '';
    $userId = $currentUser['user_id'] ?? $currentUser['id'] ?? 0;

    if ($use_mysql && $pdo) {
        $ordersSql = "SELECT o.id AS _id, o.id, o.order_number AS orderNumber, o.username AS userUsername, o.user_name AS userName, o.subtotal_amount_inr AS subtotalAmount, o.total_savings_inr AS totalSavings, o.total_amount_inr AS finalAmount, o.total_quantity AS totalQuantity, o.status, o.shipping_notes AS deliveryNotes, o.created_at AS createdAt FROM orders o";
        $params = [];

        if ($role !== 'admin') {
            $ordersSql .= " WHERE LOWER(o.username) = ?";
            $params[] = strtolower($username);
        }

        $ordersSql .= " ORDER BY o.id DESC";
        $stmt = $pdo->prepare($ordersSql);
        $stmt->execute($params);
        $ordersList = $stmt->fetchAll();

        foreach ($ordersList as &$ord) {
            $itemStmt = $pdo->prepare("SELECT product_id AS productId, title, box_size AS boxSize, unit_price_inr AS unitPrice, quantity, discount_percent AS discountPercent, discounted_unit_price_inr AS discountedUnitPrice, total_item_price_inr AS totalItemPrice FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$ord['id']]);
            $ord['items'] = $itemStmt->fetchAll();
        }

        echo json_encode($ordersList);
        exit();
    } else {
        $orders = get_json_collection('orders');
        $result = [];
        if ($role === 'admin') {
            $result = $orders;
        } else {
            foreach ($orders as $o) {
                $uName = $o['userUsername'] ?? $o['username'] ?? '';
                if (strtolower($uName) === strtolower($username)) {
                    $result[] = $o;
                }
            }
        }

        usort($result, function($a, $b) {
            $tA = isset($a['createdAt']) ? strtotime($a['createdAt']) : 0;
            $tB = isset($b['createdAt']) ? strtotime($b['createdAt']) : 0;
            return $tB - $tA;
        });

        echo json_encode(array_values($result));
        exit();
    }
}

// 2. POST /api/orders
if ($method === 'POST') {
    $input = get_json_input();
    $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
    $deliveryNotes = trim($input['deliveryNotes'] ?? '');

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['message' => 'Order must contain at least one item']);
        exit();
    }

    if ($use_mysql && $pdo) {
        try {
            $pdo->beginTransaction();

            $processedItems = [];
            $totalQuantity = 0;
            $subtotalAmount = 0.0;
            $finalAmount = 0.0;

            foreach ($items as $item) {
                $pId = $item['productId'] ?? 0;
                $orderQty = (int)($item['quantity'] ?? 0);

                if ($orderQty <= 0) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid quantity for item']);
                    exit();
                }

                $pStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? OR _id = ? OR sku = ?");
                $pStmt->execute([$pId, $pId, $pId]);
                $prod = $pStmt->fetch();

                if (!$prod) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(['message' => "Product $pId not found"]);
                    exit();
                }

                $availQty = (int)$prod['stock_qty'];
                if ($availQty < $orderQty) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(['message' => "Insufficient warehouse stock for {$prod['title']}. Requested: $orderQty, Available: $availQty"]);
                    exit();
                }

                $tiers = json_decode($prod['discount_tiers_json'] ?? '', true);
                if (!is_array($tiers) || empty($tiers)) {
                    $tiers = [
                        ['minQuantity' => 100, 'discountPercent' => 5],
                        ['minQuantity' => 300, 'discountPercent' => 10],
                        ['minQuantity' => 500, 'discountPercent' => 18],
                        ['minQuantity' => 600, 'discountPercent' => 20]
                    ];
                }

                usort($tiers, function($a, $b) {
                    return $b['minQuantity'] - $a['minQuantity'];
                });

                $applicableDiscountPercent = 0;
                foreach ($tiers as $t) {
                    if ($orderQty >= $t['minQuantity']) {
                        $applicableDiscountPercent = (float)$t['discountPercent'];
                        break;
                    }
                }

                $unitPrice = (float)$prod['price_inr'];
                $itemSubtotal = $unitPrice * $orderQty;
                $discountedUnitPrice = $unitPrice * (1 - $applicableDiscountPercent / 100.0);
                $itemTotal = $discountedUnitPrice * $orderQty;

                $processedItems[] = [
                    'product_id' => $prod['id'],
                    'title' => $prod['title'],
                    'box_size' => $prod['box_size'],
                    'unit_price_inr' => $unitPrice,
                    'quantity' => $orderQty,
                    'discount_percent' => $applicableDiscountPercent,
                    'discounted_unit_price_inr' => round($discountedUnitPrice, 2),
                    'total_item_price_inr' => round($itemTotal, 2)
                ];

                $totalQuantity += $orderQty;
                $subtotalAmount += $itemSubtotal;
                $finalAmount += $itemTotal;

                // Deduct inventory stock
                $updStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
                $updStmt->execute([$orderQty, $prod['id']]);
            }

            $totalSavings = $subtotalAmount - $finalAmount;
            $orderNumber = 'ORD-' . substr((string)time(), -6);

            $ordStmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, username, user_name, subtotal_amount_inr, total_savings_inr, total_amount_inr, total_quantity, shipping_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Processing')");
            $ordStmt->execute([
                $orderNumber,
                $currentUser['user_id'] ?? $currentUser['id'] ?? null,
                $currentUser['username'] ?? 'employee',
                $currentUser['name'] ?? $currentUser['username'] ?? 'Employee',
                round($subtotalAmount, 2),
                round($totalSavings, 2),
                round($finalAmount, 2),
                $totalQuantity,
                $deliveryNotes
            ]);
            $orderId = $pdo->lastInsertId();

            foreach ($processedItems as $pi) {
                $itemInsStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, title, box_size, unit_price_inr, quantity, discount_percent, discounted_unit_price_inr, total_item_price_inr) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $itemInsStmt->execute([
                    $orderId,
                    $pi['product_id'],
                    $pi['title'],
                    $pi['box_size'],
                    $pi['unit_price_inr'],
                    $pi['quantity'],
                    $pi['discount_percent'],
                    $pi['discounted_unit_price_inr'],
                    $pi['total_item_price_inr']
                ]);
            }

            $pdo->commit();

            echo json_encode([
                '_id' => $orderId,
                'id' => $orderId,
                'orderNumber' => $orderNumber,
                'userUsername' => $currentUser['username'] ?? '',
                'userName' => $currentUser['name'] ?? '',
                'totalQuantity' => $totalQuantity,
                'subtotalAmount' => round($subtotalAmount, 2),
                'totalSavings' => round($totalSavings, 2),
                'finalAmount' => round($finalAmount, 2),
                'status' => 'Processing',
                'deliveryNotes' => $deliveryNotes,
                'createdAt' => date('c')
            ]);
            exit();
        } catch (\Exception $ex) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['message' => 'Failed to place order: ' . $ex->getMessage()]);
            exit();
        }
    } else {
        // Fallback JSON DB
        $products = get_json_collection('products');
        $orders = get_json_collection('orders');

        $processedItems = [];
        $totalQuantity = 0;
        $subtotalAmount = 0.0;
        $finalAmount = 0.0;

        foreach ($items as $item) {
            $pId = $item['productId'] ?? '';
            $orderQty = (int)($item['quantity'] ?? 0);

            if ($orderQty <= 0) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid quantity for item']);
                exit();
            }

            $productIndex = -1;
            foreach ($products as $idx => $p) {
                $currId = $p['_id'] ?? $p['id'] ?? '';
                if ($currId == $pId) {
                    $productIndex = $idx;
                    break;
                }
            }

            if ($productIndex === -1) {
                http_response_code(400);
                echo json_encode(['message' => "Product $pId not found"]);
                exit();
            }

            $prod = $products[$productIndex];
            $availQty = (int)($prod['availableQuantity'] ?? 0);

            if ($availQty < $orderQty) {
                $pTitle = $prod['title'] ?? 'Product';
                http_response_code(400);
                echo json_encode(['message' => "Insufficient warehouse stock for $pTitle. Requested: $orderQty, Available: $availQty"]);
                exit();
            }

            $applicableDiscountPercent = 0;
            if (isset($prod['discountTiers']) && is_array($prod['discountTiers'])) {
                $tiers = $prod['discountTiers'];
                usort($tiers, function($a, $b) { return $b['minQuantity'] - $a['minQuantity']; });
                foreach ($tiers as $tier) {
                    if ($orderQty >= $tier['minQuantity']) {
                        $applicableDiscountPercent = (float)$tier['discountPercent'];
                        break;
                    }
                }
            }

            $unitPrice = (float)($prod['unitPrice'] ?? 0);
            $itemSubtotal = $unitPrice * $orderQty;
            $discountedUnitPrice = $unitPrice * (1 - $applicableDiscountPercent / 100.0);
            $itemTotal = $discountedUnitPrice * $orderQty;

            $processedItems[] = [
                'productId' => $prod['_id'] ?? $prod['id'],
                'title' => $prod['title'] ?? '',
                'boxSize' => $prod['boxSize'] ?? '',
                'unitPrice' => $unitPrice,
                'quantity' => $orderQty,
                'discountPercent' => $applicableDiscountPercent,
                'discountedUnitPrice' => round($discountedUnitPrice, 2),
                'totalItemPrice' => round($itemTotal, 2)
            ];

            $totalQuantity += $orderQty;
            $subtotalAmount += $itemSubtotal;
            $finalAmount += $itemTotal;

            $products[$productIndex]['availableQuantity'] = $availQty - $orderQty;
            $products[$productIndex]['updatedAt'] = date('c');
        }

        save_json_collection('products', $products);

        $totalSavings = $subtotalAmount - $finalAmount;
        $orderNumber = 'ORD-' . substr((string)time(), -6);

        $newOrder = [
            '_id' => 'ord_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
            'orderNumber' => $orderNumber,
            'userUsername' => $currentUser['username'] ?? '',
            'userName' => $currentUser['name'] ?? '',
            'items' => $processedItems,
            'totalQuantity' => $totalQuantity,
            'subtotalAmount' => round($subtotalAmount, 2),
            'totalSavings' => round($totalSavings, 2),
            'finalAmount' => round($finalAmount, 2),
            'status' => 'Processing',
            'deliveryNotes' => $deliveryNotes,
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ];

        $orders[] = $newOrder;
        save_json_collection('orders', $orders);

        http_response_code(201);
        echo json_encode($newOrder);
        exit();
    }
}
