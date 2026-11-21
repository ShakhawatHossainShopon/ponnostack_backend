<?php

declare(strict_types=1);

/** @var string $activePage */
$activePage = $activePage ?? 'admins';

$navItems = [
    [
        'id'    => 'dashboard',
        'label' => 'ড্যাশবোর্ড',
        'sub'   => 'Overview & Stats',
        'icon'  => 'layout-dashboard',
        'url'   => 'index.php?page=dashboard',
    ],
    [
        'id'    => 'admins',
        'label' => 'অ্যাডমিন ইউজার',
        'sub'   => 'Access & Roles',
        'icon'  => 'users',
        'url'   => 'index.php?page=admins',
    ],
    [
        'id'    => 'tenants',
        'label' => 'স্টোর ও টেন্যান্টস',
        'sub'   => 'Client Stores',
        'icon'  => 'store',
        'url'   => 'index.php?page=tenants',
    ],
    [
        'id'    => 'settings',
        'label' => 'প্ল্যাটফর্ম সেটিংস',
        'sub'   => 'System Config',
        'icon'  => 'settings',
        'url'   => 'index.php?page=settings',
    ],
];
?>
<!-- Sidebar Overlay for Mobile -->
<div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs z-30 hidden lg:hidden"></div>

<!-- Sidebar Component (Pure Light Mode) -->
<aside id="mainSidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200/90 flex flex-col justify-between transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out shrink-0">
    <div>
        <!-- Brand / Logo Area -->
        <div class="h-16 px-5 flex items-center justify-between border-b border-slate-100">
            <a href="index.php?page=dashboard" class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black shadow-md shadow-indigo-600/20">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-slate-900 leading-tight">Ponnostack</h1>
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block">Admin Panel</span>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links Group -->
        <div class="p-3.5 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 py-1.5 block">মেইন মেনু</span>
            
            <?php foreach ($navItems as $item): ?>
                <?php $isActive = ($activePage === $item['id']); ?>
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   class="group flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs font-bold transition <?= $isActive ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/70 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                        <div class="truncate">
                            <span class="block leading-tight"><?= htmlspecialchars($item['label']) ?></span>
                            <span class="text-[10px] font-normal <?= $isActive ? 'text-indigo-500' : 'text-slate-400' ?>"><?= htmlspecialchars($item['sub']) ?></span>
                        </div>
                    </div>
                    <?php if ($isActive): ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 shrink-0"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar Footer / Environment Badge -->
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 m-3 rounded-2xl">
        <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400">Environment</span>
            <span class="font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md uppercase font-mono text-[10px]">
                <?= htmlspecialchars(function_exists('env') ? (string)env('APP_ENV', 'local') : 'local') ?>
            </span>
        </div>
        <div class="mt-2 text-[10px] text-slate-400 flex items-center justify-between">
            <span>Core v2.0</span>
            <span class="inline-flex items-center text-emerald-600 font-semibold gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
            </span>
        </div>
    </div>
</aside>
