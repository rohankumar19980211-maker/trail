import React, { useState, useEffect } from 'react';
import API from '../services/api';
import { History, Package, Calendar, DollarSign, Tag, CheckCircle2 } from 'lucide-react';

export default function OrderHistoryPage() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchOrders = async () => {
      try {
        const res = await API.get('/orders');
        setOrders(res.data);
      } catch (err) {
        console.error('Error fetching orders:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchOrders();
  }, []);

  if (loading) {
    return (
      <div className="py-20 text-center">
        <div className="w-10 h-10 border-4 border-amber-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p className="text-xs font-semibold text-gray-600 mt-2">Loading order history...</p>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      
      <div>
        <h1 className="text-2xl font-black text-gray-900">Bulk Order History</h1>
        <p className="text-xs text-gray-500">Track placed orders, quantities, applied bulk tier discounts, and totals in ₹ (INR).</p>
      </div>

      {orders.length === 0 ? (
        <div className="bg-white py-16 text-center rounded-2xl border border-gray-200 space-y-2">
          <History className="w-12 h-12 text-amber-500/60 mx-auto" />
          <h3 className="text-base font-bold text-gray-900">No Orders Found</h3>
          <p className="text-xs text-gray-500">Orders placed by internal employees will appear here.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {orders.map((order) => (
            <div
              key={order._id}
              className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm space-y-4"
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 pb-3 gap-2">
                <div>
                  <div className="flex items-center space-x-3">
                    <span className="font-mono font-bold text-base text-gray-900">{order.orderNumber}</span>
                    <span className="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-0.5 rounded-full flex items-center">
                      <CheckCircle2 className="w-3.5 h-3.5 mr-1 text-emerald-600" />
                      {order.status}
                    </span>
                  </div>
                  <div className="text-xs text-gray-500 mt-1 flex items-center space-x-3">
                    <span>Placed by: <strong className="text-gray-800">{order.userName} ({order.userUsername})</strong></span>
                    <span>•</span>
                    <span className="flex items-center">
                      <Calendar className="w-3.5 h-3.5 mr-1" />
                      {new Date(order.createdAt).toLocaleString()}
                    </span>
                  </div>
                </div>

                <div className="text-left sm:text-right">
                  <div className="text-xs text-gray-400">Total Order Amount</div>
                  <div className="text-2xl font-black text-amber-700">₹{order.finalAmount.toFixed(2)}</div>
                  {order.totalSavings > 0 && (
                    <div className="text-xs font-bold text-emerald-700">Saved ₹{order.totalSavings.toFixed(2)} in tier discounts</div>
                  )}
                </div>
              </div>

              {/* Order Items Table */}
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs text-gray-600">
                  <thead className="bg-gray-50 text-gray-500 font-bold uppercase">
                    <tr>
                      <th className="py-2 px-3">Box Product</th>
                      <th className="py-2 px-3">Box Size</th>
                      <th className="py-2 px-3">Quantity</th>
                      <th className="py-2 px-3">Applied Tier Discount</th>
                      <th className="py-2 px-3 text-right">Effective Unit Price</th>
                      <th className="py-2 px-3 text-right">Item Total</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {order.items.map((item, idx) => (
                      <tr key={idx}>
                        <td className="py-2 px-3 font-semibold text-gray-900">{item.title}</td>
                        <td className="py-2 px-3 font-mono font-bold text-amber-900">{item.boxSize}</td>
                        <td className="py-2 px-3 font-bold text-gray-900">{item.quantity} units</td>
                        <td className="py-2 px-3">
                          {item.discountPercent > 0 ? (
                            <span className="bg-emerald-50 text-emerald-800 font-bold px-1.5 py-0.5 rounded border border-emerald-200">
                              {item.discountPercent}% OFF
                            </span>
                          ) : (
                            <span className="text-gray-400">Standard Price</span>
                          )}
                        </td>
                        <td className="py-2 px-3 text-right font-semibold">₹{item.discountedUnitPrice.toFixed(2)}</td>
                        <td className="py-2 px-3 text-right font-bold text-gray-900">₹{item.totalItemPrice.toFixed(2)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

            </div>
          ))}
        </div>
      )}

    </div>
  );
}
