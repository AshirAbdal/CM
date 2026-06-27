<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
$layout = $layout ?? 'app';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="generator" content="clickdigim v1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageMeta['title'] ?? 'Admin | Majestic Marquees') ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
                        // ── Brand theme (mirrors mm-frontend) ───────────────
                        primary:    '#a57b5b',   // brand tan (was #2563eb)
                        sidebar:    '#a57b5b',   // brand tan
                        background: '#faf6ec',   // brand cream
                        card:       '#ffffff',
                        cream:  { 50: '#faf6ec', 100: '#f4ecd9', 200: '#ede1c4' },
                        forest: { 500: '#586b4f', 600: '#475a40', 700: '#3a4a3a', 800: '#3f503c', 900: '#23301f' },
                        tan:    { 50: '#f6f1ea', 100: '#efe3d5', 200: '#e2cdb4', 300: '#d0b08a', 400: '#bd9676', 500: '#a57b5b', 600: '#8c6849', 700: '#70533a', 800: '#5a4430', 900: '#4a3829' },
                        // Re-map Tailwind's built-in blue onto the brand tan so
                        // every existing `blue-*` accent class becomes brand-
                        // coloured without touching page markup or logic.
                        blue:   { 50: '#f6f1ea', 100: '#efe3d5', 200: '#e2cdb4', 300: '#d0b08a', 400: '#bd9676', 500: '#a57b5b', 600: '#8c6849', 700: '#70533a', 800: '#5a4430', 900: '#4a3829' }
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
<body class="bg-cream-50 text-gray-800">
<div class="flex min-h-screen bg-cream-50">

    <!-- Mobile sidebar overlay -->
    <div id="sidebar-overlay" onclick="closeSidebar()"
         class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-30 w-60 shrink-0 bg-tan-500 text-gray-100 flex flex-col overflow-y-auto -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen transition-transform duration-300">
        <div class="px-6 py-5 border-b border-tan-600">
            <div class="flex items-center justify-center">
                <img src="/logo-original.webp" alt="Majestic Marquees" class="h-16 w-auto object-contain">
            </div>
            <p class="mt-3 text-center text-[11px] uppercase tracking-widest text-cream-100">Admin Panel</p>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-0.5 px-3">
                <?php if (can('dashboard.view')): ?>
                <li>
                    <a href="/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'dashboard' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9638;</span>
                        Dashboard
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can_any(['leads.view', 'customers.view'])): ?>
                <li>
                    <a href="/customer-info-details" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'leads' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9993;</span>
                        Customer Information
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can('inventory.view')): ?>
                <li>
                    <a href="/inventory" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'inventory' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9707;</span>
                        Inventory
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can('leads.view')): ?>
                <li>
                    <a href="/lead-management" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'lead-management' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9889;</span>
                        Lead Management
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can_any(['reviews.view', 'reviews.manage'])): ?>
                <li>
                    <a href="/reviews" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'reviews' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9733;</span>
                        Reviews
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('posts.view')): ?>
                <li>
                    <a href="/posts" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'posts' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none">&#9998;</span>
                        Blog
                    </a>
                </li>
                <?php endif; ?>

                <?php
                    // "Settings" groups the configuration pages (survey + AI
                    // context, image manager, Xero, users & roles). It is a pure
                    // toggle (no page of its own) and auto-expands when one of
                    // its children is the active page. Each child is gated by
                    // its own permission, and the group only renders when at
                    // least one child is visible.
                    $settingsNavs = ['survey-questions', 'ai-settings', 'smtp-settings', 'images', 'xero', 'user-management'];
                    $settingsOpen = in_array($activeNav ?? '', $settingsNavs, true);
                    $canSurvey     = can('survey.manage');
                    $canAi         = can('ai.manage');
                    $canSmtp       = can('smtp.manage');
                    $canImages     = can('images.view');
                    $canXero       = can('xero.manage');
                    $canUsersRoles = can('users.manage') || can('roles.manage');
                    $showSettings = $canSurvey || $canAi || $canSmtp || $canImages || $canXero || $canUsersRoles;
                ?>
                <?php if ($showSettings): ?>
                <li>
                    <button type="button"
                            id="settings-toggle"
                            data-submenu-toggle="settings"
                            aria-controls="settings-submenu"
                            aria-expanded="<?= $settingsOpen ? 'true' : 'false' ?>"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= $settingsOpen ? 'text-white' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                        <span class="text-base leading-none shrink-0">&#9881;</span>
                        <span class="flex-1 text-left">Settings</span>
                        <svg id="settings-chevron" class="w-4 h-4 shrink-0 transition-transform duration-200 <?= $settingsOpen ? 'rotate-180' : '' ?>" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <ul id="settings-submenu" class="mt-0.5 space-y-0.5 pl-3 <?= $settingsOpen ? '' : 'hidden' ?>">
                        <?php if ($canSurvey): ?>
                        <li>
                            <a href="/survey-questions" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'survey-questions' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                                <span class="text-base leading-none shrink-0">&#9783;</span>
                                Survey &amp; AI Context
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($canAi): ?>
                        <li>
                            <a href="/ai-settings" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'ai-settings' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                                <span class="text-base leading-none shrink-0">&#10024;</span>
                                AI Settings
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($canSmtp): ?>
                        <li>
                            <a href="/smtp-settings" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'smtp-settings' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                                <span class="text-base leading-none shrink-0">&#9993;</span>
                                Email / SMTP
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($canImages): ?>
                        <li>
                            <a href="/images" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'images' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                                <span class="text-base leading-none shrink-0">&#9654;</span>
                                Image Manager
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($canXero): ?>
                        <li>
                            <a href="/xero" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'xero' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                                <span class="text-base leading-none shrink-0">&#128179;</span>
                                Xero Integration
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($canUsersRoles): ?>
                        <li>
                            <a href="/user-management" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors <?= ($activeNav ?? '') === 'user-management' ? 'bg-tan-700 text-white font-medium' : 'text-cream-100 hover:bg-tan-600 hover:text-white' ?>">
                                <span class="text-base leading-none shrink-0">&#128101;</span>
                                Users &amp; Roles
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="px-6 py-4 border-t border-tan-600 text-xs text-cream-100">
            v1.0 &middot; Admin
        </div>
    </aside>

    <!-- Main column -->
    <div class="flex flex-col flex-1 min-w-0">
        <header class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center justify-between">
            <button onclick="openSidebar()" aria-label="Open menu"
                    class="lg:hidden p-2 -ml-1 text-gray-500 hover:text-gray-800 transition-colors">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>
            <div class="hidden lg:block"></div>
            <div class="flex items-center gap-4">
                <?php if (!empty($_SESSION['admin_name'])): ?>
                <div class="hidden sm:flex flex-col items-end leading-tight">
                    <span class="text-sm text-gray-700"><?= e($_SESSION['admin_name']) ?></span>
                    <?php if (current_role_name() !== ''): ?>
                    <span class="text-[11px] text-gray-400"><?= e(current_role_name()) ?></span>
                    <?php endif; ?>
                </div>
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
                         class="hidden absolute right-0 top-9 w-[calc(100vw-1rem)] sm:w-80 max-w-80 bg-white border border-gray-200
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

                <a href="/change-password" class="text-sm px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">Change password</a>
                <a href="/logout" class="text-sm px-3 py-1.5 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors">Sign out</a>
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

<script>
function openSidebar() {
    document.getElementById('admin-sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('admin-sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.add('hidden');
    document.body.style.overflow = '';
}
</script>

<script>
// Collapsible sidebar groups (e.g. Settings): toggle the submenu + rotate chevron.
document.querySelectorAll('[data-submenu-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var id      = btn.getAttribute('data-submenu-toggle');
        var submenu = document.getElementById(id + '-submenu');
        var chevron = document.getElementById(id + '-chevron');
        if (!submenu) return;
        submenu.classList.toggle('hidden');
        var expanded = !submenu.classList.contains('hidden');
        if (chevron) chevron.classList.toggle('rotate-180', expanded);
        btn.setAttribute('aria-expanded', String(expanded));
    });
});
</script>
</body>
<?php endif; ?>
</html>
