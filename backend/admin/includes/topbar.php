<?php

declare(strict_types=1);

/** @var array $currentUser */
?>
<!-- Topbar Navigation (Pure Light Mode - Sticky on Mobile & Desktop) -->
<header class="bg-white/95 backdrop-blur-md border-b border-slate-200/90 h-16 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-30 shadow-2xs">
    <div class="flex items-center space-x-3">
        <!-- Mobile Sidebar Toggle Button -->
        <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition border border-slate-200">
            <i data-lucide="menu" class="w-4 h-4"></i>
        </button>

        <div>
            <h2 class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($pageTitle ?? 'ড্যাশবোর্ড') ?></h2>
            <span class="text-[10px] text-slate-400 hidden sm:block">সিস্টেম ম্যানেজমেন্ট পোর্টাল</span>
        </div>
    </div>

    <!-- Right Side Actions: User profile & Logout -->
    <div class="flex items-center space-x-3">
        <!-- User Pill -->
        <div class="flex items-center space-x-2.5 bg-slate-50 py-1.5 px-3 rounded-2xl border border-slate-200/80">
            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="text-left hidden sm:block">
                <span class="text-xs font-bold text-slate-900 block leading-tight"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></span>
                <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($currentUser['role'] ?? 'admin') ?></span>
            </div>
        </div>

        <!-- Logout Button -->
        <button type="button" onclick="handleLogout()"
            class="py-1.5 px-3 rounded-xl border border-slate-200 bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 text-slate-600 text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer shadow-xs">
            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            <span class="hidden sm:inline">লগআউট</span>
        </button>
    </div>
</header>
