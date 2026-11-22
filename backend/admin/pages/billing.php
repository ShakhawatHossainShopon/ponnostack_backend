<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Monthly Billing & Invoices Management
 * 
 * Manages recurring monthly billing, subscription renewal approvals,
 * automated invoices, manual offline collections, and payment history.
 */

use App\Models\TenantPayment;
use App\Models\Tenant;

$paymentModel = new TenantPayment();
$tenantModel = new Tenant();

$payments = $paymentModel->getAll();
$stats = $paymentModel->getBillingStats();
$tenantStats = $tenantModel->getStats();
$allTenants = $tenantModel->getAll();
$activeTenants = array_values(array_filter($allTenants, fn($t) => in_array($t['status'], ['active', 'expired', 'suspended'], true)));
?>
<div class="space-y-6">

    <!-- Top Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-800 border border-indigo-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                <span>মাসিক বিল ও ইনভয়েস ম্যানেজমেন্ট</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">মাসিক বিল ও ইনভয়েস</h2>
            <p class="text-xs text-slate-500 mt-1">মার্চেন্টদের মাসিক সাবস্ক্রিপশন বিল যাচাই, নবায়ন অনুমোদন, স্বয়ংক্রিয় ইনভয়েস তৈরি ও অফলাইন বিল সংগ্রহ</p>
        </div>

        <button type="button" onclick="openManualBillModal()"
            class="inline-flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl bg-[#00684A] hover:bg-[#00523A] active:scale-95 text-white text-xs font-bold shadow-brand-glow transition cursor-pointer self-start sm:self-auto">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>অফলাইন বিল এন্ট্রি করুন</span>
        </button>
    </div>

    <!-- Billing KPI Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Total Collected -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-emerald-200/90 bg-emerald-50/20 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">সর্বমোট সংগৃহীত বিল</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900 font-mono" id="statTotalCollected">৳ <?= number_format((float)$stats['total_collected']) ?></div>
            <span class="text-[10px] text-emerald-700 font-semibold mt-0.5 block">সকল অনুমোদিত পেমেন্ট</span>
        </div>

        <!-- Pending Renewals -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-amber-200/90 bg-amber-50/20 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">অপেক্ষমাণ নবায়ন বিল</span>
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                    <i data-lucide="hourglass" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-amber-900" id="statPendingRenewals"><?= $stats['pending_renewals'] ?></div>
            <span class="text-[10px] text-amber-700 font-semibold mt-0.5 block">মার্চেন্ট কর্তৃক পোর্টালে জমাকৃত</span>
        </div>

        <!-- Monthly Collected (Current Month) -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-indigo-200/90 bg-indigo-50/20 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-indigo-800 uppercase tracking-wider">চলতি মাসের সংগ্রহ</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-indigo-900 font-mono" id="statMonthlyCollected">৳ <?= number_format((float)$stats['monthly_collected']) ?></div>
            <span class="text-[10px] text-indigo-700 font-semibold mt-0.5 block"><?= date('F Y') ?></span>
        </div>

        <!-- Total Paid Invoices -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200/90 bg-slate-50 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">পরিশোধিত ইনভয়েস</span>
                <div class="w-8 h-8 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold">
                    <i data-lucide="file-check-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900" id="statPaidCount"><?= $stats['initial_count'] + $stats['renewal_count'] ?></div>
            <span class="text-[10px] text-slate-500 font-semibold mt-0.5 block">নতুন ও নবায়ন মিলিয়ে</span>
        </div>

        <!-- Overdue / Expired Stores -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-rose-200/90 bg-rose-50/20 shadow-2xs col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">বকেয়া বিল (ওভারডিউ)</span>
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center font-bold">
                    <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-rose-900" id="statOverdueStores"><?= $tenantStats['overdue'] ?></div>
            <span class="text-[10px] text-rose-700 font-semibold mt-0.5 block">মেয়াদ উত্তীর্ণ স্টোরফ্রন্ট</span>
        </div>
    </div>

    <!-- Filter Pills & Search Bar -->
    <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
        <!-- Sub Filter Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none" id="billingFilterPills">
            <button type="button" onclick="filterBilling('all')" class="billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-[#00684A] text-white shadow-2xs cursor-pointer shrink-0" data-filter="all">
                সব বিল ও ইনভয়েস (<?= count($payments) ?>)
            </button>
            <button type="button" onclick="filterBilling('pending')" class="billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-filter="pending">
                অপেক্ষমাণ বিল (<?= $stats['total_pending'] ?>)
            </button>
            <button type="button" onclick="filterBilling('approved')" class="billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-filter="approved">
                পরিশোধিত (Paid) (<?= $stats['initial_count'] + $stats['renewal_count'] ?>)
            </button>
            <button type="button" onclick="filterBilling('renewal')" class="billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-filter="renewal">
                মাসিক রিনিউয়াল (<?= $stats['renewal_count'] ?>)
            </button>
            <button type="button" onclick="filterBilling('initial')" class="billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0" data-filter="initial">
                নতুন সাবস্ক্রিপশন (<?= $stats['initial_count'] ?>)
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full md:w-80 shrink-0">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="billingSearchInput" oninput="applyBillingFilters()" placeholder="ইনভয়েস নং, স্টোর, ফোন বা TrxID..."
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00684A] focus:bg-white transition">
        </div>
    </div>

    <!-- Payments & Invoices Master Table -->
    <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 sm:px-5">ইনভয়েস নং ও তারিখ</th>
                        <th class="py-3.5 px-4">স্টোরফ্রন্ট ও উৎস</th>
                        <th class="py-3.5 px-4">পেমেন্ট ধরন</th>
                        <th class="py-3.5 px-4">বিলিং মাস ও মেথড</th>
                        <th class="py-3.5 px-4">TrxID ও প্রেরক</th>
                        <th class="py-3.5 px-4">পরিমাণ</th>
                        <th class="py-3.5 px-4">স্ট্যাটাস</th>
                        <th class="py-3.5 px-4 text-right">পদক্ষেপ (Actions)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="billingTableBody">
                    <?php if (empty($payments)): ?>
                        <tr id="emptyBillingRow">
                            <td colspan="8" class="py-14 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-2">
                                    <i data-lucide="receipt" class="w-6 h-6"></i>
                                </div>
                                <span class="font-bold text-slate-600 text-sm">কোনো বিলিং অথবা ইনভয়েস রেকর্ড পাওয়া যায়নি</span>
                                <p class="text-xs text-slate-400 mt-0.5">মার্চেন্ট বিল পরিশোধ করলে বা অফলাইন বিল এন্ট্রি করলে এখানে প্রদর্শিত হবে।</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr class="hover:bg-slate-50/70 transition billing-row"
                                id="payment-row-<?= $p['id'] ?>"
                                data-status="<?= $p['status'] ?>"
                                data-type="<?= $p['payment_type'] ?>"
                                data-search="<?= strtolower(htmlspecialchars(($p['invoice_no'] ?? '') . ' ' . $p['store_name'] . ' ' . ($p['owner_name'] ?? '') . ' ' . ($p['sender_number'] ?? '') . ' ' . $p['trx_id'])) ?>">

                                <!-- 1. Invoice No & Date -->
                                <td class="py-3.5 px-4 sm:px-5">
                                    <div class="min-w-0">
                                        <button type="button" onclick="viewInvoice(<?= $p['id'] ?>)" 
                                            class="font-mono font-bold text-xs text-[#00684A] hover:underline flex items-center space-x-1 cursor-pointer">
                                            <span><?= htmlspecialchars($p['invoice_no']) ?></span>
                                            <i data-lucide="external-link" class="w-3 h-3"></i>
                                        </button>
                                        <span class="text-[10px] text-slate-400 font-mono block mt-0.5"><?= $p['formatted_date_short'] ?></span>
                                    </div>
                                </td>

                                <!-- 2. Store & Source Badge -->
                                <td class="py-3.5 px-4">
                                    <div class="min-w-0">
                                        <strong class="font-bold text-slate-900 block truncate"><?= htmlspecialchars($p['store_name']) ?></strong>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <!-- Source Badge -->
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold <?= $p['source_badge_cls'] ?>">
                                                <i data-lucide="<?= $p['source_icon'] ?>" class="w-2.5 h-2.5"></i>
                                                <span><?= $p['source_label'] ?></span>
                                            </span>
                                            <?php if (!empty($p['owner_phone'])): ?>
                                                <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($p['owner_phone']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Payment Type Badge (Differentiating new subscription vs monthly renewal) -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold <?= $p['type_badge_cls'] ?>">
                                        <i data-lucide="<?= $p['type_icon'] ?>" class="w-3 h-3"></i>
                                        <span><?= $p['type_label'] ?></span>
                                    </span>
                                </td>

                                <!-- 4. Billing Month & Method -->
                                <td class="py-3.5 px-4">
                                    <strong class="text-slate-800 text-xs block"><?= htmlspecialchars($p['billing_month'] ?? 'সাধারণ বিল') ?></strong>
                                    <span class="text-[10px] text-slate-400 block mt-0.5 font-medium"><?= htmlspecialchars($p['payment_method']) ?></span>
                                </td>

                                <!-- 5. TrxID & Sender -->
                                <td class="py-3.5 px-4">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-800 font-mono text-[11px] font-bold">
                                        <span><?= htmlspecialchars($p['trx_id']) ?></span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono block mt-0.5">প্রেরক: <?= htmlspecialchars($p['sender_number'] ?? '-') ?></span>
                                </td>

                                <!-- 6. Amount -->
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-black text-xs text-[#00684A]">৳ <?= number_format((float)$p['amount']) ?></span>
                                </td>

                                <!-- 7. Status -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $p['status_badge_cls'] ?>">
                                        <i data-lucide="<?= $p['status_icon'] ?>" class="w-3 h-3"></i>
                                        <span><?= $p['status_label'] ?></span>
                                    </span>
                                </td>

                                <!-- 8. Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <button type="button" onclick="approveBillingPayment(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['store_name'])) ?>', '<?= htmlspecialchars($p['billing_month'] ?? '') ?>')"
                                                class="inline-flex items-center space-x-1 py-1.5 px-3 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-[11px] font-bold shadow-brand-glow transition cursor-pointer" title="পেমেন্ট অনুমোদন করুন">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>অনুমোদন</span>
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" onclick="viewInvoice(<?= $p['id'] ?>)"
                                            class="p-1.5 rounded-xl border border-slate-200 text-slate-600 hover:text-[#00684A] hover:bg-emerald-50 hover:border-emerald-200 transition cursor-pointer" title="ইনভয়েস দেখুন ও প্রিন্ট করুন">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <button type="button" onclick="deleteBillingPayment(<?= $p['id'] ?>, '<?= htmlspecialchars($p['invoice_no']) ?>')"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="মুছে ফেলুন">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
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

<!-- ======================================================= -->
<!-- MODAL 1: OFFICIAL PRINTABLE INVOICE MODAL -->
<!-- ======================================================= -->
<div id="invoiceModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-white/85 backdrop-blur-md hidden transition-all duration-300">
    <div class="bg-white border border-slate-200/90 rounded-3xl max-w-2xl w-full max-h-[92vh] overflow-y-auto shadow-2xl p-5 sm:p-8 space-y-6 relative" onclick="event.stopPropagation()">
        
        <!-- Action Bar: Print & Close -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 print:hidden">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">অফিসিয়াল পেমেন্ট ইনভয়েস</h3>
                    <p class="text-[11px] text-slate-400 font-mono" id="invModalSubtitle">INV-0000</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button type="button" onclick="printInvoiceDoc()"
                    class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold transition shadow-brand-glow cursor-pointer">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>প্রিন্ট / PDF</span>
                </button>
                <button type="button" onclick="closeInvoiceModal()"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- INVOICE DOCUMENT BODY (PRINTABLE AREA) -->
        <div id="invoicePrintArea" class="space-y-6">
            <!-- Brand & Invoice Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                <div>
                    <img src="../assets/images/logo.png" alt="Ponnostack" class="h-8 w-auto object-contain mb-1.5" onerror="this.outerHTML='<span class=\'font-black text-xl text-[#00684A]\'>Ponnostack</span>'">
                    <p class="text-xs text-slate-500 font-medium">সম্পূর্ণ স্বয়ংক্রিয় মাল্টি-টেন্যান্ট ই-কমার্স প্ল্যাটফর্ম</p>
                    <span class="text-[11px] text-slate-400 block font-mono">ওয়েবসাইট: ponnostack.shop | ঢাকা, বাংলাদেশ</span>
                </div>

                <div class="sm:text-right">
                    <span class="text-xs font-black uppercase text-slate-400 tracking-wider block">INVOICE</span>
                    <strong class="text-base sm:text-lg font-black text-slate-900 font-mono block" id="invDocNo">INV-202611-0001</strong>
                    <span class="text-xs text-slate-500 font-mono block mt-1">ইস্যুর তারিখ: <span id="invDocDate">22 Nov, 2026</span></span>
                    <div class="mt-2" id="invStatusBadge">
                        <!-- Dynamic status stamp -->
                    </div>
                </div>
            </div>

            <!-- Bill To & Payment Summary Card -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">বিল গ্রহীতা (Bill To):</span>
                    <strong class="text-slate-900 text-sm block font-black" id="invStoreName">Store Name</strong>
                    <span class="text-slate-600 block" id="invOwnerName">Owner Name</span>
                    <span class="text-slate-500 font-mono block" id="invOwnerPhone">017XXXXXXXX</span>
                    <span class="text-slate-500 font-mono block" id="invOwnerEmail">owner@example.com</span>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">পেমেন্ট বিবরণ (Payment Details):</span>
                    <div class="space-y-1">
                        <div class="flex justify-between"><span class="text-slate-500">মেথড:</span> <strong class="font-bold text-slate-800" id="invMethod">bKash (Send Money)</strong></div>
                        <div class="flex justify-between"><span class="text-slate-500">প্রেরক নম্বর:</span> <span class="font-mono text-slate-800 font-bold" id="invSender">-</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">TrxID:</span> <span class="font-mono text-emerald-800 font-bold" id="invTrxId">-</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">বিলিং মাস:</span> <strong class="font-bold text-slate-800" id="invMonth">November 2026</strong></div>
                    </div>
                </div>
            </div>

            <!-- Itemized Table -->
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
                                <strong class="text-slate-900 font-bold block" id="invItemTitle">পণ্যস্ট্যাক ই-কমার্স স্টোরফ্রন্ট সাবস্ক্রিপশন ফি</strong>
                                <span class="text-[11px] text-slate-500" id="invItemDesc">বিলিং মাস: নভেম্বর ২০২৬</span>
                            </td>
                            <td class="py-3 px-4 text-slate-700 font-medium" id="invPackage">স্ট্যান্ডার্ড প্যাকেজ</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold" id="invTypeBadge">মাসিক রিনিউয়াল</span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900 text-sm" id="invAmount">৳ 199</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 text-xs">
                        <tr>
                            <td colspan="3" class="py-2.5 px-4 font-bold text-slate-700 text-right">মোট প্রদেয় বিল (Total Paid):</td>
                            <td class="py-2.5 px-4 text-right font-mono font-black text-base text-[#00684A]" id="invTotalAmount">৳ 199</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Seal & Terms -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-400 gap-2">
                <span>এটি একটি সিস্টেম জেনারেটেড ডিজিটাল ইনভয়েস, কোনো ম্যানুয়াল স্বাক্ষরের প্রয়োজন নেই।</span>
                <span class="font-mono text-emerald-700 font-bold">✓ VERIFIED BY PONNOSTACK BILLING</span>
            </div>
        </div>

    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL 2: MANUAL OFFLINE BILL COLLECTION MODAL -->
<!-- ======================================================= -->
<div id="manualBillModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-white/85 backdrop-blur-md hidden transition-all duration-300">
    <div class="bg-white border border-slate-200/90 rounded-3xl max-w-lg w-full shadow-2xl p-5 sm:p-7 space-y-4 relative" onclick="event.stopPropagation()">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-[#00684A] flex items-center justify-center font-bold">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">অফলাইন বিল এন্ট্রি</h3>
                    <p class="text-[11px] text-slate-400">ক্যাশ বা সরাসরি পেমেন্ট গ্রহণ করে মেয়াদ বৃদ্ধি করুন</p>
                </div>
            </div>
            <button type="button" onclick="closeManualBillModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="manualBillForm" onsubmit="submitManualBill(event)" class="space-y-3.5">
            <!-- Select Storefront -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">স্টোরফ্রন্ট নির্বাচন করুন <span class="text-rose-500">*</span></label>
                <select id="mbTenantId" name="tenant_id" required onchange="onManualStoreChange(this)"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-[#00684A] transition bg-white">
                    <option value="">-- স্টোর নির্বাচন করুন --</option>
                    <?php foreach ($activeTenants as $at): ?>
                        <option value="<?= $at['id'] ?>" data-fee="<?= $at['monthly_fee'] ?>" data-phone="<?= htmlspecialchars($at['owner_phone'] ?? '') ?>">
                            <?= htmlspecialchars($at['name']) ?> (ফি: ৳<?= $at['monthly_fee'] ?> | বিলিং ডেট: <?= $at['billing_day'] ?> তারিখ)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Billing Month & Amount -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">বিলিং মাস <span class="text-rose-500">*</span></label>
                    <input type="text" id="mbBillingMonth" name="billing_month" required value="<?= date('F Y') ?>"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">পরিমাণ (৳) <span class="text-rose-500">*</span></label>
                    <input type="number" id="mbAmount" name="amount" required min="1" value="200"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-[#00684A] focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <!-- Payment Method & TrxID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">পেমেন্ট মেথড</label>
                    <select id="mbPaymentMethod" name="payment_method" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition bg-white">
                        <option value="Cash" selected>ক্যাশ (Cash in Hand)</option>
                        <option value="bKash">bKash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">রশিদ নং / TrxID</label>
                    <input type="text" id="mbTrxId" name="trx_id" placeholder="CASH-<?= date('Ymd') ?>"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-[#00684A] transition">
                </div>
            </div>

            <!-- Admin Note -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">মন্তব্য (ঐচ্ছিক)</label>
                <input type="text" id="mbAdminNote" name="admin_note" placeholder="অফলাইন ক্যাশ গ্রহণ"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-[#00684A] transition">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeManualBillModal()" class="py-2 px-4 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnManualBillSubmit" class="py-2 px-5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer flex items-center space-x-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>বিল সংরক্ষণ ও মেয়াদ বৃদ্ধি করুন</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentBillingFilter = 'all';

    // Click outside backdrop to close modals
    ['invoiceModal', 'manualBillModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', (e) => {
                if (e.target === el) el.classList.add('hidden');
            });
        }
    });

    // ==========================================
    // 1. FILTER & SEARCH HANDLERS
    // ==========================================
    function filterBilling(filter) {
        currentBillingFilter = filter;

        document.querySelectorAll('.billing-filter-pill').forEach(pill => {
            if (pill.dataset.filter === filter) {
                pill.className = 'billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-[#00684A] text-white shadow-2xs cursor-pointer shrink-0';
            } else {
                pill.className = 'billing-filter-pill px-3.5 py-1.5 rounded-xl text-xs font-bold transition bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 cursor-pointer shrink-0';
            }
        });

        applyBillingFilters();
    }

    function applyBillingFilters() {
        const query = (document.getElementById('billingSearchInput')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.billing-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const status = row.dataset.status;
            const type = row.dataset.type;
            const text = row.dataset.search || '';

            let matchesFilter = false;
            if (currentBillingFilter === 'all') matchesFilter = true;
            else if (currentBillingFilter === 'pending') matchesFilter = (status === 'pending');
            else if (currentBillingFilter === 'approved') matchesFilter = (status === 'approved');
            else if (currentBillingFilter === 'renewal') matchesFilter = (type === 'renewal');
            else if (currentBillingFilter === 'initial') matchesFilter = (type === 'initial');

            const matchesSearch = (!query || text.includes(query));

            if (matchesFilter && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('emptyBillingRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    // ==========================================
    // 2. APPROVE RENEWAL PAYMENT
    // ==========================================
    async function approveBillingPayment(id, storeName, month) {
        if (!confirm(`আপনি কি "${storeName}" স্টোরের "${month}" মাসের সাবস্ক্রিপশন বিল অনুমোদন করতে চান?\n\nঅনুমোদনের পর স্টোরের মেয়াদ স্বয়ংক্রিয়ভাবে পরবর্তী বিলিং ডেট পর্যন্ত বৃদ্ধি পাবে।`)) {
            return;
        }

        try {
            const res = await apiFetch(`../api/admin/payments/${id}/approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ admin_note: 'অ্যাডমিন কর্তৃক মাসিক বিল অনুমোদিত' })
            });

            if (res.success) {
                Toast.success(res.message || 'পেমেন্ট অনুমোদিত হয়েছে এবং স্টোরের মেয়াদ বৃদ্ধি করা হয়েছে!');
                setTimeout(() => window.location.reload(), 700);
            }
        } catch (err) {
            Toast.error(err.message || 'পেমেন্ট অনুমোদন ব্যর্থ হয়েছে।');
        }
    }

    // ==========================================
    // 3. DELETE PAYMENT RECORD
    // ==========================================
    async function deleteBillingPayment(id, invoiceNo) {
        if (!confirm(`আপনি কি ইনভয়েস "${invoiceNo}" মুছে ফেলতে চান?`)) return;

        try {
            const res = await apiFetch(`../api/admin/payments/${id}`, {
                method: 'DELETE'
            });

            if (res.success) {
                Toast.success('পেমেন্ট রেকর্ড মুছে ফেলা হয়েছে!');
                const row = document.getElementById(`payment-row-${id}`);
                if (row) row.remove();
                applyBillingFilters();
            }
        } catch (err) {
            Toast.error(err.message || 'পেমেন্ট মুছে ফেলা সম্ভব হয়নি।');
        }
    }

    // ==========================================
    // 4. VIEW & PRINT INVOICE MODAL
    // ==========================================
    async function viewInvoice(id) {
        try {
            const res = await apiFetch(`../api/admin/payments/${id}/invoice`);
            if (res.success && res.data) {
                const inv = res.data;
                document.getElementById('invModalSubtitle').innerText = inv.invoice_no;
                document.getElementById('invDocNo').innerText = inv.invoice_no;
                document.getElementById('invDocDate').innerText = inv.formatted_date_short;
                document.getElementById('invStoreName').innerText = inv.store_name;
                document.getElementById('invOwnerName').innerText = inv.owner_name || 'গ্রাহক';
                document.getElementById('invOwnerPhone').innerText = inv.owner_phone || '-';
                document.getElementById('invOwnerEmail').innerText = inv.owner_email || '-';

                document.getElementById('invMethod').innerText = inv.payment_method;
                document.getElementById('invSender').innerText = inv.sender_number || '-';
                document.getElementById('invTrxId').innerText = inv.trx_id;
                document.getElementById('invMonth').innerText = inv.billing_month || 'সাধারণ সাবস্ক্রিপশন';

                document.getElementById('invPackage').innerText = inv.tenant_package || 'স্ট্যান্ডার্ড প্যাকেজ';
                document.getElementById('invItemDesc').innerText = 'বিলিং মাস: ' + (inv.billing_month || '-');
                document.getElementById('invAmount').innerText = '৳ ' + inv.amount;
                document.getElementById('invTotalAmount').innerText = '৳ ' + inv.amount;

                const typeBadge = document.getElementById('invTypeBadge');
                typeBadge.innerText = inv.type_label;
                typeBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold ' + inv.type_badge_cls;

                const statusStamp = document.getElementById('invStatusBadge');
                if (inv.status === 'approved') {
                    statusStamp.innerHTML = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-100 text-[#00684A] border border-emerald-300 font-black text-xs uppercase tracking-wider">✓ PAID / অনুমোদিত</span>`;
                } else {
                    statusStamp.innerHTML = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 font-bold text-xs uppercase tracking-wider">⏳ PENDING APPROVAL</span>`;
                }

                document.getElementById('invoiceModal').classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            }
        } catch (err) {
            Toast.error(err.message || 'ইনভয়েস লোড করা সম্ভব হয়নি।');
        }
    }

    function closeInvoiceModal() {
        document.getElementById('invoiceModal').classList.add('hidden');
    }

    function printInvoiceDoc() {
        window.print();
    }

    // ==========================================
    // 5. MANUAL OFFLINE BILL MODAL HANDLERS
    // ==========================================
    function openManualBillModal() {
        document.getElementById('manualBillForm').reset();
        document.getElementById('manualBillModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeManualBillModal() {
        document.getElementById('manualBillModal').classList.add('hidden');
    }

    function onManualStoreChange(sel) {
        const opt = sel.options[sel.selectedIndex];
        if (opt) {
            const fee = opt.getAttribute('data-fee');
            if (fee) document.getElementById('mbAmount').value = fee;
        }
    }

    async function submitManualBill(e) {
        e.preventDefault();
        const form = document.getElementById('manualBillForm');
        const formData = new FormData(form);
        const btn = document.getElementById('btnManualBillSubmit');

        setButtonLoading(btn, true, 'সংরক্ষণ হচ্ছে...');
        try {
            const payload = Object.fromEntries(formData.entries());
            const res = await apiFetch('../api/admin/payments/manual', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.success) {
                Toast.success(res.message || 'অফলাইন বিল সফলভাবে সংরক্ষিত হয়েছে!');
                closeManualBillModal();
                setTimeout(() => window.location.reload(), 700);
            }
        } catch (err) {
            Toast.error(err.message || 'বিল সংরক্ষণ ব্যর্থ হয়েছে।');
        } finally {
            setButtonLoading(btn, false);
        }
    }
</script>
