<?php
// orders.php - Wholesale Order & Quote Tracking
$pageTitle = 'Wholesale Orders & Quotes';
require_once __DIR__ . '/includes/header.php';

// Require Employee or Admin login
require_login('employee');

// Handle Order Status Update (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin() && isset($_POST['update_status']) && $pdo) {
    $orderId = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? 'Processing';
    if ($orderId > 0 && in_array($status, ['Processing', 'Shipped', 'Delivered', 'Cancelled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$status, $orderId]);
        } catch (\Exception $e) {}
    }
}

// Fetch Orders from MySQL
$orders = [];
if ($db_connected && $pdo) {
    try {
        if (is_admin()) {
            $stmt = $pdo->query("SELECT * FROM `orders` ORDER BY `id` DESC");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `username` = ? OR `user_id` = ? ORDER BY `id` DESC");
            $stmt->execute([$currentUser['username'], $currentUser['id'] ?? 0]);
        }
        $orders = $stmt->fetchAll();
    } catch (\Exception $e) {}
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">
                <span>📦 B2B Procurement Desk</span>
                <span>•</span>
                <span><?= is_admin() ? 'All Enterprise Quotes' : 'Your Submitted Orders' ?></span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">Wholesale Orders & Quotes</h1>
            <p class="text-xs text-slate-400 mt-1">Directly querying MySQL database `orders` table</p>
        </div>

        <a href="index.php" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs shadow flex items-center space-x-1.5 transition-colors">
            <span>+ Place New Quote</span>
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="bg-slate-800/60 border border-slate-700/80 rounded-2xl p-12 text-center text-slate-400">
            <div class="text-5xl mb-3">📋</div>
            <h3 class="text-lg font-bold text-white">No Wholesale Orders Yet</h3>
            <p class="text-xs text-slate-400 mt-1">Configure bulk quantities on the catalog and submit your first procurement quote</p>
            <a href="index.php" class="inline-block mt-6 px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-amber-400 font-bold rounded-xl text-xs">
                Explore Box Catalog
            </a>
        </div>
    <?php else: ?>
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 uppercase font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">Purchaser / Company</th>
                            <th class="py-3 px-4">Total Boxes</th>
                            <th class="py-3 px-4">Bulk Savings</th>
                            <th class="py-3 px-4">Invoice Amount</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <?php if (is_admin()): ?>
                                <th class="py-3 px-4 text-right">Update Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-amber-400"><?= htmlspecialchars($o['order_number']) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white"><?= htmlspecialchars($o['user_name'] ?: $o['username']) ?></div>
                                    <div class="text-[10px] text-slate-500 font-mono">@<?= htmlspecialchars($o['username']) ?></div>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-200"><?= number_format($o['total_quantity']) ?> pcs</td>
                                <td class="py-3 px-4 font-bold text-emerald-400">₹<?= number_format($o['total_savings_inr'], 2) ?></td>
                                <td class="py-3 px-4 font-black text-white text-sm">₹<?= number_format($o['total_amount_inr'], 2) ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold 
                                        <?= $o['status'] === 'Delivered' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 
                                           ($o['status'] === 'Shipped' ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30') ?>">
                                        <?= htmlspecialchars($o['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($o['created_at']) ?></td>
                                
                                <?php if (is_admin()): ?>
                                    <td class="py-3 px-4 text-right">
                                        <form method="POST" class="inline-flex items-center space-x-1">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="status" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 text-slate-300 text-[11px] rounded-lg px-2 py-1 outline-none">
                                                <option value="Processing" <?= $o['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="Shipped" <?= $o['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="Delivered" <?= $o['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="Cancelled" <?= $o['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
