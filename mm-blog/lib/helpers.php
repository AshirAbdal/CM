<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * Shared helpers for the Majestic Marquees blog (raw PHP).
 * Pure PHP — no framework. Included once by public/index.php.
 */

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('MAIN_SITE', $_is_local ? 'http://localhost:8001' : 'https://website.majesticmarquees.clickdigim.com/');
define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
unset($_is_local);

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Server-side GET against the public blog API.
 * Returns decoded array or null on failure.
 */
function blog_api(string $path): ?array
{
    $ch = curl_init(API_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-API-Key: ' . API_KEY, 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $code >= 400) {
        return null;
    }

    $decoded = json_decode($res, true);
    return is_array($decoded) ? $decoded : null;
}
