<?php
// install.php - 1-Click Database Setup & 360+ Product Seeder
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$message = '';
$status_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    if (!$db_connected || !$pdo) {
        $message = "Database connection failed: " . ($db_error_msg ?? 'Unknown error');
        $status_type = 'error';
    } else {
        try {
            // 1. Create Tables
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `username` VARCHAR(100) NOT NULL UNIQUE,
                  `password_hash` VARCHAR(255) NOT NULL,
                  `name` VARCHAR(150) NOT NULL,
                  `role` ENUM('admin', 'employee') DEFAULT 'employee',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `products` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `sku` VARCHAR(100) NOT NULL UNIQUE,
                  `title` VARCHAR(255) NOT NULL,
                  `box_size` VARCHAR(100) NOT NULL,
                  `category` VARCHAR(100) NOT NULL,
                  `size_category` VARCHAR(50) DEFAULT 'Medium',
                  `length` FLOAT NOT NULL DEFAULT 12,
                  `width` FLOAT NOT NULL DEFAULT 12,
                  `height` FLOAT NOT NULL DEFAULT 12,
                  `wall_strength` VARCHAR(50) DEFAULT 'ECT-32',
                  `description` TEXT,
                  `price_inr` DECIMAL(10,2) NOT NULL DEFAULT 45.00,
                  `stock_qty` INT NOT NULL DEFAULT 500,
                  `image_url` TEXT,
                  `discount_tiers_json` TEXT,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `orders` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `order_number` VARCHAR(50) NOT NULL UNIQUE,
                  `user_id` INT DEFAULT NULL,
                  `username` VARCHAR(100) NOT NULL,
                  `user_name` VARCHAR(150) NOT NULL,
                  `subtotal_amount_inr` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                  `total_savings_inr` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                  `total_amount_inr` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                  `total_quantity` INT NOT NULL DEFAULT 0,
                  `shipping_notes` TEXT,
                  `status` ENUM('Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Processing',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `order_items` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `order_id` INT NOT NULL,
                  `product_id` INT NOT NULL,
                  `sku` VARCHAR(100) NOT NULL,
                  `title` VARCHAR(255) NOT NULL,
                  `unit_price` DECIMAL(10,2) NOT NULL,
                  `discounted_unit_price` DECIMAL(10,2) NOT NULL,
                  `quantity` INT NOT NULL,
                  `tier_discount_percent` INT NOT NULL DEFAULT 0,
                  `total_price` DECIMAL(12,2) NOT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 2. Ensure Master Admin and Sanity Employee exist
            $adminHash = hash_password_secure('admin123', 'defaultsalt2026');
            $sanityHash = hash_password_secure('SanityPass2026!', 'sanitysalt2026');

            $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password_hash`, `name`, `role`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `name` = VALUES(`name`), `role` = VALUES(`role`)");
            $stmt->execute(['admin', $adminHash, 'Master Admin', 'admin']);
            $stmt->execute(['sanity_emp', $sanityHash, 'Sanity Test Employee', 'employee']);

            // 3. Seed Products if empty
            $prodCountStmt = $pdo->query("SELECT COUNT(*) FROM `products`");
            $prodCount = $prodCountStmt->fetchColumn();

            $seededCount = 0;
            $jsonFile = __DIR__ . '/data/products.json';

            if ($prodCount < 10 && file_exists($jsonFile)) {
                $raw = file_get_contents($jsonFile);
                $productsData = json_decode($raw, true);

                if (is_array($productsData)) {
                    $insertProd = $pdo->prepare("
                        INSERT INTO `products` (`sku`, `title`, `box_size`, `category`, `size_category`, `length`, `width`, `height`, `wall_strength`, `description`, `price_inr`, `stock_qty`, `image_url`, `discount_tiers_json`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `price_inr` = VALUES(`price_inr`), `stock_qty` = VALUES(`stock_qty`)
                    ");

                    foreach ($productsData as $p) {
                        $sku = $p['sku'] ?? ('BOX-' . strtoupper(substr(md5($p['title']), 0, 8)));
                        $title = $p['title'] ?? 'Heavy Duty Box';
                        $boxSize = $p['boxSize'] ?? '12" x 12" x 12"';
                        $cat = $p['category'] ?? 'Corrugated Cartons';
                        $sizeCat = $p['sizeCategory'] ?? 'Medium';
                        $len = floatval($p['length'] ?? 12);
                        $w = floatval($p['width'] ?? 12);
                        $h = floatval($p['height'] ?? 12);
                        $wall = $p['wallStrength'] ?? 'ECT-32';
                        $desc = $p['description'] ?? 'Industrial grade shipping box';
                        $price = floatval($p['price_inr'] ?? ($p['unitPrice'] ? $p['unitPrice'] * 45 : 45.00));
                        $stock = intval($p['availableQuantity'] ?? 500);
                        $img = $p['image'] ?? 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80';
                        $tiers = json_encode($p['discountTiers'] ?? [
                            ['minQuantity' => 100, 'discountPercent' => 5],
                            ['minQuantity' => 300, 'discountPercent' => 10],
                            ['minQuantity' => 500, 'discountPercent' => 18],
                            ['minQuantity' => 600, 'discountPercent' => 20],
                            ['minQuantity' => 1000, 'discountPercent' => 25],
                        ]);

                        $insertProd->execute([$sku, $title, $boxSize, $cat, $sizeCat, $len, $w, $h, $wall, $desc, $price, $stock, $img, $tiers]);
                        $seededCount++;
                    }
                }
            }

            $message = "Installation completed successfully! Tables verified. Default admin & sanity_emp configured. {$seededCount} box products seeded.";
            $status_type = 'success';
        } catch (\Exception $ex) {
            $message = "Installation error: " . $ex->getMessage();
            $status_type = 'error';
        }
    }
}

// Check current counts
$currentUsers = 0;
$currentProducts = 0;
if ($db_connected && $pdo) {
    try {
        $currentUsers = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $currentProducts = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
    } catch (\Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOXRETAIL - 1-Click Database Setup & Seeder</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-xl w-full bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-8">
        <div class="flex items-center space-x-4 mb-6">
            <div class="w-12 h-12 bg-amber-500/20 text-amber-500 rounded-xl flex items-center justify-center font-bold text-2xl">
                📦
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">BOXRETAIL Database Setup</h1>
                <p class="text-slate-400 text-sm">Automated MySQL Schema & 360+ Product Catalog Installer</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl <?= $status_type === 'success' ? 'bg-emerald-500/20 border border-emerald-500/40 text-emerald-300' : 'bg-rose-500/20 border border-rose-500/40 text-rose-300' ?>">
                <p class="font-medium"><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-slate-950/60 rounded-xl p-5 border border-slate-700/60 mb-6 space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">MySQL Connection Status:</span>
                <?php if ($db_connected): ?>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Connected (<?= htmlspecialchars($db_name) ?>)</span>
                <?php else: ?>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-400 border border-rose-500/30">Failed</span>
                <?php endif; ?>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">Database User:</span>
                <span class="font-mono text-xs text-amber-400"><?= htmlspecialchars($db_user) ?></span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">Current Users in MySQL:</span>
                <span class="font-bold text-white"><?= intval($currentUsers) ?> accounts</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400">Current Products in MySQL:</span>
                <span class="font-bold text-white"><?= intval($currentProducts) ?> products</span>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <button type="submit" name="run_install" value="1" class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-xl shadow-lg transition-all transform active:scale-95 flex items-center justify-center space-x-2">
                <span>🚀 Run Auto-Installer & Seed 360+ Products</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-700 flex justify-between text-xs text-slate-400">
            <a href="index.php" class="hover:text-amber-400 underline">← Return to Storefront</a>
            <a href="login.php" class="hover:text-amber-400 underline">Portal Sign-In →</a>
        </div>
    </div>
</body>
</html>
