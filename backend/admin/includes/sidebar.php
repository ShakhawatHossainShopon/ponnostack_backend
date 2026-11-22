<?php

declare(strict_types=1);

/** @var string $activePage */
$activePage = $activePage ?? 'dashboard';

$unreadMsgCount = 0;
try {
    $msgCountModel = new \App\Models\ContactMessage();
    $unreadMsgCount = $msgCountModel->getUnreadCount();
} catch (\Throwable $e) {}

$tenantStats = ['payment_pending' => 0, 'setup_pending' => 0, 'active' => 0];
$pendingRenewals = 0;
try {
    $tModel = new \App\Models\Tenant();
    $tenantStats = $tModel->getStats();
    $payModel = new \App\Models\TenantPayment();
    $payStats = $payModel->getBillingStats();
    $pendingRenewals = $payStats['pending_renewals'] ?? 0;
} catch (\Throwable $e) {}

// Section 1: Overview
$overviewNav = [
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
];

// Section 2: Storefront Lifecycle (Completely Separate Worlds)
$storefrontNav = [
    [
        'id'         => 'active_tenants',
        'alias'      => ['active_tenants', 'tenants'],
        'label'      => 'অল অ্যাক্টিভ স্টোরফ্রন্ট',
        'sub'        => 'চলমান স্টোর ও বিলিং',
        'icon'       => 'store',
        'url'        => 'index.php?page=active_tenants',
        'badge'      => $tenantStats['active'],
        'badge_cls'  => 'bg-emerald-100 text-[#00684A] border border-emerald-200',
    ],
    [
        'id'         => 'setup_tenants',
        'alias'      => ['setup_tenants'],
        'label'      => 'সেটআপ পেন্ডিং',
        'sub'        => 'ডোমেইন বরাদ্দ অপেক্ষমাণ',
        'icon'       => 'globe',
        'url'        => 'index.php?page=setup_tenants',
        'badge'      => $tenantStats['setup_pending'],
        'badge_cls'  => $tenantStats['setup_pending'] > 0 ? 'bg-sky-500 text-white font-black animate-pulse' : 'bg-slate-100 text-slate-600',
    ],
    [
        'id'         => 'payment_tenants',
        'alias'      => ['payment_tenants'],
        'label'      => 'পেমেন্ট পেন্ডিং',
        'sub'        => 'বিকাশ TrxID যাচাই',
        'icon'       => 'wallet',
        'url'        => 'index.php?page=payment_tenants',
        'badge'      => $tenantStats['payment_pending'],
        'badge_cls'  => $tenantStats['payment_pending'] > 0 ? 'bg-amber-500 text-white font-black animate-pulse' : 'bg-slate-100 text-slate-600',
    ],
    [
        'id'         => 'billing',
        'alias'      => ['billing', 'invoices'],
        'label'      => 'মাসিক বিল ও ইনভয়েস',
        'sub'        => 'নবায়ন বিল কালেকশন',
        'icon'       => 'receipt',
        'url'        => 'index.php?page=billing',
        'badge'      => $pendingRenewals,
        'badge_cls'  => $pendingRenewals > 0 ? 'bg-indigo-600 text-white font-black animate-pulse' : 'bg-slate-100 text-slate-600',
    ],
];

// Section 3: Platform Settings & Communication
$platformNav = [
    [
        'id'    => 'packages',
        'label' => 'প্যাকেজ ইনফো',
        'sub'   => 'Package & Pricing',
        'icon'  => 'tag',
        'url'   => 'index.php?page=packages',
    ],
    [
        'id'    => 'inbox',
        'label' => 'ইনবক্স ও মেসেজ',
        'sub'   => 'Customer Inquiries',
        'icon'  => 'mail',
        'url'   => 'index.php?page=inbox',
        'badge' => $unreadMsgCount,
        'badge_cls' => 'bg-rose-500 text-white',
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
<div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/15 backdrop-blur-xs z-30 hidden lg:hidden"></div>

<!-- Sidebar Component (Pure Light Mode) -->
<aside id="mainSidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200/90 flex flex-col justify-between transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out shrink-0 overflow-y-auto">
    <div>
        <!-- Brand / Logo Area -->
        <div class="h-16 px-5 flex items-center justify-between border-b border-slate-100">
            <a href="index.php?page=dashboard" class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-[#00684A] text-white flex items-center justify-center font-black shadow-md shadow-[#00684A]/25">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-slate-900 leading-tight">Ponnostack</h1>
                    <span class="text-[10px] font-bold text-[#00684A] uppercase tracking-wider block">Admin Panel</span>
                </div>
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Section 1: Overview -->
        <div class="p-3.5 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 py-1 block">মেইন মেনু</span>
            <?php foreach ($overviewNav as $item): ?>
                <?php $isActive = ($activePage === $item['id']); ?>
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   class="group flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs font-bold transition <?= $isActive ? 'bg-emerald-50 text-emerald-900 border border-emerald-200/90 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-[#00684A]' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                        <div class="truncate">
                            <span class="block leading-tight"><?= htmlspecialchars($item['label']) ?></span>
                            <span class="text-[10px] font-normal <?= $isActive ? 'text-emerald-700' : 'text-slate-400' ?>"><?= htmlspecialchars($item['sub']) ?></span>
                        </div>
                    </div>
                    <?php if ($isActive): ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#00684A] shrink-0"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Section 2: Storefront Lifecycle (Each Stage Has Its Own Sidebar Link) -->
        <div class="px-3.5 pb-2 space-y-1">
            <div class="flex items-center justify-between px-3 py-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">মার্চেন্ট স্টোরফ্রন্ট</span>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            </div>

            <?php foreach ($storefrontNav as $item): ?>
                <?php $isActive = in_array($activePage, $item['alias'] ?? [$item['id']], true); ?>
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   class="group flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs font-bold transition <?= $isActive ? 'bg-emerald-50 text-emerald-900 border border-emerald-200/90 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-[#00684A]' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                        <div class="truncate">
                            <span class="block leading-tight"><?= htmlspecialchars($item['label']) ?></span>
                            <span class="text-[10px] font-normal <?= $isActive ? 'text-emerald-700' : 'text-slate-400' ?>"><?= htmlspecialchars($item['sub']) ?></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <?php if (isset($item['badge'])): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono shadow-2xs <?= $item['badge_cls'] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= $item['badge'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($isActive): ?>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#00684A] shrink-0"></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Section 3: Platform Settings & Communication -->
        <div class="px-3.5 pb-3 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 py-1 block">প্ল্যাটফর্ম সেটিংস</span>
            <?php foreach ($platformNav as $item): ?>
                <?php $isActive = ($activePage === $item['id']); ?>
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   class="group flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs font-bold transition <?= $isActive ? 'bg-emerald-50 text-emerald-900 border border-emerald-200/90 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-[#00684A]' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                        <div class="truncate">
                            <span class="block leading-tight"><?= htmlspecialchars($item['label']) ?></span>
                            <span class="text-[10px] font-normal <?= $isActive ? 'text-emerald-700' : 'text-slate-400' ?>"><?= htmlspecialchars($item['sub']) ?></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black <?= $item['badge_cls'] ?? 'bg-rose-500 text-white' ?> shadow-2xs">
                                <?= $item['badge'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($isActive): ?>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#00684A] shrink-0"></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar Footer / Environment Badge -->
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 m-3 rounded-2xl">
        <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400">Environment</span>
            <span class="font-bold text-[#00684A] bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md uppercase font-mono text-[10px]">
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
