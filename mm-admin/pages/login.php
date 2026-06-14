<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
define('API_KEY',  'mq-prod-public-key-001');
define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';
    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);
    // PUBLIC request — X-Tenant-Key + Origin (no JWT yet)
    $ch = curl_init(API_BASE . '/wl/admin/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'email'    => $_POST['email']    ?? '',
            'password' => $_POST['password'] ?? '',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . API_KEY,
            'Origin: '    . ORIGIN,
            'User-Agent: '      . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $res    = json_decode(curl_exec($ch), true);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 200 && isset($res['token'])) {
        session_regenerate_id(true);
        $_SESSION['jwt'] = $res['token'];
        if (!empty($res['user']['name'])) {
            $_SESSION['admin_name'] = $res['user']['name'];
        }
        header('Location: /dashboard');
        exit;
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $error = 'Invalid email or password.';
}

$layout = 'auth';
?>
<script type="application/json" id="page-meta">
{
    "title": "Admin Login — Majestic Marquees",
    "description": "Admin login for Majestic Marquees"
}
</script>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-800">Majestic Marquees</h1>
            <p class="mt-1 text-sm text-gray-500">Admin Panel</p>
        </div>
        <form method="POST" action="/login" class="w-full p-8 bg-white rounded-2xl shadow-md">
            <h2 class="mb-6 text-xl font-semibold text-gray-700">Sign in</h2>

            <?php if ($error): ?>
            <div class="mb-4 px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-600">Email</label>
                <input
                    type="email"
                    name="email"
                    placeholder="admin@example.com"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-gray-300"
                    required>
            </div>

            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium text-gray-600">Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-gray-300"
                    required>
            </div>

            <button
                type="submit"
                class="w-full py-3 text-sm font-semibold text-white bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                Sign in
            </button>
        </form>
    </div>
</div>
