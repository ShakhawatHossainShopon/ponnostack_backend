<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Entry Point & Health Diagnostics
 * 
 * Strict Design Standard: Pure Light Mode Only. Zero Dark Mode.
 */

require_once __DIR__ . '/app/Core/Env.php';
require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Env;
use App\Core\Database;

// 1. Initialize Environment
Env::load(__DIR__ . '/.env');

// 2. Perform Database Diagnostics
$dbDiag = Database::testConnection();

$appName = (string)env('APP_NAME', 'Ponnostack');
$appEnv  = (string)env('APP_ENV', 'local');
$appUrl  = (string)env('APP_URL', 'http://localhost');
$isProd  = strtolower($appEnv) === 'production';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName) ?> | System Status</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-emerald-500 selection:text-white">

    <div class="w-full max-w-xl">
        <!-- Main Card Container (Pure Light Mode) -->
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm">
            
            <!-- Top Header & Environment Pill -->
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-5 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2">
                        <span><?= htmlspecialchars($appName) ?></span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider <?= $isProd ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                            <?= htmlspecialchars($appEnv) ?>
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Multi-Tenant E-Commerce Platform Core</p>
                </div>

                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $dbDiag['connected'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                        <span class="w-2 h-2 rounded-full <?= $dbDiag['connected'] ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' ?>"></span>
                        <?= $dbDiag['connected'] ? 'System Healthy' : 'Database Offline' ?>
                    </span>
                </div>
            </div>

            <!-- Status Indicator Panel -->
            <div class="space-y-4">
                <!-- Database Connection Diagnostic Card -->
                <div class="bg-slate-50/70 border <?= $dbDiag['connected'] ? 'border-emerald-200' : 'border-rose-200' ?> rounded-2xl p-5 transition">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl <?= $dbDiag['connected'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?> flex items-center justify-center font-bold text-sm">
                                <?= $dbDiag['connected'] ? '✓' : '✕' ?>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">MySQL Database Connection</h3>
                                <p class="text-[11px] <?= $dbDiag['connected'] ? 'text-emerald-700 font-semibold' : 'text-rose-600 font-semibold' ?>">
                                    <?= $dbDiag['connected'] ? 'Active & Ready for Queries' : 'Connection failed to establish' ?>
                                </p>
                            </div>
                        </div>

                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-xs">
                            <?= htmlspecialchars($dbDiag['version']) ?>
                        </span>
                    </div>

                    <!-- Connection Metadata Table -->
                    <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Host</span>
                            <span class="text-slate-800 font-semibold truncate block"><?= htmlspecialchars($dbDiag['host']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Database</span>
                            <span class="text-emerald-700 font-bold truncate block"><?= htmlspecialchars($dbDiag['database']) ?></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">User</span>
                            <span class="text-slate-700 truncate block"><?= htmlspecialchars($dbDiag['user']) ?></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">PHP Driver</span>
                            <span class="text-indigo-600 font-semibold block">PDO MySQL (<?= phpversion() ?>)</span>
                        </div>
                    </div>

                    <?php if (!$dbDiag['connected'] && !empty($dbDiag['error'])): ?>
                        <div class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-mono break-words">
                            <?= htmlspecialchars($dbDiag['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Environment Details Strip -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-xs">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">App URL</span>
                        <a href="<?= htmlspecialchars($appUrl) ?>" target="_blank" class="text-blue-600 hover:underline font-mono font-medium truncate block mt-0.5">
                            <?= htmlspecialchars($appUrl) ?>
                        </a>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-xs">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Server Time</span>
                        <span class="text-slate-700 font-mono block mt-0.5"><?= date('Y-m-d H:i:s T') ?></span>
                    </div>
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Architecture: Clean Rebuild v2.0 (Light Mode)</span>
                <span>PHP <?= phpversion() ?></span>
            </div>
        </div>
    </div>

</body>
</html>
