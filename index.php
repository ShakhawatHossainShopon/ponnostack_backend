<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Entry Point & Health Diagnostics
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
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 selection:bg-emerald-500 selection:text-white">

    <div class="w-full max-w-xl">
        <!-- Main Card Container -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
            <!-- Subtle Background Glow -->
            <div class="absolute -top-24 -right-24 w-64 h-64 <?= $dbDiag['connected'] ? 'bg-emerald-500/10' : 'bg-rose-500/10' ?> rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top Header & Environment Pill -->
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/80 pb-5 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-white flex items-center gap-2">
                        <span><?= htmlspecialchars($appName) ?></span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider <?= $isProd ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' ?>">
                            <?= htmlspecialchars($appEnv) ?>
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">Multi-Tenant E-Commerce Platform Core</p>
                </div>

                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $dbDiag['connected'] ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30' ?>">
                        <span class="w-2 h-2 rounded-full <?= $dbDiag['connected'] ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' ?>"></span>
                        <?= $dbDiag['connected'] ? 'System Healthy' : 'Database Offline' ?>
                    </span>
                </div>
            </div>

            <!-- Status Indicator Panel -->
            <div class="space-y-4">
                <!-- Database Connection Diagnostic Card -->
                <div class="bg-slate-950/60 border <?= $dbDiag['connected'] ? 'border-emerald-500/30' : 'border-rose-500/40' ?> rounded-2xl p-5 transition">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl <?= $dbDiag['connected'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' ?> flex items-center justify-center font-bold text-sm">
                                <?= $dbDiag['connected'] ? '✓' : '✕' ?>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">MySQL Database Connection</h3>
                                <p class="text-[11px] <?= $dbDiag['connected'] ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= $dbDiag['connected'] ? 'Active & Ready for Queries' : 'Connection failed to establish' ?>
                                </p>
                            </div>
                        </div>

                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                            <?= htmlspecialchars($dbDiag['version']) ?>
                        </span>
                    </div>

                    <!-- Connection Metadata Table -->
                    <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-slate-900/60 p-3 rounded-xl border border-slate-800/80">
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">Host</span>
                            <span class="text-slate-200 truncate block"><?= htmlspecialchars($dbDiag['host']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">Database</span>
                            <span class="text-emerald-400 font-bold truncate block"><?= htmlspecialchars($dbDiag['database']) ?></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">User</span>
                            <span class="text-slate-300 truncate block"><?= htmlspecialchars($dbDiag['user']) ?></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">PHP Driver</span>
                            <span class="text-indigo-300 block">PDO MySQL (<?= phpversion() ?>)</span>
                        </div>
                    </div>

                    <?php if (!$dbDiag['connected'] && !empty($dbDiag['error'])): ?>
                        <div class="mt-3 p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-300 text-xs font-mono break-words">
                            <?= htmlspecialchars($dbDiag['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Environment Details Strip -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-950/40 border border-slate-800/60 rounded-xl p-3">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">App URL</span>
                        <a href="<?= htmlspecialchars($appUrl) ?>" target="_blank" class="text-sky-400 hover:underline font-mono truncate block mt-0.5">
                            <?= htmlspecialchars($appUrl) ?>
                        </a>
                    </div>
                    <div class="bg-slate-950/40 border border-slate-800/60 rounded-xl p-3">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">Server Time</span>
                        <span class="text-slate-300 font-mono block mt-0.5"><?= date('Y-m-d H:i:s T') ?></span>
                    </div>
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-slate-500">
                <span>Architecture: Clean Rebuild v2.0</span>
                <span>Powered by PHP <?= phpversion() ?></span>
            </div>
        </div>
    </div>

</body>
</html>
