<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

const API_BASE = 'http://localhost:8000';
const API_KEY  = 'wl_sk_clickdigim9876543210xyz';
const ORIGIN   = 'https://clickdigim.com';

$result = '';

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
    $ch = curl_init(API_BASE . '/wl/forms/contact');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'name'    => $_POST['name']    ?? '',
            'email'   => $_POST['email']   ?? '',
            'message' => $_POST['message'] ?? '',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Tenant-Key: '    . API_KEY,
            'Origin: '          . ORIGIN,
            'User-Agent: '      . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $res    = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $result = $status === 200 ? 'Message sent!' : 'Error: ' . $res;
}
?>
<script type="application/json" id="page-meta">
{
    "title": "Contact Us — ClickDigim",
    "name": {
        "description":  "Get in touch with ClickDigim.",
        "keywords":     "marquee hire, wedding marquee, UK",
        "robots":       "index, follow",
        "author":       "ClickDigim"
    },
    "property": {
        "og:title":       "Contact Us — ClickDigim",
        "og:description": "Get in touch with ClickDigim.",
        "og:image":       "https://clickdigim.com/images/contact.jpg",
        "og:type":        "website",
        "og:url":         "https://clickdigim.com/contact",
        "twitter:card":   "summary_large_image",
        "twitter:title":  "Contact Us — ClickDigim"
    },
    "schema": {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "ClickDigim",
        "description": "Premium digital marketing and event services",
        "url": "https://clickdigim.com",
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "url": "https://clickdigim.com/contact"
        }
    }
}
</script>

<h1 style="color:#0066cc">ClickDigim</h1>
<h2>Contact Us</h2>
<?php if ($result): ?>
    <p style="color:green"><?= htmlspecialchars($result) ?></p>
<?php endif; ?>
<form method="POST" action="/">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <input    name="name"    placeholder="Your name"  required style="width:100%;padding:8px;margin-bottom:10px"><br>
    <input    name="email"   placeholder="Your email" type="email" required style="width:100%;padding:8px;margin-bottom:10px"><br>
    <textarea name="message" placeholder="Message"    required   style="width:100%;padding:8px;margin-bottom:10px;height:100px"></textarea><br>
    <button type="submit" style="background:#0066cc;color:white;padding:10px 20px;border:none;cursor:pointer">Send</button>
</form>
