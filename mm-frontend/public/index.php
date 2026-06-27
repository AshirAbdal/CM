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
$_https = (($_SERVER['HTTPS'] ?? '') === 'on')
       || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_start([
    'cookie_httponly' => true,
    // SameSite=Lax (the modern browser default), NOT Strict. Strict drops the
    // session cookie on cross-site top-level navigations (arriving from an email
    // link, a search result or a bookmark), which with use_strict_mode silently
    // resets the visitor's session and loses in-progress multi-step quote/form
    // state. Lax keeps the cookie on those top-level navigations while still
    // withholding it on cross-site POST / subresource; the CSRF token guards POSTs.
    'cookie_samesite' => 'Lax',
    'cookie_secure'   => $_https,   // HTTPS-only cookie in production
    'use_strict_mode' => true,
]);
unset($_https);

// Session hygiene - defend against fixation / stale sessions. Rotate the
// session id every 2h while preserving CSRF + multi-step form state.
$_now = time();
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = $_now;
} elseif ($_now - $_SESSION['created_at'] > 7200) {
    session_regenerate_id(true);
    $_SESSION['created_at'] = $_now;
}
unset($_now);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/consent.php';
require __DIR__ . '/../lib/seo.php';

$allowedHosts = ['localhost', '127.0.0.1', 'majesticmarquees.com', 'www.majesticmarquees.com', 'website.majesticmarquees.clickdigim.com'];
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(400);
    exit('Invalid host.');
}

$path         = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
$isSpaRequest = isset($_SERVER['HTTP_X_SPA_REQUEST']);

// Site-image sync API — the admin (on a separate server) uploads/queries
// replacement images here. Returns JSON and exits; never hits the page router.
if ($path === '/api/site-images') {
    require __DIR__ . '/../lib/site_images.php';
    exit;
}

// API proxy — forwards frontend requests to the backend with API_KEY attached
// server-side (the key is never exposed to the browser). Routes like /api/proxy/wl/...
if (strpos($path, '/api/proxy') === 0) {
    require __DIR__ . '/../lib/api_proxy.php';
    exit;
}

// Cookie-consent proof log — receives the visitor's consent choices from
// public/consent.js and records them (GDPR Art. 7). Returns JSON and exits.
if ($path === '/api/consent-log') {
    require __DIR__ . '/../lib/consent_log.php';
    exit;
}

// Redirect /home → / (301 permanent)
if ($path === '/home') {
    header('Location: /', true, 301);
    exit;
}

$pages = [
    '/'                                  => __DIR__ . '/../pages/home.php',
    '/about'                             => __DIR__ . '/../pages/about.php',
    '/our-tents'                         => __DIR__ . '/../pages/our-tents.php',
    '/our-tents/stretch-nomadic-bedouin' => __DIR__ . '/../pages/our-tents-stretch.php',
    '/our-tents/sailcloth-silhouette'    => __DIR__ . '/../pages/our-tents-sailcloth.php',
    '/our-tents/custom-bespoke'          => __DIR__ . '/../pages/our-tents-custom.php',
    '/gallery'                           => __DIR__ . '/../pages/gallery.php',
    '/faq'                               => __DIR__ . '/../pages/faq.php',
    '/contact-get-a-quote'               => __DIR__ . '/../pages/contact.php',
    '/terms-conditions'                  => __DIR__ . '/../pages/terms-conditions.php',
    '/privacy-policy-2'                  => __DIR__ . '/../pages/privacy-policy.php',
    '/cookie-policy'                     => __DIR__ . '/../pages/cookie-policy.php',
];

if (array_key_exists($path, $pages)) {
    $pageFile = $pages[$path];
} else {
    http_response_code(404);
    $pageFile = __DIR__ . '/../pages/not-found.php';
}

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

// Build the <head> SEO meta. Engine-managed routes (see seo_is_managed) derive
// the title from the first <h1>, the description from the first <p>, and emit a
// full set of meta tags + schema.org @graph. Other routes keep their own inline
// page-meta JSON block (legacy behaviour).
if (seo_is_managed($path)) {
    $pageMeta = seo_build_meta($path, $pageContent, [
        'faqs'   => $faqs ?? null,
        'robots' => (http_response_code() === 404) ? 'noindex, follow' : null,
    ]);
} else {
    $pageMeta = [];
    if (preg_match('/<script type="application\/json" id="page-meta">(.+?)<\/script>/s', $pageContent, $m)) {
        $pageMeta = json_decode(trim($m[1]), true) ?? [];
    }
}

// This URL serves two different representations depending on the X-SPA-Request
// header: a full HTML document for normal navigations, and a head-less fragment
// for client-side SPA navigation. Advertise that so no cache (browser memory
// cache, bfcache or a proxy) ever serves the doctype-less fragment in place of
// a full page load - which a parser would reject ("start tag seen without
// seeing a doctype first").
header('Vary: X-SPA-Request');

if ($isSpaRequest) {
    // Client-side navigation: spa.js reads a page-meta JSON block from the
    // fragment. Engine routes carry no inline block, so emit a fresh one.
    if (seo_is_managed($path)) {
        $pageContent = '<script type="application/json" id="page-meta">'
            . json_encode($pageMeta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . $pageContent;
    }
    echo $pageContent;
} else {
    // Full page load - PHP already injected meta into <head>, strip any inline
    // page-meta block from the body.
    $pageContent = preg_replace('/<script type="application\/json" id="page-meta">.+?<\/script>/s', '', $pageContent);
    require __DIR__ . '/../layout/page.php';
}
