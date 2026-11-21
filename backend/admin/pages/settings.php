<?php

declare(strict_types=1);

/** @var array $currentUser */
?>
<div class="space-y-6">
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">প্ল্যাটফর্ম সেটিংস (System Settings)</h2>
        <p class="text-xs text-slate-500 mt-1">কোর কনফিগারেশন, পেমেন্ট মেথড ও সিস্টেম ভেরিয়েবল</p>
    </div>

    <!-- Setting Card -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">এনভায়রনমেন্ট স্ট্যাটাস (.env)</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/70">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">App Name</span>
                <span class="text-slate-800 font-bold font-mono mt-0.5 block"><?= htmlspecialchars(function_exists('env') ? (string)env('APP_NAME', 'Ponnostack') : 'Ponnostack') ?></span>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/70">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">App URL</span>
                <span class="text-indigo-600 font-bold font-mono mt-0.5 block"><?= htmlspecialchars(function_exists('env') ? (string)env('APP_URL', 'http://localhost') : 'http://localhost') ?></span>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/70">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Database Host</span>
                <span class="text-slate-800 font-bold font-mono mt-0.5 block"><?= htmlspecialchars(function_exists('env') ? (string)env('DB_HOST', 'localhost') : 'localhost') ?></span>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/70">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Database Name</span>
                <span class="text-emerald-700 font-bold font-mono mt-0.5 block"><?= htmlspecialchars(function_exists('env') ? (string)env('DB_DATABASE', 'ponnostack_db') : 'ponnostack_db') ?></span>
            </div>
        </div>
    </div>
</div>
