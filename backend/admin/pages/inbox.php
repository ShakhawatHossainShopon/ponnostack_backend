<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Customer Inquiries & Messages Inbox
 * 
 * Strict Design Standard: Pure Light Mode Only. Zero Dark Mode.
 */

use App\Models\ContactMessage;

$msgModel = new ContactMessage();
$messages = $msgModel->getAll();
$unreadCount = $msgModel->getUnreadCount();
$totalCount = count($messages);
?>
<div class="space-y-6">
    <!-- Top Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#00684A]"></span>
                <span>কাস্টমার ইনবক্স</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">ইনবক্স ও মেসেজ (Customer Inquiries)</h2>
            <p class="text-xs text-slate-500 mt-1">ওয়েবসাইট কন্টাক্ট ফর্ম থেকে আসা গ্রাহকদের সরাসরি বার্তা ও প্রশ্নসমূহ পরিচালনা করুন।</p>
        </div>

        <div class="flex items-center space-x-2 shrink-0 self-start sm:self-auto">
            <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700">
                <i data-lucide="inbox" class="w-4 h-4 text-slate-500"></i>
                <span>মোট: <strong class="text-slate-900" id="totalMsgBadge"><?= $totalCount ?></strong></span>
            </div>
            <?php if ($unreadCount > 0): ?>
                <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700" id="unreadBadgeWrapper">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span>অপঠিত: <strong id="unreadMsgBadge"><?= $unreadCount ?></strong></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Tabs & Refresh -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0">
            <button type="button" onclick="filterInbox('all')" id="btnFilterAll"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-[#00684A] text-white shadow-2xs">
                সব মেসেজ (<?= $totalCount ?>)
            </button>
            <button type="button" onclick="filterInbox('unread')" id="btnFilterUnread"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200">
                অপঠিত (<?= $unreadCount ?>)
            </button>
            <button type="button" onclick="filterInbox('read')" id="btnFilterRead"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200">
                পঠিত (<?= max(0, $totalCount - $unreadCount) ?>)
            </button>
        </div>

        <button type="button" onclick="window.location.reload()" 
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition border border-slate-200/80 self-end sm:self-auto cursor-pointer">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            <span>রিফ্রেশ</span>
        </button>
    </div>

    <!-- Messages Container -->
    <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
        <?php if (empty($messages)): ?>
            <!-- Empty State -->
            <div class="py-16 px-4 text-center space-y-3" id="emptyInboxNotice">
                <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <i data-lucide="mail-open" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">ইনবক্স বর্তমানে সম্পূর্ণ খালি</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">ওয়েবসাইট কন্টাক্ট ফর্ম থেকে গ্রাহকরা বার্তা পাঠালে তাৎক্ষণিকভাবে এখানে জমা হবে।</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-5">প্রেরকের তথ্য</th>
                            <th class="py-3.5 px-5">মোবাইল নম্বর</th>
                            <th class="py-3.5 px-5">বার্তার বিষয় ও প্রিভিউ</th>
                            <th class="py-3.5 px-4 text-center">স্ট্যাটাস</th>
                            <th class="py-3.5 px-4">তারিখ ও সময়</th>
                            <th class="py-3.5 px-5 text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-sans" id="inboxTableBody">
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                                $isUnread = ($msg['status'] === 'unread');
                                $formattedDate = date('d M, Y - h:i A', strtotime($msg['created_at']));
                                $cleanPhone = preg_replace('/[^0-9+]/', '', $msg['phone']);
                            ?>
                            <tr class="hover:bg-slate-50/60 transition group <?= $isUnread ? 'bg-indigo-50/20' : '' ?>" id="row-msg-<?= (int)$msg['id'] ?>" data-status="<?= htmlspecialchars($msg['status']) ?>">
                                <!-- Sender -->
                                <td class="py-4 px-5">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl <?= $isUnread ? 'bg-[#00684A] text-white font-black' : 'bg-slate-100 text-slate-600 font-bold' ?> flex items-center justify-center text-xs shrink-0 shadow-2xs">
                                            <?= mb_substr(trim($msg['name']), 0, 1) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <strong class="text-slate-900 block font-bold text-xs truncate <?= $isUnread ? 'font-black text-emerald-950' : '' ?>"><?= htmlspecialchars($msg['name']) ?></strong>
                                            <?php if (!empty($msg['email'])): ?>
                                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-[11px] text-slate-400 hover:text-[#00684A] truncate block font-mono">
                                                    <?= htmlspecialchars($msg['email']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-[10px] text-slate-400 block">ইমেইল উল্লেখ নেই</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Phone with Call & WhatsApp -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <a href="tel:<?= htmlspecialchars($cleanPhone) ?>" class="font-mono font-bold text-slate-800 hover:text-[#00684A] transition">
                                            <?= htmlspecialchars($msg['phone']) ?>
                                        </a>
                                        <a href="https://wa.me/<?= htmlspecialchars($cleanPhone) ?>" target="_blank" class="p-1 rounded-md text-emerald-600 hover:bg-emerald-50 transition" title="WhatsApp এ মেসেজ দিন">
                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </td>

                                <!-- Subject & Message Preview -->
                                <td class="py-4 px-5 max-w-xs sm:max-w-md">
                                    <div class="cursor-pointer" onclick="viewMessageDetails(<?= (int)$msg['id'] ?>)">
                                        <span class="block text-[11px] font-bold text-slate-700 truncate mb-0.5">
                                            <?= htmlspecialchars($msg['subject'] ?: 'সাধারণ বার্তা') ?>
                                        </span>
                                        <p class="text-[11px] text-slate-500 line-clamp-1 group-hover:text-slate-800 transition">
                                            <?= htmlspecialchars($msg['message']) ?>
                                        </p>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <?php if ($isUnread): ?>
                                        <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs" id="status-badge-<?= (int)$msg['id'] ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            <span>অপঠিত</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200" id="status-badge-<?= (int)$msg['id'] ?>">
                                            পঠিত
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Date & Time -->
                                <td class="py-4 px-4 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    <?= $formattedDate ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- View Button -->
                                        <button type="button" onclick="viewMessageDetails(<?= (int)$msg['id'] ?>)" 
                                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-[#00684A] border border-slate-200 text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer" title="সম্পূর্ণ বার্তা দেখুন">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span class="hidden sm:inline">দেখুন</span>
                                        </button>

                                        <!-- Mark Read Button -->
                                        <?php if ($isUnread): ?>
                                            <button type="button" onclick="markMessageAsRead(<?= (int)$msg['id'] ?>)" id="btn-read-<?= (int)$msg['id'] ?>"
                                                    class="p-1.5 rounded-xl text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition cursor-pointer" title="পঠিত চিহ্নিত করুন">
                                                <i data-lucide="check" class="w-4 h-4"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Delete Button -->
                                        <button type="button" onclick="deleteMessage(<?= (int)$msg['id'] ?>)" 
                                                class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="ডিলিট করুন">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: VIEW FULL MESSAGE DETAILS                         -->
<!-- ======================================================== -->
<div id="viewMessageModal" onclick="if(event.target === this) closeViewMessageModal()" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-100/85 backdrop-blur-md hidden transition-all duration-200">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200/90 space-y-5 animate-scale-up">
        
        <!-- Modal Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#00684A] flex items-center justify-center font-bold">
                    <i data-lucide="mail-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 leading-tight" id="modalSenderName">-</h3>
                    <p class="text-[11px] text-slate-400 font-mono" id="modalSenderDate">-</p>
                </div>
            </div>
            <button type="button" onclick="closeViewMessageModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Contact Quick Actions -->
        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                <span class="text-[10px] font-bold text-slate-400 uppercase block mb-0.5">মোবাইল নম্বর</span>
                <div class="flex items-center justify-between">
                    <a href="#" id="modalSenderPhoneLink" class="font-mono font-bold text-slate-900 hover:text-[#00684A] truncate">-</a>
                    <a href="#" id="modalSenderWhatsappLink" target="_blank" class="p-1 rounded text-emerald-600 hover:bg-emerald-50" title="WhatsApp">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                <span class="text-[10px] font-bold text-slate-400 uppercase block mb-0.5">ইমেইল ঠিকানা</span>
                <a href="#" id="modalSenderEmailLink" class="font-mono text-slate-900 hover:text-[#00684A] truncate block">-</a>
            </div>
        </div>

        <!-- Message Subject & Body -->
        <div class="space-y-1.5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">বার্তার বিবরণ</span>
            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/90 text-xs text-slate-800 leading-relaxed font-sans whitespace-pre-wrap max-h-60 overflow-y-auto" id="modalSenderMessage">
                -
            </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeViewMessageModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                বন্ধ করুন
            </button>
        </div>
    </div>
</div>

<script>
    // Cached elements
    let currentViewingId = null;

    function filterInbox(type) {
        const rows = document.querySelectorAll('#inboxTableBody tr');
        const btnAll = document.getElementById('btnFilterAll');
        const btnUnread = document.getElementById('btnFilterUnread');
        const btnRead = document.getElementById('btnFilterRead');

        // Reset button styles
        [btnAll, btnUnread, btnRead].forEach(btn => {
            btn.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200';
        });

        if (type === 'all') {
            btnAll.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-[#00684A] text-white shadow-2xs';
            rows.forEach(r => r.classList.remove('hidden'));
        } else if (type === 'unread') {
            btnUnread.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-[#00684A] text-white shadow-2xs';
            rows.forEach(r => {
                r.classList.toggle('hidden', r.dataset.status !== 'unread');
            });
        } else if (type === 'read') {
            btnRead.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-[#00684A] text-white shadow-2xs';
            rows.forEach(r => {
                r.classList.toggle('hidden', r.dataset.status === 'unread');
            });
        }
    }

    async function viewMessageDetails(id) {
        currentViewingId = id;
        try {
            const res = await apiFetch(`../api/admin/inbox/${id}`);
            if (res.success && res.data) {
                const msg = res.data;
                document.getElementById('modalSenderName').innerText = msg.name;
                document.getElementById('modalSenderDate').innerText = msg.created_at;
                
                const cleanPhone = (msg.phone || '').replace(/[^0-9+]/g, '');
                const phoneLink = document.getElementById('modalSenderPhoneLink');
                phoneLink.innerText = msg.phone;
                phoneLink.href = 'tel:' + cleanPhone;

                const waLink = document.getElementById('modalSenderWhatsappLink');
                waLink.href = 'https://wa.me/' + cleanPhone;

                const emailLink = document.getElementById('modalSenderEmailLink');
                if (msg.email) {
                    emailLink.innerText = msg.email;
                    emailLink.href = 'mailto:' + msg.email;
                } else {
                    emailLink.innerText = 'ইমেইল নেই';
                    emailLink.removeAttribute('href');
                }

                document.getElementById('modalSenderMessage').innerText = msg.message;

                // Update row status visually in table
                const row = document.getElementById('row-msg-' + id);
                if (row) {
                    row.dataset.status = 'read';
                    row.classList.remove('bg-indigo-50/20');
                    const badge = document.getElementById('status-badge-' + id);
                    if (badge) {
                        badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200';
                        badge.innerText = 'পঠিত';
                    }
                    const readBtn = document.getElementById('btn-read-' + id);
                    if (readBtn) readBtn.remove();
                }

                // Show modal
                document.getElementById('viewMessageModal').classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            }
        } catch (err) {
            Toast.error(err.message || 'মেসেজ লোড করতে সমস্যা হয়েছে।');
        }
    }

    function closeViewMessageModal() {
        document.getElementById('viewMessageModal').classList.add('hidden');
    }

    async function markMessageAsRead(id) {
        try {
            const res = await apiFetch(`../api/admin/inbox/${id}/read`, { method: 'PUT' });
            if (res.success) {
                Toast.success('বার্তাটি পঠিত হিসেবে চিহ্নিত করা হয়েছে।');
                const row = document.getElementById('row-msg-' + id);
                if (row) {
                    row.dataset.status = 'read';
                    row.classList.remove('bg-indigo-50/20');
                    const badge = document.getElementById('status-badge-' + id);
                    if (badge) {
                        badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200';
                        badge.innerText = 'পঠিত';
                    }
                    const btn = document.getElementById('btn-read-' + id);
                    if (btn) btn.remove();
                }
            }
        } catch (err) {
            Toast.error(err.message || 'আপডেট ব্যর্থ হয়েছে।');
        }
    }

    async function deleteMessage(id) {
        if (!confirm('আপনি কি নিশ্চিতভাবে এই বার্তাটি স্থায়ীভাবে ডিলিট করতে চান?')) return;

        try {
            const res = await apiFetch(`../api/admin/inbox/${id}`, { method: 'DELETE' });
            if (res.success) {
                Toast.success('বার্তাটি সফলভাবে ডিলিট করা হয়েছে।');
                const row = document.getElementById('row-msg-' + id);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                Toast.error(res.message || 'ডিলিট ব্যর্থ হয়েছে।');
            }
        } catch (err) {
            Toast.error(err.message || 'ডিলিট ব্যর্থ হয়েছে।');
        }
    }
</script>
