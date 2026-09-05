import React, { useState } from 'react';
import { useCart } from '../context/CartContext';
import API from '../services/api';
import { ShoppingBag, Trash2, Tag, Check, ArrowRight, Warehouse, AlertCircle, ShoppingCart } from 'lucide-react';

export default function CartPage({ setActiveTab }) {
  const { cartItems, updateQuantity, removeFromCart, clearCart, getSubtotal, getTotalSavings, getFinalTotal } = useCart();
  const [notes, setNotes] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [orderSuccess, setOrderSuccess] = useState(null);

  const subtotal = getSubtotal();
  const totalSavings = getTotalSavings();
  const finalTotal = getFinalTotal();

  const handleCheckout = async () => {
    if (cartItems.length === 0) return;
    setSubmitting(true);
    try {
      const itemsPayload = cartItems.map(item => ({
        productId: item.product._id,
        quantity: item.quantity
      }));

      const res = await API.post('/orders', {
        items: itemsPayload,
        deliveryNotes: notes
      });

      setOrderSuccess(res.data);
      clearCart();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to place order.');
    } finally {
      setSubmitting(false);
    }
  };

  if (orderSuccess) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-16 text-center space-y-6">
        <div className="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
          <Check className="w-10 h-10" />
        </div>
        <h2 className="text-3xl font-black text-gray-900">Bulk Order Placed Successfully!</h2>
        <p className="text-gray-600 text-sm max-w-md mx-auto">
          Order reference <span className="font-mono font-bold text-amber-800">{orderSuccess.orderNumber}</span> has been processed. Warehouse stock has been updated automatically.
        </p>

        <div className="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm max-w-md mx-auto text-left space-y-3">
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">Order Ref:</span>
            <span className="font-mono font-bold text-gray-900">{orderSuccess.orderNumber}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">Total Boxes Ordered:</span>
            <span className="font-bold text-gray-900">{orderSuccess.totalQuantity} units</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-500">Bulk Tier Savings:</span>
            <span className="font-bold text-emerald-600">₹{orderSuccess.totalSavings.toFixed(2)}</span>
          </div>
          <div className="pt-2 border-t border-gray-100 flex justify-between text-base font-extrabold">
            <span>Amount Paid:</span>
            <span className="text-amber-700">₹{orderSuccess.finalAmount.toFixed(2)}</span>
          </div>
        </div>

        <div className="flex justify-center space-x-4 pt-4">
          <button
            onClick={() => { setOrderSuccess(null); setActiveTab('catalog'); }}
            className="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl shadow transition"
          >
            Return to Catalog
          </button>
          <button
            onClick={() => { setOrderSuccess(null); setActiveTab('orders'); }}
            className="px-6 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-bold text-sm rounded-xl shadow-sm transition"
          >
            View Order History
          </button>
        </div>
      </div>
    );
  }

  if (cartItems.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-16 text-center space-y-4">
        <ShoppingCart className="w-16 h-16 text-amber-500/60 mx-auto" />
        <h2 className="text-2xl font-bold text-gray-900">Your Bulk Cart is Empty</h2>
        <p className="text-sm text-gray-500">Browse box sizes from our warehouse catalog and calculate wholesale savings.</p>
        <button
          onClick={() => setActiveTab('catalog')}
          className="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl shadow transition inline-flex items-center space-x-2"
        >
          <span>Browse Box Catalog</span>
          <ArrowRight className="w-4 h-4" />
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      
      <div className="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
          <h1 className="text-2xl font-black text-gray-900">Bulk Order Cart</h1>
          <p className="text-xs text-gray-500">Review selected box quantities and applied wholesale tier discounts in ₹ (INR).</p>
        </div>
        <button
          onClick={clearCart}
          className="text-xs text-red-600 hover:text-red-800 font-semibold"
        >
          Clear Cart
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Cart Items List */}
        <div className="lg:col-span-2 space-y-4">
          {cartItems.map(({ product, quantity, discountPercent }) => {
            const basePrice = product.unitPrice;
            const itemSubtotal = basePrice * quantity;
            const discountedPrice = basePrice * (1 - discountPercent / 100);
            const itemTotal = discountedPrice * quantity;
            const itemSavings = itemSubtotal - itemTotal;

            return (
              <div
                key={product._id}
                className="bg-white rounded-2xl p-4 sm:p-5 border border-gray-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
              >
                <div className="flex items-center space-x-4">
                  <img
                    src={product.image}
                    alt={product.title}
                    className="w-16 h-16 rounded-xl object-cover border border-gray-200 bg-gray-50"
                  />
                  <div>
                    <h3 className="font-bold text-gray-900 text-base">{product.title}</h3>
                    <div className="text-xs text-gray-500 flex items-center space-x-2 mt-0.5">
                      <span className="font-mono bg-amber-50 text-amber-900 font-bold px-2 py-0.5 rounded border border-amber-200">
                        Size: {product.boxSize}
                      </span>
                      <span>Base: ₹{basePrice.toFixed(2)}/box</span>
                    </div>

                    {discountPercent > 0 ? (
                      <div className="mt-2 inline-flex items-center space-x-1 bg-emerald-50 text-emerald-800 text-xs font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                        <Tag className="w-3 h-3 text-emerald-600" />
                        <span>Applied Tier Discount: {discountPercent}% OFF</span>
                      </div>
                    ) : (
                      <div className="mt-2 text-[11px] text-amber-700 font-medium">
                        💡 Add to 100+ boxes for 5% bulk discount
                      </div>
                    )}
                  </div>
                </div>

                <div className="flex items-center justify-between sm:justify-end w-full sm:w-auto space-x-6">
                  <div className="flex flex-col items-center">
                    <span className="text-[10px] text-gray-400 font-bold uppercase mb-1">Quantity</span>
                    <input
                      type="number"
                      min="1"
                      value={quantity}
                      onChange={(e) => updateQuantity(product._id, e.target.value)}
                      className="w-20 px-2 py-1 border border-gray-300 rounded-lg text-center font-bold text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    />
                  </div>

                  <div className="text-right min-w-[100px]">
                    <span className="text-xs text-gray-400 line-through block">₹{itemSubtotal.toFixed(2)}</span>
                    <span className="text-lg font-black text-amber-700">₹{itemTotal.toFixed(2)}</span>
                    {itemSavings > 0 && (
                      <span className="text-[10px] font-bold text-emerald-700 block">Saved ₹{itemSavings.toFixed(2)}</span>
                    )}
                  </div>

                  <button
                    onClick={() => removeFromCart(product._id)}
                    className="text-gray-400 hover:text-red-600 p-1 transition"
                  >
                    <Trash2 className="w-5 h-5" />
                  </button>
                </div>
              </div>
            );
          })}
        </div>

        {/* Order Summary Sidebar */}
        <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm h-fit space-y-6">
          <h2 className="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">Order Summary</h2>

          <div className="space-y-3 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>Subtotal:</span>
              <span className="font-semibold text-gray-800">₹{subtotal.toFixed(2)}</span>
            </div>

            <div className="flex justify-between text-emerald-700 font-bold">
              <span>Bulk Tier Discounts:</span>
              <span>- ₹{totalSavings.toFixed(2)}</span>
            </div>

            <div className="pt-3 border-t border-gray-200 flex justify-between items-baseline">
              <span className="text-base font-bold text-gray-900">Total Order Amount:</span>
              <span className="text-2xl font-black text-amber-700">₹{finalTotal.toFixed(2)}</span>
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1">Warehouse / Delivery Notes</label>
            <textarea
              rows="3"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="e.g. Expedite pallet packaging..."
              className="w-full p-2.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"
            ></textarea>
          </div>

          <button
            onClick={handleCheckout}
            disabled={submitting}
            className="w-full py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl shadow-lg flex items-center justify-center space-x-2 transition disabled:opacity-50"
          >
            <ShoppingBag className="w-5 h-5" />
            <span>{submitting ? 'Processing Order...' : 'Submit Bulk Order'}</span>
          </button>
        </div>

      </div>

    </div>
  );
}
