<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Payment Pending Storefronts
 * 
 * Stage 1: Checkout applications awaiting bKash TrxID verification.
 * Approving payment transitions store to Stage 2: Setup Pending (setup_tenants.php).
 */

use App\Models\Tenant;

$tenantModel = new Tenant();
$tenants = $tenantModel->getAll('payment_pending');
$totalPending = count($tenants);
?>
<div class="space-y-6">

    <!-- Top Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                <span>ধাপ ১: পেমেন্ট ভেরিফিকেশন</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">পেমেন্ট পেন্ডিং স্টোরফ্রন্ট</h2>
            <p class="text-xs text-slate-500 mt-1">মার্চেন্টদের নতুন চেকআউট আবেদন ও বিকাশ ট্রানজেকশন আইডি (TrxID) যাচাই করে অনুমোদন করুন</p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-black bg-amber-100 text-amber-900 border border-amber-200 shadow-2xs">
                মোট অপেক্ষমাণ: <?= $totalPending ?> টি
            </span>
        </div>
    </div>

    <!-- Info Notice -->
    <div class="bg-amber-50/70 border border-amber-200/90 p-4 rounded-2xl flex items-start space-x-3 text-xs text-amber-950">
        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">
            <i data-lucide="info" class="w-4 h-4"></i>
        </div>
        <div class="space-y-0.5 min-w-0">
            <h4 class="font-bold text-amber-900">পেমেন্ট যাচাইয়ের নিয়মাবলী:</h4>
            <p class="text-[11px] text-amber-800 leading-relaxed">
                মার্চেন্টের বিকাশ ট্রানজেকশন আইডি (TrxID) এবং প্রেরক নম্বরটি আপনার বিকাশ স্টেটমেন্টের সাথে মিলিয়ে নিন। সবকিছু ঠিক থাকলে <strong>"পেমেন্ট অনুমোদন করুন"</strong> বাটনে ক্লিক করুন। অনুমোদনের পর স্টোরটি স্বয়ংক্রিয়ভাবে <strong>"সেটআপ পেন্ডিং"</strong> পেজে স্থানান্তরিত হবে, যেখানে ডোমেইন বা ভার্সেল লিংক বসিয়ে সক্রিয় করা যাবে।
            </p>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex items-center justify-between gap-3">
        <div class="relative w-full sm:w-80">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="pendingSearch" oninput="searchPending(this.value)" placeholder="স্টোর নাম, ফোন বা TrxID লিখুন..."
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00684A] focus:bg-white transition">
        </div>
        <span class="text-xs text-slate-400 font-mono hidden sm:inline" id="searchMatchCount">প্রদর্শিত: <?= $totalPending ?> টি</span>
    </div>

    <!-- Payment Pending Table -->
    <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 sm:px-5">স্টোর ও লোগো</th>
                        <th class="py-3.5 px-4">মার্চেন্ট ও যোগাযোগ</th>
                        <th class="py-3.5 px-4">প্যাকেজ ও মাসিক ফি</th>
                        <th class="py-3.5 px-4">বিকাশ TrxID ও প্রেরক</th>
                        <th class="py-3.5 px-4">আবেদনের সময়</th>
                        <th class="py-3.5 px-4 text-right">পদক্ষেপ (Action)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="pendingTableBody">
                    <?php if (empty($tenants)): ?>
                        <tr id="emptyPendingRow">
                            <td colspan="6" class="py-14 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-2">
                                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                                </div>
                                <span class="font-bold text-slate-700 text-sm">বর্তমানে কোনো পেমেন্ট পেন্ডিং আবেদন নেই</span>
                                <p class="text-xs text-slate-400 mt-0.5">নতুন কোনো মার্চেন্ট চেকআউট করলে এখানে তালিকাভুক্ত হবে।</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tenants as $t): ?>
                            <tr class="hover:bg-amber-50/20 transition pending-row"
                                id="tenant-row-<?= $t['id'] ?>"
                                data-search="<?= strtolower(htmlspecialchars($t['name'] . ' ' . $t['slug'] . ' ' . $t['owner_name'] . ' ' . ($t['owner_phone'] ?? '') . ' ' . ($t['payment_trx_id'] ?? ''))) ?>">

                                <!-- Store & Logo -->
                                <td class="py-3.5 px-4 sm:px-5">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                            <?php if (!empty($t['logo_url'])): ?>
                                                <img src="<?= htmlspecialchars($t['logo_url']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="w-full h-full object-contain p-1" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                                <i data-lucide="store" class="w-5 h-5 text-amber-700 hidden"></i>
                                            <?php else: ?>
                                                <i data-lucide="store" class="w-5 h-5 text-amber-700"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-black text-slate-900 block truncate text-xs sm:text-sm"><?= htmlspecialchars($t['name']) ?></span>
                                                <?php if (($t['source'] ?? 'website') === 'admin'): ?>
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200" title="অ্যাডমিন নিজে তৈরি করেছেন">
                                                        <i data-lucide="user-check" class="w-2.5 h-2.5"></i>
                                                        <span>অ্যাডমিন তৈরি</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-sky-50 text-sky-700 border border-sky-200" title="ওয়েবসাইট চেকআউট অর্ডার">
                                                        <i data-lucide="globe" class="w-2.5 h-2.5"></i>
                                                        <span>অনলাইন অর্ডার</span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-mono block">slug: <?= htmlspecialchars($t['slug']) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Merchant & Contact -->
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
                                        <span class="text-[10px] text-slate-400 block truncate"><?= htmlspecialchars($t['owner_email']) ?></span>
                                    </div>
                                </td>

                                <!-- Package & Fee -->
                                <td class="py-3.5 px-4">
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-800 block"><?= htmlspecialchars($t['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ') ?></span>
                                        <span class="font-mono font-black text-[#00684A] text-xs">৳ <?= number_format((float)$t['monthly_fee']) ?> /মাস</span>
                                    </div>
                                </td>

                                <!-- bKash TrxID & Sender -->
                                <td class="py-3.5 px-4">
                                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-950 px-2.5 py-1 rounded-xl">
                                        <span class="font-mono font-black text-xs tracking-wider"><?= htmlspecialchars($t['payment_trx_id'] ?? '-') ?></span>
                                        <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($t['payment_trx_id'] ?? '') ?>'); Toast.success('TrxID কপি করা হয়েছে!')"
                                            class="text-emerald-700 hover:text-emerald-950 transition cursor-pointer" title="কপি করুন">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-mono block mt-1">প্রেরক: <?= htmlspecialchars($t['payment_sender'] ?? $t['owner_phone'] ?? '-') ?></span>
                                </td>

                                <!-- Time -->
                                <td class="py-3.5 px-4">
                                    <span class="text-xs text-slate-600 block"><?= $t['formatted_created'] ?? date('d M, Y', strtotime($t['created_at'])) ?></span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 text-[10px] font-bold border border-amber-200 mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>পেমেন্ট যাচাই বাকি</span>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button type="button" onclick="approvePayment(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')"
                                            class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>পেমেন্ট অনুমোদন করুন</span>
                                        </button>
                                        <button type="button" onclick="deleteStore(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')"
                                            class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="আবেদন বাতিল করুন">
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

<script>
    async function approvePayment(tenantId, storeName) {
        if (!confirm(`আপনি কি নিশ্চিত যে "${storeName}" স্টোরের বিকাশ পেমেন্ট যাচাই করেছেন এবং অনুমোদন করতে চান?\n\nঅনুমোদনের পর স্টোরটি সরাসরি "সেটআপ পেন্ডিং" পেজে স্থানান্তরিত হবে।`)) {
            return;
        }

        try {
            const res = await apiFetch(`../api/admin/tenants/${tenantId}/approve-payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            if (res.success) {
                Toast.success(res.message || 'পেমেন্ট অনুমোদিত হয়েছে! সেটআপ পেন্ডিং পেজে পাঠানো হচ্ছে...');
                setTimeout(() => {
                    window.location.href = 'index.php?page=setup_tenants';
                }, 700);
            }
        } catch (err) {
            Toast.error(err.message || 'পেমেন্ট অনুমোদন ব্যর্থ হয়েছে।');
        }
    }

    async function deleteStore(id, name) {
        if (!confirm(`আপনি কি নিশ্চিত যে "${name}" স্টোরের আবেদনটি বাতিল ও মুছে ফেলতে চান?`)) {
            return;
        }

        try {
            const res = await apiFetch(`../api/admin/tenants/${id}`, { method: 'DELETE' });
            if (res.success) {
                Toast.success(res.message || 'আবেদন বাতিল করা হয়েছে।');
                const row = document.getElementById(`tenant-row-${id}`);
                if (row) row.remove();
                setTimeout(() => window.location.reload(), 600);
            }
        } catch (err) {
            Toast.error(err.message || 'মুছে ফেলতে সমস্যা হয়েছে।');
        }
    }

    function searchPending(query) {
        query = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.pending-row');
        let count = 0;

        rows.forEach(r => {
            const text = r.dataset.search || '';
            if (!query || text.includes(query)) {
                r.style.display = '';
                count++;
            } else {
                r.style.display = 'none';
            }
        });

        const counter = document.getElementById('searchMatchCount');
        if (counter) counter.innerText = `প্রদর্শিত: ${count} টি`;
    }
</script>
