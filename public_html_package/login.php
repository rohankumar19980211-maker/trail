<?php
// login.php - Split-Screen Staff & Admin Authentication Portal
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = "Please enter both Username and Password.";
    } else {
        $userFound = null;

        // 1. Direct MySQL Query
        if ($db_connected && $pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `users` WHERE LOWER(username) = ? LIMIT 1");
                $stmt->execute([$username]);
                $userRow = $stmt->fetch();

                if ($userRow) {
                    $storedHash = $userRow['password_hash'] ?? '';
                    if (verify_password_secure($password, $storedHash)) {
                        $userFound = [
                            'id' => $userRow['id'],
                            'username' => $userRow['username'],
                            'name' => $userRow['name'] ?? $userRow['username'],
                            'role' => $userRow['role'] ?? 'employee'
                        ];
                    } else {
                        $error = "Invalid credentials. Incorrect password.";
                    }
                } else {
                    $error = "Invalid credentials. User not found.";
                }
            } catch (\Exception $e) {
                $error = "Database authentication error: " . $e->getMessage();
            }
        } else {
            $error = "Database connection error. Please run install.php or check credentials.";
        }

        // 2. Successful Login
        if ($userFound) {
            $_SESSION['box_user'] = $userFound;
            if ($userFound['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php?msg=login_success");
            }
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal Sign-In - BOXRETAIL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-full flex items-center justify-center p-4">

    <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden">
        
        <!-- Left Column: Branding & Quick Test Accounts -->
        <div class="p-8 sm:p-10 bg-gradient-to-br from-slate-900 via-slate-900 to-amber-950/40 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-800">
            <div>
                <a href="index.php" class="inline-flex items-center space-x-2 text-white group mb-8">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center font-bold text-lg">
                        📦
                    </div>
                    <span class="text-xl font-black">BOX<span class="text-amber-500">RETAIL</span></span>
                </a>

                <h2 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                    Wholesale Operations & Employee Portal
                </h2>
                <p class="text-slate-400 text-sm mt-3 leading-relaxed">
                    Internal access for staff, warehouse dispatch, logistics coordinators, and administrators.
                </p>
            </div>

            <!-- Quick Demo Credentials Card -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 space-y-3">
                <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider">Quick Fill Test Accounts:</span>
                
                <div class="grid grid-cols-1 gap-2">
                    <button type="button" 
                            onclick="fillCredentials('admin', 'admin123')"
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700/80 border border-slate-700 text-left transition-colors flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-white">Master Admin</div>
                            <div class="text-[11px] text-slate-400 font-mono">admin / admin123</div>
                        </div>
                        <span class="text-amber-400 text-[10px] font-bold">Autofill ➔</span>
                    </button>

                    <button type="button" 
                            onclick="fillCredentials('sanity_emp', 'SanityPass2026!')"
                            class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700/80 border border-slate-700 text-left transition-colors flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-white">Sanity Test Employee</div>
                            <div class="text-[11px] text-slate-400 font-mono">sanity_emp / SanityPass2026!</div>
                        </div>
                        <span class="text-amber-400 text-[10px] font-bold">Autofill ➔</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Sign In Form -->
        <div class="p-8 sm:p-10 flex flex-col justify-center">
            
            <div class="mb-6">
                <div class="flex items-center space-x-2 text-amber-500 text-xs font-bold uppercase tracking-wider mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <span>Secure Sign-In</span>
                </div>
                <h3 class="text-2xl font-bold text-white">Sign In to Portal</h3>
                <p class="text-xs text-slate-400 mt-1">Direct verification against MySQL Database</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold flex items-center space-x-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Username / Employee ID</label>
                    <input type="text" 
                           id="usernameInput"
                           name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required 
                           placeholder="e.g. sanity_emp or admin"
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <input type="password" 
                           id="passwordInput"
                           name="password" 
                           required 
                           placeholder="••••••••••••"
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors">
                </div>

                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl shadow-lg transition-all transform active:scale-95 text-sm flex items-center justify-center space-x-2">
                    <span>Sign In to Portal</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-800 text-center">
                <a href="index.php" class="text-xs text-slate-400 hover:text-amber-400 transition-colors">
                    ← Back to Storefront
                </a>
            </div>

        </div>

    </div>

    <script>
        function fillCredentials(u, p) {
            document.getElementById('usernameInput').value = u;
            document.getElementById('passwordInput').value = p;
        }
    </script>
</body>
</html>
