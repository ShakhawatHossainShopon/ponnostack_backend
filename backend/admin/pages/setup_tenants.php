<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Setup Pending Storefronts
 * 
 * Stage 2: Payment verified stores awaiting domain / Vercel URL assignment.
 * Assigning URL activates the store and starts its monthly billing cycle from today.
 * Transitions store to Stage 3: All Active Storefronts (active_tenants.php).
 */

use App\Models\Tenant;

$tenantModel = new Tenant();
$tenants = $tenantModel->getAll('setup_pending');
$totalPending = count($tenants);
?>
<div class="space-y-6">

    <!-- Top Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                <span>ধাপ ২: ডোমেইন ও ইউআরএল বরাদ্দ</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">সেটআপ পেন্ডিং স্টোরফ্রন্ট</h2>
            <p class="text-xs text-slate-500 mt-1">পেমেন্ট যাচাই সম্পন্ন হওয়া স্টোরগুলোতে লাইভ ডোমেইন বা ভার্সেল লিংক বরাদ্দ দিয়ে সক্রিয় করুন</p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-black bg-sky-100 text-sky-900 border border-sky-200 shadow-2xs">
                মোট সেটআপ বাকি: <?= $totalPending ?> টি
            </span>
        </div>
    </div>

    <!-- Info Notice -->
    <div class="bg-sky-50/70 border border-sky-200/90 p-4 rounded-2xl flex items-start space-x-3 text-xs text-sky-950">
        <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">
            <i data-lucide="info" class="w-4 h-4"></i>
        </div>
        <div class="space-y-0.5 min-w-0">
            <h4 class="font-bold text-sky-900">সেটআপ ও সক্রিয়করণের কার্যপদ্ধতি:</h4>
            <p class="text-[11px] text-sky-800 leading-relaxed">
                এই স্টোরগুলোর বিকাশ পেমেন্ট ইতোমধ্যে সফলভাবে কনফার্ম করা হয়েছে। <strong>"ইউআরএল সেটআপ ও সক্রিয় করুন"</strong> বাটনে ক্লিক করে স্টোরের লাইভ লিংক (যেমন: <code>https://mystore.vercel.app</code> বা কাস্টম ডোমেইন) বসিয়ে সক্রিয় করে দিন। সক্রিয় করার সাথে সাথে কাস্টমার পোর্টালে স্টোর ও অ্যাডমিন লিংক উন্মুক্ত হবে এবং স্টোরটি সরাসরি <strong>"অল অ্যাক্টিভ স্টোরফ্রন্ট"</strong> পেজে স্থানান্তরিত হবে।
            </p>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex items-center justify-between gap-3">
        <div class="relative w-full sm:w-80">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="setupSearch" oninput="searchSetup(this.value)" placeholder="স্টোর নাম, ফোন বা স্লাগ লিখুন..."
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00684A] focus:bg-white transition">
        </div>
        <span class="text-xs text-slate-400 font-mono hidden sm:inline" id="setupMatchCount">প্রদর্শিত: <?= $totalPending ?> টি</span>
    </div>

    <!-- Setup Pending Table -->
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
                <tbody class="divide-y divide-slate-100" id="setupTableBody">
                    <?php if (empty($tenants)): ?>
                        <tr id="emptySetupRow">
                            <td colspan="5" class="py-14 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-2">
                                    <i data-lucide="globe" class="w-6 h-6"></i>
                                </div>
                                <span class="font-bold text-slate-700 text-sm">বর্তমানে কোনো সেটআপ পেন্ডিং স্টোর নেই</span>
                                <p class="text-xs text-slate-400 mt-0.5">সব স্টোর ডোমেইন বরাদ্দ পেয়ে অল অ্যাক্টিভ তালিকায় লাইভ রয়েছে।</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tenants as $t): ?>
                            <tr class="hover:bg-sky-50/20 transition setup-row"
                                id="tenant-row-<?= $t['id'] ?>"
                                data-search="<?= strtolower(htmlspecialchars($t['name'] . ' ' . $t['slug'] . ' ' . $t['owner_name'] . ' ' . ($t['owner_phone'] ?? ''))) ?>">

                                <!-- Store & Logo -->
                                <td class="py-3.5 px-4 sm:px-5">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                            <?php if (!empty($t['logo_url'])): ?>
                                                <img src="<?= htmlspecialchars($t['logo_url']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="w-full h-full object-contain p-1" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                                <i data-lucide="store" class="w-5 h-5 text-sky-700 hidden"></i>
                                            <?php else: ?>
                                                <i data-lucide="store" class="w-5 h-5 text-sky-700"></i>
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
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <code class="text-[10px] text-slate-500 font-mono font-bold bg-slate-100 px-1.5 py-0.5 rounded truncate max-w-[140px]" title="<?= htmlspecialchars($t['uuid']) ?>"><?= htmlspecialchars($t['uuid']) ?></code>
                                                <button type="button" onclick="copyText('<?= htmlspecialchars($t['uuid']) ?>', 'Store UUID')" class="p-1 rounded hover:bg-sky-100 text-slate-400 hover:text-sky-700 transition cursor-pointer" title="UUID কপি করুন">
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
                                        class="inline-flex items-center space-x-1.5 py-2 px-4 rounded-xl bg-[#00684A] hover:bg-[#00523A] text-white text-xs font-bold shadow-brand-glow transition cursor-pointer">
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
<!-- MODAL: URL SETUP & ACTIVATE (Pure Light Frosted Glass)             -->
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
            <div class="flex justify-between">
                <span class="text-slate-500">মার্চেন্ট ও ফোন:</span>
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

<script>
    function openSetupModal(tenant) {
        document.getElementById('setupTenantId').value = tenant.id;
        document.getElementById('setupStoreName').innerText = tenant.name + ' (' + tenant.slug + ')';
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
                Toast.success(res.message || 'স্টোর সফলভাবে লাইভ ও সক্রিয় হয়েছে! অল অ্যাক্টিভ পেজে পাঠানো হচ্ছে...');
                closeSetupModal();
                setTimeout(() => {
                    window.location.href = 'index.php?page=active_tenants';
                }, 700);
            }
        } catch (err) {
            Toast.error(err.message || 'সেটআপ ব্যর্থ হয়েছে।');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    function searchSetup(query) {
        query = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.setup-row');
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

        const counter = document.getElementById('setupMatchCount');
        if (counter) counter.innerText = `প্রদর্শিত: ${count} টি`;
    }

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
