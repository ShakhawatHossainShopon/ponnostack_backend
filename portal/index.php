<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$tenant = requirePortalAuth();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLive = (strpos($host, 'ponnostack.shop') !== false);
$homeUrl = $isLive ? 'https://ponnostack.shop/' : ($protocol . $host . '/Ponnostack/');

$storeName = htmlspecialchars($tenant['name']);
$slug = htmlspecialchars($tenant['slug']);
$ownerName = htmlspecialchars($tenant['owner_name']);
$ownerEmail = htmlspecialchars($tenant['owner_email']);
$ownerPhone = htmlspecialchars($tenant['owner_phone'] ?? '-');
$address = htmlspecialchars($tenant['address'] ?? 'ঠিকানা উল্লেখ নেই');
$packageName = htmlspecialchars($tenant['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ');
$monthlyFee = (int)($tenant['monthly_fee'] ?? 199);
$productLimit = (int)($tenant['product_limit'] ?? 50);
$trxId = htmlspecialchars($tenant['payment_trx_id'] ?? '-');
$senderNumber = htmlspecialchars($tenant['payment_sender'] ?? '-');
$status = strtolower($tenant['status'] ?? 'pending');

// Logo URL handling
$rawLogo = $tenant['logo'] ?? null;
if (!empty($rawLogo)) {
    $logoUrl = (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://'))
        ? $rawLogo 
        : rtrim($homeUrl, '/') . '/' . ltrim($rawLogo, '/');
} else {
    $logoUrl = $tenant['logo_url'] ?? null;
}

// Strict Dynamic URL Logic:
// NO hardcoded / pre-assigned domain is promised or shown.
// URLs are only defined and displayed when ADMIN assigns them and activates the store.
$isActive = ($status === 'active');
$assignedDomain = trim((string)($tenant['domain'] ?? ''));
$assignedAdminUrl = trim((string)($tenant['admin_url'] ?? ''));

$isStoreLive = ($isActive && !empty($assignedDomain));

if ($isStoreLive) {
    $storeUrl = str_starts_with($assignedDomain, 'http') ? $assignedDomain : "https://{$assignedDomain}";
    $storeDisplayUrl = preg_replace('#^https?://#', '', rtrim($storeUrl, '/'));
    $clientAdminUrl = !empty($assignedAdminUrl) 
        ? (str_starts_with($assignedAdminUrl, 'http') ? $assignedAdminUrl : "https://{$assignedAdminUrl}")
        : rtrim($storeUrl, '/') . '/admin/';
} else {
    $storeUrl = null;
    $storeDisplayUrl = 'অ্যাডমিন অনুমোদনের পর লিংক দেওয়া হবে';
    $clientAdminUrl = null;
}

// Subscription Expiry / Renewal date & Days Left Logic
$subscriptionEndsAt = $tenant['subscription_ends_at'] ?? null;
$billingDay = (int)($tenant['billing_day'] ?? 10);

$bnMonths = [
    'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
    'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
    'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
    'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
];

$renewalDate = 'অ্যাক্টিভেশনের পর নির্ধারিত হবে';
$renewalDateShort = '-';
$daysLeft = null;
$diffSeconds = 0;
$isOverdue = (!empty($tenant['is_overdue']) || $status === 'expired' || $status === 'suspended');

if (!empty($subscriptionEndsAt)) {
    $expiryTimestamp = strtotime($subscriptionEndsAt);
    $diffSeconds = $expiryTimestamp - time();
    $day = date('d', $expiryTimestamp);
    $engMonth = date('F', $expiryTimestamp);
    $year = date('Y', $expiryTimestamp);
    $monthBn = $bnMonths[$engMonth] ?? $engMonth;

    $renewalDate = "{$day} {$monthBn}, {$year}";
    $renewalDateShort = date('d M, Y', $expiryTimestamp);

    $diffDays = (int)ceil($diffSeconds / 86400);
    $daysLeft = max(0, $diffDays);

    if ($diffSeconds <= 0) {
        $isOverdue = true;
        $daysLeft = 0;
    }
}

$isExpToday = (!empty($subscriptionEndsAt) && date('Y-m-d', strtotime($subscriptionEndsAt)) === date('Y-m-d'));

if (empty($subscriptionEndsAt)) {
    $daysLeftLabel = 'স্টোর অ্যাক্টিভেশনের পর নির্ধারিত হবে';
    $daysLeftBadgeClass = 'bg-slate-100 text-slate-600 border border-slate-200';
    $daysLeftIcon = 'help-circle';
} elseif ($isOverdue) {
    $daysLeftLabel = 'মেয়াদ উত্তীর্ণ (বিল বকেয়া)';
    $daysLeftBadgeClass = 'bg-rose-100 text-rose-800 border border-rose-300';
    $daysLeftIcon = 'alert-octagon';
} elseif ($isExpToday) {
    $daysLeftLabel = 'আজকেই শেষ দিন (রাত ১১:৫৯)';
    $daysLeftBadgeClass = 'bg-rose-100 text-rose-800 border border-rose-300 animate-pulse';
    $daysLeftIcon = 'alert-triangle';
} elseif ($daysLeft === 1) {
    $daysLeftLabel = 'আর মাত্র ১ দিন বাকি';
    $daysLeftBadgeClass = 'bg-amber-100 text-amber-900 border border-amber-300 animate-pulse';
    $daysLeftIcon = 'clock';
} elseif ($daysLeft <= 5) {
    $daysLeftLabel = "আর মাত্র {$daysLeft} দিন বাকি";
    $daysLeftBadgeClass = 'bg-amber-100 text-amber-900 border border-amber-300 animate-pulse';
    $daysLeftIcon = 'clock';
} else {
    $daysLeftLabel = "আর {$daysLeft} দিন বাকি";
    $daysLeftBadgeClass = 'bg-emerald-100 text-[#00684A] border border-emerald-300';
    $daysLeftIcon = 'shield-check';
}

$paymentModel = new \App\Models\TenantPayment();
$tenantPayments = $paymentModel->getByTenantId((int)$tenant['id']);
$universalBkash = '01886161110';
?>
<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= $storeName ?> | কাস্টমার ড্যাশবোর্ড | Ponnostack</title>
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#00684A">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($homeUrl) ?>assets/images/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($homeUrl) ?>assets/images/favicon.png">

    <!-- Fonts: Hind Siliguri & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

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
        /* Touch optimization & smooth scrolling */
        * {
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between text-slate-800 antialiased selection:bg-brand-100 selection:text-brand-900 overflow-x-hidden">

    <!-- TOP HEADER (Clean & Mobile-Optimized) -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-3.5 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
            <!-- Left: Back Button & Logo -->
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                <!-- Back Button -->
                <a href="<?= htmlspecialchars($homeUrl) ?>" class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:scale-95 border border-slate-200/80 text-[11px] sm:text-xs font-bold text-slate-700 transition group shrink-0" title="হোমপেজে ফিরে যান">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5 text-slate-500 group-hover:text-brand-600"></i>
                    <span class="hidden sm:inline">হোমপেজ</span>
                    <span class="sm:hidden">হোম</span>
                </a>

                <!-- Brand Logo & Portal Tag -->
                <a href="<?= htmlspecialchars($homeUrl) ?>" class="flex items-center shrink-0">
                    <img src="<?= htmlspecialchars($homeUrl) ?>assets/images/logo.png" alt="Ponnostack Logo" class="h-6 sm:h-8 w-auto object-contain" width="110" height="28">
                </a>
                <span class="hidden md:inline-block px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-[10px] sm:text-[11px] font-bold text-brand-800 shrink-0">
                    কাস্টমার পোর্টাল
                </span>
            </div>

            <!-- Right Actions: Store Pill & Logout -->
            <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
                <div class="flex items-center space-x-1.5 px-2 sm:px-2.5 py-1 bg-brand-50 border border-brand-200 rounded-xl text-[10px] sm:text-[11px] font-bold text-brand-800 max-w-[100px] sm:max-w-[180px]">
                    <span class="w-2 h-2 rounded-full shrink-0 <?= $isStoreLive ? 'bg-[#00ED64] animate-pulse' : 'bg-amber-400' ?>"></span>
                    <span class="truncate"><?= $storeName ?></span>
                </div>

                <a href="logout.php" onclick="handleLogout(event)" class="px-2.5 sm:px-3.5 py-1 sm:py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 border border-slate-200 rounded-xl text-[11px] sm:text-xs font-bold text-slate-600 transition flex items-center space-x-1 cursor-pointer" title="লগআউট">
                    <i data-lucide="log-out" class="w-3.5 h-3.5 shrink-0"></i>
                    <span class="hidden sm:inline">লগআউট</span>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN DASHBOARD CONTENT (Mobile Clean, No Clutter) -->
    <main class="max-w-6xl mx-auto px-3.5 sm:px-6 py-4 sm:py-8 w-full flex-1 space-y-4 sm:space-y-6">

        <!-- Back Navigation Link & Quick Info -->
        <div class="flex items-center justify-between text-xs pb-0.5">
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="inline-flex items-center space-x-1 text-slate-500 hover:text-brand-600 font-bold transition group">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-1"></i>
                <span>প্রধান ওয়েবসাইটে ফিরে যান</span>
            </a>
            <span class="text-[11px] text-slate-400">কাস্টমার অ্যাকাউন্ট</span>
        </div>

        <!-- 1. Store Header & Identity Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-7 shadow-sm">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3.5 sm:gap-5">
                
                <!-- Logo & Store Details -->
                <div class="flex items-center space-x-3 sm:space-x-4 w-full sm:w-auto min-w-0">
                    <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                        <?php if ($logoUrl): ?>
                            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= $storeName ?>" class="w-full h-full object-contain p-1">
                        <?php else: ?>
                            <i data-lucide="store" class="w-7 h-7 sm:w-8 sm:h-8 text-brand-600"></i>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <h1 class="text-base sm:text-2xl font-black text-slate-900 truncate leading-snug"><?= $storeName ?></h1>
                            <?php if ($isStoreLive): ?>
                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-brand-800 text-[10px] font-black border border-emerald-200 inline-flex items-center space-x-1 shrink-0">
                                    <i data-lucide="check" class="w-3 h-3 text-brand-600"></i>
                                    <span>সক্রিয় (Live)</span>
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 text-[10px] font-black border border-amber-200 inline-flex items-center space-x-1 shrink-0">
                                    <i data-lucide="clock" class="w-3 h-3 text-amber-700"></i>
                                    <span>অপেক্ষমাণ (Pending)</span>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($subscriptionEndsAt)): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-flex items-center space-x-1 shrink-0 <?= $daysLeftBadgeClass ?>" title="বিল এক্সপায়ার ডেট: <?= $renewalDate ?>">
                                    <i data-lucide="<?= $daysLeftIcon ?>" class="w-3 h-3"></i>
                                    <span>বিল এক্সপায়ার: <?= $renewalDate ?> (<?= $daysLeftLabel ?>)</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[11px] sm:text-xs text-slate-500 font-mono mt-0.5 flex items-center space-x-1 truncate">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span class="truncate"><?= $storeDisplayUrl ?></span>
                        </p>
                    </div>
                </div>

                <!-- Primary Action Button: Full-width on mobile -->
                <div class="w-full sm:w-auto shrink-0 pt-1 sm:pt-0">
                    <?php if ($isStoreLive): ?>
                        <a href="<?= $storeUrl ?>" target="_blank" class="w-full sm:w-auto px-5 py-2.5 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-bold rounded-xl transition shadow-brand-glow flex items-center justify-center space-x-1.5 cursor-pointer">
                            <span>স্টোরফ্রন্ট ভিজিট করুন</span>
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php else: ?>
                        <div class="w-full sm:w-auto px-3.5 py-2 bg-amber-50 border border-amber-200 text-amber-900 text-[11px] sm:text-xs font-bold rounded-xl flex items-center justify-center space-x-1.5 shadow-2xs">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                            <span>অ্যাডমিন লিংক বরাদ্দের অপেক্ষায়...</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- 2. Onboarding & Activation Multi-Step Progress Tracker (Mobile Clean Stepper) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-3.5">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                <div class="flex items-center space-x-1.5 sm:space-x-2">
                    <i data-lucide="git-commit" class="w-4 h-4 text-brand-600"></i>
                    <h3 class="text-xs font-black uppercase text-slate-900">স্টোর অনবোর্ডিং প্রোগ্রেস</h3>
                </div>
                <span class="text-[11px] font-bold <?= $isStoreLive ? 'text-brand-600' : 'text-amber-600' ?>">
                    <?= $isStoreLive ? '৩/৩ ধাপ সম্পন্ন' : '২/৩ ধাপ চলমান' ?>
                </span>
            </div>

            <!-- Stepper Grid (Cards stacked cleanly on mobile) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 sm:gap-4">
                
                <!-- Step 1: Order & Payment Submitted -->
                <div class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-emerald-50/70 border border-emerald-200/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-brand-600 text-white flex items-center justify-center text-xs font-bold shadow-2xs">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="text-[10px] font-bold text-brand-800 bg-white px-2 py-0.5 rounded-md border border-emerald-200">ধাপ ১: সম্পন্ন ✓</span>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-slate-900">অর্ডার ও পেমেন্ট সাবমিট</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                            স্টোর স্লাগ ও বিকাশ ট্রানজেকশন (TrxID: <span class="font-mono font-bold text-brand-700"><?= $trxId ?></span>) সফলভাবে সংরক্ষিত হয়েছে।
                        </p>
                    </div>
                </div>

                <!-- Step 2: Admin Verification & Cloud Deployment -->
                <div class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl <?= $isStoreLive ? 'bg-emerald-50/70 border border-emerald-200/80' : 'bg-amber-50/80 border border-amber-300' ?> space-y-1.5">
                    <div class="flex items-center justify-between">
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg <?= $isStoreLive ? 'bg-brand-600 text-white' : 'bg-amber-500 text-white animate-pulse' ?> flex items-center justify-center text-xs font-bold shadow-2xs">
                            <?php if ($isStoreLive): ?>
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <?php else: ?>
                                <i data-lucide="hourglass" class="w-3.5 h-3.5"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] font-bold <?= $isStoreLive ? 'text-brand-800 bg-white border-emerald-200' : 'text-amber-900 bg-amber-100 border-amber-300' ?> px-2 py-0.5 rounded-md border">
                            <?= $isStoreLive ? 'ধাপ ২: সম্পন্ন ✓' : 'ধাপ ২: চলমান ⏳' ?>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-slate-900">অ্যাডমিন যাচাই ও ডেপ্লয়মেন্ট</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                            <?= $isStoreLive 
                                ? 'অ্যাডমিন আপনার বিকাশ পেমেন্ট নিশ্চিত করেছে এবং ক্লাউড স্টোরফ্রন্ট প্রস্তুত করেছে।' 
                                : 'অ্যাডমিন টিম আপনার পেমেন্ট ভেরিফাই করছে এবং ক্লাউডে স্টোর ডেপ্লয়মেন্ট প্রস্তুত করছে।' ?>
                        </p>
                    </div>
                </div>

                <!-- Step 3: Store Live & Control Panel Ready -->
                <div class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl <?= $isStoreLive ? 'bg-emerald-50/70 border border-emerald-200/80' : 'bg-slate-50 border border-slate-200' ?> space-y-1.5">
                    <div class="flex items-center justify-between">
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg <?= $isStoreLive ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-500' ?> flex items-center justify-center text-xs font-bold shadow-2xs">
                            <?php if ($isStoreLive): ?>
                                <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                            <?php else: ?>
                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] font-bold <?= $isStoreLive ? 'text-brand-800 bg-white border-emerald-200' : 'text-slate-500 bg-white border-slate-200' ?> px-2 py-0.5 rounded-md border">
                            <?= $isStoreLive ? 'ধাপ ৩: সক্রিয় 🎉' : 'ধাপ ৩: অপেক্ষমাণ 🔒' ?>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-slate-900">স্টোর লাইভ ও অ্যাক্টিভ</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                            <?= $isStoreLive 
                                ? 'আপনার স্টোরফ্রন্ট এবং অ্যাডমিন কন্ট্রোল প্যানেল সম্পূর্ণ উন্মুক্ত ও সক্রিয়।' 
                                : 'অ্যাডমিন কর্তৃক চূড়ান্ত লাইভ লিংক বরাদ্দ সম্পন্ন হওয়ার পর স্টোর চালু হবে।' ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- 3. Status Notification Banner -->
        <?php if (!$isStoreLive): ?>
            <?php if ($status === 'setup_pending'): ?>
                <div class="p-3.5 sm:p-4 bg-sky-50/90 border border-sky-200 rounded-xl sm:rounded-2xl flex items-start space-x-2.5 sm:space-x-3 text-xs text-sky-950">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sky-600"></i>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <h3 class="text-xs sm:text-sm font-black text-sky-900">পেমেন্ট অনুমোদিত হয়েছে! ডোমেইন ও লাইভ লিংক সেটআপ চলছে</h3>
                        <p class="text-[11px] sm:text-xs text-sky-800 leading-relaxed">
                            আপনার বিকাশ ট্রানজেকশন (TrxID: <strong class="font-mono"><?= $trxId ?></strong>) সফলভাবে যাচাই করা হয়েছে। অ্যাডমিন টিম এখন আপনার স্টোরফ্রন্টের জন্য লাইভ ইউআরএল ও অ্যাডমিন প্যানেল সেটআপ করছেন। সম্পন্ন হওয়ামাত্রই স্টোর লাইভ হয়ে যাবে।
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-3.5 sm:p-4 bg-amber-50/80 border border-amber-200 rounded-xl sm:rounded-2xl flex items-start space-x-2.5 sm:space-x-3 text-xs text-amber-950">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <h3 class="text-xs sm:text-sm font-black text-amber-900">আপনার সাবস্ক্রিপশন ও পেমেন্ট রিকোয়েস্ট অপেক্ষমাণ রয়েছে</h3>
                        <p class="text-[11px] sm:text-xs text-amber-800 leading-relaxed">
                            আপনি বিকাশ নম্বরে যে ফি পাঠিয়েছেন (TrxID: <strong class="font-mono"><?= $trxId ?></strong>), তা যাচাই করে অ্যাডমিন টিম আপনার স্টোরফ্রন্টের জন্য লাইভ ইউআরএল এবং অ্যাডমিন কন্ট্রোল প্যানেল লিঙ্ক সক্রিয় করে দেবেন।
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="p-3.5 sm:p-4 bg-emerald-50/80 border border-emerald-200 rounded-xl sm:rounded-2xl flex items-start space-x-2.5 sm:space-x-3 text-xs text-brand-950">
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-emerald-100 text-brand-700 flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                </div>
                <div class="space-y-1 min-w-0">
                    <h3 class="text-xs sm:text-sm font-black text-brand-900">আপনার স্টোরফ্রন্ট বর্তমানে লাইভ ও সম্পূর্ণ সক্রিয়</h3>
                    <p class="text-[11px] sm:text-xs text-brand-800 leading-relaxed">
                        আপনার স্টোরফ্রন্ট (<a href="<?= $storeUrl ?>" target="_blank" class="underline font-bold font-mono"><?= $storeDisplayUrl ?></a>) সফলভাবে ডেপ্লয় করা হয়েছে এবং সক্রিয় রয়েছে।
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4. Monthly Subscription Billing & Invoices Section (High-Priority Placement) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-7 shadow-sm space-y-5">
            <!-- Header Bar with Status & Action Button -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-200 flex items-center justify-center font-bold shadow-2xs shrink-0">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-black text-slate-900">মাসিক সাবস্ক্রিপশন বিল ও ইনভয়েস</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $daysLeftBadgeClass ?>">
                                <?= $daysLeftLabel ?>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">নিয়মিত মাসিক বিল পরিশোধ করুন, বিলিং মেয়াদের হিসাব রাখুন এবং অফিসিয়াল ইনভয়েস ডাউনলোড করুন</p>
                    </div>
                </div>

                <!-- Pay Bill Action Button -->
                <button type="button" onclick="openPayMonthlyModal()"
                    class="inline-flex items-center justify-center space-x-2 py-2.5 px-5 rounded-xl bg-[#00684A] hover:bg-[#00523A] active:scale-95 text-white text-xs font-bold shadow-brand-glow transition cursor-pointer self-start sm:self-auto shrink-0">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                    <span><?= $isOverdue ? 'বকেয়া বিল এখনই পরিশোধ করুন' : 'মাসিক বিল পরিশোধ করুন' ?></span>
                </button>
            </div>

            <!-- Expiration & Countdown Highlight Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- 1. Next Expiration / Due Date -->
                <div class="p-4 rounded-2xl border <?= $isOverdue ? 'border-rose-200 bg-rose-50/40' : ($daysLeft <= 5 && $daysLeft !== null ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200/90 bg-slate-50/60') ?>">
                    <div class="flex items-center justify-between text-slate-500 mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider">বিল এক্সপায়ার ডেট</span>
                        <div class="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 <?= $isOverdue ? 'text-rose-600' : 'text-[#00684A]' ?>"></i>
                        </div>
                    </div>
                    <div class="text-base sm:text-lg font-black <?= $isOverdue ? 'text-rose-700' : 'text-slate-900' ?>">
                        <?= $renewalDate ?>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium block mt-1">
                        স্থায়ী বিলিং ডেট: প্রতি মাসের <?= $billingDay ?> তারিখ
                    </span>
                </div>

                <!-- 2. Days Left Countdown -->
                <div class="p-4 rounded-2xl border <?= $isOverdue ? 'border-rose-200 bg-rose-50/40' : ($daysLeft <= 5 && $daysLeft !== null ? 'border-amber-200 bg-amber-50/40' : 'border-emerald-200/90 bg-emerald-50/30') ?>">
                    <div class="flex items-center justify-between text-slate-500 mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider">কতদিন বাকি আছে</span>
                        <div class="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center">
                            <i data-lucide="<?= $daysLeftIcon ?>" class="w-3.5 h-3.5 <?= $isOverdue ? 'text-rose-600' : ($daysLeft <= 5 && $daysLeft !== null ? 'text-amber-600' : 'text-[#00684A]') ?>"></i>
                        </div>
                    </div>
                    <div class="text-base sm:text-lg font-black <?= $isOverdue ? 'text-rose-700' : ($daysLeft <= 5 && $daysLeft !== null ? 'text-amber-800' : 'text-emerald-900') ?>">
                        <?= $daysLeftLabel ?>
                    </div>
                    <span class="text-[10px] <?= $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-400 font-medium' ?> block mt-1">
                        <?= $isOverdue ? '⚠️ সার্ভিস সাময়িক স্থগিতের ঝুঁকিতে' : ($isExpToday ? '⚠️ আজ রাত ১১:৫৯ পর্যন্ত সচল' : 'সার্ভিস সচল রাখার সময়সীমা') ?>
                    </span>
                </div>

                <!-- 3. Monthly Fee Amount -->
                <div class="p-4 rounded-2xl border border-slate-200/90 bg-slate-50/60">
                    <div class="flex items-center justify-between text-slate-500 mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider">মাসিক ফি</span>
                        <div class="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-3.5 h-3.5 text-slate-600"></i>
                        </div>
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 font-mono">
                        ৳ <?= number_format($monthlyFee) ?> <span class="text-xs font-normal text-slate-500 font-sans">/ মাস</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium block mt-1">
                        প্যাকেজ: <?= htmlspecialchars($packageName) ?>
                    </span>
                </div>

                <!-- 4. Quick Pay Action -->
                <div class="p-4 rounded-2xl border border-slate-200/90 bg-slate-50/60 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider">বিল পেমেন্ট অ্যাকশন</span>
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                    </div>
                    <button type="button" onclick="openPayMonthlyModal()"
                        class="w-full py-2 px-3 rounded-xl <?= $isOverdue ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-[#00684A] hover:bg-[#00523A] text-white' ?> text-xs font-bold transition flex items-center justify-center space-x-1.5 cursor-pointer shadow-xs">
                        <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                        <span><?= $isOverdue ? 'এখনই বকেয়া দিন' : 'মাসিক বিল পরিশোধ' ?></span>
                    </button>
                    <span class="text-[10px] text-slate-400 text-center block mt-1">
                        বিকাশ সেন্ড মানি ভেরিফিকেশন
                    </span>
                </div>
            </div>

            <!-- Billing Status Alert Box -->
            <?php if ($isOverdue): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold shrink-0">
                            <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <strong class="text-rose-900 font-black block">আপনার স্টোরের সাবস্ক্রিপশন মেয়াদ শেষ হয়েছে (বিল বকেয়া)!</strong>
                            <span class="text-rose-700 text-[11px]">সার্ভিস সচল রাখতে অনুগ্রহ করে চলতি মাসের ফি (৳<?= number_format($monthlyFee) ?>) পরিশোধ করুন।</span>
                        </div>
                    </div>
                    <button type="button" onclick="openPayMonthlyModal()" class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shrink-0 cursor-pointer self-start sm:self-auto">
                        এখনই পরিশোধ করুন
                    </button>
                </div>
            <?php elseif ($isExpToday): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs animate-pulse">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold shrink-0">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <strong class="text-rose-900 font-black block">আজকেই আপনার স্টোরের মাসিক মেয়াদ শেষ হচ্ছে!</strong>
                            <span class="text-rose-700 text-[11px]">নিরবচ্ছিন্ন সার্ভিস নিশ্চিত করতে পরবর্তী মাসের বিল পরিশোধ করে রাখুন।</span>
                        </div>
                    </div>
                    <button type="button" onclick="openPayMonthlyModal()" class="px-3.5 py-1.5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white font-bold text-xs shrink-0 cursor-pointer self-start sm:self-auto">
                        বিল দিন
                    </button>
                </div>
            <?php elseif ($daysLeft !== null && $daysLeft <= 5): ?>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold shrink-0">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <strong class="text-amber-900 font-black block">মেয়াদ শেষ হতে আর মাত্র <?= $daysLeft ?> দিন বাকি আছে।</strong>
                            <span class="text-amber-700 text-[11px]">স্থায়ী বিলিং ডেট: প্রতি মাসের <?= $tenant['billing_day'] ?? 10 ?> তারিখ।</span>
                        </div>
                    </div>
                    <button type="button" onclick="openPayMonthlyModal()" class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shrink-0 cursor-pointer self-start sm:self-auto">
                        বিল দিন
                    </button>
                </div>
            <?php endif; ?>

            <!-- Invoices & Transaction History Table -->
            <div class="border border-slate-200/90 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="py-3 px-4">ইনভয়েস নং</th>
                                <th class="py-3 px-4">তারিখ</th>
                                <th class="py-3 px-4">বিলিং মাস</th>
                                <th class="py-3 px-4">পেমেন্ট ধরন</th>
                                <th class="py-3 px-4">TrxID ও মেথড</th>
                                <th class="py-3 px-4">পরিমাণ</th>
                                <th class="py-3 px-4">স্ট্যাটাস</th>
                                <th class="py-3 px-4 text-right">ইনভয়েস রশিদ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($tenantPayments)): ?>
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-400">
                                        এখনো কোনো পেমেন্ট রেকর্ড পাওয়া যায়নি।
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tenantPayments as $tp): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 px-4 font-mono font-bold text-[#00684A]">
                                            <?= htmlspecialchars($tp['invoice_no']) ?>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-slate-500">
                                            <?= $tp['formatted_date_short'] ?>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-slate-800">
                                            <?= htmlspecialchars($tp['billing_month'] ?? 'সাধারণ বিল') ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold <?= $tp['type_badge_cls'] ?>">
                                                <i data-lucide="<?= $tp['type_icon'] ?>" class="w-2.5 h-2.5"></i>
                                                <span><?= $tp['type_label'] ?></span>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-mono font-bold text-slate-800 block"><?= htmlspecialchars($tp['trx_id']) ?></span>
                                            <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($tp['payment_method']) ?> (<?= htmlspecialchars($tp['sender_number'] ?? '-') ?>)</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono font-black text-slate-900 text-xs">
                                            ৳ <?= number_format((float)$tp['amount']) ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold <?= $tp['status_badge_cls'] ?>">
                                                <i data-lucide="<?= $tp['status_icon'] ?>" class="w-2.5 h-2.5"></i>
                                                <span><?= $tp['status_label'] ?></span>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button type="button" onclick='showPortalInvoice(<?= json_encode($tp, JSON_UNESCAPED_UNICODE) ?>)'
                                                class="inline-flex items-center space-x-1 py-1 px-2.5 rounded-lg border border-slate-200 text-slate-700 hover:text-[#00684A] hover:bg-emerald-50 hover:border-emerald-200 font-bold text-[11px] transition cursor-pointer">
                                                <i data-lucide="printer" class="w-3 h-3"></i>
                                                <span>ইনভয়েস</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Grid of Cards: Quick Actions, Subscription & Owner Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-5">
            
            <!-- Card 1: Subscription & Package -->
            <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-3 sm:space-y-4">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div class="flex items-center space-x-1.5">
                        <i data-lucide="package" class="w-4 h-4 text-brand-600"></i>
                        <h3 class="text-xs font-black uppercase text-slate-900">সাবস্ক্রিপশন বিবরণ</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-brand-50 text-brand-700 font-mono text-[10px] font-bold">
                        <?= htmlspecialchars($tenant['package_slug'] ?? 'starter') ?>
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">প্যাকেজ:</span>
                        <strong class="font-bold text-slate-800"><?= $packageName ?></strong>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">মাসিক ফি:</span>
                        <strong class="font-mono font-bold text-brand-700">৳ <?= number_format($monthlyFee) ?>/মাস</strong>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">প্রোডাক্ট ক্যাপাসিটি:</span>
                        <span class="font-bold text-slate-800"><?= $productLimit > 0 ? "{$productLimit}টি প্রোডাক্ট" : "আনলিমিটেড" ?></span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">পেমেন্ট মেথড:</span>
                        <span class="font-bold text-pink-600 font-mono">bKash (Send Money)</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">প্রেরক নম্বর:</span>
                        <span class="font-mono text-slate-800 font-bold"><?= $senderNumber ?></span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">TrxID:</span>
                        <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"><?= $trxId ?></span>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-500">পরবর্তী নবায়ন:</span>
                        <div class="text-right">
                            <span class="font-mono font-bold block <?= $isOverdue ? 'text-rose-600' : 'text-slate-800' ?>"><?= $renewalDate ?></span>
                            <span class="text-[10px] font-bold <?= $isOverdue ? 'text-rose-600' : ($daysLeft !== null && $daysLeft <= 5 ? 'text-amber-600' : 'text-emerald-700') ?>">
                                <?= $daysLeftLabel ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Storefront Management Quick Access -->
            <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-3 sm:space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center space-x-1.5 pb-2.5 border-b border-slate-100 mb-3 sm:mb-4">
                        <i data-lucide="sliders" class="w-4 h-4 text-brand-600"></i>
                        <h3 class="text-xs font-black uppercase text-slate-900">কন্ট্রোল প্যানেল</h3>
                    </div>

                    <div class="space-y-2">
                        <?php if ($isStoreLive && $clientAdminUrl): ?>
                            <!-- Active Client Store Admin -->
                            <a href="<?= $clientAdminUrl ?>" target="_blank" class="w-full p-3 rounded-xl sm:rounded-2xl bg-slate-50 hover:bg-brand-50 border border-slate-200 hover:border-brand-300 transition flex items-center justify-between group cursor-pointer active:scale-98">
                                <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg sm:rounded-xl bg-white border border-slate-200 text-brand-600 flex items-center justify-center font-bold shadow-2xs shrink-0">
                                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                    </div>
                                    <div class="text-left min-w-0">
                                        <span class="text-xs font-black text-slate-800 group-hover:text-brand-700 block truncate">স্টোর অ্যাডমিন ড্যাশবোর্ড</span>
                                        <span class="text-[10px] text-slate-400 block truncate">প্রোডাক্ট, স্টক ও অর্ডার পরিচালনা</span>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-transform shrink-0"></i>
                            </a>

                            <!-- Active Storefront URL -->
                            <a href="<?= $storeUrl ?>" target="_blank" class="w-full p-3 rounded-xl sm:rounded-2xl bg-slate-50 hover:bg-brand-50 border border-slate-200 hover:border-brand-300 transition flex items-center justify-between group cursor-pointer active:scale-98">
                                <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg sm:rounded-xl bg-white border border-slate-200 text-brand-600 flex items-center justify-center font-bold shadow-2xs shrink-0">
                                        <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                    </div>
                                    <div class="text-left min-w-0">
                                        <span class="text-xs font-black text-slate-800 group-hover:text-brand-700 block truncate">লাইভ কাস্টমার স্টোরফ্রন্ট</span>
                                        <span class="text-[10px] text-slate-400 block truncate">গ্রাহকরা যেমন দেখেন</span>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-transform shrink-0"></i>
                            </a>
                        <?php else: ?>
                            <!-- Locked Client Store Admin (Pending) -->
                            <div class="w-full p-3 rounded-xl sm:rounded-2xl bg-slate-50/70 border border-slate-200/80 flex items-center justify-between opacity-80 cursor-not-allowed">
                                <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg sm:rounded-xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center font-bold shadow-2xs shrink-0">
                                        <i data-lucide="lock" class="w-4 h-4"></i>
                                    </div>
                                    <div class="text-left min-w-0">
                                        <span class="text-xs font-bold text-slate-600 block truncate">স্টোর অ্যাডমিন ড্যাশবোর্ড</span>
                                        <span class="text-[10px] text-amber-600 font-semibold block truncate">অনুমোদনের পর সক্রিয় হবে</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 bg-slate-200/60 px-2 py-0.5 rounded shrink-0">লকড</span>
                            </div>

                            <!-- Locked Storefront (Pending) -->
                            <div class="w-full p-3 rounded-xl sm:rounded-2xl bg-slate-50/70 border border-slate-200/80 flex items-center justify-between opacity-80 cursor-not-allowed">
                                <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg sm:rounded-xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center font-bold shadow-2xs shrink-0">
                                        <i data-lucide="lock" class="w-4 h-4"></i>
                                    </div>
                                    <div class="text-left min-w-0">
                                        <span class="text-xs font-bold text-slate-600 block truncate">লাইভ কাস্টমার স্টোরফ্রন্ট</span>
                                        <span class="text-[10px] text-amber-600 font-semibold block truncate">লিংক বরাদ্দের পর সক্রিয় হবে</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 bg-slate-200/60 px-2 py-0.5 rounded shrink-0">লকড</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-[11px] text-slate-500 mt-2">
                    💡 সহায়তা: <a href="mailto:support@ponnostack.shop" class="text-brand-600 font-bold hover:underline">support@ponnostack.shop</a>
                </div>
            </div>

            <!-- Card 3: Store Owner Credentials & Info -->
            <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-3 sm:space-y-4">
                <div class="flex items-center space-x-1.5 pb-2.5 border-b border-slate-100">
                    <i data-lucide="user" class="w-4 h-4 text-brand-600"></i>
                    <h3 class="text-xs font-black uppercase text-slate-900">মালিকের তথ্য ও অ্যাকাউন্ট</h3>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="py-1 border-b border-slate-50">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">মালিকের নাম</span>
                        <strong class="text-slate-800 text-xs block mt-0.5"><?= $ownerName ?></strong>
                    </div>

                    <div class="py-1 border-b border-slate-50">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">লগইন ইমেইল</span>
                        <strong class="font-mono text-slate-800 text-xs block mt-0.5 break-all"><?= $ownerEmail ?></strong>
                    </div>

                    <div class="py-1 border-b border-slate-50">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">মোবাইল নম্বর</span>
                        <span class="font-mono text-slate-800 block mt-0.5"><?= $ownerPhone ?></span>
                    </div>

                    <div class="py-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">স্টোর ঠিকানা</span>
                        <span class="text-slate-600 block mt-0.5"><?= $address ?></span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        🔒 যেকোনো ব্রাউজার থেকে আপনার রেজিস্টার্ড ইমেইল ও পাসওয়ার্ড দিয়ে কাস্টমার পোর্টালে ঢুকতে পারবেন।
                    </p>
                </div>
            </div>

        </div>

    </main>

    <!-- FLOATING TOAST NOTIFICATION CONTAINER -->
    <div id="portalToastContainer" class="fixed top-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- ======================================================= -->
    <!-- MODAL 1: PAY MONTHLY BILL (PURE LIGHT FROSTED GLASS) -->
    <!-- ======================================================= -->
    <div id="payMonthlyModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-white/85 backdrop-blur-md hidden transition-all duration-300">
        <div class="bg-white border border-slate-200/90 rounded-3xl max-w-md w-full shadow-2xl p-5 sm:p-7 space-y-4 relative" onclick="event.stopPropagation()">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">মাসিক বিল পরিশোধ</h3>
                        <p class="text-[11px] text-slate-400">বিকাশ সেন্ড মানির মাধ্যমে ফি জমা দিন</p>
                    </div>
                </div>
                <button type="button" onclick="closePayMonthlyModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- bKash Send Money Instruction Strip -->
            <div class="p-3.5 bg-gradient-to-r from-pink-50 to-rose-50 border border-pink-200 rounded-2xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-pink-900 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-pink-600 animate-pulse"></span>
                        <span>বিকাশ সেন্ড মানি (Personal)</span>
                    </span>
                    <span class="text-xs font-mono font-black text-pink-700">৳ <?= number_format($monthlyFee) ?></span>
                </div>
                <div class="flex items-center justify-between bg-white px-3 py-1.5 rounded-xl border border-pink-200">
                    <span class="font-mono font-black text-sm text-slate-900" id="portalUniversalBkash"><?= htmlspecialchars($universalBkash) ?></span>
                    <button type="button" onclick="copyPortalBkash()" class="text-xs font-bold text-pink-600 hover:text-pink-800 transition flex items-center space-x-1 cursor-pointer">
                        <i data-lucide="copy" class="w-3 h-3"></i>
                        <span>কপি</span>
                    </button>
                </div>
                <p class="text-[10px] text-slate-500">উক্ত নম্বরে বিকাশ অ্যাপ থেকে সেন্ড মানি করে নিচের তথ্য পূরণ করুন।</p>
            </div>

            <form id="payMonthlyForm" onsubmit="submitMonthlyPayment(event)" class="space-y-3">
                <input type="hidden" name="amount" value="<?= $monthlyFee ?>">

                <!-- Billing Month -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">কোন মাসের জন্য বিল দিচ্ছেন?</label>
                    <input type="text" id="pmMonth" name="billing_month" required value="<?= date('F Y') ?>"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>

                <!-- Sender bKash Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">আপনার বিকাশ নম্বর (যে নম্বর থেকে টাকা পাঠিয়েছেন) <span class="text-rose-500">*</span></label>
                    <input type="tel" id="pmSenderNumber" name="sender_number" required placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($tenant['owner_phone'] ?? '') ?>"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>

                <!-- TrxID -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">বিকাশ ট্রানজেকশন আইডি (TrxID) <span class="text-rose-500">*</span></label>
                    <input type="text" id="pmTrxId" name="trx_id" required placeholder="8N7A6D5E"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono uppercase text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>

                <!-- Note (Optional) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">মন্তব্য (ঐচ্ছিক)</label>
                    <input type="text" id="pmNote" name="note" placeholder="যেমন: নভেম্বর মাসের নিয়মিত ফি"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="closePayMonthlyModal()" class="py-2 px-4 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                        বাতিল
                    </button>
                    <button type="submit" id="btnPayMonthlySubmit" class="py-2 px-5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer flex items-center space-x-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>পেমেন্ট তথ্য সাবমিট করুন</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL 2: PRINTABLE INVOICE MODAL -->
    <!-- ======================================================= -->
    <div id="portalInvoiceModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-white/85 backdrop-blur-md hidden transition-all duration-300">
        <div class="bg-white border border-slate-200/90 rounded-3xl max-w-2xl w-full max-h-[92vh] overflow-y-auto shadow-2xl p-5 sm:p-8 space-y-6 relative" onclick="event.stopPropagation()">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 print:hidden">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-[#00684A] flex items-center justify-center font-bold">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">অফিসিয়াল ইনভয়েস</h3>
                        <p class="text-[11px] text-slate-400 font-mono" id="pInvSubtitle">INV-0000</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" onclick="window.print()"
                        class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold transition shadow-brand-glow cursor-pointer">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>প্রিন্ট / PDF</span>
                    </button>
                    <button type="button" onclick="closePortalInvoiceModal()"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- INVOICE DOCUMENT BODY -->
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div>
                        <img src="<?= htmlspecialchars($homeUrl) ?>assets/images/logo.png" alt="Ponnostack" class="h-8 w-auto object-contain mb-1.5" onerror="this.outerHTML='<span class=\'font-black text-xl text-[#00684A]\'>Ponnostack</span>'">
                        <p class="text-xs text-slate-500 font-medium">সম্পূর্ণ স্বয়ংক্রিয় মাল্টি-টেন্যান্ট ই-কমার্স প্ল্যাটফর্ম</p>
                        <span class="text-[11px] text-slate-400 block font-mono">ওয়েবসাইট: ponnostack.shop | ঢাকা, বাংলাদেশ</span>
                    </div>

                    <div class="sm:text-right">
                        <span class="text-xs font-black uppercase text-slate-400 tracking-wider block">INVOICE</span>
                        <strong class="text-base sm:text-lg font-black text-slate-900 font-mono block" id="pInvDocNo">INV-0000</strong>
                        <span class="text-xs text-slate-500 font-mono block mt-1">ইস্যুর তারিখ: <span id="pInvDocDate">-</span></span>
                        <div class="mt-2" id="pInvStatusBadge"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">বিল গ্রহীতা (Bill To):</span>
                        <strong class="text-slate-900 text-sm block font-black"><?= $storeName ?></strong>
                        <span class="text-slate-600 block"><?= $ownerName ?></span>
                        <span class="text-slate-500 font-mono block"><?= $ownerPhone ?></span>
                        <span class="text-slate-500 font-mono block"><?= $ownerEmail ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">পেমেন্ট বিবরণ (Payment Details):</span>
                        <div class="space-y-1">
                            <div class="flex justify-between"><span class="text-slate-500">মেথড:</span> <strong class="font-bold text-slate-800" id="pInvMethod">bKash</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">প্রেরক নম্বর:</span> <span class="font-mono text-slate-800 font-bold" id="pInvSender">-</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">TrxID:</span> <span class="font-mono text-emerald-800 font-bold" id="pInvTrxId">-</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">বিলিং মাস:</span> <strong class="font-bold text-slate-800" id="pInvMonth">-</strong></div>
                        </div>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-black tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">আইটেম বিবরণ</th>
                                <th class="py-2.5 px-4">প্যাকেজ</th>
                                <th class="py-2.5 px-4">ধরন</th>
                                <th class="py-2.5 px-4 text-right">পরিমাণ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-3 px-4">
                                    <strong class="text-slate-900 font-bold block">পণ্যস্ট্যাক ই-কমার্স স্টোরফ্রন্ট সাবস্ক্রিপশন ফি</strong>
                                    <span class="text-[11px] text-slate-500" id="pInvItemDesc">-</span>
                                </td>
                                <td class="py-3 px-4 text-slate-700 font-medium"><?= $packageName ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" id="pInvTypeBadge">-</span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-black text-slate-900 text-sm" id="pInvAmount">৳ 0</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200 text-xs">
                            <tr>
                                <td colspan="3" class="py-2.5 px-4 font-bold text-slate-700 text-right">মোট প্রদেয় বিল:</td>
                                <td class="py-2.5 px-4 text-right font-mono font-black text-base text-[#00684A]" id="pInvTotalAmount">৳ 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-400 gap-2">
                    <span>এটি একটি সিস্টেম জেনারেটেড ডিজিটাল ইনভয়েস, কোনো ম্যানুয়াল স্বাক্ষরের প্রয়োজন নেই।</span>
                    <span class="font-mono text-emerald-700 font-bold">✓ OFFICIAL PONNOSTACK RECEIPT</span>
                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200/80 py-4 text-center text-[11px] text-slate-400">
        &copy; <?= date('Y') ?> Ponnostack Platform. সর্বস্বত্ব সংরক্ষিত।
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            // Click outside to close modals
            ['payMonthlyModal', 'portalInvoiceModal'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('click', (e) => {
                        if (e.target === el) el.classList.add('hidden');
                    });
                }
            });
        });

        // SLEEK FLOATING TOAST SYSTEM
        function portalToast(message, type = 'success') {
            const container = document.getElementById('portalToastContainer');
            if (!container) return;
            container.innerHTML = '';

            const toast = document.createElement('div');
            const isErr = (type === 'error');
            const bg = isErr ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'bg-[#00684A] text-white shadow-lg shadow-brand-glow';
            const icon = isErr ? 'alert-circle' : 'check-circle';

            toast.className = `flex items-center space-x-2 px-4 py-2.5 rounded-2xl text-xs font-bold pointer-events-auto transform -translate-y-3 opacity-0 transition-all duration-250 ${bg}`;
            toast.innerHTML = `<i data-lucide="${icon}" class="w-4 h-4 shrink-0"></i><span>${message}</span>`;

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
            }, 3500);
        }

        function copyPortalBkash() {
            const num = document.getElementById('portalUniversalBkash').innerText.trim();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(num).then(() => {
                    portalToast('বিকাশ নম্বর কপি করা হয়েছে: ' + num, 'success');
                });
            }
        }

        function openPayMonthlyModal() {
            document.getElementById('pmTrxId').value = '';
            document.getElementById('payMonthlyModal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        }

        function closePayMonthlyModal() {
            document.getElementById('payMonthlyModal').classList.add('hidden');
        }

        async function submitMonthlyPayment(e) {
            e.preventDefault();
            const form = document.getElementById('payMonthlyForm');
            const formData = new FormData(form);
            const btn = document.getElementById('btnPayMonthlySubmit');

            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-1">⟳</span> <span>সাবমিট হচ্ছে...</span>';

            try {
                const payload = Object.fromEntries(formData.entries());
                const res = await fetch('api/pay-bill.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    portalToast(data.message, 'success');
                    closePayMonthlyModal();
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    portalToast(data.message || 'পেমেন্ট সাবমিট ব্যর্থ হয়েছে।', 'error');
                }
            } catch (err) {
                portalToast('সার্ভার রেসপন্স ত্রুটি: অনুগ্রহ করে পুনরায় চেষ্টা করুন।', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>পেমেন্ট তথ্য সাবমিট করুন</span>';
                if (window.lucide) lucide.createIcons();
            }
        }

        function showPortalInvoice(tp) {
            document.getElementById('pInvSubtitle').innerText = tp.invoice_no;
            document.getElementById('pInvDocNo').innerText = tp.invoice_no;
            document.getElementById('pInvDocDate').innerText = tp.formatted_date_short;

            document.getElementById('pInvMethod').innerText = tp.payment_method;
            document.getElementById('pInvSender').innerText = tp.sender_number || '-';
            document.getElementById('pInvTrxId').innerText = tp.trx_id;
            document.getElementById('pInvMonth').innerText = tp.billing_month || 'সাধারণ সাবস্ক্রিপশন';
            document.getElementById('pInvItemDesc').innerText = 'বিলিং মাস: ' + (tp.billing_month || '-');
            document.getElementById('pInvAmount').innerText = '৳ ' + tp.amount;
            document.getElementById('pInvTotalAmount').innerText = '৳ ' + tp.amount;

            const typeBadge = document.getElementById('pInvTypeBadge');
            typeBadge.innerText = tp.type_label;
            typeBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold ' + tp.type_badge_cls;

            const statusBadge = document.getElementById('pInvStatusBadge');
            if (tp.status === 'approved') {
                statusBadge.innerHTML = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-100 text-[#00684A] border border-emerald-300 font-black text-xs uppercase tracking-wider">✓ PAID / অনুমোদিত</span>`;
            } else {
                statusBadge.innerHTML = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 font-bold text-xs uppercase tracking-wider">⏳ PENDING APPROVAL</span>`;
            }

            document.getElementById('portalInvoiceModal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        }

        function closePortalInvoiceModal() {
            document.getElementById('portalInvoiceModal').classList.add('hidden');
        }

        function handleLogout(e) {
            e.preventDefault();
            try {
                localStorage.removeItem('ponnostack_auth_token');
                localStorage.removeItem('ponnostack_tenant');
                localStorage.removeItem('ponnostack_tenant_uuid');
                localStorage.removeItem('ponnostack_tenant_email');
                localStorage.removeItem('ponnostack_tenant_name');
            } catch (err) {}
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
    </script>
</body>
</html>