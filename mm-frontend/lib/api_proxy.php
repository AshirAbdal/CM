<?php
/**
 * API Proxy - forwards frontend requests to the backend API with the API_KEY
 * attached server-side (never exposed to the browser).
 * 
 * Usage from frontend:
 *   fetch('/api/proxy/wl/public/testimonials', ...)
 *   fetch('/api/proxy/wl/forms/testimonial', ...)
 * 
 * This intercepts all /api/proxy/* requests and forwards them to
 * https://apiv1.clickdigim.com/wl/...  with X-API-Key attached.
 */

if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

// Extract the requested backend path (everything after /api/proxy)
$requestPath = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestPath, '/api/proxy') === 0) {
    $backendPath = substr($requestPath, strlen('/api/proxy'));
} else {
    http_response_code(400);
    exit('Invalid proxy request.');
}

// Reconstruct the backend URL
$backendUrl = rtrim(API_BASE, '/') . $backendPath;
if ($_SERVER['QUERY_STRING'] ?? '') {
    $backendUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// Prepare headers for the backend request
$headers = [];
foreach (getallheaders() as $name => $value) {
    // Skip host/connection/proxy-related headers that would confuse the backend
    if (stripos($name, 'host') === 0 ||
        stripos($name, 'connection') === 0 ||
        stripos($name, 'content-length') === 0) {
        continue;
    }
    // Keep only headers that should be forwarded
    if (stripos($name, 'content-type') === 0 ||
        stripos($name, 'accept') === 0) {
        $headers[$name] = $value;
    }
}

// Add the API key (never exposed to frontend)
$headers['X-API-Key'] = API_KEY;

// Initialize curl
$ch = curl_init($backendUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER    => array_map(function ($k, $v) { return "$k: $v"; }, array_keys($headers), array_values($headers)),
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

// Forward the request method and body
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
} elseif ($method !== 'GET') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
}

// Execute
$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? 'application/json';
curl_close($ch);

// Return the response
http_response_code($httpCode);
header("Content-Type: $contentType");
echo $response;
exit;
?>
