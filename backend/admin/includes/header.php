<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $activePage */
$pageTitle = $pageTitle ?? 'অ্যাডমিন ড্যাশবোর্ড | Ponnostack';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Hind Siliguri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Hind Siliguri"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#F0FDF4',
                            100: '#DCFCE7',
                            200: '#BBF7D0',
                            300: '#86EFAC',
                            400: '#4ADE80',
                            500: '#00ED64',
                            600: '#00684A',
                            700: '#00523A',
                            800: '#003E2C',
                            900: '#001E17',
                        }
                    },
                    boxShadow: {
                        'brand-glow': '0 10px 25px -5px rgba(0, 104, 74, 0.25)',
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Top Page Transition Loader */
        #topProgressBar {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
            box-shadow: 0 0 10px rgba(0, 104, 74, 0.7);
        }

        /* Smooth Page Entry Animation (Opacity only to prevent position:fixed clipping) */
        .page-content-animate {
            animation: pageFadeIn 0.2s ease-out forwards;
        }

        @keyframes pageFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Shimmer Wave for Skeletons (Pure Light Mode) */
        .skeleton-shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmerWave 1.5s infinite;
        }

        @keyframes shimmerWave {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Toast Entrance Animation */
        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .toast-animate-in {
            animation: toastSlideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen h-[100dvh] overflow-hidden antialiased selection:bg-indigo-600 selection:text-white flex flex-col">

    <!-- Universal Top Loading Progress Bar -->
    <div id="topProgressBar" class="fixed top-0 left-0 h-[3px] bg-indigo-600 z-50 opacity-0 pointer-events-none" style="width: 0%;"></div>

    <!-- Master App Layout (Sidebar + Main Content) -->
    <div class="flex-1 h-full flex overflow-hidden">
