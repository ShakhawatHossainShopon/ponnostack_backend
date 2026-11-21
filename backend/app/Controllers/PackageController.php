<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Package;

/**
 * Package Controller
 * 
 * Provides:
 * 1. GET /api/packages - Public API returning dynamic pricing & product limits for homepage
 * 2. PUT / POST /api/admin/packages/{id} - Protected Admin API to update package price and product limit
 */
class PackageController extends Controller
{
    private Package $packageModel;

    public function __construct()
    {
        $this->packageModel = new Package();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Public API: Retrieve all 3 packages with dynamic pricing & limits
     * GET /api/packages
     */
    public function index(): void
    {
        $packages = $this->packageModel->getAll();
        
        $this->success($packages, 'প্যাকেজের তথ্য সফলভাবে পাওয়া গেছে।');
    }

    /**
     * Admin Protected API: Update price and product limit of a package
     * PUT /api/admin/packages/{id}
     * POST /api/admin/packages/{id}
     */
    public function updatePrice(string|int $id): void
    {
        // 1. Session Authentication Guard
        if (empty($_SESSION['admin_user'])) {
            $this->error('অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে লগইন করুন।', 401);
            return;
        }

        $packageId = (int)$id;
        $existing = $this->packageModel->find($packageId);
        if (!$existing) {
            $this->error('প্যাকেজটি খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        // 2. Parse Payload
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        if (!isset($data['price']) || !is_numeric($data['price'])) {
            $this->error('প্যাকেজের মূল্য (টাকা) আবশ্যক এবং তা একটি সঠিক সংখ্যা হতে হবে।', 422);
            return;
        }

        $newPrice = (int)$data['price'];
        if ($newPrice < 0) {
            $this->error('প্যাকেজের মূল্য শূন্য বা তার বেশি হতে হবে।', 422);
            return;
        }

        // Product limit (if provided, default to existing if omitted)
        $newProductLimit = isset($data['product_limit']) && is_numeric($data['product_limit'])
            ? (int)$data['product_limit']
            : (int)$existing['product_limit'];

        if ($newProductLimit < 0) {
            $this->error('প্রোডাক্ট লিমিট শূন্য বা তার বেশি হতে হবে (০ = আনলিমিটেড)।', 422);
            return;
        }

        // 3. Update Package in Database
        $this->packageModel->updatePackage($packageId, $newPrice, $newProductLimit);

        $updated = $this->packageModel->find($packageId);

        $this->success($updated, 'প্যাকেজের তথ্য সফলভাবে আপডেট করা হয়েছে।');
    }
}
