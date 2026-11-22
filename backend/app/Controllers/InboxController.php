<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\ContactMessage;

/**
 * InboxController
 * Admin API Controller for managing customer contact inquiries.
 */
class InboxController extends Controller
{
    private ContactMessage $model;

    public function __construct()
    {
        $this->model = new ContactMessage();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Session Authentication Guard
     */
    private function checkAuth(): bool
    {
        if (empty($_SESSION['admin_user'])) {
            $this->error('অননুমোদিত এক্সেস। অনুগ্রহ করে লগইন করুন।', 401);
            return false;
        }
        return true;
    }

    /**
     * GET /api/admin/inbox
     */
    public function index(): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $status = $_GET['status'] ?? null;
        $messages = $this->model->getAll($status);
        $unreadCount = $this->model->getUnreadCount();

        $this->success([
            'messages'     => $messages,
            'unread_count' => $unreadCount,
            'total_count'  => count($messages),
        ], 'বার্তাগুলোর তালিকা সফলভাবে পাওয়া গেছে।');
    }

    /**
     * GET /api/admin/inbox/{id}
     */
    public function show(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $msgId = (int)$id;
        $message = $this->model->find($msgId);

        if (!$message) {
            $this->error('বার্তাটি পাওয়া যায়নি।', 404);
            return;
        }

        // Auto mark as read when viewed
        if ($message['status'] === 'unread') {
            $this->model->markAsRead($msgId);
            $message['status'] = 'read';
        }

        $this->success($message, 'বার্তার বিবরণ সফলভাবে পাওয়া গেছে।');
    }

    /**
     * PUT or POST /api/admin/inbox/{id}/read
     */
    public function markRead(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $msgId = (int)$id;
        $success = $this->model->markAsRead($msgId);

        if ($success) {
            $this->success(null, 'বার্তাটি পঠিত হিসেবে চিহ্নিত করা হয়েছে।');
        } else {
            $this->error('আপডেট ব্যর্থ হয়েছে।', 400);
        }
    }

    /**
     * DELETE /api/admin/inbox/{id}
     */
    public function destroy(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $msgId = (int)$id;
        $success = $this->model->delete($msgId);

        if ($success) {
            $this->success(null, 'বার্তাটি সফলভাবে ডিলিট করা হয়েছে।');
        } else {
            $this->error('ডিলিট ব্যর্থ হয়েছে।', 400);
        }
    }
}
