<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

// --- JWT auth guard (matches dashboard.php) -------------------------------
// Validate the session JWT against the API on every load; a 401 means the
// token is missing/expired/invalid, so force a fresh login.
$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
define('API_KEY',  'mq-prod-public-key-001');
define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
// The frontend (website) runs on a SEPARATE server, so image changes are pushed
// over HTTP to its /api/site-images endpoint instead of a shared disk path.
// IMG_SYNC_SECRET must match the value on the frontend (set both via env in prod).
define('FRONTEND_BASE',   $_is_local ? 'http://localhost:8001' : 'https://website.majesticmarquees.clickdigim.com');
define('IMG_SYNC_SECRET', getenv('IMG_SYNC_SECRET') ?: 'mq-img-sync-3f8c1d9a7b2e4056c1a8f3d6e90b2c4d');
unset($_is_local);

$ch = curl_init(API_BASE . '/wl/admin/leads');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: '            . API_KEY,
        'Origin: '               . ORIGIN,
        'Authorization: Bearer ' . ($_SESSION['jwt'] ?? ''),
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status === 401) {
    // JWT expired or invalid - kick back to login
    session_destroy();
    header('Location: /login');
    exit;
}

// --- CSRF token (inline, matches login.php) -------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Frontend image sync over HTTP ─────────────────────────────────────────
// The website's images live on a DIFFERENT server. We manage them through its
// /api/site-images endpoint: GET for current metadata, POST to replace one.

/** Web URL the browser uses to preview a managed asset (with cache-buster). */
function frontend_asset_url(string $relFile, ?int $version): string {
    if (strncmp($relFile, '../', 3) === 0) {
        $url = FRONTEND_BASE . '/' . ltrim(substr($relFile, 3), '/');   // ../logo.png → /logo.png
    } else {
        $url = FRONTEND_BASE . '/assets/' . ltrim($relFile, '/');       // images/x.jpg → /assets/images/x.jpg
    }
    return $version ? $url . '?v=' . $version : $url;
}

/** Fetch size + mtime for every managed asset from the frontend. */
function fetch_image_meta(): array {
    $ch = curl_init(FRONTEND_BASE . '/api/site-images');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-Img-Secret: ' . IMG_SYNC_SECRET],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) { return ['ok' => false, 'files' => []]; }
    $json  = json_decode((string) $res, true);
    $files = (is_array($json) && isset($json['files']) && is_array($json['files'])) ? $json['files'] : [];
    return ['ok' => true, 'files' => $files];
}

/** Push one replacement image to the frontend over HTTP (multipart). */
function upload_image_to_frontend(string $relPath, string $tmpFile, string $mime, string $origName): array {
    if (!class_exists('CURLFile')) {
        return ['ok' => false, 'error' => 'Server is missing CURLFile support.'];
    }
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName) ?: 'upload';
    $ch = curl_init(FRONTEND_BASE . '/api/site-images');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['X-Img-Secret: ' . IMG_SYNC_SECRET],
        CURLOPT_POSTFIELDS     => [
            'path' => $relPath,
            'file' => new CURLFile($tmpFile, $mime, $safeName),
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($res === false) { return ['ok' => false, 'error' => 'Network error: ' . $err]; }
    $json = json_decode((string) $res, true);
    if ($code === 200 && is_array($json) && !empty($json['ok'])) { return ['ok' => true]; }
    return ['ok' => false, 'error' => (is_array($json) && isset($json['error'])) ? $json['error'] : ('HTTP ' . $code)];
}

// Build full config - grouped by section
$sections = [
    'Site'        => [
        'logo'             => ['file' => '../logo.png',                        'title' => 'Site Logo'],
    ],
    'Home'        => [
        'home_hero'        => ['file' => 'images/home-hero-bg.jpg',            'title' => 'Hero Background'],
        'home_story_1'     => ['file' => 'images/home-story-1.jpg',            'title' => 'Our Story - Left'],
        'home_story_2'     => ['file' => 'images/home-story-2.jpg',            'title' => 'Our Story - Right'],
        'home_features'    => ['file' => 'images/home-features-bg.jpg',        'title' => 'Features Background'],
    ],
    'Home - Tent Carousel (12 slides)' => array_combine(
        array_map(fn($i) => "home_tent_$i", range(1, 12)),
        array_map(fn($i) => ['file' => "images/home-tent-carousel-$i.jpg", 'title' => "Tent Carousel $i"], range(1, 12))
    ),
    'About'       => [
        'about_hero'       => ['file' => 'images/about-hero-bg.jpg',           'title' => 'Hero Background'],
        'about_founder'    => ['file' => 'images/about-founder-portrait.jpg',  'title' => 'Founder Portrait'],
        'about_cta'        => ['file' => 'images/about-cta-bg.webp',           'title' => 'Founder / CTA Section Background'],
    ],
    'Contact'     => [
        'contact_hero'     => ['file' => 'images/contact-hero-bg.jpg',         'title' => 'Hero Background'],
        'contact_vision_1' => ['file' => 'images/contact-vision-1.jpg',        'title' => 'Vision - Image 1'],
        'contact_vision_2' => ['file' => 'images/contact-vision-2.jpg',        'title' => 'Vision - Image 2'],
        'contact_vision_3' => ['file' => 'images/contact-vision-3.jpg',        'title' => 'Vision - Image 3'],
        'contact_vision_4' => ['file' => 'images/contact-vision-4.jpg',        'title' => 'Vision - Image 4'],
    ],
    'Gallery'     => [
        'gallery_hero'     => ['file' => 'images/gallery-hero-bg.jpg',         'title' => 'Hero Background'],
        'gallery_process'  => ['file' => 'images/gallery-process-image.jpg',   'title' => 'Process Image'],
        'gallery_faq'      => ['file' => 'images/gallery-faq-image.jpg',       'title' => 'FAQ Section Image'],
    ],
    'Gallery - Showcase (20 images)'  => array_combine(
        array_map(fn($i) => "gallery_showcase_$i", range(1, 20)),
        array_map(fn($i) => ['file' => "images/gallery-showcase-$i.jpg", 'title' => "Showcase $i"], range(1, 20))
    ),
    'FAQ'         => [
        'faq_hero'         => ['file' => 'images/faq-hero-bg.jpg',             'title' => 'Hero Background'],
        'faq_collage_1'    => ['file' => 'images/faq-collage-1.jpg',           'title' => 'Collage 1'],
        'faq_collage_2'    => ['file' => 'images/faq-collage-2.jpg',           'title' => 'Collage 2'],
        'faq_collage_3'    => ['file' => 'images/faq-collage-3.jpg',           'title' => 'Collage 3'],
        'faq_collage_4'    => ['file' => 'images/faq-collage-4.jpg',           'title' => 'Collage 4'],
    ],
    'Our Tents'   => [
        'our_tents_hero'          => ['file' => 'images/our-tents-hero-bg.jpg',      'title' => 'Hero Background'],
        'qtents_logo'             => ['file' => 'images/stretch-qtents-logo.webp',   'title' => 'QTents Logo (shared)'],
        'our_tents_discover_bg'   => ['file' => 'images/our-tents-discover-bg.webp', 'title' => 'Discover Section Background'],
        'our_tents_step_1'        => ['file' => 'images/our-tents-step-1.webp',      'title' => 'Discover Step 1'],
        'our_tents_step_2'        => ['file' => 'images/our-tents-step-2.webp',      'title' => 'Discover Step 2'],
        'our_tents_step_3'        => ['file' => 'images/our-tents-step-3.webp',      'title' => 'Discover Step 3'],
        'our_tents_stretch_card'  => ['file' => 'images/our-tents-stretch-card.jpg', 'title' => 'Tent Style - Stretch Card'],
        'our_tents_sailcloth_card'=> ['file' => 'images/our-tents-sailcloth-card.jpg','title' => 'Tent Style - Sailcloth Card'],
        'our_tents_custom_card'   => ['file' => 'images/our-tents-custom-card.jpg',  'title' => 'Tent Style - Custom Card'],
    ],
    'Our Tents - Carousel (12 slides)' => array_combine(
        array_map(fn($i) => "our_tents_carousel_$i", range(1, 12)),
        array_map(fn($i) => ['file' => "images/our-tents-carousel-$i.jpg", 'title' => "Carousel $i"], range(1, 12))
    ),
    'Stretch / Nomadic - Hero + Carousel' => array_merge(
        ['stretch_hero' => ['file' => 'images/stretch-hero-bg.jpg', 'title' => 'Hero Background']],
        array_combine(
            array_map(fn($i) => "stretch_carousel_$i", range(1, 12)),
            array_map(fn($i) => ['file' => "images/stretch-carousel-$i.jpg", 'title' => "Carousel $i"], range(1, 12))
        )
    ),
    'Stretch / Nomadic - Sections' => [
        'stretch_quote'        => ['file' => 'images/stretch-quote-bg.webp',   'title' => 'Quote Form Background'],
        'stretch_canvas_tent'  => ['file' => 'images/stretch-canvas-tent.webp','title' => 'Canvas Tent Image'],
        'stretch_colors'       => ['file' => 'images/stretch-colors.webp',     'title' => 'Colors Image'],
    ],
    'Why QTents - Icons (shared: Stretch & Sailcloth)' => [
        'why_fabrics'      => ['file' => 'images/stretch-why-fabrics.webp',      'title' => 'Fabrics'],
        'why_waterproof'   => ['file' => 'images/stretch-why-waterproof.webp',   'title' => 'Waterproof'],
        'why_firesafe'     => ['file' => 'images/stretch-why-firesafe.webp',     'title' => 'Fire Safe'],
        'why_uv'           => ['file' => 'images/stretch-why-uv.webp',           'title' => 'UV Resistant'],
        'why_algae'        => ['file' => 'images/stretch-why-algae.webp',        'title' => 'Anti-Algae'],
        'why_printing'     => ['file' => 'images/stretch-why-printing.webp',     'title' => 'Printing'],
        'why_instructions' => ['file' => 'images/stretch-why-instructions.webp', 'title' => 'Instructions'],
    ],
    'Sailcloth - Hero + Carousel' => array_merge(
        ['sailcloth_hero' => ['file' => 'images/sailcloth-hero-bg.jpg', 'title' => 'Hero Background']],
        array_combine(
            array_map(fn($i) => "sailcloth_carousel_$i", range(1, 12)),
            array_map(fn($i) => ['file' => "images/sailcloth-carousel-$i.jpg", 'title' => "Carousel $i"], range(1, 12))
        )
    ),
    'Sailcloth - Sections' => [
        'sailcloth_quote'         => ['file' => 'images/sailcloth-quote-bg.webp',     'title' => 'Quote Form Background'],
        'sailcloth_canvas_layers' => ['file' => 'images/sailcloth-canvas-layers.webp','title' => 'Canvas Layers Image'],
        'sailcloth_why_bg'        => ['file' => 'images/sailcloth-why-bg.webp',       'title' => 'Why Section Background'],
    ],
    'Custom / Bespoke - Hero + Carousel' => array_merge(
        [
            'custom_hero'  => ['file' => 'images/custom-hero-bg.jpg',   'title' => 'Hero Background'],
            'custom_quote' => ['file' => 'images/custom-quote-bg.jpg',  'title' => 'Quote Background'],
        ],
        array_combine(
            array_map(fn($i) => "custom_carousel_$i", range(1, 12)),
            array_map(fn($i) => ['file' => "images/custom-carousel-$i.jpg", 'title' => "Carousel $i"], range(1, 12))
        )
    ),
    'Stretch / Nomadic - Configurator Sizes (18)' => [
        'stretch_config_4-5x4-5'   => ['file' => 'images/stretch-config-4-5x4-5.jpg',   'title' => '4.5×4.5'],
        'stretch_config_4-5x7-5'   => ['file' => 'images/stretch-config-4-5x7-5.jpg',   'title' => '4.5×7.5'],
        'stretch_config_6x6'       => ['file' => 'images/stretch-config-6x6.jpg',       'title' => '6×6'],
        'stretch_config_6x10-5'    => ['file' => 'images/stretch-config-6x10-5.jpg',    'title' => '6×10.5'],
        'stretch_config_6x15'      => ['file' => 'images/stretch-config-6x15.jpg',      'title' => '6×15'],
        'stretch_config_7-5x7-5'   => ['file' => 'images/stretch-config-7-5x7-5.jpg',   'title' => '7.5×7.5'],
        'stretch_config_7-5x10-5'  => ['file' => 'images/stretch-config-7-5x10-5.jpg',  'title' => '7.5×10.5'],
        'stretch_config_9x9'       => ['file' => 'images/stretch-config-9x9.jpg',       'title' => '9×9'],
        'stretch_config_9x15'      => ['file' => 'images/stretch-config-9x15.jpg',      'title' => '9×15'],
        'stretch_config_10-5x10-5' => ['file' => 'images/stretch-config-10-5x10-5.jpg', 'title' => '10.5×10.5'],
        'stretch_config_12x12'     => ['file' => 'images/stretch-config-12x12.jpg',     'title' => '12×12'],
        'stretch_config_12x18'     => ['file' => 'images/stretch-config-12x18.jpg',     'title' => '12×18'],
        'stretch_config_15x15'     => ['file' => 'images/stretch-config-15x15.jpg',     'title' => '15×15'],
        'stretch_config_18x18'     => ['file' => 'images/stretch-config-18x18.jpg',     'title' => '18×18'],
        'stretch_config_21x21'     => ['file' => 'images/stretch-config-21x21.jpg',     'title' => '21×21'],
        'stretch_config_21x25-5'   => ['file' => 'images/stretch-config-21x25-5.jpg',   'title' => '21×25.5'],
        'stretch_config_21x30'     => ['file' => 'images/stretch-config-21x30.jpg',     'title' => '21×30'],
        'stretch_config_custom'    => ['file' => 'images/stretch-config-custom.jpg',    'title' => 'Custom'],
    ],
    'Sailcloth - Configurator Sizes (22)' => [
        'sailcloth_config_6x6'    => ['file' => 'images/sailcloth-config-6x6.jpg',    'title' => '6×6'],
        'sailcloth_config_6x12'   => ['file' => 'images/sailcloth-config-6x12.jpg',   'title' => '6×12'],
        'sailcloth_config_6x18'   => ['file' => 'images/sailcloth-config-6x18.jpg',   'title' => '6×18'],
        'sailcloth_config_6x24'   => ['file' => 'images/sailcloth-config-6x24.jpg',   'title' => '6×24'],
        'sailcloth_config_6x30'   => ['file' => 'images/sailcloth-config-6x30.jpg',   'title' => '6×30'],
        'sailcloth_config_8x8'    => ['file' => 'images/sailcloth-config-8x8.jpg',    'title' => '8×8'],
        'sailcloth_config_8x16'   => ['file' => 'images/sailcloth-config-8x16.jpg',   'title' => '8×16'],
        'sailcloth_config_8x24'   => ['file' => 'images/sailcloth-config-8x24.jpg',   'title' => '8×24'],
        'sailcloth_config_8x32'   => ['file' => 'images/sailcloth-config-8x32.jpg',   'title' => '8×32'],
        'sailcloth_config_10x10'  => ['file' => 'images/sailcloth-config-10x10.jpg',  'title' => '10×10'],
        'sailcloth_config_10x20'  => ['file' => 'images/sailcloth-config-10x20.jpg',  'title' => '10×20'],
        'sailcloth_config_10x30'  => ['file' => 'images/sailcloth-config-10x30.jpg',  'title' => '10×30'],
        'sailcloth_config_12x12'  => ['file' => 'images/sailcloth-config-12x12.jpg',  'title' => '12×12'],
        'sailcloth_config_12x24'  => ['file' => 'images/sailcloth-config-12x24.jpg',  'title' => '12×24'],
        'sailcloth_config_12x36'  => ['file' => 'images/sailcloth-config-12x36.jpg',  'title' => '12×36'],
        'sailcloth_config_14x14'  => ['file' => 'images/sailcloth-config-14x14.jpg',  'title' => '14×14'],
        'sailcloth_config_14x28'  => ['file' => 'images/sailcloth-config-14x28.jpg',  'title' => '14×28'],
        'sailcloth_config_14x42'  => ['file' => 'images/sailcloth-config-14x42.jpg',  'title' => '14×42'],
        'sailcloth_config_20x20'  => ['file' => 'images/sailcloth-config-20x20.jpg',  'title' => '20×20'],
        'sailcloth_config_20x26'  => ['file' => 'images/sailcloth-config-20x26.jpg',  'title' => '20×26'],
        'sailcloth_config_20x32'  => ['file' => 'images/sailcloth-config-20x32.jpg',  'title' => '20×32'],
        'sailcloth_config_20x38'  => ['file' => 'images/sailcloth-config-20x38.jpg',  'title' => '20×38'],
    ],
];

// Flat map for POST lookup
$images_config = array_merge(...array_values($sections));

$allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_key'])) {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';
    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);
    $key = trim($_POST['image_key']);

    if (!array_key_exists($key, $images_config)) {
        $message     = 'Invalid image key.';
        $messageType = 'error';
    } elseif (!isset($_FILES['new_image']) || $_FILES['new_image']['error'] !== UPLOAD_ERR_OK) {
        $message     = 'No file uploaded or upload error (code ' . ($_FILES['new_image']['error'] ?? '?') . ').';
        $messageType = 'error';
    } else {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['new_image']['tmp_name']);

        if (!in_array($mimeType, $allowed_mime, true)) {
            $message     = 'Rejected: only JPEG, PNG, WebP, GIF and AVIF are allowed.';
            $messageType = 'error';
        } else {
            // Push the replacement to the frontend server over HTTP.
            $result = upload_image_to_frontend(
                $images_config[$key]['file'],
                $_FILES['new_image']['tmp_name'],
                (string) $mimeType,
                (string) ($_FILES['new_image']['name'] ?? 'upload')
            );
            if ($result['ok']) {
                $message     = 'Successfully replaced: ' . e($images_config[$key]['title']);
                $messageType = 'success';
            } else {
                $message     = 'Upload failed: ' . e($result['error']);
                $messageType = 'error';
            }
        }
    }
}

// Re-issue a CSRF token for the freshly rendered forms (the previous one was
// consumed above on a successful POST verification).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Current image metadata from the frontend (size + mtime per slot).
$metaResult = fetch_image_meta();
$imageMeta  = $metaResult['files'];
$metaOk     = $metaResult['ok'];

$layout    = 'app';
$activeNav = 'images';
?>
<script type="application/json" id="page-meta">
{
    "title": "Image Manager - Majestic Marquees Admin"
}
</script>

<div class="space-y-10">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Image Manager</h2>
        <p class="text-sm text-gray-500 mt-1">
            Upload a replacement image and it is pushed straight to the live website over the API.
            The current image is always shown - if it looks like the wrong picture, just replace it.
        </p>
        <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-800 space-y-1">
            <p class="font-semibold">Before you upload - size &amp; resolution</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li><span class="font-medium">Max file size: 2&nbsp;MB.</span> Larger files are rejected with an "upload error (code 1)". Compress the photo first (e.g. tinypng.com) if it is bigger.</li>
                <li><span class="font-medium">Formats:</span> JPEG, PNG, WebP, GIF or AVIF only.</li>
                <li><span class="font-medium">Configurator (tent size) images:</span> displayed at a 4:3 ratio and cropped to fit - use a 4:3 image (e.g. 1200×900 or 1600×1200) to avoid unwanted cropping.</li>
                <li>There is no minimum resolution, but use sharp images at least 1000&nbsp;px wide for best quality.</li>
            </ul>
        </div>
    </div>

    <?php if ($message !== ''): ?>
    <div class="rounded-lg border px-5 py-4 text-sm font-medium
        <?= $messageType === 'success'
            ? 'bg-green-50 border-green-300 text-green-800'
            : 'bg-red-50 border-red-300 text-red-800' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <?php if (!$metaOk): ?>
    <div class="rounded-lg border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-800">
        Couldn't reach the website server to load the current images. You can still upload - changes apply once the site is reachable. Refresh to retry.
    </div>
    <?php endif; ?>

    <?php foreach ($sections as $sectionName => $entries): ?>
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-widest text-gray-500 border-b border-gray-200 pb-2 mb-4">
            <?= e($sectionName) ?>
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($entries as $key => $cfg):
                $meta       = $imageMeta[$cfg['file']] ?? null;
                $exists     = $meta !== null;
                $verAt      = $exists ? (int) ($meta['updated_at'] ?? 0) : null;
                // Preview straight from the live website (cross-origin <img> is fine).
                $previewSrc = $exists ? e(frontend_asset_url($cfg['file'], $verAt)) : null;
                $mtime      = $exists ? date('d M Y H:i', $verAt) : null;
                $size       = $exists ? round(((int) ($meta['bytes'] ?? 0)) / 1024) . ' KB' : null;
            ?>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col">

                <!-- Preview -->
                <div class="relative h-36 bg-gray-100 flex items-center justify-center overflow-hidden">
                    <?php if ($previewSrc): ?>
                        <img src="<?= $previewSrc ?>"
                             alt="<?= e($cfg['title']) ?>"
                             class="w-full h-full object-cover">
                        <span class="absolute bottom-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded">
                            <?= $size ?>
                        </span>
                    <?php else: ?>
                        <div class="text-center px-2">
                            <svg class="mx-auto mb-1 text-gray-300" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider">No image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info + upload -->
                <div class="p-3 flex flex-col gap-2 flex-1">
                    <div>
                        <p class="text-xs font-semibold text-gray-800 leading-snug"><?= e($cfg['title']) ?></p>
                        <p class="text-[10px] text-gray-400 font-mono mt-0.5 break-all"><?= e($cfg['file']) ?></p>
                        <?php if ($mtime): ?>
                        <p class="text-[10px] text-gray-400 mt-0.5">Updated: <?= $mtime ?></p>
                        <?php else: ?>
                        <p class="text-[10px] text-red-400 mt-0.5">File missing on disk</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="/images" enctype="multipart/form-data" class="mt-auto flex flex-col gap-1.5">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="image_key" value="<?= e($key) ?>">
                        <input type="file"
                               name="new_image"
                               accept="image/jpeg,image/png,image/webp,image/gif,image/avif"
                               required
                               class="text-[10px] text-gray-600 w-full
                                      file:mr-2 file:py-1 file:px-2
                                      file:rounded file:border-0 file:text-[10px] file:font-medium
                                      file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                        <button type="submit"
                                class="w-full py-1.5 px-3 bg-indigo-600 hover:bg-indigo-700
                                       text-white text-[11px] font-semibold rounded transition-colors">
                            Replace
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>
