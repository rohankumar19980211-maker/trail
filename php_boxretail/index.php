<?php
// index.php - 360+ Box Product Wholesale Catalog & Tier Calculator
$pageTitle = 'Wholesale Box Catalog & Bulk Tier Calculator';
require_once __DIR__ . '/includes/header.php';

// Fetch products directly from MySQL PDO
$products = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM `products` ORDER BY `id` ASC");
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $products[] = [
                'id' => $r['id'],
                'sku' => $r['sku'],
                'title' => $r['title'],
                'boxSize' => $r['box_size'],
                'category' => $r['category'],
                'sizeCategory' => $r['size_category'],
                'length' => floatval($r['length']),
                'width' => floatval($r['width']),
                'height' => floatval($r['height']),
                'wallStrength' => $r['wall_strength'],
                'description' => $r['description'],
                'unitPrice' => floatval($r['price_inr']),
                'availableQuantity' => intval($r['stock_qty']),
                'image' => $r['image_url'],
                'discountTiers' => json_decode($r['discount_tiers_json'] ?? '[]', true) ?: [
                    ['minQuantity' => 100, 'discountPercent' => 5],
                    ['minQuantity' => 300, 'discountPercent' => 10],
                    ['minQuantity' => 500, 'discountPercent' => 18],
                    ['minQuantity' => 600, 'discountPercent' => 20],
                    ['minQuantity' => 1000, 'discountPercent' => 25],
                ]
            ];
        }
    } catch (\Exception $e) {}
}

// Fallback to JSON if database empty
if (empty($products) && file_exists(__DIR__ . '/data/products.json')) {
    $raw = file_get_contents(__DIR__ . '/data/products.json');
    $data = json_decode($raw, true);
    if (is_array($data)) {
        foreach ($data as $p) {
            $products[] = [
                'id' => $p['_id'] ?? $p['id'] ?? 1,
                'sku' => $p['sku'] ?? 'BOX-GEN-001',
                'title' => $p['title'],
                'boxSize' => $p['boxSize'],
                'category' => $p['category'],
                'sizeCategory' => $p['sizeCategory'] ?? 'Medium',
                'length' => floatval($p['length'] ?? 12),
                'width' => floatval($p['width'] ?? 12),
                'height' => floatval($p['height'] ?? 12),
                'wallStrength' => $p['wallStrength'] ?? 'ECT-32',
                'description' => $p['description'],
                'unitPrice' => floatval($p['price_inr'] ?? ($p['unitPrice'] ? $p['unitPrice'] * 45 : 45.00)),
                'availableQuantity' => intval($p['availableQuantity'] ?? 500),
                'image' => $p['image'],
                'discountTiers' => $p['discountTiers'] ?? []
            ];
        }
    }
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8" 
      x-data="catalogApp(<?= htmlspecialchars(json_encode($products), ENT_QUOTES, 'UTF-8') ?>)">

    <!-- Hero Section with Live Stats -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-amber-950/30 border border-slate-700/80 p-8 sm:p-12 mb-10 shadow-2xl">
        <div class="max-w-3xl relative z-10 space-y-4">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-400 text-xs font-bold uppercase tracking-wider">
                <span>🏭 Direct Manufacturer Supply</span>
                <span>•</span>
                <span>₹ INR Wholesale Billing</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                Bulk Industrial Packaging <br class="hidden sm:block">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500">Tier Discounts up to 25%</span>
            </h1>
            <p class="text-slate-300 text-base sm:text-lg leading-relaxed">
                Order directly from our factory floor. Choose from over <strong class="text-amber-400 font-semibold" x-text="`${products.length}+`"></strong> standard box dimensions or calculate automated volume discounts for procurement.
            </p>

            <div class="pt-2 flex flex-wrap items-center gap-3">
                <a href="#catalog" class="px-6 py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-xl shadow-lg transition-all transform active:scale-95 text-sm flex items-center space-x-2">
                    <span>Browse 360+ SKUs</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </a>
                <button @click="openQuickCalculator(products[0])" class="px-5 py-3.5 bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold rounded-xl transition-all text-sm flex items-center space-x-2">
                    <span>🧮 Bulk Volume Calculator</span>
                </button>
            </div>
        </div>

        <!-- Background Ambient Accents -->
        <div class="absolute -right-20 -top-20 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Catalog Control Bar (Search, Category Pills, Size Filter) -->
    <div id="catalog" class="space-y-6 mb-8 scroll-mt-24">
        
        <!-- Search and Sort Row -->
        <div class="flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
            
            <!-- Search Bar -->
            <div class="relative flex-1 max-w-xl">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Search by SKU, box title, dimensions (e.g. 12x12x12), or material..."
                       class="w-full pl-11 pr-4 py-3 bg-slate-800/90 border border-slate-700 rounded-xl text-white placeholder-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none text-sm shadow-sm transition-all">
                <button x-show="searchQuery" @click="searchQuery = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white text-xs font-bold">Clear</button>
            </div>

            <!-- Sorting & Total Counter -->
            <div class="flex items-center space-x-4 justify-between">
                <span class="text-xs text-slate-400 font-medium whitespace-nowrap" x-text="`Showing ${filteredProducts.length} of ${products.length} SKUs`"></span>
                <select x-model="sortBy" class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2.5 outline-none focus:border-amber-500">
                    <option value="default">Sort: Default</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="size_asc">Size: Smallest First</option>
                    <option value="size_desc">Size: Largest First</option>
                </select>
            </div>
        </div>

        <!-- Category Pills -->
        <div class="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-thin">
            <template x-for="cat in categories" :key="cat">
                <button @click="selectedCategory = cat" 
                        :class="selectedCategory === cat ? 'bg-amber-500 text-slate-950 font-black shadow-lg shadow-amber-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-medium'"
                        class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all flex items-center space-x-1.5"
                        x-text="cat">
                </button>
            </template>
        </div>

        <!-- Size Category Sub-Filter -->
        <div class="flex items-center space-x-2 text-xs">
            <span class="text-slate-400 font-medium">Box Size:</span>
            <template x-for="sz in sizeFilters" :key="sz">
                <button @click="selectedSize = sz"
                        :class="selectedSize === sz ? 'text-amber-400 font-bold underline' : 'text-slate-400 hover:text-slate-200'"
                        class="px-2 py-1 transition-colors"
                        x-text="sz">
                </button>
            </template>
        </div>
    </div>

    <!-- 360+ Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="p in paginatedProducts" :key="p.sku">
            <div class="group bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 hover:border-amber-500/50 rounded-2xl p-5 shadow-xl transition-all duration-200 flex flex-col justify-between">
                
                <!-- Card Header -->
                <div>
                    <!-- Image & Badges -->
                    <div class="relative h-44 rounded-xl overflow-hidden bg-slate-900 mb-4 border border-slate-700/50 flex items-center justify-center">
                        <img :src="p.image" :alt="p.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        
                        <span class="absolute top-2.5 left-2.5 bg-slate-950/80 backdrop-blur-sm text-amber-400 font-mono text-[10px] font-bold px-2 py-0.5 rounded border border-slate-700/80" x-text="p.sku"></span>
                        <span class="absolute bottom-2.5 right-2.5 bg-emerald-500/90 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded shadow" x-text="`Up to 25% OFF`"></span>
                    </div>

                    <!-- Title & Details -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span class="text-amber-500/90 font-medium" x-text="p.category"></span>
                            <span class="bg-slate-700/50 px-1.5 py-0.5 rounded text-[10px]" x-text="p.sizeCategory"></span>
                        </div>
                        <h3 class="text-white font-bold text-base leading-snug line-clamp-1 group-hover:text-amber-400 transition-colors" x-text="p.title"></h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="`Dimensions: ${p.boxSize}`"></p>
                        <p class="text-xs text-slate-400 font-mono" x-text="`Wall Strength: ${p.wallStrength || 'ECT-32 Single-Wall'}`"></p>
                    </div>

                    <!-- Tier Discount Preview Pills -->
                    <div class="mt-3 pt-3 border-t border-slate-700/60 grid grid-cols-3 gap-1 text-[10px] text-center">
                        <div class="bg-slate-900/60 p-1 rounded border border-slate-700/40">
                            <div class="text-slate-400">100+ pcs</div>
                            <div class="text-emerald-400 font-bold">-5%</div>
                        </div>
                        <div class="bg-slate-900/60 p-1 rounded border border-slate-700/40">
                            <div class="text-slate-400">500+ pcs</div>
                            <div class="text-emerald-400 font-bold">-18%</div>
                        </div>
                        <div class="bg-slate-900/60 p-1 rounded border border-slate-700/40">
                            <div class="text-slate-400">1000+ pcs</div>
                            <div class="text-emerald-400 font-bold">-25%</div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer & Pricing -->
                <div class="mt-5 pt-3 border-t border-slate-700/80 space-y-3">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <span class="text-[11px] text-slate-400">Starting at:</span>
                            <div class="text-xl font-black text-amber-400 leading-none" x-text="`₹${p.unitPrice.toFixed(2)}`"></div>
                            <span class="text-[10px] text-slate-500">per piece (excl. GST)</span>
                        </div>
                        <div class="text-right text-[11px] text-slate-400">
                            <span class="text-emerald-400 font-semibold" x-text="`In Stock (${p.availableQuantity || 500})`"></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="openQuickCalculator(p)" class="w-full py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl transition-all flex items-center justify-center space-x-1">
                            <span>🧮 Tier Calc</span>
                        </button>
                        <button @click="addToCart(p, 100)" class="w-full py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 text-xs font-bold rounded-xl shadow transition-all active:scale-95 flex items-center justify-center space-x-1">
                            <span>+ 100 Qty</span>
                        </button>
                    </div>

                    <button @click="open3dModal(p)" class="w-full py-1.5 bg-slate-900/60 hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-[11px] rounded-lg border border-slate-700/60 flex items-center justify-center space-x-1 transition-colors">
                        <span>📦 View Proportional 3D Geometry</span>
                    </button>
                </div>

            </div>
        </template>
    </div>

    <!-- Pagination Controls -->
    <div class="mt-10 flex items-center justify-between border-t border-slate-800 pt-6" x-show="totalPages > 1">
        <button @click="currentPage = Math.max(1, currentPage - 1)" 
                :disabled="currentPage === 1"
                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-200 text-xs font-semibold hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed">
            ← Previous
        </button>

        <span class="text-xs text-slate-400" x-text="`Page ${currentPage} of ${totalPages}`"></span>

        <button @click="currentPage = Math.min(totalPages, currentPage + 1)" 
                :disabled="currentPage === totalPages"
                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-200 text-xs font-semibold hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed">
            Next →
        </button>
    </div>

    <!-- Bulk Tier Discount Calculator Modal -->
    <div x-show="isCalcModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="isCalcModalOpen = false"></div>

            <div class="relative bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-6">
                
                <div class="flex items-start justify-between border-b border-slate-800 pb-4">
                    <div>
                        <span class="text-xs font-mono uppercase bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded border border-amber-500/30" x-text="calcProduct?.sku"></span>
                        <h3 class="text-lg font-bold text-white mt-1" x-text="calcProduct?.title"></h3>
                        <p class="text-xs text-slate-400" x-text="`Base Unit Price: ₹${calcProduct?.unitPrice?.toFixed(2)}`"></p>
                    </div>
                    <button @click="isCalcModalOpen = false" class="text-slate-400 hover:text-white p-1">✕</button>
                </div>

                <!-- Quantity Slider & Input -->
                <div class="space-y-3 bg-slate-950/60 p-4 rounded-xl border border-slate-800">
                    <div class="flex justify-between items-center text-xs">
                        <label class="font-bold text-slate-300">Order Quantity (Pieces):</label>
                        <input type="number" 
                               x-model.number="calcQuantity" 
                               min="10" 
                               max="10000" 
                               step="10"
                               class="w-24 bg-slate-800 border border-slate-700 rounded-lg px-2 py-1 text-center font-black text-amber-400 text-base outline-none focus:border-amber-500">
                    </div>

                    <input type="range" 
                           x-model.number="calcQuantity" 
                           min="10" 
                           max="2000" 
                           step="10"
                           class="w-full accent-amber-500 bg-slate-800 h-2 rounded-lg cursor-pointer">

                    <!-- Tier Threshold Indicators -->
                    <div class="grid grid-cols-5 gap-1 text-[10px] text-center pt-2">
                        <div :class="calcQuantity >= 100 ? 'text-emerald-400 font-bold' : 'text-slate-500'">100+ (5%)</div>
                        <div :class="calcQuantity >= 300 ? 'text-emerald-400 font-bold' : 'text-slate-500'">300+ (10%)</div>
                        <div :class="calcQuantity >= 500 ? 'text-emerald-400 font-bold' : 'text-slate-500'">500+ (18%)</div>
                        <div :class="calcQuantity >= 600 ? 'text-emerald-400 font-bold' : 'text-slate-500'">600+ (20%)</div>
                        <div :class="calcQuantity >= 1000 ? 'text-emerald-400 font-bold' : 'text-slate-500'">1000+ (25%)</div>
                    </div>
                </div>

                <!-- Real-Time Calculation Result Breakdown -->
                <div class="bg-gradient-to-br from-slate-950 to-slate-900 border border-amber-500/30 rounded-xl p-5 space-y-3">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>Original Unit Price:</span>
                        <span class="font-mono text-slate-300" x-text="`₹${calcProduct?.unitPrice?.toFixed(2)}`"></span>
                    </div>

                    <div class="flex justify-between text-xs items-center">
                        <span class="text-emerald-400 font-semibold">Bulk Volume Discount:</span>
                        <span class="bg-emerald-500/20 text-emerald-400 font-bold px-2 py-0.5 rounded text-xs" 
                              x-text="`-${calcResults.discountPercent}% OFF`"></span>
                    </div>

                    <div class="flex justify-between text-xs text-slate-300">
                        <span>Effective Unit Price:</span>
                        <span class="font-bold text-white text-sm" x-text="`₹${calcResults.discountedUnitPrice?.toFixed(2)}`"></span>
                    </div>

                    <div class="flex justify-between text-xs text-emerald-400 font-medium">
                        <span>Total Bulk Savings:</span>
                        <span class="font-bold" x-text="`₹${calcResults.savings?.toFixed(2)}`"></span>
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex justify-between items-baseline">
                        <span class="text-sm font-bold text-slate-200">Net Wholesale Total:</span>
                        <div class="text-2xl font-black text-amber-400" x-text="`₹${calcResults.totalPrice?.toFixed(2)}`"></div>
                    </div>
                </div>

                <!-- Add to Cart with Calculated Quantity -->
                <button @click="addToCart(calcProduct, calcQuantity); isCalcModalOpen = false" 
                        class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl shadow-lg transition-all transform active:scale-95 flex items-center justify-center space-x-2">
                    <span>Add <span x-text="`${calcQuantity}x`"></span> to Wholesale Cart</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Proportional 3D Box Geometry Preview Modal -->
    <div x-show="is3dModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="is3dModalOpen = false"></div>

            <div class="relative bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-6 text-center">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">📦 Proportional 3D Dimension Preview</h3>
                    <button @click="is3dModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <div class="py-6 flex flex-col items-center justify-center">
                    <div class="w-48 h-40 bg-gradient-to-br from-amber-700 to-amber-900 border-2 border-amber-500/60 rounded-xl shadow-2xl transform rotate-6 hover:rotate-0 transition-transform duration-500 flex flex-col items-center justify-center text-slate-950 p-4 relative">
                        <span class="font-black text-white text-base tracking-wider" x-text="box3dProduct?.sku"></span>
                        <span class="text-xs text-amber-200 font-mono mt-1" x-text="box3dProduct?.boxSize"></span>
                        <div class="absolute -bottom-3 bg-slate-900 text-amber-400 border border-slate-700 px-2 py-0.5 rounded text-[10px] font-bold">
                            Heavy Duty Kraft ECT-32
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-xs bg-slate-950/60 p-3 rounded-xl border border-slate-800 text-slate-300">
                    <div>Length: <strong class="text-white" x-text="`${box3dProduct?.length}&quot;`"></strong></div>
                    <div>Width: <strong class="text-white" x-text="`${box3dProduct?.width}&quot;`"></strong></div>
                    <div>Height: <strong class="text-white" x-text="`${box3dProduct?.height}&quot;`"></strong></div>
                </div>

                <button @click="openQuickCalculator(box3dProduct); is3dModalOpen = false" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs">
                    Open Tier Discount Calculator
                </button>
            </div>
        </div>
    </div>

</main>

<script>
    function catalogApp(initialProducts) {
        return {
            products: initialProducts || [],
            searchQuery: '',
            selectedCategory: 'All Categories',
            selectedSize: 'All Sizes',
            sortBy: 'default',
            currentPage: 1,
            pageSize: 24,

            // Modals state
            isCalcModalOpen: false,
            calcProduct: null,
            calcQuantity: 100,

            is3dModalOpen: false,
            box3dProduct: null,

            categories: [
                'All Categories',
                'Corrugated Cartons',
                'Heavy-Duty Moving',
                'Corrugated Mailers',
                'Die-Cut / Gift Boxes',
                'Telescopic & Special'
            ],

            sizeFilters: ['All Sizes', 'Small', 'Medium', 'Large', 'Extra Large'],

            get filteredProducts() {
                const query = this.searchQuery.toLowerCase().trim();
                return this.products.filter(p => {
                    // Category filter
                    if (this.selectedCategory !== 'All Categories' && p.category !== this.selectedCategory) {
                        return false;
                    }
                    // Size filter
                    if (this.selectedSize !== 'All Sizes' && p.sizeCategory !== this.selectedSize) {
                        return false;
                    }
                    // Search query
                    if (query) {
                        const matchTitle = (p.title || '').toLowerCase().includes(query);
                        const matchSku = (p.sku || '').toLowerCase().includes(query);
                        const matchSize = (p.boxSize || '').toLowerCase().includes(query);
                        const matchDesc = (p.description || '').toLowerCase().includes(query);
                        if (!matchTitle && !matchSku && !matchSize && !matchDesc) return false;
                    }
                    return true;
                }).sort((a, b) => {
                    if (this.sortBy === 'price_asc') return a.unitPrice - b.unitPrice;
                    if (this.sortBy === 'price_desc') return b.unitPrice - a.unitPrice;
                    if (this.sortBy === 'size_asc') return (a.length * a.width * a.height) - (b.length * b.width * b.height);
                    if (this.sortBy === 'size_desc') return (b.length * b.width * b.height) - (a.length * a.width * a.height);
                    return 0;
                });
            },

            get totalPages() {
                return Math.ceil(this.filteredProducts.length / this.pageSize) || 1;
            },

            get paginatedProducts() {
                const start = (this.currentPage - 1) * this.pageSize;
                return this.filteredProducts.slice(start, start + this.pageSize);
            },

            openQuickCalculator(product) {
                this.calcProduct = product;
                this.calcQuantity = 100;
                this.isCalcModalOpen = true;
            },

            open3dModal(product) {
                this.box3dProduct = product;
                this.is3dModalOpen = true;
            },

            get calcResults() {
                if (!this.calcProduct) return {};
                return this.calculateTierDiscount(this.calcProduct.unitPrice, this.calcQuantity, this.calcProduct.discountTiers);
            }
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
