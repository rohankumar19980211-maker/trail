import React from 'react';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { Package, ShoppingCart, History, Shield, LogOut, Box, Zap, User } from 'lucide-react';

export default function Navbar({ activeTab, setActiveTab }) {
  const { user, logout } = useAuth();
  const { cartItems } = useCart();

  const totalCartCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <header className="bg-gradient-to-r from-amber-950 via-amber-900 to-amber-950 text-white shadow-xl sticky top-0 z-40 border-b border-amber-800/80 backdrop-blur-md">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        
        {/* Brand Logo */}
        <div className="flex items-center space-x-3 cursor-pointer group" onClick={() => setActiveTab('catalog')}>
          <div className="bg-gradient-to-br from-amber-400 to-amber-600 text-amber-950 p-2.5 rounded-xl font-extrabold flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:scale-105 transition transform">
            <Box className="w-5 h-5" />
          </div>
          <div>
            <div className="flex items-center space-x-1">
              <span className="font-black text-xl tracking-tight text-white">BOX</span>
              <span className="font-light text-xl text-amber-400">RETAIL</span>
              <span className="hidden sm:inline-block ml-2 text-[10px] bg-amber-800/90 text-amber-200 px-2 py-0.5 rounded font-mono uppercase tracking-wider border border-amber-700">
                Bulk Direct
              </span>
            </div>
            <span className="text-[10px] text-amber-300/70 font-mono tracking-widest uppercase block -mt-1">
              Factory Wholesale
            </span>
          </div>
        </div>

        {/* Navigation Tabs */}
        {user && (
          <nav className="flex items-center space-x-1 sm:space-x-3">
            <button
              onClick={() => setActiveTab('catalog')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition duration-150 ${
                activeTab === 'catalog'
                  ? 'bg-amber-700 text-white shadow border border-amber-500/30'
                  : 'text-amber-200 hover:bg-amber-900/80 hover:text-white'
              }`}
            >
              <Package className="w-4 h-4" />
              <span>Catalog</span>
            </button>

            <button
              onClick={() => setActiveTab('cart')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition duration-150 relative ${
                activeTab === 'cart'
                  ? 'bg-amber-700 text-white shadow border border-amber-500/30'
                  : 'text-amber-200 hover:bg-amber-900/80 hover:text-white'
              }`}
            >
              <ShoppingCart className="w-4 h-4" />
              <span>Cart</span>
              {cartItems.length > 0 && (
                <span className="ml-1 bg-amber-400 text-amber-950 text-[11px] font-black px-2 py-0.5 rounded-full shadow animate-pulse">
                  {cartItems.length}
                </span>
              )}
            </button>

            <button
              onClick={() => setActiveTab('orders')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition duration-150 ${
                activeTab === 'orders'
                  ? 'bg-amber-700 text-white shadow border border-amber-500/30'
                  : 'text-amber-200 hover:bg-amber-900/80 hover:text-white'
              }`}
            >
              <History className="w-4 h-4" />
              <span className="hidden sm:inline">Order History</span>
            </button>

            {user.role === 'admin' && (
              <button
                onClick={() => setActiveTab('admin')}
                className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-black transition duration-150 ${
                  activeTab === 'admin'
                    ? 'bg-amber-500 text-amber-950 shadow-lg shadow-amber-500/20'
                    : 'bg-amber-900/90 text-amber-300 hover:bg-amber-800 hover:text-white border border-amber-700'
                }`}
              >
                <Shield className="w-4 h-4" />
                <span>Admin Dashboard</span>
              </button>
            )}
          </nav>
        )}

        {/* User Info & Logout */}
        {user && (
          <div className="flex items-center space-x-3">
            <div className="hidden md:flex items-center space-x-2 bg-amber-900/60 p-1.5 px-3 rounded-xl border border-amber-800 text-xs">
              <User className="w-4 h-4 text-amber-400" />
              <div className="flex flex-col text-left">
                <span className="font-bold text-white leading-tight">{user.name}</span>
                <span className="text-[10px] text-amber-300 font-mono capitalize">
                  {user.role === 'admin' ? '⚡ Administrator' : `Employee (${user.username})`}
                </span>
              </div>
            </div>
            <button
              onClick={logout}
              title="Sign Out"
              className="p-2 text-amber-300 hover:text-white hover:bg-amber-900/80 rounded-xl transition border border-transparent hover:border-amber-700"
            >
              <LogOut className="w-5 h-5" />
            </button>
          </div>
        )}
      </div>
    </header>
  );
}
