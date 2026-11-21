<?php

declare(strict_types=1);

/**
 * Ponnostack Public - Modern High-End Dribbble Footer
 * Pure Light Mode, MongoDB Green Brand System, Clean Routing (No .php / No index.php)
 */

$footerLang = $lang ?? 'bn';

$footerI18n = [
    'bn' => [
        'tagline'        => 'বাংলাদেশের আধুনিক ও সাশ্রয়ী নো-কোড ই-কমার্স প্ল্যাটফর্ম। কোনো কোডিং জটিলতা ছাড়াই নিজস্ব ব্র্যান্ডেড অনলাইন শপ পরিচালনা করুন।',
        'status_live'    => 'ক্লাউড সিস্টেম সচল (৯৯.৯% আপটাইম)',
        'col_platform'   => 'প্ল্যাটফর্ম',
        'link_home'      => 'মূল পাতা (Home)',
        'link_demo'      => 'লাইভ প্ল্যাটফর্ম ডেমো',
        'link_features'  => 'কোর ফিচারসমূহ',
        'link_pricing'   => 'প্রাইসিং প্ল্যান',
        'col_links'      => 'কুইক অ্যাক্সেস',
        'link_contact'   => 'সরাসরি যোগাযোগ',
        'link_whatsapp'  => 'হোয়াটসঅ্যাপ সহায়তা',
        'col_support'    => 'সরাসরি সহায়তা',
        'support_loc'    => 'ঢাকা, বাংলাদেশ',
        'support_hours'  => 'সকাল ৯:০০ টা - রাত ১০:০০ টা',
        'copyright'      => 'সর্বস্বত্ব সংরক্ষিত।',
        'pure_light'     => 'লাইট মোড আর্কিটেকচার',
    ],
    'en' => [
        'tagline'        => 'Bangladesh\'s dedicated no-code commerce platform. Effortlessly launch and scale your branded online storefront with zero complexity.',
        'status_live'    => 'Cloud Systems Operational (99.9% Uptime)',
        'col_platform'   => 'Platform',
        'link_home'      => 'Home Overview',
        'link_demo'      => 'Interactive Demo',
        'link_features'  => 'Platform Features',
        'link_pricing'   => 'Pricing Plans',
        'col_links'      => 'Quick Access',
        'link_contact'   => 'Get In Touch',
        'link_whatsapp'  => 'WhatsApp Support',
        'col_support'    => 'Direct Support',
        'support_loc'    => 'Dhaka, Bangladesh',
        'support_hours'  => '9:00 AM - 10:00 PM (Daily)',
        'copyright'      => 'All rights reserved.',
        'pure_light'     => 'Pure Light Architecture',
    ],
];

$ft = $footerI18n[$footerLang] ?? $footerI18n['bn'];
?>
    <!-- High-End Dribbble Light Mode Footer -->
    <footer class="bg-slate-50 border-t border-slate-200 mt-auto pt-14 pb-8 text-slate-600 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12 pb-12 border-b border-slate-200/80">
                
                <!-- Col 1: Brand & Tagline (5 Cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <a href="./" class="inline-block">
                        <img src="assets/images/logo.png" alt="Ponnostack" width="160" height="48" class="h-8 w-auto object-contain">
                    </a>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-sm">
                        <?= htmlspecialchars($ft['tagline']) ?>
                    </p>
                    <div>
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-50 text-[#00684A] border border-emerald-200 text-[11px] font-bold">
                            <span class="w-2 h-2 rounded-full bg-[#00ED64] animate-pulse"></span>
                            <span><?= htmlspecialchars($ft['status_live']) ?></span>
                        </span>
                    </div>
                </div>

                <!-- Col 2: Platform Links (2 Cols) -->
                <div class="lg:col-span-2 space-y-3">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-900"><?= htmlspecialchars($ft['col_platform']) ?></h4>
                    <ul class="space-y-2 text-xs font-medium text-slate-600">
                        <li><a href="./" class="hover:text-[#00684A] transition"><?= htmlspecialchars($ft['link_home']) ?></a></li>
                        <li><a href="#demo" class="hover:text-[#00684A] transition"><?= htmlspecialchars($ft['link_demo']) ?></a></li>
                        <li><a href="#features" class="hover:text-[#00684A] transition"><?= htmlspecialchars($ft['link_features']) ?></a></li>
                        <li><a href="#pricing" class="hover:text-[#00684A] transition"><?= htmlspecialchars($ft['link_pricing']) ?></a></li>
                    </ul>
                </div>

                <!-- Col 3: Quick Access (2 Cols) -->
                <div class="lg:col-span-2 space-y-3">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-900"><?= htmlspecialchars($ft['col_links']) ?></h4>
                    <ul class="space-y-2 text-xs font-medium text-slate-600">
                        <li><a href="#contact" class="hover:text-[#00684A] transition"><?= htmlspecialchars($ft['link_contact']) ?></a></li>
                        <li><a href="https://wa.me/8801642956206" target="_blank" rel="noopener noreferrer" class="hover:text-[#00684A] transition"><?= htmlspecialchars($ft['link_whatsapp']) ?></a></li>
                    </ul>
                </div>

                <!-- Col 4: Direct Support (3 Cols) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-900"><?= htmlspecialchars($ft['col_support']) ?></h4>
                    <ul class="space-y-2 text-xs text-slate-600">
                        <li class="flex items-center space-x-2">
                            <i data-lucide="phone-call" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <a href="tel:01642956206" class="font-mono font-bold text-slate-900 hover:text-[#00684A]">01642956206</a>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <a href="mailto:support@ponnostack.shop" class="font-mono text-slate-700 hover:text-[#00684A]">support@ponnostack.shop</a>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <span><?= htmlspecialchars($ft['support_loc']) ?></span>
                        </li>
                        <li class="flex items-center space-x-2 text-[11px] text-slate-400">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <span><?= htmlspecialchars($ft['support_hours']) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Sub-Footer Bar -->
            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Ponnostack. <?= htmlspecialchars($ft['copyright']) ?></p>
                <div class="flex items-center space-x-4 text-[11px]">
                    <span class="text-slate-400"><?= htmlspecialchars($ft['pure_light']) ?></span>
                    <span class="text-slate-300">•</span>
                    <a href="?lang=bn" class="hover:text-[#00684A] <?= $footerLang === 'bn' ? 'font-black text-[#00684A]' : '' ?>">বাংলা</a>
                    <span class="text-slate-300">|</span>
                    <a href="?lang=en" class="hover:text-[#00684A] <?= $footerLang === 'en' ? 'font-black text-[#00684A]' : '' ?>">English</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Initialize Lucide Icons -->
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
