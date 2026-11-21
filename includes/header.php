<?php

declare(strict_types=1);

/**
 * Ponnostack Public - Header
 * Pure Light Mode Standard with Legacy Assets, Dual Language (BN / EN)
 * Sticky, Smooth Scrolling, Professional Shadow & Fluid Micro-interactions
 */

// 1. Language Detection & State
$lang = (string)($_GET['lang'] ?? $_COOKIE['ponnostack_lang'] ?? 'bn');
if (!in_array($lang, ['bn', 'en'], true)) {
    $lang = 'bn';
}

if (isset($_GET['lang'])) {
    setcookie('ponnostack_lang', $lang, time() + (86400 * 30), '/');
}

// 2. Language Dictionary
$translations = [
    'bn' => [
        'title'       => 'Ponnostack - বাংলাদেশে নো-কোড ই-কমার্স ওয়েবসাইট বিল্ডার',
        'desc'        => 'কোনো কোডিং ছাড়াই মাত্র ২ মিনিটে তৈরি করুন আপনার অনলাইন শপ। সাশ্রয়ী মূল্যে ফ্রি সাবডোমেন, কাস্টম ডোমেন ও দ্রুতগতির নো-কোড ই-কমার্স প্ল্যাটফর্ম পন্যস্ট্যাক।',
        'keywords'    => 'ই-কমার্স ওয়েবসাইট বিল্ডার বাংলাদেশ, অনলাইন শপ তৈরি, নো কোড ওয়েবসাইট, ecommerce website builder bangladesh, create online store bd, ponnostack',
        'home'        => 'হোম',
        'features'    => 'ফিচারসমূহ',
        'pricing'     => 'প্রাইসিং',
        'contact'     => 'যোগাযোগ',
        'cta'         => 'শুরু করুন',
    ],
    'en' => [
        'title'       => 'Ponnostack - No-Code E-Commerce Website Builder in Bangladesh',
        'desc'        => 'Build and launch your custom branded online store in Bangladesh in 2 minutes without coding. Fast, secure, and affordable e-commerce platform.',
        'keywords'    => 'no-code ecommerce builder bangladesh, online store maker bd, create online shop bangladesh, ecommerce platform dhaka, ponnostack',
        'home'        => 'Home',
        'features'    => 'Features',
        'pricing'     => 'Pricing',
        'contact'     => 'Contact',
        'cta'         => 'Get Started',
    ],
];

$t = $translations[$lang];

// 3. Dynamic Canonical Base & URL Detection (Localhost & Production ponnostack.shop)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isProduction = (bool)preg_match('/ponnostack\.shop/i', $host);

if ($isProduction) {
    $baseUrl = 'https://ponnostack.shop/';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $baseUrl = $protocol . $host . '/Ponnostack/';
}

$canonicalUrl = $baseUrl . ($lang === 'en' ? '?lang=en' : '');
// Production canonical for search engines
$prodUrl = 'https://ponnostack.shop/' . ($lang === 'en' ? '?lang=en' : '');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title']) ?></title>

    <!-- CRITICAL SEO META TAGS (Target: 95-100 Google Lighthouse Score) -->
    <meta name="description" content="<?= htmlspecialchars($t['desc']) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($t['keywords']) ?>">
    <meta name="author" content="Ponnostack">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

    <!-- CANONICAL & MULTILINGUAL HREFLANG -->
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <link rel="alternate" hreflang="bn" href="<?= htmlspecialchars($baseUrl) ?>">
    <link rel="alternate" hreflang="en" href="<?= htmlspecialchars($baseUrl . '?lang=en') ?>">
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($baseUrl) ?>">

    <!-- GEO-TARGETING FOR GOOGLE SEARCH (BANGLADESH) -->
    <meta name="geo.region" content="BD">
    <meta name="geo.placename" content="Dhaka, Bangladesh">
    <meta name="geo.position" content="23.8103;90.4125">
    <meta name="ICBM" content="23.8103, 90.4125">

    <!-- OPEN GRAPH (Facebook, LinkedIn, WhatsApp) -->
    <meta property="og:site_name" content="Ponnostack">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($t['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($t['desc']) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($baseUrl . 'assets/images/og-banner.png') ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($baseUrl . 'assets/images/og-banner.png') ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1920">
    <meta property="og:image:height" content="1080">
    <meta property="og:image:alt" content="Ponnostack - No-Code E-Commerce Website Builder">
    <meta property="og:locale" content="<?= $lang === 'en' ? 'en_US' : 'bn_BD' ?>">

    <!-- TWITTER / X CARDS -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($t['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($t['desc']) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($baseUrl . 'assets/images/og-banner.png') ?>">
    <meta name="theme-color" content="#00684A">

    <!-- BRAND IDENTITY & FAVICONS (Google Search 48px Compliant) -->
    <link rel="icon" href="assets/images/favicon.ico" sizes="48x48">
    <link rel="icon" type="image/png" sizes="48x48" href="assets/images/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- SCHEMA.ORG STRUCTURED DATA (JSON-LD FOR GOOGLE RICH RESULTS) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "WebSite",
                "@id": "<?= $baseUrl ?>#website",
                "url": "<?= $baseUrl ?>",
                "name": "Ponnostack",
                "description": "<?= htmlspecialchars($t['desc']) ?>",
                "inLanguage": "<?= $lang === 'en' ? 'en-US' : 'bn-BD' ?>"
            },
            {
                "@type": "Organization",
                "@id": "<?= $baseUrl ?>#organization",
                "name": "Ponnostack",
                "url": "<?= $baseUrl ?>",
                "logo": {
                    "@type": "ImageObject",
                    "url": "<?= $baseUrl ?>assets/images/logo.png",
                    "width": 512,
                    "height": 160
                },
                "contactPoint": {
                    "@type": "ContactPoint",
                    "telephone": "+8801642956206",
                    "contactType": "Customer Support",
                    "email": "support@ponnostack.shop",
                    "areaServed": "BD",
                    "availableLanguage": ["Bengali", "English"]
                },
                "address": {
                    "@type": "PostalAddress",
                    "addressLocality": "Dhaka",
                    "addressCountry": "BD"
                }
            },
            {
                "@type": "SoftwareApplication",
                "@id": "<?= $baseUrl ?>#software",
                "name": "Ponnostack E-Commerce Platform",
                "applicationCategory": "BusinessApplication",
                "operatingSystem": "All modern web browsers",
                "offers": {
                    "@type": "Offer",
                    "price": "200.00",
                    "priceCurrency": "BDT",
                    "priceValidUntil": "2027-12-31",
                    "availability": "https://schema.org/InStock",
                    "seller": {
                        "@id": "<?= $baseUrl ?>#organization"
                    }
                },
                "aggregateRating": {
                    "@type": "AggregateRating",
                    "ratingValue": "4.9",
                    "reviewCount": "184"
                }
            },
            {
                "@type": "FAQPage",
                "@id": "<?= $baseUrl ?>#faq",
                "mainEntity": [
                    {
                        "@type": "Question",
                        "name": "পন্যস্ট্যাক ব্যবহার করতে কি কোনো কোডিং জানতে হবে?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "না, কোনো কোডিং বা টেকনিক্যাল জ্ঞানের প্রয়োজন নেই। নো-কোড ইন্টারফেস দিয়ে মাত্র কয়েক মিনিটে নিজের ওয়েবসাইট সাজাতে পারবেন।"
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "আমি কি নিজস্ব কাস্টম ডোমেইন ব্যবহার করতে পারব?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "হ্যাঁ, যেকোনো প্ল্যানেই আপনার নিজস্ব .com, .xyz, বা .shop ডোমেইন ফ্রিতে কানেক্ট করতে পারবেন।"
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "পন্যস্ট্যাকের মাসিক খরচ কত?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "আমাদের স্টার্টার প্ল্যান শুরু মাত্র ২০০ টাকা/মাস থেকে।"
                        }
                    }
                ]
            }
        ]
    }
    </script>

    <!-- Google Fonts: Plus Jakarta Sans & Hind Siliguri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Hind Siliguri"', 'sans-serif'],
                        bangla: ['"Hind Siliguri"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#00ED64', // MongoDB Spring Green
                            500: '#10b981',
                            600: '#00684A', // MongoDB Forest Green
                            700: '#005139',
                            800: '#023424',
                            900: '#001E2B', // MongoDB Slate Navy
                        }
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- GSAP (GreenSock) Animation Engine -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <style>
        /* 1. Global Smooth Scrolling with Sticky Header Offset */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5.5rem;
        }

        /* 2. MongoDB Green Selection */
        ::selection {
            background-color: #00684A;
            color: #ffffff;
        }

        /* 3. Header Transition & High-End Shadows */
        #mainHeader {
            transition: background-color 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        border-color 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        padding 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .header-top {
            background-color: rgba(255, 255, 255, 0.88);
            border-bottom: 1px solid rgba(241, 245, 249, 0.9);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .header-scrolled {
            background-color: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 4px 20px -2px rgba(0, 30, 43, 0.07), 0 2px 6px -1px rgba(0, 30, 43, 0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /* 4. Nav Link MongoDB Green Underline Indicator */
        .nav-link {
            position: relative;
            transition: color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: #00684A;
            border-radius: 9999px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            transform: translateX(-50%);
        }
        .nav-link:hover::after {
            width: 100%;
        }

        /* 5. MongoDB Primary Green Button (.btn-cta) */
        .btn-cta {
            background: linear-gradient(135deg, #00684A 0%, #005139 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px -1px rgba(0, 104, 74, 0.35);
            transition: filter 0.25s ease, box-shadow 0.25s ease, transform 0.15s ease;
        }
        .btn-cta:hover {
            filter: brightness(1.12);
            box-shadow: 0 4px 14px -1px rgba(0, 104, 74, 0.45);
        }
        .btn-cta:active {
            filter: brightness(0.96);
            transform: scale(0.98);
        }

        /* 6. Mobile Menu Entrance Animation */
        @keyframes mobileSlideIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-mobile-menu {
            animation: mobileSlideIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* 7. Page Preloader High-End Animations */
        #pagePreloader {
            transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s ease;
        }

        @keyframes loaderSweep {
            0% {
                transform: translateX(-100%);
            }
            50% {
                transform: translateX(35%);
            }
            100% {
                transform: translateX(130%);
            }
        }

        .animate-loader-sweep {
            animation: loaderSweep 1.3s cubic-bezier(0.65, 0, 0.35, 1) infinite;
        }

        @keyframes subtlePulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.08);
                opacity: 0.35;
            }
        }

        .animate-subtle-pulse {
            animation: subtlePulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Global High-End Page Preloader (Pure Light Mode) -->
    <div id="pagePreloader" class="fixed inset-0 z-[9999] bg-white flex flex-col items-center justify-center select-none" aria-live="polite">
        <div class="relative flex flex-col items-center">
            <!-- Brand Icon Container with Dual Glow & Spinner Ring -->
            <div class="relative w-20 h-20 mb-5 flex items-center justify-center">
                <!-- Soft Breathing Glow -->
                <div class="absolute inset-0 rounded-3xl bg-emerald-500/15 filter blur-md animate-subtle-pulse"></div>

                <!-- Spinning Micro Accent Ring -->
                <div class="absolute -inset-1 rounded-[26px] border-2 border-slate-100"></div>
                <div class="absolute -inset-1 rounded-[26px] border-2 border-transparent border-t-[#00684A] animate-spin"></div>

                <!-- Center Brand Logo Card -->
                <div class="relative w-16 h-16 rounded-2xl bg-white border border-slate-200/80 shadow-md shadow-emerald-600/5 flex items-center justify-center p-3">
                    <img src="assets/images/favicon.png" alt="Ponnostack Logo" width="48" height="48" class="w-full h-full object-contain">
                </div>
            </div>

            <!-- Typography & Status -->
            <div class="text-center space-y-1">
                <span class="text-sm font-black text-slate-900 tracking-tight block">Ponnostack</span>
                <span class="text-[11px] font-bold text-slate-400 tracking-wider flex items-center justify-center space-x-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#00ED64] animate-ping"></span>
                    <span><?= $lang === 'en' ? 'Loading experience...' : 'লোড হচ্ছে...' ?></span>
                </span>
            </div>

            <!-- Sleek Minimalist Progress Track -->
            <div class="w-40 h-1 bg-slate-100 rounded-full mt-5 overflow-hidden">
                <div class="w-1/2 h-full bg-gradient-to-r from-[#00684A] to-[#00ED64] rounded-full animate-loader-sweep"></div>
            </div>
        </div>
    </div>

    <!-- Sticky & Smooth Professional Header -->
    <header id="mainHeader" class="sticky top-0 z-50 header-top w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="navContainer" class="flex items-center justify-between h-16 sm:h-20 transition-all duration-300">
                <!-- Brand Logo from Legacy Assets -->
                <a href="./" class="flex items-center group py-1 shrink-0">
                    <img src="assets/images/logo.png" alt="Ponnostack" width="160" height="48"
                         class="h-7 sm:h-9 w-auto max-w-[120px] sm:max-w-none object-contain transition-transform duration-300 group-hover:scale-[1.02]">
                </a>

                <!-- Desktop Nav Links (Smooth Hover Indicator) -->
                <nav class="hidden md:flex items-center space-x-8 text-xs font-bold text-slate-600">
                    <a href="./" class="nav-link hover:text-[#00684A]"><?= htmlspecialchars($t['home']) ?></a>
                    <a href="#pricing" class="nav-link hover:text-[#00684A]"><?= htmlspecialchars($t['pricing']) ?></a>
                    <a href="#features" class="nav-link hover:text-[#00684A]"><?= htmlspecialchars($t['features']) ?></a>
                    <a href="#contact" class="nav-link hover:text-[#00684A]"><?= htmlspecialchars($t['contact']) ?></a>
                </nav>

                <!-- Right Side: Dual Language Switcher & CTAs (Desktop) -->
                <div class="hidden md:flex items-center space-x-3.5 shrink-0">
                    <!-- Language Switcher Pill (Bangla / English) -->
                    <div class="inline-flex items-center rounded-xl bg-slate-100/90 p-0.5 border border-slate-200/80 text-[11px] font-bold shadow-2xs shrink-0">
                        <a href="?lang=bn" 
                           class="px-2.5 py-1 rounded-lg transition-all duration-200 <?= $lang === 'bn' ? 'bg-white text-[#00684A] shadow-xs font-black' : 'text-slate-500 hover:text-slate-900' ?>"
                           title="বাংলা সংস্করণ">
                            বাংলা
                        </a>
                        <a href="?lang=en" 
                           class="px-2.5 py-1 rounded-lg transition-all duration-200 <?= $lang === 'en' ? 'bg-white text-[#00684A] shadow-xs font-black' : 'text-slate-500 hover:text-slate-900' ?>"
                           title="English Version">
                            EN
                        </a>
                    </div>

                    <!-- Primary Header CTA Button (Points to Pricing) -->
                    <a href="#pricing" 
                       class="btn-cta text-xs font-bold text-white px-5 py-2.5 rounded-xl inline-flex items-center justify-center space-x-1.5 cursor-pointer shrink-0 whitespace-nowrap">
                        <span><?= htmlspecialchars($t['cta']) ?></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 shrink-0"></i>
                    </a>
                </div>

                <!-- Mobile Header Actions & Toggle -->
                <div class="flex md:hidden items-center space-x-2 shrink-0">
                    <!-- Mobile Language Switcher -->
                    <div class="inline-flex items-center rounded-lg bg-slate-100 p-0.5 border border-slate-200 text-[10px] font-bold shrink-0">
                        <a href="?lang=bn" class="px-2 py-0.5 rounded transition <?= $lang === 'bn' ? 'bg-white text-[#00684A] shadow-2xs font-black' : 'text-slate-500' ?>">বাং</a>
                        <a href="?lang=en" class="px-2 py-0.5 rounded transition <?= $lang === 'en' ? 'bg-white text-[#00684A] shadow-2xs font-black' : 'text-slate-500' ?>">EN</a>
                    </div>

                    <!-- Mobile CTA Button in Header Navbar -->
                    <a href="#pricing" 
                       class="btn-cta text-xs font-bold text-white px-3.5 py-1.5 rounded-xl inline-flex items-center justify-center gap-1.5 shadow-xs cursor-pointer active:scale-95 transition-all shrink-0 whitespace-nowrap"
                       title="<?= htmlspecialchars($t['cta']) ?>">
                        <span class="inline-block"><?= htmlspecialchars($t['cta']) ?></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 shrink-0"></i>
                    </a>

                    <!-- Mobile Hamburger Button -->
                    <button type="button" onclick="openMobileDrawer()" 
                            class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition focus:outline-none cursor-pointer shrink-0"
                            aria-label="Open Navigation Menu">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Off-Canvas Drawer (Slides in from the Side) -->
    <div id="mobileDrawerBackdrop" onclick="closeMobileDrawer()" 
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 transition-opacity duration-300 opacity-0 pointer-events-none md:hidden"></div>

    <aside id="mobileDrawer" 
           class="fixed inset-y-0 right-0 w-[290px] max-w-[85vw] bg-white z-50 shadow-2xl flex flex-col justify-between transform translate-x-full transition-transform duration-300 ease-in-out border-l border-slate-200/90 md:hidden">
        
        <!-- Drawer Header -->
        <div class="h-16 px-5 border-b border-slate-100 flex items-center justify-between">
            <a href="./" onclick="closeMobileDrawer()" class="flex items-center">
                <img src="assets/images/logo.png" alt="Ponnostack" width="130" height="38" class="h-7 w-auto object-contain">
            </a>
            <button type="button" onclick="closeMobileDrawer()" 
                    class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer"
                    aria-label="Close Navigation Menu">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Drawer Links & Language -->
        <div class="flex-1 overflow-y-auto px-5 py-6 space-y-5">
            <!-- Nav Links -->
            <nav class="space-y-1.5 text-xs font-bold text-slate-700">
                <a href="./" onclick="closeMobileDrawer()" 
                   class="flex items-center space-x-3 px-3.5 py-3 rounded-xl hover:bg-slate-50 hover:text-[#00684A] transition">
                    <i data-lucide="home" class="w-4 h-4 text-slate-400"></i>
                    <span><?= htmlspecialchars($t['home']) ?></span>
                </a>
                <a href="#pricing" onclick="closeMobileDrawer()" 
                   class="flex items-center space-x-3 px-3.5 py-3 rounded-xl hover:bg-slate-50 hover:text-[#00684A] transition">
                    <i data-lucide="tag" class="w-4 h-4 text-slate-400"></i>
                    <span><?= htmlspecialchars($t['pricing']) ?></span>
                </a>
                <a href="#features" onclick="closeMobileDrawer()" 
                   class="flex items-center space-x-3 px-3.5 py-3 rounded-xl hover:bg-slate-50 hover:text-[#00684A] transition">
                    <i data-lucide="sparkles" class="w-4 h-4 text-slate-400"></i>
                    <span><?= htmlspecialchars($t['features']) ?></span>
                </a>
                <a href="#contact" onclick="closeMobileDrawer()" 
                   class="flex items-center space-x-3 px-3.5 py-3 rounded-xl hover:bg-slate-50 hover:text-[#00684A] transition">
                    <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                    <span><?= htmlspecialchars($t['contact']) ?></span>
                </a>
            </nav>

            <!-- Language Switcher in Drawer -->
            <div class="pt-4 border-t border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2 px-1">ভাষা / Language</span>
                <div class="flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200/80 text-xs font-bold">
                    <a href="?lang=bn" class="w-1/2 text-center py-1.5 rounded-lg transition <?= $lang === 'bn' ? 'bg-white text-[#00684A] shadow-2xs font-black' : 'text-slate-500' ?>">
                        বাংলা
                    </a>
                    <a href="?lang=en" class="w-1/2 text-center py-1.5 rounded-lg transition <?= $lang === 'en' ? 'bg-white text-[#00684A] shadow-2xs font-black' : 'text-slate-500' ?>">
                        English
                    </a>
                </div>
            </div>
        </div>

        <!-- Drawer Footer Actions -->
        <div class="p-5 border-t border-slate-100 bg-slate-50/50">
            <a href="#pricing" onclick="closeMobileDrawer()" 
               class="block w-full py-3 text-center btn-cta text-white text-xs font-bold rounded-xl shadow-md">
                <?= htmlspecialchars($t['cta']) ?>
            </a>
        </div>
    </aside>

    <script>
        // 0. High-End Preloader Dismissal
        (function() {
            const preloader = document.getElementById('pagePreloader');
            if (!preloader) return;

            const hidePreloader = () => {
                if (preloader.dataset.dismissed === 'true') return;
                preloader.dataset.dismissed = 'true';
                preloader.style.opacity = '0';
                preloader.style.pointerEvents = 'none';
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 450);
            };

            if (document.readyState === 'complete') {
                setTimeout(hidePreloader, 300);
            } else {
                window.addEventListener('load', () => {
                    setTimeout(hidePreloader, 300);
                });
            }

            // Failsafe timeout: never keep user waiting more than 2 seconds
            setTimeout(hidePreloader, 2000);
        })();

        // 1. Dynamic Sticky Scroll Effect & Shadow Elevation
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.getElementById('mainHeader');
            const navContainer = document.getElementById('navContainer');

            const onScroll = () => {
                if (window.scrollY > 15) {
                    header.classList.add('header-scrolled');
                    header.classList.remove('header-top');
                    if (navContainer) {
                        navContainer.classList.remove('sm:h-20');
                        navContainer.classList.add('sm:h-16');
                    }
                } else {
                    header.classList.add('header-top');
                    header.classList.remove('header-scrolled');
                    if (navContainer) {
                        navContainer.classList.remove('sm:h-16');
                        navContainer.classList.add('sm:h-20');
                    }
                }
            };

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll(); // initial check
        });

        // 2. Mobile Off-Canvas Drawer Handlers (Slide-in from the Side)
        function openMobileDrawer() {
            const drawer = document.getElementById('mobileDrawer');
            const backdrop = document.getElementById('mobileDrawerBackdrop');
            if (drawer && backdrop) {
                backdrop.classList.remove('pointer-events-none');
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');

                drawer.classList.remove('translate-x-full');
                drawer.classList.add('translate-x-0');

                document.body.style.overflow = 'hidden';
            }
            if (window.lucide) lucide.createIcons();
        }

        function closeMobileDrawer() {
            const drawer = document.getElementById('mobileDrawer');
            const backdrop = document.getElementById('mobileDrawerBackdrop');
            if (drawer && backdrop) {
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
                backdrop.classList.add('pointer-events-none');

                drawer.classList.remove('translate-x-0');
                drawer.classList.add('translate-x-full');

                document.body.style.overflow = '';
            }
        }
    </script>
