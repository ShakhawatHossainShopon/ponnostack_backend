<?php

declare(strict_types=1);

/** @var array $currentUser */
?>
<div class="space-y-6">
    <!-- Header Banner & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                <span>অ্যাডমিন ডিরেক্টরি</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">অ্যাডমিন ম্যানেজমেন্ট (Admin Accounts)</h2>
            <p class="text-xs text-slate-500 mt-1">প্ল্যাটফর্ম পরিচালনার জন্য অনুমোদিত অ্যাডমিনদের তালিকা ও নিয়ন্ত্রণ</p>
        </div>
        
        <button type="button" onclick="openAddAdminModal()"
            class="inline-flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition cursor-pointer self-start sm:self-auto">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>নতুন অ্যাডমিন যোগ করুন</span>
        </button>
    </div>

    <!-- Metrics Cards (3 Stats) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">মোট অ্যাডমিন</span>
                <span id="statTotalAdmins" class="text-2xl font-black text-slate-900 mt-0.5 block">
                    <span class="inline-block w-8 h-6 rounded-lg skeleton-shimmer align-middle"></span>
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">সুপার অ্যাডমিন</span>
                <span id="statSuperAdmins" class="text-2xl font-black text-indigo-600 mt-0.5 block">
                    <span class="inline-block w-8 h-6 rounded-lg skeleton-shimmer align-middle"></span>
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="shield" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">সক্রিয় অ্যাকাউন্ট</span>
                <span id="statActiveAdmins" class="text-2xl font-black text-emerald-600 mt-0.5 block">
                    <span class="inline-block w-8 h-6 rounded-lg skeleton-shimmer align-middle"></span>
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Admins Table Card (Pure Light Mode) -->
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">সকল অ্যাডমিন তালিকা</h3>
            <span id="adminCountBadge" class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600">
                লোড হচ্ছে...
            </span>
        </div>

        <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
            <table class="w-full min-w-[620px] text-left text-xs">
                <thead>
                    <tr class="text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-100 bg-slate-50/60 font-bold">
                        <th class="py-3 px-4">অ্যাডমিন নাম</th>
                        <th class="py-3 px-4">ইমেইল ঠিকানা</th>
                        <th class="py-3 px-4">ভূমিকা (Role)</th>
                        <th class="py-3 px-4">স্ট্যাটাস</th>
                        <th class="py-3 px-4">তৈরির তারিখ</th>
                        <th class="py-3 px-4 text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody id="adminsTableBody" class="divide-y divide-slate-100 font-medium text-slate-700">
                    <!-- Initial Shimmer Skeleton Rows -->
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl skeleton-shimmer shrink-0"></div>
                                <div class="space-y-1.5">
                                    <div class="w-28 h-3.5 rounded-md skeleton-shimmer"></div>
                                    <div class="w-16 h-2.5 rounded-md skeleton-shimmer"></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4"><div class="w-36 h-3 rounded-md skeleton-shimmer"></div></td>
                        <td class="py-3.5 px-4"><div class="w-20 h-5 rounded-full skeleton-shimmer"></div></td>
                        <td class="py-3.5 px-4"><div class="w-16 h-5 rounded-full skeleton-shimmer"></div></td>
                        <td class="py-3.5 px-4"><div class="w-24 h-3 rounded-md skeleton-shimmer"></div></td>
                        <td class="py-3.5 px-4 text-right"><div class="w-12 h-6 rounded-lg skeleton-shimmer ml-auto"></div></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Admin (Teleported directly to <body> for true viewport coverage) -->
<!-- Standard: Clicking outside backdrop does NOT close modal. Only [X] cross or [বাতিল] cancel button closes it. -->
<div id="modalAdmin" class="fixed inset-0 w-full h-full bg-slate-900/50 backdrop-blur-xs z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-fade-in">
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 id="modalTitle" class="text-sm font-black text-slate-900">নতুন অ্যাডমিন যোগ করুন</h3>
            <button type="button" onclick="closeAdminModal()" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form id="adminForm" onsubmit="handleSaveAdmin(event)" class="p-5 space-y-4">
            <input type="hidden" id="formAdminId">

            <div>
                <label for="formName" class="block text-xs font-bold text-slate-700 mb-1">পূর্ণ নাম *</label>
                <input type="text" id="formName" required placeholder="উদাহরণ: শওকত হোসেন"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition">
            </div>

            <div>
                <label for="formEmail" class="block text-xs font-bold text-slate-700 mb-1">ইমেইল ঠিকানা *</label>
                <input type="email" id="formEmail" required placeholder="admin@example.com"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition">
            </div>

            <div>
                <label for="formPassword" class="block text-xs font-bold text-slate-700 mb-1">
                    পাসওয়ার্ড <span id="pwdRequiredIndicator" class="text-rose-500">*</span>
                    <span id="pwdOptionalNote" class="text-[10px] text-slate-400 font-normal hidden">(পরিবর্তন না করতে চাইলে খালি রাখুন)</span>
                </label>
                <input type="password" id="formPassword" placeholder="ন্যূনতম ৬ অক্ষর"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="formRole" class="block text-xs font-bold text-slate-700 mb-1">ভূমিকা (Role)</label>
                    <select id="formRole" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 bg-white">
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>

                <div>
                    <label for="formStatus" class="block text-xs font-bold text-slate-700 mb-1">স্ট্যাটাস</label>
                    <select id="formStatus" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 bg-white">
                        <option value="active">Active (সক্রিয়)</option>
                        <option value="inactive">Inactive (নিষ্ক্রিয়)</option>
                    </select>
                </div>
            </div>

            <div id="modalFormError" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center space-x-2">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
                <span id="modalFormErrorText"></span>
            </div>

            <!-- Form Actions -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeAdminModal()" class="py-2 px-3.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                    বাতিল
                </button>
                <button type="submit" id="btnSaveAdmin" class="py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center">
                    <span>সংরক্ষণ করুন</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const CURRENT_USER_ID = <?= (int)($currentUser['id'] ?? 0) ?>;
    let adminsData = [];

    document.addEventListener('DOMContentLoaded', () => {
        // Teleport modal directly to <body> so it always covers 100vw x 100vh of the browser viewport
        const modal = document.getElementById('modalAdmin');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        loadAdmins();
    });

    // 1. Fetch all admins from Database via REST API with Skeleton
    async function loadAdmins() {
        const tbody = document.getElementById('adminsTableBody');
        const badge = document.getElementById('adminCountBadge');

        // Render Shimmer Skeleton Rows while loading
        tbody.innerHTML = `
            ${[1, 2, 3].map(() => `
                <tr>
                    <td class="py-3.5 px-4">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl skeleton-shimmer shrink-0"></div>
                            <div class="space-y-1.5">
                                <div class="w-28 h-3.5 rounded-md skeleton-shimmer"></div>
                                <div class="w-16 h-2.5 rounded-md skeleton-shimmer"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4"><div class="w-36 h-3 rounded-md skeleton-shimmer"></div></td>
                    <td class="py-3.5 px-4"><div class="w-20 h-5 rounded-full skeleton-shimmer"></div></td>
                    <td class="py-3.5 px-4"><div class="w-16 h-5 rounded-full skeleton-shimmer"></div></td>
                    <td class="py-3.5 px-4"><div class="w-24 h-3 rounded-md skeleton-shimmer"></div></td>
                    <td class="py-3.5 px-4 text-right"><div class="w-12 h-6 rounded-lg skeleton-shimmer ml-auto"></div></td>
                </tr>
            `).join('')}
        `;

        try {
            const json = await apiFetch('../api/admin/users');
            adminsData = json.data || [];
            renderAdminsTable(adminsData);
            updateStats(adminsData);
        } catch (err) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-8 text-center">
                        <div class="max-w-xs mx-auto p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700">
                            <i data-lucide="alert-circle" class="w-6 h-6 mx-auto mb-1 text-rose-600"></i>
                            <span class="font-bold text-xs block">ডাটা লোড ত্রুটি</span>
                            <span class="text-[11px] text-rose-600 mt-0.5 block">${escapeHtml(err.message)}</span>
                            <button onclick="loadAdmins()" class="mt-2.5 px-3 py-1 bg-white border border-rose-200 rounded-lg text-xs font-bold text-rose-700 hover:bg-rose-100 transition">
                                পুনরায় চেষ্টা করুন
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            if (window.lucide) lucide.createIcons();
        }
    }

    // 2. Render Table Rows
    function renderAdminsTable(list) {
        const tbody = document.getElementById('adminsTableBody');
        const badge = document.getElementById('adminCountBadge');

        badge.textContent = `মোট ${list.length} জন`;

        if (list.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <span class="text-xs font-semibold block">কোনো অ্যাডমিন অ্যাকাউন্ট পাওয়া যায়নি।</span>
                    </td>
                </tr>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        tbody.innerHTML = list.map(admin => {
            const isSuper = admin.role === 'super_admin';
            const isActive = admin.status === 'active';
            const isSelf = admin.id == CURRENT_USER_ID;

            const roleBadge = isSuper
                ? `<span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                     <i data-lucide="shield" class="w-3 h-3"></i><span>Super Admin</span>
                   </span>`
                : `<span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                     <span>Admin</span>
                   </span>`;

            const statusBadge = isActive
                ? `<span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                     <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Active</span>
                   </span>`
                : `<span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                     <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span><span>Inactive</span>
                   </span>`;

            const dateFormatted = new Date(admin.created_at).toLocaleDateString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric' });

            return `
                <tr class="hover:bg-slate-50/80 transition">
                    <td class="py-3 px-4">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-xs uppercase shadow-2xs">
                                ${admin.name.charAt(0)}
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 block leading-tight">${escapeHtml(admin.name)}</span>
                                ${isSelf ? '<span class="text-[10px] text-indigo-600 font-bold block">(আপনি)</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-4 font-mono text-slate-700">${escapeHtml(admin.email)}</td>
                    <td class="py-3 px-4">${roleBadge}</td>
                    <td class="py-3 px-4">${statusBadge}</td>
                    <td class="py-3 px-4 text-slate-500 text-[11px]">${dateFormatted}</td>
                    <td class="py-3 px-4 text-right">
                        <div class="inline-flex items-center space-x-1">
                            <button type="button" onclick="openEditAdminModal(${admin.id})"
                                class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer" title="এডিট করুন">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            ${!isSelf ? `
                            <button type="button" id="btnDel-${admin.id}" onclick="deleteAdmin(${admin.id}, '${escapeHtml(admin.name)}')"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="ডিলিট করুন">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) lucide.createIcons();
    }

    // 3. Update Statistics
    function updateStats(list) {
        document.getElementById('statTotalAdmins').textContent = list.length;
        document.getElementById('statSuperAdmins').textContent = list.filter(a => a.role === 'super_admin').length;
        document.getElementById('statActiveAdmins').textContent = list.filter(a => a.status === 'active').length;
    }

    // 4. Modal Handlers
    function openAddAdminModal() {
        const modal = document.getElementById('modalAdmin');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        document.getElementById('formAdminId').value = '';
        document.getElementById('formName').value = '';
        document.getElementById('formEmail').value = '';
        document.getElementById('formPassword').value = '';
        document.getElementById('formPassword').required = true;
        document.getElementById('pwdRequiredIndicator').classList.remove('hidden');
        document.getElementById('pwdOptionalNote').classList.add('hidden');
        document.getElementById('formRole').value = 'admin';
        document.getElementById('formStatus').value = 'active';

        document.getElementById('modalTitle').textContent = 'নতুন অ্যাডমিন যোগ করুন';
        document.getElementById('modalFormError').classList.add('hidden');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function openEditAdminModal(id) {
        const admin = adminsData.find(a => a.id == id);
        if (!admin) return;

        const modal = document.getElementById('modalAdmin');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        document.getElementById('formAdminId').value = admin.id;
        document.getElementById('formName').value = admin.name;
        document.getElementById('formEmail').value = admin.email;
        document.getElementById('formPassword').value = '';
        document.getElementById('formPassword').required = false;
        document.getElementById('pwdRequiredIndicator').classList.add('hidden');
        document.getElementById('pwdOptionalNote').classList.remove('hidden');
        document.getElementById('formRole').value = admin.role;
        document.getElementById('formStatus').value = admin.status;

        document.getElementById('modalTitle').textContent = `অ্যাডমিন এডিট: ${admin.name}`;
        document.getElementById('modalFormError').classList.add('hidden');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeAdminModal() {
        const modal = document.getElementById('modalAdmin');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // 5. Save Admin (Create or Update) with Button Loader & Toast
    async function handleSaveAdmin(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveAdmin');
        const errBox = document.getElementById('modalFormError');
        const errText = document.getElementById('modalFormErrorText');

        const id = document.getElementById('formAdminId').value;
        const name = document.getElementById('formName').value.trim();
        const email = document.getElementById('formEmail').value.trim();
        const password = document.getElementById('formPassword').value;
        const role = document.getElementById('formRole').value;
        const status = document.getElementById('formStatus').value;

        errBox.classList.add('hidden');
        setBtnLoading(btn, true, 'সংরক্ষণ হচ্ছে...');

        const payload = { name, email, role, status };
        if (password) payload.password = password;

        const isEdit = Boolean(id);
        const url = isEdit ? `../api/admin/users/${id}` : '../api/admin/users';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const data = await apiFetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            closeAdminModal();
            Toast.success(data.message || (isEdit ? 'অ্যাডমিন তথ্য আপডেট হয়েছে!' : 'নতুন অ্যাডমিন তৈরি হয়েছে!'));
            await loadAdmins();
        } catch (err) {
            errText.textContent = err.message || 'ত্রুটি ঘটেছে, পুনরায় চেষ্টা করুন।';
            errBox.classList.remove('hidden');
            Toast.error(err.message, 'অপারেশন ব্যর্থ');
            if (window.lucide) lucide.createIcons();
        } finally {
            setBtnLoading(btn, false);
        }
    }

    // 6. Delete Admin with Universal Confirm Modal & Micro-Loader
    async function deleteAdmin(id, name) {
        const ok = await Confirm.ask({
            title: 'অ্যাডমিন ডিলিট নিশ্চিতকরণ',
            message: `আপনি কি নিশ্চিতভাবে "${name}" অ্যাকাউন্টটি প্ল্যাটফর্ম থেকে চিরতরে মুছে ফেলতে চান?`,
            confirmText: 'হ্যাঁ, ডিলিট করুন',
            cancelText: 'বাতিল',
            type: 'danger'
        });
        if (!ok) return;

        const delBtn = document.getElementById(`btnDel-${id}`);
        if (delBtn) setBtnLoading(delBtn, true, '');

        try {
            const data = await apiFetch(`../api/admin/users/${id}`, { method: 'DELETE' });
            Toast.success(data.message || 'অ্যাডমিন সফলভাবে ডিলিট করা হয়েছে');
            await loadAdmins();
        } catch (err) {
            Toast.error(err.message, 'ডিলিট ব্যর্থ');
            if (delBtn) setBtnLoading(delBtn, false);
        }
    }
</script>
