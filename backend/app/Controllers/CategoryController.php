<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Uploader;
use App\Models\Category;
use App\Models\Tenant;

/**
 * Category Controller
 *
 * Handles category creation, listing, image upload with ~200KB compression,
 * update, and deletion. Enforces the default "Unnamed" category for each tenant.
 */
class CategoryController extends Controller
{
    protected Category $categoryModel;
    protected Tenant $tenantModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
        $this->tenantModel = new Tenant();
    }

    /**
     * Resolve target tenant from parameter, query, headers, or body
     */
    protected function resolveTenant(?string $paramUuid = null): ?array
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $allHeaders = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];

        $candidates = [
            $paramUuid,
            $_GET['store'] ?? null,
            $_GET['store_uuid'] ?? null,
            $_GET['tenant_uuid'] ?? null,
            $input['store'] ?? null,
            $input['store_uuid'] ?? null,
            $input['tenant_uuid'] ?? null,
            $_SERVER['HTTP_X_STORE_UUID'] ?? null,
            $_SERVER['HTTP_X_TENANT_UUID'] ?? null,
            $_SERVER['REDIRECT_HTTP_X_STORE_UUID'] ?? null,
            $_SERVER['REDIRECT_HTTP_X_TENANT_UUID'] ?? null,
            $allHeaders['x-store-uuid'] ?? null,
            $allHeaders['x-tenant-uuid'] ?? null,
            $allHeaders['store-uuid'] ?? null,
        ];

        $uuid = '';
        foreach ($candidates as $cand) {
            if ($cand !== null && trim((string)$cand) !== '') {
                $uuid = trim((string)$cand);
                break;
            }
        }

        if (!empty($uuid)) {
            $tenant = $this->tenantModel->findByUuid($uuid);
            if (!$tenant) {
                $tenant = $this->tenantModel->findBySlug($uuid);
            }
            if ($tenant) {
                return ['id' => (int)$tenant['id'], 'uuid' => $tenant['uuid']];
            }
        }

        return null;
    }

    /**
     * GET /api/categories
     * GET /api/tenants/{uuid}/categories
     * List all categories for the storefront tenant (ensuring default "Unnamed" exists)
     */
    public function index(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি। অনুগ্রহ করে Store UUID প্রদান করুন।', 404);
            return;
        }

        // Ensure default "Unnamed" category exists
        $this->categoryModel->ensureDefaultCategory($tenant['id'], $tenant['uuid']);

        $excludeDefault = (!empty($_GET['exclude_default']) && ($_GET['exclude_default'] === '1' || $_GET['exclude_default'] === 'true'));
        $categories = $this->categoryModel->allByTenant($tenant['uuid'], $excludeDefault);

        $this->success($categories, 'Categories retrieved successfully');
    }

    /**
     * GET /api/categories/{uuid}
     * Find single category
     */
    public function show(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        $cat = $this->categoryModel->findByUuid($uuid, $tenant ? $tenant['uuid'] : null);
        if (!$cat) {
            $this->error('ক্যাটাগরি পাওয়া যায়নি।', 404);
            return;
        }

        $this->success($cat, 'Category details loaded successfully');
    }

    /**
     * POST /api/categories/upload-image
     * Upload single category image with automatic compression down to ~200KB WebP
     */
    public function uploadImage(): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        // 1. Check for Base64 JSON upload (WAF / ModSecurity safe)
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;
        $b64 = $input['image_base64'] ?? $input['image'] ?? null;

        if (!empty($b64) && is_string($b64) && str_starts_with($b64, 'data:image')) {
            $result = Uploader::uploadBase64($b64, 'uploads/categories', 5 * 1024 * 1024, true);
            if (!$result['success']) {
                $this->error($result['error'] ?? 'ক্যাটাগরি ইমেজ আপলোড ব্যর্থ হয়েছে।', 400);
                return;
            }

            $this->success([
                'url'            => $result['full_url'],
                'relative_url'   => $result['url'],
                'filename'       => $result['filename'],
                'size_formatted' => '200 KB',
            ], 'Category image uploaded and compressed successfully');
            return;
        }

        // 2. Fallback: Standard Multipart $_FILES upload
        $file = $_FILES['image'] ?? null;
        if (!$file || empty($file['name'])) {
            $this->error('কোনো ছবি নির্বাচন করা হয়নি।', 400);
            return;
        }

        // Upload and compress image down to ~200KB WebP
        $result = Uploader::upload($file, 'uploads/categories', compress: true);
        if (!$result['success']) {
            $this->error($result['error'] ?? 'ক্যাটাগরি ইমেজ আপলোড ব্যর্থ হয়েছে।', 400);
            return;
        }

        $this->success([
            'url'            => $result['full_url'],
            'relative_url'   => $result['url'],
            'filename'       => $result['filename'],
            'size_formatted' => '200 KB',
        ], 'Category image uploaded and compressed successfully');
    }

    /**
     * POST /api/categories
     * POST /api/tenants/{uuid}/categories
     * Create a new category
     */
    public function store(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $name = trim((string)($input['name'] ?? ''));
        if (empty($name)) {
            $this->error('ক্যাটাগরির নাম আবশ্যক।', 422);
            return;
        }

        // Generate URL-friendly slug
        $rawSlug = !empty($input['slug']) ? (string)$input['slug'] : $name;
        $cleanSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug)));
        $cleanSlug = trim($cleanSlug, '-');

        if (empty($cleanSlug)) {
            $cleanSlug = 'cat-' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        // Check slug uniqueness for this tenant
        $existing = $this->categoryModel->findBySlug($cleanSlug, $tenant['uuid']);
        if ($existing) {
            $cleanSlug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $image = !empty($input['image']) ? trim((string)$input['image']) : null;

        $newUuid = $this->categoryModel->create([
            'tenant_id'   => $tenant['id'],
            'tenant_uuid' => $tenant['uuid'],
            'name'        => $name,
            'slug'        => $cleanSlug,
            'image'       => $image,
            'is_default'  => 0,
        ]);

        $created = $this->categoryModel->findByUuid($newUuid, $tenant['uuid']);
        $this->success($created, 'ক্যাটাগরি সফলভাবে তৈরি করা হয়েছে।', 201);
    }

    /**
     * PUT /api/categories/{uuid}
     * POST /api/categories/{uuid}
     * Update category
     */
    public function update(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        $cat = $this->categoryModel->findByUuid($uuid, $tenant ? $tenant['uuid'] : null);
        if (!$cat) {
            $this->error('ক্যাটাগরি পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $data = [];
        if (isset($input['name'])) {
            $name = trim((string)$input['name']);
            if (!empty($name)) {
                $data['name'] = $name;
            }
        }

        if (array_key_exists('image', $input)) {
            $data['image'] = !empty($input['image']) ? trim((string)$input['image']) : null;
        }

        // If not default category, allow updating slug if provided
        if (empty($cat['is_default']) && strtolower($cat['slug']) !== 'unnamed' && !empty($input['slug'])) {
            $cleanSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', (string)$input['slug'])));
            $cleanSlug = trim($cleanSlug, '-');
            if (!empty($cleanSlug)) {
                $existing = $this->categoryModel->findBySlug($cleanSlug, $cat['tenant_uuid'], $uuid);
                if (!$existing) {
                    $data['slug'] = $cleanSlug;
                }
            }
        }

        if (!empty($data)) {
            $this->categoryModel->update($uuid, $data, $cat['tenant_uuid']);
        }

        $updated = $this->categoryModel->findByUuid($uuid, $cat['tenant_uuid']);
        $this->success($updated, 'ক্যাটাগরি সফলভাবে আপডেট করা হয়েছে।');
    }

    /**
     * DELETE /api/categories/{uuid}
     * POST /api/categories/{uuid}/delete
     * Delete category (cannot delete default "Unnamed")
     */
    public function destroy(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        $cat = $this->categoryModel->findByUuid($uuid, $tenant ? $tenant['uuid'] : null);
        if (!$cat) {
            $this->error('ক্যাটাগরি পাওয়া যায়নি।', 404);
            return;
        }

        if (!empty($cat['is_default']) || strtolower($cat['slug']) === 'unnamed') {
            $this->error('ডিফল্ট "Unnamed" ক্যাটাগরি মুছে ফেলা সম্ভব নয়।', 400);
            return;
        }

        $deleted = $this->categoryModel->delete($uuid, $cat['tenant_uuid']);
        if (!$deleted) {
            $this->error('ক্যাটাগরি মুছে ফেলা ব্যর্থ হয়েছে।', 500);
            return;
        }

        $this->success(null, 'ক্যাটাগরি মুছে ফেলা হয়েছে এবং প্রোডাক্টসমূহ ডিফল্ট ক্যাটাগরিতে স্থানান্তর করা হয়েছে।');
    }
}
