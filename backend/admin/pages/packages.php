<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Package Info & Pricing Management
 * 
 * Strict Design: Pure Light Mode, MongoDB Green (#00684A) Palette.
 * Fixed 3 Packages. Price (Taka) & Product Limit are editable. Zero Add / Delete buttons.
 */

use App\Models\Package;

$packageModel = new Package();
$packages = $packageModel->getAll();
?>
<div class="space-y-6">
    <!-- Top Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-[#00684A] border border-emerald-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#00ED64]"></span>
                <span>প্যাকেজ কন্ট্রোল</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">প্যাকেজ ইনফো (Package Info & Rates)</h2>
            <p class="text-xs text-slate-500 mt-1">হোমপেজের ৩টি প্যাকেজের মাসিক মূল্য (টাকা) এবং প্রোডাক্ট আপলোড সীমা নিয়ন্ত্রণ করুন। শুধুমাত্র এই দুটি মান পরিবর্তনযোগ্য।</p>
        </div>

        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-600 shrink-0 self-start sm:self-auto">
            <i data-lucide="shield-check" class="w-4 h-4 text-[#00684A]"></i>
            <span>৩টি স্থায়ী প্যাকেজ</span>
        </div>
    </div>

    <!-- 3 Pricing Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($packages as $pkg): ?>
            <?php 
                $isStarter    = ($pkg['slug'] === 'starter');
                $isPopular    = !empty($pkg['is_popular']);
                $pkgId        = (int)$pkg['id'];
                $price        = (int)$pkg['price'];
                $productLimit = (int)($pkg['product_limit'] ?? 0);
            ?>
            <div id="card-package-<?= $pkgId ?>" 
                 class="bg-white rounded-3xl p-6 sm:p-7 border <?= $isPopular ? 'border-[#00684A] shadow-md shadow-[#00684A]/10 ring-2 ring-[#00684A]/20' : 'border-slate-200/90 shadow-xs' ?> flex flex-col justify-between relative transition-all duration-300 hover:shadow-lg">
                
                <?php if ($isPopular): ?>
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center space-x-1 px-3 py-1 rounded-full bg-[#00684A] text-white text-[10px] font-black uppercase tracking-wider shadow-sm">
                            <i data-lucide="sparkles" class="w-3 h-3 text-[#00ED64]"></i>
                            <span><?= htmlspecialchars($pkg['badge_bn'] ?: 'সবচেয়ে জনপ্রিয়') ?></span>
                        </span>
                    </div>
                <?php endif; ?>

                <div>
                    <!-- Package Title & Slug -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-black text-slate-900 leading-tight">
                                <?= htmlspecialchars($pkg['name_bn']) ?>
                            </h3>
                            <span class="text-[11px] font-bold text-slate-400 block mt-0.5">
                                <?= htmlspecialchars($pkg['name_en']) ?> (<?= htmlspecialchars($pkg['slug']) ?>)
                            </span>
                        </div>
                        <span class="w-9 h-9 rounded-xl <?= $isPopular ? 'bg-emerald-50 text-[#00684A]' : 'bg-slate-100 text-slate-600' ?> flex items-center justify-center shrink-0">
                            <i data-lucide="<?= $isStarter ? 'zap' : ($pkg['slug'] === 'professional' ? 'trending-up' : 'crown') ?>" class="w-5 h-5"></i>
                        </span>
                    </div>

                    <!-- Price & Limit Display Banner -->
                    <div class="p-4 rounded-2xl <?= $isPopular ? 'bg-emerald-50/70 border border-emerald-100' : 'bg-slate-50 border border-slate-100' ?> my-4 space-y-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">বর্তমান মূল্য (Price)</span>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-xs font-bold text-slate-500">৳</span>
                                <span id="display-price-<?= $pkgId ?>" class="text-3xl font-black text-slate-900 tracking-tight">
                                    <?= $price ?>
                                </span>
                                <span class="text-xs font-bold text-slate-500 ml-1">
                                    <?= htmlspecialchars($pkg['billing_period_bn']) ?>
                                </span>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-500 block">
                                বাংলায়: ৳<span id="display-price-bn-<?= $pkgId ?>"><?= htmlspecialchars($pkg['formatted_price_bn']) ?></span>
                            </span>
                        </div>

                        <!-- Product Limit Indicator -->
                        <div class="pt-2.5 border-t <?= $isPopular ? 'border-emerald-200/80' : 'border-slate-200/80' ?> flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-500">প্রোডাক্ট আপলোড সীমা:</span>
                            <span id="display-limit-badge-<?= $pkgId ?>" class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black <?= $productLimit === 0 ? 'bg-purple-100 text-purple-700' : 'bg-white text-slate-800 border border-slate-200 shadow-2xs' ?>">
                                <?= htmlspecialchars($pkg['product_limit_label_bn']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Features Summary Preview -->
                    <div class="space-y-2.5 pt-2 border-t border-slate-100 text-xs text-slate-600">
                        <div class="flex items-center space-x-2 font-bold text-slate-800">
                            <i data-lucide="box" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <span id="display-limit-feature-<?= $pkgId ?>"><?= htmlspecialchars($pkg['formatted_product_limit_bn']) ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <span>কাস্টম ডোমেইন ও ফ্রি SSL সিকিউরিটি</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <span>নো-কোড ক্যাটালগ ও ইনভেন্টরি বিল্ডার</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-[#00684A] shrink-0"></i>
                            <span>সরাসরি অর্ডার ট্র্যাকিং ও স্টক নিয়ন্ত্রণ</span>
                        </div>
                    </div>
                </div>

                <!-- Update Button (Triggers Edit Modal) -->
                <div class="pt-6 mt-6 border-t border-slate-100">
                    <button type="button" 
                            onclick="openEditPackageModal(<?= $pkgId ?>, '<?= htmlspecialchars(addslashes($pkg['name_bn'])) ?>', <?= $price ?>, <?= $productLimit ?>)"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white transition-all shadow-xs cursor-pointer flex items-center justify-center space-x-2 active:scale-95 <?= $isPopular ? 'bg-[#00684A] hover:bg-[#005139]' : 'bg-slate-900 hover:bg-slate-800' ?>">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>প্যাকেজ এডিট করুন (Edit Info)</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick API Notice & Info -->
    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="radio" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="text-xs font-black text-slate-900">গ্লোবাল ডাইনামিক প্যাকেজ API সক্রিয়</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">এখানে মূল্য বা প্রোডাক্ট সীমা পরিবর্তন করলে হোমপেজের প্রাইসিং সেকশন স্বয়ংক্রিয়ভাবে রিয়েল-টাইমে আপডেট হয়ে যাবে।</p>
            </div>
        </div>
        <a href="../api/packages" target="_blank" 
           class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition shrink-0">
            <span>API রেসপন্স দেখুন</span>
            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
        </a>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: UPDATE PACKAGE (Price & Product Limit)            -->
<!-- ======================================================== -->
<div id="modalEditPackage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs hidden" role="dialog" aria-modal="true">
    <div class="bg-white rounded-3xl max-w-md w-full border border-slate-200/90 shadow-2xl overflow-hidden animate-scale-in">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#00684A] flex items-center justify-center shrink-0">
                    <i data-lucide="tag" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="modalPkgTitle" class="text-sm font-black text-slate-900">প্যাকেজ তথ্য আপডেট</h3>
                    <span class="text-[10px] font-bold text-slate-400 block">মূল্য ও প্রোডাক্ট সীমা নির্ধারণ করুন</span>
                </div>
            </div>
            <button type="button" onclick="closeEditPackageModal()" 
                    class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form id="formEditPackage" onsubmit="handleSavePackage(event)" class="p-6 space-y-4">
            <input type="hidden" id="editPkgId" value="">

            <!-- Error Banner -->
            <div id="pkgFormError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center space-x-2 hidden">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
                <span id="pkgFormErrorText"></span>
            </div>

            <!-- Price Input -->
            <div>
                <label for="inputPkgPrice" class="block text-xs font-bold text-slate-700 mb-1.5">
                    প্যাকেজের মূল্য (টাকা / BDT) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">
                        ৳
                    </div>
                    <input type="number" id="inputPkgPrice" name="price" required min="0" step="1"
                           class="w-full pl-9 pr-14 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 text-sm font-black focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00684A] focus:border-transparent transition"
                           placeholder="200">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                        / মাস
                    </div>
                </div>
            </div>

            <!-- Product Upload Limit Input -->
            <div>
                <label for="inputPkgProdLimit" class="block text-xs font-bold text-slate-700 mb-1.5">
                    প্রোডাক্ট আপলোড সীমা (Product Limit) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="box" class="w-4 h-4"></i>
                    </div>
                    <input type="number" id="inputPkgProdLimit" name="product_limit" required min="0" step="1"
                           class="w-full pl-9 pr-14 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 text-sm font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00684A] focus:border-transparent transition"
                           placeholder="50">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                        টি
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5 flex items-center space-x-1">
                    <i data-lucide="info" class="w-3 h-3 text-[#00684A] shrink-0"></i>
                    <span>০ (শূন্য) লিখলে এটি <strong>"আনলিমিটেড প্রোডাক্ট আপলোড"</strong> হিসেবে প্রদর্শিত হবে।</span>
                </p>
            </div>

            <!-- Modal Action Buttons -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeEditPackageModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnSavePackage"
                        class="px-5 py-2 rounded-xl bg-[#00684A] hover:bg-[#005139] text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm cursor-pointer">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                    <span>সংরক্ষণ করুন (Save Changes)</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. Bengali Digits Converter Utility
    function toBengaliDigits(num) {
        const en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return String(num).replace(/[0-9]/g, w => bn[en.indexOf(w)]);
    }

    // 2. Open Modal with Pre-filled Values
    function openEditPackageModal(id, name, currentPrice, currentLimit) {
        document.getElementById('editPkgId').value = id;
        document.getElementById('inputPkgPrice').value = currentPrice;
        document.getElementById('inputPkgProdLimit').value = currentLimit;
        document.getElementById('modalPkgTitle').textContent = `প্যাকেজ এডিট: ${name}`;
        document.getElementById('pkgFormError').classList.add('hidden');

        const modal = document.getElementById('modalEditPackage');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            const input = document.getElementById('inputPkgPrice');
            input.focus();
            input.select();
        }, 50);

        if (window.lucide) lucide.createIcons();
    }

    // 3. Close Modal
    function closeEditPackageModal() {
        const modal = document.getElementById('modalEditPackage');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // 4. Save Price & Product Limit via API
    async function handleSavePackage(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSavePackage');
        const errBox = document.getElementById('pkgFormError');
        const errText = document.getElementById('pkgFormErrorText');

        const id = document.getElementById('editPkgId').value;
        const price = parseInt(document.getElementById('inputPkgPrice').value, 10);
        const product_limit = parseInt(document.getElementById('inputPkgProdLimit').value, 10);

        if (isNaN(price) || price < 0) {
            errText.textContent = 'অনুগ্রহ করে সঠিক টাকার পরিমাণ দিন।';
            errBox.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
            return;
        }

        if (isNaN(product_limit) || product_limit < 0) {
            errText.textContent = 'অনুগ্রহ করে সঠিক প্রোডাক্ট লিমিট দিন (০ = আনলিমিটেড)।';
            errBox.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
            return;
        }

        errBox.classList.add('hidden');
        setBtnLoading(btn, true, 'সংরক্ষণ হচ্ছে...');

        try {
            // Send PUT to backend API
            const response = await apiFetch(`../api/admin/packages/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ price, product_limit })
            });

            // Update UI immediately on screen
            const priceEl = document.getElementById(`display-price-${id}`);
            const priceBnEl = document.getElementById(`display-price-bn-${id}`);
            const badgeEl = document.getElementById(`display-limit-badge-${id}`);
            const featEl = document.getElementById(`display-limit-feature-${id}`);

            if (priceEl) priceEl.textContent = price;
            if (priceBnEl) priceBnEl.textContent = toBengaliDigits(price);

            if (badgeEl) {
                if (product_limit === 0) {
                    badgeEl.className = 'inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-purple-100 text-purple-700';
                    badgeEl.textContent = 'আনলিমিটেড';
                } else {
                    badgeEl.className = 'inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-white text-slate-800 border border-slate-200 shadow-2xs';
                    badgeEl.textContent = toBengaliDigits(product_limit) + ' টি';
                }
            }

            if (featEl) {
                featEl.textContent = (product_limit === 0) ? 'আনলিমিটেড প্রোডাক্ট আপলোড' : (toBengaliDigits(product_limit) + 'টি প্রোডাক্ট আপলোড');
            }

            closeEditPackageModal();
            Toast.success(response.message || 'প্যাকেজের তথ্য সফলভাবে আপডেট হয়েছে!');
        } catch (err) {
            errText.textContent = err.message || 'ত্রুটি ঘটেছে, পুনরায় চেষ্টা করুন।';
            errBox.classList.remove('hidden');
            Toast.error(err.message, 'আপডেট ব্যর্থ');
            if (window.lucide) lucide.createIcons();
        } finally {
            setBtnLoading(btn, false);
        }
    }
</script>
