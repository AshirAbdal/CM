<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

const API_BASE = 'http://localhost:8000';
const API_KEY  = 'wl_sk_clickdigim9876543210xyz';
const ORIGIN   = 'https://admin.clickdigim.com';

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
    $ch = curl_init(API_BASE . '/wl/auth/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'email'    => $_POST['email']    ?? '',
            'password' => $_POST['password'] ?? '',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Tenant-Key: ' . API_KEY,
            'Origin: '       . ORIGIN,
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
        header('Location: /dashboard');
        exit;
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $error = 'Invalid email or password.';
}
?>
<script type="application/json" id="page-meta">
{
    "title": "Admin Login — ClickDigim",
    "description": "Admin login for ClickDigim",
    "og_title": "Admin Login — ClickDigim",
    "og_image": ""
}
</script>

<h1 style="color:#0066cc">ClickDigim Admin</h1>
<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="POST" action="/login">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <input  name="email"    type="email"    placeholder="Email"    required style="width:100%;padding:8px;margin-bottom:10px"><br>
    <input  name="password" type="password" placeholder="Password" required style="width:100%;padding:8px;margin-bottom:10px"><br>
    <button type="submit" style="background:#0066cc;color:white;padding:10px 20px;border:none;cursor:pointer">Login</button>
</form>
    </form>
</body>
</html>
