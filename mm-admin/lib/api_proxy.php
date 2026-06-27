<?php
/**
 * Same-origin API proxy for the admin host.
 *
 * The browser calls /api/proxy/<backend-path> on this host; this script attaches
 * the tenant X-API-Key (and, when an admin session is present, the signed-in
 * user's Bearer JWT) server-side and forwards the request to the backend API.
 * Neither the API key nor the JWT is ever sent to the browser, so nothing
 * sensitive appears in view-source.
 *
 * Usage from a page:
 *   fetch('/api/proxy/wl/public/survey/' + token)
 *   fetch('/api/proxy/wl/public/estimates/' + token + '/respond', { method:'POST', ... })
 */

if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);
$apiBase = $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com';
$apiKey  = 'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462';
$origin  = $_is_local ? 'http://localhost:8002' : 'https://admin.majesticmarquees.clickdigim.com';
unset($_is_local);

// Resolve the backend path (everything after /api/proxy), without the query string.
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$prefix  = '/api/proxy';
if (strpos($reqPath, $prefix) !== 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid proxy request.']);
    exit;
}
$backendPath = substr($reqPath, strlen($prefix));
if ($backendPath === '' || $backendPath[0] !== '/') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid proxy request.']);
    exit;
}

// SECURITY: only forward to known backend namespaces. This prevents the proxy
// from being used as an open relay to arbitrary backend paths.
$allowed = false;
foreach (['/wl/', '/uploads/'] as $allowedPrefix) {
    if (strpos($backendPath, $allowedPrefix) === 0) { $allowed = true; break; }
}
if (!$allowed) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden path.']);
    exit;
}

// Reconstruct the backend URL (preserve the query string).
$backendUrl = rtrim($apiBase, '/') . $backendPath;
$queryString = $_SERVER['QUERY_STRING'] ?? '';
if ($queryString !== '') { $backendUrl .= '?' . $queryString; }

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Build the forwarded headers: inject the secrets server-side.
$headers = [
    'X-API-Key: ' . $apiKey,
    'Origin: ' . $origin,
    'Accept: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'application/json'),
];

// Attach the admin JWT only when a session holds one (public calls have none).
$jwt = $_SESSION['jwt'] ?? '';
if ($jwt !== '') { $headers[] = 'Authorization: Bearer ' . $jwt; }

// Forward the request body for write methods.
$body = null;
if ($method !== 'GET' && $method !== 'HEAD') {
    $body = file_get_contents('php://input');
    $headers[] = 'Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'application/json');
}

$ch = curl_init($backendUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT        => 60,
]);
if ($body !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }

$response = curl_exec($ch);
if ($response === false) {
    error_log('[api_proxy] upstream error: ' . curl_error($ch));
    curl_close($ch);
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Upstream request failed.']);
    exit;
}
$httpCode    = (int) (curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 502);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/json';
curl_close($ch);

http_response_code($httpCode);
header('Content-Type: ' . $contentType);
echo $response;
exit;
