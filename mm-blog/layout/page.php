<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageMeta['title'] ?? 'Blog | Majestic Marquees') ?></title>
    <link rel="icon" href="data:,">
    <?php foreach ($pageMeta['name'] ?? [] as $k => $v): ?>
    <meta name="<?= e($k) ?>" content="<?= e($v) ?>">
    <?php endforeach; ?>
    <?php foreach ($pageMeta['property'] ?? [] as $k => $v): ?>
    <meta property="<?= e($k) ?>" content="<?= e($v) ?>">
    <?php endforeach; ?>
    <?php if (!empty($pageMeta['canonical'])): ?>
    <link rel="canonical" href="<?= e($pageMeta['canonical']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageMeta['schema'])): ?>
    <script type="application/ld+json"><?= json_encode($pageMeta['schema']) ?></script>
    <?php endif; ?>

    <!-- Web fonts load via consent.js only after "functional" consent; until
         then the system-font fallbacks in tailwind.config apply. -->
    <script>
        window.MM_CONSENT  = {
            version: "<?= CONSENT_VERSION ?>",
            fonts: "https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        };
        window.MM_TRACKERS = { ga4: "", metaPixel: "" };
    </script>
    <script src="/consent.js" defer></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream:  { 50: '#faf6ec', 100: '#f4ecd9', 200: '#ede1c4' },
                        forest: { 500: '#586b4f', 600: '#475a40', 700: '#3a4a3a', 800: '#3f503c', 900: '#23301f' },
                        tan:    { 400: '#bd9676', 500: '#a57b5b', 600: '#8c6849' },
                        ink:    '#1f2519'
                    },
                    fontFamily: {
                        display: ['"Cormorant Garamond"', 'Playfair Display', 'Georgia', 'serif'],
                        sans:    ['Inter', 'system-ui', 'sans-serif']
                    },
                    maxWidth: { prose: '70ch' }
                }
            }
        };
    </script>
    <style type="text/tailwindcss">
        @layer base {
            html { scroll-behavior: smooth; }
            body { @apply bg-[#f5f1e8] text-forest-800 font-sans antialiased; }
            h1, h2, h3, h4, h5 { @apply font-display text-forest-800 tracking-tight; }
        }
        @layer components {
            .container-x { @apply mx-auto w-full max-w-7xl px-5 sm:px-8 lg:px-12; }
            .heading-m { @apply font-display font-semibold leading-[1.2] text-[28px] sm:text-[34px] lg:text-[40px]; }
            .text-primary-ttl { @apply font-display font-semibold text-[22px] sm:text-[24px]; }
            .text-body-s { @apply font-sans font-normal text-[14px] leading-[1.5]; }
            .hero-blog h1, .hero-blog h2, .hero-blog h3 { color: #ffffff !important; }
            .hero-blog, .hero-blog p, .hero-blog span, .hero-blog a { color: #ffffff; }
            .btn { @apply inline-flex items-center justify-center px-6 py-2 text-sm font-medium tracking-wider uppercase transition-colors; }
            .btn-primary { @apply btn bg-tan-500 text-white hover:bg-tan-600 rounded-sm py-3.5; }
            .btn-outline { @apply btn border border-forest-700 text-forest-800 hover:bg-forest-700 hover:text-cream-50; }
            .section { @apply py-16 sm:py-20 lg:py-28; }
            .eyebrow { @apply font-display italic text-lg text-forest-700; }
            .prose-blog { @apply max-w-prose text-forest-800/90 leading-relaxed; }
            .prose-blog p { @apply my-5; }
            .prose-blog h2 { @apply font-display text-3xl mt-12 mb-4 text-forest-800; }
            .prose-blog h3 { @apply font-display text-2xl mt-10 mb-3 text-forest-800; }
            .prose-blog ul { @apply list-disc pl-6 my-5 space-y-2; }
            .prose-blog a { @apply text-tan-500 underline underline-offset-2 hover:text-tan-600; }
            .prose-blog blockquote { @apply border-l-2 border-tan-500 pl-5 italic font-display text-xl text-forest-700 my-8; }
            .link-underline { @apply relative; }
            .link-underline::after {
                content: '';
                @apply absolute left-0 -bottom-0.5 h-px w-full bg-current origin-right scale-x-0 transition-transform duration-300 ease-out;
            }
            .link-underline:hover::after { @apply origin-left scale-x-100; }
        }
    </style>
</head>
<body>
<?php $main = rtrim(MAIN_SITE, '/'); ?>
<div class="flex flex-col min-h-screen">

    <header class="relative z-30 bg-[#F5F1E8]" id="main-nav">
        <div class="container-x flex items-center justify-between h-[90px]">
            <a href="/" class="flex items-center h-[90px] shrink-0 overflow-visible" aria-label="Blog home">
                <img src="/logo-original.webp" alt="Majestic Marquees" class="h-[62px] w-auto object-contain">
            </a>

            <nav class="hidden lg:flex items-center gap-8">
                <a href="<?= e($main) ?>/" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">Majestic Marquees</a>

                <div class="relative group">
                    <a href="<?= e($main) ?>/our-tents" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors inline-flex items-center gap-1 text-forest-800 hover:text-tan-500">
                        Our Tents
                        <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true" class="opacity-70"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"></path></svg>
                    </a>
                    <div class="absolute left-0 top-full pt-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity z-50">
                        <div class="bg-cream-50 border border-cream-200 min-w-[260px] py-2 shadow-lg">
                            <a href="<?= e($main) ?>/our-tents/stretch-nomadic-bedouin" class="block px-5 py-3 text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">Stretch / Nomadic / Bedouin</a>
                            <a href="<?= e($main) ?>/our-tents/sailcloth-silhouette" class="block px-5 py-3 text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">Sailcloth / Silhouette</a>
                            <a href="<?= e($main) ?>/our-tents/custom-bespoke" class="block px-5 py-3 text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">Custom / Bespoke</a>
                        </div>
                    </div>
                </div>

                <a href="<?= e($main) ?>/gallery" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">Gallery</a>
                <a href="<?= e($main) ?>/faq" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">FAQ</a>
                <a href="<?= e($main) ?>/about" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">About</a>
                <a href="<?= e($main) ?>/contact-get-a-quote" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500">Contact / Get a Quote</a>

                <a href="/" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider text-tan-500" aria-current="page">Blog</a>
                <a href="<?= e($main) ?>/contact-get-a-quote#contact-form" class="btn-primary">Inquire Today</a>
            </nav>

            <button type="button" aria-label="Menu Toggle" class="lg:hidden p-2 text-forest-800" data-menu-toggle>
                <span class="block w-6 h-px bg-forest-800 mb-1.5"></span>
                <span class="block w-6 h-px bg-forest-800 mb-1.5"></span>
                <span class="block w-6 h-px bg-forest-800"></span>
            </button>
        </div>

        <div class="lg:hidden border-t border-cream-200 bg-cream-50 hidden" data-mobile-menu>
            <div class="container-x py-6 flex flex-col gap-2">
                <a href="<?= e($main) ?>/" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Majestic Marquees</a>
                <div class="flex flex-col">
                    <div class="flex items-center justify-between">
                        <a href="<?= e($main) ?>/our-tents" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Our Tents</a>
                        <button type="button" aria-label="Toggle submenu" class="p-2 text-forest-800" data-submenu-toggle>
                            <svg width="12" height="8" viewBox="0 0 10 6" aria-hidden="true" class="transition-transform"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"></path></svg>
                        </button>
                    </div>
                    <div class="pl-4 flex flex-col gap-2 pb-2 hidden" data-submenu>
                        <a href="<?= e($main) ?>/our-tents/stretch-nomadic-bedouin" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-700 py-1">Stretch / Nomadic / Bedouin</a>
                        <a href="<?= e($main) ?>/our-tents/sailcloth-silhouette" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-700 py-1">Sailcloth / Silhouette</a>
                        <a href="<?= e($main) ?>/our-tents/custom-bespoke" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-700 py-1">Custom / Bespoke</a>
                    </div>
                </div>
                <a href="<?= e($main) ?>/gallery" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Gallery</a>
                <a href="<?= e($main) ?>/faq" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">FAQ</a>
                <a href="<?= e($main) ?>/about" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">About</a>
                <a href="<?= e($main) ?>/contact-get-a-quote" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Contact / Get a Quote</a>
                <a href="/" class="text-[16px] leading-[1.5] capitalize tracking-wider text-tan-500 py-2" aria-current="page">Blog</a>
                <a href="<?= e($main) ?>/contact-get-a-quote#contact-form" class="btn-primary w-fit mt-2">Inquire Today</a>
            </div>
        </div>
    </header>

    <main id="content" class="flex-1">
<?= $pageContent ?? '' ?>
    </main>

    <footer class="bg-[#f5f1e8] text-forest-800 pt-20 pb-10 border-t border-black">
        <div class="container-x pb-20 grid gap-12 md:grid-cols-4">
            <div>
                <h2 class="font-display font-semibold text-[18px] uppercase leading-none text-forest-800">MAJESTIC MARQUEES</h2>
                <p class="mt-6 uppercase text-[16px] font-light text-[#2c2c2c]">Superior Tents, Exceptional Service</p>
            </div>
            <div>
                <ul class="space-y-1.5">
                    <li><a href="<?= e($main) ?>/our-tents" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Our Tents</a></li>
                    <li><a href="<?= e($main) ?>/gallery" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Gallery</a></li>
                    <li><a href="<?= e($main) ?>/contact-get-a-quote" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Contact / Get a Quote</a></li>
                </ul>
            </div>
            <div>
                <ul class="space-y-1.5">
                    <li><a href="<?= e($main) ?>/about" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">About</a></li>
                    <li><a href="<?= e($main) ?>/faq" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">FAQ</a></li>
                    <li><a href="/" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Blog</a></li>
                </ul>
                <p class="mt-8 uppercase text-[16px] font-light text-[#2c2c2c]">Speak With Us</p>
                <a href="tel:+6282342464312" class="inline-block link-underline mt-2 text-[16px] font-light text-[#2c2c2c] hover:text-tan-500 transition-colors">+62 823-4246-4312</a>
            </div>
            <div>
                <ul class="space-y-1.5">
                    <li><a href="<?= e($main) ?>/terms-conditions" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Terms and Condition</a></li>
                    <li><a href="<?= e($main) ?>/privacy-policy-2" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Privacy Policy</a></li>
                    <li><a href="<?= e($main) ?>/cookie-policy" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Cookie Policy</a></li>
                    <li><button type="button" data-consent-open class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors text-left">Cookie Settings</button></li>
                </ul>
            </div>
        </div>

        <div class="container-x"><div class="border-t border-forest-800/15"></div></div>

        <div class="container-x pt-14 pb-6 flex items-center justify-center gap-8">
            <a href="https://www.facebook.com/MajesticMarqueesAndTents/" aria-label="Facebook" class="text-forest-800 hover:text-tan-500 transition-colors">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.51 1.49-3.9 3.78-3.9 1.09 0 2.24.2 2.24.2v2.47h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"></path></svg>
            </a>
            <a href="https://www.linkedin.com/company/majesticmarqueesandtents/" aria-label="LinkedIn" class="text-forest-800 hover:text-tan-500 transition-colors">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.62 0 4.29 2.38 4.29 5.48v6.26zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.23 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.46c.98 0 1.77-.77 1.77-1.72V1.72C24 .77 23.21 0 22.23 0z"></path></svg>
            </a>
            <a href="https://www.youtube.com/@MajesticMarqueesAndTents" aria-label="YouTube" class="text-forest-800 hover:text-tan-500 transition-colors">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.6 3.6 12 3.6 12 3.6s-7.6 0-9.4.5A3 3 0 0 0 .5 6.2C0 8 0 12 0 12s0 4 .5 5.8a3 3 0 0 0 2.1 2.1c1.8.5 9.4.5 9.4.5s7.6 0 9.4-.5a3 3 0 0 0 2.1-2.1c.5-1.8.5-5.8.5-5.8s0-4-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"></path></svg>
            </a>
            <a href="https://www.instagram.com/ptmajesticmarqueesandtents" aria-label="Instagram" class="text-forest-800 hover:text-tan-500 transition-colors">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"></circle></svg>
            </a>
            <a href="https://g.page/r/CaQq7Kj2DzyQEAE/review" aria-label="Google Business" class="text-forest-800 hover:text-tan-500 transition-colors">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 9l1.2-3.2A2 2 0 0 1 7.07 4.5h9.86a2 2 0 0 1 1.87 1.3L20 9"></path><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"></path><path d="M4 9h16"></path><path d="M9 14h6"></path><text x="12" y="8.2" font-size="3.4" font-weight="700" text-anchor="middle" fill="currentColor" stroke="none">G</text></svg>
            </a>
        </div>

        <div class="container-x pt-2 pb-2 text-center text-[16px] font-light uppercase text-[#2c2c2c]">
            &copy; <?= date('Y') ?> Majestic Marquees &amp; Tents. Superior Tents, Exceptional Service.
        </div>
    </footer>

</div>

<?php render_cookie_consent(); ?>

<script>
(function () {
    var toggle = document.querySelector('[data-menu-toggle]');
    var menu   = document.querySelector('[data-mobile-menu]');
    if (toggle && menu) {
        toggle.addEventListener('click', function () { menu.classList.toggle('hidden'); });
    }
    var subToggle = document.querySelector('[data-submenu-toggle]');
    var submenu   = document.querySelector('[data-submenu]');
    if (subToggle && submenu) {
        subToggle.addEventListener('click', function () {
            submenu.classList.toggle('hidden');
            var icon = subToggle.querySelector('svg');
            if (icon) icon.classList.toggle('rotate-180');
        });
    }
})();
</script>
</body>
</html>
