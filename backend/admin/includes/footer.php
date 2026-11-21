<?php

declare(strict_types=1);
?>
    </div> <!-- Close Master Flex Container -->

    <!-- Universal Confirmation Modal (Pure Light Mode) -->
    <!-- Standard: Clicking outside backdrop does NOT close modal. Only [X] cross or [বাতিল] cancel button closes it. -->
    <div id="globalConfirmModal" class="fixed inset-0 w-full h-full bg-slate-900/50 backdrop-blur-xs z-[100] hidden flex items-center justify-center p-4 transition-all">
        <div class="bg-white border border-slate-200 rounded-3xl w-full max-w-sm p-6 shadow-2xl text-center transform transition-all animate-fade-in relative">
            <!-- Modal Close Button (Cross) -->
            <button type="button" id="globalCloseXBtn" class="absolute top-3.5 right-3.5 w-7 h-7 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer" title="বন্ধ করুন">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <!-- Icon Badge -->
            <div id="globalConfirmIcon" class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 shadow-xs">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>

            <h3 id="globalConfirmTitle" class="text-base font-black text-slate-900 leading-tight">আপনি কি নিশ্চিত?</h3>
            <p id="globalConfirmMessage" class="text-xs text-slate-500 mt-2 leading-relaxed">এই কাজটি সম্পন্ন করতে চান?</p>

            <!-- Buttons -->
            <div class="mt-6 flex items-center justify-center space-x-2.5">
                <button type="button" id="globalCancelBtn" class="w-1/2 py-2.5 px-4 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition cursor-pointer shadow-xs">
                    বাতিল
                </button>
                <button type="button" id="globalConfirmBtn" class="w-1/2 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center">
                    হ্যাঁ, নিশ্চিত
                </button>
            </div>
        </div>
    </div>

    <!-- Universal Toast Notification Container (Pure Light Mode) -->
    <div id="toastContainer" class="fixed bottom-5 right-5 z-[110] space-y-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"></div>

    <script>
        // ==========================================
        // 1. UNIVERSAL CONFIRMATION DIALOG (PROMISE-BASED)
        // ==========================================
        const Confirm = {
            ask(options = {}) {
                return new Promise((resolve) => {
                    const modal = document.getElementById('globalConfirmModal');
                    const titleEl = document.getElementById('globalConfirmTitle');
                    const msgEl = document.getElementById('globalConfirmMessage');
                    const confirmBtn = document.getElementById('globalConfirmBtn');
                    const cancelBtn = document.getElementById('globalCancelBtn');
                    const closeXBtn = document.getElementById('globalCloseXBtn');
                    const iconEl = document.getElementById('globalConfirmIcon');

                    titleEl.textContent = options.title || 'আপনি কি নিশ্চিত?';
                    msgEl.textContent = options.message || 'এই কাজটি সম্পন্ন করতে চান?';
                    confirmBtn.textContent = options.confirmText || 'হ্যাঁ, নিশ্চিত';
                    cancelBtn.textContent = options.cancelText || 'বাতিল';

                    if (options.type === 'primary') {
                        confirmBtn.className = 'w-1/2 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center';
                        iconEl.className = 'w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center mx-auto mb-3 shadow-xs';
                        iconEl.innerHTML = '<i data-lucide="help-circle" class="w-6 h-6"></i>';
                    } else {
                        confirmBtn.className = 'w-1/2 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center';
                        iconEl.className = 'w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 shadow-xs';
                        iconEl.innerHTML = '<i data-lucide="alert-triangle" class="w-6 h-6"></i>';
                    }

                    if (window.lucide) lucide.createIcons();

                    if (modal.parentElement !== document.body) {
                        document.body.appendChild(modal);
                    }

                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';

                    const cleanup = (result) => {
                        modal.classList.add('hidden');
                        document.body.style.overflow = '';
                        confirmBtn.onclick = null;
                        cancelBtn.onclick = null;
                        if (closeXBtn) closeXBtn.onclick = null;
                        resolve(result);
                    };

                    confirmBtn.onclick = () => cleanup(true);
                    cancelBtn.onclick = () => cleanup(false);
                    if (closeXBtn) closeXBtn.onclick = () => cleanup(false);
                    // Standard Rule: Clicking outside backdrop does NOT close modal.
                    // Only [X] cross button, [বাতিল] cancel button, or action button closes it.
                });
            }
        };

        // ==========================================
        // 2. UNIVERSAL TOP PAGE PROGRESS LOADER
        // ==========================================
        const PageLoader = {
            bar: null,
            timer: null,

            init() {
                this.bar = document.getElementById('topProgressBar');
            },

            start() {
                if (!this.bar) this.init();
                if (!this.bar) return;
                
                clearTimeout(this.timer);
                this.bar.style.opacity = '1';
                this.bar.style.width = '25%';

                this.timer = setTimeout(() => {
                    this.bar.style.width = '65%';
                    this.timer = setTimeout(() => {
                        this.bar.style.width = '85%';
                    }, 200);
                }, 100);
            },

            done() {
                if (!this.bar) this.init();
                if (!this.bar) return;

                clearTimeout(this.timer);
                this.bar.style.width = '100%';
                setTimeout(() => {
                    this.bar.style.opacity = '0';
                    setTimeout(() => {
                        this.bar.style.width = '0%';
                    }, 300);
                }, 200);
            }
        };

        // ==========================================
        // 3. UNIVERSAL TOAST NOTIFICATION ENGINE
        // ==========================================
        const Toast = {
            container: null,

            getContainer() {
                if (!this.container) {
                    this.container = document.getElementById('toastContainer');
                }
                return this.container;
            },

            show(message, type = 'success', title = null) {
                const c = this.getContainer();
                if (!c) return;

                const id = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

                let iconHtml = '';
                let borderClass = '';
                let defaultTitle = '';

                switch (type) {
                    case 'error':
                        iconHtml = `<div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0"><i data-lucide="alert-circle" class="w-4 h-4"></i></div>`;
                        borderClass = 'border-rose-200 bg-white text-slate-800';
                        defaultTitle = title || 'ত্রুটি ঘটেছে';
                        break;
                    case 'warning':
                        iconHtml = `<div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><i data-lucide="alert-triangle" class="w-4 h-4"></i></div>`;
                        borderClass = 'border-amber-200 bg-white text-slate-800';
                        defaultTitle = title || 'সতর্কতা';
                        break;
                    case 'info':
                        iconHtml = `<div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0"><i data-lucide="info" class="w-4 h-4"></i></div>`;
                        borderClass = 'border-blue-200 bg-white text-slate-800';
                        defaultTitle = title || 'তথ্য';
                        break;
                    case 'success':
                    default:
                        iconHtml = `<div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i data-lucide="check-circle" class="w-4 h-4"></i></div>`;
                        borderClass = 'border-emerald-200 bg-white text-slate-800';
                        defaultTitle = title || 'সফল হয়েছে!';
                        break;
                }

                const toast = document.createElement('div');
                toast.id = id;
                toast.className = `p-3.5 rounded-2xl shadow-xl border ${borderClass} flex items-start space-x-3 pointer-events-auto toast-animate-in transition-all duration-300`;
                toast.innerHTML = `
                    ${iconHtml}
                    <div class="flex-1 min-w-0 pt-0.5">
                        <span class="text-xs font-bold text-slate-900 block leading-tight">${escapeHtml(defaultTitle)}</span>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-normal break-words">${escapeHtml(message)}</p>
                    </div>
                    <button type="button" onclick="Toast.dismiss('${id}')" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg transition">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                `;

                c.appendChild(toast);
                if (window.lucide) lucide.createIcons();

                // Auto dismiss in 3.8s
                setTimeout(() => {
                    this.dismiss(id);
                }, 3800);
            },

            success(msg, title) { this.show(msg, 'success', title); },
            error(msg, title) { this.show(msg, 'error', title); },
            warning(msg, title) { this.show(msg, 'warning', title); },
            info(msg, title) { this.show(msg, 'info', title); },

            dismiss(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(10px) scale(0.95)';
                    setTimeout(() => el.remove(), 250);
                }
            }
        };

        // ==========================================
        // 4. UNIVERSAL BUTTON LOADER CONTROLLER
        // ==========================================
        function setBtnLoading(btn, isLoading, loadingText = 'অপেক্ষা করুন...') {
            if (!btn) return;
            if (isLoading) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.classList.add('opacity-80', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-current inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>${escapeHtml(loadingText)}</span>
                `;
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
                if (window.lucide) lucide.createIcons();
            }
        }

        // ==========================================
        // 5. UNIVERSAL API FETCH & ERROR HANDLER
        // ==========================================
        async function apiFetch(url, options = {}) {
            PageLoader.start();
            try {
                const res = await fetch(url, options);
                const json = await res.json().catch(() => ({ success: false, message: 'সার্ভার থেকে সঠিক JSON রেসপন্স পাওয়া যায়নি।' }));

                if (!res.ok || !json.success) {
                    if (res.status === 401) {
                        Toast.error('আপনার সেশনের মেয়াদ শেষ হয়েছে। পুনরায় লগইন করুন।', 'সেশন উত্তীর্ণ');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1200);
                        throw new Error('সেশন উত্তীর্ণ');
                    }

                    const errMsg = json.message || `সার্ভার ত্রুটি (HTTP ${res.status})`;
                    throw new Error(errMsg);
                }

                return json;
            } catch (err) {
                if (err.name === 'TypeError') {
                    const netErr = 'সার্ভারের সাথে সংযোগ স্থাপন করা সম্ভব হয়নি। দয়া করে ইন্টারনেট ও লোকালহোস্ট চেক করুন।';
                    Toast.error(netErr, 'সংযোগ ত্রুটি');
                    throw new Error(netErr);
                }
                throw err;
            } finally {
                PageLoader.done();
            }
        }

        // ==========================================
        // 6. DOM READY & SMOOTH NAVIGATION
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {
            PageLoader.init();
            if (window.lucide) lucide.createIcons();

            // Intercept internal navigation clicks for silky smooth transition & auto-close mobile drawer
            document.querySelectorAll('a[href^="index.php"]').forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (href && !href.startsWith('#') && !link.hasAttribute('target')) {
                        PageLoader.start();

                        // Auto-close mobile drawer when tapping a navigation item
                        const sidebar = document.getElementById('mainSidebar');
                        const backdrop = document.getElementById('sidebarBackdrop');
                        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                            sidebar.classList.add('-translate-x-full');
                            if (backdrop) backdrop.classList.add('hidden');
                        }
                    }
                });
            });
        });

        // Mobile Sidebar Drawer Controller
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        // Logout with Universal Confirm Modal
        async function handleLogout() {
            const ok = await Confirm.ask({
                title: 'লগআউট নিশ্চিতকরণ',
                message: 'আপনি কি নিশ্চিতভাবে বর্তমান সেশন থেকে লগআউট করতে চান?',
                confirmText: 'লগআউট করুন',
                cancelText: 'বাতিল',
                type: 'danger'
            });
            if (!ok) return;

            PageLoader.start();
            try {
                await fetch('../api/admin/logout', { method: 'POST' });
            } catch (e) {}
            Toast.info('সফলভাবে লগআউট সম্পন্ন হয়েছে।', 'বিদায়');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 300);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
