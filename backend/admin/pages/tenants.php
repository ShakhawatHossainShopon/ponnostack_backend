<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Storefront & Subscription Lifecycle Management
 * 
 * 3-Stage Pipeline:
 *  1. পেমেন্ট পেন্ডিং (Payment Pending) - bKash TrxID review & approval
 *  2. সেটআপ পেন্ডিং (Setup Pending) - Domain / Vercel link assignment & activation
 *  3. অল অ্যাক্টিভ স্টোরফ্রন্ট (All Active Storefronts) - Live stores, billing countdown, grace period extensions
 * 
 * Strict Design Standards:
 *  - Pure Light Mode Only. Zero Dark Mode.
 *  - Modals Backdrop: Pure Light Frosted Glass (bg-white/85 backdrop-blur-md), zero black overlays.
 *  - Click outside modal closes it immediately.
 *  - MongoDB Green Brand Palette: #00684A (Primary), #00ED64 (Accent), #00523A (Hover).
 *  - NO SweetAlert2 popups; strictly inline highlights and Toast.success / Toast.error notifications.
 */

use App\Models\Tenant;
use App\Models\Package;

$tenantModel = new Tenant();
$tenants = $tenantModel->getAll();
$stats = $tenantModel->getStats();

$packageModel = new Package();
$packages = $packageModel->getAll();

// Partition tenants into the 3 Pipeline Stages
$paymentPendingTenants = array_values(array_filter($tenants, fn($t) => in_array($t['status'], ['payment_pending', 'pending'], true)));
$setupPendingTenants   = array_values(array_filter($tenants, fn($t) => $t['status'] === 'setup_pending'));
$activeTenants         = array_values(array_filter($tenants, fn($t) => in_array($t['status'], ['active', 'suspended', 'expired'], true)));
?>
<div class="space-y-6">

    <!-- 1. Top Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#00684A] animate-pulse"></span>
                <span>স্টোরফ্রন্ট ও সাবস্ক্রিপশন পাইপলাইন</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">স্টোর ও সাবস্ক্রিপশন ম্যানেজমেন্ট</h2>
            <p class="text-xs text-slate-500 mt-1">মার্চেন্ট লাইফসাইকেল: পেমেন্ট যাচাই ➔ ডোমেইন/ভার্সেল লিংক সেটআপ ➔ সক্রিয় স্টোর ট্র্যাকিং ও বিলিং</p>
        </div>

        <button type="button" onclick="openCreateStoreModal()"
            class="inline-flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl bg-[#00684A] hover:bg-[#00523A] active:scale-95 text-white text-xs font-bold shadow-brand-glow transition cursor-pointer self-start sm:self-auto">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>নতুন স্টোর তৈরি করুন</span>
        </button>
    </div>

    <!-- 2. The 3 Core Pipeline Navigation Tabs -->
    <div class="bg-white p-2 rounded-2xl border border-slate-200/90 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <!-- Tab 1: Payment Pending -->
            <button type="button" onclick="switchPipelineTab('payment_pending')" id="tabBtn_payment_pending"
                class="pipeline-tab flex items-center justify-between p-3.5 rounded-xl border transition text-left cursor-pointer bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100/80">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold shrink-0 bg-amber-100 text-amber-800">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-xs font-black truncate">১. পেমেন্ট পেন্ডিং</span>
                            <span id="badgePaymentPending" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-200/80 text-amber-900 font-mono">
                                <?= count($paymentPendingTenants) ?>
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-400 block truncate">বিকাশ TrxID যাচাই ও অনুমোদন</span>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0 ml-1"></i>
            </button>

            <!-- Tab 2: Setup Pending -->
            <button type="button" onclick="switchPipelineTab('setup_pending')" id="tabBtn_setup_pending"
                class="pipeline-tab flex items-center justify-between p-3.5 rounded-xl border transition text-left cursor-pointer bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100/80">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold shrink-0 bg-sky-100 text-sky-800">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-xs font-black truncate">২. সেটআপ পেন্ডিং</span>
                            <span id="badgeSetupPending" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-sky-200/80 text-sky-900 font-mono">
                                <?= count($setupPendingTenants) ?>
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-400 block truncate">ডোমেইন/ভার্সেল লিংক বরাদ্দ</span>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0 ml-1"></i>
            </button>

            <!-- Tab 3: All Active Storefronts -->
            <button type="button" onclick="switchPipelineTab('active')" id="tabBtn_active"
                class="pipeline-tab flex items-center justify-between p-3.5 rounded-xl border transition text-left cursor-pointer bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100/80">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold shrink-0 bg-emerald-100 text-[#00684A]">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-xs font-black truncate">৩. অল অ্যাক্টিভ স্টোরফ্রন্ট</span>
                            <span id="badgeActive" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-200/80 text-emerald-950 font-mono">
                                <?= count($activeTenants) ?>
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-400 block truncate">চলমান স্টোর ও মাসিক বিলিং</span>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0 ml-1"></i>
            </button>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- PIPELINE VIEW 1: PAYMENT PENDING (পেমেন্ট পেন্ডিং স্টোরফ্রন্ট)       -->
    <!-- =================================================================== -->
    <div id="pipelineView_payment_pending" class="pipeline-view space-y-4 hidden">
        <div class="bg-amber-50/60 border border-amber-200/80 p-4 rounded-2xl flex items-center justify-between gap-3 text-xs text-amber-950">
            <div class="flex items-center space-x-2.5">
                <i data-lucide="info" class="w-4 h-4 text-amber-700 shrink-0"></i>
                <span>মার্চেন্টরা চেকআউট করে বিকাশ TrxID জমা দিলে এখানে আসে। TrxID যাচাই করে <strong>"পেমেন্ট অনুমোদন করুন"</strong> বাটনে ক্লিক করলে স্টোরটি ২য় ধাপে (সেটআপ পেন্ডিং) চলে যাবে।</span>
            </div>
            <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-900 font-bold font-mono shrink-0">
                <?= count($paymentPendingTenants) ?> টি আবেদন
            </span>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 sm:px-5">স্টোর ও লোগো</th>
                            <th class="py-3.5 px-4">মার্চেন্ট ও ফোন</th>
                            <th class="py-3.5 px-4">প্যাকেজ ও ফি</th>
                            <th class="py-3.5 px-4">বিকাশ পেমেন্ট TrxID</th>
                            <th class="py-3.5 px-4">আবেদনের তারিখ</th>
                            <th class="py-3.5 px-4 text-right">পদক্ষেপ (Action)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tableBody_payment_pending">
                        <?php if (empty($paymentPendingTenants)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-2">
                                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                                    </div>
                                    <span class="font-bold text-slate-700 text-sm">কোনো পেমেন্ট পেন্ডিং আবেদন নেই</span>
                                    <p class="text-xs text-slate-400 mt-0.5">সব আবেদন অনুমোদিত হয়ে পরবর্তী ধাপে রয়েছে।</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paymentPendingTenants as $t): ?>
                                <tr class="hover:bg-amber-50/20 transition" id="tenant-row-<?= $t['id'] ?>">
                                    <!-- Store & Logo -->
                                    <td class="py-3.5 px-4 sm:px-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                                <?php if (!empty($t['logo_url'])): ?>
                                                    <img src="<?= htmlspecialchars($t['logo_url']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="w-full h-full object-contain p-1">
                                                <?php else: ?>
                                                    <i data-lucide="store" class="w-5 h-5 text-amber-700"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-black text-slate-900 block truncate text-xs sm:text-sm"><?= htmlspecialchars($t['name']) ?></span>
                                                <span class="text-[10px] text-slate-400 font-mono block">slug: <?= htmlspecialchars($t['slug']) ?></span>
                                                <div class="flex items-center space-x-1.5 mt-0.5">
                                                    <code class="text-[10px] text-slate-500 font-mono font-bold bg-slate-100 px-1.5 py-0.5 rounded truncate max-w-[140px]" title="<?= htmlspecialchars($t['uuid']) ?>"><?= htmlspecialchars($t['uuid']) ?></code>
                                                    <button type="button" onclick="copyText('<?= htmlspecialchars($t['uuid']) ?>', 'Store UUID')" class="p-1 rounded hover:bg-amber-100 text-slate-400 hover:text-amber-700 transition cursor-pointer" title="UUID কপি করুন">
                                                        <i data-lucide="copy" class="w-3 h-3"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Merchant & Phone -->
                                    <td class="py-3.5 px-4">
                                        <div class="min-w-0">
                                            <strong class="font-bold text-slate-800 block truncate"><?= htmlspecialchars($t['owner_name']) ?></strong>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="text-[11px] text-slate-500 font-mono"><?= htmlspecialchars($t['owner_phone'] ?? '-') ?></span>
                                                <?php if (!empty($t['owner_phone'])): ?>
                                                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $t['owner_phone']) ?>" class="text-slate-400 hover:text-[#00684A]" title="কল করুন">
                                                        <i data-lucide="phone" class="w-3 h-3"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Package & Fee -->
                                    <td class="py-3.5 px-4">
                                        <div class="min-w-0">
                                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($t['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ') ?></span>
                                            <span class="font-mono font-black text-[#00684A] text-xs">৳ <?= number_format((float)$t['monthly_fee']) ?> /মাস</span>
                                        </div>
                                    </td>

                                    <!-- bKash TrxID -->
                                    <td class="py-3.5 px-4">
                                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-950 px-2.5 py-1 rounded-xl">
                                            <span class="font-mono font-black text-xs tracking-wider"><?= htmlspecialchars($t['payment_trx_id'] ?? '-') ?></span>
                                            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($t['payment_trx_id'] ?? '') ?>'); Toast.success('TrxID কপি করা হয়েছে!')"
                                                class="text-emerald-700 hover:text-emerald-950 transition cursor-pointer" title="কপি করুন">
                                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono block mt-1">প্রেরক: <?= htmlspecialchars($t['payment_sender'] ?? $t['owner_phone'] ?? '-') ?></span>
                                    </td>

                                    <!-- Application Date -->
                                    <td class="py-3.5 px-4">
                                        <span class="text-xs text-slate-600 block"><?= $t['formatted_created'] ?? date('d M, Y', strtotime($t['created_at'])) ?></span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 text-[10px] font-bold border border-amber-200 mt-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>পেমেন্ট যাচাই অপেক্ষমাণ</span>
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end space-x-1.5">
                                            <button type="button" onclick="approvePayment(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')"
                                                class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-2xs transition cursor-pointer">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>পেমেন্ট অনুমোদন করুন</span>
                                            </button>
                                            <button type="button" onclick="deleteStore(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')"
                                                class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="আবেদন বাতিল করুন">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- PIPELINE VIEW 2: SETUP PENDING (সেটআপ পেন্ডিং স্টোরফ্রন্ট)          -->
    <!-- =================================================================== -->
    <div id="pipelineView_setup_pending" class="pipeline-view space-y-4 hidden">
        <div class="bg-sky-50/60 border border-sky-200/80 p-4 rounded-2xl flex items-center justify-between gap-3 text-xs text-sky-950">
            <div class="flex items-center space-x-2.5">
                <i data-lucide="info" class="w-4 h-4 text-sky-700 shrink-0"></i>
                <span>এই স্টোরগুলোর বিকাশ পেমেন্ট ভেরিফাই হয়েছে। এখন তাদের লাইভ স্টোরফ্রন্ট URL (ডোমেইন বা Vercel লিংক) বসিয়ে <strong>"সক্রিয় করুন"</strong> চাপলে স্টোরটি লাইভ হবে এবং অল অ্যাক্টিভ স্টোরফ্রন্টে চলে যাবে।</span>
            </div>
            <span class="px-2.5 py-1 rounded-lg bg-sky-100 text-sky-900 font-bold font-mono shrink-0">
                <?= count($setupPendingTenants) ?> টি সেটআপ অপেক্ষমাণ
            </span>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 sm:px-5">স্টোর ও লোগো</th>
                            <th class="py-3.5 px-4">মার্চেন্ট ও ফোন</th>
                            <th class="py-3.5 px-4">অনুমোদিত পেমেন্ট</th>
                            <th class="py-3.5 px-4">ডোমেইন ও ইউআরএল অবস্থা</th>
                            <th class="py-3.5 px-4 text-right">পদক্ষেপ (Action)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tableBody_setup_pending">
                        <?php if (empty($setupPendingTenants)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-2">
                                        <i data-lucide="globe" class="w-6 h-6"></i>
                                    </div>
                                    <span class="font-bold text-slate-700 text-sm">কোনো সেটআপ পেন্ডিং স্টোর নেই</span>
                                    <p class="text-xs text-slate-400 mt-0.5">সব স্টোর ডোমেইন বরাদ্দ পেয়ে লাইভ রয়েছে।</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($setupPendingTenants as $t): ?>
                                <tr class="hover:bg-sky-50/20 transition" id="tenant-row-<?= $t['id'] ?>">
                                    <!-- Store & Logo -->
                                    <td class="py-3.5 px-4 sm:px-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                                <?php if (!empty($t['logo_url'])): ?>
                                                    <img src="<?= htmlspecialchars($t['logo_url']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="w-full h-full object-contain p-1">
                                                <?php else: ?>
                                                    <i data-lucide="store" class="w-5 h-5 text-sky-700"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-black text-slate-900 block truncate text-xs sm:text-sm"><?= htmlspecialchars($t['name']) ?></span>
                                                <span class="text-[10px] text-slate-400 font-mono block">slug: <?= htmlspecialchars($t['slug']) ?></span>
                                                <div class="flex items-center space-x-1.5 mt-0.5">
                                                    <code class="text-[10px] text-slate-500 font-mono font-bold bg-slate-100 px-1.5 py-0.5 rounded truncate max-w-[140px]" title="<?= htmlspecialchars($t['uuid']) ?>"><?= htmlspecialchars($t['uuid']) ?></code>
                                                    <button type="button" onclick="copyText('<?= htmlspecialchars($t['uuid']) ?>', 'Store UUID')" class="p-1 rounded hover:bg-amber-100 text-slate-400 hover:text-amber-700 transition cursor-pointer" title="UUID কপি করুন">
                                                        <i data-lucide="copy" class="w-3 h-3"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Merchant & Phone -->
                                    <td class="py-3.5 px-4">
                                        <div class="min-w-0">
                                            <strong class="font-bold text-slate-800 block truncate"><?= htmlspecialchars($t['owner_name']) ?></strong>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="text-[11px] text-slate-500 font-mono"><?= htmlspecialchars($t['owner_phone'] ?? '-') ?></span>
                                                <?php if (!empty($t['owner_phone'])): ?>
                                                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $t['owner_phone']) ?>" class="text-slate-400 hover:text-[#00684A]" title="কল করুন">
                                                        <i data-lucide="phone" class="w-3 h-3"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Verified Payment -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-bold border border-emerald-200">
                                                <i data-lucide="check-circle" class="w-3 h-3 text-[#00684A]"></i>
                                                <span>পেমেন্ট অ্যাপ্রুভড</span>
                                            </span>
                                            <span class="font-mono text-xs font-bold text-slate-700"><?= htmlspecialchars($t['payment_trx_id'] ?? '-') ?></span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 block mt-1"><?= htmlspecialchars($t['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ') ?> (৳<?= number_format((float)$t['monthly_fee']) ?>)</span>
                                    </td>

                                    <!-- Domain Status -->
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-sky-50 text-sky-800 text-[11px] font-bold border border-sky-200">
                                            <i data-lucide="link" class="w-3.5 h-3.5 text-sky-600"></i>
                                            <span>ইউআরএল বরাদ্দ অপেক্ষমাণ</span>
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono block mt-1">প্রস্তাবিত: https://<?= htmlspecialchars($t['slug']) ?>.vercel.app</span>
                                    </td>

                                    <!-- Action -->
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" onclick="openSetupModal(<?= htmlspecialchars(json_encode($t)) ?>)"
                                            class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer">
                                            <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                                            <span>ইউআরএল সেটআপ ও সক্রিয় করুন</span>
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

    <!-- =================================================================== -->
    <!-- PIPELINE VIEW 3: ALL ACTIVE STOREFRONTS (অল অ্যাক্টিভ স্টোরফ্রন্ট)  -->
    <!-- =================================================================== -->
    <div id="pipelineView_active" class="pipeline-view space-y-4">
        
        <!-- Active Stores KPI Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- Total Active -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-emerald-200/90 bg-emerald-50/20 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">মোট সক্রিয় স্টোর</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                        <i data-lucide="store" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-black text-[#00684A]" id="statActive"><?= $stats['active'] ?></div>
                <span class="text-[10px] text-emerald-700 font-semibold mt-0.5 block">লাইভ ও সেবা চালু</span>
            </div>

            <!-- Expiring Today -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-200/90 bg-rose-50/20 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">আজকেই মেয়াদ শেষ</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-black text-rose-900" id="statExpiringToday"><?= $stats['expiring_today'] ?></div>
                <span class="text-[10px] text-rose-700 font-semibold mt-0.5 block">জরুরি বিল নবায়ন প্রয়োজন</span>
            </div>

            <!-- Expiring Soon (<= 5 Days) -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-orange-200/90 bg-orange-50/20 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-orange-800 uppercase tracking-wider">৫ দিন বাকি (শীঘ্রই শেষ)</span>
                    <div class="w-8 h-8 rounded-xl bg-orange-100 text-orange-800 flex items-center justify-center font-bold">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-black text-orange-900" id="statExpiringSoon"><?= $stats['expiring_soon'] ?></div>
                <span class="text-[10px] text-orange-700 font-semibold mt-0.5 block">বিলিং রিমাইন্ডার পাঠানো হবে</span>
            </div>

            <!-- Suspended / Overdue -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200/90 bg-slate-50 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">স্থগিত / ওভারডিউ</span>
                    <div class="w-8 h-8 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold">
                        <i data-lucide="pause-circle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-black text-slate-900" id="statSuspended"><?= $stats['suspended'] + $stats['expired'] ?></div>
                <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">বকেয়া বিলের জন্য বন্ধ</span>
            </div>

            <!-- Monthly Recurring Revenue (MRR) -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-emerald-200/90 bg-emerald-50/10 shadow-2xs col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">মাসিক আয় (MRR)</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-black text-slate-900 font-mono" id="statMRR">৳ <?= number_format((float)$stats['mrr']) ?></div>
                <span class="text-[10px] text-slate-400 font-semibold mt-0.5 block">সক্রিয় স্টোর হতে প্রতি মাসে</span>
            </div>
        </div>

        <!-- Filter Pills & Search Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
            <!-- Sub Filter Pills -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none" id="activeSubFilters">
                <button type="button" onclick="filterActiveStores('all')" class="active-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-[#00684A] text-white shadow-2xs cursor-pointer shrink-0" data-sub="all">
                    সব সক্রিয় স্টোর (<?= count($activeTenants) ?>)
                </button>
                <button type="button" onclick="filterActiveStores('expiring_today')" class="active-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-sub="expiring_today">
                    আজকেই শেষ (<?= $stats['expiring_today'] ?>)
                </button>
                <button type="button" onclick="filterActiveStores('expiring_soon')" class="active-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-sub="expiring_soon">
                    ৫ দিন বাকি (<?= $stats['expiring_soon'] ?>)
                </button>
                <button type="button" onclick="filterActiveStores('suspended')" class="active-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-sub="suspended">
                    স্থগিত / ওভারডিউ (<?= $stats['suspended'] + $stats['expired'] ?>)
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative w-full md:w-72 shrink-0">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="activeSearchInput" oninput="handleActiveSearch(this.value)" placeholder="স্টোর, নাম, ফোন বা লিংক খুঁজুন..."
                    class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00684A] focus:bg-white transition">
            </div>
        </div>

        <!-- Active Storefronts Master Table -->
        <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 sm:px-5">স্টোর ও লাইভ ডোমেইন</th>
                            <th class="py-3.5 px-4">মার্চেন্ট ও ফোন</th>
                            <th class="py-3.5 px-4">স্থায়ী বিলিং ডেট</th>
                            <th class="py-3.5 px-4">বর্তমান মেয়াদ ও কাউন্টডাউন</th>
                            <th class="py-3.5 px-4">স্ট্যাটাস</th>
                            <th class="py-3.5 px-4 text-right">পদক্ষেপ (Actions)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tableBody_active">
                        <?php if (empty($activeTenants)): ?>
                            <tr id="emptyActiveRow">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-2">
                                        <i data-lucide="store" class="w-6 h-6"></i>
                                    </div>
                                    <span class="font-bold text-slate-600 text-sm">কোনো সক্রিয় স্টোরফ্রন্ট নেই</span>
                                    <p class="text-xs text-slate-400 mt-0.5">পেন্ডিং স্টোরগুলোর ইউআরএল সেটআপ সম্পন্ন হলে এখানে প্রদর্শিত হবে।</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeTenants as $t): ?>
                                <?php
                                $rowStatus = $t['status'];
                                $daysLeft = $t['days_left'];
                                $isExpToday = (!empty($t['subscription_ends_at']) && date('Y-m-d', strtotime($t['subscription_ends_at'])) === date('Y-m-d'));
                                $isExpSoon = ($t['is_expiring_soon'] && !$isExpToday);
                                $isOverdue = ($t['is_overdue'] || $rowStatus === 'suspended' || $rowStatus === 'expired');
                                $liveUrl = $t['domain'] ? (str_starts_with($t['domain'], 'http') ? $t['domain'] : 'https://' . $t['domain']) : null;
                                ?>
                                <tr class="hover:bg-slate-50/70 transition active-tenant-row"
                                    id="tenant-row-<?= $t['id'] ?>"
                                    data-status="<?= $rowStatus ?>"
                                    data-today="<?= $isExpToday ? '1' : '0' ?>"
                                    data-soon="<?= $isExpSoon ? '1' : '0' ?>"
                                    data-overdue="<?= $isOverdue ? '1' : '0' ?>"
                                    data-search="<?= strtolower(htmlspecialchars($t['name'] . ' ' . $t['slug'] . ' ' . $t['owner_name'] . ' ' . ($t['owner_phone'] ?? '') . ' ' . ($t['domain'] ?? ''))) ?>">

                                    <!-- 1. Store & Live Domain -->
                                    <td class="py-3.5 px-4 sm:px-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                                <?php if (!empty($t['logo_url'])): ?>
                                                    <img src="<?= htmlspecialchars($t['logo_url']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="w-full h-full object-contain p-1">
                                                <?php else: ?>
                                                    <i data-lucide="store" class="w-5 h-5 text-[#00684A]"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-black text-slate-900 block truncate text-xs sm:text-sm"><?= htmlspecialchars($t['name']) ?></span>
                                                <?php if ($liveUrl): ?>
                                                    <a href="<?= htmlspecialchars($liveUrl) ?>" target="_blank" class="inline-flex items-center space-x-1 text-[11px] text-[#00684A] hover:underline font-mono truncate max-w-[200px]" title="লাইভ স্টোর ভিজিট করুন">
                                                        <span><?= htmlspecialchars(preg_replace('#^https?://#', '', rtrim($liveUrl, '/'))) ?></span>
                                                        <i data-lucide="external-link" class="w-3 h-3 shrink-0"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-mono block">slug: <?= htmlspecialchars($t['slug']) ?></span>
                                                <?php endif; ?>
                                                <div class="flex items-center space-x-1.5 mt-0.5">
                                                    <code class="text-[10px] text-slate-500 font-mono font-bold bg-slate-100 px-1.5 py-0.5 rounded truncate max-w-[140px]" title="<?= htmlspecialchars($t['uuid']) ?>"><?= htmlspecialchars($t['uuid']) ?></code>
                                                    <button type="button" onclick="copyText('<?= htmlspecialchars($t['uuid']) ?>', 'Store UUID')" class="p-1 rounded hover:bg-emerald-100 text-slate-400 hover:text-[#00684A] transition cursor-pointer" title="UUID কপি করুন">
                                                        <i data-lucide="copy" class="w-3 h-3"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. Owner & Contact -->
                                    <td class="py-3.5 px-4">
                                        <div class="min-w-0">
                                            <strong class="font-bold text-slate-800 block truncate"><?= htmlspecialchars($t['owner_name']) ?></strong>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="text-[11px] text-slate-500 font-mono"><?= htmlspecialchars($t['owner_phone'] ?? '-') ?></span>
                                                <?php if (!empty($t['owner_phone'])): ?>
                                                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $t['owner_phone']) ?>" class="text-slate-400 hover:text-[#00684A]" title="কল করুন">
                                                        <i data-lucide="phone" class="w-3 h-3"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 3. Permanent Billing Day -->
                                    <td class="py-3.5 px-4">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 border border-slate-200">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-500"></i>
                                            <span class="text-xs font-black">প্রতি মাসের <?= $t['billing_day'] ?> তারিখ</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono block mt-1">ফি: ৳<?= number_format((float)$t['monthly_fee']) ?> /মাস</span>
                                    </td>

                                    <!-- 4. Subscription Validity & Countdown -->
                                    <td class="py-3.5 px-4">
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 block"><?= $t['formatted_expiry'] ?? '-' ?></span>
                                            
                                            <?php if ($rowStatus === 'suspended'): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 text-[10px] font-bold border border-rose-200 mt-1">
                                                    <i data-lucide="pause-circle" class="w-3 h-3"></i>
                                                    <span>সার্ভিস স্থগিত</span>
                                                </span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 text-[10px] font-bold border border-rose-200 mt-1">
                                                    <i data-lucide="alert-octagon" class="w-3 h-3"></i>
                                                    <span>মেয়াদোত্তীর্ণ (বিল বকেয়া)</span>
                                                </span>
                                            <?php elseif ($isExpToday): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-100 text-rose-900 text-[10px] font-black border border-rose-300 mt-1 animate-pulse">
                                                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                                    <span>আজকেই মেয়াদ শেষ!</span>
                                                </span>
                                            <?php elseif ($isExpSoon): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-orange-100 text-orange-900 text-[10px] font-bold border border-orange-200 mt-1">
                                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                                    <span><?= $daysLeft ?> দিন বাকি</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200 mt-1">
                                                    <i data-lucide="shield-check" class="w-3 h-3 text-[#00684A]"></i>
                                                    <span><?= $daysLeft ?> দিন বাকি</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- 5. Status Badge -->
                                    <td class="py-3.5 px-4">
                                        <?php if ($rowStatus === 'active'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 text-[11px] font-black border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#00684A] animate-pulse"></span>
                                                <span>সক্রিয় (Active)</span>
                                            </span>
                                        <?php elseif ($rowStatus === 'suspended'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[11px] font-black border border-rose-200">
                                                <span>স্থগিত</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-black border border-slate-200">
                                                <span><?= htmlspecialchars($rowStatus) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 6. Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end space-x-1.5">
                                            <!-- Grace Period Extension Button -->
                                            <button type="button" onclick="openGraceModal(<?= htmlspecialchars(json_encode($t)) ?>)"
                                                class="py-1 px-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-bold transition cursor-pointer flex items-center space-x-1"
                                                title="মার্চেন্টের অনুরোধে সাময়িক গ্রেস পিরিয়ড দিন">
                                                <i data-lucide="plus" class="w-3.5 h-3.5 text-amber-700"></i>
                                                <span>+দিন (গ্রেস)</span>
                                            </button>

                                            <!-- Monthly Bill Record Button -->
                                            <button type="button" onclick="openRenewalModal(<?= htmlspecialchars(json_encode($t)) ?>)"
                                                class="py-1 px-2.5 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 text-xs font-bold transition cursor-pointer flex items-center space-x-1"
                                                title="মাসিক বিল গ্রহণ ও সাইকেল রিনিউ করুন">
                                                <i data-lucide="credit-card" class="w-3.5 h-3.5 text-[#00684A]"></i>
                                                <span>বিল এন্ট্রি</span>
                                            </button>

                                            <!-- Suspend / Resume Toggle -->
                                            <?php if ($rowStatus === 'active'): ?>
                                                <button type="button" onclick="toggleStoreStatus(<?= $t['id'] ?>, 'suspended')"
                                                    class="p-1.5 rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer" title="সার্ভিস সাময়িক বন্ধ (Suspend) করুন">
                                                    <i data-lucide="pause-circle" class="w-4 h-4"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" onclick="toggleStoreStatus(<?= $t['id'] ?>, 'active')"
                                                    class="p-1.5 rounded-xl text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition cursor-pointer" title="সার্ভিস পুনরায় চালু (Activate) করুন">
                                                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Ledger History -->
                                            <button type="button" onclick="openLedgerModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')"
                                                class="p-1.5 rounded-xl text-slate-400 hover:text-[#00684A] hover:bg-emerald-50 transition cursor-pointer" title="পেমেন্ট লেজার ইতিহাস দেখুন">
                                                <i data-lucide="history" class="w-4 h-4"></i>
                                            </button>

                                            <!-- Delete -->
                                            <button type="button" onclick="deleteStore(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')"
                                                class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="স্টোর মুছে ফেলুন">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- =================================================================== -->
<!-- MODAL: STAGE 2 URL SETUP & ACTIVATE (সেটআপ পেন্ডিং ➔ সক্রিয়করণ)     -->
<!-- =================================================================== -->
<div id="setupModal" onclick="if(event.target === this) closeSetupModal()" class="fixed inset-0 w-full h-full bg-white/85 backdrop-blur-md z-[100] hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white border border-slate-200/90 rounded-3xl w-full max-w-lg p-5 sm:p-7 shadow-2xl relative max-h-[92vh] overflow-y-auto">
        <button type="button" onclick="closeSetupModal()" class="absolute top-4 right-4 w-7 h-7 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-sky-100 text-sky-800 flex items-center justify-center font-bold">
                <i data-lucide="globe" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">স্টোরফ্রন্ট ইউআরএল সেটআপ ও সক্রিয়করণ</h3>
                <p class="text-xs text-slate-400">লাইভ লিংক বসিয়ে স্টোরটি সক্রিয় করুন এবং বিলিং সাইকেল শুরু করুন</p>
            </div>
        </div>

        <!-- Store Overview -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 mb-4 space-y-2 text-xs">
            <div class="flex justify-between">
                <span class="text-slate-500">স্টোর নাম:</span>
                <strong class="text-slate-900 font-bold" id="setupStoreName">-</strong>
            </div>
            <div class="flex justify-between items-center bg-white p-2 rounded-xl border border-slate-200">
                <span class="text-slate-600 font-bold text-[11px]">Store UUID:</span>
                <div class="flex items-center space-x-1.5">
                    <code class="font-mono font-black text-[#00684A] text-[11px] selection:bg-emerald-100" id="setupUuid">-</code>
                    <button type="button" onclick="copyText(document.getElementById('setupUuid').textContent, 'Store UUID')" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-[#00684A] hover:text-white text-slate-700 text-[10px] font-bold transition flex items-center space-x-1 cursor-pointer shadow-2xs" title="UUID কপি করুন">
                        <i data-lucide="copy" class="w-3 h-3"></i>
                        <span>কপি</span>
                    </button>
                </div>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">স্টোর ওনার ও ফোন:</span>
                <span class="font-bold text-slate-800" id="setupOwner">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">প্যাকেজ ও ফি:</span>
                <span class="font-bold font-mono text-[#00684A]" id="setupPackage">-</span>
            </div>
            <div class="flex justify-between items-center pt-1 border-t border-slate-200/60">
                <span class="text-slate-500">যাচাইকৃত বিকাশ TrxID:</span>
                <span class="font-mono font-black text-emerald-800 bg-emerald-100/70 px-2 py-0.5 rounded" id="setupTrxId">-</span>
            </div>
        </div>

        <form id="setupForm" onsubmit="submitStoreSetup(event)" class="space-y-4">
            <input type="hidden" id="setupTenantId" name="id">

            <!-- Live Storefront URL -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    লাইভ স্টোরফ্রন্ট URL (ডোমেইন / ভার্সেল লিংক) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <i data-lucide="globe" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="url" id="setupDomain" name="domain" required placeholder="https://mybrand.vercel.app"
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <span class="text-[10px] text-slate-400 mt-0.5 block">উদাহরণ: https://mystore.vercel.app অথবা https://mystore.com</span>
            </div>

            <!-- Client Admin URL -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    ক্লায়েন্ট স্টোর অ্যাডমিন প্যানেল URL
                </label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="url" id="setupAdminUrl" name="admin_url" placeholder="https://mybrand.vercel.app/admin"
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <span class="text-[10px] text-slate-400 mt-0.5 block">খালি রাখলে স্বয়ংক্রিয়ভাবে {domain}/admin হবে</span>
            </div>

            <!-- Billing Cycle Automation Note -->
            <div class="p-3 bg-emerald-50/70 border border-emerald-200/80 rounded-2xl flex items-start space-x-2.5 text-xs text-emerald-950">
                <i data-lucide="info" class="w-4 h-4 text-[#00684A] shrink-0 mt-0.5"></i>
                <div class="space-y-0.5">
                    <span class="font-bold">বিলিং সাইকেল স্বয়ংক্রিয় শুরু:</span>
                    <p class="text-[11px] text-emerald-900 leading-relaxed">
                        আজ সক্রিয় করার সাথে সাথে বিলিং সাইকেল গণনা শুরু হবে। আজকের দিনটিই (<strong class="font-mono"><?= date('j') ?> তারিখ</strong>) প্রতি মাসের স্থায়ী বিলিং ডেট হিসেবে সংরক্ষিত হবে এবং প্রাথমিক ১ মাসের মেয়াদ নির্ধারিত হবে।
                    </p>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeSetupModal()" class="py-2 px-4 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnSetupSubmit" class="py-2 px-5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer flex items-center space-x-1.5">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>সেটআপ সম্পন্ন ও সক্রিয় করুন</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL: GRACE PERIOD EXTENSION (+২ দিন, +৫ দিন, +১ সপ্তাহ)           -->
<!-- =================================================================== -->
<div id="graceModal" onclick="if(event.target === this) closeGraceModal()" class="fixed inset-0 w-full h-full bg-white/85 backdrop-blur-md z-[100] hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white border border-slate-200/90 rounded-3xl w-full max-w-md p-5 sm:p-6 shadow-2xl relative">
        <button type="button" onclick="closeGraceModal()" class="absolute top-4 right-4 w-7 h-7 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">সাময়িক গ্রেস পিরিয়ড বৃদ্ধি</h3>
                <p class="text-xs text-slate-400" id="graceStoreTitle">স্টোরের মেয়াদ বৃদ্ধি করুন</p>
            </div>
        </div>

        <!-- Strict Business Rule Notice -->
        <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-2xl mb-4 text-xs text-amber-950 space-y-1">
            <div class="flex items-center space-x-1.5 font-bold text-amber-900">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-700"></i>
                <span>নিয়মাবলী:</span>
            </div>
            <p class="text-[11px] text-amber-800 leading-relaxed">
                মার্চেন্টের অনুরোধে সাময়িক গ্রেস পিরিয়ড দিন। <strong>মূল মাসিক বিলিং তারিখ অপরিবর্তিত থাকবে</strong> এবং বর্ধিত সময় পার হলে যদি বিল না দেওয়া হয় তবে সার্ভিস স্বয়ংক্রিয়ভাবে স্থগিত (Suspended) হয়ে যাবে।
            </p>
        </div>

        <form id="graceForm" onsubmit="submitGraceExtension(event)" class="space-y-4">
            <input type="hidden" id="graceTenantId" name="id">

            <!-- Quick Preset Chips -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">কুইক ডে সিলেক্টর</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="setGraceDays(2, this)" class="grace-chip py-1.5 px-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-[#00684A] hover:text-[#00684A] transition cursor-pointer">
                        +২ দিন
                    </button>
                    <button type="button" onclick="setGraceDays(5, this)" class="grace-chip py-1.5 px-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-[#00684A] hover:text-[#00684A] transition cursor-pointer">
                        +৫ দিন
                    </button>
                    <button type="button" onclick="setGraceDays(7, this)" class="grace-chip py-1.5 px-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-[#00684A] hover:text-[#00684A] transition cursor-pointer">
                        +১ সপ্তাহ
                    </button>
                    <button type="button" onclick="setGraceDays(10, this)" class="grace-chip py-1.5 px-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-[#00684A] hover:text-[#00684A] transition cursor-pointer">
                        +১০ দিন
                    </button>
                </div>
            </div>

            <!-- Custom Days Input -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">বাড়তি দিনের সংখ্যা</label>
                <div class="relative">
                    <input type="number" id="graceDaysInput" name="days" value="5" min="1" max="60" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-[#00684A] focus:outline-none focus:border-[#00684A] transition">
                    <span class="text-xs text-slate-400 absolute right-3 top-1/2 -translate-y-1/2">দিন</span>
                </div>
            </div>

            <!-- Admin Note -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">অ্যাডমিন নোট / কারণ (ঐচ্ছিক)</label>
                <input type="text" id="graceNote" name="note" placeholder="যেমন: মার্চেন্টের অনুরোধে ৫ দিন বাড়ানো হলো"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeGraceModal()" class="py-2 px-4 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnGraceSubmit" class="py-2 px-5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-2xs transition cursor-pointer flex items-center space-x-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>গ্রেস পিরিয়ড বৃদ্ধি করুন</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL: RECORD MONTHLY RENEWAL PAYMENT (মাসিক বিল এন্ট্রি)           -->
<!-- =================================================================== -->
<div id="renewalModal" onclick="if(event.target === this) closeRenewalModal()" class="fixed inset-0 w-full h-full bg-white/85 backdrop-blur-md z-[100] hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white border border-slate-200/90 rounded-3xl w-full max-w-lg p-5 sm:p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto">
        <button type="button" onclick="closeRenewalModal()" class="absolute top-4 right-4 w-7 h-7 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">মাসিক বিল এন্ট্রি ও নবায়ন</h3>
                <p class="text-xs text-slate-400" id="renewalStoreTitle">পেমেন্ট গ্রহণ করে সাইকেল নবায়ন করুন</p>
            </div>
        </div>

        <form id="renewalForm" onsubmit="submitRenewalPayment(event)" class="space-y-3.5">
            <input type="hidden" id="renTenantId" name="id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">বিলিং মাস <span class="text-rose-500">*</span></label>
                    <input type="text" id="renBillingMonth" name="billing_month" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">পরিশোধিত ফি (৳) <span class="text-rose-500">*</span></label>
                    <input type="number" id="renAmount" name="amount" required min="1"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-[#00684A] focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">পেমেন্ট মেথড</label>
                    <select id="renMethod" name="payment_method" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition bg-white">
                        <option value="bKash" selected>bKash (বিকাশ)</option>
                        <option value="Nagad">Nagad (নগদ)</option>
                        <option value="Rocket">Rocket (রকেট)</option>
                        <option value="Bank">Bank Transfer</option>
                        <option value="Cash">নগদ গ্রহণ (Cash)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রেরকের ফোন নম্বর</label>
                    <input type="text" id="renSender" name="sender_number"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">লেনদেন নম্বর (TrxID) <span class="text-rose-500">*</span></label>
                <input type="text" id="renTrxId" name="trx_id" required placeholder="যেমন: TRX928A..."
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono uppercase text-slate-800 focus:outline-none focus:border-[#00684A] transition">
            </div>

            <!-- Auto-Calculated Next Due Date Preview -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">পরবর্তী বিলিং মেয়াদ (স্বয়ংক্রিয় নির্ধারিত)</label>
                <div class="relative">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="date" id="renNextDueDate" name="next_due_date"
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <span class="text-[10px] text-slate-400 mt-0.5 block">স্টোরের স্থায়ী বিলিং তারিখের ভিত্তিতে পরবর্তী মাসের সাইকেল নির্ধারিত</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">অ্যাডমিন নোট (ঐচ্ছিক)</label>
                <input type="text" id="renAdminNote" name="admin_note" placeholder="মাসিক বিল গ্রহণ"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeRenewalModal()" class="py-2 px-4 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnRenewalSubmit" class="py-2 px-5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer flex items-center space-x-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>বিল গ্রহণ ও রিনিউ সম্পন্ন করুন</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL: PAYMENT HISTORY LEDGER (পেমেন্ট লেজার ইতিহাস)                -->
<!-- =================================================================== -->
<div id="ledgerModal" onclick="if(event.target === this) closeLedgerModal()" class="fixed inset-0 w-full h-full bg-white/85 backdrop-blur-md z-[100] hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white border border-slate-200/90 rounded-3xl w-full max-w-2xl p-5 sm:p-7 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <button type="button" onclick="closeLedgerModal()" class="absolute top-4 right-4 w-7 h-7 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                <i data-lucide="file-text" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900" id="ledgerStoreTitle">পেমেন্ট ও বিলিং ইতিহাস</h3>
                <p class="text-xs text-slate-400">এই স্টোরের সকল লেনদেন ও গ্রেস পিরিয়ডের রেকর্ড</p>
            </div>
        </div>

        <div class="border border-slate-200/80 rounded-2xl overflow-hidden">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold uppercase text-slate-400">
                    <tr>
                        <th class="py-2.5 px-3">তারিখ ও সময়</th>
                        <th class="py-2.5 px-3">বিলিং মাস</th>
                        <th class="py-2.5 px-3">টাইপ</th>
                        <th class="py-2.5 px-3">পরিমাণ</th>
                        <th class="py-2.5 px-3">TrxID ও মেথড</th>
                        <th class="py-2.5 px-3">নোট</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="ledgerTableBody">
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">লোড হচ্ছে...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL: MANUAL CREATE STORE (নতুন স্টোর সরাসরি তৈরি)                -->
<!-- =================================================================== -->
<div id="createStoreModal" onclick="if(event.target === this) closeCreateStoreModal()" class="fixed inset-0 w-full h-full bg-white/85 backdrop-blur-md z-[100] hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white border border-slate-200/90 rounded-3xl w-full max-w-xl p-5 sm:p-7 shadow-2xl relative max-h-[92vh] overflow-y-auto">
        <button type="button" onclick="closeCreateStoreModal()" class="absolute top-4 right-4 w-7 h-7 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                <i data-lucide="store" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">নতুন মার্চেন্ট স্টোর তৈরি করুন</h3>
                <p class="text-xs text-slate-400">অ্যাডমিন কর্তৃক সরাসরি অনবোর্ড করুন</p>
            </div>
        </div>

        <form id="createStoreForm" onsubmit="submitCreateStore(event)" class="space-y-3.5" enctype="multipart/form-data">
            <!-- Store Name & Slug -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">স্টোরের নাম <span class="text-rose-500">*</span></label>
                    <input type="text" id="crName" name="name" required placeholder="যেমন: ফ্যাশন হাউস" oninput="autoGenerateSlug(this.value)"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">স্টোর স্লাগ (Unique) <span class="text-rose-500">*</span></label>
                    <input type="text" id="crSlug" name="slug" required placeholder="fashion-house"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <!-- Owner Info -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">মার্চেন্টের নাম <span class="text-rose-500">*</span></label>
                    <input type="text" id="crOwnerName" name="owner_name" required placeholder="নাম"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ফোন নম্বর <span class="text-rose-500">*</span></label>
                    <input type="tel" id="crOwnerPhone" name="owner_phone" required placeholder="017xxxxxxxx"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ইমেইল <span class="text-rose-500">*</span></label>
                    <input type="email" id="crOwnerEmail" name="owner_email" required placeholder="merchant@gmail.com"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">কাস্টমার পোর্টাল পাসওয়ার্ড</label>
                <input type="text" id="crPassword" name="password" value="123456" placeholder="ন্যূনতম ৬ ডিজিট"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
            </div>

            <!-- Package Selection & Fee -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্যাকেজ</label>
                    <select id="crPackage" name="package_slug" onchange="updatePackageDefaults()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition bg-white">
                        <?php foreach ($packages as $pkg): ?>
                            <?php $pkgName = $pkg['name_bn'] ?? $pkg['name_en'] ?? $pkg['slug']; ?>
                            <option value="<?= htmlspecialchars($pkg['slug']) ?>" data-fee="<?= $pkg['price'] ?>" data-name="<?= htmlspecialchars($pkgName) ?>">
                                <?= htmlspecialchars($pkgName) ?> - ৳<?= $pkg['price'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">মাসিক ফি (৳)</label>
                    <input type="number" id="crFee" name="monthly_fee" value="199" required min="1"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-[#00684A] focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <!-- Live Domain & Client Admin URL -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">লাইভ স্টোর URL (ঐচ্ছিক)</label>
                    <input type="url" id="crDomain" name="domain" placeholder="https://mybrand.vercel.app"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ক্লায়েন্ট অ্যাডমিন URL (ঐচ্ছিক)</label>
                    <input type="url" id="crAdminUrl" name="admin_url" placeholder="https://mybrand.vercel.app/admin"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <!-- Billing Day & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রতি মাসের বিলিং তারিখ</label>
                    <select id="crBillingDay" name="billing_day" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition bg-white">
                        <?php for ($d = 1; $d <= 28; $d++): ?>
                            <option value="<?= $d ?>" <?= $d === (int)date('j') ? 'selected' : '' ?>>প্রতি মাসের <?= $d ?> তারিখ</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রাথমিক স্ট্যাটাস</label>
                    <select id="crStatus" name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition bg-white">
                        <option value="active" selected>সরাসরি সক্রিয় (Active)</option>
                        <option value="setup_pending">সেটআপ পেন্ডিং</option>
                        <option value="payment_pending">পেমেন্ট পেন্ডিং</option>
                    </select>
                </div>
            </div>

            <!-- Logo Upload -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ব্র্যান্ড লোগো (ঐচ্ছিক)</label>
                <input type="file" id="crLogo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-600 focus:outline-none focus:border-[#00684A] transition">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeCreateStoreModal()" class="py-2 px-4 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnCreateStoreSubmit" class="py-2 px-5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer flex items-center space-x-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>স্টোর তৈরি ও সংরক্ষণ করুন</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let activePipelineTab = 'active';
    let currentActiveSubFilter = 'all';

    // Auto-select tab on load: If payment pending or setup pending exists, or URL hash
    document.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash.replace('#', '');
        const paymentPendingCount = <?= count($paymentPendingTenants) ?>;
        const setupPendingCount = <?= count($setupPendingTenants) ?>;

        if (['payment_pending', 'setup_pending', 'active'].includes(hash)) {
            switchPipelineTab(hash);
        } else if (paymentPendingCount > 0) {
            switchPipelineTab('payment_pending');
        } else if (setupPendingCount > 0) {
            switchPipelineTab('setup_pending');
        } else {
            switchPipelineTab('active');
        }
    });

    // ==========================================
    // 1. PIPELINE TAB SWITCHER
    // ==========================================
    function switchPipelineTab(tabName) {
        activePipelineTab = tabName;
        window.location.hash = tabName;

        // Hide all views
        document.querySelectorAll('.pipeline-view').forEach(view => view.classList.add('hidden'));
        
        // Show selected view
        const targetView = document.getElementById(`pipelineView_${tabName}`);
        if (targetView) targetView.classList.remove('hidden');

        // Style the active tab button
        document.querySelectorAll('.pipeline-tab').forEach(btn => {
            btn.className = 'pipeline-tab flex items-center justify-between p-3.5 rounded-xl border transition text-left cursor-pointer bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100/80';
        });

        const activeBtn = document.getElementById(`tabBtn_${tabName}`);
        if (activeBtn) {
            activeBtn.className = 'pipeline-tab flex items-center justify-between p-3.5 rounded-xl border transition text-left cursor-pointer bg-white border-[#00684A] text-slate-900 shadow-sm ring-2 ring-emerald-500/20';
        }

        if (window.lucide) lucide.createIcons();
    }

    // ==========================================
    // 2. STAGE 1: APPROVE PAYMENT (bKash TrxID)
    // ==========================================
    async function approvePayment(tenantId, storeName) {
        if (!confirm(`আপনি কি "${storeName}" স্টোরটির বিকাশ পেমেন্ট যাচাই করেছেন এবং অনুমোদন করতে চান?\n\nঅনুমোদনের পর এটি সরাসরি ২য় ধাপ (সেটআপ পেন্ডিং)-এ চলে যাবে।`)) {
            return;
        }

        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/approve-payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            if (res.success) {
                Toast.success(res.message || 'পেমেন্ট অনুমোদিত হয়েছে! স্টোরটি এখন সেটআপ পেন্ডিং-এ রয়েছে।');
                setTimeout(() => {
                    window.location.hash = 'setup_pending';
                    window.location.reload();
                }, 700);
            }
        } catch (err) {
            Toast.error(err.message || 'পেমেন্ট অনুমোদন ব্যর্থ হয়েছে।');
        }
    }

    // ==========================================
    // 3. STAGE 2: SETUP DOMAIN & ACTIVATE
    // ==========================================
    function openSetupModal(tenant) {
        document.getElementById('setupTenantId').value = tenant.id;
        document.getElementById('setupStoreName').innerText = tenant.name + ' (' + tenant.slug + ')';
        document.getElementById('setupUuid').innerText = tenant.uuid || '-';
        document.getElementById('setupOwner').innerText = tenant.owner_name + ' (' + (tenant.owner_phone || '-') + ')';
        document.getElementById('setupPackage').innerText = (tenant.package_name || 'স্ট্যান্ডার্ড প্যাকেজ') + ' - ৳' + tenant.monthly_fee + '/মাস';
        document.getElementById('setupTrxId').innerText = tenant.payment_trx_id || 'অনুমোদিত';

        // Suggest clean vercel subdomain or existing domain
        const defaultDomain = tenant.domain || `https://${tenant.slug}.vercel.app`;
        document.getElementById('setupDomain').value = defaultDomain;
        document.getElementById('setupAdminUrl').value = tenant.admin_url || `${defaultDomain}/admin`;

        document.getElementById('setupModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeSetupModal() {
        document.getElementById('setupModal').classList.add('hidden');
    }

    async function submitStoreSetup(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSetupSubmit');
        const tenantId = document.getElementById('setupTenantId').value;
        const domain = document.getElementById('setupDomain').value.trim();
        const admin_url = document.getElementById('setupAdminUrl').value.trim();

        if (!domain) {
            Toast.error('লাইভ স্টোরফ্রন্ট URL প্রদান করা আবশ্যক।');
            return;
        }

        setButtonLoading(btn, true, 'সক্রিয় হচ্ছে...');
        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/setup`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ domain, admin_url })
            });

            if (res.success) {
                Toast.success(res.message || 'স্টোর সফলভাবে লাইভ ও সক্রিয় হয়েছে!');
                closeSetupModal();
                setTimeout(() => {
                    window.location.hash = 'active';
                    window.location.reload();
                }, 700);
            }
        } catch (err) {
            Toast.error(err.message || 'সেটআপ ব্যর্থ হয়েছে।');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    // ==========================================
    // 4. STAGE 3: GRACE PERIOD EXTENSION (+দিন)
    // ==========================================
    function openGraceModal(tenant) {
        document.getElementById('graceTenantId').value = tenant.id;
        document.getElementById('graceStoreTitle').innerText = `${tenant.name} (স্থায়ী বিলিং ডেট: প্রতি মাসের ${tenant.billing_day} তারিখ)`;
        document.getElementById('graceDaysInput').value = '5';
        document.getElementById('graceNote').value = 'মার্চেন্টের অনুরোধে গ্রেস পিরিয়ড বাড়ানো হলো';

        // Reset chip styles
        document.querySelectorAll('.grace-chip').forEach(c => {
            c.classList.remove('bg-amber-100', 'border-amber-500', 'text-amber-950');
        });

        document.getElementById('graceModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeGraceModal() {
        document.getElementById('graceModal').classList.add('hidden');
    }

    function setGraceDays(days, btn) {
        document.getElementById('graceDaysInput').value = days;
        document.querySelectorAll('.grace-chip').forEach(c => {
            c.classList.remove('bg-amber-100', 'border-amber-500', 'text-amber-950');
        });
        if (btn) {
            btn.classList.add('bg-amber-100', 'border-amber-500', 'text-amber-950');
        }
    }

    async function submitGraceExtension(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGraceSubmit');
        const tenantId = document.getElementById('graceTenantId').value;
        const days = parseInt(document.getElementById('graceDaysInput').value || '0', 10);
        const note = document.getElementById('graceNote').value.trim();

        if (days <= 0) {
            Toast.error('অনুগ্রহ করে সঠিক দিনের সংখ্যা দিন।');
            return;
        }

        setButtonLoading(btn, true, 'সংরক্ষণ হচ্ছে...');
        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/extend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ days, note })
            });

            if (res.success) {
                Toast.success(res.message || `গ্রেস পিরিয়ড ${days} দিন বৃদ্ধি করা হয়েছে!`);
                closeGraceModal();
                setTimeout(() => window.location.reload(), 700);
            }
        } catch (err) {
            Toast.error(err.message || 'গ্রেস পিরিয়ড বাড়ানো সম্ভব হয়নি।');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    // ==========================================
    // 5. STAGE 3: MONTHLY RENEWAL PAYMENT
    // ==========================================
    function openRenewalModal(tenant) {
        document.getElementById('renTenantId').value = tenant.id;
        document.getElementById('renewalStoreTitle').innerText = `${tenant.name} - মাসিক বিলিং রিনিউয়াল`;
        document.getElementById('renAmount').value = tenant.monthly_fee || 199;
        document.getElementById('renSender').value = tenant.owner_phone || '';
        document.getElementById('renTrxId').value = '';

        // Bengali Month Names
        const bnMonths = ["জানুয়ারি", "ফেব্রুয়ারি", "মার্চ", "এপ্রিল", "মে", "জুন", "জুলাই", "আগস্ট", "সেপ্টেম্বর", "অক্টোবর", "নভেম্বর", "ডিসেম্বর"];
        const now = new Date();
        document.getElementById('renBillingMonth').value = `${bnMonths[now.getMonth()]} ${now.getFullYear()}`;

        // Calculate next month's billing date based on tenant's permanent billing_day
        const billingDay = parseInt(tenant.billing_day || '10', 10);
        const nextDate = new Date(now.getFullYear(), now.getMonth() + 1, billingDay);
        const yyyy = nextDate.getFullYear();
        const mm = String(nextDate.getMonth() + 1).padStart(2, '0');
        const dd = String(nextDate.getDate()).padStart(2, '0');
        document.getElementById('renNextDueDate').value = `${yyyy}-${mm}-${dd}`;

        document.getElementById('renewalModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeRenewalModal() {
        document.getElementById('renewalModal').classList.add('hidden');
    }

    async function submitRenewalPayment(e) {
        e.preventDefault();
        const btn = document.getElementById('btnRenewalSubmit');
        const tenantId = document.getElementById('renTenantId').value;
        const amount = document.getElementById('renAmount').value;
        const billing_month = document.getElementById('renBillingMonth').value;
        const payment_method = document.getElementById('renMethod').value;
        const sender_number = document.getElementById('renSender').value;
        const trx_id = document.getElementById('renTrxId').value.trim();
        const next_due_date = document.getElementById('renNextDueDate').value;
        const admin_note = document.getElementById('renAdminNote').value;

        if (!trx_id) {
            Toast.error('বিকাশ TrxID প্রদান করা আবশ্যক।');
            return;
        }

        setButtonLoading(btn, true, 'সংরক্ষণ হচ্ছে...');
        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/payments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount, billing_month, payment_method, sender_number, trx_id, next_due_date, admin_note })
            });

            if (res.success) {
                Toast.success(res.message || 'মাসিক বিল সফলভাবে রিনিউ হয়েছে!');
                closeRenewalModal();
                setTimeout(() => window.location.reload(), 700);
            }
        } catch (err) {
            Toast.error(err.message || 'পেমেন্ট সংরক্ষণ ব্যর্থ হয়েছে।');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    // ==========================================
    // 6. TOGGLE STATUS (SUSPEND / RESUME)
    // ==========================================
    async function toggleStoreStatus(tenantId, newStatus) {
        const actionLabel = (newStatus === 'suspended') ? 'সাময়িক স্থগিত (Suspend)' : 'পুনরায় চালু (Activate)';
        if (!confirm(`আপনি কি এই স্টোরটি ${actionLabel} করতে চান?`)) return;

        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });

            if (res.success) {
                Toast.success(res.message || 'স্ট্যাটাস সফলভাবে আপডেট হয়েছে!');
                setTimeout(() => window.location.reload(), 600);
            }
        } catch (err) {
            Toast.error(err.message || 'স্ট্যাটাস আপডেট ব্যর্থ হয়েছে।');
        }
    }

    // ==========================================
    // 7. PAYMENT LEDGER HISTORY MODAL
    // ==========================================
    async function openLedgerModal(tenantId, storeName) {
        document.getElementById('ledgerStoreTitle').innerText = `${storeName} - সকল লেনদেন ও বিলিং ইতিহাস`;
        const tbody = document.getElementById('ledgerTableBody');
        tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-slate-400">লোড হচ্ছে...</td></tr>`;
        document.getElementById('ledgerModal').classList.remove('hidden');

        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/payments`);
            if (res.success && res.data) {
                const payments = res.data;
                if (payments.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-slate-400">এখনো কোনো পেমেন্ট রেকর্ড নেই</td></tr>`;
                } else {
                    tbody.innerHTML = payments.map(p => `
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-600">${p.created_at || '-'}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-800">${p.billing_month || '-'}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold ${p.payment_type === 'initial' ? 'bg-indigo-50 text-indigo-700' : (p.payment_type === 'extension' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800')}">
                                    ${p.payment_type === 'initial' ? 'প্রাথমিক' : (p.payment_type === 'extension' ? 'গ্রেস এক্সটেনশন' : 'মাসিক নবায়ন')}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-[#00684A]">৳ ${p.amount}</td>
                            <td class="py-2.5 px-3">
                                <strong class="font-mono text-slate-900 block">${p.trx_id || '-'}</strong>
                                <span class="text-[10px] text-slate-400">${p.payment_method || 'bKash'} (${p.sender_number || '-'})</span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">${p.admin_note || '-'}</td>
                        </tr>
                    `).join('');
                }
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-rose-500">লোড করতে সমস্যা হয়েছে: ${err.message}</td></tr>`;
        }
    }

    function closeLedgerModal() {
        document.getElementById('ledgerModal').classList.add('hidden');
    }

    // ==========================================
    // 8. ACTIVE SUB-FILTER & SEARCH
    // ==========================================
    function filterActiveStores(filter) {
        currentActiveSubFilter = filter;

        // Update active filter pill style
        document.querySelectorAll('.active-filter-pill').forEach(pill => {
            if (pill.dataset.sub === filter) {
                pill.className = 'active-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-[#00684A] text-white shadow-2xs cursor-pointer shrink-0';
            } else {
                pill.className = 'active-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0';
            }
        });

        applyActiveFilters();
    }

    function handleActiveSearch(query) {
        applyActiveFilters();
    }

    function applyActiveFilters() {
        const query = (document.getElementById('activeSearchInput')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.active-tenant-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const status = row.dataset.status;
            const isToday = row.dataset.today === '1';
            const isSoon = row.dataset.soon === '1';
            const isOverdue = row.dataset.overdue === '1';
            const text = row.dataset.search || '';

            let matchesSub = false;
            if (currentActiveSubFilter === 'all') matchesSub = true;
            else if (currentActiveSubFilter === 'expiring_today') matchesSub = isToday;
            else if (currentActiveSubFilter === 'expiring_soon') matchesSub = isSoon;
            else if (currentActiveSubFilter === 'suspended') matchesSub = isOverdue;

            const matchesSearch = (!query || text.includes(query));

            if (matchesSub && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('emptyActiveRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    // ==========================================
    // 9. MANUAL CREATE STORE HANDLERS
    // ==========================================
    function openCreateStoreModal() {
        document.getElementById('createStoreForm').reset();
        document.getElementById('createStoreModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeCreateStoreModal() {
        document.getElementById('createStoreModal').classList.add('hidden');
    }

    function autoGenerateSlug(name) {
        const slug = name.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        document.getElementById('crSlug').value = slug;
    }

    function updatePackageDefaults() {
        const sel = document.getElementById('crPackage');
        const opt = sel.options[sel.selectedIndex];
        if (opt) {
            document.getElementById('crFee').value = opt.dataset.fee || 199;
        }
    }

    async function submitCreateStore(e) {
        e.preventDefault();
        const form = document.getElementById('createStoreForm');
        const formData = new FormData(form);
        const btn = document.getElementById('btnCreateStoreSubmit');

        setButtonLoading(btn, true, 'তৈরি হচ্ছে...');
        try {
            const res = await apiFetch('../api/admin/tenants', {
                method: 'POST',
                body: formData
            });

            if (res.success) {
                Toast.success(res.message || 'স্টোর সফলভাবে তৈরি হয়েছে!');
                closeCreateStoreModal();
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (err) {
            Toast.error(err.message || 'স্টোর তৈরি ব্যর্থ হয়েছে।');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    // ==========================================
    // 10. DELETE STORE
    // ==========================================
    async function deleteStore(id, name) {
        if (!confirm(`আপনি কি নিশ্চিত যে "${name}" স্টোরটি মুছে ফেলতে চান? এর সমস্ত পেমেন্ট রেকর্ডও মুছে যাবে।`)) {
            return;
        }

        try {
            const res = await apiFetch(`../api/admin/tenants/${id}`, { method: 'DELETE' });
            if (res.success) {
                Toast.success(res.message || 'স্টোর মুছে ফেলা হয়েছে।');
                const row = document.getElementById(`tenant-row-${id}`);
                if (row) row.remove();
                setTimeout(() => window.location.reload(), 600);
            }
        } catch (err) {
            Toast.error(err.message || 'মুছে ফেলতে সমস্যা হয়েছে।');
        }
    }

    // Helper loading indicator
    function setButtonLoading(btn, isLoading, text = 'অপেক্ষা করুন...') {
        if (!btn) return;
        if (isLoading) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.innerHTML = `<span class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span> <span>${text}</span>`;
        } else {
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
            if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
        }
    }
</script>
