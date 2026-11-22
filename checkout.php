<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - High-Conversion Dedicated Storefront Checkout & Onboarding Wizard
 * 
 * 3-Step Wizard:
 * - Step 1: Store Details, Domain Selection & Logo Upload (with real-time slug check)
 * - Step 2: Owner Contact, Login Credentials & Optional Social Links
 * - Step 3: Order Review, Universal bKash Payment & Instant Confirmation
 */

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLiveDomain = (strpos($host, 'ponnostack.shop') !== false);

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$cleanDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/\\');
$baseUrl = $isLiveDomain ? 'https://ponnostack.shop/' : ($protocol . $host . $cleanDir . '/');
$homeUrl = $baseUrl;

// 1. Load Core & Packages from Database
$packages = [];
$universalBkash = '01642956206';

try {
    $root = __DIR__;
    if (file_exists($root . '/backend/app/Core/Autoloader.php')) {
        require_once $root . '/backend/app/Core/Autoloader.php';
        \App\Core\Autoloader::register();
        $envPath = file_exists($root . '/.env') ? $root . '/.env' : $root . '/backend/.env';
        if (file_exists($envPath)) {
            \App\Core\Env::load($envPath);
        }
        // Load Universal bKash number dynamically from .env
        $universalBkash = (string)\App\Core\Env::get('UNIVERSAL_BKASH', '01642956206');
        $pkgModel = new \App\Models\Package();
        $packages = $pkgModel->getAll();
    }
} catch (\Throwable $e) {
    error_log('Checkout package fetch error: ' . $e->getMessage());
}

// Fallback if packages table is not loaded
if (empty($packages)) {
    $packages = [
        [
            'id' => 1,
            'slug' => 'starter',
            'name_bn' => 'স্ট্যান্ডার্ড প্যাকেজ',
            'name_en' => 'Standard Plan',
            'badge_bn' => 'সবচেয়ে জনপ্রিয়',
            'price' => 199,
            'product_limit' => 50,
            'formatted_price_bn' => '১৯৯',
            'formatted_price_en' => '199',
            'formatted_product_limit_bn' => '৫০টি প্রোডাক্ট আপলোড',
            'is_popular' => 1
        ],
        [
            'id' => 2,
            'slug' => 'professional',
            'name_bn' => 'প্রিমিয়াম প্যাকেজ',
            'name_en' => 'Premium Plan',
            'badge_bn' => 'গ্রোথ ফোকাস্ড',
            'price' => 399,
            'product_limit' => 100,
            'formatted_price_bn' => '৩৯৯',
            'formatted_price_en' => '399',
            'formatted_product_limit_bn' => '১০০টি প্রোডাক্ট আপলোড',
            'is_popular' => 0
        ],
        [
            'id' => 3,
            'slug' => 'enterprise',
            'name_bn' => 'এন্টারপ্রাইজ প্যাকেজ',
            'name_en' => 'Enterprise Plan',
            'badge_bn' => 'আনলিমিটেড স্কেল',
            'price' => 799,
            'product_limit' => 0,
            'formatted_price_bn' => '৭৯৯',
            'formatted_price_en' => '799',
            'formatted_product_limit_bn' => 'আনলিমিটেড প্রোডাক্ট আপলোড',
            'is_popular' => 0
        ]
    ];
}

// 2. Resolve Selected Package from Query String
$requestedParam = trim((string)($_GET['package'] ?? ($_GET['plan'] ?? ($_GET['package_slug'] ?? ''))));
$requestedId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;

$selectedPackage = null;
if (!empty($requestedParam)) {
    foreach ($packages as $pkg) {
        if (strcasecmp($pkg['slug'] ?? '', $requestedParam) === 0) {
            $selectedPackage = $pkg;
            break;
        }
    }
}
if (!$selectedPackage && $requestedId > 0) {
    foreach ($packages as $pkg) {
        if ((int)($pkg['id'] ?? 0) === $requestedId) {
            $selectedPackage = $pkg;
            break;
        }
    }
}
if (!$selectedPackage) {
    $selectedPackage = $packages[0];
}

$currentPkgSlug  = (string)($selectedPackage['slug'] ?? 'starter');
$currentPkgName  = (string)($selectedPackage['name_bn'] ?? 'স্ট্যান্ডার্ড প্যাকেজ');
$currentPkgPrice = (int)($selectedPackage['price'] ?? 200);
$currentPkgLimit = (int)($selectedPackage['product_limit'] ?? 50);
$currentLimitText = ($currentPkgLimit === 0) ? 'আনলিমিটেড প্রোডাক্ট' : "{$currentPkgLimit}টি প্রোডাক্ট";
?>
<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>স্টোরফ্রন্ট সাবস্ক্রিপশন ও রেজিস্ট্রেশন | Ponnostack</title>
    <meta name="description" content="Ponnostack-এ মাত্র ৩টি সহজ ধাপে আপনার ব্র্যান্ডের ই-কমার্স স্টোর চালু করুন। বিকাশ পেমেন্ট ও তাত্ক্ষণিক অনবোর্ডিং।">
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#00684A">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($baseUrl) ?>assets/images/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($baseUrl) ?>assets/images/favicon.png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($baseUrl) ?>assets/images/apple-touch-icon.png">

    <!-- Fonts: Hind Siliguri -->
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
        .step-transition {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between text-slate-800 antialiased selection:bg-brand-100 selection:text-brand-900">

    <!-- Floating Toast Notification System (NO Modal Alerts) -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-[99999] pointer-events-none transition-all duration-300 flex flex-col items-center space-y-2 max-w-sm sm:max-w-md w-full px-4"></div>

    <!-- ======================================================== -->
    <!-- TOP DISTRACTION-FREE HEADER                              -->
    <!-- ======================================================== -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
            <!-- Back Link -->
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="inline-flex items-center space-x-1 text-xs font-bold text-slate-600 hover:text-brand-600 transition group">
                <i data-lucide="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-1"></i>
                <span class="hidden sm:inline">হোমপেজে ফিরে যান</span>
                <span class="sm:hidden">হোম</span>
            </a>

            <!-- Brand Logo -->
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="flex items-center">
                <img src="<?= htmlspecialchars($baseUrl) ?>assets/images/logo.png" alt="Ponnostack Logo" class="h-7 sm:h-8 w-auto object-contain" width="120" height="32">
            </a>

            <!-- SSL Trust Badge -->
            <div class="inline-flex items-center space-x-1 text-[10px] sm:text-[11px] font-bold text-emerald-800 bg-emerald-50/90 border border-emerald-200 px-2.5 py-1 rounded-full shadow-2xs">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-brand-600"></i>
                <span class="hidden sm:inline">২৫৬-বিট SSL সুরক্ষিত</span>
                <span class="sm:hidden">নিরাপদ</span>
            </div>
        </div>
    </header>

    <!-- ======================================================== -->
    <!-- MAIN CONTAINER (WIDE & RESPONSIVE)                       -->
    <!-- ======================================================== -->
    <main class="max-w-5xl mx-auto px-3.5 sm:px-6 py-5 sm:py-8 w-full flex-1 space-y-4 sm:space-y-6">

        <!-- 3-STEP WIZARD PROGRESS BAR -->
        <div id="stepProgressCard" class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between relative max-w-2xl mx-auto">
                <!-- Background Line -->
                <div class="absolute left-6 right-6 top-4 sm:top-5 -translate-y-1/2 h-1 bg-slate-100 z-0"></div>
                <!-- Dynamic Progress Fill -->
                <div id="stepProgressLine" class="absolute left-6 top-4 sm:top-5 -translate-y-1/2 h-1 bg-brand-600 z-0 step-transition w-0"></div>

                <!-- Step 1 Indicator -->
                <div class="relative z-10 flex flex-col items-center">
                    <button type="button" onclick="goToStep(1)" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-brand-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow-md shadow-brand-600/30 step-transition focus:outline-none" id="badgeStep1">
                        <span>১</span>
                    </button>
                    <span id="labelStep1" class="text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center">
                        <span class="sm:hidden">স্টোর</span>
                        <span class="hidden sm:inline">স্টোর ও ডোমেন</span>
                    </span>
                </div>

                <!-- Step 2 Indicator -->
                <div class="relative z-10 flex flex-col items-center">
                    <button type="button" onclick="goToStep(2)" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none" id="badgeStep2">
                        <span>২</span>
                    </button>
                    <span id="labelStep2" class="text-[11px] sm:text-xs font-bold text-slate-400 mt-1.5 text-center">
                        <span class="sm:hidden">মালিক</span>
                        <span class="hidden sm:inline">মালিক ও সোশ্যাল</span>
                    </span>
                </div>

                <!-- Step 3 Indicator -->
                <div class="relative z-10 flex flex-col items-center">
                    <button type="button" onclick="goToStep(3)" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none" id="badgeStep3">
                        <span>৩</span>
                    </button>
                    <span id="labelStep3" class="text-[11px] sm:text-xs font-bold text-slate-400 mt-1.5 text-center">
                        <span class="sm:hidden">পেমেন্ট</span>
                        <span class="hidden sm:inline">পেমেন্ট ও রিভিউ</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- PACKAGE SELECTOR STRIP (CLEAN & COMPACT) -->
        <div id="packageSelectStrip" class="p-3.5 sm:p-4 bg-gradient-to-r from-emerald-50/90 via-teal-50/40 to-white border border-emerald-200/90 rounded-2xl sm:rounded-3xl flex flex-wrap items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-gradient-to-br from-brand-600 to-brand-700 text-white flex items-center justify-center shadow-brand-glow shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4 text-brand-500"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-black text-brand-700 uppercase tracking-wider bg-brand-100/90 px-2 py-0.5 rounded" id="pkgBadgeText">
                            <?= htmlspecialchars($selectedPackage['badge_bn'] ?? 'প্যাকেজ') ?>
                        </span>
                        <span class="text-[11px] text-slate-500 hidden sm:inline" id="pkgLimitText">
                            <?= htmlspecialchars($currentLimitText) ?>
                        </span>
                    </div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 mt-0.5" id="coDisplayPackageName">
                        <?= htmlspecialchars($currentPkgName) ?>
                    </h3>
                </div>
            </div>

            <!-- Price & Selector -->
            <div class="flex items-center space-x-3 ml-auto">
                <div class="text-right">
                    <span class="text-lg sm:text-2xl font-black text-brand-700 font-mono tracking-tight" id="coDisplayAmount">
                        ৳ <?= number_format($currentPkgPrice) ?>
                    </span>
                    <span class="text-[10px] text-slate-400 font-bold block sm:inline">/মাস</span>
                </div>

                <!-- Package Dropdown -->
                <select id="coPackageSelector" onchange="onPackageSelectChange(this)"
                    class="px-2.5 py-1.5 sm:px-3 sm:py-2 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs cursor-pointer hover:border-brand-500 transition">
                    <?php foreach ($packages as $pkg): ?>
                        <?php 
                            $isSel = ($pkg['slug'] === $currentPkgSlug);
                            $pkgLimit = (int)($pkg['product_limit'] ?? 0);
                            $limitLabel = ($pkgLimit === 0) ? 'আনলিমিটেড' : "{$pkgLimit}টি";
                        ?>
                        <option value="<?= htmlspecialchars($pkg['slug']) ?>" 
                                data-id="<?= (int)$pkg['id'] ?>"
                                data-name="<?= htmlspecialchars($pkg['name_bn']) ?>" 
                                data-price="<?= (int)$pkg['price'] ?>" 
                                data-limit="<?= $pkgLimit ?>"
                                data-limit-text="<?= ($pkgLimit === 0) ? 'আনলিমিটেড প্রোডাক্ট' : "{$pkgLimit}টি প্রোডাক্ট" ?>"
                                data-badge="<?= htmlspecialchars($pkg['badge_bn'] ?? 'প্যাকেজ') ?>"
                                <?= $isSel ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pkg['name_bn']) ?> (৳ <?= number_format((int)$pkg['price']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- MAIN CHECKOUT FORM CARD -->
        <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-7 lg:p-8 shadow-sm relative">
            <form id="multiStepCheckoutForm" onsubmit="handleMultiStepSubmit(event)" novalidate>
                <!-- Hidden Package Values -->
                <input type="hidden" id="coPackageSlug" name="package_slug" value="<?= htmlspecialchars($currentPkgSlug) ?>">
                <input type="hidden" id="coPackageName" name="package_name" value="<?= htmlspecialchars($currentPkgName) ?>">
                <input type="hidden" id="coAmount" name="amount" value="<?= $currentPkgPrice ?>">
                <input type="hidden" id="coProductLimit" name="product_limit" value="<?= $currentPkgLimit ?>">

                <!-- ======================================================== -->
                <!-- STEP 1: STORE DETAILS, DOMAIN & LOGO                     -->
                <!-- ======================================================== -->
                <div id="stepSection1" class="space-y-4 sm:space-y-5 step-transition">
                    <!-- Step Header -->
                    <div class="border-b border-slate-100 pb-3 sm:pb-4 flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold">
                            <i data-lucide="store" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black text-slate-900 leading-tight">ধাপ ১: স্টোর ও ডোমেনের তথ্য</h2>
                            <p class="text-[11px] text-slate-500 hidden sm:block">আপনার ব্র্যান্ডের নাম, ওয়েবসাইট স্লাগ এবং লোগো নির্ধারণ করুন</p>
                        </div>
                    </div>

                    <!-- Store Name & Slug -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="coStoreName" class="block text-xs font-bold text-slate-700">
                                    স্টোরের নাম <span class="text-rose-500">*</span>
                                </label>
                                <span id="nameStatusBadge" class="text-[10px] font-bold"></span>
                            </div>
                            <input type="text" id="coStoreName" name="store_name" required 
                                   placeholder="e.g. Dream Fashion" 
                                   oninput="handleStoreNameInput(this.value)"
                                   class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="coStoreSlug" class="block text-xs font-bold text-slate-700">
                                    স্টোর স্লাগ / হ্যান্ডেল <span class="text-rose-500">*</span>
                                </label>
                                <span id="slugCheckingIndicator" class="hidden text-[10px] text-slate-400 font-normal">
                                    <i data-lucide="loader-2" class="w-3 h-3 inline animate-spin text-brand-600"></i> যাচাই হচ্ছে...
                                </span>
                            </div>
                            <div class="relative">
                                <input type="text" id="coStoreSlug" name="store_slug" required 
                                       placeholder="dream-fashion" 
                                       oninput="handleSlugInput(this.value)"
                                       class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                            </div>
                            <div class="mt-1 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400 text-[10px]">স্টোরের ইউনিক আইডি (অ্যাডমিন অনুমোদনের পর লাইভ লিংক বরাদ্দ হবে)</span>
                                <span id="slugStatusBadge" class="text-[10px] font-bold shrink-0 ml-1"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Domain Choice: Free Subdomain vs Custom Domain -->
                    <div class="p-3.5 sm:p-4 bg-slate-50/80 border border-slate-200/90 rounded-2xl space-y-2.5">
                        <label class="block text-xs font-black text-slate-800 flex items-center space-x-1.5">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-brand-600"></i>
                            <span>ডোমেন অপশন নির্বাচন করুন <span class="text-rose-500">*</span></span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <!-- Option 1: Free Subdomain -->
                            <label class="relative flex items-center p-3 bg-white border-2 border-brand-600 rounded-xl cursor-pointer hover:border-brand-600 transition shadow-2xs" id="domainOptionSubdomainLabel">
                                <input type="radio" name="domain_choice" value="subdomain" checked 
                                       onchange="toggleDomainChoice('subdomain')" 
                                       class="text-brand-600 focus:ring-brand-500">
                                <div class="ml-2.5">
                                    <span class="block text-xs font-bold text-slate-900">হোস্টেড স্টোরফ্রন্ট (Cloud Hosted)</span>
                                    <span class="block text-[11px] text-slate-400">অ্যাডমিন কর্তৃক লাইভ লিংক বরাদ্দ হবে</span>
                                </div>
                            </label>

                            <!-- Option 2: Custom Domain -->
                            <label class="relative flex items-center p-3 bg-white border border-slate-300 rounded-xl cursor-pointer hover:border-brand-500 transition shadow-2xs" id="domainOptionCustomLabel">
                                <input type="radio" name="domain_choice" value="custom" 
                                       onchange="toggleDomainChoice('custom')" 
                                       class="text-brand-600 focus:ring-brand-500">
                                <div class="ml-2.5">
                                    <span class="block text-xs font-bold text-slate-900">নিজস্ব কাস্টম ডোমেন</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">যেমন: yourbrand.com</span>
                                </div>
                            </label>
                        </div>

                        <!-- Custom Domain Input Box (Toggled) -->
                        <div id="customDomainInputBox" class="hidden p-3 bg-brand-50/70 border border-brand-200 rounded-xl space-y-1 transition-all">
                            <label for="coCustomDomain" class="block text-[11px] font-bold text-brand-900">
                                আপনার ডোমেনের নাম দিন <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-mono">https://</span>
                                <input type="text" id="coCustomDomain" name="custom_domain" 
                                       placeholder="mybrand.com or www.mybrand.com"
                                       class="w-full pl-16 pr-3 py-2 bg-white border border-brand-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-brand-600 shadow-2xs">
                            </div>
                            <p class="text-[10px] text-slate-500">
                                💡 অর্ডার অনুমোদনের পর DNS কানেক্ট গাইড প্রদান করা হবে।
                            </p>
                        </div>
                    </div>

                    <!-- Logo Upload Section (Compact & Required) -->
                    <div id="coLogoSection" class="p-3.5 sm:p-4 bg-slate-50/80 border border-slate-200/90 rounded-2xl transition">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-black text-slate-800 flex items-center space-x-1.5">
                                <i data-lucide="image" class="w-3.5 h-3.5 text-brand-600"></i>
                                <span>স্টোর লোগো <span class="text-rose-500">*</span></span>
                            </label>
                            <button type="button" id="btnRemoveLogo" onclick="removeSelectedLogo()" class="text-rose-600 hover:text-rose-700 text-[10px] font-bold hidden cursor-pointer">
                                লোগো সরান
                            </button>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Preview Box -->
                            <div id="logoPreviewContainer" class="w-12 h-12 rounded-xl bg-white border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                <img id="logoPreviewImg" src="" alt="Logo" class="w-full h-full object-contain hidden p-0.5">
                                <i data-lucide="image-plus" id="logoPlaceholderIcon" class="w-5 h-5 text-slate-400"></i>
                            </div>

                            <!-- File Chooser -->
                            <div class="flex-1">
                                <input type="file" id="coLogoFile" name="logo" accept="image/png, image/jpeg, image/webp, image/svg+xml" 
                                       onchange="handleLogoSelect(event)"
                                       class="block w-full text-xs text-slate-600 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                                <span class="text-[10px] text-slate-400 mt-0.5 block" id="logoStatusText">PNG, JPG, WebP বা SVG (সর্বোচ্চ ৫MB)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Store Address -->
                    <div>
                        <label for="coAddress" class="block text-xs font-bold text-slate-700 mb-1">
                            ঠিকানা (ঐচ্ছিক)
                        </label>
                        <input type="text" id="coAddress" name="address" 
                               placeholder="e.g. Road 4, Dhanmondi, Dhaka" 
                               class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                    </div>

                    <!-- Next Step Button -->
                    <div class="pt-3 flex justify-end">
                        <button type="button" onclick="goToStep(2)" class="w-full sm:w-auto px-7 py-3 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-black rounded-xl transition shadow-brand-glow flex items-center justify-center space-x-2 cursor-pointer">
                            <span>পরবর্তী ধাপ (মালিকের তথ্য)</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- STEP 2: OWNER DETAILS, CREDENTIALS & SOCIAL LINKS        -->
                <!-- ======================================================== -->
                <div id="stepSection2" class="space-y-4 sm:space-y-5 step-transition hidden">
                    <!-- Step Header -->
                    <div class="border-b border-slate-100 pb-3 sm:pb-4 flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold">
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black text-slate-900 leading-tight">ধাপ ২: মালিক ও অ্যাডমিন তথ্য</h2>
                            <p class="text-[11px] text-slate-500 hidden sm:block">স্টোর কন্ট্রোল প্যানেলের লগইন তথ্য দিন</p>
                        </div>
                    </div>

                    <!-- Owner Name & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        <div>
                            <label for="coOwnerName" class="block text-xs font-bold text-slate-700 mb-1">
                                আপনার নাম <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="coOwnerName" name="owner_name" required 
                                   placeholder="e.g. Tanvir Ahmed" 
                                   class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="coOwnerEmail" class="block text-xs font-bold text-slate-700">
                                    লগইন ইমেইল <span class="text-rose-500">*</span>
                                </label>
                                <span id="emailStatusBadge" class="text-[10px] font-bold"></span>
                            </div>
                            <input type="email" id="coOwnerEmail" name="owner_email" required 
                                   placeholder="tanvir@gmail.com" 
                                   oninput="handleEmailInput(this.value)"
                                   class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                        </div>
                    </div>

                    <!-- Phone & Password -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        <div>
                            <label for="coOwnerPhone" class="block text-xs font-bold text-slate-700 mb-1">
                                মোবাইল নম্বর <span class="text-rose-500">*</span>
                            </label>
                            <input type="tel" id="coOwnerPhone" name="owner_phone" required 
                                   placeholder="017XXXXXXXX" 
                                   class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs transition">
                        </div>

                        <div>
                            <label for="coOwnerPassword" class="block text-xs font-bold text-slate-700 mb-1">
                                লগইন পাসওয়ার্ড <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" id="coOwnerPassword" name="owner_password" required 
                                       placeholder="At least 4 characters" 
                                       class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs transition pr-10">
                                <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none" title="পাসওয়ার্ড দেখুন">
                                    <i data-lucide="eye" id="pwdEyeIcon" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Social Media Links (Collapsible Accordion, Default Closed) -->
                    <details class="group bg-slate-50/80 border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 transition">
                        <summary class="flex items-center justify-between cursor-pointer list-none text-xs font-bold text-slate-700 select-none">
                            <span class="flex items-center space-x-1.5">
                                <i data-lucide="share-2" class="w-3.5 h-3.5 text-brand-600"></i>
                                <span>সোশ্যাল মিডিয়া লিংকসমূহ (ঐচ্ছিক)</span>
                            </span>
                            <span class="text-[11px] text-slate-400 flex items-center space-x-1">
                                <i data-lucide="chevron-down" class="w-4 h-4 group-open:rotate-180 transition-transform"></i>
                            </span>
                        </summary>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 mt-2 border-t border-slate-200/60">
                            <!-- Facebook -->
                            <div>
                                <label for="coFacebook" class="block text-[11px] font-bold text-slate-600 mb-1">ফেসবুক পেজ (URL)</label>
                                <input type="url" id="coFacebook" name="facebook" placeholder="https://facebook.com/yourpage" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs">
                            </div>

                            <!-- WhatsApp -->
                            <div>
                                <label for="coWhatsapp" class="block text-[11px] font-bold text-slate-600 mb-1">হোয়াটসঅ্যাপ নম্বর</label>
                                <input type="text" id="coWhatsapp" name="whatsapp" placeholder="01XXXXXXXXX" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs">
                            </div>

                            <!-- Instagram -->
                            <div>
                                <label for="coInstagram" class="block text-[11px] font-bold text-slate-600 mb-1">ইনস্টাগ্রাম (URL)</label>
                                <input type="url" id="coInstagram" name="instagram" placeholder="https://instagram.com/yourhandle" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs">
                            </div>

                            <!-- YouTube -->
                            <div>
                                <label for="coYoutube" class="block text-[11px] font-bold text-slate-600 mb-1">ইউটিউব চ্যানেল (URL)</label>
                                <input type="url" id="coYoutube" name="youtube" placeholder="https://youtube.com/@yourchannel" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs">
                            </div>

                            <!-- TikTok -->
                            <div class="sm:col-span-2">
                                <label for="coTiktok" class="block text-[11px] font-bold text-slate-600 mb-1">টিকটক প্রোফাইল (URL)</label>
                                <input type="url" id="coTiktok" name="tiktok" placeholder="https://tiktok.com/@yourprofile" 
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-brand-600 shadow-2xs">
                            </div>
                        </div>
                    </details>

                    <!-- Navigation Buttons -->
                    <div class="pt-3 flex items-center justify-between gap-3">
                        <button type="button" onclick="goToStep(1)" class="px-4 py-2.5 sm:px-5 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center space-x-1 cursor-pointer">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>আগের ধাপ</span>
                        </button>
                        <button type="button" onclick="goToStep(3)" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-black rounded-xl transition shadow-brand-glow flex items-center space-x-1.5 cursor-pointer">
                            <span>পরবর্তী ধাপ (পেমেন্ট)</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- STEP 3: ORDER REVIEW, BKASH PAYMENT & CONFIRMATION       -->
                <!-- ======================================================== -->
                <div id="stepSection3" class="space-y-4 sm:space-y-5 step-transition hidden">
                    <!-- Step Header -->
                    <div class="border-b border-slate-100 pb-3 sm:pb-4 flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center font-bold">
                            <i data-lucide="credit-card" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black text-slate-900 leading-tight">ধাপ ৩: রিভিউ ও বিকাশ পেমেন্ট</h2>
                            <p class="text-[11px] text-slate-500 hidden sm:block">তথ্যের সারসংক্ষেপ দেখুন এবং বিকাশ ট্রানজেকশন সম্পন্ন করুন</p>
                        </div>
                    </div>

                    <!-- Review Summary Card (Compact) -->
                    <div class="p-3.5 sm:p-4 bg-slate-50 border border-slate-200/90 rounded-2xl space-y-2.5 text-xs">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-2.5">
                                <div id="reviewLogoThumb" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                    <img id="reviewLogoImg" src="" alt="Logo" class="w-full h-full object-contain hidden p-0.5">
                                    <i data-lucide="store" id="reviewLogoPlaceholder" class="w-5 h-5 text-slate-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-900 text-xs sm:text-sm leading-tight" id="reviewStoreName">-</h4>
                                    <div class="text-[11px] font-mono text-brand-600 font-bold mt-0.5" id="reviewStoreUrl">-</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase">মাসিক ফি</span>
                                <span class="text-lg sm:text-xl font-black text-brand-700 font-mono" id="reviewAmount">৳ <?= number_format($currentPkgPrice) ?></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2.5 border-t border-slate-200/70 text-[11px]">
                            <div>
                                <span class="text-slate-400 block">মালিক:</span>
                                <strong class="text-slate-800" id="reviewOwnerName">-</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block">ইমেইল:</span>
                                <strong class="text-slate-800 truncate block font-mono" id="reviewOwnerEmail">-</strong>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <span class="text-slate-400 block">মোবাইল:</span>
                                <strong class="font-mono text-slate-800" id="reviewOwnerPhone">-</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Universal bKash Payment Card (Clean & Focused) -->
                    <div class="p-3.5 sm:p-5 bg-gradient-to-r from-pink-50/80 via-rose-50/40 to-white border-2 border-pink-200 rounded-2xl sm:rounded-3xl space-y-3 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-pink-700 uppercase tracking-wider block">বিকাশ নম্বর (Send Money)</span>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span class="text-lg sm:text-2xl font-black text-pink-600 font-mono tracking-wider select-all" id="universalBkashText">
                                        <?= htmlspecialchars($universalBkash) ?>
                                    </span>
                                    <button type="button" onclick="copyUniversalBkash()" 
                                            class="px-2.5 py-1 bg-white hover:bg-pink-100 text-pink-700 border border-pink-300 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer shadow-2xs">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        <span>কপি</span>
                                    </button>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">প্রদেয় ফি</span>
                                <span class="text-base sm:text-xl font-black text-slate-900 font-mono block mt-0.5" id="coReviewPayFee">
                                    ৳ <?= number_format($currentPkgPrice) ?>
                                </span>
                            </div>
                        </div>

                        <!-- 3-Step Clean Payment Instruction -->
                        <div class="bg-white/95 p-3 rounded-xl border border-pink-100 text-[11px] sm:text-xs text-slate-700 space-y-1">
                            <div class="font-bold text-slate-900 flex items-center space-x-1 text-xs">
                                <i data-lucide="info" class="w-3.5 h-3.5 text-pink-600"></i>
                                <span>পেমেন্ট নির্দেশিকা:</span>
                            </div>
                            <p class="text-slate-600 leading-normal">
                                বিকাশ অ্যাপে <strong>Send Money</strong> করে উপরের নম্বরে <strong class="text-brand-700 font-mono" id="coPayFeeText">৳ <?= number_format($currentPkgPrice) ?></strong> পাঠান। এরপর আপনার নম্বর ও <strong>TrxID</strong> নিচে দিয়ে অর্ডার নিশ্চিত করুন।
                            </p>
                        </div>
                    </div>

                    <!-- Payment Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        <div>
                            <label for="coSenderNumber" class="block text-xs font-bold text-slate-700 mb-1">
                                প্রেরক বিকাশ নম্বর <span class="text-rose-500">*</span>
                            </label>
                            <input type="tel" id="coSenderNumber" name="sender_number" required 
                                   placeholder="01XXXXXXXXX" 
                                   class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-pink-500 shadow-2xs transition">
                        </div>

                        <div>
                            <label for="coTrxId" class="block text-xs font-bold text-slate-700 mb-1">
                                ট্রানজেকশন আইডি (TrxID) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="coTrxId" name="trx_id" required 
                                   placeholder="e.g. BL89XK241Z" 
                                   oninput="this.value = this.value.toUpperCase()"
                                   class="w-full px-3.5 py-2.5 bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-pink-500 shadow-2xs uppercase transition">
                        </div>
                    </div>

                    <!-- Navigation & Final Submit Button -->
                    <div class="pt-3 flex items-center justify-between gap-3 border-t border-slate-100">
                        <button type="button" onclick="goToStep(2)" class="px-4 py-2.5 sm:px-5 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center space-x-1 cursor-pointer">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>আগের ধাপ</span>
                        </button>
                        <button type="submit" id="btnSubmitCheckout" class="px-7 py-3.5 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-black rounded-xl transition shadow-brand-glow flex items-center space-x-2 cursor-pointer">
                            <i data-lucide="check-circle" class="w-4 h-4 text-brand-500"></i>
                            <span>অর্ডার নিশ্চিত করুন</span>
                        </button>
                    </div>
                </div>

            </form>

            <!-- ======================================================== -->
            <!-- NATIVE ORDER CONFIRMATION VIEW (NO MODAL ALERTS)         -->
            <!-- ======================================================== -->
            <div id="confirmationView" class="hidden text-center py-6 sm:py-8 space-y-5 step-transition">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-emerald-100 text-brand-600 mx-auto flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <i data-lucide="check-circle-2" class="w-10 h-10 sm:w-12 sm:h-12 stroke-[2.5]"></i>
                </div>

                <div class="space-y-1">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900">অভিনন্দন! আপনার স্টোর অর্ডার সফল হয়েছে</h2>
                    <p class="text-xs text-slate-500">আপনার তথ্য এবং বিকাশ পেমেন্ট রেকর্ড সফলভাবে সংরক্ষিত হয়েছে।</p>
                </div>

                <div class="max-w-md mx-auto text-left text-xs space-y-2.5 p-4 sm:p-5 bg-slate-50 border border-slate-200/90 rounded-2xl shadow-2xs">
                    <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                        <span class="text-slate-500">স্টোরের নাম:</span>
                        <strong class="font-black text-slate-900 text-sm" id="confStoreName">-</strong>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                        <span class="text-slate-500">স্টোর স্লাগ:</span>
                        <span class="font-mono text-brand-600 font-bold" id="confStoreSlug">-</span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                        <span class="text-slate-500">প্যাকেজ:</span>
                        <span class="font-bold text-slate-800" id="confPackageName">-</span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                        <span class="text-slate-500">bKash TrxID:</span>
                        <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded" id="confTrxId">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">স্ট্যাটাস:</span>
                        <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded">⏳ পেন্ডিং অনুমোদন (Awaiting Admin Approval)</span>
                    </div>
                </div>

                <div class="max-w-md mx-auto p-3.5 bg-emerald-50/80 border border-emerald-200/90 rounded-2xl text-left text-xs text-slate-700 space-y-1">
                    <div class="flex items-center space-x-1.5 font-bold text-brand-700">
                        <i data-lucide="mail-check" class="w-4 h-4 text-brand-600"></i>
                        <span>ইমেইলে ক্রেডেনশিয়াল পাঠানো হয়েছে</span>
                    </div>
                    <p class="text-slate-600 leading-relaxed">
                        আপনার লগইন ইমেইল ও পাসওয়ার্ড এবং স্টোরের যাবতীয় তথ্য আপনার ইমেইলে পাঠিয়ে দেওয়া হয়েছে। নিচের বাটন দিয়ে সরাসরি আপনার কাস্টমার পোর্টালে প্রবেশ করতে পারবেন:
                    </p>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="portal/" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-8 py-3.5 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-black rounded-xl transition shadow-brand-glow cursor-pointer">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>কাস্টমার পোর্টালে প্রবেশ করুন &rarr;</span>
                    </a>
                    <a href="<?= htmlspecialchars($homeUrl) ?>" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-3.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        <span>হোমপেজ</span>
                    </a>
                </div>
            </div>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200/80 py-5 text-center text-xs text-slate-400">
        <div class="max-w-5xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>&copy; <?= date('Y') ?> Ponnostack. সর্বস্বত্ব সংরক্ষিত।</span>
            <div class="flex items-center space-x-4 text-[11px] text-slate-500">
                <a href="<?= htmlspecialchars($homeUrl) ?>#features" class="hover:text-brand-600 transition">ফিচারসমূহ</a>
                <a href="<?= htmlspecialchars($homeUrl) ?>#pricing" class="hover:text-brand-600 transition">প্রাইসিং</a>
                <a href="<?= htmlspecialchars($homeUrl) ?>#contact" class="hover:text-brand-600 transition">যোগাযোগ ও সাপোর্ট</a>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const REGISTER_API_URL = "<?= htmlspecialchars($baseUrl) ?>api/storefront/register.php";
        const CHECK_SLUG_API_URL = "<?= htmlspecialchars($baseUrl) ?>api/storefront/check-slug.php";
        const UNIVERSAL_BKASH = "<?= htmlspecialchars($universalBkash) ?>";
        const HOME_URL = "<?= htmlspecialchars($homeUrl) ?>";

        let currentStep = 1;
        let selectedLogoFile = null;
        let slugCheckTimeout = null;
        let nameCheckTimeout = null;
        let emailCheckTimeout = null;
        let isSlugValid = false;

        // Active packages array
        const activePackages = <?= json_encode($packages, JSON_UNESCAPED_UNICODE) ?>;

        // ==========================================================
        // SLEEK FLOATING TOAST SYSTEM (NO SWEETALERT)
        // ==========================================================
        function showToast(message, type = 'error') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            // Remove previous toasts to show strictly one by one
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

        // ==========================================================
        // HIGHLIGHT FIELD IN RED & FOCUS (ONE BY ONE)
        // ==========================================================
        function highlightField(id, message) {
            const el = document.getElementById(id);
            if (!el) return;

            // Apply red border & subtle tint
            el.classList.add('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/30');
            el.classList.remove('border-slate-300');

            // Show Toast
            showToast(message, 'error');

            // Focus and smooth scroll
            el.focus();
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Remove red border as soon as user types
            const onTypeClear = () => {
                el.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/30');
                el.classList.add('border-slate-300');
                el.removeEventListener('input', onTypeClear);
            };
            el.addEventListener('input', onTypeClear);
        }

        function clearFieldError(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/30');
            el.classList.add('border-slate-300');
        }

        function highlightLogo(message) {
            const logoSec = document.getElementById('coLogoSection');
            if (logoSec) {
                logoSec.classList.add('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/40');
                logoSec.classList.remove('border-slate-200/90', 'bg-slate-50/80');
                logoSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            showToast(message, 'error');
            const fileInput = document.getElementById('coLogoFile');
            if (fileInput) fileInput.focus();
        }

        function clearLogoHighlight() {
            const logoSec = document.getElementById('coLogoSection');
            if (logoSec) {
                logoSec.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/40');
                logoSec.classList.add('border-slate-200/90', 'bg-slate-50/80');
            }
        }

        // Auto Slug Generator & Store Name Uniqueness Check
        function handleStoreNameInput(name) {
            const cleanName = name.trim();
            const nameBadge = document.getElementById('nameStatusBadge');
            clearFieldError('coStoreName');

            const slugInput = document.getElementById('coStoreSlug');
            if (slugInput.dataset.userEdited !== 'true') {
                let slug = cleanName.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                if (slug) {
                    slugInput.value = slug;
                    handleSlugInput(slug);
                }
            }

            if (!cleanName || cleanName.length < 2) {
                if (nameBadge) nameBadge.innerHTML = '';
                return;
            }

            clearTimeout(nameCheckTimeout);
            nameCheckTimeout = setTimeout(() => {
                fetch(`${CHECK_SLUG_API_URL}?name=${encodeURIComponent(cleanName)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (nameBadge) {
                            if (data.available) {
                                nameBadge.innerHTML = '<span class="text-emerald-600 font-bold">✓ নামটি ফাঁকা আছে!</span>';
                            } else {
                                nameBadge.innerHTML = `<span class="text-rose-600 font-bold">✕ ${data.message || 'ইতিমধ্যে ব্যবহৃত'}</span>`;
                            }
                        }
                    })
                    .catch(() => {});
            }, 350);
        }

        // Real-Time Slug Check with Debounce
        function handleSlugInput(val) {
            const rawSlug = val.toLowerCase().replace(/[^a-z0-9-]/g, '');
            const slugInput = document.getElementById('coStoreSlug');
            slugInput.value = rawSlug;
            clearFieldError('coStoreSlug');

            const preview = document.getElementById('slugLivePreview');
            if (preview) {
                preview.innerText = rawSlug || '';
            }

            const indicator = document.getElementById('slugCheckingIndicator');
            const badge = document.getElementById('slugStatusBadge');

            if (!rawSlug || rawSlug.length < 2) {
                isSlugValid = false;
                badge.innerHTML = '<span class="text-slate-400">কমপক্ষে ২ অক্ষর</span>';
                return;
            }

            clearTimeout(slugCheckTimeout);
            indicator.classList.remove('hidden');

            slugCheckTimeout = setTimeout(() => {
                fetch(`${CHECK_SLUG_API_URL}?slug=${encodeURIComponent(rawSlug)}`)
                    .then(res => res.json())
                    .then(data => {
                        indicator.classList.add('hidden');
                        if (data.available) {
                            isSlugValid = true;
                            badge.innerHTML = '<span class="text-emerald-600 font-bold">✓ স্লাগটি খালি আছে!</span>';
                        } else {
                            isSlugValid = false;
                            badge.innerHTML = `<span class="text-rose-600 font-bold">✕ ${data.message || 'ইতিমধ্যে ব্যবহৃত'}</span>`;
                        }
                    })
                    .catch(() => {
                        indicator.classList.add('hidden');
                        isSlugValid = true;
                    });
            }, 350);
        }

        document.getElementById('coStoreSlug').addEventListener('input', function() {
            this.dataset.userEdited = 'true';
        });

        // Real-Time Email Check with Debounce
        function handleEmailInput(val) {
            const email = val.trim().toLowerCase();
            const badge = document.getElementById('emailStatusBadge');
            clearFieldError('coOwnerEmail');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!email || !emailRegex.test(email)) {
                if (badge) badge.innerHTML = '';
                return;
            }

            clearTimeout(emailCheckTimeout);
            emailCheckTimeout = setTimeout(() => {
                fetch(`${CHECK_SLUG_API_URL}?email=${encodeURIComponent(email)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (badge) {
                            if (data.available) {
                                badge.innerHTML = '<span class="text-emerald-600 font-bold">✓ ইমেইলটি ব্যবহারযোগ্য!</span>';
                            } else {
                                badge.innerHTML = `<span class="text-rose-600 font-bold">✕ স্টোরফ্রন্ট ইমেইল অলরেডি এক্সিস্ট!</span>`;
                            }
                        }
                    })
                    .catch(() => {});
            }, 350);
        }

        // Domain Type Toggle
        function toggleDomainChoice(choice) {
            const customBox = document.getElementById('customDomainInputBox');
            const subLabel = document.getElementById('domainOptionSubdomainLabel');
            const custLabel = document.getElementById('domainOptionCustomLabel');

            if (choice === 'custom') {
                customBox.classList.remove('hidden');
                custLabel.classList.add('border-brand-600', 'border-2');
                custLabel.classList.remove('border-slate-300');
                subLabel.classList.remove('border-brand-600', 'border-2');
                subLabel.classList.add('border-slate-300');
                document.getElementById('coCustomDomain').focus();
            } else {
                customBox.classList.add('hidden');
                subLabel.classList.add('border-brand-600', 'border-2');
                subLabel.classList.remove('border-slate-300');
                custLabel.classList.remove('border-brand-600', 'border-2');
                custLabel.classList.add('border-slate-300');
                document.getElementById('coCustomDomain').value = '';
                clearFieldError('coCustomDomain');
            }
        }

        // Logo File Handler & Preview
        function handleLogoSelect(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 5242880) { // 5MB
                showToast('ফাইলটি অনেক বড়! সর্বোচ্চ ৫ মেগাবাইট ছবি দিন।', 'error');
                e.target.value = '';
                return;
            }

            clearLogoHighlight();
            selectedLogoFile = file;
            const reader = new FileReader();
            reader.onload = function(evt) {
                const img = document.getElementById('logoPreviewImg');
                img.src = evt.target.result;
                img.classList.remove('hidden');
                document.getElementById('logoPlaceholderIcon').classList.add('hidden');
                document.getElementById('btnRemoveLogo').classList.remove('hidden');
                document.getElementById('logoStatusText').innerText = file.name;

                // Step 3 Review preview
                const rImg = document.getElementById('reviewLogoImg');
                rImg.src = evt.target.result;
                rImg.classList.remove('hidden');
                document.getElementById('reviewLogoPlaceholder').classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        function removeSelectedLogo() {
            selectedLogoFile = null;
            document.getElementById('coLogoFile').value = '';
            const img = document.getElementById('logoPreviewImg');
            img.src = '';
            img.classList.add('hidden');
            document.getElementById('logoPlaceholderIcon').classList.remove('hidden');
            document.getElementById('btnRemoveLogo').classList.add('hidden');
            document.getElementById('logoStatusText').innerText = 'PNG, JPG, WebP বা SVG (সর্বোচ্চ ৫MB)';

            const rImg = document.getElementById('reviewLogoImg');
            rImg.src = '';
            rImg.classList.add('hidden');
            document.getElementById('reviewLogoPlaceholder').classList.remove('hidden');
        }

        // Show/Hide Password Toggle
        function togglePasswordVisibility() {
            const input = document.getElementById('coOwnerPassword');
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

        // Package Change Handler
        function onPackageSelectChange(select) {
            const slug = select.value;
            const opt = select.options[select.selectedIndex];
            const name = opt.getAttribute('data-name');
            const price = parseInt(opt.getAttribute('data-price')) || 200;
            const limit = parseInt(opt.getAttribute('data-limit')) || 0;
            const limitText = opt.getAttribute('data-limit-text') || '';
            const badge = opt.getAttribute('data-badge') || 'প্যাকেজ';

            // Update Hidden Inputs
            document.getElementById('coPackageSlug').value = slug;
            document.getElementById('coPackageName').value = name;
            document.getElementById('coAmount').value = price;
            document.getElementById('coProductLimit').value = limit;

            // Update Top Card (Restoring original Brand Green font-mono styling)
            document.getElementById('coDisplayPackageName').innerText = name;
            document.getElementById('coDisplayAmount').innerText = '৳ ' + price.toLocaleString('en-US');
            document.getElementById('pkgLimitText').innerText = limitText;
            document.getElementById('pkgBadgeText').innerText = badge;

            // Update Step 3 Review
            document.getElementById('reviewAmount').innerText = '৳ ' + price.toLocaleString('en-US');
            document.getElementById('coReviewPayFee').innerText = '৳ ' + price.toLocaleString('en-US');
            document.getElementById('coPayFeeText').innerText = '৳ ' + price.toLocaleString('en-US');
        }

        // ==========================================================
        // STRICT ONE-BY-ONE STEP NAVIGATION & VALIDATION
        // ==========================================================
        async function goToStep(step) {
            if (step === currentStep) return;

            // Prevent skipping ahead past the next sequential step
            if (step > currentStep + 1) {
                step = currentStep + 1;
            }

            // Strictly validate forward progress
            if (step > currentStep) {
                if (currentStep === 1) {
                    const storeName = document.getElementById('coStoreName').value.trim();
                    const storeSlug = document.getElementById('coStoreSlug').value.trim();
                    const domainChoice = document.querySelector('input[name="domain_choice"]:checked').value;
                    const customDomain = document.getElementById('coCustomDomain').value.trim();

                    // 1. Check Store Name empty
                    if (!storeName || storeName.length < 2) {
                        highlightField('coStoreName', 'অনুগ্রহ করে আগে স্টোরের নাম লিখুন (কমপক্ষে ২ অক্ষর)');
                        return;
                    }

                    // 2. Check Store Name uniqueness in DB
                    try {
                        const nameRes = await fetch(`${CHECK_SLUG_API_URL}?name=${encodeURIComponent(storeName)}`);
                        const nameData = await nameRes.json();
                        if (!nameData.available) {
                            highlightField('coStoreName', 'এই নামে ইতিমধ্যে একটি স্টোর নিবন্ধিত আছে। অন্য নাম দিন।');
                            return;
                        }
                    } catch(e) {}

                    // 3. Check Store Slug empty
                    if (!storeSlug || storeSlug.length < 2) {
                        highlightField('coStoreSlug', 'অনুগ্রহ করে স্টোর স্লাগ দিন (ইংরেজি অক্ষর ও হাইফেন)');
                        return;
                    }

                    // 4. Check Slug uniqueness in DB
                    try {
                        const slugRes = await fetch(`${CHECK_SLUG_API_URL}?slug=${encodeURIComponent(storeSlug)}`);
                        const slugData = await slugRes.json();
                        if (!slugData.available) {
                            highlightField('coStoreSlug', 'এই স্লাগটি ইতিমধ্যে অন্য কেউ ব্যবহার করছে।');
                            return;
                        }
                    } catch(e) {}

                    // 5. Check Custom Domain (if selected)
                    if (domainChoice === 'custom' && !customDomain) {
                        highlightField('coCustomDomain', 'অনুগ্রহ করে আপনার নিজস্ব ডোমেনের নাম লিখুন');
                        return;
                    }

                    // 6. Check Store Logo (Mandatory)
                    if (!selectedLogoFile) {
                        highlightLogo('অনুগ্রহ করে স্টোরের একটি লোগো আপলোড করুন');
                        return;
                    }
                } else if (currentStep === 2) {
                    const ownerName = document.getElementById('coOwnerName').value.trim();
                    const ownerEmail = document.getElementById('coOwnerEmail').value.trim();
                    const ownerPhone = document.getElementById('coOwnerPhone').value.trim();
                    const ownerPassword = document.getElementById('coOwnerPassword').value.trim();

                    // 1. Check Owner Name
                    if (!ownerName || ownerName.length < 2) {
                        highlightField('coOwnerName', 'অনুগ্রহ করে আপনার নাম লিখুন');
                        return;
                    }

                    // 2. Check Owner Email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!ownerEmail || !emailRegex.test(ownerEmail)) {
                        highlightField('coOwnerEmail', 'অনুগ্রহ করে সঠিক ইমেইল ঠিকানা প্রদান করুন');
                        return;
                    }

                    // 3. Check Owner Email uniqueness in DB
                    try {
                        const emailRes = await fetch(`${CHECK_SLUG_API_URL}?email=${encodeURIComponent(ownerEmail)}`);
                        const emailData = await emailRes.json();
                        if (!emailData.available) {
                            highlightField('coOwnerEmail', 'স্টোরফ্রন্ট ইমেইল অলরেডি এক্সিস্ট! অন্য ইমেইল দিন।');
                            return;
                        }
                    } catch(e) {}

                    // 4. Check Owner Phone
                    if (!ownerPhone || ownerPhone.length < 10) {
                        highlightField('coOwnerPhone', 'অনুগ্রহ করে সঠিক মোবাইল নম্বর লিখুন');
                        return;
                    }

                    // 5. Check Password
                    if (!ownerPassword || ownerPassword.length < 4) {
                        highlightField('coOwnerPassword', 'পাসওয়ার্ড কমপক্ষে ৪ অক্ষরের হতে হবে');
                        return;
                    }
                }
            }

            // Populate Step 3 Review Card when entering Step 3
            if (step === 3) {
                const storeName = document.getElementById('coStoreName').value.trim();
                const storeSlug = document.getElementById('coStoreSlug').value.trim();
                const domainChoice = document.querySelector('input[name="domain_choice"]:checked').value;
                const customDomain = document.getElementById('coCustomDomain').value.trim();
                const ownerName = document.getElementById('coOwnerName').value.trim();
                const ownerEmail = document.getElementById('coOwnerEmail').value.trim();
                const ownerPhone = document.getElementById('coOwnerPhone').value.trim();

                document.getElementById('reviewStoreName').innerText = storeName;
                document.getElementById('reviewOwnerName').innerText = ownerName;
                document.getElementById('reviewOwnerEmail').innerText = ownerEmail;
                document.getElementById('reviewOwnerPhone').innerText = ownerPhone;

                if (domainChoice === 'custom' && customDomain) {
                    document.getElementById('reviewStoreUrl').innerText = customDomain;
                } else {
                    document.getElementById('reviewStoreUrl').innerText = storeSlug + '.ponnostack.shop';
                }
            }

            // Animate and Switch Steps
            currentStep = step;
            ['stepSection1', 'stepSection2', 'stepSection3'].forEach((id, idx) => {
                const el = document.getElementById(id);
                if (idx + 1 === step) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });

            // Update Progress Bar
            const line = document.getElementById('stepProgressLine');
            const b1 = document.getElementById('badgeStep1');
            const b2 = document.getElementById('badgeStep2');
            const b3 = document.getElementById('badgeStep3');
            const l1 = document.getElementById('labelStep1');
            const l2 = document.getElementById('labelStep2');
            const l3 = document.getElementById('labelStep3');

            if (step === 1) {
                line.style.width = '0%';
                b1.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-brand-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow-md shadow-brand-600/30 step-transition focus:outline-none';
                b1.innerHTML = '<span>১</span>';
                b2.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none';
                b2.innerHTML = '<span>২</span>';
                b3.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none';
                b3.innerHTML = '<span>৩</span>';
                l1.className = 'text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center';
                l2.className = 'text-[11px] sm:text-xs font-bold text-slate-400 mt-1.5 text-center';
                l3.className = 'text-[11px] sm:text-xs font-bold text-slate-400 mt-1.5 text-center';
            } else if (step === 2) {
                line.style.width = '50%';
                b1.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none';
                b1.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i>';
                b2.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-brand-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow-md shadow-brand-600/30 step-transition focus:outline-none';
                b2.innerHTML = '<span>২</span>';
                b3.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none';
                b3.innerHTML = '<span>৩</span>';
                l1.className = 'text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center';
                l2.className = 'text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center';
                l3.className = 'text-[11px] sm:text-xs font-bold text-slate-400 mt-1.5 text-center';
            } else if (step === 3) {
                line.style.width = '100%';
                b1.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none';
                b1.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i>';
                b2.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm step-transition focus:outline-none';
                b2.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i>';
                b3.className = 'w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-brand-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow-md shadow-brand-600/30 step-transition focus:outline-none';
                b3.innerHTML = '<span>৩</span>';
                l1.className = 'text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center';
                l2.className = 'text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center';
                l3.className = 'text-[11px] sm:text-xs font-black text-brand-700 mt-1.5 text-center';
            }

            if (window.lucide) lucide.createIcons();
            window.scrollTo({ top: 100, behavior: 'smooth' });
        }

        // Copy Universal bKash Number
        function copyUniversalBkash() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(UNIVERSAL_BKASH).then(() => {
                    showToast('বিকাশ নম্বর কপি হয়েছে: ' + UNIVERSAL_BKASH, 'success');
                });
            }
        }

        // Final Form Submission Handler (One-by-One validation & Native Success View)
        function handleMultiStepSubmit(e) {
            e.preventDefault();

            // Safety check for logo
            if (!selectedLogoFile) {
                goToStep(1);
                highlightLogo('অনুগ্রহ করে স্টোরের একটি লোগো আপলোড করুন');
                return;
            }

            const senderNumber = document.getElementById('coSenderNumber').value.trim();
            const trxId = document.getElementById('coTrxId').value.trim().toUpperCase();

            // 1. Check Sender Number
            if (!senderNumber || senderNumber.length < 10) {
                highlightField('coSenderNumber', 'যে বিকাশ নম্বর থেকে টাকা পাঠিয়েছেন তা লিখুন');
                return;
            }

            // 2. Check TrxID
            if (!trxId || trxId.length < 6) {
                highlightField('coTrxId', 'বিকাশ ট্রানজেকশন আইডি (TrxID) প্রদান করুন');
                return;
            }

            const btn = document.getElementById('btnSubmitCheckout');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1.5"></i><span>অর্ডার হচ্ছে...</span>';

            const form = document.getElementById('multiStepCheckoutForm');
            const formData = new FormData(form);

            if (selectedLogoFile) {
                formData.set('logo', selectedLogoFile);
            }

            fetch(REGISTER_API_URL, {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error('Non-JSON server response:', text);
                    return {
                        success: false,
                        message: 'সার্ভার রেসপন্স ত্রুটি: অনুগ্রহ করে পুনরায় চেষ্টা করুন।'
                    };
                }
                return data;
            })
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-brand-500 inline mr-1.5"></i><span>অর্ডার নিশ্চিত করুন</span>';

                if (data.success) {
                    const tenant = data.data.tenant;
                    
                    // Auto-Login: Store auth token in localStorage
                    if (data.data.auth) {
                        try {
                            localStorage.setItem('ponnostack_auth_token', data.data.auth.token);
                            localStorage.setItem('ponnostack_tenant', JSON.stringify(data.data.auth));
                            localStorage.setItem('ponnostack_tenant_uuid', data.data.auth.uuid);
                            localStorage.setItem('ponnostack_tenant_email', data.data.auth.email);
                            localStorage.setItem('ponnostack_tenant_name', data.data.auth.name);
                        } catch (err) {}
                    }

                    // Hide Form & Stepper
                    document.getElementById('multiStepCheckoutForm').classList.add('hidden');
                    document.getElementById('stepProgressCard').classList.add('hidden');
                    document.getElementById('packageSelectStrip').classList.add('hidden');

                    // Populate Native Confirmation View
                    document.getElementById('confStoreName').innerText = tenant.name;
                    document.getElementById('confStoreSlug').innerText = tenant.slug;
                    document.getElementById('confPackageName').innerText = (tenant.package_name || 'স্ট্যান্ডার্ড') + ' (৳ ' + tenant.monthly_fee + '/মাস)';
                    document.getElementById('confTrxId').innerText = tenant.payment_trx_id || '-';

                    // Reveal Confirmation View
                    const confView = document.getElementById('confirmationView');
                    confView.classList.remove('hidden');

                    if (window.lucide) lucide.createIcons();
                    window.scrollTo({ top: 50, behavior: 'smooth' });

                    showToast('অর্ডার সফলভাবে সম্পন্ন হয়েছে!', 'success');
                } else {
                    showToast(data.message || 'অর্ডার প্রসেস ব্যর্থ হয়েছে, পুনরায় চেষ্টা করুন।', 'error');
                }
                if (window.lucide) lucide.createIcons();
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-brand-500 inline mr-1.5"></i><span>অর্ডার নিশ্চিত করুন</span>';
                console.error('Fetch error:', err);
                showToast('সার্ভারের সাথে সংযোগ স্থাপন করা যায়নি। ইন্টারনেট পরীক্ষা করুন।', 'error');
                if (window.lucide) lucide.createIcons();
            });
        }

        // Initialize icons on load
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>