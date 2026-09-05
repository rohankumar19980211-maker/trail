import React, { useState, useEffect } from 'react';
import API from '../services/api';
import BulkCalculatorModal from '../components/BulkCalculatorModal';
import { Search, Filter, Box, Tag, Warehouse, ChevronLeft, ChevronRight, Calculator, Check, AlertCircle, Sparkles, ShieldCheck, Truck, Layers } from 'lucide-react';

export default function ProductCatalogPage() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [sizeCategory, setSizeCategory] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  const [selectedProduct, setSelectedProduct] = useState(null);
  const [toastMessage, setToastMessage] = useState('');

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await API.get('/products', {
        params: {
          search,
          category,
          sizeCategory,
          page,
          limit: 24
        }
      });
      setProducts(res.data.products);
      setTotalPages(res.data.totalPages);
      setTotalItems(res.data.total);
    } catch (err) {
      console.error('Error loading catalog:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, [search, category, sizeCategory, page]);

  const handleAddedToCart = () => {
    setToastMessage('Box bulk order added to cart successfully!');
    setTimeout(() => setToastMessage(''), 3500);
  };

  const categories = [
    { label: 'All Boxes', value: '' },
    { label: 'Corrugated Cartons', value: 'Corrugated Cartons' },
    { label: 'Heavy-Duty Moving', value: 'Heavy-Duty Moving' },
    { label: 'Corrugated Mailers', value: 'Corrugated Mailers' },
    { label: 'Die-Cut / Gift', value: 'Die-Cut / Gift Boxes' },
    { label: 'Telescopic & Special', value: 'Telescopic & Special' }
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 font-sans">
      
      {/* Toast Notification */}
      {toastMessage && (
        <div className="fixed bottom-6 right-6 z-50 bg-amber-950 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-amber-500 flex items-center space-x-3 animate-bounce">
          <Check className="w-5 h-5 text-amber-400" />
          <span className="font-bold text-sm">{toastMessage}</span>
        </div>
      )}

      {/* Hero Banner Header */}
      <div className="bg-gradient-to-r from-amber-950 via-amber-900 to-amber-950 text-white rounded-3xl p-6 sm:p-10 shadow-2xl border border-amber-800/80 relative overflow-hidden flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        
        {/* Background glow */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div className="space-y-3 relative z-10 max-w-3xl">
          <div className="inline-flex items-center space-x-2 bg-amber-500/20 px-3 py-1 rounded-full text-amber-300 text-xs font-mono font-bold uppercase tracking-wider border border-amber-500/30">
            <Sparkles className="w-3.5 h-3.5 text-amber-400" />
            <span>Industrial Packaging Direct • Prices in ₹ (INR)</span>
          </div>

          <h1 className="text-3xl sm:text-4xl font-black tracking-tight text-white leading-tight">
            Wholesale Box Inventory & Wholesale Tier Pricing
          </h1>

          <p className="text-sm text-amber-200/90 leading-relaxed">
            Select from 360+ standard box dimensions. Automatic tier discounts apply for orders of <strong>100 (5% OFF)</strong>, <strong>300 (10% OFF)</strong>, <strong>500 (18% OFF)</strong>, and <strong>600+ (20% OFF)</strong>.
          </p>

          <div className="flex flex-wrap items-center gap-4 pt-2 text-xs font-mono text-amber-300">
            <span className="flex items-center"><ShieldCheck className="w-4 h-4 text-emerald-400 mr-1.5" /> ISO 9001 Tested Kraft</span>
            <span className="flex items-center"><Truck className="w-4 h-4 text-emerald-400 mr-1.5" /> Express Dispatch Across India</span>
          </div>
        </div>

        {/* Counter Badge */}
        <div className="bg-white/10 backdrop-blur-xl p-5 rounded-2xl border border-white/20 text-center min-w-[220px] shadow-xl relative z-10 self-stretch lg:self-auto flex flex-col justify-center">
          <span className="block text-4xl font-black text-amber-400 font-mono">{totalItems}</span>
          <span className="text-xs uppercase tracking-wider font-bold text-amber-200 mt-1">Available Box Sizes</span>
          <span className="text-[10px] text-emerald-300 font-medium mt-1">Warehouse Stock Synced</span>
        </div>

      </div>

      {/* Category Pills Bar */}
      <div className="flex items-center space-x-2 overflow-x-auto pb-2 no-scrollbar">
        {categories.map((cat) => (
          <button
            key={cat.value}
            onClick={() => { setCategory(cat.value); setPage(1); }}
            className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition duration-200 flex items-center space-x-1.5 ${
              category === cat.value
                ? 'bg-amber-800 text-white shadow-md border border-amber-600'
                : 'bg-white text-gray-700 hover:bg-amber-50 border border-gray-200'
            }`}
          >
            <Layers className="w-3.5 h-3.5" />
            <span>{cat.label}</span>
          </button>
        ))}
      </div>

      {/* Search & Filters Bar */}
      <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 space-y-4 md:space-y-0 md:flex md:items-center md:justify-between md:space-x-4">
        
        {/* Search input */}
        <div className="relative flex-1">
          <Search className="w-5 h-5 absolute left-3.5 top-2.5 text-gray-400" />
          <input
            type="text"
            placeholder="Search by size (e.g. 12x12x12, 24x18), box title, SKU..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 font-medium"
          />
        </div>

        <div className="flex flex-wrap gap-3 items-center">
          {/* Size Category Filter */}
          <select
            value={sizeCategory}
            onChange={(e) => { setSizeCategory(e.target.value); setPage(1); }}
            className="border border-gray-300 rounded-xl py-2.5 px-3.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
          >
            <option value="">All Size Ranges</option>
            <option value="Small">Small (up to 10")</option>
            <option value="Medium">Medium (12" - 16")</option>
            <option value="Large">Large (18" - 24")</option>
            <option value="Extra Large">Extra Large (24"+)</option>
          </select>
        </div>
      </div>

      {/* Catalog Grid */}
      {loading ? (
        <div className="py-24 text-center space-y-3">
          <div className="w-12 h-12 border-4 border-amber-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p className="text-sm font-bold text-gray-600">Loading box inventory catalog...</p>
        </div>
      ) : products.length === 0 ? (
        <div className="bg-white py-20 text-center rounded-3xl border border-gray-200 space-y-4 shadow-sm">
          <AlertCircle className="w-14 h-14 text-amber-500 mx-auto" />
          <h3 className="text-xl font-bold text-gray-900">No Box Sizes Found</h3>
          <p className="text-sm text-gray-500">Try searching for standard dimensions like 12x12 or clear filters.</p>
          <button
            onClick={() => { setSearch(''); setCategory(''); setSizeCategory(''); setPage(1); }}
            className="px-5 py-2.5 bg-amber-600 text-white font-bold text-sm rounded-xl hover:bg-amber-700 transition"
          >
            Reset All Filters
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {products.map((product) => (
            <div
              key={product._id}
              className="bg-white rounded-3xl border border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group"
            >
              <div>
                {/* Image & Size Badge */}
                <div className="relative h-48 bg-gray-100 overflow-hidden">
                  <img
                    src={product.image}
                    alt={product.title}
                    className="w-full h-full object-cover group-hover:scale-108 transition duration-500"
                  />
                  <div className="absolute top-3 left-3 bg-amber-950/90 backdrop-blur-md text-amber-300 font-mono font-bold text-xs px-3 py-1 rounded-lg border border-amber-700/60 shadow-lg">
                    Size: {product.boxSize}
                  </div>
                  
                  {/* Stock Tag */}
                  <div className="absolute top-3 right-3">
                    {product.availableQuantity > 300 ? (
                      <span className="bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1 rounded-full shadow flex items-center">
                        <span className="w-1.5 h-1.5 bg-white rounded-full mr-1.5 animate-ping"></span>
                        {product.availableQuantity} in stock
                      </span>
                    ) : product.availableQuantity > 0 ? (
                      <span className="bg-amber-500 text-amber-950 font-bold text-[11px] px-2.5 py-1 rounded-full shadow">
                        Low Stock ({product.availableQuantity})
                      </span>
                    ) : (
                      <span className="bg-red-500 text-white font-bold text-[11px] px-2.5 py-1 rounded-full shadow">
                        Out of Stock
                      </span>
                    )}
                  </div>
                </div>

                {/* Card Body */}
                <div className="p-5 space-y-3">
                  <div className="text-[11px] font-mono font-bold text-amber-800 uppercase tracking-wider">
                    {product.category}
                  </div>
                  
                  <h3 className="font-bold text-gray-900 text-base leading-snug line-clamp-2">
                    {product.title}
                  </h3>

                  <p className="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                    {product.description}
                  </p>

                  <div className="flex items-baseline justify-between pt-2 border-t border-gray-100">
                    <div>
                      <span className="text-[10px] text-gray-400 block uppercase font-bold">Base Unit Price</span>
                      <span className="text-2xl font-black text-gray-900">₹{product.unitPrice.toFixed(2)}</span>
                    </div>
                    <div className="text-right">
                      <span className="text-[10px] uppercase font-bold text-gray-400 block">Stock Qty</span>
                      <span className="text-xs font-bold text-emerald-700">{product.availableQuantity} units</span>
                    </div>
                  </div>

                  {/* Tier Discount Badges */}
                  <div className="bg-amber-50/80 p-3 rounded-2xl border border-amber-200/80 space-y-1.5">
                    <span className="text-[10px] font-extrabold uppercase tracking-wider text-amber-900 flex items-center">
                      <Tag className="w-3.5 h-3.5 mr-1 text-amber-700" />
                      Bulk Discount Tiers:
                    </span>
                    <div className="flex flex-wrap gap-1">
                      {product.discountTiers.map((tier, idx) => (
                        <span
                          key={idx}
                          className="bg-white border border-amber-300 text-amber-950 text-[10px] font-bold px-2 py-0.5 rounded-md shadow-xs"
                        >
                          {tier.minQuantity}+: <span className="text-emerald-700">{tier.discountPercent}% OFF</span>
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              {/* Action Button */}
              <div className="p-5 pt-0">
                <button
                  onClick={() => setSelectedProduct(product)}
                  disabled={product.availableQuantity <= 0}
                  className="w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-extrabold text-xs rounded-2xl shadow-lg flex items-center justify-center space-x-2 transition duration-200 disabled:opacity-50"
                >
                  <Calculator className="w-4 h-4" />
                  <span>Calculate Bulk Discount & Order</span>
                </button>
              </div>

            </div>
          ))}
        </div>
      )}

      {/* Pagination Bar */}
      {totalPages > 1 && (
        <div className="bg-white p-4 rounded-2xl border border-gray-200 flex items-center justify-between shadow-sm">
          <div className="text-xs text-gray-600 font-medium">
            Showing Page <span className="font-bold text-gray-900">{page}</span> of{' '}
            <span className="font-bold text-gray-900">{totalPages}</span> ({totalItems} box products)
          </div>

          <div className="flex items-center space-x-2">
            <button
              onClick={() => setPage(prev => Math.max(1, prev - 1))}
              disabled={page === 1}
              className="p-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 disabled:opacity-40 transition"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
            <span className="text-sm font-bold text-gray-900 px-3">{page}</span>
            <button
              onClick={() => setPage(prev => Math.min(totalPages, prev + 1))}
              disabled={page === totalPages}
              className="p-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 disabled:opacity-40 transition"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      )}

      {/* Bulk Calculator Modal */}
      {selectedProduct && (
        <BulkCalculatorModal
          product={selectedProduct}
          onClose={() => setSelectedProduct(null)}
          onAddedToCart={handleAddedToCart}
        />
      )}

    </div>
  );
}
