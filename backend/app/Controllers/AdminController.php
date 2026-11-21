<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Admin;

/**
 * Admin Controller
 * Handles Admin Authentication & Full CRUD Operations
 */
class AdminController extends Controller
{
    private Admin $adminModel;

    public function __construct()
    {
        $this->adminModel = new Admin();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * POST /api/admin/login
     */
    public function login(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $email    = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            $this->error('ইমেইল ও পাসওয়ার্ড দেওয়া আবশ্যক।', 422);
            return;
        }

        $admin = $this->adminModel->findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            $this->error('ভুল ইমেইল অথবা পাসওয়ার্ড।', 401);
            return;
        }

        if ($admin['status'] !== 'active') {
            $this->error('আপনার অ্যাকাউন্টটি বর্তমানে নিষ্ক্রিয় (Inactive)।', 403);
            return;
        }

        // Store user in session
        unset($admin['password']);
        $_SESSION['admin_user'] = $admin;

        $this->success($admin, 'লগইন সফল হয়েছে।');
    }

    /**
     * POST /api/admin/logout
     */
    public function logout(): void
    {
        unset($_SESSION['admin_user']);
        session_destroy();
        $this->success(null, 'সফলভাবে লগআউট হয়েছে।');
    }

    /**
     * GET /api/admin/me
     */
    public function me(): void
    {
        $user = $_SESSION['admin_user'] ?? null;
        if (!$user) {
            $this->error('অননুমোদিত এক্সেস। অনুগ্রহ করে লগইন করুন।', 401);
            return;
        }
        $this->success($user);
    }

    /**
     * GET /api/admin/users
     * List all admins
     */
    public function index(): void
    {
        $this->checkAuth();
        $admins = $this->adminModel->all();
        $this->success($admins, 'অ্যাডমিনদের তালিকা সফলভাবে পাওয়া গেছে।');
    }

    /**
     * POST /api/admin/users
     * Create a new admin
     */
    public function store(): void
    {
        $this->checkAuth();

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $name     = trim((string)($data['name'] ?? ''));
        $email    = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');
        $role     = in_array($data['role'] ?? '', ['super_admin', 'admin'], true) ? $data['role'] : 'admin';
        $status   = in_array($data['status'] ?? '', ['active', 'inactive'], true) ? $data['status'] : 'active';

        if (empty($name) || empty($email) || empty($password)) {
            $this->error('নাম, ইমেইল ও পাসওয়ার্ড প্রদান করা আবশ্যক।', 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('সঠিক ইমেইল ঠিকানা প্রদান করুন।', 422);
            return;
        }

        if (strlen($password) < 6) {
            $this->error('পাসওয়ার্ড ন্যূনতম ৬ অক্ষরের হতে হবে।', 422);
            return;
        }

        $existing = $this->adminModel->findByEmail($email);
        if ($existing) {
            $this->error('এই ইমেইল দিয়ে ইতিপূর্বেই একটি অ্যাকাউন্ট তৈরি করা হয়েছে।', 409);
            return;
        }

        $newId = $this->adminModel->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
            'status'   => $status,
        ]);

        $created = $this->adminModel->findById($newId);
        $this->success($created, 'নতুন অ্যাডমিন সফলভাবে যুক্ত হয়েছে।', 201);
    }

    /**
     * PUT /api/admin/users/{id}
     * Update an admin
     */
    public function update(mixed $id): void
    {
        $this->checkAuth();
        $id = (int)$id;

        $admin = $this->adminModel->findById($id);
        if (!$admin) {
            $this->error('অ্যাডমিন পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];

        $name   = trim((string)($data['name'] ?? $admin['name']));
        $email  = strtolower(trim((string)($data['email'] ?? $admin['email'])));
        $role   = in_array($data['role'] ?? '', ['super_admin', 'admin'], true) ? $data['role'] : $admin['role'];
        $status = in_array($data['status'] ?? '', ['active', 'inactive'], true) ? $data['status'] : $admin['status'];

        // If email changed, check uniqueness
        if ($email !== $admin['email']) {
            $existing = $this->adminModel->findByEmail($email);
            if ($existing) {
                $this->error('এই ইমেইলটি অন্য অ্যাকাউন্টে ব্যবহৃত হচ্ছে।', 409);
                return;
            }
        }

        $updatePayload = [
            'name'   => $name,
            'email'  => $email,
            'role'   => $role,
            'status' => $status,
        ];

        if (!empty($data['password'])) {
            if (strlen((string)$data['password']) < 6) {
                $this->error('পাসওয়ার্ড ন্যূনতম ৬ অক্ষরের হতে হবে।', 422);
                return;
            }
            $updatePayload['password'] = (string)$data['password'];
        }

        $this->adminModel->update($id, $updatePayload);
        $updated = $this->adminModel->findById($id);

        $this->success($updated, 'অ্যাডমিন তথ্য সফলভাবে আপডেট হয়েছে।');
    }

    /**
     * DELETE /api/admin/users/{id}
     * Delete an admin
     */
    public function destroy(mixed $id): void
    {
        $this->checkAuth();
        $id = (int)$id;

        $currentUser = $_SESSION['admin_user'] ?? [];
        if ((int)($currentUser['id'] ?? 0) === $id) {
            $this->error('আপনি নিজের অ্যাকাউন্ট মুছে ফেলতে পারবেন না।', 400);
            return;
        }

        $admin = $this->adminModel->findById($id);
        if (!$admin) {
            $this->error('অ্যাডমিন পাওয়া যায়নি।', 404);
            return;
        }

        if ($admin['role'] === 'super_admin' && $this->adminModel->countSuperAdmins() <= 1) {
            $this->error('সিস্টেমে অন্তত একজন সুপার অ্যাডমিন থাকা আবশ্যক।', 400);
            return;
        }

        $this->adminModel->delete($id);
        $this->success(null, 'অ্যাডমিন সফলভাবে ডিলিট করা হয়েছে।');
    }

    /**
     * Authentication gatekeeper
     */
    private function checkAuth(): void
    {
        if (empty($_SESSION['admin_user'])) {
            $this->error('অননুমোদিত এক্সেস। অনুগ্রহ করে লগইন করুন।', 401);
        }
    }
}
