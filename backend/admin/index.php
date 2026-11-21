<?php

declare(strict_types=1);

/**
 * Ponnostack Admin Portal - Master Modular Page Dispatcher
 * 
 * Strict Design Standard: Pure Light Mode Only. Zero Dark Mode.
 */

// 1. Authenticate session & retrieve current user
require_once __DIR__ . '/includes/auth_guard.php';

// 2. Identify active requested page from query string ?page=...
$activePage = trim((string)($_GET['page'] ?? 'dashboard'));

$pageRegistry = [
    'dashboard' => [
        'title' => 'ড্যাশবোর্ড ওভারভিউ | Ponnostack',
        'file'  => 'dashboard.php',
    ],
    'admins' => [
        'title' => 'অ্যাডমিন ইউজার ম্যানেজমেন্ট | Ponnostack',
        'file'  => 'admins.php',
    ],
    'tenants' => [
        'title' => 'স্টোর ও টেন্যান্টস | Ponnostack',
        'file'  => 'tenants.php',
    ],
    'packages' => [
        'title' => 'প্যাকেজ ইনফো ও প্রাইসিং | Ponnostack',
        'file'  => 'packages.php',
    ],
    'settings' => [
        'title' => 'প্ল্যাটফর্ম সেটিংস | Ponnostack',
        'file'  => 'settings.php',
    ],
];

if (!array_key_exists($activePage, $pageRegistry)) {
    $activePage = 'dashboard';
}

$pageInfo  = $pageRegistry[$activePage];
$pageTitle = $pageInfo['title'];

// 3. Render Modular Master Layout
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<!-- Main Scrollable Content Area -->
<div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto page-content-animate">
        <?php require_once __DIR__ . '/pages/' . $pageInfo['file']; ?>
    </main>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
