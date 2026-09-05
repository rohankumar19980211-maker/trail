<?php
require_once __DIR__ . '/helpers.php';

function seed_php_database() {
    $defaultUsers = [
        [
            '_id' => 'usr_admin',
            'username' => 'admin',
            'password' => 'admin123',
            'name' => 'Master Admin',
            'role' => 'admin',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ],
        [
            '_id' => 'usr_john',
            'username' => 'emp_john',
            'password' => 'boxemp123',
            'name' => 'John Miller (Sales)',
            'role' => 'employee',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ],
        [
            '_id' => 'usr_sarah',
            'username' => 'emp_sarah',
            'password' => 'boxemp123',
            'name' => 'Sarah Jenkins (Logistics)',
            'role' => 'employee',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ],
        [
            '_id' => 'usr_alex',
            'username' => 'emp_alex',
            'password' => 'boxemp123',
            'name' => 'Alex Rivera (Warehouse)',
            'role' => 'employee',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ],
        [
            '_id' => 'usr_david',
            'username' => 'emp_david',
            'password' => 'boxemp123',
            'name' => 'David Vance (Procurement)',
            'role' => 'employee',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ]
    ];

    save_collection('users', $defaultUsers);

    $sampleImages = [
        'Corrugated Cartons' => [
            'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=600&q=80'
        ],
        'Heavy-Duty Moving' => [
            'https://images.unsplash.com/photo-1607344645866-009c320c5ab8?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?auto=format&fit=crop&w=600&q=80'
        ],
        'Corrugated Mailers' => [
            'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=600&q=80'
        ],
        'Die-Cut / Gift Boxes' => [
            'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1512909006721-3d6018887383?auto=format&fit=crop&w=600&q=80'
        ],
        'Telescopic & Special' => [
            'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1607344645866-009c320c5ab8?auto=format&fit=crop&w=600&q=80'
        ]
    ];

    $categories = [
        'Corrugated Cartons',
        'Heavy-Duty Moving',
        'Corrugated Mailers',
        'Die-Cut / Gift Boxes',
        'Telescopic & Special'
    ];

    $products = [];
    $idCounter = 1;

    $lengthOptions = [4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 28, 30, 36];
    $widthOptions = [4, 6, 8, 10, 12, 14, 16, 18, 20, 24];
    $heightOptions = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 24, 30];

    $count = 0;
    foreach ($lengthOptions as $l) {
        foreach ($widthOptions as $w) {
            if ($w > $l) continue;
            foreach ($heightOptions as $h) {
                if ($count >= 360) break 3;

                $category = $categories[$count % count($categories)];
                $volume = $l * $w * $h;

                $sizeCategory = 'Medium';
                if ($volume <= 300) $sizeCategory = 'Small';
                else if ($volume <= 1500) $sizeCategory = 'Medium';
                else if ($volume <= 4000) $sizeCategory = 'Large';
                else $sizeCategory = 'Extra Large';

                $baseCostINR = 25.00 + ($volume * 0.045);
                if ($category === 'Heavy-Duty Moving') $baseCostINR *= 1.45;
                if ($category === 'Die-Cut / Gift Boxes') $baseCostINR *= 1.30;
                $unitPrice = round($baseCostINR, 2);

                $stockQuantities = [150, 250, 400, 600, 850, 1200, 1500, 2200, 3500];
                $availableQuantity = $stockQuantities[($l + $w + $h) % count($stockQuantities)];

                $imgs = $sampleImages[$category];
                $image = $imgs[$count % count($imgs)];

                $sku = "BOX-" . strtoupper(substr(str_replace([' ', '/', '-'], '', $category), 0, 3)) . "-{$l}X{$w}X{$h}-" . str_pad($idCounter, 3, '0', STR_PAD_LEFT);

                $discountTiers = [
                    ['minQuantity' => 100, 'discountPercent' => 5],
                    ['minQuantity' => 300, 'discountPercent' => 10],
                    ['minQuantity' => 500, 'discountPercent' => 18],
                    ['minQuantity' => 600, 'discountPercent' => 20]
                ];

                if ($count % 7 === 0) {
                    $discountTiers[] = ['minQuantity' => 1000, 'discountPercent' => 25];
                }

                $products[] = [
                    '_id' => 'prod_' . $idCounter,
                    'sku' => $sku,
                    'title' => "{$l}\" x {$w}\" x {$h}\" " . str_replace('/', '&', $category) . " Box",
                    'boxSize' => "{$l}\" x {$w}\" x {$h}\"",
                    'length' => $l,
                    'width' => $w,
                    'height' => $h,
                    'category' => $category,
                    'sizeCategory' => $sizeCategory,
                    'description' => "Industrial grade " . strtolower($category) . " constructed from durable ECT-32 single/double wall kraft paper. Ideal for bulk shipping, storage, and retail distribution in India.",
                    'unitPrice' => $unitPrice,
                    'availableQuantity' => $availableQuantity,
                    'image' => $image,
                    'discountTiers' => $discountTiers,
                    'createdAt' => date('c'),
                    'updatedAt' => date('c')
                ];

                $idCounter++;
                $count++;
            }
        }
    }

    save_collection('products', $products);

    return ['usersCount' => count($defaultUsers), 'productsCount' => count($products)];
}

// Auto-run seed if executed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === 'seed.php') {
    $res = seed_php_database();
    echo json_encode(array_merge(['message' => 'Database successfully seeded!'], $res));
}
