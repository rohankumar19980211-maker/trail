import React, { useState, useEffect } from 'react';
import API from '../services/api';
import { Plus, RefreshCw, Trash2, Edit3, Save, Warehouse, Tag, Box, Check, Users, Key, Search, UserPlus, Lock, AlertCircle, Shield, BarChart3, PackageCheck } from 'lucide-react';

export default function AdminDashboardPage() {
  const [activeAdminTab, setActiveAdminTab] = useState('inventory'); // 'inventory' | 'employees'

  // Products state
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [actionMessage, setActionMessage] = useState('');

  // Employees state
  const [employees, setEmployees] = useState([]);
  const [empLoading, setEmpLoading] = useState(false);
  const [isEmpModalOpen, setIsEmpModalOpen] = useState(false);
  const [newEmpData, setNewEmpData] = useState({ username: '', password: '', name: '', role: 'employee' });

  // Reset Password Modal State
  const [resetEmpUser, setResetEmpUser] = useState(null);
  const [oldPasswordInput, setOldPasswordInput] = useState('');
  const [newPasswordInput, setNewPasswordInput] = useState('');
  const [resetError, setResetError] = useState('');
  const [resetLoading, setResetLoading] = useState(false);

  // Product Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [formData, setFormData] = useState({
    title: '',
    boxSize: '',
    length: 12,
    width: 12,
    height: 12,
    category: 'Corrugated Cartons',
    sizeCategory: 'Medium',
    description: '',
    unitPrice: 45.00,
    availableQuantity: 500,
    image: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
    discountTiers: [
      { minQuantity: 100, discountPercent: 5 },
      { minQuantity: 300, discountPercent: 10 },
      { minQuantity: 500, discountPercent: 18 },
      { minQuantity: 600, discountPercent: 20 }
    ]
  });

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await API.get('/products', { params: { search, limit: 100 } });
      setProducts(res.data.products);
    } catch (err) {
      console.error('Error fetching admin products:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchEmployees = async () => {
    setEmpLoading(true);
    try {
      const res = await API.get('/auth/users');
      setEmployees(res.data);
    } catch (err) {
      console.error('Error fetching employees:', err);
    } finally {
      setEmpLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
    fetchEmployees();
  }, [search]);

  const showNotification = (msg) => {
    setActionMessage(msg);
    setTimeout(() => setActionMessage(''), 3500);
  };

  // Metrics summary
  const totalStockCount = products.reduce((sum, p) => sum + (p.availableQuantity || 0), 0);

  // Seed Catalog
  const handleSeedCatalog = async () => {
    if (!window.confirm('Re-seed catalog with 350+ bulk box products in ₹ (INR)?')) return;
    setLoading(true);
    try {
      const res = await API.post('/products/seed');
      showNotification(`Catalog successfully re-seeded with ${res.data.productsCount} box products in ₹ (INR)!`);
      fetchProducts();
      fetchEmployees();
    } catch (err) {
      alert('Failed to seed catalog');
    } finally {
      setLoading(false);
    }
  };

  // Inline Stock Update
  const handleStockUpdate = async (productId, newStock) => {
    const qty = parseInt(newStock, 10);
    if (isNaN(qty) || qty < 0) return;
    try {
      await API.patch(`/products/${productId}/stock`, { availableQuantity: qty });
      setProducts(prev => prev.map(p => p._id === productId ? { ...p, availableQuantity: qty } : p));
      showNotification('Warehouse stock updated!');
    } catch (err) {
      alert('Failed to update stock');
    }
  };

  // Product Form Open Add
  const handleOpenAddModal = () => {
    setEditingId(null);
    setFormData({
      title: '',
      boxSize: '',
      length: 12,
      width: 12,
      height: 12,
      category: 'Corrugated Cartons',
      sizeCategory: 'Medium',
      description: '',
      unitPrice: 45.00,
      availableQuantity: 500,
      image: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
      discountTiers: [
        { minQuantity: 100, discountPercent: 5 },
        { minQuantity: 300, discountPercent: 10 },
        { minQuantity: 500, discountPercent: 18 },
        { minQuantity: 600, discountPercent: 20 }
      ]
    });
    setIsModalOpen(true);
  };

  // Product Form Open Edit
  const handleOpenEditModal = (product) => {
    setEditingId(product._id);
    setFormData({
      title: product.title,
      boxSize: product.boxSize,
      length: product.length || 12,
      width: product.width || 12,
      height: product.height || 12,
      category: product.category,
      sizeCategory: product.sizeCategory || 'Medium',
      description: product.description || '',
      unitPrice: product.unitPrice,
      availableQuantity: product.availableQuantity,
      image: product.image,
      discountTiers: product.discountTiers && product.discountTiers.length > 0 ? product.discountTiers : [
        { minQuantity: 100, discountPercent: 5 },
        { minQuantity: 300, discountPercent: 10 },
        { minQuantity: 500, discountPercent: 18 },
        { minQuantity: 600, discountPercent: 20 }
      ]
    });
    setIsModalOpen(true);
  };

  // Delete product
  const handleDeleteProduct = async (id, title) => {
    if (!window.confirm(`Are you sure you want to remove "${title}"?`)) return;
    try {
      await API.delete(`/products/${id}`);
      setProducts(prev => prev.filter(p => p._id !== id));
      showNotification(`Box product "${title}" removed.`);
    } catch (err) {
      alert('Failed to delete product');
    }
  };

  // Submit Product Form
  const handleFormSubmit = async (e) => {
    e.preventDefault();
    try {
      const payload = {
        ...formData,
        unitPrice: parseFloat(formData.unitPrice),
        availableQuantity: parseInt(formData.availableQuantity, 10)
      };

      if (editingId) {
        const res = await API.put(`/products/${editingId}`, payload);
        setProducts(prev => prev.map(p => p._id === editingId ? res.data : p));
        showNotification('Box product updated successfully!');
      } else {
        const res = await API.post('/products', payload);
        setProducts(prev => [res.data, ...prev]);
        showNotification('New Box product added to inventory!');
      }
      setIsModalOpen(false);
    } catch (err) {
      alert('Failed to save box product details.');
    }
  };

  // Tier Rows inside Form
  const handleAddTierRow = () => {
    setFormData(prev => ({
      ...prev,
      discountTiers: [...prev.discountTiers, { minQuantity: 1000, discountPercent: 25 }]
    }));
  };

  const handleUpdateTierRow = (index, field, value) => {
    setFormData(prev => {
      const updated = [...prev.discountTiers];
      updated[index] = { ...updated[index], [field]: parseFloat(value) || 0 };
      return { ...prev, discountTiers: updated };
    });
  };

  const handleRemoveTierRow = (index) => {
    setFormData(prev => ({
      ...prev,
      discountTiers: prev.discountTiers.filter((_, i) => i !== index)
    }));
  };

  // Handle Employee Creation
  const handleCreateEmployee = async (e) => {
    e.preventDefault();
    if (!newEmpData.username || !newEmpData.password || !newEmpData.name) {
      alert('Name, username, and password are required');
      return;
    }
    try {
      const res = await API.post('/auth/users', newEmpData);
      setEmployees(prev => [...prev, res.data]);
      setIsEmpModalOpen(false);
      setNewEmpData({ username: '', password: '', name: '', role: 'employee' });
      showNotification(`Employee account for "${res.data.name}" created successfully!`);
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to create employee account');
    }
  };

  // Open Password Reset Modal
  const handleOpenResetModal = (user) => {
    setResetEmpUser(user);
    setOldPasswordInput('');
    setNewPasswordInput('');
    setResetError('');
  };

  // Reset Employee Password with Old Password Verification
  const handleResetPasswordSubmit = async (e) => {
    e.preventDefault();
    if (!oldPasswordInput || !newPasswordInput) {
      setResetError('Both Old Password and New Password are required');
      return;
    }
    if (newPasswordInput.length < 4) {
      setResetError('New password must be at least 4 characters long');
      return;
    }

    setResetError('');
    setResetLoading(true);

    try {
      await API.put(`/auth/users/${resetEmpUser._id}/password`, {
        oldPassword: oldPasswordInput,
        newPassword: newPasswordInput
      });

      showNotification(`Password for "${resetEmpUser.name}" updated successfully!`);
      setResetEmpUser(null);
      setOldPasswordInput('');
      setNewPasswordInput('');
    } catch (err) {
      setResetError(err.response?.data?.message || 'Failed to update password. Please check old password.');
    } finally {
      setResetLoading(false);
    }
  };

  // Delete Employee
  const handleDeleteEmployee = async (user) => {
    if (user.username === 'admin' || user.role === 'admin') {
      alert('Primary admin account cannot be deleted');
      return;
    }
    if (!window.confirm(`Delete employee account for "${user.name}" (${user.username})?`)) return;
    try {
      await API.delete(`/auth/users/${user._id}`);
      setEmployees(prev => prev.filter(u => u._id !== user._id));
      showNotification(`Employee account "${user.username}" deleted.`);
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to delete employee account');
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 font-sans">
      
      {/* Toast Notification */}
      {actionMessage && (
        <div className="fixed bottom-6 right-6 z-50 bg-emerald-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-emerald-500 flex items-center space-x-3 animate-bounce">
          <Check className="w-5 h-5 text-emerald-400" />
          <span className="font-bold text-sm">{actionMessage}</span>
        </div>
      )}

      {/* Admin Executive Header */}
      <div className="bg-gradient-to-r from-amber-950 via-amber-900 to-amber-950 text-white rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between border border-amber-800 relative overflow-hidden">
        
        <div className="space-y-2 relative z-10">
          <div className="flex items-center space-x-2 text-amber-400 text-xs font-mono font-bold uppercase tracking-wider">
            <Shield className="w-4 h-4" />
            <span>Master Inventory & Employee Command Center</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-white">Admin Management Portal</h1>
          <p className="text-xs text-amber-200/90 max-w-xl leading-relaxed">
            Manage 360+ box listings, adjust warehouse inventory stock in ₹ (INR), and control internal employee logins.
          </p>
        </div>

        <div className="mt-4 md:mt-0 flex items-center space-x-3 relative z-10">
          <button
            onClick={handleSeedCatalog}
            className="px-4 py-2.5 bg-amber-900/80 hover:bg-amber-800 text-amber-300 font-bold text-xs rounded-2xl border border-amber-700/80 flex items-center space-x-2 transition"
          >
            <RefreshCw className="w-4 h-4" />
            <span>Seed Catalog (₹)</span>
          </button>

          {activeAdminTab === 'inventory' ? (
            <button
              onClick={handleOpenAddModal}
              className="px-5 py-2.5 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-amber-950 font-black text-xs rounded-2xl shadow-lg flex items-center space-x-2 transition"
            >
              <Plus className="w-4 h-4" />
              <span>Add Box Product</span>
            </button>
          ) : (
            <button
              onClick={() => setIsEmpModalOpen(true)}
              className="px-5 py-2.5 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-amber-950 font-black text-xs rounded-2xl shadow-lg flex items-center space-x-2 transition"
            >
              <UserPlus className="w-4 h-4" />
              <span>Create Employee Account</span>
            </button>
          )}
        </div>
      </div>

      {/* Metrics Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
          <div>
            <span className="text-xs font-bold text-gray-400 uppercase tracking-wider block">Total Catalog Items</span>
            <span className="text-2xl font-black text-gray-900 font-mono">{products.length} Boxes</span>
          </div>
          <div className="bg-amber-50 p-3 rounded-2xl text-amber-700">
            <Box className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
          <div>
            <span className="text-xs font-bold text-gray-400 uppercase tracking-wider block">Total Warehouse Stock</span>
            <span className="text-2xl font-black text-emerald-700 font-mono">{totalStockCount.toLocaleString()} Units</span>
          </div>
          <div className="bg-emerald-50 p-3 rounded-2xl text-emerald-700">
            <Warehouse className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
          <div>
            <span className="text-xs font-bold text-gray-400 uppercase tracking-wider block">Internal Employee Accounts</span>
            <span className="text-2xl font-black text-blue-800 font-mono">{employees.length} Users</span>
          </div>
          <div className="bg-blue-50 p-3 rounded-2xl text-blue-700">
            <Users className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Navigation Sub-Tabs */}
      <div className="flex space-x-3 border-b border-gray-200 pb-2">
        <button
          onClick={() => setActiveAdminTab('inventory')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-2xl text-xs font-extrabold transition duration-150 ${
            activeAdminTab === 'inventory'
              ? 'bg-amber-800 text-white shadow-md border border-amber-600'
              : 'text-gray-600 hover:bg-gray-100'
          }`}
        >
          <Box className="w-4 h-4" />
          <span>Box Inventory Management ({products.length})</span>
        </button>

        <button
          onClick={() => setActiveAdminTab('employees')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-2xl text-xs font-extrabold transition duration-150 ${
            activeAdminTab === 'employees'
              ? 'bg-amber-800 text-white shadow-md border border-amber-600'
              : 'text-gray-600 hover:bg-gray-100'
          }`}
        >
          <Users className="w-4 h-4" />
          <span>Internal Employee Accounts ({employees.length})</span>
        </button>
      </div>

      {/* TAB 1: INVENTORY MANAGEMENT */}
      {activeAdminTab === 'inventory' && (
        <div className="space-y-4">
          <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
            <div className="relative flex-1 max-w-md">
              <Search className="w-4 h-4 absolute left-3.5 top-3 text-gray-400" />
              <input
                type="text"
                placeholder="Search box inventory by SKU, size, or title..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-medium"
              />
            </div>
            <div className="text-xs font-semibold text-gray-500">
              Prices displayed in <span className="font-bold text-gray-900">₹ (INR)</span>
            </div>
          </div>

          {loading ? (
            <div className="py-20 text-center">
              <div className="w-10 h-10 border-4 border-amber-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
              <p className="text-xs text-gray-500 mt-2 font-bold">Loading inventory database...</p>
            </div>
          ) : (
            <div className="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs text-gray-600">
                  <thead className="bg-gray-50 text-xs text-gray-500 uppercase font-bold border-b border-gray-200">
                    <tr>
                      <th className="py-4 px-4">Box Image & Title</th>
                      <th className="py-4 px-4">Box Size</th>
                      <th className="py-4 px-4">Base Price (₹)</th>
                      <th className="py-4 px-4">Warehouse Stock Qty</th>
                      <th className="py-4 px-4">Configured Tier Discounts</th>
                      <th className="py-4 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {products.map((product) => (
                      <tr key={product._id} className="hover:bg-amber-50/40 transition duration-150">
                        <td className="py-3 px-4 flex items-center space-x-3">
                          <img
                            src={product.image}
                            alt={product.title}
                            className="w-12 h-12 rounded-xl object-cover border border-gray-200 bg-gray-50"
                          />
                          <div>
                            <div className="font-extrabold text-gray-900 text-sm line-clamp-1">{product.title}</div>
                            <div className="text-[11px] font-mono text-gray-400">{product.sku}</div>
                          </div>
                        </td>

                        <td className="py-3 px-4">
                          <span className="inline-block bg-amber-100/80 text-amber-950 font-mono font-bold text-xs px-2.5 py-1 rounded-lg border border-amber-200">
                            {product.boxSize}
                          </span>
                        </td>

                        <td className="py-3 px-4 font-black text-gray-900 text-sm">
                          ₹{product.unitPrice.toFixed(2)}
                        </td>

                        <td className="py-3 px-4">
                          <div className="flex items-center space-x-2">
                            <Warehouse className="w-4 h-4 text-amber-700" />
                            <input
                              type="number"
                              min="0"
                              value={product.availableQuantity}
                              onChange={(e) => handleStockUpdate(product._id, e.target.value)}
                              className="w-24 px-2.5 py-1 border border-gray-300 rounded-lg font-mono font-bold text-xs text-gray-900 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                            <span className="text-[11px] text-gray-400 font-medium">units</span>
                          </div>
                        </td>

                        <td className="py-3 px-4">
                          <div className="flex flex-wrap gap-1 max-w-xs">
                            {product.discountTiers.map((tier, idx) => (
                              <span
                                key={idx}
                                className="bg-emerald-50 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-md border border-emerald-200"
                              >
                                {tier.minQuantity}+ = {tier.discountPercent}%
                              </span>
                            ))}
                          </div>
                        </td>

                        <td className="py-3 px-4 text-right space-x-1">
                          <button
                            onClick={() => handleOpenEditModal(product)}
                            className="p-2 text-amber-700 hover:bg-amber-100 rounded-xl transition"
                            title="Edit Details & Discount Tiers"
                          >
                            <Edit3 className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleDeleteProduct(product._id, product.title)}
                            className="p-2 text-red-600 hover:bg-red-50 rounded-xl transition"
                            title="Delete Box Product"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      )}

      {/* TAB 2: EMPLOYEE ACCOUNTS MANAGEMENT */}
      {activeAdminTab === 'employees' && (
        <div className="space-y-4">
          <div className="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
            <div>
              <h3 className="font-extrabold text-gray-900">Internal Employee Accounts</h3>
              <p className="text-xs text-gray-500">Only Admin can create and manage employee login credentials here.</p>
            </div>
            <button
              onClick={() => setIsEmpModalOpen(true)}
              className="px-4 py-2.5 bg-amber-600 text-white font-bold text-xs rounded-xl shadow hover:bg-amber-700 flex items-center space-x-1.5 transition"
            >
              <UserPlus className="w-4 h-4" />
              <span>Add Employee</span>
            </button>
          </div>

          <div className="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
            <table className="w-full text-left text-xs text-gray-600">
              <thead className="bg-gray-50 text-xs text-gray-500 uppercase font-bold border-b border-gray-200">
                <tr>
                  <th className="py-4 px-4">Employee Name</th>
                  <th className="py-4 px-4">Username</th>
                  <th className="py-4 px-4">Role</th>
                  <th className="py-4 px-4">Created Date</th>
                  <th className="py-4 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {employees.map((emp) => (
                  <tr key={emp._id} className="hover:bg-amber-50/30 transition">
                    <td className="py-3.5 px-4 font-extrabold text-gray-900 text-sm">{emp.name}</td>
                    <td className="py-3.5 px-4 font-mono text-amber-950 font-bold">{emp.username}</td>
                    <td className="py-3.5 px-4">
                      {emp.role === 'admin' ? (
                        <span className="bg-amber-100 text-amber-950 text-[11px] font-black px-2.5 py-0.5 rounded-md border border-amber-300">
                          ⚡ Admin
                        </span>
                      ) : (
                        <span className="bg-blue-50 text-blue-800 text-[11px] font-bold px-2.5 py-0.5 rounded-md border border-blue-200">
                          Employee
                        </span>
                      )}
                    </td>
                    <td className="py-3.5 px-4 text-xs text-gray-500">
                      {emp.createdAt ? new Date(emp.createdAt).toLocaleDateString() : 'System Default'}
                    </td>
                    <td className="py-3.5 px-4 text-right space-x-2">
                      <button
                        onClick={() => handleOpenResetModal(emp)}
                        className="px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl inline-flex items-center space-x-1 transition"
                      >
                        <Key className="w-3.5 h-3.5" />
                        <span>Reset Password</span>
                      </button>

                      {emp.username !== 'admin' && (
                        <button
                          onClick={() => handleDeleteEmployee(emp)}
                          className="p-1.5 text-red-600 hover:bg-red-50 rounded-xl transition"
                          title="Delete Employee"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* CREATE EMPLOYEE MODAL */}
      {isEmpModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100">
            <div className="bg-amber-950 text-white p-5 flex items-center justify-between">
              <h3 className="font-bold text-base">Create Employee Credentials</h3>
              <button onClick={() => setIsEmpModalOpen(false)} className="text-amber-200 hover:text-white font-bold">✕</button>
            </div>
            <form onSubmit={handleCreateEmployee} className="p-6 space-y-4">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Full Employee Name</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Rahul Sharma"
                  value={newEmpData.name}
                  onChange={(e) => setNewEmpData({ ...newEmpData, name: e.target.value })}
                  className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Login Username</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. emp_rahul"
                  value={newEmpData.username}
                  onChange={(e) => setNewEmpData({ ...newEmpData, username: e.target.value })}
                  className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Account Password</label>
                <input
                  type="password"
                  required
                  placeholder="Set initial password"
                  value={newEmpData.password}
                  onChange={(e) => setNewEmpData({ ...newEmpData, password: e.target.value })}
                  className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                />
              </div>
              <div className="pt-2 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setIsEmpModalOpen(false)}
                  className="px-4 py-2 border text-xs font-bold text-gray-700 rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-amber-600 text-white font-bold text-xs rounded-xl shadow hover:bg-amber-700"
                >
                  Create Account
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* RESET PASSWORD MODAL */}
      {resetEmpUser && (
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100 animate-fadeIn">
            <div className="bg-amber-950 text-white p-5 flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <Key className="w-5 h-5 text-amber-400" />
                <h3 className="font-bold text-base">Reset Password for {resetEmpUser.name}</h3>
              </div>
              <button onClick={() => setResetEmpUser(null)} className="text-amber-200 hover:text-white font-bold">✕</button>
            </div>

            <form onSubmit={handleResetPasswordSubmit} className="p-6 space-y-4">
              <div className="text-xs text-gray-500 font-medium">
                Username: <strong className="text-gray-900 font-mono">{resetEmpUser.username}</strong>
              </div>

              {resetError && (
                <div className="p-3.5 rounded-2xl bg-red-50 border border-red-200 flex items-start space-x-2 text-red-700 text-xs font-bold">
                  <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                  <span>{resetError}</span>
                </div>
              )}

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Old / Current Password</label>
                <div className="relative">
                  <Lock className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                  <input
                    type="password"
                    required
                    placeholder="Enter current password"
                    value={oldPasswordInput}
                    onChange={(e) => setOldPasswordInput(e.target.value)}
                    className="w-full pl-9 pr-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">New Password</label>
                <div className="relative">
                  <Lock className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                  <input
                    type="password"
                    required
                    placeholder="Enter new password"
                    value={newPasswordInput}
                    onChange={(e) => setNewPasswordInput(e.target.value)}
                    className="w-full pl-9 pr-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                  />
                </div>
              </div>

              <div className="pt-2 flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={() => setResetEmpUser(null)}
                  className="px-4 py-2 border border-gray-300 text-xs font-bold text-gray-700 rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={resetLoading}
                  className="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow transition disabled:opacity-50"
                >
                  {resetLoading ? 'Updating...' : 'Save Password'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ADD / EDIT BOX MODAL */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden border border-gray-100 my-8">
            <div className="bg-amber-950 text-white p-5 flex items-center justify-between">
              <h3 className="font-bold text-lg">
                {editingId ? 'Edit Box Product & Tiered Discounts (₹)' : 'Add New Box Product to Inventory (₹)'}
              </h3>
              <button onClick={() => setIsModalOpen(false)} className="text-amber-200 hover:text-white font-bold">✕</button>
            </div>

            <form onSubmit={handleFormSubmit} className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Box Title / Name</label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. 12 x 12 x 12 Shipping Box"
                    value={formData.title}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Box Size String</label>
                  <input
                    type="text"
                    required
                    placeholder='e.g. 12" x 12" x 12"'
                    value={formData.boxSize}
                    onChange={(e) => setFormData({ ...formData, boxSize: e.target.value })}
                    className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Box Category</label>
                  <select
                    value={formData.category}
                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                    className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                  >
                    <option value="Corrugated Cartons">Corrugated Cartons</option>
                    <option value="Heavy-Duty Moving">Heavy-Duty Moving</option>
                    <option value="Corrugated Mailers">Corrugated Mailers</option>
                    <option value="Die-Cut / Gift Boxes">Die-Cut / Gift Boxes</option>
                    <option value="Telescopic & Special">Telescopic & Special</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Base Price (₹ / unit)</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={formData.unitPrice}
                    onChange={(e) => setFormData({ ...formData, unitPrice: e.target.value })}
                    className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-bold text-gray-900"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Available Stock Qty</label>
                  <input
                    type="number"
                    required
                    value={formData.availableQuantity}
                    onChange={(e) => setFormData({ ...formData, availableQuantity: e.target.value })}
                    className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-bold text-gray-900"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Box Image URL</label>
                <input
                  type="text"
                  required
                  value={formData.image}
                  onChange={(e) => setFormData({ ...formData, image: e.target.value })}
                  className="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm"
                />
              </div>

              {/* Dynamic Discount Tiers Configurator */}
              <div className="bg-amber-50/80 p-4 rounded-2xl border border-amber-200 space-y-3">
                <div className="flex items-center justify-between">
                  <label className="text-xs font-bold uppercase tracking-wider text-amber-900 flex items-center">
                    <Tag className="w-4 h-4 mr-1 text-amber-700" />
                    Configure Quantity Discount Tiers
                  </label>
                  <button
                    type="button"
                    onClick={handleAddTierRow}
                    className="text-xs font-bold text-amber-800 bg-white border border-amber-300 px-3 py-1 rounded-lg hover:bg-amber-100 transition"
                  >
                    + Add Tier Row
                  </button>
                </div>

                <div className="space-y-2">
                  {formData.discountTiers.map((tier, idx) => (
                    <div key={idx} className="flex items-center space-x-3 bg-white p-2.5 rounded-xl border border-amber-200">
                      <div className="flex-1 flex items-center space-x-1">
                        <span className="text-xs font-semibold text-gray-500 whitespace-nowrap">If Qty ≥</span>
                        <input
                          type="number"
                          value={tier.minQuantity}
                          onChange={(e) => handleUpdateTierRow(idx, 'minQuantity', e.target.value)}
                          className="w-24 px-2.5 py-1 border border-gray-300 rounded-lg font-bold text-xs text-gray-900"
                        />
                        <span className="text-xs text-gray-500">boxes</span>
                      </div>

                      <div className="flex-1 flex items-center space-x-1">
                        <span className="text-xs font-semibold text-gray-500">Discount =</span>
                        <input
                          type="number"
                          step="0.5"
                          value={tier.discountPercent}
                          onChange={(e) => handleUpdateTierRow(idx, 'discountPercent', e.target.value)}
                          className="w-20 px-2.5 py-1 border border-gray-300 rounded-lg font-bold text-xs text-emerald-700"
                        />
                        <span className="text-xs font-bold text-emerald-700">%</span>
                      </div>

                      <button
                        type="button"
                        onClick={() => handleRemoveTierRow(idx)}
                        className="text-red-500 hover:text-red-700 p-1"
                      >
                        ✕
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              <div className="pt-4 border-t border-gray-200 flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-bold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow flex items-center space-x-2"
                >
                  <Save className="w-4 h-4" />
                  <span>{editingId ? 'Save Changes' : 'Create Box Listing'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}
