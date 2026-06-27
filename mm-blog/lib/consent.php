<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * GDPR cookie-consent banner (Layer 1) + preferences panel (Layer 2).
 *
 * Markup is rendered server-side and wired up by public/consent.js.
 * Bilingual (English + Bahasa Indonesia), in the brand palette.
 *
 * Bump CONSENT_VERSION whenever the categories or wording change in a way that
 * should force every visitor to choose again (consent.js re-asks on mismatch).
 */
define('CONSENT_VERSION', '2026-06-25');

/**
 * One consent category row in the preferences panel (Layer 2).
 *
 * @param string $cat    Category key (matches consent.js CATEGORIES).
 * @param string $enName English label.
 * @param string $idName Bahasa Indonesia label.
 * @param string $enDesc English description.
 * @param string $idDesc Bahasa Indonesia description.
 * @param bool   $locked Strictly-necessary row - always on, cannot be toggled.
 */
function consent_category_row(string $cat, string $enName, string $idName, string $enDesc, string $idDesc, bool $locked = false): void
{
    ?>
    <div class="flex items-start justify-between gap-4 py-4 border-b border-forest-800/10">
        <div class="min-w-0">
            <p class="text-primary-ttl text-[17px]"><?= e($enName) ?> <span class="text-forest-700/50 font-sans text-[14px]">/ <?= e($idName) ?></span></p>
            <p class="text-body-s text-forest-700/80 mt-1"><?= e($enDesc) ?></p>
            <p class="text-body-s text-forest-700/55 mt-0.5 italic"><?= e($idDesc) ?></p>
        </div>
        <?php if ($locked): ?>
            <span class="shrink-0 mt-1 text-[12px] uppercase tracking-wider text-forest-700/60 border border-forest-800/20 rounded-full px-3 py-1">Always on</span>
            <input type="checkbox" data-cat="<?= e($cat) ?>" checked disabled class="hidden">
        <?php else: ?>
            <label class="shrink-0 mt-1 relative inline-flex items-center cursor-pointer">
                <input type="checkbox" data-cat="<?= e($cat) ?>" class="peer sr-only">
                <span class="w-11 h-6 bg-forest-800/20 rounded-full peer-checked:bg-tan-500 transition-colors"></span>
                <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></span>
            </label>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Output the cookie banner + preferences panel. Call once, just before
 * </body>. Hidden by default; consent.js reveals Layer 1 on first visit and
 * Layer 2 on request.
 */
function render_cookie_consent(): void
{
    $cookiePolicy = rtrim(MAIN_SITE, '/') . '/cookie-policy';
    ?>
    <!-- ── Layer 1: consent banner (bottom bar) ─────────────────────── -->
    <div data-consent-banner class="hidden fixed inset-x-0 bottom-0 z-[130] bg-cream-50 border-t border-forest-800/20 shadow-[0_-8px_30px_rgba(35,48,31,0.12)]"
         role="region" aria-label="Cookie consent">
        <div class="container-x py-5 sm:py-6 flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-8">
            <div class="min-w-0 lg:flex-1">
                <p class="text-primary-ttl text-[18px]">We value your privacy</p>
                <p class="text-body-s text-forest-700/85 mt-1 max-w-3xl">
                    We use cookies to run this website and load our branded web fonts. With your consent, we also use analytics and marketing cookies to understand usage and improve our content and ads.
                    Click &ldquo;Accept all&rdquo; to allow all cookies, &ldquo;Reject all&rdquo; to keep only essential ones, or &ldquo;Customize&rdquo; to choose.
                    Read our <a href="<?= e($cookiePolicy) ?>" class="underline hover:text-tan-500">Cookie Policy</a>.
                </p>
                <p class="text-body-s text-forest-700/55 mt-1 italic max-w-3xl">
                    Kami menggunakan cookie untuk menjalankan situs ini dan memuat font kami. Dengan persetujuan Anda, kami juga menggunakan cookie analitik dan pemasaran.
                    Pilih &ldquo;Terima Semua&rdquo;, &ldquo;Tolak Semua&rdquo;, atau &ldquo;Sesuaikan&rdquo;.
                </p>
            </div>
            <div class="shrink-0 flex flex-col sm:flex-row gap-3 lg:gap-3">
                <button type="button" data-consent-reject class="btn-outline w-full sm:w-auto rounded-sm py-3">Reject all</button>
                <button type="button" data-consent-customize class="btn-outline w-full sm:w-auto rounded-sm py-3">Customize</button>
                <button type="button" data-consent-accept class="btn-primary w-full sm:w-auto">Accept all</button>
            </div>
        </div>
    </div>

    <!-- ── Layer 2: preferences panel ───────────────────────────────── -->
    <div data-consent-panel class="hidden fixed inset-0 z-[140]" role="dialog" aria-modal="true" aria-labelledby="consent-panel-title">
        <div data-consent-close class="absolute inset-0 bg-forest-900/60 backdrop-blur-sm"></div>
        <div class="relative h-full overflow-y-auto flex items-start sm:items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-2xl bg-cream-50 rounded-2xl shadow-xl my-8 p-6 sm:p-8">
                <button type="button" data-consent-close aria-label="Close"
                        class="absolute right-4 top-4 w-9 h-9 rounded-full flex items-center justify-center text-forest-700/70 hover:bg-forest-700/10 hover:text-forest-800 transition-colors text-2xl leading-none">&times;</button>
                <div class="mb-2 pr-8">
                    <h2 id="consent-panel-title" class="heading-m">Manage cookie preferences</h2>
                    <p class="text-body-s text-forest-700/70 mt-2">
                        Choose which cookies we may use. Strictly necessary cookies are always on. You can change this anytime via &ldquo;Cookie settings&rdquo; in the footer.
                    </p>
                </div>

                <div class="mt-4">
                    <?php
                    consent_category_row(
                        'necessary', 'Strictly necessary', 'Diperlukan',
                        'Required for the site to work (session, security). Always active.',
                        'Diperlukan agar situs berfungsi (sesi, keamanan). Selalu aktif.',
                        true
                    );
                    consent_category_row(
                        'functional', 'Functional', 'Fungsional',
                        'Loads our branded web fonts (Google Fonts) for the intended look and feel.',
                        'Memuat font khusus kami (Google Fonts) untuk tampilan yang sesuai.'
                    );
                    consent_category_row(
                        'analytics', 'Analytics', 'Analitik',
                        'Anonymous usage statistics (Google Analytics) to help us improve the site.',
                        'Statistik penggunaan anonim (Google Analytics) untuk meningkatkan situs.'
                    );
                    consent_category_row(
                        'marketing', 'Marketing', 'Pemasaran',
                        'Advertising and remarketing tools (e.g. Meta/Facebook Pixel) to measure and improve our ads.',
                        'Alat iklan dan pemasaran (mis. Meta/Facebook Pixel) untuk mengukur dan meningkatkan iklan kami.'
                    );
                    ?>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button" data-consent-reject class="btn-outline rounded-sm py-3">Reject all</button>
                    <button type="button" data-consent-accept class="btn-outline rounded-sm py-3">Accept all</button>
                    <button type="button" data-consent-save class="btn-primary">Save preferences</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
