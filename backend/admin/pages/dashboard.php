<?php

declare(strict_types=1);

/** @var array $currentUser */

use App\Models\Tenant;
use App\Models\ContactMessage;

$tenantModel = new Tenant();
$stats = $tenantModel->getStats();

$msgModel = new ContactMessage();
$unreadMessages = $msgModel->getUnreadCount();
?>
<div class="space-y-6">
    <!-- Welcome Banner (Pure Light Mode - MongoDB Brand Green) -->
    <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold mb-3">
                <span class="w-2 h-2 rounded-full bg-[#00684A] animate-pulse"></span>
                <span>সিস্টেম লাইভ ড্যাশবোর্ড</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">স্বাগতম, <?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>! 👋</h2>
            <p class="text-xs text-slate-500 mt-1.5 max-w-xl leading-relaxed">
                Ponnostack প্ল্যাটফর্মের সেন্ট্রাল ব্যাকঅফিস থেকে সকল টেন্যান্ট স্টোর, পেমেন্ট ও সাবস্ক্রিপশন সাইকেল মনিটর করুন।
            </p>
        </div>

        <div class="shrink-0 flex items-center space-x-2">
            <a href="index.php?page=tenants" class="inline-flex items-center space-x-2 py-2.5 px-4 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer">
                <i data-lucide="store" class="w-4 h-4"></i>
                <span>স্টোর ম্যানেজ করুন</span>
            </a>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Active Stores -->
        <a href="index.php?page=tenants" class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between hover:border-emerald-300 transition">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">সক্রিয় স্টোর</span>
                <span class="text-2xl font-black text-[#00684A] mt-1 block"><?= $stats['active'] ?></span>
                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">মোট স্টোর: <?= $stats['total'] ?></span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-[#00684A] flex items-center justify-center">
                <i data-lucide="store" class="w-5 h-5"></i>
            </div>
        </a>

        <!-- Card 2: Pending Store Approvals -->
        <a href="index.php?page=tenants" class="bg-white border border-amber-200/90 bg-amber-50/20 rounded-2xl p-5 shadow-xs flex items-center justify-between hover:border-amber-300 transition">
            <div>
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block">অনুমোদন অপেক্ষমাণ</span>
                <span class="text-2xl font-black text-amber-900 mt-1 block"><?= $stats['pending'] ?></span>
                <span class="text-[10px] text-amber-700 font-semibold mt-0.5 block">পেমেন্ট রিভিউ প্রয়োজন</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </a>

        <!-- Card 3: Monthly Recurring Fee (MRR) -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">মাসিক আবর্তক ফি (MRR)</span>
                <span class="text-2xl font-black text-slate-900 font-mono mt-1 block">৳ <?= number_format($stats['mrr']) ?></span>
                <span class="text-[10px] text-emerald-600 font-semibold mt-0.5 block">সক্রিয় সাবস্ক্রিপশন হতে</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Card 4: Inquiries / Inbox -->
        <a href="index.php?page=inbox" class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between hover:border-emerald-300 transition">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">ইনবক্স বার্তা</span>
                <span class="text-2xl font-black text-slate-900 mt-1 block"><?= $unreadMessages ?></span>
                <span class="text-[10px] text-rose-600 font-semibold mt-0.5 block">নতুন অপঠিত বার্তা</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-[#00684A] flex items-center justify-center">
                <i data-lucide="mail" class="w-5 h-5"></i>
            </div>
        </a>
    </div>

    <!-- Quick Navigation Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Module Card 1: Tenants -->
        <a href="index.php?page=tenants" class="group bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs hover:border-emerald-300 hover:shadow-md transition">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] flex items-center justify-center group-hover:scale-105 transition shadow-2xs">
                    <i data-lucide="store" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-[#00684A] transition">স্টোর ও টেন্যান্ট ম্যানেজমেন্ট &rarr;</h3>
                    <p class="text-xs text-slate-500 mt-0.5">মার্চেন্ট স্টোরফ্রন্ট অনুমোদন, লাইভ লিংক বরাদ্দ, মেয়াদ ও বিলিং ট্র্যাকিং।</p>
                </div>
            </div>
        </a>

        <!-- Module Card 2: Admins -->
        <a href="index.php?page=admins" class="group bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs hover:border-emerald-300 hover:shadow-md transition">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#00684A] flex items-center justify-center group-hover:scale-105 transition shadow-2xs">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-[#00684A] transition">অ্যাডমিন ইউজার ম্যানেজমেন্ট &rarr;</h3>
                    <p class="text-xs text-slate-500 mt-0.5">সিস্টেম অ্যাডমিন অ্যাকাউন্ট ও অনুমতি পরিচালনা করুন।</p>
                </div>
            </div>
        </a>
    </div>
</div>
