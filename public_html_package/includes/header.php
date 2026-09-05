<?php
// includes/header.php - Responsive Navigation & Brand Header
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$currentUser = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - BOXRETAIL' : 'BOXRETAIL - Bulk Box Wholesale & Tier Calculator' ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js CDN for zero-build reactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-full flex flex-col font-sans antialiased selection:bg-amber-500 selection:text-slate-950" 
      x-data="boxStore()" x-init="initCart()">

    <!-- Top Wholesale Banner -->
    <div class="bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-slate-950 text-xs font-bold py-2 px-4 text-center tracking-wide flex items-center justify-center space-x-2 shadow-inner">
        <span>📦 DIRECT B2B WHOLESALE PRICING</span>
        <span class="opacity-40">•</span>
        <span>TIER DISCOUNTS UP TO 25% OFF IN ₹ (INR)</span>
        <span class="opacity-40">•</span>
        <span>360+ INDUSTRIAL BOX SKUs READY FOR DISPATCH</span>
    </div>

    <!-- Main Navigation Header -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Brand Logo -->
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-500 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl font-black tracking-tight text-white flex items-center space-x-1">
                            <span>BOX</span><span class="text-amber-500">RETAIL</span>
                            <span class="text-[10px] bg-slate-800 text-amber-400 font-semibold px-1.5 py-0.5 rounded ml-1 border border-slate-700">B2B HQ</span>
                        </div>
                        <p class="text-[10px] text-slate-400 -mt-0.5 tracking-wider uppercase">Wholesale Packaging Hub</p>
                    </div>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        Catalog (360+ SKUs)
                    </a>
                    <a href="index.php#calculator" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        Bulk Calculator
                    </a>
                    <a href="orders.php" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        Wholesale Orders
                    </a>
                </nav>

                <!-- Action Controls (Cart & Auth) -->
                <div class="flex items-center space-x-3">
                    
                    <!-- Cart Button with Live Counter Badge -->
                    <button @click="isCartOpen = true" class="relative p-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-200 hover:text-amber-400 hover:border-amber-500/50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span x-show="cartTotalCount > 0" x-text="cartTotalCount" class="absolute -top-1.5 -right-1.5 bg-amber-500 text-slate-950 text-[11px] font-black w-5 h-5 rounded-full flex items-center justify-center shadow-lg animate-pulse" x-cloak></span>
                    </button>

                    <!-- Authentication / User State -->
                    <?php if ($currentUser): ?>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 p-1.5 pl-3 pr-2 bg-slate-800 border border-slate-700 rounded-xl hover:border-slate-600 transition-colors">
                                <span class="text-xs font-semibold text-slate-200"><?= htmlspecialchars($currentUser['name'] ?? $currentUser['username']) ?></span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= ($currentUser['role'] ?? '') === 'admin' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-blue-500/20 text-blue-400 border border-blue-500/30' ?>">
                                    <?= strtoupper($currentUser['role'] ?? 'employee') ?>
                                </span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>

                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl py-1 z-50 text-sm" x-cloak>
                                <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                                    <a href="admin.php" class="block px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-amber-400 font-medium">⚙️ Master Admin</a>
                                <?php endif; ?>
                                <a href="orders.php" class="block px-4 py-2 text-slate-200 hover:bg-slate-700 hover:text-white">📦 Orders & Quotes</a>
                                <div class="border-t border-slate-700 my-1"></div>
                                <a href="logout.php" class="block px-4 py-2 text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 font-medium">🚪 Sign Out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="flex items-center space-x-1.5 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 text-sm font-bold rounded-xl shadow transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            <span>Staff Login</span>
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </header>
