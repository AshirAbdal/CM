<?php
// Let PHP's built-in dev server serve static files directly
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) return false;
}

define('APP_ENTRY', true);

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
set_exception_handler(function (Throwable $e): void {
    error_log('[' . date('c') . '] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><h1>Something went wrong.</h1></body></html>';
    exit;
});
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);
$allowedHosts = ['localhost', '127.0.0.1', 'clickdigim.com'];
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    exit('Invalid host.');
}

$path         = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
$isSpaRequest = isset($_SERVER['HTTP_X_SPA_REQUEST']);

// Redirect /home → / (301 permanent)
if ($path === '/home') {
    header('Location: /', true, 301);
    exit;
}

$pages = [
    '/'       => __DIR__ . '/../pages/home.php',
    '/about'  => __DIR__ . '/../pages/about.php',
];

if (!array_key_exists($path, $pages)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body><h1>404 — Page Not Found</h1></body></html>';
    exit;
}

ob_start();
require $pages[$path];
$pageContent = ob_get_clean();

// Extract page-meta JSON for PHP to inject into <head> (SEO — runs before JS)
$pageMeta = [];
if (preg_match('/<script type="application\/json" id="page-meta">(.+?)<\/script>/s', $pageContent, $m)) {
    $pageMeta = json_decode(trim($m[1]), true) ?? [];
}

if ($isSpaRequest) {
    // User clicked a link — keep the script block so spa.js can read it
    echo $pageContent;
} else {
    // Full page load — PHP already injected meta into <head>, strip the JSON block
    $pageContent = preg_replace('/<script type="application\/json" id="page-meta">.+?<\/script>/s', '', $pageContent);
    require __DIR__ . '/../layout/page.php';
}
