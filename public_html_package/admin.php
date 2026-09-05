<?php
// admin.php - Master Administrator Hub & Employee Manager
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Enforce Master Admin Access
require_login('admin');

$notice = '';
$notice_type = 'success';

// Handle Actions (Add Employee, Delete Employee, Reset Password, Add Product, Update Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    // 1. ADD EMPLOYEE
    if ($action === 'add_employee') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? $username);
        $role = ($_POST['role'] ?? 'employee') === 'admin' ? 'admin' : 'employee';

        if (!$username || !$password) {
            $notice = "Username and password are required.";
            $notice_type = 'error';
        } else {
            try {
                $hash = hash_password_secure($password);
                $stmt = $pdo->prepare("
                    INSERT INTO `users` (`username`, `password_hash`, `name`, `role`) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `name` = VALUES(`name`), `role` = VALUES(`role`)
                ");
                $stmt->execute([$username, $hash, $name, $role]);
                $notice = "Employee account for '{$name}' (@{$username}) saved to MySQL successfully!";
            } catch (\Exception $e) {
                $notice = "Database error: " . $e->getMessage();
                $notice_type = 'error';
            }
        }
    }

    // 2. DELETE EMPLOYEE
    if ($action === 'delete_employee') {
        $id = intval($_POST['user_id'] ?? 0);
        if ($id > 1) { // Prevent deleting primary Master Admin
            try {
                $stmt = $pdo->prepare("DELETE FROM `users` WHERE `id` = ?");
                $stmt->execute([$id]);
                $notice = "User ID #{$id} deleted successfully from MySQL.";
            } catch (\Exception $e) {
                $notice = "Error deleting user: " . $e->getMessage();
                $notice_type = 'error';
            }
        } else {
            $notice = "Primary Master Admin account cannot be deleted.";
            $notice_type = 'error';
        }
    }

    // 3. RESET PASSWORD
    if ($action === 'reset_password') {
        $id = intval($_POST['user_id'] ?? 0);
        $newPass = (string)($_POST['new_password'] ?? '');
        if ($id > 0 && strlen($newPass) >= 4) {
            try {
                $hash = hash_password_secure($newPass);
                $stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?");
                $stmt->execute([$hash, $id]);
                $notice = "Password for User ID #{$id} updated in MySQL!";
            } catch (\Exception $e) {
                $notice = "Error updating password: " . $e->getMessage();
                $notice_type = 'error';
            }
        } else {
            $notice = "Password must be at least 4 characters long.";
            $notice_type = 'error';
        }
    }

    // 4. ADD PRODUCT
    if ($action === 'add_product') {
        $sku = strtoupper(trim($_POST['sku'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $boxSize = trim($_POST['box_size'] ?? '');
        $category = $_POST['category'] ?? 'Corrugated Cartons';
        $sizeCat = $_POST['size_category'] ?? 'Medium';
        $price = floatval($_POST['price_inr'] ?? 45.00);
        $stock = intval($_POST['stock_qty'] ?? 500);
        $img = trim($_POST['image_url'] ?? 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80');

        if (!$sku || !$title) {
            $notice = "SKU and Title are required.";
            $notice_type = 'error';
        } else {
            try {
                $tiers = json_encode([
                    ['minQuantity' => 100, 'discountPercent' => 5],
                    ['minQuantity' => 300, 'discountPercent' => 10],
                    ['minQuantity' => 500, 'discountPercent' => 18],
                    ['minQuantity' => 600, 'discountPercent' => 20],
                    ['minQuantity' => 1000, 'discountPercent' => 25],
                ]);
                $stmt = $pdo->prepare("
                    INSERT INTO `products` (`sku`, `title`, `box_size`, `category`, `size_category`, `price_inr`, `stock_qty`, `image_url`, `discount_tiers_json`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `price_inr` = VALUES(`price_inr`), `stock_qty` = VALUES(`stock_qty`)
                ");
                $stmt->execute([$sku, $title, $boxSize, $category, $sizeCat, $price, $stock, $img, $tiers]);
                $notice = "Box SKU '{$sku}' saved to catalog successfully!";
            } catch (\Exception $e) {
                $notice = "Error saving product: " . $e->getMessage();
                $notice_type = 'error';
            }
        }
    }

    // 5. UPDATE STOCK
    if ($action === 'update_stock') {
        $id = intval($_POST['product_id'] ?? 0);
        $stock = intval($_POST['stock_qty'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE `products` SET `stock_qty` = ? WHERE `id` = ?");
                $stmt->execute([$stock, $id]);
                $notice = "Stock updated for product #{$id}.";
            } catch (\Exception $e) {}
        }
    }
}

// Fetch all users from MySQL
$users = [];
$totalProductsCount = 0;
$totalOrdersCount = 0;
$totalWarehouseStock = 0;

if ($db_connected && $pdo) {
    try {
        $users = $pdo->query("SELECT * FROM `users` ORDER BY `id` ASC")->fetchAll();
        $totalProductsCount = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        $totalWarehouseStock = $pdo->query("SELECT SUM(`stock_qty`) FROM `products`")->fetchColumn() ?: 0;
        $totalOrdersCount = $pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    } catch (\Exception $e) {}
}

$pageTitle = 'Master Administrator Hub';
require_once __DIR__ . '/includes/header.php';
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ activeTab: 'employees', resetModal: false, resetUserId: null, resetUsername: '' }">

    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">
                <span>🛡️ Master Admin</span>
                <span>•</span>
                <span>Live MySQL: <?= htmlspecialchars($db_name) ?></span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">Wholesale Portal Administration</h1>
            <p class="text-xs text-slate-400 mt-1">Manage employee access, database tables, and wholesale inventory</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="install.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold flex items-center space-x-1.5 transition-colors">
                <span>⚙️ DB Auto-Sync / Installer</span>
            </a>
            <a href="logout.php" class="px-4 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 rounded-xl text-xs font-bold flex items-center space-x-1.5 transition-colors">
                <span>Sign Out</span>
            </a>
        </div>
    </div>

    <?php if ($notice): ?>
        <div class="mb-6 p-4 rounded-xl <?= $notice_type === 'success' ? 'bg-emerald-500/20 border border-emerald-500/40 text-emerald-300' : 'bg-rose-500/20 border border-rose-500/40 text-rose-300' ?> text-sm font-medium flex items-center space-x-2">
            <span><?= $notice_type === 'success' ? '✓' : '⚠️' ?></span>
            <span><?= htmlspecialchars($notice) ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 shadow-xl">
            <span class="text-xs text-slate-400 font-medium">Database Users</span>
            <div class="text-2xl font-black text-white mt-1"><?= count($users) ?></div>
            <span class="text-[10px] text-emerald-400 font-bold">phpMyAdmin Synced</span>
        </div>
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 shadow-xl">
            <span class="text-xs text-slate-400 font-medium">Active Box SKUs</span>
            <div class="text-2xl font-black text-amber-400 mt-1"><?= intval($totalProductsCount) ?></div>
            <span class="text-[10px] text-slate-400">Direct Factory Catalog</span>
        </div>
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 shadow-xl">
            <span class="text-xs text-slate-400 font-medium">Warehouse Units</span>
            <div class="text-2xl font-black text-white mt-1"><?= number_format($totalWarehouseStock) ?></div>
            <span class="text-[10px] text-slate-400">Pallet Inventory</span>
        </div>
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 shadow-xl">
            <span class="text-xs text-slate-400 font-medium">Wholesale Quotes</span>
            <div class="text-2xl font-black text-emerald-400 mt-1"><?= intval($totalOrdersCount) ?></div>
            <span class="text-[10px] text-slate-400">B2B Procurement</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-800 mb-8 space-x-6">
        <button @click="activeTab = 'employees'" 
                :class="activeTab === 'employees' ? 'text-amber-400 border-amber-500 font-bold' : 'text-slate-400 hover:text-slate-200 border-transparent'"
                class="py-3 border-b-2 text-sm transition-colors flex items-center space-x-2">
            <span>👥 Employee Management (MySQL Table: `users`)</span>
        </button>
        <button @click="activeTab = 'inventory'" 
                :class="activeTab === 'inventory' ? 'text-amber-400 border-amber-500 font-bold' : 'text-slate-400 hover:text-slate-200 border-transparent'"
                class="py-3 border-b-2 text-sm transition-colors flex items-center space-x-2">
            <span>📦 Box Inventory & Add New SKU</span>
        </button>
    </div>

    <!-- TAB 1: EMPLOYEE MANAGEMENT (DIRECT MYSQL) -->
    <div x-show="activeTab === 'employees'" class="space-y-8">
        
        <!-- Add Employee Form -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 shadow-xl">
            <div class="flex items-center space-x-2 mb-4">
                <span class="text-amber-400 font-bold text-lg">+</span>
                <h3 class="text-lg font-bold text-white">Create New Employee Account</h3>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-bold border border-emerald-500/30">Instantly Saved to MySQL</span>
            </div>

            <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <input type="hidden" name="action" value="add_employee">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Rahul Sharma" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Username / ID</label>
                    <input type="text" name="username" required placeholder="e.g. rahul_sales" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Portal Password</label>
                    <input type="password" name="password" required placeholder="••••••••••••" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Role / Access</label>
                    <select name="role" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        <option value="employee">Employee (Warehouse/Sales)</option>
                        <option value="admin">Master Admin</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs shadow transition-all active:scale-95">
                        + Save to MySQL
                    </button>
                </div>
            </form>
        </div>

        <!-- Live MySQL Users Table -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-700/80 flex justify-between items-center bg-slate-950/40">
                <div>
                    <h3 class="font-bold text-white text-base">Current Accounts in MySQL (`users` table)</h3>
                    <p class="text-xs text-slate-400">All changes reflect in phpMyAdmin instantly</p>
                </div>
                <span class="text-xs font-mono text-amber-400 font-bold"><?= count($users) ?> total rows</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 uppercase font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Row # (ID)</th>
                            <th class="py-3 px-4">Username</th>
                            <th class="py-3 px-4">Full Name</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4">Created At</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="py-3 px-4 font-mono text-amber-400 font-bold"><?= htmlspecialchars($u['id']) ?></td>
                                <td class="py-3 px-4 font-mono font-semibold text-white"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($u['name'] ?? $u['username']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $u['role'] === 'admin' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-blue-500/20 text-blue-400 border border-blue-500/30' ?>">
                                        <?= strtoupper($u['role'] ?? 'employee') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400"><?= htmlspecialchars($u['created_at'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <button @click="resetModal = true; resetUserId = <?= $u['id'] ?>; resetUsername = '<?= htmlspecialchars($u['username']) ?>'" 
                                            class="px-2.5 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg text-[11px] font-semibold">
                                        Reset Password
                                    </button>

                                    <?php if ($u['id'] > 1): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete employee @<?= htmlspecialchars($u['username']) ?> from MySQL?');">
                                            <input type="hidden" name="action" value="delete_employee">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 rounded-lg text-[11px] font-semibold">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: INVENTORY & ADD SKU -->
    <div x-show="activeTab === 'inventory'" class="space-y-8" x-cloak>
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 shadow-xl">
            <h3 class="text-lg font-bold text-white mb-4">+ Add New Box SKU to Catalog</h3>
            
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="hidden" name="action" value="add_product">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">SKU</label>
                    <input type="text" name="sku" required placeholder="e.g. BOX-COR-14X14X10" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Title</label>
                    <input type="text" name="title" required placeholder='14" x 14" x 10" Corrugated Cartons' class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Dimensions</label>
                    <input type="text" name="box_size" required placeholder='14" x 14" x 10"' class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        <option value="Corrugated Cartons">Corrugated Cartons</option>
                        <option value="Heavy-Duty Moving">Heavy-Duty Moving</option>
                        <option value="Corrugated Mailers">Corrugated Mailers</option>
                        <option value="Die-Cut / Gift Boxes">Die-Cut / Gift Boxes</option>
                        <option value="Telescopic & Special">Telescopic & Special</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Size Category</label>
                    <select name="size_category" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        <option value="Small">Small</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                        <option value="Extra Large">Extra Large</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Base Price in ₹ (INR)</label>
                    <input type="number" step="0.50" name="price_inr" required value="55.00" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Stock Quantity</label>
                    <input type="number" name="stock_qty" required value="500" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Action</label>
                    <button type="submit" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs shadow transition-all active:scale-95">
                        + Save Box Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div x-show="resetModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="resetModal = false"></div>
            <div class="relative bg-slate-900 border border-slate-700 rounded-2xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-white">Reset Password for @<span x-text="resetUsername"></span></h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" :value="resetUserId">

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">New Password</label>
                        <input type="password" name="new_password" required minlength="4" placeholder="••••••••••••" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="resetModal = false" class="px-3 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-slate-950 font-bold text-xs rounded-xl">Save in MySQL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
