<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// Fetch notifications feed (latest 20, all — not unread only)
$ch = curl_init(API_BASE . '/wl/admin/notifications?limit=20');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: '            . API_KEY,
        'Origin: '               . ORIGIN,
        'Authorization: Bearer ' . ($_SESSION['jwt'] ?? ''),
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$res    = json_decode(curl_exec($ch), true);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status === 401) {
    session_destroy();
    header('Location: /login');
    exit;
}

$notifications = $res['data']         ?? [];
$unreadCount   = (int) ($res['unread_count'] ?? 0);

// Fetch customer totals for stats
$ch2 = curl_init(API_BASE . '/wl/admin/leads?limit=1');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: '            . API_KEY,
        'Origin: '               . ORIGIN,
        'Authorization: Bearer ' . ($_SESSION['jwt'] ?? ''),
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$res2 = json_decode(curl_exec($ch2), true);
curl_close($ch2);
$totalCustomers = (int) ($res2['meta']['total'] ?? 0);

$jsApiBase = json_encode(API_BASE);
$jsApiKey  = json_encode(API_KEY);
$jsOrigin  = json_encode(ORIGIN);
$jsJwt     = json_encode($_SESSION['jwt'] ?? '');

$layout    = 'app';
$activeNav = 'dashboard';
?>
<script type="application/json" id="page-meta">
{
    "title": "Dashboard — Majestic Marquees Admin",
    "description": "Admin dashboard for Majestic Marquees"
}
</script>

<script>
const _apiBase   = <?= $jsApiBase ?>;
const _apiKey    = <?= $jsApiKey ?>;
const _apiOrigin = <?= $jsOrigin ?>;
const _jwt       = <?= $jsJwt ?>;
</script>

<div class="space-y-8">

    <!-- Heading -->
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-1">Latest activity from your website forms.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Customers</p>
            <p class="mt-1 text-3xl font-bold text-gray-800"><?= $totalCustomers ?></p>
            <p class="text-xs text-gray-400 mt-1">all time</p>
        </div>
        <div class="bg-white rounded-xl border <?= $unreadCount > 0 ? 'border-blue-200' : 'border-gray-200' ?> p-5">
            <p class="text-sm <?= $unreadCount > 0 ? 'text-blue-600 font-medium' : 'text-gray-500' ?>">Unread</p>
            <p class="mt-1 text-3xl font-bold <?= $unreadCount > 0 ? 'text-blue-600' : 'text-gray-800' ?>"><?= $unreadCount ?></p>
            <p class="text-xs text-gray-400 mt-1">new form submissions</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Showing</p>
            <p class="mt-1 text-3xl font-bold text-gray-800"><?= count($notifications) ?></p>
            <p class="text-xs text-gray-400 mt-1">most recent submissions</p>
        </div>
    </div>

    <!-- Notifications feed -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-700">Recent Activity</h3>
            <?php if ($unreadCount > 0): ?>
            <button onclick="markAllReadDashboard()"
                    class="text-xs text-blue-600 hover:underline transition-colors">
                Mark all read
            </button>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <?php if (empty($notifications)): ?>
            <p class="px-6 py-10 text-center text-sm text-gray-400">No submissions yet.</p>
            <?php else: ?>
            <ul class="divide-y divide-gray-100" id="dash-notif-list">
                <?php foreach ($notifications as $n): ?>
                <li class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition-colors <?= !$n['is_read'] ? 'bg-blue-50/30' : '' ?>"
                    id="notif-row-<?= (int)$n['submission_id'] ?>">

                    <!-- Unread dot -->
                    <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 <?= !$n['is_read'] ? 'bg-blue-500' : 'bg-gray-200' ?>"></span>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Customer name — links to their detail page -->
                            <a href="/customer-info?CR_id=<?= (int)$n['CR_id'] ?>"
                               class="text-sm font-medium text-gray-800 hover:text-blue-600 transition-colors">
                                <?= e($n['name'] ?? $n['email']) ?>
                            </a>
                            <span class="text-xs text-gray-400"><?= e($n['email']) ?></span>
                            <!-- Source badge -->
                            <?php if (!empty($n['source'])): ?>
                            <span class="text-xs bg-orange-50 text-orange-600 border border-orange-200 px-1.5 py-0.5 rounded-full font-medium">
                                &#9733; <?= e($n['source']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-600 mt-0.5"><?= e($n['message']) ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= e(substr($n['created_at'] ?? '', 0, 16)) ?></p>
                    </div>

                    <!-- Mark individual read -->
                    <?php if (!$n['is_read']): ?>
                    <button onclick="markOneRead(<?= (int)$n['submission_id'] ?>)"
                            class="text-xs text-gray-400 hover:text-blue-600 transition-colors shrink-0 mt-0.5"
                            title="Mark as read">&#10003;</button>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function markOneRead(id) {
    fetch(_apiBase + '/wl/admin/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-API-Key':     _apiKey,
            'Authorization': 'Bearer ' + _jwt,
        },
        body: JSON.stringify({ ids: [id] })
    }).then(() => {
        const row = document.getElementById('notif-row-' + id);
        if (!row) return;
        row.classList.remove('bg-blue-50/30');
        const dot = row.querySelector('.bg-blue-500');
        if (dot) { dot.classList.replace('bg-blue-500', 'bg-gray-200'); }
        const btn = row.querySelector('button');
        if (btn) btn.remove();
    });
}

function markAllReadDashboard() {
    fetch(_apiBase + '/wl/admin/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-API-Key':     _apiKey,
            'Authorization': 'Bearer ' + _jwt,
        },
        body: JSON.stringify({})
    }).then(() => {
        document.querySelectorAll('#dash-notif-list .bg-blue-500')
            .forEach(el => el.classList.replace('bg-blue-500', 'bg-gray-200'));
        document.querySelectorAll('#dash-notif-list .bg-blue-50\\/30')
            .forEach(el => el.classList.remove('bg-blue-50/30'));
        document.querySelectorAll('#dash-notif-list button').forEach(b => b.remove());
    });
}
</script>
