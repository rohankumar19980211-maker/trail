<?php
// cart.php - Wholesale Cart Review & Quote Checkout
$pageTitle = 'Wholesale Cart & Procurement Quote';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_login('employee');
require_once __DIR__ . '/includes/header.php';

$orderPlaced = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order']) && $pdo) {
    $cartJson = $_POST['cart_data'] ?? '[]';
    $cart = json_decode($cartJson, true);

    $company = trim($_POST['company_name'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? ($currentUser['name'] ?? 'Procurement Officer'));
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['delivery_city'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($cart) || !is_array($cart)) {
        $error = "Your cart is empty. Please add products before submitting.";
    } else {
        try {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));
            $totalQty = 0;
            $subtotal = 0;
            $finalAmount = 0;

            foreach ($cart as $item) {
                $qty = intval($item['quantity']);
                $totalQty += $qty;
                $subtotal += (floatval($item['unitPrice']) * $qty);
                $finalAmount += floatval($item['totalPrice']);
            }

            $totalSavings = $subtotal - $finalAmount;
            $shippingNotes = "Company: {$company} | Contact: {$contactName} | Phone: {$phone} | City: {$city} | Notes: {$notes}";

            $userId = $currentUser['id'] ?? null;
            $username = $currentUser['username'] ?? ($contactName ?: 'guest_buyer');

            // 1. Insert Order
            $stmt = $pdo->prepare("
                INSERT INTO `orders` (`order_number`, `user_id`, `username`, `user_name`, `subtotal_amount_inr`, `total_savings_inr`, `total_amount_inr`, `total_quantity`, `shipping_notes`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Processing')
            ");
            $stmt->execute([$orderNumber, $userId, $username, $contactName, $subtotal, $totalSavings, $finalAmount, $totalQty, $shippingNotes]);
            $orderId = $pdo->lastInsertId();

            // 2. Insert Order Items
            $itemStmt = $pdo->prepare("
                INSERT INTO `order_items` (`order_id`, `product_id`, `sku`, `title`, `unit_price`, `discounted_unit_price`, `quantity`, `tier_discount_percent`, `total_price`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart as $item) {
                $itemStmt->execute([
                    $orderId,
                    intval($item['id'] ?? 1),
                    $item['sku'],
                    $item['title'],
                    floatval($item['unitPrice']),
                    floatval($item['discountedUnitPrice']),
                    intval($item['quantity']),
                    intval($item['discountPercent']),
                    floatval($item['totalPrice'])
                ]);
            }

            $orderPlaced = [
                'order_number' => $orderNumber,
                'total_amount' => $finalAmount,
                'total_quantity' => $totalQty,
                'total_savings' => $totalSavings
            ];
        } catch (\Exception $e) {
            $error = "Failed to process wholesale order: " . $e->getMessage();
        }
    }
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ localCartData: '' }" x-init="localCartData = JSON.stringify(cartItems)">

    <?php if ($orderPlaced): ?>
        <!-- Order Confirmation Screen -->
        <div class="max-w-2xl mx-auto bg-slate-800/90 border border-emerald-500/40 rounded-3xl p-8 sm:p-12 text-center shadow-2xl space-y-6"
             x-init="clearCart()">
            <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto text-3xl">
                ✓
            </div>
            <div>
                <span class="text-xs font-mono uppercase bg-emerald-500/20 text-emerald-400 px-3 py-1 rounded-full font-bold">Wholesale Quote Confirmed</span>
                <h1 class="text-3xl font-black text-white mt-3">Order <?= htmlspecialchars($orderPlaced['order_number']) ?> Placed!</h1>
                <p class="text-slate-400 text-sm mt-2">Saved directly into MySQL orders table. Our dispatch logistics desk has been notified.</p>
            </div>

            <div class="bg-slate-950/60 rounded-2xl p-5 border border-slate-700/60 text-left space-y-2 text-sm">
                <div class="flex justify-between text-slate-400">
                    <span>Total Boxes Ordered:</span>
                    <span class="text-white font-bold"><?= number_format($orderPlaced['total_quantity']) ?> units</span>
                </div>
                <div class="flex justify-between text-emerald-400">
                    <span>Bulk Tier Savings:</span>
                    <span class="font-bold">₹<?= number_format($orderPlaced['total_savings'], 2) ?></span>
                </div>
                <div class="flex justify-between text-base font-bold text-white pt-2 border-t border-slate-800">
                    <span>Total Invoice (Excl. GST):</span>
                    <span class="text-amber-400 font-black text-lg">₹<?= number_format($orderPlaced['total_amount'], 2) ?></span>
                </div>
            </div>

            <div class="flex justify-center space-x-4 pt-4">
                <a href="index.php" class="px-6 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-sm shadow">
                    Continue Browsing Catalog
                </a>
                <a href="orders.php" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold rounded-xl text-sm">
                    View Orders History
                </a>
            </div>
        </div>
    <?php else: ?>

        <div class="mb-8">
            <h1 class="text-3xl font-black text-white tracking-tight">Wholesale Procurement Cart & Quote</h1>
            <p class="text-xs text-slate-400 mt-1">Review items, adjust quantities for higher tier discounts, and submit order</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-sm font-semibold">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left: Cart Items List -->
            <div class="lg:col-span-2 space-y-4">
                <template x-if="cartItems.length === 0">
                    <div class="bg-slate-800/60 border border-slate-700/80 rounded-2xl p-12 text-center text-slate-400">
                        <div class="text-5xl mb-3">📦</div>
                        <h3 class="text-lg font-bold text-white">Your Wholesale Cart is Empty</h3>
                        <p class="text-xs text-slate-400 mt-1">Add items from our 360+ box catalog to configure bulk volume pricing</p>
                        <a href="index.php" class="inline-block mt-6 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-sm shadow">
                            Browse Box Catalog
                        </a>
                    </div>
                </template>

                <template x-for="(item, idx) in cartItems" :key="item.sku">
                    <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
                        <div class="flex-1">
                            <span class="text-[10px] font-mono uppercase bg-slate-900 text-amber-400 px-2 py-0.5 rounded border border-slate-700" x-text="item.sku"></span>
                            <h3 class="text-white font-bold text-base mt-1" x-text="item.title"></h3>
                            <p class="text-xs text-slate-400 font-mono" x-text="`Dimensions: ${item.boxSize} • ${item.category}`"></p>
                            <p class="text-xs text-slate-400 mt-1" x-text="`Base Unit Price: ₹${item.unitPrice.toFixed(2)}`"></p>
                        </div>

                        <div class="flex items-center space-x-6 w-full sm:w-auto justify-between sm:justify-end">
                            <div class="text-left sm:text-center">
                                <label class="block text-[10px] text-slate-400 font-bold uppercase mb-1">Quantity</label>
                                <input type="number" 
                                       :value="item.quantity" 
                                       @input="updateItemQty(idx, $event.target.value); localCartData = JSON.stringify(cartItems)"
                                       min="10" 
                                       step="10" 
                                       class="w-24 px-2 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-center font-bold text-white text-sm outline-none focus:border-amber-500">
                                <div class="text-[10px] text-emerald-400 font-bold mt-1" x-show="item.discountPercent > 0" x-text="`-${item.discountPercent}% Tier Applied`"></div>
                            </div>

                            <div class="text-right">
                                <div class="text-[10px] text-slate-400" x-show="item.discountPercent > 0">
                                    <span class="line-through" x-text="`₹${(item.unitPrice * item.quantity).toFixed(2)}`"></span>
                                </div>
                                <div class="text-lg font-black text-amber-400" x-text="`₹${item.totalPrice.toFixed(2)}`"></div>
                                <button @click="removeFromCart(idx); localCartData = JSON.stringify(cartItems)" class="text-xs text-rose-400 hover:text-rose-300 font-semibold mt-1">Remove</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Right: Order Submission Form -->
            <div class="lg:col-span-1" x-show="cartItems.length > 0">
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 shadow-xl space-y-6 sticky top-24">
                    <h3 class="text-base font-bold text-white border-b border-slate-700 pb-3">Procurement Details</h3>

                    <form method="POST" class="space-y-4" @submit="localCartData = JSON.stringify(cartItems)">
                        <input type="hidden" name="submit_order" value="1">
                        <input type="hidden" name="cart_data" :value="JSON.stringify(cartItems)">

                        <div>
                            <label class="block text-xs text-slate-300 font-semibold mb-1">Company / Business Name</label>
                            <input type="text" name="company_name" required placeholder="e.g. Acme Logistics Pvt Ltd" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-300 font-semibold mb-1">Contact Officer Name</label>
                            <input type="text" name="contact_name" value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" required placeholder="e.g. Rohan Sharma" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-300 font-semibold mb-1">Phone / WhatsApp</label>
                            <input type="tel" name="phone" required placeholder="+91 98765 43210" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-300 font-semibold mb-1">Delivery Destination (City / State)</label>
                            <input type="text" name="delivery_city" required placeholder="e.g. Mumbai, Maharashtra" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-300 font-semibold mb-1">Special Packaging Instructions</label>
                            <textarea name="notes" rows="2" placeholder="Custom pallet shrink-wrapping, dock appointment required..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs outline-none focus:border-amber-500"></textarea>
                        </div>

                        <!-- Summary Breakdown -->
                        <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-700/60 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-400">
                                <span>Subtotal:</span>
                                <span class="text-white font-medium" x-text="`₹${cartSubtotal.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-emerald-400">
                                <span>Bulk Volume Savings:</span>
                                <span class="font-bold" x-text="`-₹${cartSavings.toFixed(2)}`"></span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-white pt-2 border-t border-slate-800">
                                <span>Net Total (Excl. GST):</span>
                                <span class="text-amber-400 font-black text-lg" x-text="`₹${cartFinalAmount.toFixed(2)}`"></span>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl shadow-lg transition-all transform active:scale-95 text-sm flex items-center justify-center space-x-2">
                            <span>Submit Wholesale Quote</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </form>
                </div>
            </div>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
