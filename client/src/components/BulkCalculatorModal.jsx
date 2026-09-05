import React, { useState } from 'react';
import { useCart, calculateProductTierDiscount } from '../context/CartContext';
import { X, Calculator, Tag, Check, ShoppingBag, Box, Warehouse, Sparkles } from 'lucide-react';

export default function BulkCalculatorModal({ product, onClose, onAddedToCart }) {
  const [quantity, setQuantity] = useState(100);
  const { addToCart } = useCart();

  if (!product) return null;

  const qty = Math.max(1, parseInt(quantity) || 1);
  const discountPercent = calculateProductTierDiscount(product, qty);
  const baseUnitPrice = product.unitPrice;
  const regularTotal = baseUnitPrice * qty;
  const discountedUnitPrice = baseUnitPrice * (1 - discountPercent / 100);
  const finalTotal = discountedUnitPrice * qty;
  const savings = regularTotal - finalTotal;

  const handleAddToCart = () => {
    addToCart(product, qty);
    if (onAddedToCart) onAddedToCart();
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto font-sans">
      <div className="bg-white rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden border border-gray-100 animate-fadeIn">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-amber-950 via-amber-900 to-amber-950 text-white p-6 flex items-center justify-between border-b border-amber-800">
          <div className="flex items-center space-x-3">
            <div className="bg-amber-500/20 p-2.5 rounded-xl text-amber-300 border border-amber-500/30">
              <Calculator className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-lg font-black text-white">Bulk Discount Calculator</h3>
              <p className="text-xs text-amber-200">{product.title}</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="text-amber-200 hover:text-white p-1.5 rounded-xl hover:bg-amber-800/80 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="p-6 space-y-6">
          
          {/* Product Overview Summary */}
          <div className="flex items-center space-x-4 bg-amber-50/60 p-4 rounded-2xl border border-amber-200/80">
            <img
              src={product.image}
              alt={product.title}
              className="w-16 h-16 object-cover rounded-xl border border-amber-200 bg-white shadow-xs"
            />
            <div className="flex-1 min-w-0">
              <div className="font-extrabold text-gray-900 truncate text-base">{product.title}</div>
              <div className="text-xs text-gray-600 flex items-center space-x-2 mt-1">
                <span className="bg-white px-2.5 py-0.5 rounded-md border border-amber-300 font-mono font-bold text-amber-950">
                  Size: {product.boxSize}
                </span>
                <span className="flex items-center text-emerald-700 font-bold">
                  <Warehouse className="w-3.5 h-3.5 mr-1" />
                  {product.availableQuantity} available
                </span>
              </div>
              <div className="text-xs text-gray-500 mt-1">
                Base Unit Price: <span className="font-extrabold text-gray-900">₹{baseUnitPrice.toFixed(2)}</span> / box
              </div>
            </div>
          </div>

          {/* Admin Configured Tier Discounts List */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center">
              <Tag className="w-3.5 h-3.5 mr-1 text-amber-600" />
              Wholesale Tier Discount Scale
            </label>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
              {product.discountTiers.map((tier, idx) => {
                const isCurrentTier = qty >= tier.minQuantity &&
                  (!product.discountTiers[idx + 1] || qty < product.discountTiers[idx + 1].minQuantity);
                
                return (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => setQuantity(tier.minQuantity)}
                    className={`p-3 rounded-2xl border text-center transition duration-150 ${
                      isCurrentTier
                        ? 'bg-amber-100/80 border-amber-500 ring-2 ring-amber-500/30 text-amber-950 font-extrabold shadow-sm'
                        : 'bg-white border-gray-200 text-gray-700 hover:border-amber-300 hover:bg-amber-50/40'
                    }`}
                  >
                    <div className="text-[11px] text-gray-500 font-medium">{tier.minQuantity}+ boxes</div>
                    <div className="text-base font-black text-amber-700 flex items-center justify-center mt-0.5">
                      {tier.discountPercent}% OFF
                      {isCurrentTier && <Check className="w-4 h-4 ml-1 text-emerald-600" />}
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Quantity Selector Input */}
          <div className="bg-gray-50 p-4 rounded-2xl border border-gray-200 space-y-3">
            <div className="flex items-center justify-between">
              <label className="text-xs font-bold uppercase tracking-wider text-gray-700">Select Order Quantity (boxes):</label>
              <div className="flex items-center space-x-1">
                {[100, 300, 500, 600].map((presetQty) => (
                  <button
                    key={presetQty}
                    onClick={() => setQuantity(presetQty)}
                    className="px-3 py-1 text-xs font-bold bg-white border border-gray-300 hover:bg-amber-100 hover:border-amber-400 rounded-lg transition"
                  >
                    {presetQty}
                  </button>
                ))}
              </div>
            </div>

            <div className="flex items-center space-x-3">
              <input
                type="number"
                min="1"
                max={product.availableQuantity}
                value={quantity}
                onChange={(e) => setQuantity(e.target.value)}
                className="block w-full py-3 px-4 border border-gray-300 rounded-xl text-xl font-black text-gray-900 focus:ring-2 focus:ring-amber-500 focus:outline-none"
              />
              <span className="text-sm font-bold text-gray-500 whitespace-nowrap">units</span>
            </div>

            {qty > product.availableQuantity && (
              <p className="text-xs text-red-600 font-bold">
                ⚠️ Warning: Quantity exceeds warehouse stock ({product.availableQuantity} boxes available).
              </p>
            )}
          </div>

          {/* Live Discount & Savings Calculations Breakdown */}
          <div className="bg-gradient-to-br from-amber-50 to-orange-50 p-5 rounded-2xl border border-amber-200 space-y-3 shadow-sm">
            <div className="flex justify-between text-xs text-gray-600">
              <span>Subtotal ({qty} boxes × ₹{baseUnitPrice.toFixed(2)}):</span>
              <span className="line-through">₹{regularTotal.toFixed(2)}</span>
            </div>

            <div className="flex justify-between text-sm font-extrabold text-emerald-700">
              <span className="flex items-center">
                <Sparkles className="w-4 h-4 mr-1 text-emerald-600" />
                Applied Tier Discount ({discountPercent}%):
              </span>
              <span>- ₹{savings.toFixed(2)}</span>
            </div>

            <div className="pt-3 border-t border-amber-200/90 flex justify-between items-baseline">
              <div>
                <span className="text-[10px] text-gray-500 block uppercase font-bold tracking-wider">Effective Unit Price</span>
                <span className="text-xl font-black text-amber-950">₹{discountedUnitPrice.toFixed(2)} / box</span>
              </div>
              <div className="text-right">
                <span className="text-[10px] text-gray-500 block uppercase font-bold tracking-wider">Final Net Order Total</span>
                <span className="text-3xl font-black text-amber-700">₹{finalTotal.toFixed(2)}</span>
              </div>
            </div>
          </div>

        </div>

        {/* Modal Footer Actions */}
        <div className="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <button
            onClick={onClose}
            className="px-4 py-2.5 text-xs font-bold text-gray-600 hover:text-gray-900 transition"
          >
            Cancel
          </button>
          <button
            onClick={handleAddToCart}
            disabled={qty <= 0 || qty > product.availableQuantity}
            className="px-6 py-3 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center space-x-2 transition duration-200 disabled:opacity-50"
          >
            <ShoppingBag className="w-4 h-4" />
            <span>Add {qty} Boxes to Cart (₹{finalTotal.toFixed(2)})</span>
          </button>
        </div>

      </div>
    </div>
  );
}
