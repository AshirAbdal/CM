<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

// ───────────────────────────────────────────────────────────────────
// Site-image sync API  —  /api/site-images
//
// The admin panel is deployed on a DIFFERENT server from this frontend,
// so it can no longer write replacement images straight onto this
// server's disk. Instead it calls this endpoint over HTTP and we store
// the file in public/assets/ (the same place the site serves it from).
//
//   GET  /api/site-images  → JSON metadata (size + mtime) for every
//                            managed asset, so the admin can show the
//                            current state of each slot.
//   POST /api/site-images  → replace one asset. multipart/form-data:
//                              • path → relative asset path, e.g.
//                                "images/home-hero-bg.jpg" or "../logo.png"
//                              • file → the uploaded image
//
// Auth: a shared secret in the `X-Img-Secret` header, compared in
// constant time (hash_equals). Set IMG_SYNC_SECRET in the environment on
// BOTH the admin and this frontend; the hard-coded fallback is for local
// development only.
// ───────────────────────────────────────────────────────────────────

$IMG_SYNC_SECRET = getenv('IMG_SYNC_SECRET') ?: 'mq-img-sync-3f8c1d9a7b2e4056c1a8f3d6e90b2c4d';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    return;
}

// ── Auth ───────────────────────────────────────────────────────────
$provided = $_SERVER['HTTP_X_IMG_SECRET'] ?? '';
if ($provided === '' || !hash_equals($IMG_SYNC_SECRET, $provided)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    return;
}

// ── Storage roots ──────────────────────────────────────────────────
// Images live in public/assets/; the site logo sits at public/logo.png
// (the admin refers to it as "../logo.png"). public/ is the hard security
// boundary — nothing may ever be written outside it.
$publicRoot = realpath(__DIR__ . '/../public');
if ($publicRoot === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Storage root unavailable']);
    return;
}
$assetsRoot = $publicRoot . '/assets';

$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    'image/avif' => 'avif',
];

/**
 * Resolve a caller-supplied relative asset path (interpreted relative to
 * public/assets/, exactly like the old disk-based manager) into an
 * absolute path on this server. Normalises "." and ".." lexically — no
 * disk access required, so it works for files that don't exist yet — and
 * guarantees the result stays inside public/. Returns null when unsafe.
 */
$resolveAssetPath = static function (string $rel) use ($assetsRoot, $publicRoot): ?string {
    $rel = trim($rel);
    if ($rel === '' || strpos($rel, "\0") !== false) { return null; }

    $raw   = $assetsRoot . '/' . ltrim($rel, '/');
    $parts = [];
    foreach (explode('/', str_replace('\\', '/', $raw)) as $seg) {
        if ($seg === '' || $seg === '.') { continue; }
        if ($seg === '..') { array_pop($parts); continue; }
        $parts[] = $seg;
    }
    $full = '/' . implode('/', $parts);

    // Containment: the resolved path must be public/ itself or live inside it.
    if ($full !== $publicRoot && strpos($full, $publicRoot . '/') !== 0) {
        return null;
    }
    return $full;
};

// ── POST: replace one asset ────────────────────────────────────────
if ($method === 'POST') {
    $full = $resolveAssetPath((string) ($_POST['path'] ?? ''));
    if ($full === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid image path.']);
        return;
    }

    if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'ok'    => false,
            'error' => 'No file uploaded (code ' . ($_FILES['file']['error'] ?? '?') . ').',
        ]);
        return;
    }

    if (($_FILES['file']['size'] ?? 0) > 8 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => 'File too large (max 8 MB).']);
        return;
    }

    // Authoritative MIME sniff — never trust the client-declared type.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['file']['tmp_name']) ?: '';
    if (!isset($allowedMime[$mime])) {
        http_response_code(415);
        echo json_encode([
            'ok'    => false,
            'error' => 'Unsupported type. Allowed: JPEG, PNG, WebP, GIF, AVIF.',
        ]);
        return;
    }

    $dir = dirname($full);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Could not create target directory.']);
        return;
    }

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $full)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Write failed — check folder permissions.']);
        return;
    }
    @chmod($full, 0644);
    clearstatcache(true, $full);

    echo json_encode([
        'ok'         => true,
        'path'       => (string) $_POST['path'],
        'mime'       => $mime,
        'bytes'      => filesize($full),
        'updated_at' => filemtime($full),
    ]);
    return;
}

// ── GET: metadata for every managed asset ──────────────────────────
// Returns a map keyed by the same relative paths the admin uses, so it
// can look up each slot directly: images/<name> for gallery/page images
// and "../logo.png" for the site logo.
$files = [];

$imagesDir = $assetsRoot . '/images';
if (is_dir($imagesDir)) {
    foreach (scandir($imagesDir) ?: [] as $name) {
        if ($name === '' || $name[0] === '.') { continue; }   // skip ., .., dotfiles (.gitkeep, .DS_Store)
        $p = $imagesDir . '/' . $name;
        if (!is_file($p)) { continue; }
        $files['images/' . $name] = [
            'bytes'      => filesize($p),
            'updated_at' => filemtime($p),
        ];
    }
}

$logo = $publicRoot . '/logo.png';
if (is_file($logo)) {
    $files['../logo.png'] = [
        'bytes'      => filesize($logo),
        'updated_at' => filemtime($logo),
    ];
}

echo json_encode(['ok' => true, 'files' => $files]);
return;
