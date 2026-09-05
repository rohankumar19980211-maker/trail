<?php
// includes/footer.php - Wholesale Trust Badges, Sliding Cart Drawer, & Botpress Integration
?>
    <!-- Sliding Cart Drawer (Alpine.js) -->
    <div x-show="isCartOpen" class="relative z-50" x-cloak>
        <div x-show="isCartOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" 
             @click="isCartOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="isCartOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md bg-slate-900 border-l border-slate-800 shadow-2xl flex flex-col">
                
                <!-- Drawer Header -->
                <div class="p-6 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-amber-500/20 text-amber-500 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Wholesale Cart & Quote</h2>
                            <p class="text-xs text-slate-400" x-text="`${cartItems.length} unique box SKUs selected`"></p>
                        </div>
                    </div>
                    <button @click="isCartOpen = false" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Drawer Body / Item List -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <template x-if="cartItems.length === 0">
                        <div class="py-16 text-center text-slate-400">
                            <div class="text-5xl mb-3">📦</div>
                            <p class="font-medium text-slate-300">Your wholesale cart is empty</p>
                            <p class="text-xs text-slate-500 mt-1">Explore our 360+ box catalog to add bulk quantities</p>
                            <button @click="isCartOpen = false" class="mt-6 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs font-semibold rounded-lg">Browse Catalog</button>
                        </div>
                    </template>

                    <template x-for="(item, index) in cartItems" :key="item.sku">
                        <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-4 flex flex-col space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-[10px] font-mono uppercase bg-slate-900 text-amber-400 px-1.5 py-0.5 rounded border border-slate-700" x-text="item.sku"></span>
                                    <h4 class="font-semibold text-white text-sm mt-1" x-text="item.title"></h4>
                                    <p class="text-xs text-slate-400" x-text="`Size: ${item.boxSize} • ${item.category}`"></p>
                                </div>
                                <button @click="removeFromCart(index)" class="text-slate-500 hover:text-rose-400 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-700/60 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="text-slate-400">Qty:</span>
                                    <input type="number" 
                                           :value="item.quantity" 
                                           @input="updateItemQty(index, $event.target.value)" 
                                           min="10" 
                                           step="10"
                                           class="w-20 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-center font-bold text-white focus:border-amber-500 outline-none">
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-slate-400" x-show="item.discountPercent > 0">
                                        <span class="line-through" x-text="`₹${(item.unitPrice * item.quantity).toFixed(2)}`"></span>
                                        <span class="text-emerald-400 font-bold ml-1" x-text="`(-${item.discountPercent}%)`"></span>
                                    </div>
                                    <div class="text-base font-black text-amber-400" x-text="`₹${item.totalPrice.toFixed(2)}`"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Drawer Footer & Order Trigger -->
                <div class="p-6 border-t border-slate-800 bg-slate-950/60 space-y-3" x-show="cartItems.length > 0">
                    <div class="space-y-1.5 text-xs text-slate-400">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="text-slate-200 font-medium" x-text="`₹${cartSubtotal.toFixed(2)}`"></span>
                        </div>
                        <div class="flex justify-between text-emerald-400">
                            <span>Bulk Tier Savings:</span>
                            <span class="font-bold" x-text="`-₹${cartSavings.toFixed(2)}`"></span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-white pt-2 border-t border-slate-800">
                            <span>Total (Excl. GST):</span>
                            <span class="text-amber-400 text-lg font-black" x-text="`₹${cartFinalAmount.toFixed(2)}`"></span>
                        </div>
                    </div>

                    <a href="cart.php" class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-xl shadow-lg transition-all flex items-center justify-center space-x-2 text-sm">
                        <span>Review & Submit Wholesale Quote</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="toastMessage" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-4 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-4 opacity-0"
         class="fixed bottom-6 right-6 z-50 bg-slate-800 text-white border border-amber-500/50 shadow-2xl rounded-xl px-4 py-3 flex items-center space-x-3 text-sm" 
         x-cloak>
        <span class="text-amber-500 text-lg">✓</span>
        <span x-text="toastMessage" class="font-medium"></span>
    </div>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800/80 mt-auto text-slate-400 text-xs">
        <div class="max-w-7xl mx-auto px-4 py-10 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <span class="text-lg font-black text-white">BOX<span class="text-amber-500">RETAIL</span></span>
                </div>
                <p class="text-slate-400 leading-relaxed">
                    India's leading bulk corrugated box manufacturer & wholesale packaging partner. Direct factory supply for e-commerce, warehousing, and logistics.
                </p>
                <p class="text-[11px] text-slate-500">
                    Database: <span class="text-emerald-400 font-mono">MySQL Live</span> • Server: <span class="text-slate-300 font-mono">PHP 8.x Native</span>
                </p>
            </div>

            <div>
                <h4 class="text-white font-bold text-sm mb-3">Product Categories</h4>
                <ul class="space-y-2">
                    <li><a href="index.php?category=Corrugated+Cartons" class="hover:text-amber-400">Corrugated Cartons</a></li>
                    <li><a href="index.php?category=Heavy-Duty+Moving" class="hover:text-amber-400">Heavy-Duty Moving Boxes</a></li>
                    <li><a href="index.php?category=Corrugated+Mailers" class="hover:text-amber-400">Corrugated Mailers</a></li>
                    <li><a href="index.php?category=Die-Cut+/+Gift+Boxes" class="hover:text-amber-400">Die-Cut & Gift Boxes</a></li>
                    <li><a href="index.php?category=Telescopic+&+Special" class="hover:text-amber-400">Telescopic Packaging</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold text-sm mb-3">Enterprise Portals</h4>
                <ul class="space-y-2">
                    <li><a href="login.php" class="hover:text-amber-400">Employee Portal Sign-In</a></li>
                    <li><a href="admin.php" class="hover:text-amber-400">Master Administrator Hub</a></li>
                    <li><a href="orders.php" class="hover:text-amber-400">Wholesale Order History</a></li>
                    <li><a href="install.php" class="hover:text-amber-400">Database Setup / Auto-Sync</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold text-sm mb-3">Wholesale Guarantee</h4>
                <div class="p-3 bg-slate-900 border border-slate-800 rounded-xl space-y-2">
                    <div class="flex items-center space-x-2 text-slate-200">
                        <span class="text-amber-400">✓</span>
                        <span class="font-semibold">Tier Discounts up to 25%</span>
                    </div>
                    <div class="flex items-center space-x-2 text-slate-200">
                        <span class="text-amber-400">✓</span>
                        <span class="font-semibold">GST Invoicing (Tax Credit)</span>
                    </div>
                    <div class="flex items-center space-x-2 text-slate-200">
                        <span class="text-amber-400">✓</span>
                        <span class="font-semibold">Pan-India Freight Logistics</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800/60 py-4 text-center text-slate-500 text-[11px]">
            © <?= date('Y') ?> BOXRETAIL Packaging Solutions Pvt. Ltd. All rights reserved. Built with Native PHP 8.x & MySQL.
        </div>
    </footer>

    <!-- Alpine.js Global Store Script -->
    <script>
        function boxStore() {
            return {
                isCartOpen: false,
                cartItems: [],
                toastMessage: '',

                initCart() {
                    const saved = localStorage.getItem('boxretail_cart_items');
                    if (saved) {
                        try {
                            this.cartItems = JSON.parse(saved);
                        } catch(e) {
                            this.cartItems = [];
                        }
                    }
                },

                saveCart() {
                    localStorage.setItem('boxretail_cart_items', JSON.stringify(this.cartItems));
                },

                get cartTotalCount() {
                    return this.cartItems.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
                },

                get cartSubtotal() {
                    return this.cartItems.reduce((sum, item) => sum + (parseFloat(item.unitPrice) * parseInt(item.quantity)), 0);
                },

                get cartSavings() {
                    return this.cartItems.reduce((sum, item) => {
                        const original = parseFloat(item.unitPrice) * parseInt(item.quantity);
                        return sum + (original - parseFloat(item.totalPrice));
                    }, 0);
                },

                get cartFinalAmount() {
                    return this.cartItems.reduce((sum, item) => sum + parseFloat(item.totalPrice), 0);
                },

                addToCart(product, quantity = 100) {
                    const qty = parseInt(quantity) || 100;
                    const index = this.cartItems.findIndex(item => item.sku === product.sku);
                    
                    const calculated = this.calculateTierDiscount(product.unitPrice, qty, product.discountTiers);

                    if (index > -1) {
                        const newQty = this.cartItems[index].quantity + qty;
                        const recalculated = this.calculateTierDiscount(product.unitPrice, newQty, product.discountTiers);
                        this.cartItems[index].quantity = newQty;
                        this.cartItems[index].discountPercent = recalculated.discountPercent;
                        this.cartItems[index].discountedUnitPrice = recalculated.discountedUnitPrice;
                        this.cartItems[index].totalPrice = recalculated.totalPrice;
                    } else {
                        this.cartItems.push({
                            id: product.id,
                            sku: product.sku,
                            title: product.title,
                            boxSize: product.boxSize,
                            category: product.category,
                            unitPrice: parseFloat(product.unitPrice),
                            quantity: qty,
                            discountPercent: calculated.discountPercent,
                            discountedUnitPrice: calculated.discountedUnitPrice,
                            totalPrice: calculated.totalPrice,
                            discountTiers: product.discountTiers || []
                        });
                    }

                    this.saveCart();
                    this.showToast(`Added ${qty}x ${product.title} to cart!`);
                },

                updateItemQty(index, newQty) {
                    const qty = Math.max(10, parseInt(newQty) || 10);
                    const item = this.cartItems[index];
                    if (!item) return;

                    const calculated = this.calculateTierDiscount(item.unitPrice, qty, item.discountTiers);
                    item.quantity = qty;
                    item.discountPercent = calculated.discountPercent;
                    item.discountedUnitPrice = calculated.discountedUnitPrice;
                    item.totalPrice = calculated.totalPrice;

                    this.saveCart();
                },

                removeFromCart(index) {
                    this.cartItems.splice(index, 1);
                    this.saveCart();
                    this.showToast('Item removed from cart');
                },

                clearCart() {
                    this.cartItems = [];
                    this.saveCart();
                },

                calculateTierDiscount(unitPrice, quantity, tiers) {
                    const price = parseFloat(unitPrice) || 0;
                    const qty = parseInt(quantity) || 0;
                    let bestDiscount = 0;

                    const sortedTiers = (tiers || [
                        { minQuantity: 100, discountPercent: 5 },
                        { minQuantity: 300, discountPercent: 10 },
                        { minQuantity: 500, discountPercent: 18 },
                        { minQuantity: 600, discountPercent: 20 },
                        { minQuantity: 1000, discountPercent: 25 },
                    ]).sort((a, b) => b.minQuantity - a.minQuantity);

                    for (const tier of sortedTiers) {
                        if (qty >= tier.minQuantity) {
                            bestDiscount = tier.discountPercent;
                            break;
                        }
                    }

                    const discountedUnitPrice = price * (1 - bestDiscount / 100);
                    const totalPrice = discountedUnitPrice * qty;

                    return {
                        discountPercent: bestDiscount,
                        discountedUnitPrice: discountedUnitPrice,
                        totalPrice: totalPrice,
                        savings: (price * qty) - totalPrice
                    };
                },

                showToast(msg) {
                    this.toastMessage = msg;
                    setTimeout(() => { this.toastMessage = ''; }, 3000);
                }
            }
        }
    </script>

    <!-- Botpress AI Webchat Integration -->
    <script src="https://cdn.botpress.cloud/webchat/v2/inject.js"></script>
    <script src="https://files.bpcontent.cloud/2026/02/10/06/20260210061543-7L3O4Z5F.js" defer></script>
</body>
</html>
