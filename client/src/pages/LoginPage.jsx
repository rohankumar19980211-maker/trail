import React, { useState, useEffect, useRef } from 'react';
import { useAuth } from '../context/AuthContext';
import { Box, Lock, User, AlertCircle, ArrowRight, ShieldCheck, Truck, Package, Percent, CheckCircle2, Award, Zap, Eye, EyeOff, Sparkles, Activity } from 'lucide-react';

export default function LoginPage() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [isCapsLock, setIsCapsLock] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Mouse Tracking Glow Position
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });
  const [boxRotation, setBoxRotation] = useState({ rx: 12, ry: -15 });
  const leftPanelRef = useRef(null);

  const { login } = useAuth();

  // Mouse movement tracking for ambient glow & 3D box tilt effect
  const handleMouseMove = (e) => {
    if (!leftPanelRef.current) return;
    const rect = leftPanelRef.current.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    setMousePos({ x, y });

    // Calculate 3D tilt angles based on mouse position relative to center
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    const rotateY = ((x - centerX) / centerX) * 25; // deg
    const rotateX = -((y - centerY) / centerY) * 20; // deg
    setBoxRotation({ rx: rotateX, ry: rotateY });
  };

  // Check Caps Lock state on password input
  const handleKeyDown = (e) => {
    if (e.getModifierState) {
      setIsCapsLock(e.getModifierState('CapsLock'));
    }
  };

  const handleSubmit = async (e) => {
    e?.preventDefault();
    if (!username || !password) {
      setError('Please enter your username and password');
      return;
    }
    setError('');
    setLoading(true);
    try {
      await login(username, password);
    } catch (err) {
      setError(err.response?.data?.message || 'Invalid username or password');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#0B0F17] flex flex-col lg:flex-row font-sans overflow-hidden text-gray-100">
      
      {/* 🎨 LEFT HERO PANE (Brand & Value Proposition) */}
      <div
        ref={leftPanelRef}
        onMouseMove={handleMouseMove}
        className="lg:w-7/12 relative p-8 sm:p-12 lg:p-16 flex flex-col justify-between overflow-hidden border-b lg:border-b-0 lg:border-r border-[#FF9F1C]/20 shadow-2xl select-none"
        style={{
          background: 'radial-gradient(circle at 40% 30%, #2D1505 0%, #170A02 50%, #0F0702 100%)'
        }}
      >
        {/* Subtle Geometric Cardboard Grid Pattern Overlay */}
        <div
          className="absolute inset-0 opacity-15 pointer-events-none"
          style={{
            backgroundImage: `radial-gradient(rgba(255, 159, 28, 0.4) 1px, transparent 1px), linear-gradient(to right, rgba(255, 159, 28, 0.08) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 159, 28, 0.08) 1px, transparent 1px)`,
            backgroundSize: '32px 32px'
          }}
        ></div>

        {/* Ambient Cursor Light Glow Effect */}
        <div
          className="absolute w-96 h-96 rounded-full pointer-events-none transition-opacity duration-300 blur-3xl opacity-30"
          style={{
            background: 'radial-gradient(circle, rgba(255, 159, 28, 0.4) 0%, rgba(217, 107, 67, 0.15) 50%, transparent 80%)',
            left: `${mousePos.x - 192}px`,
            top: `${mousePos.y - 192}px`
          }}
        ></div>

        {/* Top Header & Branding */}
        <div className="relative z-10 flex items-center justify-between">
          <div className="flex items-center space-x-3.5 group cursor-pointer">
            <div className="bg-gradient-to-br from-[#FF9F1C] to-[#D96B43] text-black p-3 rounded-2xl font-black flex items-center justify-center shadow-lg shadow-[#FF9F1C]/25 group-hover:scale-105 transition transform">
              <Box className="w-7 h-7 text-[#0B0F17]" />
            </div>
            <div>
              <div className="flex items-center space-x-1">
                <span className="font-black text-2xl tracking-tight text-white">BOX</span>
                <span className="font-bold text-2xl text-[#FF9F1C]">RETAIL</span>
                <span className="ml-2 text-[10px] bg-[#FF9F1C]/15 text-[#FF9F1C] px-2.5 py-0.5 rounded-full font-mono uppercase tracking-widest border border-[#FF9F1C]/30">
                  PORTAL
                </span>
              </div>
              <span className="text-[10px] text-gray-400 font-mono tracking-widest uppercase block">
                Industrial Packaging Systems
              </span>
            </div>
          </div>

          {/* ISO 9001 Animated Status Badge */}
          <div className="hidden sm:inline-flex items-center space-x-2 bg-[#0B0F17]/80 backdrop-blur-md border border-[#FF9F1C]/30 px-3.5 py-1.5 rounded-full text-xs font-mono text-gray-300 shadow-md">
            <span className="relative flex h-2.5 w-2.5">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#10B981] opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#10B981]"></span>
            </span>
            <span className="text-[#10B981] font-bold">ISO 9001</span>
            <span className="text-gray-400">Certified Factory Direct</span>
          </div>
        </div>

        {/* Main Content & 3D Box Showcase */}
        <div className="relative z-10 my-10 space-y-8 max-w-2xl">
          
          {/* Main Typography */}
          <div className="space-y-4">
            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-white via-white to-[#E5B369]">
                Factory-Direct Wholesale Boxes
              </span>{' '}
              <br className="hidden sm:inline" />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#FF9F1C] via-[#E5B369] to-[#D96B43]">
                & Bulk Discount Tiering
              </span>
            </h1>

            <p className="text-sm sm:text-base text-gray-300/90 leading-relaxed max-w-xl font-normal">
              Order over <strong className="text-[#FF9F1C] font-semibold">360+ industrial box sizes</strong> with automated quantity tier discounts. Built for seamless internal employee order placement and real-time warehouse stock tracking.
            </p>
          </div>

          {/* 📦 DYNAMIC INTERACTIVE 3D CORRUGATED BOX MODEL */}
          <div className="py-4 flex items-center justify-center sm:justify-start">
            <div
              className="w-48 h-48 sm:w-56 sm:h-56 relative perspective-1000 cursor-grab active:cursor-grabbing group"
              style={{ perspective: '1000px' }}
            >
              <div
                className="w-full h-full relative transition-transform duration-100 ease-out transform-style-3d"
                style={{
                  transformStyle: 'preserve-3d',
                  transform: `rotateX(${boxRotation.rx}deg) rotateY(${boxRotation.ry}deg)`
                }}
              >
                {/* Front Face */}
                <div
                  className="absolute inset-0 bg-gradient-to-br from-[#E5B369] via-[#C88A36] to-[#925C18] rounded-xl border border-[#FF9F1C]/60 flex flex-col items-center justify-center p-4 shadow-2xl text-[#0B0F17] font-mono"
                  style={{ transform: 'translateZ(100px)' }}
                >
                  <Box className="w-16 h-16 text-[#0B0F17] mb-2 stroke-[1.5]" />
                  <span className="font-extrabold text-sm tracking-wider uppercase">BOXRETAIL</span>
                  <span className="text-[10px] font-bold bg-[#0B0F17] text-[#FF9F1C] px-2 py-0.5 rounded mt-1">
                    ECT-32 HEAVY DUTY
                  </span>
                </div>

                {/* Back Face */}
                <div
                  className="absolute inset-0 bg-[#A66E24] rounded-xl border border-[#FF9F1C]/40 flex items-center justify-center p-4 text-xs font-mono text-amber-950 font-bold"
                  style={{ transform: 'rotateY(180deg) translateZ(100px)' }}
                >
                  FRAGILE • HANDLE WITH CARE
                </div>

                {/* Left Face */}
                <div
                  className="absolute inset-0 bg-[#925C18] rounded-xl border border-[#FF9F1C]/40 flex items-center justify-center p-4 text-xs font-mono text-amber-950 font-bold"
                  style={{ transform: 'rotateY(-90deg) translateZ(100px)' }}
                >
                  360+ SIZES
                </div>

                {/* Right Face */}
                <div
                  className="absolute inset-0 bg-[#C88A36] rounded-xl border border-[#FF9F1C]/40 flex items-center justify-center p-4 text-xs font-mono text-amber-950 font-bold"
                  style={{ transform: 'rotateY(90deg) translateZ(100px)' }}
                >
                  INDIA WIDE DISPATCH
                </div>

                {/* Top Flap */}
                <div
                  className="absolute inset-0 bg-[#E5B369]/90 rounded-xl border border-[#FF9F1C]/40 flex items-center justify-center p-4 text-xs font-mono text-amber-950 font-bold origin-top group-hover:-rotate-x-45 transition duration-300"
                  style={{ transform: 'rotateX(90deg) translateZ(100px)' }}
                >
                  UP TO 20% OFF
                </div>
              </div>
            </div>
            <div className="hidden sm:block ml-8 space-y-1 text-xs font-mono text-gray-400">
              <span className="text-[#FF9F1C] font-bold flex items-center">
                <Sparkles className="w-3.5 h-3.5 mr-1" /> Interactive 3D Packaging
              </span>
              <p className="text-[11px] text-gray-500">Hover & move cursor to inspect corrugated box tilt.</p>
            </div>
          </div>

          {/* Interactive Feature Grid (2x2 Glass Cards) */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            {/* Card 1: Tier Discounts */}
            <div className="bg-[#0B0F17]/60 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-[#FF9F1C]/15 hover:border-[#FF9F1C]/40 hover:-translate-y-1 transition duration-300 space-y-2 group shadow-xl">
              <div className="flex items-center justify-between">
                <div className="bg-[#FF9F1C]/15 p-2.5 rounded-xl text-[#FF9F1C] group-hover:scale-110 transition">
                  <Percent className="w-5 h-5" />
                </div>
                <span className="bg-[#10B981]/15 text-[#10B981] font-mono font-bold text-xs px-2.5 py-0.5 rounded-full border border-[#10B981]/30">
                  Up to 20% OFF
                </span>
              </div>
              <h4 className="text-sm font-bold text-white">Tier Discounts</h4>
              <p className="text-xs text-gray-400">100(5%), 300(10%), 500(18%), 600+(20% off)</p>
            </div>

            {/* Card 2: 360+ Standard Sizes */}
            <div className="bg-[#0B0F17]/60 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-[#FF9F1C]/15 hover:border-[#FF9F1C]/40 hover:-translate-y-1 transition duration-300 space-y-2 group shadow-xl">
              <div className="flex items-center justify-between">
                <div className="bg-[#FF9F1C]/15 p-2.5 rounded-xl text-[#FF9F1C] group-hover:scale-110 transition">
                  <Package className="w-5 h-5" />
                </div>
                <span className="text-[#E5B369] font-mono font-bold text-xs">
                  5 Categories
                </span>
              </div>
              <h4 className="text-sm font-bold text-white">360+ Standard Sizes</h4>
              <p className="text-xs text-gray-400">Corrugated, Mailers, Heavy Duty & Gift</p>
            </div>

            {/* Card 3: Express Dispatch */}
            <div className="bg-[#0B0F17]/60 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-[#FF9F1C]/15 hover:border-[#FF9F1C]/40 hover:-translate-y-1 transition duration-300 space-y-2 group shadow-xl">
              <div className="flex items-center justify-between">
                <div className="bg-[#FF9F1C]/15 p-2.5 rounded-xl text-[#FF9F1C] group-hover:scale-110 transition">
                  <Truck className="w-5 h-5" />
                </div>
                <span className="text-[#10B981] font-mono font-bold text-xs flex items-center">
                  ₹ (INR) Direct
                </span>
              </div>
              <h4 className="text-sm font-bold text-white">Express Dispatch</h4>
              <p className="text-xs text-gray-400">India-wide factory direct shipment in ₹</p>
            </div>

            {/* Card 4: Stock Control */}
            <div className="bg-[#0B0F17]/60 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-[#FF9F1C]/15 hover:border-[#FF9F1C]/40 hover:-translate-y-1 transition duration-300 space-y-2 group shadow-xl">
              <div className="flex items-center justify-between">
                <div className="bg-[#FF9F1C]/15 p-2.5 rounded-xl text-[#FF9F1C] group-hover:scale-110 transition">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <span className="flex items-center text-[#10B981] text-xs font-mono font-bold">
                  <Activity className="w-3.5 h-3.5 mr-1" /> Live Sync
                </span>
              </div>
              <h4 className="text-sm font-bold text-white">Stock Control</h4>
              <p className="text-xs text-gray-400">Real-time inventory deduction & tracking</p>
            </div>

          </div>
        </div>

        {/* System Status Ticker Bar */}
        <div className="relative z-10 pt-6 border-t border-[#FF9F1C]/20 flex flex-col sm:flex-row items-center justify-between text-xs font-mono text-gray-400 gap-2">
          <div className="flex items-center space-x-3">
            <span className="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
            <span>360 Box Sizes Listed • Real-time Warehouse Sync</span>
          </div>
          <span className="text-[#FF9F1C] font-semibold">v2.4 Enterprise</span>
        </div>

      </div>

      {/* 🏗️ RIGHT INTERACTIVE PANE (Employee Sign-In Interface) */}
      <div className="lg:w-5/12 bg-[#0B0F17] p-8 sm:p-12 lg:p-16 flex items-center justify-center relative z-10 border-t lg:border-t-0 border-[#FF9F1C]/10">
        
        {/* Glow ambient accent behind form */}
        <div className="absolute w-80 h-80 bg-[#FF9F1C]/10 rounded-full blur-3xl pointer-events-none"></div>

        <div className="w-full max-w-md space-y-8 relative z-10">
          
          {/* Header & Security Context */}
          <div className="space-y-2 text-center sm:text-left">
            <h2 className="text-3xl font-black text-white tracking-tight">
              Employee Portal Sign-In
            </h2>
            <div className="flex items-center justify-center sm:justify-start space-x-2 text-xs font-mono text-[#FF9F1C]/80">
              <ShieldCheck className="w-4 h-4 text-[#FF9F1C]" />
              <span>Protected Enterprise Portal • Authorized Personnel Only</span>
            </div>
          </div>

          {/* Form Card Surface */}
          <div className="bg-[#0B0F17]/90 backdrop-blur-2xl p-8 rounded-3xl border border-[#FF9F1C]/20 shadow-2xl space-y-6">
            
            {error && (
              <div className="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-start space-x-3 text-red-400 text-sm font-semibold animate-fadeIn">
                <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
                <span>{error}</span>
              </div>
            )}

            <form className="space-y-6" onSubmit={handleSubmit}>
              
              {/* Username Input with Floating Label Styling */}
              <div className="space-y-1">
                <label className="block text-xs font-bold text-gray-300 uppercase tracking-wider">
                  Username / Employee ID
                </label>
                <div className="relative group">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-[#FF9F1C] transition">
                    <User className="h-5 w-5" />
                  </div>
                  <input
                    type="text"
                    required
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    placeholder="e.g. emp_john"
                    className="block w-full pl-11 pr-4 py-3.5 bg-black/70 border border-gray-700/80 rounded-2xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#FF9F1C] focus:border-[#FF9F1C] text-sm font-medium transition duration-200"
                  />
                </div>
              </div>

              {/* Password Input with Reveal Toggle & Caps Lock Detection */}
              <div className="space-y-1">
                <div className="flex justify-between items-center">
                  <label className="block text-xs font-bold text-gray-300 uppercase tracking-wider">
                    Password
                  </label>
                  {isCapsLock && (
                    <span className="text-[10px] font-bold font-mono text-amber-400 animate-pulse">
                      ⚠️ CAPS LOCK IS ON
                    </span>
                  )}
                </div>
                <div className="relative group">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-[#FF9F1C] transition">
                    <Lock className="h-5 w-5" />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    value={password}
                    onKeyDown={handleKeyDown}
                    onKeyUp={handleKeyDown}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="••••••••••••"
                    className="block w-full pl-11 pr-11 py-3.5 bg-black/70 border border-gray-700/80 rounded-2xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#FF9F1C] focus:border-[#FF9F1C] text-sm font-medium transition duration-200"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-white transition"
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
              </div>

              {/* CTA Sign-In Button */}
              <button
                type="submit"
                disabled={loading}
                className="w-full py-4 px-6 rounded-2xl font-black text-black text-sm tracking-wide shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF9F1C] transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-[#FF9F1C]/25 flex items-center justify-center space-x-2 group disabled:opacity-50"
                style={{
                  background: 'linear-gradient(135deg, #FF9F1C 0%, #D96B43 100%)'
                }}
              >
                <span>{loading ? 'Authenticating Personnel...' : 'Sign In to Portal'}</span>
                {!loading && <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition duration-200" />}
              </button>

            </form>

            <div className="pt-4 border-t border-gray-800 text-center text-xs text-gray-500 font-mono">
              🔒 Protected Enterprise Portal. Employee accounts are created by Admin.
            </div>

          </div>

        </div>
      </div>

    </div>
  );
}
