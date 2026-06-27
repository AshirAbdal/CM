<?php
// Forgot-password request page - public (no JWT).
// Collects an email and asks the backend to send a 6-digit reset code.
// The backend always responds generically, so this page never reveals
// whether an account exists.
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002' : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$error = '';
$sent  = false;
$email = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);

    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $ch = curl_init(API_BASE . '/wl/admin/password/forgot');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['email' => $email, 'base_url' => ORIGIN]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-API-Key: ' . API_KEY,
                'Origin: '    . ORIGIN,
                'User-Agent: '      . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
                'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 429) {
            $error = 'Too many requests. Please wait a few minutes and try again.';
        } else {
            // Any other outcome is reported the same way - never leak existence.
            $sent = true;
        }
    }

    if (!$sent) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$layout = 'auth';
?>
<script type="application/json" id="page-meta">
{
    "title": "Forgot password - Majestic Marquees",
    "description": "Reset your Majestic Marquees admin password"
}
</script>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <img src="/logo-original.webp" alt="Majestic Marquees" class="mx-auto h-20 w-auto object-contain">
            <p class="mt-3 text-sm text-gray-500">Admin Panel</p>
        </div>

        <?php if ($sent): ?>
        <div class="w-full p-8 bg-white rounded-2xl shadow-md text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600 text-2xl">&#9993;</div>
            <h2 class="mb-2 text-xl font-semibold text-gray-700">Check your email</h2>
            <p class="mb-6 text-sm text-gray-500">
                If that email is registered, we have sent a 6-digit reset code.
                Enter it on the next page to choose a new password.
            </p>
            <a href="/reset-password?email=<?= rawurlencode($email) ?>"
               class="block w-full py-3 text-sm font-semibold text-white bg-tan-500 rounded-lg hover:bg-tan-600 transition">
                Enter reset code
            </a>
            <a href="/login" class="mt-4 inline-block text-sm text-tan-600 hover:underline">Back to sign in</a>
        </div>
        <?php else: ?>
        <form method="POST" action="/forgot-password" class="w-full p-8 bg-white rounded-2xl shadow-md">
            <h2 class="mb-2 text-xl font-semibold text-gray-700">Forgot password</h2>
            <p class="mb-6 text-sm text-gray-500">Enter your email and we will send you a reset code.</p>

            <?php if ($error): ?>
            <div class="mb-4 px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium text-gray-600">Email</label>
                <input
                    type="email"
                    name="email"
                    value="<?= e($email) ?>"
                    placeholder="admin@example.com"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-tan-400"
                    required autofocus>
            </div>

            <button
                type="submit"
                class="w-full py-3 text-sm font-semibold text-white bg-tan-500 rounded-lg hover:bg-tan-600 transition">
                Send reset code
            </button>

            <div class="mt-6 text-center">
                <a href="/login" class="text-sm text-tan-600 hover:underline">Back to sign in</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
