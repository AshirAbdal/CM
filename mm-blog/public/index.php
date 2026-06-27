<?php
// Let PHP's built-in dev server serve static files (logo, favicon, …) directly.
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

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/consent.php';

$allowedHosts = ['localhost', '127.0.0.1', 'blog.majesticmarquees.com', 'www.blog.majesticmarquees.com', 'blog.majesticmarquees.clickdigim.com'];
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    exit('Invalid host.');
}

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

// Redirect /home → / (301 permanent)
if ($path === '/home') {
    header('Location: /', true, 301);
    exit;
}

// Proof-of-consent logging endpoint (best-effort, never blocks the page).
if ($path === '/api/consent-log') {
    require __DIR__ . '/../lib/consent_log.php';
    exit;
}

if ($path === '/') {
    $pageFile = __DIR__ . '/../pages/home.php';
} elseif (preg_match('#^/([a-z0-9-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    $pageFile = __DIR__ . '/../pages/post.php';
} else {
    http_response_code(404);
    $pageFile = __DIR__ . '/../pages/not-found.php';
}

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

// Extract page-meta JSON for PHP to inject into <head> (SEO)
$pageMeta = [];
if (preg_match('/<script type="application\/json" id="page-meta">(.+?)<\/script>/s', $pageContent, $m)) {
    $pageMeta = json_decode(trim($m[1]), true) ?? [];
}

// Full page load — PHP injects meta into <head>, strip the JSON block from the body
$pageContent = preg_replace('/<script type="application\/json" id="page-meta">.+?<\/script>/s', '', $pageContent);
require __DIR__ . '/../layout/page.php';
