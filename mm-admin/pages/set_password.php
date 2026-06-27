<?php
// Set-password page (invite acceptance) - public (no JWT).
// Opened from the invitation email link: /set-password?token={64-hex}
// GET  -> look up the invite and show the invitee's name/email.
// POST -> set the chosen password and activate the account.
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002' : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$token = preg_replace('/[^a-f0-9]/', '', $_POST['token'] ?? $_GET['token'] ?? '');

$error   = '';
$done    = false;
$invite  = null;   // ['name' => ..., 'email' => ...] when the token is valid
$invalid = (strlen($token) !== 64);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** Shared cURL helper for talking to the backend. */
$apiCall = function (string $method, string $path, ?array $body = null): array {
    $ch = curl_init(API_BASE . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-Key: ' . API_KEY,
            'Origin: '    . ORIGIN,
            'User-Agent: '      . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $res    = json_decode(curl_exec($ch), true);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, is_array($res) ? $res : []];
};

if (!$invalid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);

    $pw      = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $pwOk = strlen($pw) >= 8
        && preg_match('/[A-Z]/', $pw)
        && preg_match('/[a-z]/', $pw)
        && preg_match('/[0-9]/', $pw)
        && preg_match('/[^A-Za-z0-9]/', $pw);
    if (!$pwOk) {
        $error = 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.';
    } elseif ($pw !== $confirm) {
        $error = 'The passwords do not match.';
    } else {
        [$status, $res] = $apiCall('POST', '/wl/admin/invite/accept', ['token' => $token, 'password' => $pw]);
        if ($status === 200 && !empty($res['success'])) {
            $done = true;
        } else {
            $error = $res['error'] ?? 'This invitation is invalid or has expired.';
            if ($status === 400 || $status === 404) {
                $invalid = true;
            }
        }
    }

    if (!$done) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Look up the invite for display (GET, or a POST that did not consume it).
if (!$invalid && !$done) {
    [$status, $res] = $apiCall('GET', '/wl/admin/invite/' . $token);
    if ($status === 200 && !empty($res['success'])) {
        $invite = ['name' => $res['name'] ?? '', 'email' => $res['email'] ?? ''];
    } else {
        $invalid = true;
    }
}

$layout = 'auth';
?>
<script type="application/json" id="page-meta">
{
    "title": "Set your password - Majestic Marquees",
    "description": "Activate your Majestic Marquees admin account"
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
            <h2 class="mb-2 text-xl font-semibold text-gray-700">You are all set</h2>
            <p class="mb-6 text-sm text-gray-500">Your password has been set. You can now sign in to the admin panel.</p>
            <a href="/login" class="block w-full py-3 text-sm font-semibold text-white bg-tan-500 rounded-lg hover:bg-tan-600 transition">
                Go to sign in
            </a>
        </div>
        <?php elseif ($invalid): ?>
        <div class="w-full p-8 bg-white rounded-2xl shadow-md text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 text-2xl">&#33;</div>
            <h2 class="mb-2 text-xl font-semibold text-gray-700">Invitation not valid</h2>
            <p class="mb-6 text-sm text-gray-500">
                <?= e($error ?: 'This invitation link is invalid or has expired. Please ask an administrator to send a new invite.') ?>
            </p>
            <a href="/login" class="inline-block text-sm text-tan-600 hover:underline">Back to sign in</a>
        </div>
        <?php else: ?>
        <form method="POST" action="/set-password" class="w-full p-8 bg-white rounded-2xl shadow-md">
            <h2 class="mb-1 text-xl font-semibold text-gray-700">Set your password</h2>
            <p class="mb-6 text-sm text-gray-500">
                Welcome<?= $invite && $invite['name'] !== '' ? ', ' . e($invite['name']) : '' ?>. Choose a password to activate your account.
            </p>

            <?php if ($error): ?>
            <div class="mb-4 px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-600">Email</label>
                <input
                    type="email"
                    value="<?= e($invite['email'] ?? '') ?>"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                    readonly>
            </div>

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-600">Password</label>
                <input
                    type="password"
                    name="password"
                    id="sp-password"
                    minlength="8"
                    placeholder="At least 8 characters"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-tan-400"
                    required autofocus>
                <ul id="pw-reqs" class="mt-2 space-y-1 text-xs text-gray-500">
                    <li data-req="len"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> At least 8 characters</li>
                    <li data-req="upper"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> An uppercase letter (A-Z)</li>
                    <li data-req="lower"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A lowercase letter (a-z)</li>
                    <li data-req="num"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A number (0-9)</li>
                    <li data-req="special" class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A special character (e.g. !?@#$)</li>
                </ul>
            </div>

            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium text-gray-600">Confirm password</label>
                <input
                    type="password"
                    name="confirm_password"
                    id="sp-confirm"
                    minlength="8"
                    placeholder="Re-enter password"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg outline-none text-sm focus:ring-2 focus:ring-tan-400"
                    required>
                <p id="sp-match" class="mt-1.5 text-xs hidden"></p>
            </div>

            <button
                type="submit"
                id="sp-submit"
                class="w-full py-3 text-sm font-semibold text-white bg-tan-500 rounded-lg hover:bg-tan-600 transition">
                Set password and continue
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const pw = document.getElementById('sp-password');
    if (!pw) return; // success / invalid states have no form
    const confirm = document.getElementById('sp-confirm');
    const reqs    = document.getElementById('pw-reqs');
    const match   = document.getElementById('sp-match');
    const submit  = document.getElementById('sp-submit');

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
