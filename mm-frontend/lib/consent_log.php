<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * Proof-of-consent log endpoint (GDPR Art. 7(1) - be able to demonstrate consent).
 *
 * Receives the consent payload from public/consent.js and appends one JSON line
 * to logs/consent/consent-YYYY-MM.log. Best-effort and fail-safe: it never blocks
 * the visitor and always returns 200 (the first-party cookie is the primary record).
 *
 * Privacy by design: we store a SHA-256 hash of the IP (salted), NOT the raw IP,
 * plus the user-agent, the chosen categories, the policy version and a timestamp.
 */

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    return;
}

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 4096) {        // tiny payload only
    echo json_encode(['ok' => true]);                // fail-safe: do not block UX
    return;
}

$data = json_decode($raw, true);
if (!is_array($data) || empty($data['cats']) || !is_array($data['cats'])) {
    echo json_encode(['ok' => true]);
    return;
}

// Normalise the categories to 0/1 so nothing arbitrary is written.
$allowed = ['necessary', 'functional', 'location', 'analytics', 'marketing'];
$cats = [];
foreach ($allowed as $c) {
    $cats[$c] = !empty($data['cats'][$c]) ? 1 : 0;
}
$cats['necessary'] = 1; // always on

$ip   = $_SERVER['REMOTE_ADDR'] ?? '';
$salt = defined('CONSENT_VERSION') ? CONSENT_VERSION : 'mm';
$record = [
    'id'      => bin2hex(random_bytes(8)),
    'ts'      => gmdate('c'),
    'v'       => isset($data['v']) ? substr((string) $data['v'], 0, 32) : (defined('CONSENT_VERSION') ? CONSENT_VERSION : ''),
    'cats'    => $cats,
    'ip_hash' => $ip !== '' ? hash('sha256', $ip . '|' . $salt) : '',
    'ua'      => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 256),
];

$dir = __DIR__ . '/../logs/consent';
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}
$file = $dir . '/consent-' . gmdate('Y-m') . '.log';
$line = json_encode($record, JSON_UNESCAPED_SLASHES) . "\n";

if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
    // Could not write a dedicated file (e.g. read-only host) - fall back to the
    // PHP error log so a record still exists, then succeed for the visitor.
    error_log('[consent] ' . trim($line));
}

echo json_encode(['ok' => true]);
