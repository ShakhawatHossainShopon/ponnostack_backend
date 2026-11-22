<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// If already logged in, redirect straight to dashboard
$currentTenant = getLoggedInTenant();
if ($currentTenant) {
    header('Location: index.php');
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLive = (strpos($host, 'ponnostack.shop') !== false);
$homeUrl = $isLive ? 'https://ponnostack.shop/' : ($protocol . $host . '/Ponnostack/');
?>
<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>কাস্টমার পোর্টাল লগইন | Ponnostack</title>
    <meta name="description" content="Ponnostack কাস্টমার পোর্টালে প্রবেশ করুন। আপনার স্টোরফ্রন্ট, ক্যাটালগ ও অর্ডার ম্যানেজ করুন।">
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#00684A">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($homeUrl) ?>assets/images/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($homeUrl) ?>assets/images/favicon.png">

    <!-- Fonts: Hind Siliguri & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Hind Siliguri"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#F0FDF4',
                            100: '#DCFCE7',
                            200: '#BBF7D0',
                            500: '#00ED64',
                            600: '#00684A',
                            700: '#00523A',
                            800: '#003E2C',
                            900: '#001E17',
                        }
                    },
                    boxShadow: {
                        'brand-glow': '0 10px 25px -5px rgba(0, 104, 74, 0.25)',
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Hind Siliguri', -apple-system, sans-serif;
            background-color: #F8FAFC;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between text-slate-800 antialiased selection:bg-brand-100 selection:text-brand-900">

    <!-- Floating Toast Notification System (NO Modal Popups) -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-[99999] pointer-events-none transition-all duration-300 flex flex-col items-center space-y-2 max-w-sm sm:max-w-md w-full px-4"></div>

    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="inline-flex items-center space-x-1 text-xs font-bold text-slate-600 hover:text-brand-600 transition group">
                <i data-lucide="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-1"></i>
                <span class="hidden sm:inline">হোমপেজে ফিরে যান</span>
                <span class="sm:hidden">হোম</span>
            </a>

            <a href="<?= htmlspecialchars($homeUrl) ?>" class="flex items-center">
                <img src="<?= htmlspecialchars($homeUrl) ?>assets/images/logo.png" alt="Ponnostack Logo" class="h-7 sm:h-8 w-auto object-contain" width="120" height="32">
            </a>

            <div class="inline-flex items-center space-x-1 text-[10px] sm:text-[11px] font-bold text-emerald-800 bg-emerald-50/90 border border-emerald-200 px-2.5 py-1 rounded-full shadow-2xs">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-brand-600"></i>
                <span class="hidden sm:inline">কাস্টমার সিকিউর পোর্টাল</span>
                <span class="sm:hidden">সিকিউর</span>
            </div>
        </div>
    </header>

    <!-- Main Login Card -->
    <main class="flex-1 flex items-center justify-center px-3.5 sm:px-6 py-6 sm:py-12">
        <div class="w-full max-w-md bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-xl space-y-5 sm:space-y-6">
            
            <!-- Card Header -->
            <div class="text-center space-y-1">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 mx-auto flex items-center justify-center font-bold shadow-2xs mb-3">
                    <i data-lucide="store" class="w-6 h-6"></i>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900">কাস্টমার পোর্টালে লগইন</h1>
                <p class="text-xs text-slate-500">আপনার ব্র্যান্ডেড স্টোরফ্রন্ট ও ক্যাটালগ পরিচালনা করুন</p>
            </div>

            <!-- Informational Notice (As specified by user) -->
            <div class="p-3.5 sm:p-4 bg-emerald-50/80 border border-emerald-200/90 rounded-2xl flex items-start space-x-2.5 text-xs text-slate-700">
                <i data-lucide="info" class="w-4 h-4 text-brand-600 shrink-0 mt-0.5"></i>
                <div class="leading-relaxed">
                    <strong class="text-brand-700 font-bold block mb-0.5">স্টোরফ্রন্ট কন্ট্রোল প্যানেল</strong>
                    প্যাকেজ চেকআউট করার সময় যে ইমেইল ও পাসওয়ার্ড দিয়েছিলেন তা দিয়ে এখানে প্রবেশ করুন। আপনার সুবিধার জন্য পাসওয়ার্ডটি আপনার রেজিস্টার্ড ইমেইলেও পাঠিয়ে দেওয়া হয়েছে।
                </div>
            </div>

            <!-- Login Form -->
            <form id="portalLoginForm" onsubmit="handlePortalLogin(event)" class="space-y-4">
                <!-- Owner Email -->
                <div>
                    <label for="loginEmail" class="block text-xs font-bold text-slate-700 mb-1">
                        লগইন ইমেইল <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                        <input type="email" id="loginEmail" name="email" required 
                               placeholder="e.g. owner@example.com" 
                               class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="loginPassword" class="block text-xs font-bold text-slate-700">
                            পাসওয়ার্ড <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-slate-400 font-mono">চেকআউটে ব্যবহৃত পাসওয়ার্ড</span>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                        <input type="password" id="loginPassword" name="password" required 
                               placeholder="••••••••" 
                               class="w-full pl-9 pr-10 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i data-lucide="eye" id="pwdEyeIcon" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center space-x-2 text-xs text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" id="loginRemember" name="remember" checked class="w-3.5 h-3.5 accent-brand-600 rounded">
                        <span>৩০ দিন লগইন মনে রাখুন</span>
                    </label>
                    <a href="mailto:support@ponnostack.shop" class="text-[11px] font-bold text-brand-600 hover:underline">পাসওয়ার্ড ভুলে গেছেন?</a>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" id="btnLoginSubmit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-black rounded-xl transition shadow-brand-glow flex items-center justify-center space-x-2 cursor-pointer">
                        <span>পোর্টালে প্রবেশ করুন</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>

            <!-- Bottom Help -->
            <div class="pt-3 border-t border-slate-100 text-center text-xs text-slate-500">
                <span>নতুন স্টোর খুলতে চান?</span>
                <a href="<?= htmlspecialchars($homeUrl) ?>#pricing" class="font-bold text-brand-600 hover:underline ml-1">প্যাকেজ কিনুন &rarr;</a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200/80 py-4 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Ponnostack. সর্বস্বত্ব সংরক্ষিত।
    </footer>

    <!-- Script: Auth & LocalStorage persistence -->
    <script>
        const AUTH_API = 'api/auth.php';

        function togglePasswordVisibility() {
            const input = document.getElementById('loginPassword');
            const icon = document.getElementById('pwdEyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            if (window.lucide) lucide.createIcons();
        }

        function showToast(message, type = 'error') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            container.innerHTML = '';

            const toast = document.createElement('div');
            const isErr = (type === 'error');
            const bg = isErr 
                ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' 
                : 'bg-brand-600 text-white shadow-lg shadow-brand-glow';
            const icon = isErr ? 'alert-circle' : 'check-circle';

            toast.className = `flex items-center space-x-2 px-4 py-2.5 rounded-2xl text-xs font-bold pointer-events-auto transform -translate-y-3 opacity-0 transition-all duration-250 ${bg}`;
            toast.innerHTML = `
                <i data-lucide="${icon}" class="w-4 h-4 shrink-0"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            if (window.lucide) lucide.createIcons();

            requestAnimationFrame(() => {
                toast.classList.remove('-translate-y-3', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            });

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('-translate-y-3', 'opacity-0');
                setTimeout(() => toast.remove(), 250);
            }, 3000);
        }

        function highlightField(id, message) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/40');
            el.classList.remove('border-slate-300');
            showToast(message, 'error');
            el.focus();

            const onInput = () => {
                el.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/40');
                el.classList.add('border-slate-300');
                el.removeEventListener('input', onInput);
            };
            el.addEventListener('input', onInput);
        }

        // On Load: If already logged in in localStorage, check and redirect
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            const savedToken = localStorage.getItem('ponnostack_auth_token');
            if (savedToken) {
                // Verify with backend
                fetch(AUTH_API)
                    .then(r => r.json())
                    .then(data => {
                        if (data.authenticated) {
                            window.location.href = 'index.php';
                        }
                    })
                    .catch(() => {});
            }
        });

        // Form Submission
        async function handlePortalLogin(e) {
            e.preventDefault();

            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value.trim();
            const remember = document.getElementById('loginRemember').checked;

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                highlightField('loginEmail', 'সঠিক ইমেইল ঠিকানা প্রদান করুন');
                return;
            }

            if (!password || password.length < 4) {
                highlightField('loginPassword', 'পাসওয়ার্ড কমপক্ষে ৪ অক্ষরের হতে হবে');
                return;
            }

            const btn = document.getElementById('btnLoginSubmit');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1.5"></i><span>যাচাই করা হচ্ছে...</span>';
            if (window.lucide) lucide.createIcons();

            try {
                const res = await fetch(AUTH_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, remember })
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<span>পোর্টালে প্রবেশ করুন</span><i data-lucide="arrow-right" class="w-4 h-4"></i>';
                if (window.lucide) lucide.createIcons();

                if (data.success) {
                    showToast(data.message || 'লগইন সফল হয়েছে!', 'success');
                    // Store in localStorage for client persistence
                    if (data.data && data.data.auth) {
                        localStorage.setItem('ponnostack_auth_token', data.data.auth.token);
                        localStorage.setItem('ponnostack_tenant', JSON.stringify(data.data.auth));
                    }
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 600);
                } else {
                    highlightField('loginPassword', data.message || 'লগইন ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<span>পোর্টালে প্রবেশ করুন</span><i data-lucide="arrow-right" class="w-4 h-4"></i>';
                if (window.lucide) lucide.createIcons();
                showToast('সার্ভারের সাথে সংযোগ স্থাপন করা যায়নি। ইন্টারনেট পরীক্ষা করুন।', 'error');
            }
        }
    </script>
</body>
</html>
