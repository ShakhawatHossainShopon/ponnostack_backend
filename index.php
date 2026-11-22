<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Landing Page Entry Point
 * Dribbble-Style High-End Hero Section:
 * - MongoDB Green Brand Theme (#00684A Forest / #00ED64 Spring)
 * - Buttery Smooth Floating Physics & Ambient Mesh Graphics (GSAP + Compositor CSS)
 * - Refined Balanced Typography (Not oversized)
 * - The 2 Requested Buttons: 'View Demo' & 'Pricing'
 * - Clean Modern Platform Showcase (No bKash/payment cards, no fake claims)
 */

require_once __DIR__ . '/includes/header.php';

// Bilingual Dictionary
$heroI18n = [
    'bn' => [
        'badge'        => 'নেক্সট-জেনারেশন কমার্স প্ল্যাটফর্ম',
        'headline_p1'  => 'অনলাইন ব্যবসার জন্য তৈরি',
        'headline_col' => 'স্মার্ট ও শক্তিশালী',
        'headline_p2'  => 'ই-কমার্স ইকোসিস্টেম',
        'subtitle'     => 'প্রোডাক্ট ক্যাটালগ ম্যানেজমেন্ট, স্মুথ চেকআউট এবং ব্র্যান্ডেড অনলাইন শপ তৈরি করুন একক প্ল্যাটফর্ম থেকে।',
        'btn_demo'     => 'ডেমো দেখুন',
        'btn_pricing'  => 'প্রাইসিং প্ল্যান',
        'tag_speed'       => 'সুপারফাস্ট স্পিড (<০.৮ সে.)',
        'tag_mobile'      => '১০০% রেসপন্সিভ থিম',
        'tag_catalog'     => 'স্মার্ট ক্যাটালগ বিল্ডার',
        'metric_catalog'  => 'প্রোডাক্ট ক্যাটালগ',
        'val_catalog'     => 'আনলিমিটেড প্রোডাক্ট',
        'metric_speed'    => 'পেজ লোডিং স্পিড',
        'val_speed'       => '০.৬৪ সেকেন্ড',
        'metric_uptime'   => 'ক্লাউড সিস্টেম আপটাইম',
        'val_uptime'      => '৯৯.৯৮%',
        'prod_title'      => 'প্রিমিয়াম ব্ল্যাক স্নিকার্স',
        'prod_cat'        => 'লাইফস্টাইল কালেকশন',
        'prod_price'      => '৳২,৪৫০',
        'prod_stock'      => 'ইন স্টক (In Stock)',
        'prod_btn'        => 'অর্ডার প্রিভিউ',
        'live_preview'    => 'storefront.ponnostack.com',
    ],
    'en' => [
        'badge'           => 'Next-Generation Commerce Platform',
        'headline_p1'     => 'The All-in-One Operating System for',
        'headline_col'    => 'Modern Online Commerce',
        'headline_p2'     => '',
        'subtitle'        => 'Effortlessly manage product catalogs, deliver seamless checkout, and scale your brand storefront from a unified dashboard.',
        'btn_demo'        => 'View Demo',
        'btn_pricing'     => 'Pricing Plans',
        'tag_speed'       => 'Blazing Speed (<0.8s)',
        'tag_mobile'      => '100% Responsive Theme',
        'tag_catalog'     => 'Smart Catalog Builder',
        'metric_catalog'  => 'Product Catalog',
        'val_catalog'     => 'Unlimited Products',
        'metric_speed'    => 'Page Load Time',
        'val_speed'       => '0.64s',
        'metric_uptime'   => 'Cloud System Uptime',
        'val_uptime'      => '99.98%',
        'prod_title'   => 'Minimalist Black Sneakers',
        'prod_cat'     => 'Lifestyle Collection',
        'prod_price'   => '৳2,450',
        'prod_stock'   => 'In Stock',
        'prod_btn'     => 'Order Preview',
        'live_preview' => 'storefront.ponnostack.com',
    ]
];

$h = $heroI18n[$lang] ?? $heroI18n['bn'];

// ---------------------------------------------------------------------------
// Dynamic Package Pricing & Product Limit Loader (Reads live rates from DB with graceful fallback)
// ---------------------------------------------------------------------------
$pkgData = [
    'starter' => [
        'price'         => 200,
        'product_limit' => 50,
    ],
    'professional' => [
        'price'         => 400,
        'product_limit' => 100,
    ],
    'enterprise' => [
        'price'         => 800,
        'product_limit' => 0,
    ],
];

try {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        if (!class_exists(\App\Core\Env::class)) {
            require_once __DIR__ . '/app/Core/Env.php';
        }
        if (!class_exists(\App\Core\Database::class)) {
            require_once __DIR__ . '/app/Core/Database.php';
        }
        \App\Core\Env::load($envFile);
        $dbConn = \App\Core\Database::getConnection();
        $dbStmt = $dbConn->query("SELECT `slug`, `price`, `product_limit` FROM `packages`");
        $fetchedRows = $dbStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fetchedRows as $row) {
            $slug = (string)($row['slug'] ?? '');
            if (isset($pkgData[$slug])) {
                $pkgData[$slug]['price'] = (int)$row['price'];
                $pkgData[$slug]['product_limit'] = (int)$row['product_limit'];
            }
        }
    }
} catch (\Throwable $e) {
    // Graceful fallback to default values
}

if (!function_exists('toBnDigits')) {
    function toBnDigits(int|string $num): string {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, (string)$num);
    }
}

if (!function_exists('formatProductLimitText')) {
    function formatProductLimitText(int $limit, string $l = 'bn'): string {
        if ($limit === 0) {
            return ($l === 'bn') ? 'আনলিমিটেড প্রোডাক্ট আপলোড' : 'Unlimited Products Upload';
        }
        return ($l === 'bn') 
            ? (toBnDigits($limit) . 'টি প্রোডাক্ট আপলোড') 
            : ($limit . ' Products Upload');
    }
}

$dynPriceStdBn = '৳' . toBnDigits($pkgData['starter']['price']);
$dynPricePrmBn = '৳' . toBnDigits($pkgData['professional']['price']);
$dynPriceGldBn = '৳' . toBnDigits($pkgData['enterprise']['price']);

$dynPriceStdEn = '৳' . $pkgData['starter']['price'];
$dynPricePrmEn = '৳' . $pkgData['professional']['price'];
$dynPriceGldEn = '৳' . $pkgData['enterprise']['price'];

$dynLimitStdBn = formatProductLimitText($pkgData['starter']['product_limit'], 'bn');
$dynLimitPrmBn = formatProductLimitText($pkgData['professional']['product_limit'], 'bn');
$dynLimitGldBn = formatProductLimitText($pkgData['enterprise']['product_limit'], 'bn');

$dynLimitStdEn = formatProductLimitText($pkgData['starter']['product_limit'], 'en');
$dynLimitPrmEn = formatProductLimitText($pkgData['professional']['product_limit'], 'en');
$dynLimitGldEn = formatProductLimitText($pkgData['enterprise']['product_limit'], 'en');

// Bilingual Dictionary - Pricing Section (Standard, Premium, Gold)
$pricingI18n = [
    'bn' => [
        'badge'        => 'স্বচ্ছ ও সাশ্রয়ী প্রাইসিং',
        'title_p1'     => 'আপনার অনলাইন ব্যবসার জন্য',
        'title_col'    => 'স্মার্ট প্রাইসিং প্ল্যান',
        'title_p2'     => 'বেছে নিন',
        'subtitle'     => 'কোনো লুকানো চার্জ নেই। সব প্ল্যানেই কাস্টম ডোমেইন ও ফ্রি SSL অন্তর্ভুক্ত। আপনার প্রোডাক্ট সংখ্যা অনুযায়ী সেরা প্ল্যানটি বেছে নিন।',
        'per_month'    => '/মাস',
        'popular_tag'  => 'সবচেয়ে জনপ্রিয়',
        'best_value'   => 'বেস্ট ভ্যালু ও সেরা চয়েস',
        'growth_tag'   => 'গ্রোথ শপ',
        'scale_tag'    => 'আনলিমিটেড স্কেল',
        'cta_standard' => 'স্ট্যান্ডার্ড দিয়ে শুরু করুন',
        'cta_premium'  => 'প্রিমিয়াম প্ল্যান বেছে নিন',
        'cta_gold'     => 'গোল্ড প্ল্যান বেছে নিন',
        'trust_1'      => 'সব প্যাকেজেই কাস্টম ডোমেইন কানেক্ট সুবিধা',
        'trust_2'      => 'ফ্রি SSL ও ইনস্ট্যান্ট স্টোর অ্যাক্টিভেশন',
        'trust_3'      => 'যেকোনো সময় সহজে প্ল্যান আপগ্রেড',

        // Standard Plan (Highlighted / Most Popular)
        'plan_std_title'  => 'স্ট্যান্ডার্ড',
        'plan_std_desc'   => 'নতুন উদ্যোক্তা ও ছোট অনলাইন ব্যবসার জন্য সবচেয়ে জনপ্রিয় ও সাশ্রয়ী সমাধান।',
        'plan_std_price'  => $dynPriceStdBn,
        'plan_std_f1'     => $dynLimitStdBn,
        'plan_std_f2'     => '১৫টি ক্যাটাগরি তৈরি',
        'plan_std_f3'     => 'কাস্টম ডোমেইন ও সাবডোমেইন যুক্ত করার সুবিধা',
        'plan_std_f4'     => 'সরাসরি অনলাইন কাস্টমার অর্ডার ও চেকআউট',
        'plan_std_f5'     => 'ফ্রি SSL সিকিউরিটি সার্টিফিকেট',
        'plan_std_f6'     => 'মোবাইল ও পিসি ১০০% রেসপন্সিভ স্টোরফ্রন্ট',
        'plan_std_f7'     => 'সহজ স্টোর ম্যানেজমেন্ট ড্যাশবোর্ড ও সাপোর্ট',

        // Premium Plan
        'plan_prm_title'  => 'প্রিমিয়াম',
        'plan_prm_desc'   => 'মাঝারি ক্যাটালগ ও নিয়মিত অর্ডার পাওয়ার অনলাইন শপের জন্য উপযুক্ত।',
        'plan_prm_price'  => $dynPricePrmBn,
        'plan_prm_f1'     => $dynLimitPrmBn,
        'plan_prm_f2'     => '৩০টি ক্যাটাগরি তৈরি',
        'plan_prm_f3'     => 'কাস্টম ডোমেইন ও সাবডোমেইন যুক্ত করার সুবিধা',
        'plan_prm_f4'     => 'সরাসরি অনলাইন কাস্টমার অর্ডার ও চেকআউট',
        'plan_prm_f5'     => 'ফ্রি SSL সিকিউরিটি সার্টিফিকেট',
        'plan_prm_f6'     => 'কুপন ও ডিসকাউন্ট ভাউচার কোড ইঞ্জিন',
        'plan_prm_f7'     => 'দ্রুত টেকনিক্যাল সাপোর্ট অ্যাসিস্ট্যান্স',

        // Gold Plan
        'plan_gld_title'  => 'গোল্ড',
        'plan_gld_desc'   => 'বড় ক্যাটালগ ও আনলিমিটেড প্রোডাক্ট যুক্ত করার জন্য আলটিমেট প্যাকেজ।',
        'plan_gld_price'  => $dynPriceGldBn,
        'plan_gld_f1'     => $dynLimitGldBn,
        'plan_gld_f2'     => 'আনলিমিটেড ক্যাটাগরি ম্যানেজমেন্ট',
        'plan_gld_f3'     => 'কাস্টম ডোমেইন ও সাবডোমেইন যুক্ত করার সুবিধা',
        'plan_gld_f4'     => 'সরাসরি অনলাইন কাস্টমার অর্ডার ও চেকআউট',
        'plan_gld_f5'     => 'ফ্রি SSL সিকিউরিটি সার্টিফিকেট',
        'plan_gld_f6'     => 'কুপন কোড ও সেলস অ্যানালিটিক্স রিপোর্ট',
        'plan_gld_f7'     => '২৪/৭ প্রায়োরিটি কাস্টমার সাপোর্ট',
    ],
    'en' => [
        'badge'        => 'Transparent & Predictable Pricing',
        'title_p1'     => 'Choose the Perfect Plan for',
        'title_col'    => 'Your Commerce Growth',
        'title_p2'     => '',
        'subtitle'     => 'Zero hidden fees. Custom domain & free SSL included in all tiers. Pick your product capacity and start selling.',
        'per_month'    => '/mo',
        'popular_tag'  => 'Most Popular',
        'best_value'   => 'Best Value & Top Choice',
        'growth_tag'   => 'Growth Store',
        'scale_tag'    => 'Unlimited Scale',
        'cta_standard' => 'Get Started with Standard',
        'cta_premium'  => 'Choose Premium Plan',
        'cta_gold'     => 'Choose Gold Plan',
        'trust_1'      => 'Custom Domain & Subdomain on All Plans',
        'trust_2'      => 'Free SSL & Instant Store Activation',
        'trust_3'      => 'Upgrade or Switch Plans Anytime',

        // Standard Plan - HIGHLIGHTED / MOST POPULAR
        'plan_std_title'  => 'Standard',
        'plan_std_desc'   => 'The most popular and affordable choice for new boutiques and growing stores.',
        'plan_std_price'  => $dynPriceStdEn,
        'plan_std_f1'     => $dynLimitStdEn,
        'plan_std_f2'     => '15 Categories Creation',
        'plan_std_f3'     => 'Custom Domain & Subdomain Support',
        'plan_std_f4'     => 'Direct Online Customer Orders & Checkout',
        'plan_std_f5'     => 'Free SSL Security Certificate',
        'plan_std_f6'     => '100% Mobile & PC Responsive Storefront',
        'plan_std_f7'     => 'Easy Store Management Dashboard & Support',

        // Premium Plan
        'plan_prm_title'  => 'Premium',
        'plan_prm_desc'   => 'Perfect for stores with regular sales and medium inventory catalogs.',
        'plan_prm_price'  => $dynPricePrmEn,
        'plan_prm_f1'     => $dynLimitPrmEn,
        'plan_prm_f2'     => '30 Categories Creation',
        'plan_prm_f3'     => 'Custom Domain & Subdomain Support',
        'plan_prm_f4'     => 'Direct Online Customer Orders & Checkout',
        'plan_prm_f5'     => 'Free SSL Security Certificate',
        'plan_prm_f6'     => 'Coupon & Promotional Discount Codes',
        'plan_prm_f7'     => 'Fast Technical Assistance & Support',

        // Gold Plan
        'plan_gld_title'  => 'Gold',
        'plan_gld_desc'   => 'Ultimate solution for large inventories with unlimited product scalability.',
        'plan_gld_price'  => $dynPriceGldEn,
        'plan_gld_f1'     => $dynLimitGldEn,
        'plan_gld_f2'     => 'Unlimited Categories Management',
        'plan_gld_f3'     => 'Custom Domain & Subdomain Support',
        'plan_gld_f4'     => 'Direct Online Customer Orders & Checkout',
        'plan_gld_f5'     => 'Free SSL Security Certificate',
        'plan_gld_f6'     => 'Coupon Codes & Sales Analytics Reports',
        'plan_gld_f7'     => '24/7 Priority Customer Support',
    ]
];

$p = $pricingI18n[$lang] ?? $pricingI18n['bn'];

// Bilingual Dictionary - Sections (Demo, Features, Contact)
$sectionsI18n = [
    'bn' => [
        // Live Demo Section
        'demo_badge'       => 'লাইভ এক্সপেরিয়েন্স',
        'demo_title_p1'    => 'এক নজরে দেখুন',
        'demo_title_col'   => 'লাইভ প্ল্যাটফর্ম ডেমো',
        'demo_title_p2'    => '',
        'demo_subtitle'    => 'কীভাবে আপনার অনলাইন স্টোর চলবে এবং কাস্টমাররা সহজে অর্ডার করবে—সরাসরি ইন্টারফেসে দেখুন।',
        'demo_tab_store'   => 'কাস্টমার স্টোরফ্রন্ট প্রিভিউ',
        'demo_tab_admin'   => 'মার্চেন্ট ড্যাশবোর্ড ওভারভিউ',
        'demo_preview_url' => 'storefront.ponnostack.com/preview',
        'demo_card_title'  => 'প্রিমিয়াম ব্ল্যাক স্নিকার্স',
        'demo_card_price'  => '৳২,৪৫০',
        'demo_card_cat'    => 'লাইফস্টাইল ফুটওয়্যার',
        'demo_btn_order'   => 'সরাসরি অর্ডার প্রিভিউ',
        'demo_cta_title'   => 'আপনার নিজস্ব ব্র্যান্ডেড স্টোর শুরু করতে প্রস্তুত?',
        'demo_cta_btn'     => 'প্রাইসিং প্ল্যান দেখুন',

        // Features Section
        'feat_badge'       => 'কোর আর্কিটেকচার',
        'feat_title_p1'    => 'ব্যবসা বড় করতে তৈরি',
        'feat_title_col'   => 'শক্তিশালী প্ল্যাটফর্ম ফিচার',
        'feat_title_p2'    => '',
        'feat_subtitle'    => 'কোনো জটিল কোডিং ছাড়াই সম্পূর্ণ আধুনিক, দ্রুত ও স্কেলেবল ই-কমার্স আর্কিটেকচার।',
        
        'feat1_title'      => 'নো-কোড ক্যাটালগ বিল্ডার',
        'feat1_desc'       => 'সহজে আনলিমিটেড প্রোডাক্ট ও ক্যাটাগরি তৈরি করুন। ছবি, দাম এবং স্টক নিমিষেই আপডেট করুন।',
        
        'feat2_title'      => 'কাস্টম ডোমেইন ও ফ্রি SSL',
        'feat2_desc'       => 'যেকোনো প্যাকেজেই নিজস্ব ব্র্যান্ড ডোমেইন যুক্ত করার সুবিধা ও ফ্রি স্বয়ংক্রিয় SSL সিকিউরিটি।',
        
        'feat3_title'      => 'আল্ট্রাফাস্ট পেজ স্পিড',
        'feat3_desc'       => 'ক্যাশ-অপ্টিমাইজড আর্কিটেকচারের মাধ্যমে ০.৮ সেকেন্ডেরও কম সময়ে স্মুথলি পেজ লোড নিশ্চিত করে।',
        
        'feat4_title'      => '১০০% মোবাইল রেসপন্সিভ',
        'feat4_desc'       => 'স্মার্টফোন, ট্যাবলেট এবং পিসি—সব ডিভাইসের স্ক্রিনে নিখুঁত ও আরামদায়ক শপিং অভিজ্ঞতা।',
        
        'feat5_title'      => 'সরাসরি অর্ডার ও স্টক ট্র্যাকিং',
        'feat5_desc'       => 'কাস্টমারদের ঝামেলাহীন চেকআউট এবং মার্চেন্টদের জন্য রিয়েল-টাইম অর্ডার ট্র্যাকিং ও স্টক নিয়ন্ত্রণ।',
        
        'feat6_title'      => 'নিরাপদ ক্লাউড ইনফ্রাস্ট্রাকচার',
        'feat6_desc'       => 'উচ্চ নিরাপত্তা ব্যবস্থা এবং ৯৯.৯% আপটাইম সহ আপনার অনলাইন ব্যবসা সবসময় থাকে লাইভ ও সচল।',
        'feat1_tag'        => 'সহজ ম্যানেজমেন্ট',
        'feat2_tag'        => 'সব প্ল্যানে অন্তর্ভুক্ত',
        'feat3_tag'        => '০.৮ সেকেন্ডে লোডিং',
        'feat4_tag'        => 'স্মার্টফোন অপ্টিমাইজড',
        'feat5_tag'        => 'রিয়েল-টাইম স্টক আপডেট',
        'feat6_tag'        => '৯৯.৯% আপটাইম গ্যারান্টি',

        // Contact Section
        'contact_badge'    => 'সরাসরি সহায়তা',
        'contact_title_p1' => 'যেকোনো প্রয়োজনে আমাদের সাথে',
        'contact_title_col'=> 'সরাসরি যোগাযোগ করুন',
        'contact_title_p2' => '',
        'contact_subtitle' => 'স্টোর সেটআপ বা প্ল্যাটফর্মের যেকোনো সহায়তায় আমাদের সাপোর্ট টিম সবসময় পাশে আছে।',
        'contact_phone'    => 'হটলাইন নম্বর',
        'contact_phone_val'=> '01642956206',
        'contact_email'    => 'অফিসিয়াল ইমেইল',
        'contact_email_val'=> 'support@ponnostack.shop',
        'contact_loc'      => 'অফিস লোকেশন',
        'contact_loc_val'  => 'ঢাকা, বাংলাদেশ',
        'contact_hours'    => 'সাপোর্ট সময়',
        'contact_hours_val'=> 'সকাল ৯:০০ টা - রাত ১০:০০ টা (প্রতিদিন)',
        'contact_wa_btn'   => 'সরাসরি হোয়াটসঅ্যাপে কথা বলুন',
        'contact_form_title'=> 'একটি সংক্ষিপ্ত বার্তা পাঠান',
        'contact_name'     => 'আপনার নাম',
        'contact_ph'       => 'ফোন নম্বর',
        'contact_msg'      => 'আপনার মেসেজ বা রিকোয়ারমেন্ট',
        'contact_btn'      => 'মেসেজ পাঠান',
        'contact_guarantee'=> 'আমরা সাধারণত অল্প সময়ের মধ্যে ফিরতি যোগাযোগ করি। কোনো স্প্যাম নেই।',
    ],
    'en' => [
        // Live Demo Section
        'demo_badge'       => 'Interactive Experience',
        'demo_title_p1'    => 'Explore the',
        'demo_title_col'   => 'Live Platform Demo',
        'demo_title_p2'    => 'in Real Time',
        'demo_subtitle'    => 'See how effortless storefront browsing and direct customer checkouts function on Ponnostack.',
        'demo_tab_store'   => 'Customer Storefront Preview',
        'demo_tab_admin'   => 'Merchant Dashboard Overview',
        'demo_preview_url' => 'storefront.ponnostack.com/preview',
        'demo_card_title'  => 'Minimalist Black Sneakers',
        'demo_card_price'  => '৳2,450',
        'demo_card_cat'    => 'Lifestyle Footwear',
        'demo_btn_order'   => 'Instant Order Preview',
        'demo_cta_title'   => 'Ready to launch your branded online storefront?',
        'demo_cta_btn'     => 'Explore Pricing Plans',

        // Features Section
        'feat_badge'       => 'Core Architecture',
        'feat_title_p1'    => 'Built for Commerce Growth with',
        'feat_title_col'   => 'Robust Platform Features',
        'feat_title_p2'    => '',
        'feat_subtitle'    => 'A fully modern and scalable e-commerce infrastructure without any technical complexity.',
        
        'feat1_title'      => 'No-Code Catalog Builder',
        'feat1_desc'       => 'Effortlessly curate products and categories with instant pricing and inventory updates.',
        
        'feat2_title'      => 'Custom Domain & Free SSL',
        'feat2_desc'       => 'Connect your unique brand domain or free subdomain with automated SSL security certificates.',
        
        'feat3_title'      => 'Ultra-Fast Cloud Speed',
        'feat3_desc'       => 'Optimized cache architecture ensures page load times under 0.8 seconds for high conversion.',
        
        'feat4_title'      => '100% Mobile First Design',
        'feat4_desc'       => 'Responsive fluid interface tailored for seamless shopping across smartphones and desktops.',
        
        'feat5_title'      => 'Direct Order & Stock Control',
        'feat5_desc'       => 'Frictionless checkout experience with real-time stock deductions and instant order tracking.',
        
        'feat6_title'      => 'Cloud Infrastructure & Security',
        'feat6_desc'       => 'Enterprise-grade protection with 99.9% uptime to keep your commerce operations continuously live.',
        'feat1_tag'        => 'Easy Management',
        'feat2_tag'        => 'Included in All Plans',
        'feat3_tag'        => 'Under 0.8s Page Load',
        'feat4_tag'        => 'Mobile-First Design',
        'feat5_tag'        => 'Real-Time Inventory',
        'feat6_tag'        => '99.9% Cloud Uptime',

        // Contact Section
        'contact_badge'    => 'Direct Support',
        'contact_title_p1' => 'Reach Out Directly to',
        'contact_title_col'=> 'Our Support Team',
        'contact_title_p2' => '',
        'contact_subtitle' => 'Our team is ready to assist with store onboarding, domain setup, or technical guidance.',
        'contact_phone'    => 'Direct Hotline',
        'contact_phone_val'=> '01642956206',
        'contact_email'    => 'Official Support',
        'contact_email_val'=> 'support@ponnostack.shop',
        'contact_loc'      => 'Office Location',
        'contact_loc_val'  => 'Dhaka, Bangladesh',
        'contact_hours'    => 'Operating Hours',
        'contact_hours_val'=> '9:00 AM - 10:00 PM (Daily)',
        'contact_wa_btn'   => 'Chat via WhatsApp',
        'contact_form_title'=> 'Send a Quick Message',
        'contact_name'     => 'Your Name',
        'contact_ph'       => 'Phone Number',
        'contact_msg'      => 'Your Message or Store Requirements',
        'contact_btn'      => 'Send Inquiry',
        'contact_guarantee'=> 'We typically respond promptly. Zero spam guaranteed.',
    ]
];

$s = $sectionsI18n[$lang] ?? $sectionsI18n['bn'];
?>

<style>
    /* Dribbble Style Background Tech Grid with Radial Fade Mask - MongoDB Green Tint */
    .hero-grid-pattern {
        background-size: 36px 36px;
        background-image: 
            linear-gradient(to right, rgba(0, 104, 74, 0.06) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(0, 104, 74, 0.06) 1px, transparent 1px);
        mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 40%, transparent 100%);
        -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 40%, transparent 100%);
    }

    /* Vibrant Glowing Orbs with Depth Blur - Official MongoDB Palette */
    .glow-orb-primary {
        background: radial-gradient(circle, rgba(0, 237, 100, 0.28) 0%, rgba(0, 104, 74, 0.14) 50%, transparent 70%);
        filter: blur(65px);
    }
    .glow-orb-forest {
        background: radial-gradient(circle, rgba(0, 104, 74, 0.32) 0%, rgba(2, 52, 36, 0.16) 50%, transparent 70%);
        filter: blur(65px);
    }
    .glow-orb-mint {
        background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, rgba(0, 237, 100, 0.08) 50%, transparent 70%);
        filter: blur(60px);
    }

    /* Dribbble Glassmorphism Card Style with MongoDB Green Ambient Shadow */
    .dribbble-card {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 20px 60px -15px rgba(0, 104, 74, 0.12), 0 8px 24px -8px rgba(0, 30, 43, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.95);
    }

    /* 
     * Silky Smooth Hardware-Accelerated Floating Physics
     * Uses cubic-bezier sine curve & runs on the GPU compositor thread
     * Decoupled from entrance animation to eliminate any jumping or jitter
     */
    @keyframes smoothFloat1 {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-8px);
        }
    }

    @keyframes smoothFloat2 {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(8px);
        }
    }

    .animate-float-badge-1 {
        animation: smoothFloat1 5.5s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite;
        will-change: transform;
    }

    .animate-float-badge-2 {
        animation: smoothFloat2 6.2s cubic-bezier(0.45, 0.05, 0.55, 0.95) 0.8s infinite;
        will-change: transform;
    }

    /* Dribbble / Behance Pricing Card Micro-Interactions */
    .pricing-card {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), 
                    box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1), 
                    border-color 0.3s ease;
    }

    .pricing-card-popular {
        box-shadow: 0 20px 50px -10px rgba(0, 104, 74, 0.16), 0 8px 24px -6px rgba(0, 30, 43, 0.06);
    }
    .pricing-card-popular:hover {
        box-shadow: 0 30px 65px -12px rgba(0, 104, 74, 0.24), 0 12px 30px -6px rgba(0, 30, 43, 0.08);
    }

    /* 
     * Hero Ambient Particle Icons (Fade & Float Particle System)
     * Mimics modern Particle JS ambient drift without heavy JS overhead
     * Smooth GPU compositor keyframes with staggered durations and delays
     */
    @keyframes particleFadeDrift1 {
        0% {
            opacity: 0;
            transform: translate3d(0, 12px, 0) scale(0.85);
        }
        25% {
            opacity: 0.75;
            transform: translate3d(8px, -6px, 0) scale(1);
        }
        50% {
            opacity: 0.95;
            transform: translate3d(14px, -14px, 0) scale(1.05);
        }
        75% {
            opacity: 0.5;
            transform: translate3d(6px, -8px, 0) scale(0.95);
        }
        100% {
            opacity: 0;
            transform: translate3d(0, 12px, 0) scale(0.85);
        }
    }

    @keyframes particleFadeDrift2 {
        0% {
            opacity: 0;
            transform: translate3d(0, -12px, 0) scale(0.9);
        }
        30% {
            opacity: 0.85;
            transform: translate3d(-10px, 4px, 0) scale(1.08);
        }
        60% {
            opacity: 0.4;
            transform: translate3d(-16px, 14px, 0) scale(1);
        }
        85% {
            opacity: 0.8;
            transform: translate3d(-8px, 6px, 0) scale(1.04);
        }
        100% {
            opacity: 0;
            transform: translate3d(0, -12px, 0) scale(0.9);
        }
    }

    @keyframes particleFadeDrift3 {
        0% {
            opacity: 0;
            transform: translate3d(0, 0, 0) scale(0.8);
        }
        35% {
            opacity: 0.9;
            transform: translate3d(12px, -15px, 0) scale(1.06);
        }
        70% {
            opacity: 0.35;
            transform: translate3d(-8px, -8px, 0) scale(0.92);
        }
        100% {
            opacity: 0;
            transform: translate3d(0, 0, 0) scale(0.8);
        }
    }

    .hero-particle-1 {
        animation: particleFadeDrift1 6.8s ease-in-out infinite;
        will-change: transform, opacity;
    }
    .hero-particle-2 {
        animation: particleFadeDrift2 8.4s ease-in-out 0.8s infinite;
        will-change: transform, opacity;
    }
    .hero-particle-3 {
        animation: particleFadeDrift3 6.2s ease-in-out 1.6s infinite;
        will-change: transform, opacity;
    }
    .hero-particle-4 {
        animation: particleFadeDrift1 8.8s ease-in-out 2.4s infinite;
        will-change: transform, opacity;
    }
    .hero-particle-5 {
        animation: particleFadeDrift2 7.6s ease-in-out 3.2s infinite;
        will-change: transform, opacity;
    }
    .hero-particle-6 {
        animation: particleFadeDrift3 8.0s ease-in-out 4.0s infinite;
        will-change: transform, opacity;
    }

    /* Ensure All Feature Cards Always Render with 100% Solid Full Opacity */
    .feat-card {
        opacity: 1 !important;
        will-change: auto;
    }
</style>

<!-- Main Content Area -->
<main class="flex-1 overflow-x-hidden">
    <!-- ============================================================ -->
    <!-- DRIBBLE-STYLE HERO SECTION WITH VIBRANT GSAP ANIMATED MESH   -->
    <!-- ============================================================ -->
    <section class="relative pt-8 pb-10 sm:pt-12 sm:pb-14 lg:pt-14 lg:pb-16 bg-slate-50/40 overflow-hidden">
        
        <!-- 1. Rich Dribbble Graphics: Animated MongoDB Green Ambient Orbs (GSAP Driven) -->
        <div id="orbGreenPrimary" class="absolute top-10 left-1/4 w-[430px] h-[430px] rounded-full glow-orb-primary pointer-events-none -z-10"></div>
        <div id="orbGreenForest"  class="absolute top-20 right-1/4 w-[460px] h-[460px] rounded-full glow-orb-forest pointer-events-none -z-10"></div>
        <div id="orbMint"         class="absolute top-48 left-1/2 -translate-x-1/2 w-[530px] h-[390px] rounded-full glow-orb-mint pointer-events-none -z-10"></div>

        <!-- 2. Rich Dribbble Graphics: MongoDB Green Tech Grid Overlay -->
        <div class="absolute inset-0 w-full h-full hero-grid-pattern pointer-events-none -z-10"></div>

        <!-- 3. Ambient Floating & Fading Particle Icons (Rich Modern Atmosphere) -->
        <!-- Floating Micro-Pill 1: Storefront (Top Left) -->
        <div class="hero-particle-1 hidden md:flex absolute top-[10%] left-[5%] xl:left-[8%] items-center space-x-1.5 px-3 py-1.5 rounded-2xl bg-white/80 border border-emerald-100 shadow-md shadow-[#00684A]/5 backdrop-blur-md pointer-events-none -z-10 text-[#00684A]">
            <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
            <span class="text-[10px] font-bold text-slate-700">Storefront</span>
        </div>

        <!-- Floating Micro-Pill 2: Blazing Fast (Top Right) -->
        <div class="hero-particle-2 hidden md:flex absolute top-[12%] right-[6%] xl:right-[9%] items-center space-x-1.5 px-3 py-1.5 rounded-2xl bg-white/80 border border-emerald-100 shadow-md shadow-[#00684A]/5 backdrop-blur-md pointer-events-none -z-10 text-[#00684A]">
            <i data-lucide="zap" class="w-3.5 h-3.5 fill-[#00ED64]/30 text-[#00684A]"></i>
            <span class="text-[10px] font-bold text-slate-700">Fast Speed</span>
        </div>

        <!-- Floating Micro-Card 3: Price Tag (Mid Left) -->
        <div class="hero-particle-3 hidden sm:flex absolute top-[36%] left-[3%] xl:left-[6%] w-10 h-10 rounded-2xl bg-white/85 border border-emerald-100 shadow-lg shadow-[#00684A]/8 items-center justify-center text-[#00684A] backdrop-blur-md pointer-events-none -z-10">
            <i data-lucide="tag" class="w-4 h-4"></i>
        </div>

        <!-- Floating Micro-Card 4: Security Shield (Mid Right) -->
        <div class="hero-particle-4 hidden sm:flex absolute top-[34%] right-[3%] xl:right-[6%] w-10 h-10 rounded-2xl bg-white/85 border border-emerald-100 shadow-lg shadow-[#00684A]/8 items-center justify-center text-[#00684A] backdrop-blur-md pointer-events-none -z-10">
            <i data-lucide="shield-check" class="w-4 h-4 text-[#00684A]"></i>
        </div>

        <!-- Floating Micro-Pill 5: No-Code (Lower Left) -->
        <div class="hero-particle-5 hidden lg:flex absolute top-[64%] left-[4%] xl:left-[7%] items-center space-x-1.5 px-3 py-1.5 rounded-2xl bg-white/80 border border-emerald-100 shadow-md shadow-[#00684A]/5 backdrop-blur-md pointer-events-none -z-10 text-[#00684A]">
            <i data-lucide="layers" class="w-3.5 h-3.5 text-[#00684A]"></i>
            <span class="text-[10px] font-bold text-slate-700">No-Code</span>
        </div>

        <!-- Floating Micro-Pill 6: Analytics & Growth (Lower Right) -->
        <div class="hero-particle-6 hidden lg:flex absolute top-[66%] right-[4%] xl:right-[7%] items-center space-x-1.5 px-3 py-1.5 rounded-2xl bg-white/80 border border-emerald-100 shadow-md shadow-[#00684A]/5 backdrop-blur-md pointer-events-none -z-10 text-[#00684A]">
            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-[#00684A]"></i>
            <span class="text-[10px] font-bold text-slate-700">Growth</span>
        </div>

        <!-- Ambient Floating Particles / Sparkles in MongoDB Green -->
        <div class="hero-particle-1 absolute top-[20%] left-[22%] text-[#00ED64] pointer-events-none -z-10">
            <i data-lucide="sparkles" class="w-5 h-5"></i>
        </div>
        <div class="hero-particle-2 absolute top-[24%] right-[22%] text-[#00684A] pointer-events-none -z-10">
            <i data-lucide="sparkles" class="w-4 h-4"></i>
        </div>
        <div class="hero-particle-3 absolute bottom-[18%] left-[17%] text-[#00684A] pointer-events-none -z-10">
            <i data-lucide="boxes" class="w-5 h-5"></i>
        </div>
        <div class="hero-particle-4 absolute bottom-[15%] right-[18%] text-[#00ED64] pointer-events-none -z-10">
            <i data-lucide="globe" class="w-5 h-5"></i>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            
            <!-- Center Content Stack (Refined Dribbble Typography Scale) -->
            <div class="text-center max-w-2xl mx-auto">
                <!-- Sleek Pill Tag with MongoDB Green Accent -->
                <div class="hero-anim inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-white/90 border border-emerald-200/80 text-[#00684A] text-xs font-bold mb-5 shadow-2xs backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                    <span><?= htmlspecialchars($h['badge']) ?></span>
                </div>

                <!-- Refined Balanced Headline (Not Oversized) -->
                <h1 class="hero-anim text-2xl sm:text-4xl lg:text-[40px] font-black text-slate-900 tracking-tight leading-[1.2]">
                    <?= htmlspecialchars($h['headline_p1']) ?>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00684A] via-[#00875A] to-[#00ED64] block sm:inline">
                        <?= htmlspecialchars($h['headline_col']) ?>
                    </span>
                    <?= htmlspecialchars($h['headline_p2']) ?>
                </h1>

                <!-- Refined Subtitle -->
                <p class="hero-anim mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl mx-auto">
                    <?= htmlspecialchars($h['subtitle']) ?>
                </p>

                <!-- THE TWO BUTTONS: View Demo & Pricing -->
                <div class="hero-anim mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <!-- Primary Button: View Demo -->
                    <a href="#demo" 
                       class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-[#00684A] to-[#005139] hover:brightness-110 active:scale-95 text-white text-xs font-black shadow-lg shadow-[#00684A]/25 flex items-center justify-center space-x-2 cursor-pointer transition-all duration-200">
                        <i data-lucide="play-circle" class="w-4 h-4 text-[#00ED64]"></i>
                        <span><?= htmlspecialchars($h['btn_demo']) ?></span>
                    </a>

                    <!-- Secondary Button: Pricing -->
                    <a href="#pricing" 
                       class="w-full sm:w-auto px-6 py-3 rounded-xl bg-white/95 hover:bg-emerald-50/40 text-slate-700 hover:text-[#00684A] border border-slate-200/90 hover:border-[#00684A]/40 text-xs font-bold shadow-2xs hover:shadow-md transition-all duration-200 flex items-center justify-center space-x-2 cursor-pointer active:scale-95">
                        <i data-lucide="tag" class="w-3.5 h-3.5 text-[#00684A]"></i>
                        <span><?= htmlspecialchars($h['btn_pricing']) ?></span>
                    </a>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- DRIBBLE-STYLE PRODUCT & STOREFRONT SHOWCASE CARD              -->
            <!-- ============================================================ -->
            <div id="showcaseWrapper" class="mt-8 sm:mt-12 max-w-4xl mx-auto relative">
                
                <!-- Floating Glass Tag 1: Speed Badge (Top Left) - Silky Smooth Composited Float -->
                <div id="floatBadge1" class="hidden sm:flex absolute -top-5 -left-4 sm:-left-8 z-20">
                    <div class="animate-float-badge-1 flex items-center space-x-2.5 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-xl border border-emerald-100/90 shadow-xl shadow-[#00684A]/10">
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0">
                            <i data-lucide="zap" class="w-3.5 h-3.5 fill-[#00684A]/20"></i>
                        </span>
                        <span class="text-xs font-bold text-slate-800"><?= htmlspecialchars($h['tag_speed']) ?></span>
                    </div>
                </div>

                <!-- Floating Glass Tag 2: Mobile Badge (Bottom Right) - Silky Smooth Composited Float -->
                <div id="floatBadge2" class="hidden sm:flex absolute -bottom-5 -right-4 sm:-right-8 z-20">
                    <div class="animate-float-badge-2 flex items-center space-x-2.5 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-xl border border-emerald-100/90 shadow-xl shadow-[#00684A]/10">
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                        </span>
                        <span class="text-xs font-bold text-slate-800"><?= htmlspecialchars($h['tag_mobile']) ?></span>
                    </div>
                </div>

                <!-- Main Glass Card Container -->
                <div id="dribbbleMockup" class="dribbble-card rounded-3xl p-2.5 sm:p-5 border border-white overflow-hidden">
                    <div class="rounded-2xl bg-white border border-slate-100 p-3 sm:p-6 shadow-2xs overflow-hidden">
                        
                        <!-- Window Navigation Bar -->
                        <div class="flex items-center justify-between gap-1.5 sm:gap-2 pb-3.5 sm:pb-4 border-b border-slate-100 mb-4 sm:mb-5">
                            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
                                <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-rose-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-[#00ED64] inline-block"></span>
                            </div>
                            <div class="flex items-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-1 rounded-lg bg-slate-50 border border-slate-200/80 text-[10px] sm:text-[11px] font-mono text-slate-500 min-w-0 max-w-[150px] sm:max-w-none">
                                <i data-lucide="lock" class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-[#00684A] shrink-0"></i>
                                <span class="block truncate sm:hidden">ponnostack.shop</span>
                                <span class="hidden sm:inline"><?= htmlspecialchars($h['live_preview']) ?></span>
                            </div>
                            <div class="flex items-center space-x-1 sm:space-x-1.5 text-xs text-slate-400 shrink-0">
                                <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse shrink-0"></span>
                                <span class="text-[9px] sm:text-[10px] font-bold text-slate-500 uppercase">Online</span>
                            </div>
                        </div>

                        <!-- 2-Column Split: Clean Modern Storefront + Live Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                            
                            <!-- Left: Product Storefront Showcase Card (7 cols) -->
                            <div class="md:col-span-7 bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-center gap-4">
                                <!-- Modern Product Thumbnail Mockup -->
                                <div class="w-full sm:w-36 h-36 rounded-xl bg-gradient-to-tr from-emerald-100/40 via-green-50/30 to-white border border-emerald-100/70 flex flex-col items-center justify-center p-3 shrink-0 relative overflow-hidden group">
                                    <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded-md bg-emerald-50 text-[#00684A] border border-emerald-200 text-[9px] font-bold">
                                        <?= htmlspecialchars($h['prod_stock']) ?>
                                    </span>
                                    <i data-lucide="package" class="w-10 h-10 text-[#00684A] group-hover:scale-110 transition-transform"></i>
                                    <span class="text-[10px] font-mono text-slate-400 mt-2">SKU: PN-2026</span>
                                </div>

                                <!-- Product Info & Action -->
                                <div class="w-full space-y-2 text-left">
                                    <span class="text-[10px] font-bold text-[#00684A] uppercase tracking-wider block"><?= htmlspecialchars($h['prod_cat']) ?></span>
                                    <h4 class="text-sm font-black text-slate-900"><?= htmlspecialchars($h['prod_title']) ?></h4>
                                    <div class="flex items-baseline space-x-2">
                                        <span class="text-base font-black text-slate-900"><?= htmlspecialchars($h['prod_price']) ?></span>
                                        <span class="text-xs text-slate-400 line-through">৳২,৯০০</span>
                                        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">-১৫%</span>
                                    </div>
                                    <div class="pt-1">
                                        <button type="button" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#00684A] hover:bg-[#005139] active:scale-95 text-white text-xs font-bold shadow-xs transition flex items-center justify-center space-x-1.5 cursor-pointer">
                                            <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                                            <span><?= htmlspecialchars($h['prod_btn']) ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Platform Capability & Performance Metrics (5 cols) -->
                            <div class="md:col-span-5 space-y-3">
                                <!-- Metric Card 1: Product Catalog (100% Genuine Platform Capability) -->
                                <div class="bg-slate-50/70 border border-slate-200/70 p-3.5 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"><?= htmlspecialchars($h['metric_catalog']) ?></span>
                                        <span class="text-sm font-black text-slate-900 mt-0.5 block"><?= htmlspecialchars($h['val_catalog']) ?></span>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center font-bold shrink-0">
                                        <i data-lucide="layers" class="w-4 h-4"></i>
                                    </div>
                                </div>

                                <!-- Metric Card 2: Speed -->
                                <div class="bg-slate-50/70 border border-slate-200/70 p-3.5 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"><?= htmlspecialchars($h['metric_speed']) ?></span>
                                        <span class="text-sm font-black text-[#00684A] mt-0.5 block font-mono"><?= htmlspecialchars($h['val_speed']) ?></span>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center font-bold shrink-0">
                                        <i data-lucide="gauge" class="w-4 h-4"></i>
                                    </div>
                                </div>

                                <!-- Metric Card 3: Cloud Uptime -->
                                <div class="bg-slate-50/70 border border-slate-200/70 p-3.5 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"><?= htmlspecialchars($h['metric_uptime']) ?></span>
                                        <span class="text-sm font-black text-[#00684A] mt-0.5 block font-mono"><?= htmlspecialchars($h['val_uptime']) ?></span>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center font-bold shrink-0">
                                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 2ND SECTION: HIGH-END DRIBBBLE / BEHANCE PRICING SECTION     -->
    <!-- ============================================================ -->
    <section id="pricing" class="relative pt-8 pb-14 sm:pt-12 sm:pb-18 lg:pt-14 lg:pb-20 bg-slate-50/70 border-t border-b border-slate-200/80 overflow-hidden">
        
        <!-- Subtle Ambient Background Glows in MongoDB Green -->
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-full max-w-[650px] h-[380px] rounded-full bg-emerald-500/10 filter blur-[90px] pointer-events-none -z-10"></div>
        <div class="absolute bottom-0 right-10 w-full max-w-[420px] h-[320px] rounded-full bg-[#00684A]/6 filter blur-[80px] pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            
            <!-- Section Header (Balanced, Crisp Typography) -->
            <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-12">
                <!-- Pill Badge -->
                <div class="pricing-anim-header inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-white border border-emerald-200/90 text-[#00684A] text-xs font-bold mb-4 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                    <span><?= htmlspecialchars($p['badge']) ?></span>
                </div>

                <!-- Headline -->
                <h2 class="pricing-anim-header text-2xl sm:text-4xl lg:text-[38px] font-black text-slate-900 tracking-tight leading-[1.2]">
                    <?= htmlspecialchars($p['title_p1']) ?>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00684A] via-[#00875A] to-[#00ED64] block sm:inline">
                        <?= htmlspecialchars($p['title_col']) ?>
                    </span>
                    <?= htmlspecialchars($p['title_p2']) ?>
                </h2>

                <!-- Subtitle -->
                <p class="pricing-anim-header mt-3 sm:mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl mx-auto">
                    <?= htmlspecialchars($p['subtitle']) ?>
                </p>
            </div>

            <!-- 3-Tier Modern Pricing Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8 items-stretch pt-2 sm:pt-4">
                
                <!-- ============================================================ -->
                <!-- 1. STANDARD PLAN (৳200 / Month) - HIGHLIGHTED / MOST POPULAR -->
                <!-- ============================================================ -->
                <div class="pricing-card bg-white rounded-3xl p-5 sm:p-7 lg:p-8 border-2 border-[#00684A] pricing-card-popular transition-all duration-300 flex flex-col justify-between hover:-translate-y-2 relative mt-4 sm:mt-0 lg:-translate-y-2 z-10 group ring-4 ring-[#00ED64]/15 shadow-lg shadow-[#00684A]/10">
                    
                    <!-- Floating Star Pill Badge (Most Popular) -->
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3.5 sm:px-4 py-1 rounded-full bg-gradient-to-r from-[#00684A] via-[#00875A] to-[#00ED64] text-white text-[10px] sm:text-[11px] font-black uppercase tracking-wider shadow-md shadow-[#00684A]/30 flex items-center space-x-1.5 whitespace-nowrap">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-white"></i>
                        <span><?= htmlspecialchars($p['popular_tag']) ?></span>
                    </div>

                    <div>
                        <!-- Plan Header -->
                        <div class="flex items-center justify-between mb-4 mt-1">
                            <div>
                                <span class="text-xs font-black uppercase tracking-wider text-[#00684A] block"><?= htmlspecialchars($p['best_value']) ?></span>
                                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= htmlspecialchars($p['plan_std_title']) ?></h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200/60 flex items-center justify-center text-[#00684A] shadow-xs">
                                <i data-lucide="zap" class="w-5 h-5 fill-[#00684A]/20"></i>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed sm:min-h-[36px]">
                            <?= htmlspecialchars($p['plan_std_desc']) ?>
                        </p>

                        <!-- Price Tag -->
                        <div class="mt-5 mb-6 pb-5 sm:mt-6 sm:mb-7 sm:pb-6 border-b border-emerald-100/80 flex items-baseline">
                            <span id="price-val-starter" class="text-4xl sm:text-[42px] font-black text-slate-900 tracking-tight font-sans"><?= htmlspecialchars($p['plan_std_price']) ?></span>
                            <span class="text-xs text-slate-400 font-bold ml-1.5"><?= htmlspecialchars($p['per_month']) ?></span>
                        </div>

                        <!-- Core Features List (100% Real Features - All Green Checkmarks) -->
                        <div class="space-y-3 sm:space-y-3.5 mb-6 sm:mb-8">
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span id="prod-limit-starter" class="text-xs text-slate-900 font-black"><?= htmlspecialchars($p['plan_std_f1']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-900 font-black"><?= htmlspecialchars($p['plan_std_f2']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100/80 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_std_f3']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100/80 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_std_f4']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100/80 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_std_f5']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100/80 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_std_f6']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100/80 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_std_f7']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Action Button (Primary MongoDB Green) -->
                    <a href="checkout?package=starter" 
                       class="w-full py-3 sm:py-3.5 px-4 rounded-xl sm:rounded-2xl bg-gradient-to-r from-[#00684A] via-[#00684A] to-[#005139] hover:brightness-110 active:scale-95 text-white text-xs font-black transition-all shadow-lg shadow-[#00684A]/30 flex items-center justify-center space-x-2 cursor-pointer">
                        <span><?= htmlspecialchars($p['cta_standard']) ?></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-[#00ED64]"></i>
                    </a>
                </div>

                <!-- ============================================== -->
                <!-- 2. PREMIUM PLAN (৳400 / Month)                 -->
                <!-- ============================================== -->
                <div class="pricing-card bg-white rounded-3xl p-5 sm:p-7 lg:p-8 border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between hover:-translate-y-1.5 relative group">
                    <div>
                        <!-- Plan Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block"><?= htmlspecialchars($p['growth_tag']) ?></span>
                                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= htmlspecialchars($p['plan_prm_title']) ?></h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-600 group-hover:bg-emerald-50 group-hover:text-[#00684A] transition-colors">
                                <i data-lucide="compass" class="w-5 h-5"></i>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed sm:min-h-[36px]">
                            <?= htmlspecialchars($p['plan_prm_desc']) ?>
                        </p>

                        <!-- Price Tag -->
                        <div class="mt-5 mb-6 pb-5 sm:mt-6 sm:mb-7 sm:pb-6 border-b border-slate-100 flex items-baseline">
                            <span id="price-val-professional" class="text-4xl font-black text-slate-900 tracking-tight font-sans"><?= htmlspecialchars($p['plan_prm_price']) ?></span>
                            <span class="text-xs text-slate-400 font-bold ml-1.5"><?= htmlspecialchars($p['per_month']) ?></span>
                        </div>

                        <!-- Core Features List (100% Real Features - All Green Checkmarks) -->
                        <div class="space-y-3 sm:space-y-3.5 mb-6 sm:mb-8">
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span id="prod-limit-professional" class="text-xs text-slate-900 font-bold"><?= htmlspecialchars($p['plan_prm_f1']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-900 font-bold"><?= htmlspecialchars($p['plan_prm_f2']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_prm_f3']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_prm_f4']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_prm_f5']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_prm_f6']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_prm_f7']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Action Button -->
                    <a href="checkout?package=professional" 
                       class="w-full py-3 sm:py-3.5 px-4 rounded-xl sm:rounded-2xl border border-slate-200/90 hover:border-[#00684A] text-slate-700 hover:text-[#00684A] hover:bg-emerald-50/40 text-xs font-bold transition-all shadow-2xs flex items-center justify-center space-x-2 active:scale-95 cursor-pointer">
                        <span><?= htmlspecialchars($p['cta_premium']) ?></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <!-- ============================================== -->
                <!-- 3. GOLD PLAN (৳800 / Month) - UNLIMITED SCALE  -->
                <!-- ============================================== -->
                <div class="pricing-card bg-white rounded-3xl p-5 sm:p-7 lg:p-8 border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between hover:-translate-y-1.5 relative group">
                    <div>
                        <!-- Plan Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 block"><?= htmlspecialchars($p['scale_tag']) ?></span>
                                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= htmlspecialchars($p['plan_gld_title']) ?></h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-amber-50/80 border border-amber-200/60 flex items-center justify-center text-amber-600 shadow-2xs">
                                <i data-lucide="crown" class="w-5 h-5"></i>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed sm:min-h-[36px]">
                            <?= htmlspecialchars($p['plan_gld_desc']) ?>
                        </p>

                        <!-- Price Tag -->
                        <div class="mt-5 mb-6 pb-5 sm:mt-6 sm:mb-7 sm:pb-6 border-b border-slate-100 flex items-baseline">
                            <span id="price-val-enterprise" class="text-4xl font-black text-slate-900 tracking-tight font-sans"><?= htmlspecialchars($p['plan_gld_price']) ?></span>
                            <span class="text-xs text-slate-400 font-bold ml-1.5"><?= htmlspecialchars($p['per_month']) ?></span>
                        </div>

                        <!-- Core Features List (100% Real Features - All Green Checkmarks) -->
                        <div class="space-y-3 sm:space-y-3.5 mb-6 sm:mb-8">
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span id="prod-limit-enterprise" class="text-xs text-slate-900 font-black"><?= htmlspecialchars($p['plan_gld_f1']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-900 font-black"><?= htmlspecialchars($p['plan_gld_f2']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_gld_f3']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_gld_f4']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_gld_f5']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_gld_f6']) ?></span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                                </span>
                                <span class="text-xs text-slate-800 font-bold"><?= htmlspecialchars($p['plan_gld_f7']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Action Button (Bold Dark Tone) -->
                    <a href="checkout?package=enterprise" 
                       class="w-full py-3 sm:py-3.5 px-4 rounded-xl sm:rounded-2xl bg-slate-900 hover:bg-[#00684A] active:scale-95 text-white text-xs font-bold transition-all shadow-sm hover:shadow-md flex items-center justify-center space-x-2 cursor-pointer">
                        <span><?= htmlspecialchars($p['cta_gold']) ?></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- Trust Bar / Guarantees -->
            <div class="mt-8 pt-6 sm:mt-12 sm:pt-8 border-t border-slate-200/70 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-8 lg:gap-10 text-xs font-bold text-slate-500 text-center sm:text-left">
                <div class="flex items-center space-x-2">
                    <i data-lucide="shield-check" class="w-4 h-4 text-[#00684A]"></i>
                    <span><?= htmlspecialchars($p['trust_1']) ?></span>
                </div>
                <div class="flex items-center space-x-2">
                    <i data-lucide="zap" class="w-4 h-4 text-[#00684A]"></i>
                    <span><?= htmlspecialchars($p['trust_2']) ?></span>
                </div>
                <div class="flex items-center space-x-2">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-[#00684A]"></i>
                    <span><?= htmlspecialchars($p['trust_3']) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 3RD SECTION: INTERACTIVE PLATFORM DEMO SHOWCASE              -->
    <!-- ============================================================ -->
    <section id="demo" class="relative py-14 sm:py-20 lg:py-24 bg-white border-b border-slate-200/80 overflow-hidden">
        
        <!-- Subtle Ambient Background Glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-[600px] h-[350px] rounded-full bg-emerald-500/5 filter blur-[90px] pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-white border border-emerald-200/90 text-[#00684A] text-xs font-bold mb-4 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                    <span><?= htmlspecialchars($s['demo_badge']) ?></span>
                </div>

                <h2 class="text-2xl sm:text-4xl lg:text-[38px] font-black text-slate-900 tracking-tight leading-[1.2]">
                    <?= htmlspecialchars($s['demo_title_p1']) ?>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00684A] via-[#00875A] to-[#00ED64] block sm:inline">
                        <?= htmlspecialchars($s['demo_title_col']) ?>
                    </span>
                    <?= htmlspecialchars($s['demo_title_p2']) ?>
                </h2>

                <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl mx-auto">
                    <?= htmlspecialchars($s['demo_subtitle']) ?>
                </p>
            </div>

            <!-- Demo Browser Container Mockup -->
            <div id="demoMockup" class="max-w-5xl mx-auto rounded-3xl p-2.5 sm:p-5 bg-slate-50/70 border border-slate-200 shadow-xl overflow-hidden">
                <!-- Browser Chrome Bar -->
                <div class="flex items-center justify-between gap-1.5 sm:gap-2 pb-3 sm:pb-3.5 mb-3.5 sm:mb-4 border-b border-slate-200/70 overflow-hidden">
                    <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
                        <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-rose-400"></span>
                        <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-400"></span>
                        <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-[#00ED64]"></span>
                    </div>
                    <div class="flex items-center space-x-1.5 sm:space-x-2 px-2 sm:px-3.5 py-1 sm:py-1.5 rounded-lg sm:rounded-xl bg-white border border-slate-200 text-[10px] sm:text-xs font-mono text-slate-600 shadow-2xs min-w-0 max-w-[170px] sm:max-w-none">
                        <i data-lucide="lock" class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[#00684A] shrink-0"></i>
                        <span class="block truncate sm:hidden">ponnostack.shop/demo</span>
                        <span class="hidden sm:inline"><?= htmlspecialchars($s['demo_preview_url']) ?></span>
                    </div>
                    <div class="flex items-center space-x-1 sm:space-x-1.5 text-xs text-[#00684A] font-bold shrink-0">
                        <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                        <span class="text-[11px] hidden sm:inline">Live Preview</span>
                    </div>
                </div>

                <!-- 2-Column Split: Customer Storefront Experience + Merchant Management Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
                    
                    <!-- Col 1: Customer Storefront Experience (7 Cols) -->
                    <div class="lg:col-span-7 bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-2xs flex flex-col justify-between">
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-0 mb-4">
                                <span class="w-fit text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#00684A] bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-100 whitespace-nowrap">
                                    <?= htmlspecialchars($s['demo_tab_store']) ?>
                                </span>
                                <span class="text-[11px] sm:text-xs font-mono text-slate-400 whitespace-nowrap">Theme: Modern Clean</span>
                            </div>

                            <!-- Showcase Product Item Mockup -->
                            <div class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-200/70">
                                <div class="w-full sm:w-32 h-32 rounded-xl bg-gradient-to-tr from-emerald-100/30 via-slate-50 to-white border border-emerald-100 flex flex-col items-center justify-center p-3 shrink-0 relative">
                                    <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded-md bg-emerald-100 text-[#00684A] text-[9px] font-bold">In Stock</span>
                                    <i data-lucide="package" class="w-10 h-10 text-[#00684A]"></i>
                                    <span class="text-[10px] text-slate-400 font-mono mt-2">SKU: PN-2026</span>
                                </div>
                                <div class="w-full space-y-1.5 text-left">
                                    <span class="text-[10px] font-bold text-[#00684A] uppercase"><?= htmlspecialchars($s['demo_card_cat']) ?></span>
                                    <h4 class="text-base font-black text-slate-900"><?= htmlspecialchars($s['demo_card_title']) ?></h4>
                                    <div class="flex items-baseline space-x-2">
                                        <span class="text-lg font-black text-slate-900"><?= htmlspecialchars($s['demo_card_price']) ?></span>
                                        <span class="text-xs text-slate-400 line-through">৳২,৯০০</span>
                                        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">-১৫%</span>
                                    </div>
                                    <p class="text-xs text-slate-500 pt-0.5">সাইজ সিলেক্ট করুন ও ক্যাশ অন ডেলিভারিতে অর্ডার কনফার্ম করুন।</p>
                                </div>
                            </div>

                            <!-- Quick Form Preview -->
                            <div class="mt-4 pt-4 border-t border-slate-100 space-y-2.5">
                                <span class="text-xs font-bold text-slate-800 block">কুইক চেকআউট প্রিভিউ:</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                    <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-xs">
                                        নাম: রফিকুল ইসলাম
                                    </div>
                                    <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-xs">
                                        ফোন: 01700-000000
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-3">
                            <a href="#pricing" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-[#00684A] to-[#005139] hover:brightness-110 text-white text-xs font-black shadow-md shadow-[#00684A]/20 flex items-center justify-center space-x-2 cursor-pointer active:scale-95 transition">
                                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                <span><?= htmlspecialchars($s['demo_btn_order']) ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Col 2: Merchant Control Overview (5 Cols) -->
                    <div class="lg:col-span-5 bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-2xs flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-0 mb-4">
                                <span class="w-fit text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 whitespace-nowrap">
                                    <?= htmlspecialchars($s['demo_tab_admin']) ?>
                                </span>
                                <span class="text-[11px] sm:text-xs font-mono text-[#00684A] font-bold whitespace-nowrap">Auto Sync</span>
                            </div>

                            <div class="space-y-3">
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0">
                                            <i data-lucide="layers" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase block">ক্যাটালগ ক্যাপাসিটি</span>
                                            <span class="text-xs font-bold text-slate-900 mt-0.5 block">৫০ / ১০০ / আনলিমিটেড</span>
                                        </div>
                                    </div>
                                    <i data-lucide="check" class="w-4 h-4 text-[#00684A]"></i>
                                </div>

                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0">
                                            <i data-lucide="globe" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase block">কাস্টম ডোমেইন স্ট্যাটাস</span>
                                            <span class="text-xs font-bold text-slate-900 mt-0.5 block">সক্রিয় (Active) + SSL</span>
                                        </div>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-[#00ED64]"></span>
                                </div>

                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0">
                                            <i data-lucide="clock" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase block">অর্ডার নোটিফিকেশন</span>
                                            <span class="text-xs font-bold text-slate-900 mt-0.5 block">ইনস্ট্যান্ট রিয়েল-টাইম</span>
                                        </div>
                                    </div>
                                    <i data-lucide="bell" class="w-4 h-4 text-[#00684A]"></i>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-100 text-center">
                            <span class="text-xs font-bold text-slate-800 block mb-1"><?= htmlspecialchars($s['demo_cta_title']) ?></span>
                            <a href="#pricing" class="inline-flex items-center space-x-1.5 text-xs font-black text-[#00684A] hover:underline mt-1">
                                <span><?= htmlspecialchars($s['demo_cta_btn']) ?></span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 4TH SECTION: CORE PLATFORM ARCHITECTURE & CAPABILITIES       -->
    <!-- ============================================================ -->
    <section id="features" class="relative py-14 sm:py-20 lg:py-24 bg-slate-50/70 border-b border-slate-200/80 overflow-hidden">
        
        <!-- Subtle Ambient Background Glows in MongoDB Green -->
        <div class="absolute -top-20 left-10 w-96 h-96 rounded-full bg-emerald-500/8 filter blur-[80px] pointer-events-none -z-10"></div>
        <div class="absolute -bottom-20 right-10 w-96 h-96 rounded-full bg-[#00684A]/6 filter blur-[80px] pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-white border border-emerald-200/90 text-[#00684A] text-xs font-bold mb-4 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                    <span><?= htmlspecialchars($s['feat_badge']) ?></span>
                </div>

                <h2 class="text-2xl sm:text-4xl lg:text-[38px] font-black text-slate-900 tracking-tight leading-[1.2]">
                    <?= htmlspecialchars($s['feat_title_p1']) ?>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00684A] via-[#00875A] to-[#00ED64] block sm:inline">
                        <?= htmlspecialchars($s['feat_title_col']) ?>
                    </span>
                    <?= htmlspecialchars($s['feat_title_p2']) ?>
                </h2>

                <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl mx-auto">
                    <?= htmlspecialchars($s['feat_subtitle']) ?>
                </p>
            </div>

            <!-- 6-Card Architecture Grid (3x2) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                
                <!-- Card 1 -->
                <div class="feat-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-100 flex items-center justify-center mb-5 group-hover:bg-[#00684A] group-hover:text-white transition-colors">
                            <i data-lucide="layers" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-[#00684A] transition-colors mb-2">
                            <?= htmlspecialchars($s['feat1_title']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($s['feat1_desc']) ?>
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center space-x-1.5 text-[11px] font-bold text-[#00684A]">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s['feat1_tag']) ?></span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="feat-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-100 flex items-center justify-center mb-5 group-hover:bg-[#00684A] group-hover:text-white transition-colors">
                            <i data-lucide="globe" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-[#00684A] transition-colors mb-2">
                            <?= htmlspecialchars($s['feat2_title']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($s['feat2_desc']) ?>
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center space-x-1.5 text-[11px] font-bold text-[#00684A]">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s['feat2_tag']) ?></span>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="feat-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-100 flex items-center justify-center mb-5 group-hover:bg-[#00684A] group-hover:text-white transition-colors">
                            <i data-lucide="zap" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-[#00684A] transition-colors mb-2">
                            <?= htmlspecialchars($s['feat3_title']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($s['feat3_desc']) ?>
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center space-x-1.5 text-[11px] font-bold text-[#00684A]">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s['feat3_tag']) ?></span>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="feat-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-100 flex items-center justify-center mb-5 group-hover:bg-[#00684A] group-hover:text-white transition-colors">
                            <i data-lucide="smartphone" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-[#00684A] transition-colors mb-2">
                            <?= htmlspecialchars($s['feat4_title']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($s['feat4_desc']) ?>
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center space-x-1.5 text-[11px] font-bold text-[#00684A]">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s['feat4_tag']) ?></span>
                    </div>
                </div>

                <!-- Card 5 -->
                <div class="feat-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-100 flex items-center justify-center mb-5 group-hover:bg-[#00684A] group-hover:text-white transition-colors">
                            <i data-lucide="boxes" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-[#00684A] transition-colors mb-2">
                            <?= htmlspecialchars($s['feat5_title']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($s['feat5_desc']) ?>
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center space-x-1.5 text-[11px] font-bold text-[#00684A]">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s['feat5_tag']) ?></span>
                    </div>
                </div>

                <!-- Card 6 -->
                <div class="feat-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 group flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] border border-emerald-100 flex items-center justify-center mb-5 group-hover:bg-[#00684A] group-hover:text-white transition-colors">
                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-[#00684A] transition-colors mb-2">
                            <?= htmlspecialchars($s['feat6_title']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($s['feat6_desc']) ?>
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center space-x-1.5 text-[11px] font-bold text-[#00684A]">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span><?= htmlspecialchars($s['feat6_tag']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 5TH SECTION: DIRECT CONTACT & CLIENT SUPPORT CHANNELS        -->
    <!-- ============================================================ -->
    <section id="contact" class="relative py-14 sm:py-20 lg:py-24 bg-white overflow-hidden">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-white border border-emerald-200/90 text-[#00684A] text-xs font-bold mb-4 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                    <span><?= htmlspecialchars($s['contact_badge']) ?></span>
                </div>

                <h2 class="text-2xl sm:text-4xl lg:text-[38px] font-black text-slate-900 tracking-tight leading-[1.2]">
                    <?= htmlspecialchars($s['contact_title_p1']) ?>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00684A] via-[#00875A] to-[#00ED64] block sm:inline">
                        <?= htmlspecialchars($s['contact_title_col']) ?>
                    </span>
                    <?= htmlspecialchars($s['contact_title_p2']) ?>
                </h2>

                <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl mx-auto">
                    <?= htmlspecialchars($s['contact_subtitle']) ?>
                </p>
            </div>

            <!-- 2-Column Split: Direct Channels & Inquiry Form -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start max-w-5xl mx-auto">
                
                <!-- Left: Genuine Contact Channels (5 Cols) -->
                <div class="lg:col-span-5 space-y-4">
                    
                    <!-- Hotline Card -->
                    <a href="tel:01642956206" class="block p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-[#00684A]/60 hover:bg-emerald-50/20 transition group">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#00684A] group-hover:bg-[#00684A] group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                <i data-lucide="phone-call" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"><?= htmlspecialchars($s['contact_phone']) ?></span>
                                <span class="text-sm font-black text-slate-900 group-hover:text-[#00684A] transition-colors font-mono"><?= htmlspecialchars($s['contact_phone_val']) ?></span>
                            </div>
                        </div>
                    </a>

                    <!-- Email Card -->
                    <a href="mailto:support@ponnostack.shop" class="block p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-[#00684A]/60 hover:bg-emerald-50/20 transition group">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#00684A] group-hover:bg-[#00684A] group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"><?= htmlspecialchars($s['contact_email']) ?></span>
                                <span class="text-sm font-black text-slate-900 group-hover:text-[#00684A] transition-colors font-mono"><?= htmlspecialchars($s['contact_email_val']) ?></span>
                            </div>
                        </div>
                    </a>

                    <!-- Operating Hours & Location -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="flex items-center space-x-3">
                            <i data-lucide="map-pin" class="w-4 h-4 text-[#00684A] shrink-0"></i>
                            <span class="text-xs text-slate-700 font-bold"><?= htmlspecialchars($s['contact_loc_val']) ?></span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i data-lucide="clock" class="w-4 h-4 text-[#00684A] shrink-0"></i>
                            <span class="text-xs text-slate-600 font-medium"><?= htmlspecialchars($s['contact_hours_val']) ?></span>
                        </div>
                    </div>

                    <!-- Direct WhatsApp Button -->
                    <a href="https://wa.me/8801642956206" target="_blank" rel="noopener noreferrer" 
                       class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold transition-all shadow-md flex items-center justify-center space-x-2 cursor-pointer">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span><?= htmlspecialchars($s['contact_wa_btn']) ?></span>
                    </a>
                </div>

                <!-- Right: Clean Inquiry Form (7 Cols) -->
                <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm">
                    <h3 class="text-base font-black text-slate-900 mb-1"><?= htmlspecialchars($s['contact_form_title']) ?></h3>
                    <p class="text-xs text-slate-500 mb-5"><?= htmlspecialchars($s['contact_guarantee']) ?></p>

                    <form id="contactForm" onsubmit="handleContactSubmit(event)" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label for="contactName" class="block text-xs font-bold text-slate-700 mb-1.5"><?= htmlspecialchars($s['contact_name']) ?> <span class="text-rose-500">*</span></label>
                                <input type="text" id="contactName" name="name" required placeholder="উদাঃ সাকিব আহমেদ" 
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] focus:bg-white transition">
                            </div>

                            <div>
                                <label for="contactPhone" class="block text-xs font-bold text-slate-700 mb-1.5"><?= htmlspecialchars($s['contact_ph']) ?> <span class="text-rose-500">*</span></label>
                                <input type="tel" id="contactPhone" name="phone" required placeholder="01XXXXXXXXX" 
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 font-mono focus:outline-none focus:border-[#00684A] focus:bg-white transition">
                            </div>
                        </div>

                        <div>
                            <label for="contactEmail" class="block text-xs font-bold text-slate-700 mb-1.5">ইমেইল ঠিকানা (ঐচ্ছিক)</label>
                            <input type="email" id="contactEmail" name="email" placeholder="example@email.com" 
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 font-mono focus:outline-none focus:border-[#00684A] focus:bg-white transition">
                        </div>

                        <div>
                            <label for="contactMsg" class="block text-xs font-bold text-slate-700 mb-1.5"><?= htmlspecialchars($s['contact_msg']) ?> <span class="text-rose-500">*</span></label>
                            <textarea id="contactMsg" name="message" rows="3" required placeholder="আপনার স্টোর বা প্রয়োজনীয় সার্ভিস সম্পর্কে লিখুন..." 
                                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] focus:bg-white transition resize-none"></textarea>
                        </div>

                        <div id="contactFormFeedback" class="hidden p-3.5 rounded-xl text-xs font-bold"></div>

                        <button type="submit" id="contactSubmitBtn" 
                                class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-[#00684A] to-[#005139] hover:brightness-110 active:scale-95 text-white text-xs font-black shadow-md shadow-[#00684A]/25 transition flex items-center justify-center space-x-2 cursor-pointer">
                            <span><?= htmlspecialchars($s['contact_btn']) ?></span>
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ============================================================ -->
<!-- GSAP VIBRANT BACKGROUND ORBS & MOTION TIMELINE SCRIPT         -->
<!-- ============================================================ -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof gsap === 'undefined') return;

        // 1. GSAP-Powered Vibrant Gradient Orbs Organic Drifting
        // Orb 1: Spring Green Orb smooth drift
        gsap.to("#orbGreenPrimary", {
            x: 60,
            y: 40,
            scale: 1.15,
            duration: 7,
            repeat: -1,
            yoyo: true,
            ease: "sine.inOut"
        });

        // Orb 2: Forest Green Orb counter drift
        gsap.to("#orbGreenForest", {
            x: -50,
            y: 50,
            scale: 1.18,
            duration: 8,
            repeat: -1,
            yoyo: true,
            ease: "sine.inOut",
            delay: 0.5
        });

        // Orb 3: Mint Center Orb subtle breathing drift
        gsap.to("#orbMint", {
            x: 35,
            y: -30,
            scale: 1.1,
            duration: 6.5,
            repeat: -1,
            yoyo: true,
            ease: "sine.inOut",
            delay: 1
        });

        // 2. Entrance Animation Timeline for Content
        // Note: Badges use CSS compositor animation for floating, so entrance animates opacity & scale only!
        const tl = gsap.timeline({ defaults: { ease: "power3.out" } });

        tl.from(".hero-anim", {
            y: 28,
            opacity: 0,
            duration: 0.75,
            stagger: 0.1,
            delay: 0.1
        })
        .from("#dribbbleMockup", {
            y: 35,
            opacity: 0,
            scale: 0.96,
            duration: 0.9,
            ease: "power3.out"
        }, "-=0.4")
        .from(["#floatBadge1", "#floatBadge2"], {
            scale: 0.92,
            opacity: 0,
            duration: 0.7,
            stagger: 0.15,
            ease: "power2.out"
        }, "-=0.35");

        // 3. Pricing Section Scroll-Triggered Entrance Animation (Dribbble Reveal)
        const pricingSection = document.getElementById('pricing');
        if (pricingSection && 'IntersectionObserver' in window) {
            const pricingObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        gsap.from(".pricing-anim-header", {
                            y: 25,
                            opacity: 0,
                            duration: 0.7,
                            stagger: 0.1,
                            ease: "power2.out"
                        });

                        gsap.from(".pricing-card", {
                            y: 40,
                            opacity: 0,
                            duration: 0.85,
                            stagger: 0.15,
                            ease: "power3.out",
                            delay: 0.1
                        });

                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            pricingObserver.observe(pricingSection);
        }

        // 4. Features Section Scroll Entrance (Safe - Never touch opacity, always 100% solid)
        const featuresSection = document.getElementById('features');
        if (featuresSection && 'IntersectionObserver' in window) {
            const featObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        gsap.from(".feat-card", {
                            y: 20,
                            duration: 0.45,
                            stagger: 0.05,
                            ease: "power2.out",
                            clearProps: "all"
                        });
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });
            featObserver.observe(featuresSection);
        }

        // 5. Dynamic Packages Live Sync (Real-time synchronization with Global API)
        (function syncPackagesLive() {
            const isBn = (document.documentElement.lang !== 'en');
            fetch('api/packages')
                .then(res => res.json())
                .then(res => {
                    if (res && res.success && Array.isArray(res.data)) {
                        res.data.forEach(pkg => {
                            const slug = pkg.slug;
                            const priceEl = document.getElementById('price-val-' + slug);
                            const limitEl = document.getElementById('prod-limit-' + slug);

                            if (priceEl) {
                                const priceStr = isBn ? ('৳' + pkg.formatted_price_bn) : ('৳' + pkg.formatted_price_en);
                                priceEl.textContent = priceStr;
                            }

                            if (limitEl) {
                                const limitStr = isBn ? pkg.formatted_product_limit_bn : pkg.formatted_product_limit_en;
                                limitEl.textContent = limitStr;
                            }
                        });
                    }
                })
                .catch(() => {
                    // Gracefully retain SSR values if offline or network drops
                });
        })();

        // Initialize Lucide Icons for dynamic elements
        if (window.lucide) {
            lucide.createIcons();
        }
    });

    // Contact Form Submission Handler (Dynamic AJAX to Database)
    async function handleContactSubmit(e) {
        e.preventDefault();
        const feedback = document.getElementById('contactFormFeedback');
        const submitBtn = document.getElementById('contactSubmitBtn');
        const name = document.getElementById('contactName')?.value.trim();
        const phone = document.getElementById('contactPhone')?.value.trim();
        const email = document.getElementById('contactEmail')?.value.trim();
        const message = document.getElementById('contactMsg')?.value.trim();

        function showFeedback(msg, type) {
            if (!feedback) return;
            const isSuccess = (type === 'success');
            feedback.className = isSuccess 
                ? 'p-3.5 rounded-xl text-xs font-bold bg-emerald-50 text-[#00684A] border border-emerald-200 block'
                : 'p-3.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 block';
            feedback.innerHTML = `<div class="flex items-center space-x-2"><i data-lucide="${isSuccess ? 'check-circle' : 'alert-circle'}" class="w-4 h-4 shrink-0"></i><span>${msg}</span></div>`;
            if (window.lucide) lucide.createIcons();
            if (isSuccess) {
                setTimeout(() => { feedback.className = 'hidden'; }, 7000);
            }
        }

        if (!name || name.length < 2) {
            showFeedback('অনুগ্রহ করে আপনার নাম দিন (কমপক্ষে ২ অক্ষর)', 'error');
            return;
        }
        if (!phone || phone.length < 10) {
            showFeedback('সঠিক মোবাইল নম্বর প্রদান করুন', 'error');
            return;
        }
        if (!message || message.length < 5) {
            showFeedback('অনুগ্রহ করে বার্তার বিস্তারিত লিখুন (কমপক্ষে ৫ অক্ষর)', 'error');
            return;
        }

        submitBtn.disabled = true;
        const origBtnHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1.5"></i><span>বার্তা পাঠানো হচ্ছে...</span>';
        if (window.lucide) lucide.createIcons();

        try {
            const res = await fetch('api/contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, phone, email, message })
            });
            const data = await res.json();
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
            if (window.lucide) lucide.createIcons();

            if (data.success) {
                showFeedback(data.message || 'ধন্যবাদ! আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে।', 'success');
                e.target.reset();
            } else {
                showFeedback(data.message || 'বার্তা পাঠাতে সমস্যা হয়েছে। পুনরায় চেষ্টা করুন।', 'error');
            }
        } catch (err) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
            if (window.lucide) lucide.createIcons();
            showFeedback('সার্ভারের সাথে সংযোগ স্থাপন করা যায়নি। ইন্টারনেট পরীক্ষা করুন।', 'error');
        }
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
