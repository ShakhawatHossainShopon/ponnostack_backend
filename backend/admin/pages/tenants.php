<?php

declare(strict_types=1);

/** @var array $currentUser */
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span>মডিউল প্রিভিউ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">স্টোর ও টেন্যান্টস (Tenant Stores)</h2>
            <p class="text-xs text-slate-500 mt-1">Ponnostack প্ল্যাটফর্মে নিবন্ধিত মার্চেন্ট স্টোরফ্রন্টগুলোর লাইভ ডিরেক্টরি</p>
        </div>

        <button type="button" onclick="Toast.info('টেন্যান্ট অনবোর্ডিং ফিচারটি পরবর্তী ধাপে যুক্ত করা হবে।', 'শীঘ্রই আসছে')"
            class="inline-flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs transition cursor-pointer self-start sm:self-auto">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>নতুন স্টোর তৈরি করুন</span>
        </button>
    </div>

    <!-- Demo Card -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-8 text-center shadow-xs">
        <div class="w-16 h-16 rounded-3xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="store" class="w-8 h-8"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900">টেন্যান্ট আর্কিটেকচার প্রস্তুত</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1.5">
            অ্যাডমিন মডিউল চূড়ান্ত হওয়ার পর, পরবর্তী ধাপে আমরা টেন্যান্ট স্কিমা ও স্টোরফ্রন্ট অনবোর্ডিং সিস্টেমটি এই পেজে ইন্টিগ্রেট করব।
        </p>
        <div class="mt-5">
            <a href="index.php?page=admins" class="inline-flex items-center space-x-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800">
                <span>&larr; অ্যাডমিন ইউজার ম্যানেজমেন্টে ফিরুন</span>
            </a>
        </div>
    </div>
</div>
