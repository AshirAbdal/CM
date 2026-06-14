<?php
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
$allowedHosts = ['localhost', '127.0.0.1', 'admin.clickdigim.com'];
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    exit('Invalid host.');
}

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

if ($path === '/logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

if (in_array($path, ['/', '/login'])) {
    // Redirect root to /login — one canonical URL for the login page
    if ($path === '/') {
        header('Location: /login', true, 302);
        exit;
    }
    if (isset($_SESSION['jwt'])) { header('Location: /dashboard'); exit; }
    $pageFile = __DIR__ . '/../pages/login.php';
} elseif ($path === '/dashboard') {
    if (!isset($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/dashboard.php';
} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body><h1>404 — Page Not Found</h1></body></html>';
    exit;
}

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

require __DIR__ . '/../layout/page.php';
