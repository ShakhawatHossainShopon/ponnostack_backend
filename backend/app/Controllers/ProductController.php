<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Uploader;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;

/**
 * Product Controller
 *
 * Handles product listing, creation, multi-image upload with ~200KB WebP compression,
 * variant/size inventory tracking, stock adjustments, and discounts.
 */
class ProductController extends Controller
{
    protected Product $productModel;
    protected Category $categoryModel;
    protected Tenant $tenantModel;
    protected \App\Models\Package $packageModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->tenantModel = new Tenant();
        $this->packageModel = new \App\Models\Package();
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
                return $tenant;
            }
        }

        return null;
    }

    /**
     * GET /api/products
     * GET /api/tenants/{uuid}/products
     */
    public function index(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $filters = [];
        if (!empty($_GET['category_uuid'])) {
            $filters['category_uuid'] = trim((string)$_GET['category_uuid']);
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = trim((string)$_GET['status']);
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = trim((string)$_GET['search']);
        }
        if (isset($_GET['in_stock'])) {
            $filters['in_stock'] = ($_GET['in_stock'] === '1' || $_GET['in_stock'] === 'true');
        }
        if (!empty($_GET['limit'])) {
            $filters['limit'] = max(1, (int)$_GET['limit']);
            $filters['offset'] = max(0, (int)($_GET['offset'] ?? 0));
        }

        $products = $this->productModel->allByTenant($tenant['uuid'], $filters);
        $this->success($products, 'Products retrieved successfully');
    }

    /**
     * GET /api/products/{uuid} (Supports UUID or Slug)
     */
    public function show(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        $tenantUuid = $tenant ? $tenant['uuid'] : null;

        $product = $this->productModel->findByIdentifier($uuid, $tenantUuid);
        if (!$product && $tenantUuid !== null) {
            $product = $this->productModel->findByIdentifier($uuid, null);
        }

        if (!$product) {
            $this->error('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $this->success($product, 'Product retrieved successfully');
    }

    /**
     * POST /api/products
     * POST /api/tenants/{uuid}/products
     */
    public function store(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        // Package Product Upload Limit Validation
        $pkgSlug = $tenant['package_slug'] ?? 'starter';
        $pkg = $this->packageModel->findBySlug($pkgSlug);
        $packageLimit = null;
        if ($pkg && isset($pkg['product_limit'])) {
            $packageLimit = (int)$pkg['product_limit'];
        }
        if ($packageLimit === null && isset($tenant['product_limit'])) {
            $packageLimit = (int)$tenant['product_limit'];
        }
        if ($packageLimit === null) {
            $packageLimit = 50;
        }

        // 0 indicates unlimited products (e.g. enterprise package)
        if ($packageLimit > 0) {
            $currentProductCount = $this->productModel->countByTenant($tenant['uuid']);
            if ($currentProductCount >= $packageLimit) {
                $this->error("আপনার প্রোডাক্ট আপলোডের লিমিট শেষ হয়ে গেছে। আপনার প্যাকেজের আওতায় সর্বোচ্চ {$packageLimit}টি প্রোডাক্ট যোগ করার সীমা পূর্ণ হয়ে গেছে। আপনি আর নতুন প্রোডাক্ট যোগ করতে পারবেন না।", 403, [
                    'limit_reached' => true,
                    'current_count' => $currentProductCount,
                    'product_limit' => $packageLimit,
                    'package_slug'  => $pkgSlug,
                ]);
                return;
            }
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $name = trim((string)($input['name'] ?? ''));
        if (empty($name)) {
            $this->error('প্রোডাক্টের নাম আবশ্যক।', 422);
            return;
        }

        $mainPrice = (float)($input['main_price'] ?? 0);
        $retailerPrice = (float)($input['retailer_price'] ?? 0);
        if ($retailerPrice <= 0) {
            $this->error('বিক্রয় মূল্য শূন্যের চেয়ে বেশি হতে হবে।', 422);
            return;
        }

        // Slug generation
        $slug = trim((string)($input['slug'] ?? ''));
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            if (empty($slug)) {
                $slug = 'product-' . bin2hex(random_bytes(4));
            }
        }

        // Ensure slug uniqueness for this tenant
        $existing = $this->productModel->findBySlug($slug, $tenant['uuid']);
        if ($existing) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        // Resolve Category (Default to Unnamed if not provided)
        $categoryUuid = trim((string)($input['category_uuid'] ?? ''));
        if (empty($categoryUuid)) {
            $defaultCat = $this->categoryModel->ensureDefaultCategory($tenant['id'], $tenant['uuid']);
            $categoryUuid = $defaultCat['uuid'];
        }

        // Handle Images: if base64 images passed inline, upload them
        $images = is_array($input['images'] ?? null) ? $input['images'] : [];
        if (!empty($input['images_base64']) && is_array($input['images_base64'])) {
            foreach ($input['images_base64'] as $b64) {
                if (is_string($b64) && !empty($b64)) {
                    $up = Uploader::uploadBase64($b64, 'uploads/products', 5242880, true);
                    if (!empty($up['success']) && !empty($up['full_url'])) {
                        $images[] = $up['full_url'];
                    }
                }
            }
        }

        // Stock and Variants
        $stock = max(0, (int)($input['stock'] ?? 0));
        $sizes = $input['sizes'] ?? [];
        $colors = $input['colors'] ?? [];

        // If sizes provided with stock numbers, calculate total stock if stock wasn't explicitly higher
        if (is_array($sizes) && !empty($sizes)) {
            $variantTotal = 0;
            $hasVariantStock = false;
            foreach ($sizes as $s) {
                if (is_array($s) && isset($s['stock'])) {
                    $variantTotal += max(0, (int)$s['stock']);
                    $hasVariantStock = true;
                }
            }
            if ($hasVariantStock && $variantTotal > 0) {
                $stock = $variantTotal;
            }
        }

        $productData = [
            'tenant_id'          => $tenant['id'],
            'tenant_uuid'        => $tenant['uuid'],
            'category_uuid'      => $categoryUuid,
            'name'               => $name,
            'slug'               => $slug,
            'description'        => $input['description'] ?? '',
            'main_price'         => $mainPrice,
            'retailer_price'     => $retailerPrice,
            'discount_percent'   => (int)($input['discount_percent'] ?? 0),
            'discount_starts_at' => $input['discount_starts_at'] ?? null,
            'discount_ends_at'   => $input['discount_ends_at'] ?? null,
            'stock'              => $stock,
            'sizes'              => $sizes,
            'colors'             => $colors,
            'images'             => array_slice($images, 0, 10),
            'status'             => ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $created = $this->productModel->create($productData);
        if (!$created) {
            $this->error('প্রোডাক্ট তৈরি করতে ব্যর্থ হয়েছে।', 500);
            return;
        }

        $this->success($created, 'প্রোডাক্ট সফলভাবে তৈরি করা হয়েছে।', 201);
    }

    /**
     * PUT /api/products/{uuid}
     * POST /api/products/{uuid}
     */
    public function update(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $product = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        if (!$product) {
            $this->error('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $updateData = [];

        if (isset($input['name'])) {
            $name = trim((string)$input['name']);
            if (empty($name)) {
                $this->error('প্রোডাক্টের নাম খালি রাখা যাবে না।', 422);
                return;
            }
            $updateData['name'] = $name;
        }

        if (isset($input['slug'])) {
            $slug = trim((string)$input['slug']);
            if (!empty($slug) && $slug !== $product['slug']) {
                $existing = $this->productModel->findBySlug($slug, $tenant['uuid']);
                if ($existing && $existing['uuid'] !== $uuid) {
                    $slug .= '-' . bin2hex(random_bytes(2));
                }
                $updateData['slug'] = $slug;
            }
        }

        if (isset($input['category_uuid'])) {
            $updateData['category_uuid'] = !empty($input['category_uuid']) ? trim((string)$input['category_uuid']) : null;
        }

        if (isset($input['description'])) {
            $updateData['description'] = (string)$input['description'];
        }

        if (isset($input['main_price'])) {
            $updateData['main_price'] = (float)$input['main_price'];
        }

        if (isset($input['retailer_price'])) {
            $updateData['retailer_price'] = (float)$input['retailer_price'];
        }

        if (isset($input['discount_percent'])) {
            $updateData['discount_percent'] = (int)$input['discount_percent'];
        }
        if (array_key_exists('discount_starts_at', $input)) {
            $updateData['discount_starts_at'] = $input['discount_starts_at'];
        }
        if (array_key_exists('discount_ends_at', $input)) {
            $updateData['discount_ends_at'] = $input['discount_ends_at'];
        }

        if (isset($input['status'])) {
            $updateData['status'] = in_array($input['status'], ['active', 'inactive'], true) ? $input['status'] : 'active';
        }

        if (isset($input['sizes'])) {
            $updateData['sizes'] = $input['sizes'];
        }

        if (isset($input['colors'])) {
            $updateData['colors'] = $input['colors'];
        }

        // Process images
        if (isset($input['images'])) {
            $images = is_array($input['images']) ? $input['images'] : [];
            if (!empty($input['images_base64']) && is_array($input['images_base64'])) {
                foreach ($input['images_base64'] as $b64) {
                    if (is_string($b64) && !empty($b64)) {
                        $up = Uploader::uploadBase64($b64, 'uploads/products', 5242880, true);
                        if (!empty($up['success']) && !empty($up['full_url'])) {
                            $images[] = $up['full_url'];
                        }
                    }
                }
            }
            $updateData['images'] = array_slice($images, 0, 10);
        }

        if (isset($input['stock'])) {
            $updateData['stock'] = max(0, (int)$input['stock']);
        }

        $this->productModel->update($uuid, $updateData);
        $updated = $this->productModel->findByUuid($uuid, $tenant['uuid']);

        $this->success($updated, 'প্রোডাক্ট সফলভাবে আপডেট করা হয়েছে।');
    }

    /**
     * DELETE /api/products/{uuid}
     */
    public function destroy(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $product = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        if (!$product) {
            $this->error('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $this->productModel->delete($uuid);
        $this->success(null, 'প্রোডাক্ট মুছে ফেলা হয়েছে।');
    }

    /**
     * POST /api/products/{uuid}/stock
     * Adjust or set stock directly
     */
    public function adjustStock(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $product = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        if (!$product) {
            $this->error('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $updateData = [];
        if (isset($input['stock'])) {
            $updateData['stock'] = max(0, (int)$input['stock']);
        } elseif (isset($input['change'])) {
            $change = (int)$input['change'];
            $this->productModel->adjustStock($uuid, $change);
        }

        if (isset($input['sizes']) && is_array($input['sizes'])) {
            $updateData['sizes'] = $input['sizes'];
            $sTotal = 0;
            $hasS = false;
            foreach ($input['sizes'] as $sz) {
                if (is_array($sz) && isset($sz['stock'])) {
                    $sTotal += max(0, (int)$sz['stock']);
                    $hasS = true;
                }
            }
            if ($hasS && !isset($input['stock'])) {
                $updateData['stock'] = $sTotal;
            }
        }

        if (isset($input['colors']) && is_array($input['colors'])) {
            $updateData['colors'] = $input['colors'];
            if (!$hasS && !isset($input['stock'])) {
                $cTotal = 0;
                $hasC = false;
                foreach ($input['colors'] as $cl) {
                    if (is_array($cl) && isset($cl['stock'])) {
                        $cTotal += max(0, (int)$cl['stock']);
                        $hasC = true;
                    }
                }
                if ($hasC) {
                    $updateData['stock'] = $cTotal;
                }
            }
        }

        if (!empty($updateData)) {
            $this->productModel->update($uuid, $updateData);
        }

        $updated = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        $this->success($updated, 'স্টক আপডেট সম্পন্ন হয়েছে।');
    }

    /**
     * PUT|POST /api/products/{uuid}/discount
     * Set or activate promotional discount
     */
    public function setDiscount(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $product = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        if (!$product) {
            $this->error('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $percent = isset($input['discount_percent']) ? max(1, min(99, (int)$input['discount_percent'])) : 20;
        $startsAt = !empty($input['discount_starts_at']) ? $input['discount_starts_at'] : date('Y-m-d H:i:s');
        $endsAt = !empty($input['discount_ends_at']) ? $input['discount_ends_at'] : null;

        $this->productModel->update($uuid, [
            'discount_percent'   => $percent,
            'discount_starts_at' => $startsAt,
            'discount_ends_at'   => $endsAt,
        ]);

        $updated = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        $this->success($updated, 'ডিসকাউন্ট সফলভাবে চালু করা হয়েছে!');
    }

    /**
     * DELETE /api/products/{uuid}/discount
     * Remove promotional discount
     */
    public function removeDiscount(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $product = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        if (!$product) {
            $this->error('প্রোডাক্টটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $this->productModel->update($uuid, [
            'discount_percent'   => 0,
            'discount_starts_at' => null,
            'discount_ends_at'   => null,
        ]);

        $updated = $this->productModel->findByUuid($uuid, $tenant['uuid']);
        $this->success($updated, 'ডিসকাউন্ট বাতিল করা হয়েছে।');
    }

    /**
     * POST /api/products/upload-images
     * Compresses incoming product images to ~200KB WebP
     */
    public function uploadImages(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('অননুমোদিত অনুরোধ। Store UUID প্রয়োজন।', 401);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $urls = [];

        // 1. Process Base64 images array (WAF-proof)
        if (!empty($input['images_base64']) && is_array($input['images_base64'])) {
            foreach ($input['images_base64'] as $b64) {
                if (is_string($b64) && !empty($b64)) {
                    $up = Uploader::uploadBase64($b64, 'uploads/products', 5242880, true);
                    if (!empty($up['success']) && !empty($up['full_url'])) {
                        $urls[] = $up['full_url'];
                    }
                }
            }
        }

        // 2. Fallback: Process multipart $_FILES if present
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $filesCount = count($_FILES['images']['name']);
            for ($i = 0; $i < $filesCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name'     => $_FILES['images']['name'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                    ];
                    $up = Uploader::upload($singleFile, 'uploads/products', Uploader::IMAGE_TYPES, 5242880, true);
                    if (!empty($up['success']) && !empty($up['full_url'])) {
                        $urls[] = $up['full_url'];
                    }
                }
            }
        }

        if (empty($urls)) {
            $this->error('কোনো ছবি পাওয়া যায়নি বা আপলোড ব্যর্থ হয়েছে।', 422);
            return;
        }

        $this->success(['urls' => $urls], 'ছবিসমূহ সফলভাবে আপলোড ও ২০০KB-তে অপটিমাইজ করা হয়েছে।');
    }
}
