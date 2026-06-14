<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
define('API_KEY',  'mq-prod-public-key-001');
define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);
// PRIVATE request — X-API-Key + Origin + Authorization: Bearer JWT
$ch = curl_init(API_BASE . '/wl/admin/leads');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: '       . API_KEY,
        'Origin: '          . ORIGIN,
        'Authorization: Bearer ' . $_SESSION['jwt'],
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$res    = json_decode(curl_exec($ch), true);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status === 401) {
    // JWT expired or invalid — kick back to login
    session_destroy();
    header('Location: /login');
    exit;
}
$submissions = $res['data'] ?? [];

$layout    = 'app';
$activeNav = 'dashboard';
?>
<script type="application/json" id="page-meta">
{
    "title": "Dashboard — Majestic Marquees Admin",
    "description": "Admin dashboard for Majestic Marquees"
}
</script>

<div class="space-y-8">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-1">Form submissions received from the website.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Submissions</p>
            <p class="mt-1 text-3xl font-bold text-gray-800"><?= count($submissions) ?></p>
            <p class="text-xs text-gray-400 mt-1">across all forms</p>
        </div>
    </div>

    <!-- Submissions table -->
    <div>
        <h3 class="text-base font-semibold text-gray-700 mb-4">Form Submissions</h3>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($submissions as $row): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 text-gray-800 font-medium"><?= e($row['name'] ?? '') ?></td>
                        <td class="px-5 py-3 text-gray-600"><?= e($row['email'] ?? '') ?></td>
                        <td class="px-5 py-3 text-gray-600"><?= e($row['message'] ?? '') ?></td>
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap"><?= e($row['created_at'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-gray-400">No submissions yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
