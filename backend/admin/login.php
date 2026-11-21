<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect straight to admin dashboard
if (!empty($_SESSION['admin_user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন লগইন | Ponnostack</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Hind Siliguri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Hind Siliguri"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-indigo-600 selection:text-white">

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 mb-3">
                <i data-lucide="shield-check" class="w-8 h-8"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Ponnostack Admin</h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">প্ল্যাটফর্ম ম্যানেজমেন্ট পোর্টালে লগইন করুন</p>
        </div>

        <!-- Login Card (Pure Light Mode) -->
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm">
            
            <!-- Error Alert Container -->
            <div id="loginErrorAlert" class="hidden mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center space-x-2">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
                <span id="loginErrorText"></span>
            </div>

            <form id="adminLoginForm" onsubmit="handleAdminLogin(event)" class="space-y-4">
                <!-- Email Field -->
                <div>
                    <label for="loginEmail" class="block text-xs font-bold text-slate-700 mb-1.5">ইমেইল ঠিকানা (Email)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </div>
                        <input type="email" id="loginEmail" name="email" required placeholder="name@domain.com"
                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="loginPassword" class="block text-xs font-bold text-slate-700 mb-1.5">পাসওয়ার্ড (Password)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input type="password" id="loginPassword" name="password" required placeholder="••••••••"
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                            <i id="eyeIcon" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Info -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" id="rememberMe" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
                        <span class="text-slate-600 font-medium">আমাকে মনে রাখুন</span>
                    </label>
                    <span class="text-slate-400 text-[11px]">নিরাপদ সেশন</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="loginSubmitBtn"
                    class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold transition flex items-center justify-center space-x-2 shadow-sm cursor-pointer mt-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>লগইন করুন</span>
                </button>
            </form>
        </div>

        <!-- Footer Note -->
        <p class="text-center text-[11px] text-slate-400 mt-6">
            Ponnostack Platform Core &bull; Light Mode Only
        </p>
    </div>

    <script>
        lucide.createIcons();

        function togglePasswordVisibility() {
            const pwd = document.getElementById('loginPassword');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                pwd.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        async function handleAdminLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('loginSubmitBtn');
            const alertBox = document.getElementById('loginErrorAlert');
            const errorText = document.getElementById('loginErrorText');

            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            alertBox.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-2">⟳</span> <span>যাচাই করা হচ্ছে...</span>`;

            try {
                const res = await fetch('../api/admin/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    btn.innerHTML = `<span>লগইন সফল! প্রবেশ করা হচ্ছে...</span>`;
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 400);
                } else {
                    errorText.textContent = data.message || 'ভুল ইমেইল অথবা পাসওয়ার্ড।';
                    alertBox.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = `<i data-lucide="log-in" class="w-4 h-4"></i><span>লগইন করুন</span>`;
                    lucide.createIcons();
                }
            } catch (err) {
                errorText.textContent = 'সার্ভারের সাথে সংযোগ স্থাপন করা সম্ভব হয়নি।';
                alertBox.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="log-in" class="w-4 h-4"></i><span>লগইন করুন</span>`;
                lucide.createIcons();
            }
        }
    </script>
</body>
</html>
