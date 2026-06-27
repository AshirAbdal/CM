<?php
// Let PHP's built-in dev server serve static files (logo, favicon, …) directly.
// In production (Apache) the .htaccess already serves existing files, so this
// block only matters for the local `php -S` dev server.
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
$_https = (($_SERVER['HTTPS'] ?? '') === 'on')
       || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// Public, unauthenticated token pages (survey / estimate) are served from this
// same admin host. They are fully stateless - they read a token from the URL
// and talk straight to the API - so they never need the admin session. The
// public proxy calls those pages make (/api/proxy/wl/public/...) are equally
// anonymous. We skip session_start for all of them so an anonymous visitor never
// has a PHPSESSID minted on the admin host (defence in depth, and no stray
// session files for the public).
$_publicPath    = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/') ?: '/';
$_isPublicToken = (bool) preg_match('#^/(?:survey|estimate)/[a-f0-9]{64}$#', $_publicPath)
               || strpos($_publicPath, '/api/proxy/wl/public/') === 0;
if (!$_isPublicToken) {
    session_start([
        'cookie_httponly' => true,
        // SameSite=Lax (the modern browser default), NOT Strict. Strict makes the
        // browser DROP the session cookie on every cross-site top-level navigation
        // to the CRM - clicking an admin link from a notification email, from the
        // website, or from a bookmark opened after another site. PHP's
        // use_strict_mode then mints a fresh empty session, so the admin lands on
        // /login as if logged out. Lax still sends the cookie on those normal
        // top-level navigations (keeping the admin signed in) while withholding it
        // on cross-site POST / subresource requests; the explicit per-session CSRF
        // token continues to protect form submissions.
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => $_https,   // HTTPS-only cookie in production
        'use_strict_mode' => true,
    ]);
}
unset($_https, $_publicPath, $_isPublicToken);
$allowedHosts = ['localhost', '127.0.0.1', 'admin.majesticmarquees.com', 'admin.majesticmarquees.clickdigim.com'];
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    exit('Invalid host.');
}

require __DIR__ . '/../lib/helpers.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

// Same-origin API proxy: forwards /api/proxy/<backend-path> to the backend API
// with the tenant key (and the admin JWT when a session is present) attached
// server-side, so neither secret is ever exposed to the browser.
if (strpos($path, '/api/proxy') === 0) {
    require __DIR__ . '/../lib/api_proxy.php';
    exit;
}

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
} elseif ($path === '/forgot-password') {
    // Public - request a password reset code by email.
    $pageFile = __DIR__ . '/../pages/forgot_password.php';
} elseif ($path === '/reset-password') {
    // Public - enter the emailed code and choose a new password.
    $pageFile = __DIR__ . '/../pages/reset_password.php';
} elseif ($path === '/set-password') {
    // Public - accept an invitation and set the first password.
    $pageFile = __DIR__ . '/../pages/set_password.php';
} elseif ($path === '/dashboard') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('dashboard.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/dashboard.php';
} elseif ($path === '/customer-info-details') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can_any(['leads.view', 'customers.view'])) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/customer_info_details.php';
} elseif ($path === '/customer-info') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can_any(['leads.view', 'customers.view'])) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/customer_info.php';
} elseif ($path === '/images') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('images.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/images.php';
} elseif ($path === '/inventory') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('inventory.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/inventory.php';
} elseif ($path === '/survey-questions') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('survey.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/survey_questions.php';
} elseif ($path === '/ai-settings') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('ai.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/ai_settings.php';
} elseif ($path === '/smtp-settings') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('smtp.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/smtp_settings.php';
} elseif ($path === '/lead-management') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('leads.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/lead_management.php';
} elseif ($path === '/reviews') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can_any(['reviews.view', 'reviews.manage'])) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/reviews.php';
} elseif ($path === '/posts') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('posts.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/posts.php';
} elseif ($path === '/posts/new') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('posts.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/post_editor.php';
} elseif (preg_match('#^/posts/(\d+)/edit$#', $path, $m)) {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('posts.manage')) { header('Location: ' . landing_path()); exit; }
    $_GET['id'] = $m[1];
    $pageFile = __DIR__ . '/../pages/post_editor.php';
} elseif ($path === '/deal') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('leads.view')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/deal.php';
} elseif ($path === '/make-offer') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('leads.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/make_offer.php';
} elseif ($path === '/xero') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can('xero.manage')) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/xero.php';
} elseif ($path === '/user-management') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    if (!can_any(['users.manage', 'roles.manage'])) { header('Location: ' . landing_path()); exit; }
    $pageFile = __DIR__ . '/../pages/user_management.php';
} elseif ($path === '/users') {
    // Merged into the combined Users & Roles page (under Settings).
    header('Location: /user-management?tab=users', true, 301);
    exit;
} elseif ($path === '/roles') {
    // Merged into the combined Users & Roles page (under Settings).
    header('Location: /user-management?tab=roles', true, 301);
    exit;
} elseif ($path === '/change-password') {
    if (empty($_SESSION['jwt'])) { header('Location: /login'); exit; }
    $pageFile = __DIR__ . '/../pages/change_password.php';
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
