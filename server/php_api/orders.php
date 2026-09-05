<?php
require_once __DIR__ . '/helpers.php';

$currentUser = get_auth_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$orders = get_collection('orders');
$products = get_collection('products');

// 1. GET /api/orders
if ($method === 'GET') {
    $role = isset($currentUser['role']) ? $currentUser['role'] : 'employee';
    $username = isset($currentUser['username']) ? $currentUser['username'] : '';

    $result = [];
    if ($role === 'admin') {
        $result = $orders;
    } else {
        foreach ($orders as $o) {
            $uName = isset($o['userUsername']) ? $o['userUsername'] : (isset($o['username']) ? $o['username'] : '');
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

// 2. POST /api/orders
if ($method === 'POST') {
    $input = get_json_input();
    $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
    $deliveryNotes = isset($input['deliveryNotes']) ? trim($input['deliveryNotes']) : '';

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['message' => 'Order must contain at least one item']);
        exit();
    }

    $processedItems = [];
    $totalQuantity = 0;
    $subtotalAmount = 0.0;
    $finalAmount = 0.0;

    foreach ($items as $item) {
        $pId = isset($item['productId']) ? $item['productId'] : '';
        $orderQty = isset($item['quantity']) ? (int)$item['quantity'] : 0;

        if ($orderQty <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid quantity for item']);
            exit();
        }

        $productIndex = -1;
        foreach ($products as $idx => $p) {
            $currId = isset($p['_id']) ? $p['_id'] : (isset($p['id']) ? $p['id'] : '');
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
        $availQty = isset($prod['availableQuantity']) ? (int)$prod['availableQuantity'] : 0;

        if ($availQty < $orderQty) {
            $pTitle = isset($prod['title']) ? $prod['title'] : 'Product';
            http_response_code(400);
            echo json_encode(['message' => "Insufficient warehouse stock for $pTitle. Requested: $orderQty, Available: $availQty"]);
            exit();
        }

        $applicableDiscountPercent = 0;
        if (isset($prod['discountTiers']) && is_array($prod['discountTiers'])) {
            $tiers = $prod['discountTiers'];
            usort($tiers, function($a, $b) {
                return $b['minQuantity'] - $a['minQuantity'];
            });
            foreach ($tiers as $tier) {
                if ($orderQty >= $tier['minQuantity']) {
                    $applicableDiscountPercent = (float)$tier['discountPercent'];
                    break;
                }
            }
        }

        $unitPrice = isset($prod['unitPrice']) ? (float)$prod['unitPrice'] : 0;
        $itemSubtotal = $unitPrice * $orderQty;
        $discountedUnitPrice = $unitPrice * (1 - $applicableDiscountPercent / 100.0);
        $itemTotal = $discountedUnitPrice * $orderQty;

        $processedItems[] = [
            'productId' => isset($prod['_id']) ? $prod['_id'] : $prod['id'],
            'title' => isset($prod['title']) ? $prod['title'] : '',
            'boxSize' => isset($prod['boxSize']) ? $prod['boxSize'] : '',
            'unitPrice' => $unitPrice,
            'quantity' => $orderQty,
            'discountPercent' => $applicableDiscountPercent,
            'discountedUnitPrice' => round($discountedUnitPrice, 2),
            'totalItemPrice' => round($itemTotal, 2)
        ];

        $totalQuantity += $orderQty;
        $subtotalAmount += $itemSubtotal;
        $finalAmount += $itemTotal;

        // Deduct inventory stock
        $products[$productIndex]['availableQuantity'] = $availQty - $orderQty;
        $products[$productIndex]['updatedAt'] = date('c');
    }

    save_collection('products', $products);

    $totalSavings = $subtotalAmount - $finalAmount;
    $orderNumber = 'ORD-' . substr((string)time(), -6);

    $newOrder = [
        '_id' => 'ord_' . time() . '_' . substr(md5(mt_rand()), 0, 6),
        'orderNumber' => $orderNumber,
        'userUsername' => isset($currentUser['username']) ? $currentUser['username'] : '',
        'userName' => isset($currentUser['name']) ? $currentUser['name'] : '',
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
    save_collection('orders', $orders);

    http_response_code(201);
    echo json_encode($newOrder);
    exit();
}

http_response_code(405);
echo json_encode(['message' => 'Method Not Allowed']);
