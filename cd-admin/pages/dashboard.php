<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

const API_BASE = 'http://localhost:8000';
const API_KEY  = 'wl_sk_clickdigim9876543210xyz';
const ORIGIN   = 'https://admin.clickdigim.com';

$ch = curl_init(API_BASE . '/wl/admin/submissions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-Tenant-Key: '  . API_KEY,
        'Origin: '        . ORIGIN,
        'Authorization: Bearer ' . $_SESSION['jwt'],
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$res    = json_decode(curl_exec($ch), true);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 200) {
    session_destroy();
    header('Location: /login');
    exit;
}

$submissions = $res['data'] ?? [];
?>
<script type="application/json" id="page-meta">
{
    "title": "Dashboard — ClickDigim Admin",
    "description": "Admin dashboard for ClickDigim",
    "og_title": "Dashboard — ClickDigim Admin",
    "og_image": ""
}
</script>

<h1 style="color:#0066cc">ClickDigim Dashboard</h1>
<p><a href="/logout">Logout</a></p>
<h2>Form Submissions (<?= count($submissions) ?>)</h2>
<?php if (empty($submissions)): ?>
    <p>No submissions yet. Submit the contact form at <a href="http://localhost:8003">localhost:8003</a></p>
<?php else: ?>
    <table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
        <tr style="background:#0066cc;color:white">
            <th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th>
        </tr>
        <?php foreach ($submissions as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['message']) ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
