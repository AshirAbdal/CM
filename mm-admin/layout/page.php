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
                    <a href="/customer-info-details" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'leads' ? 'bg-gray-700 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9993;</span>
                        Customer Information
                    </a>
                </li>
                <li>
                    <a href="/images" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'images' ? 'bg-gray-700 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9654;</span>
                        Image Manager
                    </a>
                </li>
                <li>
                    <a href="/inventory" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'inventory' ? 'bg-gray-700 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9707;</span>
                        Inventory
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

                <!-- ── Notification bell ──────────────────────────── -->
                <div class="relative" id="notif-wrap">
                    <button id="notif-btn"
                            onclick="toggleNotif()"
                            class="relative text-gray-400 hover:text-gray-700 transition-colors p-1"
                            aria-label="Notifications">
                        <!-- Bell SVG -->
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span id="notif-badge"
                              class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 text-white
                                     text-[10px] font-bold flex items-center justify-center hidden">
                            0
                        </span>
                    </button>

                    <!-- Dropdown -->
                    <div id="notif-dropdown"
                         class="hidden absolute right-0 top-9 w-80 bg-white border border-gray-200
                                rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-700">Notifications</p>
                            <button onclick="markAllRead()"
                                    class="text-xs text-blue-600 hover:underline">Mark all read</button>
                        </div>
                        <ul id="notif-list"
                            class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                            <li class="px-4 py-3 text-xs text-gray-400 italic">Loading…</li>
                        </ul>
                        <div class="px-4 py-2 border-t border-gray-100 text-center">
                            <a href="/customer-info-details"
                               class="text-xs text-blue-600 hover:underline">View all customers →</a>
                        </div>
                    </div>
                </div>

                <a href="/logout" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">Sign out</a>
            </div>
        </header>

        <main class="flex-1 p-6 lg:p-8">
            <?= $pageContent ?? '' ?>
        </main>
    </div>

</div>

<script>
(function () {
    // ── Config injected server-side so JS can call the API ──
    // These are already defined by each page (customer_info_details etc.)
    // Fall back to empty string when on a page that doesn't define them.
    const apiBase = (typeof _apiBase !== 'undefined') ? _apiBase : '';
    const apiKey  = (typeof _apiKey  !== 'undefined') ? _apiKey  : '';
    const jwt     = (typeof _jwt     !== 'undefined') ? _jwt     : '';

    let loaded = false;

    function fetchNotifs() {
        if (!apiBase || !jwt) return;
        fetch(apiBase + '/wl/admin/notifications?limit=15', {
            headers: {
                'X-API-Key':     apiKey,
                'Authorization': 'Bearer ' + jwt,
            }
        })
        .then(r => r.json())
        .then(json => {
            if (!json.success) return;
            renderBadge(json.unread_count);
            renderList(json.data);
            loaded = true;
        })
        .catch(() => {});
    }

    function renderBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function renderList(items) {
        const list = document.getElementById('notif-list');
        if (!list) return;
        if (!items || items.length === 0) {
            list.innerHTML = '<li class="px-4 py-3 text-xs text-gray-400 italic">No notifications yet.</li>';
            return;
        }
        list.innerHTML = items.map(n => {
            const dot  = n.is_read
                ? 'bg-gray-200'
                : 'bg-blue-500';
            const date = (n.created_at || '').substring(0, 16).replace('T', ' ');
            const src  = n.source ? '<span class="text-[10px] bg-orange-50 text-orange-600 border border-orange-200 px-1 rounded">' + esc(n.source) + '</span>' : '';
            const href = '/customer-info?CR_id=' + encodeURIComponent(n.CR_id);
            return '<li>'
                 + '<a href="' + href + '" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">'
                 + '<span class="mt-1.5 w-2 h-2 rounded-full shrink-0 ' + dot + '"></span>'
                 + '<div class="flex-1 min-w-0">'
                 + '<div class="flex items-center gap-2 flex-wrap">'
                 + '<p class="text-xs font-medium text-gray-800 truncate">' + esc(n.name || n.email) + '</p>'
                 + src
                 + '</div>'
                 + '<p class="text-xs text-gray-500 mt-0.5 leading-snug">' + esc(n.message) + '</p>'
                 + '<p class="text-[10px] text-gray-400 mt-1">' + esc(date) + '</p>'
                 + '</div></a></li>';
        }).join('');
    }

    window.toggleNotif = function () {
        const dd = document.getElementById('notif-dropdown');
        if (!dd) return;
        const isHidden = dd.classList.toggle('hidden');
        if (!isHidden && !loaded) fetchNotifs();
    };

    window.markAllRead = function () {
        if (!apiBase || !jwt) return;
        fetch(apiBase + '/wl/admin/notifications/read', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-API-Key':     apiKey,
                'Authorization': 'Bearer ' + jwt,
            },
            body: JSON.stringify({})
        })
        .then(() => {
            renderBadge(0);
            // update dots in dropdown to grey
            document.querySelectorAll('#notif-list .bg-blue-500')
                .forEach(el => { el.classList.replace('bg-blue-500', 'bg-gray-200'); });
        })
        .catch(() => {});
    };

    // Close dropdown when clicking outside
    document.addEventListener('click', e => {
        const wrap = document.getElementById('notif-wrap');
        if (wrap && !wrap.contains(e.target)) {
            const dd = document.getElementById('notif-dropdown');
            if (dd) dd.classList.add('hidden');
        }
    });

    function esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Load badge count on page load (doesn't open the dropdown)
    fetchNotifs();
})();
</script>
</body>
<?php endif; ?>
</html>
