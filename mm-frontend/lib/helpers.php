<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * Shared helpers for the Majestic Marquees raw-PHP frontend.
 * Pure PHP - no framework. Included once by public/index.php.
 */

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
define('API_KEY',  'mq-prod-public-key-001');
define('ORIGIN',   $_is_local ? 'http://localhost:8001'   : 'https://website.majesticmarquees.clickdigim.com');
unset($_is_local);

// ─────────────────────────────────────────────────────────────────
// Email-code verification (held in the PHP session, no backend table).
// ─────────────────────────────────────────────────────────────────
define('FORM_CODE_TTL',     600);   // seconds the emailed code stays valid (10 min)
define('FORM_MAX_ATTEMPTS', 5);     // wrong-code tries before the visitor must restart

// ─────────────────────────────────────────────────────────────────
// Google reCAPTCHA v2 (checkbox) - bot protection on public forms.
//   • Site key   (public, rendered in the form)        → RECAPTCHA_SITE_KEY
//   • Secret key (private, used in server-side verify)  → RECAPTCHA_SECRET_KEY
// Docs: https://developers.google.com/recaptcha/docs/display
//       https://developers.google.com/recaptcha/docs/verify
// ─────────────────────────────────────────────────────────────────
define('RECAPTCHA_SITE_KEY',   '6LfUaSQtAAAAAIam7YoA7X3pPvqhYQQGDaqtxel6');
define('RECAPTCHA_SECRET_KEY', '6LfUaSQtAAAAAICBppCx2Ro-IeEedzhSZ45VS-jk');


/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Return (creating if needed) the per-session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input markup carrying the current CSRF token. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify a submitted CSRF token against the stable per-session token. */
function verify_csrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';
    return $expected !== '' && hash_equals($expected, $submitted);
}

/**
 * Markup for the Google reCAPTCHA v2 checkbox widget. Place INSIDE the <form>
 * so the generated `g-recaptcha-response` field is submitted with the form.
 * Rendered explicitly by app.js (see renderRecaptcha) so it also works after
 * SPA navigation.
 *
 * @param string $theme 'light' or 'dark'.
 */
function recaptcha_widget(string $theme = 'light', string $extraClass = ''): string
{
    return '<div class="g-recaptcha ' . e($extraClass) . '" '
         . 'data-sitekey="' . e(RECAPTCHA_SITE_KEY) . '" '
         . 'data-theme="' . e($theme) . '"></div>';
}

/**
 * Server-side verification of a Google reCAPTCHA token. Returns true only when
 * Google confirms the challenge was solved by a human. Blocks bots / automated
 * submissions before any email is sent.
 * Docs: https://developers.google.com/recaptcha/docs/verify
 */
function verify_recaptcha(string $token): bool
{
    if ($token === '') {
        return false;
    }

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = is_string($response) ? json_decode($response, true) : null;
    return is_array($data) && !empty($data['success']);
}

/**
 * Return a web-accessible URL for an asset file, with a cache-busting
 * version query string derived from the file's last-modified time.
 *
 * Usage: <img src="<?= get_image_url('images/home-hero-bg.jpg') ?>">
 */
function get_image_url(string $filename): string
{
    $diskPath = __DIR__ . '/../public/assets/' . ltrim($filename, '/');
    $version  = file_exists($diskPath) ? filemtime($diskPath) : time();
    return '/assets/' . ltrim($filename, '/') . '?v=' . $version;
}

/**
 * Build the shared tenant-authentication + tracing headers sent on every
 * backend call. Centralised so api_post() and api_get() stay in lock-step.
 *
 * `X-CSRF-Token` carries the visitor's session-bound CSRF token so the
 * request is tied end-to-end to an established browser session (defence in
 * depth; the backend can enforce it once it tracks the token).
 *
 * @param bool $json When true, also advertise a JSON request body.
 * @return string[]
 */
function api_headers(bool $json): array
{
    $headers = [
        'Accept: application/json',
        'X-API-Key: '       . API_KEY,
        'X-CSRF-Token: '    . csrf_token(),
        'Origin: '          . ORIGIN,
        'User-Agent: '      . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
        'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
    ];
    if ($json) {
        array_unshift($headers, 'Content-Type: application/json');
    }
    return $headers;
}

/**
 * POST a JSON payload to the backend API.
 *
 * Sends the tenant authentication headers required by the white-label
 * backend (X-API-Key + Origin), the visitor's session CSRF token, and
 * forwards the visitor's User-Agent and IP so the API can log/rate-limit
 * the real client.
 *
 * Returns ['ok' => bool, 'message' => string].
 */
function api_post(string $path, array $payload): array
{
    $ch = curl_init(API_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => api_headers(true),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response = curl_exec($ch);
    $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = is_string($response) ? json_decode($response, true) : null;

    if ($code >= 200 && $code < 300) {
        return ['ok' => true, 'message' => $json['message'] ?? "Thank you! We'll be in touch soon."];
    }
    return ['ok' => false, 'message' => $json['message'] ?? 'Something went wrong. Please try again.'];
}

/**
 * GET JSON data from the backend API.
 *
 * Sends the same tenant authentication headers as api_post() and decodes
 * the JSON response. Use $query to append a query string (e.g. ['page' => 'home']).
 *
 * Returns ['ok' => bool, 'status' => int, 'data' => mixed].
 */
function api_get(string $path, array $query = []): array
{
    $url = API_BASE . $path;
    if ($query !== []) {
        $url .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => api_headers(false),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response = curl_exec($ch);
    $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = is_string($response) ? json_decode($response, true) : null;

    return [
        'ok'     => $code >= 200 && $code < 300,
        'status' => $code,
        'data'   => $data,
    ];
}

/**
 * Render the email-code entry step (step 2 of the verified form flow).
 * Posts back to the same page with form_step=verify + the captured email.
 *
 * @param bool $dark  Use light inputs suited to a dark/coloured background.
 */
function render_verification_step(string $email, string $error = '', bool $dark = false): void
{
    $muted    = $dark ? 'text-cream-50/85' : 'text-forest-700/80';
    $strong   = $dark ? 'text-cream-50'    : 'text-forest-800';
    $errCls   = $dark ? 'text-red-300'     : 'text-red-700';
    $inputCls = $dark
        ? 'w-full bg-cream-50 border-0 rounded-md px-5 py-3 text-center text-lg tracking-[0.5em] text-forest-800 focus:outline-none focus:ring-2 focus:ring-tan-500'
        : 'w-full bg-[#f5f1e8] border border-forest-800/30 rounded-md px-5 py-3 text-center text-lg tracking-[0.5em] text-forest-800 focus:outline-none focus:border-tan-500';
    ?>
    <form class="space-y-4 max-w-md mx-auto" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="form_step" value="verify">
        <input type="hidden" name="email" value="<?= e($email) ?>">
        <p class="<?= $muted ?> text-sm text-center">
            We've emailed a 6-digit verification code to
            <strong class="<?= $strong ?>"><?= e($email) ?></strong>.
            Enter it below to confirm and send your enquiry.
        </p>
        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9]*" maxlength="6" required placeholder="••••••"
               class="<?= $inputCls ?>">
        <?php if ($error !== ''): ?><p class="<?= $errCls ?> text-sm text-center"><?= e($error) ?></p><?php endif; ?>
        <div class="pt-2 text-center">
            <button type="submit" class="btn-primary px-10">Confirm &amp; Send</button>
        </div>
    </form>
    <?php
}

/**
 * Render a Quote request form (compact or background-image variant) that
 * submits a real POST to the current page. POST handling + status must be
 * prepared by the page via handle_quote_submit().
 *
 * @param array{
 *   id?:string, source?:string, variant?:string, eyebrow?:string,
 *   title?:string, subtitle?:string, submitLabel?:string, bgImage?:string,
 *   status?:array{ok:bool,message:string}|null
 * } $o
 */
function render_quote_form(array $o): void
{
    $id      = $o['id'] ?? 'tailored-quote';
    $variant = $o['variant'] ?? 'compact';
    $eyebrow = $o['eyebrow'] ?? '';
    $title   = $o['title'] ?? 'Personalized Quote';
    $subtitle = $o['subtitle'] ?? '';
    $submit  = $o['submitLabel'] ?? 'Send Inquiry';
    $bgImage = $o['bgImage'] ?? '';
    $status  = $o['status'] ?? null;
    $step    = $status['step'] ?? 'form';
    $ok      = $status['ok']   ?? false;
    $success = ($step === 'done');
    $isVerify = ($step === 'verify');
    $verifyEmail = $status['email'] ?? '';
    $error   = ($status && !$ok) ? ($status['message'] ?? '') : '';

    $mailIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 7l9 6 9-6"></path></svg>';

    if ($variant === 'bgImage') {
        ?>
        <section id="<?= e($id) ?>" data-ajax-form-region class="relative py-20 sm:py-28"
                 style="background-image:url('<?= e($bgImage) ?>');background-size:cover;background-position:center;">
            <div class="absolute inset-0 bg-black/55" aria-hidden="true"></div>
            <div class="relative z-10 container-x max-w-5xl mx-auto">
                <div class="mb-10">
                    <?php if ($eyebrow !== ''): ?>
                        <p class="text-cream-50/70 uppercase tracking-widest text-xs mb-3"><?= e($eyebrow) ?></p>
                    <?php endif; ?>
                    <h2 class="heading-xl" style="color:#ffffff;"><?= e($title) ?></h2>
                    <p class="mt-5 text-white/80 max-w-3xl text-body"><?= e($subtitle) ?></p>
                </div>
                <?php if ($success): ?>
                    <div class="max-w-2xl mx-auto">
                        <p class="text-center text-white font-medium py-6 text-lg">Thank you! We'll be in touch soon.</p>
                    </div>
                <?php elseif ($isVerify): ?>
                    <?php render_verification_step($verifyEmail, $error, true); ?>
                <?php else: ?>
                    <form class="space-y-4 max-w-2xl mx-auto" method="POST">
                        <?= csrf_field() ?>
                        <input type="text" name="name" placeholder="Name*" required
                               class="w-full bg-cream-50/95 border-0 rounded-lg px-6 py-4 text-sm text-forest-800 placeholder:text-forest-700/55 focus:outline-none focus:ring-2 focus:ring-tan-500">
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-forest-700/50 pointer-events-none" aria-hidden="true"><?= $mailIcon ?></span>
                            <input type="email" name="email" placeholder="Your best email address*" required
                                   class="w-full bg-cream-50/95 border-0 rounded-lg pl-11 pr-6 py-4 text-sm text-forest-800 placeholder:text-forest-700/55 focus:outline-none focus:ring-2 focus:ring-tan-500">
                        </div>
                        <label class="flex items-start gap-3 pt-1 cursor-pointer">
                            <input type="checkbox" name="agree" required class="mt-0.5 w-4 h-4 accent-tan-500 shrink-0">
                            <span class="text-white/85 text-sm leading-snug">By submitting this form, you agree to our
                                <a href="/terms-conditions" class="spa-link underline underline-offset-2 hover:text-cream-100">Terms and Conditions</a>.
                            </span>
                        </label>
                        <?= recaptcha_widget('dark') ?>
                        <?php if ($error !== ''): ?><p class="text-red-300 text-sm"><?= e($error) ?></p><?php endif; ?>
                        <div class="pt-4 text-center">
                            <button type="submit" class="btn-primary px-14 py-3 text-sm"><?= e($submit) ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return;
    }

    // compact variant
    ?>
    <section id="<?= e($id) ?>" data-ajax-form-region class="section bg-[#d7c8a5] pt-6 sm:pt-8 pb-12 sm:pb-16">
        <div class="container-x max-w-4xl mx-auto text-center">
            <?php if ($eyebrow !== ''): ?><p class="eyebrow mb-4 text-cream-50/80"><?= e($eyebrow) ?></p><?php endif; ?>
            <h2 class="heading-xl text-cream-50"><?= e($title) ?></h2>
            <p class="mt-6 text-cream-50/90 max-w-2xl mx-auto text-body"><?= e($subtitle) ?></p>
            <?php if ($success): ?>
                <p class="mt-16 text-cream-50 font-medium text-lg">Thank you! We'll be in touch soon.</p>
            <?php elseif ($isVerify): ?>
                <div class="mt-16 text-left max-w-xl mx-auto">
                    <?php render_verification_step($verifyEmail, $error, true); ?>
                </div>
            <?php else: ?>
                <form class="mt-16 space-y-4 text-left max-w-xl mx-auto" method="POST">
                    <?= csrf_field() ?>
                    <label class="block">
                        <span class="sr-only">Name</span>
                        <input type="text" name="name" placeholder="Name*" required
                               class="w-full bg-cream-50 border-0 rounded-md px-5 py-2.5 text-sm text-forest-800 placeholder:text-forest-700/50 focus:outline-none focus:ring-2 focus:ring-tan-500">
                    </label>
                    <label class="block">
                        <span class="sr-only">Email</span>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-forest-700/50" aria-hidden="true"><?= $mailIcon ?></span>
                            <input type="email" name="email" placeholder="Your best email address*" required
                                   class="w-full bg-cream-50 border-0 rounded-md pl-10 pr-5 py-2.5 text-sm text-forest-800 placeholder:text-forest-700/50 focus:outline-none focus:ring-2 focus:ring-tan-500">
                        </div>
                    </label>
                    <label class="flex items-start gap-3 justify-center pt-1">
                        <input type="checkbox" name="agree" required class="mt-0.5 w-4 h-4 accent-tan-500">
                        <span class="text-cream-50/90 text-xs sm:text-sm">By requesting a quote, you agree to our Terms and Conditions.</span>
                    </label>
                    <div class="flex justify-center"><?= recaptcha_widget('light') ?></div>
                    <?php if ($error !== ''): ?><p class="text-red-700 text-sm text-center"><?= e($error) ?></p><?php endif; ?>
                    <div class="pt-3 text-center">
                        <button type="submit" class="btn-primary px-10"><?= e($submit) ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Shared two-step, email-verified form processor.
 *
 * The verification code lives entirely in THIS PHP session - no backend
 * table is involved. The backend only emails the code and (after we verify
 * it) stores the lead.
 *
 * Step 1 ('request'): validate fields, verify the Google reCAPTCHA token
 *   server-side, generate a 6-digit code, stash its hash + the form payload in
 *   $_SESSION, then ask the backend to email the code. Nothing is stored yet.
 * Step 2 ('verify'): compare the entered code against the session (expiry +
 *   attempt limited). On success, post the stored payload to the backend to
 *   create the lead, then clear the session.
 *
 * Returns a status array:
 *   ['ok' => bool, 'step' => 'form'|'verify'|'done', 'message' => string,
 *    'email' => string]
 *   - step 'form'   → re-render the data-entry form (with $message as error)
 *   - step 'verify' → render the code-entry form for $email
 *   - step 'done'   → submission complete; show the success message
 */
function process_form_submit(string $source, bool $requireAgree = false): array
{
    if (!verify_csrf()) {
        return ['ok' => false, 'step' => 'form', 'message' => 'Invalid request. Please try again.', 'email' => ''];
    }

    // ── Step 2: confirm the emailed code against the PHP session ──
    if (($_POST['form_step'] ?? '') === 'verify') {
        $email = trim($_POST['email'] ?? '');
        $code  = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $v     = $_SESSION['form_verify'] ?? null;

        if (!is_array($v) || $email === '' || ($v['email'] ?? '') !== $email) {
            unset($_SESSION['form_verify']);
            return ['ok' => false, 'step' => 'form', 'message' => 'Your session expired. Please start again.', 'email' => ''];
        }
        if (time() > ($v['expires'] ?? 0)) {
            unset($_SESSION['form_verify']);
            return ['ok' => false, 'step' => 'form', 'message' => 'This code has expired. Please start again.', 'email' => ''];
        }
        if (($v['attempts'] ?? 0) >= FORM_MAX_ATTEMPTS) {
            unset($_SESSION['form_verify']);
            return ['ok' => false, 'step' => 'form', 'message' => 'Too many attempts. Please start again.', 'email' => ''];
        }
        if (strlen($code) !== 6) {
            return ['ok' => false, 'step' => 'verify', 'email' => $email, 'message' => 'Please enter the 6-digit code we emailed you.'];
        }
        if (!password_verify($code, $v['code_hash'] ?? '')) {
            $_SESSION['form_verify']['attempts'] = ($v['attempts'] ?? 0) + 1;
            return ['ok' => false, 'step' => 'verify', 'email' => $email, 'message' => 'Incorrect code. Please try again.'];
        }

        // Verified - create the lead from the session-stored payload (anti-tamper).
        $res = api_post('/wl/forms/contact', $v['payload']);
        unset($_SESSION['form_verify']);
        if ($res['ok']) {
            return ['ok' => true, 'step' => 'done', 'email' => $email, 'message' => $res['message']];
        }
        return ['ok' => false, 'step' => 'form', 'email' => $email, 'message' => $res['message']];
    }

    // ── Step 1: validate + email a verification code ─────────────
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'step' => 'form', 'message' => 'Please enter a valid name and email address.', 'email' => $email];
    }
    if ($requireAgree && empty($_POST['agree'])) {
        return ['ok' => false, 'step' => 'form', 'message' => 'Please accept the Terms and Conditions.', 'email' => $email];
    }

    // Google reCAPTCHA - block bots before any email is sent.
    if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        return ['ok' => false, 'step' => 'form', 'message' => 'Please complete the reCAPTCHA and try again.', 'email' => $email];
    }

    // Generate the code; stash its hash + the form payload in the session.
    $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $payload = [
        'name'    => $name,
        'email'   => $email,
        'message' => trim($_POST['message'] ?? ''),
        'source'  => $source,
    ];
    $_SESSION['form_verify'] = [
        'email'     => $email,
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'payload'   => $payload,
        'attempts'  => 0,
        'expires'   => time() + FORM_CODE_TTL,
    ];

    // Ask the backend to email the code (it holds the tenant SMTP config).
    $res = api_post('/wl/forms/send-code', [
        'email' => $email,
        'name'  => $name,
        'code'  => $code,
    ]);

    if ($res['ok']) {
        return ['ok' => true, 'step' => 'verify', 'email' => $email, 'message' => $res['message']];
    }
    unset($_SESSION['form_verify']);
    return ['ok' => false, 'step' => 'form', 'email' => $email, 'message' => $res['message']];
}

/**
 * Process a quote-form POST for the current page.
 * Returns the status array (or null when not a matching POST).
 */
function handle_quote_submit(string $source): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }
    return process_form_submit($source, true);
}

/**
 * Process a contact-form POST for the current page.
 */
function handle_contact_submit(): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }
    return process_form_submit('contact-form', false);
}

/** Open a carousel. Arrows/dots are built by app.js based on the data flags. */
function carousel_open(array $o = []): void
{
    $class  = $o['class'] ?? '';
    $arrows = ($o['arrows'] ?? true) ? '1' : '0';
    $dots   = ($o['dots'] ?? true) ? '1' : '0';
    $autoplay = ($o['autoplay'] ?? true) ? (string) ($o['interval'] ?? 5000) : '0';
    echo '<div class="relative ' . $class . '" data-carousel data-arrows="' . $arrows . '" data-dots="' . $dots . '" data-autoplay="' . $autoplay . '">'
        . '<div class="overflow-hidden" data-carousel-viewport>'
        . '<div class="flex" data-carousel-track>';
}

/** Close a carousel opened with carousel_open(). */
function carousel_close(): void
{
    echo '</div></div></div>';
}

/**
 * Render an FAQ-style accordion. The open/close behaviour is handled by
 * app.js (first item open by default).
 *
 * @param array<int,array{q:string,a:string}> $items
 * @param string $variant 'lined' | 'boxed'
 */
function render_accordion(array $items, string $variant = 'lined'): void
{
    $isLined = $variant === 'lined';
    echo '<div data-accordion class="' . ($isLined ? 'space-y-0' : 'space-y-3') . '">';
    foreach ($items as $it) {
        $itemClass = $isLined
            ? 'border-t border-forest-700/50 last:border-b last:border-forest-700/50'
            : 'bg-white border border-cream-200';
        $btnClass = $isLined
            ? 'w-full flex justify-between items-center text-left py-5'
            : 'w-full flex justify-between items-center text-left px-6 py-5';
        $qClass = $isLined
            ? 'heading-s text-forest-800'
            : 'text-secondary-ttl text-forest-800';
        $panelClass = $isLined
            ? 'pb-6 pr-10 text-body text-forest-700/90 max-w-[92%]'
            : 'px-6 pb-6 text-forest-700/80 text-body';
        $icon = $isLined
            ? '<span class="text-forest-800 shrink-0 ml-4"><svg data-accordion-icon class="h-3 w-3 transition-transform" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true"><path d="M2 1 L8 5 L2 9 Z"></path></svg></span>'
            : '<span class="text-tan-500 text-2xl shrink-0 ml-4" data-accordion-icon data-icon-type="plus">+</span>';

        echo '<div data-accordion-item class="' . $itemClass . '">'
            . '<button type="button" data-accordion-trigger class="' . $btnClass . '">'
            . '<span class="' . $qClass . '">' . e($it['q']) . '</span>'
            . $icon
            . '</button>'
            . '<div data-accordion-panel class="' . $panelClass . '">' . e($it['a']) . '</div>'
            . '</div>';
    }
    echo '</div>';
}

/**
 * Render the PageHero used on legal pages.
 */
function page_hero(string $title, string $eyebrow = '', string $subtitle = '', string $bgImage = ''): void
{
    $style = $bgImage !== ''
        ? 'background-image:url(\'' . e($bgImage) . '\');'
        : 'background-color:#3a4a3a;';
    ?>
    <section class="page-hero relative w-full h-[70vh] min-h-[420px] bg-cover bg-center flex items-center justify-center" style="<?= $style ?>">
        <div class="absolute inset-0 bg-black/45" aria-hidden="true"></div>
        <div class="relative z-10 container-x text-center text-white">
            <?php if ($eyebrow !== ''): ?>
                <p class="font-display italic text-secondary-ttl mb-5"><?= e($eyebrow) ?></p>
            <?php endif; ?>
            <h1 class="heading-xl text-white max-w-5xl mx-auto drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)]"><?= e($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p class="mt-6 text-body max-w-3xl mx-auto drop-shadow-[0_1px_4px_rgba(0,0,0,0.5)]"><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render a row of colour swatches.
 *
 * @param array<int,array{name:string,hex:string}> $colors
 */
function render_color_swatches(array $colors): void
{
    echo '<div class="flex flex-wrap gap-4">';
    foreach ($colors as $c) {
        echo '<div class="text-center">'
            . '<div class="w-16 h-16 border border-cream-200 shadow-sm" style="background:' . e($c['hex']) . '"></div>'
            . '<div class="mt-2 text-xs uppercase tracking-wider text-forest-700">' . e($c['name']) . '</div>'
            . '</div>';
    }
    echo '</div>';
}


/**
 * Testimonials section. The heading/subheading are static; the cards are
 * hydrated by app.js from the backend (optionally filtered by $page).
 */
function render_testimonials(string $heading = 'What Clients Say', ?string $subheading = null, string $bgClass = 'bg-[#d7c7a5]', ?string $page = null): void
{
    $sub  = $subheading ?? 'Our clients treasure the moments we create together.';
    $attr = $page !== null ? ' data-page="' . e($page) . '"' : '';
    ?>
    <section class="section py-24 sm:py-32 lg:py-40 <?= $bgClass ?>">
        <div class="container-x text-center mb-12">
            <h2 class="heading-l"><?= e($heading) ?></h2>
            <h3 class="mt-4 text-forest-700/80 italic text-secondary-ttl"><?= e($sub) ?></h3>
        </div>
        <div class="container-x">
            <div data-testimonials<?= $attr ?>></div>
        </div>
    </section>
    <?php
}

/**
 * Render a size configurator (Stretch / Sailcloth). The interactive tabs,
 * spec table and image carousel are built by app.js from the embedded JSON.
 *
 * @param array<int,array{label:string,variants:array<int,array<string,string>>}> $groups
 * @param array<int,string> $defaultImages Gallery images shown for variants that
 *        don't define their own `images` array.
 */
function render_configurator(array $groups, array $defaultImages = []): void
{
    ?>
    <div data-configurator data-default-images='<?= e(json_encode(array_values($defaultImages))) ?>'>
        <div class="flex flex-wrap justify-center gap-3 mb-4" data-config-groups></div>
        <div class="flex flex-wrap justify-center gap-3 mb-10" data-config-variants></div>
        <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] gap-8 lg:gap-12 items-start">
            <div><table class="w-full text-sm border-collapse"><tbody data-config-table></tbody></table></div>
            <div data-config-images></div>
        </div>
    </div>
    <script type="application/json" data-config-data><?= json_encode($groups) ?></script>
    <?php
}
