<?php
// Reset-password page - public (no JWT).
// Takes the email + 6-digit code from the reset email and a new password.
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002' : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$error = '';
$done  = false;
$email = trim($_GET['email'] ?? '');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);

    $email   = trim($_POST['email'] ?? '');
    $code    = preg_replace('/\D/', '', $_POST['code'] ?? '');
    $pw      = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($code) !== 6) {
        $error = 'Enter the 6-digit code from your email.';
    } elseif (!(strlen($pw) >= 8
        && preg_match('/[A-Z]/', $pw)
        && preg_match('/[a-z]/', $pw)
        && preg_match('/[0-9]/', $pw)
        && preg_match('/[^A-Za-z0-9]/', $pw))) {
        $error = 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.';
    } elseif ($pw !== $confirm) {
        $error = 'The passwords do not match.';
    } else {
        $ch = curl_init(API_BASE . '/wl/admin/password/reset');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'email'        => $email,
                'code'         => $code,
                'new_password' => $pw,
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

        if ($status === 200 && !empty($res['success'])) {
            $done = true;
        } elseif ($status === 429) {
            $error = 'Too many attempts. Please wait a few minutes and try again.';
        } else {
            $error = $res['error'] ?? 'The code is invalid or has expired. Please request a new one.';
        }
    }

    if (!$done) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$layout = 'auth';
?>
<script type="application/json" id="page-meta">
{
    "title": "Reset password - Majestic Marquees",
    "description": "Choose a new Majestic Marquees admin password"
}
</script>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <img src="/logo-original.webp" alt="Majestic Marquees" class="mx-auto h-20 w-auto object-contain">
            <p class="mt-3 text-sm text-gray-500">Admin Panel</p>
        </div>

        <?php if ($done): ?>
        <div class="w-full p-8 bg-white rounded-2xl shadow-md text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600 text-2xl">&#10003;</div>
            <h2 class="mb-2 text-xl font-semibold text-gray-700">Password reset</h2>
            <p class="mb-6 text-sm text-gray-500">Your password has been reset. You can now sign in with your new password.</p>
            <a href="/login" class="block w-full py-3 text-sm font-semibold text-white bg-tan-500 rounded-lg hover:bg-tan-600 transition">
                Go to sign in
            </a>
        </div>
        <?php else: ?>
        <form method="POST" action="/reset-password" class="w-full p-8 bg-white rounded-2xl shadow-md">
            <h2 class="mb-2 text-xl font-semibold text-gray-700">Reset password</h2>
            <p class="mb-6 text-sm text-gray-500">Enter the code we emailed you and choose a new password.</p>

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
                    value="<?= e($email) ?>"
                    placeholder="admin@example.com"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-tan-400"
                    required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-600">Reset code</label>
                <input
                    type="text"
                    name="code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    placeholder="123456"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm tracking-[0.3em] text-center focus:ring-2 focus:ring-tan-400"
                    required autofocus>
            </div>

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-600">New password</label>
                <input
                    type="password"
                    name="new_password"
                    id="rp-password"
                    minlength="8"
                    placeholder="At least 8 characters"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-tan-400"
                    required>
                <ul id="pw-reqs" class="mt-2 space-y-1 text-xs text-gray-500">
                    <li data-req="len"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> At least 8 characters</li>
                    <li data-req="upper"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> An uppercase letter (A-Z)</li>
                    <li data-req="lower"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A lowercase letter (a-z)</li>
                    <li data-req="num"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A number (0-9)</li>
                    <li data-req="special" class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A special character (e.g. !?@#$)</li>
                </ul>
            </div>

            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium text-gray-600">Confirm new password</label>
                <input
                    type="password"
                    name="confirm_password"
                    id="rp-confirm"
                    minlength="8"
                    placeholder="Re-enter new password"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-tan-400"
                    required>
                <p id="rp-match" class="mt-1.5 text-xs hidden"></p>
            </div>

            <button
                type="submit"
                id="rp-submit"
                class="w-full py-3 text-sm font-semibold text-white bg-tan-500 rounded-lg hover:bg-tan-600 transition">
                Reset password
            </button>

            <div class="mt-6 text-center">
                <a href="/forgot-password" class="text-sm text-tan-600 hover:underline">Need a new code?</a>
                <span class="text-gray-300">&middot;</span>
                <a href="/login" class="text-sm text-tan-600 hover:underline">Back to sign in</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const pw = document.getElementById('rp-password');
    if (!pw) return; // success state has no form
    const confirm = document.getElementById('rp-confirm');
    const reqs    = document.getElementById('pw-reqs');
    const match   = document.getElementById('rp-match');
    const submit  = document.getElementById('rp-submit');

    function checks(v) {
        return {
            len:     v.length >= 8,
            upper:   /[A-Z]/.test(v),
            lower:   /[a-z]/.test(v),
            num:     /[0-9]/.test(v),
            special: /[^A-Za-z0-9]/.test(v),
        };
    }
    function allOk(c) { return c.len && c.upper && c.lower && c.num && c.special; }

    function render() {
        const c = checks(pw.value);
        reqs.querySelectorAll('li[data-req]').forEach(function (li) {
            const ok  = c[li.getAttribute('data-req')];
            const dot = li.querySelector('.pw-dot');
            li.classList.toggle('text-green-600', ok);
            li.classList.toggle('text-gray-500', !ok);
            dot.innerHTML = ok ? '&#10003;' : '&#9675;';
        });
        if (confirm.value) {
            const same = confirm.value === pw.value;
            match.textContent = same ? 'Passwords match.' : 'Passwords do not match.';
            match.classList.remove('hidden', 'text-green-600', 'text-red-600');
            match.classList.add(same ? 'text-green-600' : 'text-red-600');
        } else {
            match.classList.add('hidden');
        }
        submit.disabled = !(allOk(c) && confirm.value === pw.value);
        submit.classList.toggle('opacity-50', submit.disabled);
    }

    pw.addEventListener('input', render);
    confirm.addEventListener('input', render);
    render();
})();
</script>
