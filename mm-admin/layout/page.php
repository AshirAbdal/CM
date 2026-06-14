<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
$layout = $layout ?? 'app';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageMeta['title'] ?? 'Admin | Majestic Marquees') ?></title>
    <link rel="icon" href="data:,">
    <?php if (!empty($pageMeta['description'])): ?>
    <meta name="description" content="<?= e($pageMeta['description']) ?>">
    <?php endif; ?>
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:    '#2563eb',
                        sidebar:    '#0f172a',
                        background: '#f8fafc',
                        card:       '#ffffff'
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body { @apply font-sans antialiased; }
        }
    </style>
</head>
<?php if ($layout === 'auth'): ?>
<body class="bg-gray-100 text-gray-800">
    <?= $pageContent ?? '' ?>
</body>
<?php else: ?>
<body class="bg-gray-50 text-gray-800">
<div class="flex min-h-screen bg-gray-50">

    <!-- Sidebar -->
    <aside class="w-60 shrink-0 bg-gray-900 text-gray-200 flex flex-col sticky top-0 h-screen overflow-y-auto">
        <div class="px-6 py-5 border-b border-gray-700">
            <p class="text-xs uppercase tracking-widest text-gray-400">Admin Panel</p>
            <h1 class="mt-1 text-sm font-semibold text-white leading-snug">Majestic Marquees</h1>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-0.5 px-3">
                <li>
                    <a href="/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'dashboard' ? 'bg-gray-700 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9638;</span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/images" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'images' ? 'bg-gray-700 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9654;</span>
                        Image Manager
                    </a>
                </li>
            </ul>
        </nav>

        <div class="px-6 py-4 border-t border-gray-700 text-xs text-gray-500">
            v1.0 &middot; Admin
        </div>
    </aside>

    <!-- Main column -->
    <div class="flex flex-col flex-1 min-w-0">
        <header class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
            <div></div>
            <div class="flex items-center gap-4">
                <?php if (!empty($_SESSION['admin_name'])): ?>
                <span class="text-sm text-gray-600"><?= e($_SESSION['admin_name']) ?></span>
                <?php endif; ?>
                <a href="/logout" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">Sign out</a>
            </div>
        </header>

        <main class="flex-1 p-6 lg:p-8">
            <?= $pageContent ?? '' ?>
        </main>
    </div>

</div>
</body>
<?php endif; ?>
</html>
