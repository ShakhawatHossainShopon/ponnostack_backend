<?php

declare(strict_types=1);

/** @var array $currentUser */
?>
<div class="space-y-6">
    <!-- Welcome Banner (Pure Light Mode) -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold mb-3">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                <span>সিস্টেম লাইভ ড্যাশবোর্ড</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">স্বাগতম, <?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>! 👋</h2>
            <p class="text-xs text-slate-500 mt-1.5 max-w-xl leading-relaxed">
                Ponnostack প্ল্যাটফর্মের সেন্ট্রাল ব্যাকঅফিস থেকে সকল টেন্যান্ট, ইউজার অ্যাক্সেস ও সাবস্ক্রিপশন মনিটর করুন।
            </p>
        </div>

        <div class="shrink-0 flex items-center space-x-2">
            <a href="index.php?page=admins" class="inline-flex items-center space-x-2 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>অ্যাডমিন ম্যানেজ করুন</span>
            </a>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">সিস্টেম স্ট্যাটাস</span>
                <span class="text-xl font-black text-emerald-600 mt-1 block">সচল (Active)</span>
                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">Core v2.0</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="activity" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">ডাটাবেজ ইঞ্জিন</span>
                <span class="text-xl font-black text-slate-900 mt-1 block">MySQL / MariaDB</span>
                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block"><?= htmlspecialchars(env('DB_DATABASE', 'ponnostack_db')) ?></span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="database" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">টেন্যান্ট স্টোরস</span>
                <span class="text-xl font-black text-purple-600 mt-1 block">মাল্টি-টেন্যান্ট</span>
                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">আইসোলেটেড আর্কিটেকচার</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="store" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">নিরাপত্তা স্তর</span>
                <span class="text-xl font-black text-blue-600 mt-1 block">Bcrypt & JWT</span>
                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">Protected Session</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Module Card 1 -->
        <a href="index.php?page=admins" class="group bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs hover:border-indigo-300 hover:shadow-md transition">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition">অ্যাডমিন ইউজার ম্যানেজমেন্ট &rarr;</h3>
                    <p class="text-xs text-slate-500 mt-0.5">নতুন অ্যাডমিন তৈরি, পারমিশন পরিবর্তন এবং পাসওয়ার্ড রিসেট করুন।</p>
                </div>
            </div>
        </a>

        <!-- Module Card 2 -->
        <a href="index.php?page=tenants" class="group bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs hover:border-purple-300 hover:shadow-md transition">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition">
                    <i data-lucide="store" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-purple-600 transition">স্টোর ও টেন্যান্ট ডিরেক্টরি (ডেমো) &rarr;</h3>
                    <p class="text-xs text-slate-500 mt-0.5">ক্লাইন্ট স্টোরফ্রন্ট সাবস্ক্রিপশন ও মার্চেন্ট অনবোর্ডিং দেখুন।</p>
                </div>
            </div>
        </a>
    </div>
</div>
