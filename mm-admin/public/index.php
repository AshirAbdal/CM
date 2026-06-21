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
$allowedHosts = ['localhost', '127.0.0.1', 'admin.majesticmarquees.com', 'admin.majesticmarquees.clickdigim.com'];
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    exit('Invalid host.');
}

require __DIR__ . '/../lib/helpers.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

if ($path === '/logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

if (in_array($path, ['/', '/login'])) {
    // Redirect root to /login - one canonical URL for the login page
    if ($path === '/') {
        header('Location: /login', true, 302);
        exit;
    }
    if (!empty($_SESSION['jwt'])) { header('Location: /dashboard'); exit; }
    $pageFile = __DIR__ . '/../pages/login.php';
} elseif ($path === '/dashboard') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/dashboard.php';
} elseif ($path === '/customer-info-details') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/customer_info_details.php';
} elseif ($path === '/customer-info') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/customer_info.php';
} elseif ($path === '/images') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/images.php';
} elseif ($path === '/inventory') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/inventory.php';
} elseif ($path === '/survey-questions') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/survey_questions.php';
} elseif ($path === '/lead-management') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/lead_management.php';
} elseif ($path === '/deal') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/deal.php';
} elseif ($path === '/make-offer') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/make_offer.php';
} elseif ($path === '/xero') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/xero.php';
} elseif (preg_match('#^/estimate/([a-f0-9]{64})$#', $path, $m)) {
    // Public estimate page - no auth required
    $_GET['token'] = $m[1];
    $pageFile = __DIR__ . '/../pages/estimate_public.php';
    // Public page - output directly, skip admin layout
    require $pageFile;
    exit;
} elseif (preg_match('#^/survey/([a-f0-9]{64})$#', $path, $m)) {
    // Public verification survey page - no auth required
    $_GET['token'] = $m[1];
    $pageFile = __DIR__ . '/../pages/survey_public.php';
    // Public page - output directly, skip admin layout
    require $pageFile;
    exit;
} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body><h1>404 - Page Not Found</h1></body></html>';
    exit;
}

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

// Extract page-meta JSON into $pageMeta for the <head>, then strip the block.
$pageMeta = [];
if (preg_match('/<script type="application\/json" id="page-meta">(.+?)<\/script>/s', $pageContent, $m)) {
    $pageMeta = json_decode(trim($m[1]), true) ?? [];
    $pageContent = preg_replace('/<script type="application\/json" id="page-meta">.+?<\/script>/s', '', $pageContent);
}

require __DIR__ . '/../layout/page.php';
