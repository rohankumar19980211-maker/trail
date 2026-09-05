import React, { useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import Navbar from './components/Navbar';
import LoginPage from './pages/LoginPage';
import AdminLoginPage from './pages/AdminLoginPage';
import ProductCatalogPage from './pages/ProductCatalogPage';
import AdminDashboardPage from './pages/AdminDashboardPage';
import CartPage from './pages/CartPage';
import OrderHistoryPage from './pages/OrderHistoryPage';
import BotpressChatbot from './components/BotpressChatbot';

// Employee Portal View on Homepage `/`
function EmployeePortal() {
  const { user, loading } = useAuth();
  const [activeTab, setActiveTab] = useState('catalog');

  if (loading) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50">
        <div className="w-12 h-12 border-4 border-amber-600 border-t-transparent rounded-full animate-spin"></div>
        <p className="mt-4 text-sm font-semibold text-gray-600">Loading Portal...</p>
      </div>
    );
  }

  // 1. Homepage will be simple login page if employee is not logged in
  if (!user) {
    return (
      <>
        <LoginPage />
        <BotpressChatbot />
      </>
    );
  }

  // 2. After login internal employee can see listed products
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col relative">
      <Navbar activeTab={activeTab} setActiveTab={setActiveTab} />
      
      <main className="flex-1">
        {activeTab === 'catalog' && <ProductCatalogPage />}
        {activeTab === 'cart' && <CartPage setActiveTab={setActiveTab} />}
        {activeTab === 'orders' && <OrderHistoryPage />}
        {activeTab === 'admin' && user.role === 'admin' && <AdminDashboardPage />}
      </main>

      <BotpressChatbot />

      <footer className="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-500">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
          <div>
            © 2026 <strong>Box Bulk Retailer Corp</strong>. Internal Employee Portal.
          </div>
          <div className="flex items-center space-x-4 font-mono">
            <span>350+ Box Sizes</span>
            <span>•</span>
            <span>Bulk Discounts: 100(5%), 300(10%), 500(18%), 600(20%)</span>
          </div>
        </div>
      </footer>
    </div>
  );
}

// Dedicated Admin Portal on `/admin`
function AdminPortal() {
  const { user, loading } = useAuth();
  const [activeTab, setActiveTab] = useState('admin');

  if (loading) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-amber-950 text-white">
        <div className="w-12 h-12 border-4 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
        <p className="mt-4 text-sm font-semibold text-amber-200">Loading Admin Control Center...</p>
      </div>
    );
  }

  // If not logged in as Admin, present dedicated Admin Login
  if (!user || user.role !== 'admin') {
    return (
      <>
        <AdminLoginPage />
        <BotpressChatbot />
      </>
    );
  }

  // Dedicated Admin Portal view
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col relative">
      <Navbar activeTab={activeTab} setActiveTab={setActiveTab} />
      
      <main className="flex-1">
        {activeTab === 'admin' && <AdminDashboardPage />}
        {activeTab === 'catalog' && <ProductCatalogPage />}
        {activeTab === 'cart' && <CartPage setActiveTab={setActiveTab} />}
        {activeTab === 'orders' && <OrderHistoryPage />}
      </main>

      <BotpressChatbot />

      <footer className="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-500">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
          <div>
            © 2026 <strong>Box Bulk Retailer Corp</strong>. Administrator Control Center (`/admin`).
          </div>
          <div className="flex items-center space-x-4 font-mono">
            <span>Admin Management Active</span>
          </div>
        </div>
      </footer>
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <CartProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/" element={<EmployeePortal />} />
            <Route path="/admin" element={<AdminPortal />} />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </BrowserRouter>
      </CartProvider>
    </AuthProvider>
  );
}
