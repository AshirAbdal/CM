<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageMeta['title'] ?? 'Majestic Marquees & Tents') ?></title>
    <link rel="icon" href="data:,">
    <!-- Staging: force the whole frontend to be non-indexable. -->
    <meta name="robots" content="noindex, nofollow">
    <?php foreach ($pageMeta['name'] ?? [] as $k => $v): if ($k === 'robots') continue; ?>
    <meta name="<?= e($k) ?>" content="<?= e($v) ?>">
    <?php endforeach; ?>
    <?php foreach ($pageMeta['property'] ?? [] as $k => $v): ?>
    <meta property="<?= e($k) ?>" content="<?= e($v) ?>">
    <?php endforeach; ?>
    <?php if (!empty($pageMeta['schema'])): ?>
    <script type="application/ld+json"><?= json_encode($pageMeta['schema']) ?></script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

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
                        display: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans:    ['"Open Sans"', 'system-ui', 'sans-serif']
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

            /* ── Typography system ─────────────────────────────── */
            /* Headings - Playfair Display, responsive (scale up to target px) */
            .heading-xl       { @apply font-display font-semibold leading-[1.2] text-[36px] sm:text-[44px] lg:text-[56px]; }
            .heading-l        { @apply font-display font-semibold leading-[1.2] text-[32px] sm:text-[40px] lg:text-[48px]; }
            .heading-m        { @apply font-display font-semibold leading-[1.2] text-[28px] sm:text-[34px] lg:text-[40px]; }
            .heading-s        { @apply font-display font-medium   text-[18px]; }
            /* Titles */
            .text-primary-ttl   { @apply font-display font-semibold text-[22px] sm:text-[24px]; }
            .text-secondary-ttl { @apply font-display font-semibold text-[20px]; }
            /* Body - Open Sans */
            .text-body        { @apply font-sans font-normal text-[16px] leading-[1.5]; }
            .text-accent      { @apply font-sans font-normal text-[16px]; }
            .text-body-s      { @apply font-sans font-normal text-[14px] leading-[1.5]; }

            .btn { @apply inline-flex items-center justify-center px-6 py-2 text-sm font-medium tracking-wider uppercase transition-colors; }
            .btn-primary { @apply btn bg-tan-500 text-white hover:bg-tan-500 hover:opacity-85 rounded-sm py-3.5; }
            .btn-outline { @apply btn border border-forest-700 text-forest-800 hover:bg-tan-500 hover:border-tan-500 hover:text-white; }
            .section { @apply py-16 sm:py-20 lg:py-24; }
            .eyebrow { @apply text-xs uppercase tracking-[0.2em] text-tan-500 font-sans; }
            .placeholder-img { @apply bg-[#d7c8a5]; }

            /* Animated underline on hover */
            .link-underline { @apply relative; }
            .link-underline::after {
                content: '';
                @apply absolute left-0 -bottom-0.5 h-px w-full bg-current origin-right scale-x-0 transition-transform duration-300 ease-out;
            }
            .link-underline:hover::after { @apply origin-left scale-x-100; }
        }
    </style>
    <script src="/app.js" defer></script>
    <script src="/spa.js" defer></script>
    <!-- Google reCAPTCHA v2. Explicit render so it also works after SPA navigation; see renderRecaptcha() in app.js -->
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadRecaptchaCallback&render=explicit" async defer></script>
</head>
<body>
<div class="flex flex-col">

    <header class="sticky top-0 z-40 bg-[#F5F1E8]/95 backdrop-blur border-b border-black" id="main-nav">
        <div class="container-x flex items-center justify-between h-[90px]">
            <a href="/" class="spa-link flex items-center h-[90px] shrink-0 overflow-visible" aria-label="Home">
                <!-- Change only the h-* below to resize the logo; the header height stays fixed (h-[90px] on the row). -->
                <img src="/logo-original.webp" alt="Majestic Marquees" class="h-[62px] w-auto object-contain">
            </a>

            <nav class="hidden lg:flex items-center gap-8">
                <a href="/" class="spa-link nav-link link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-exact>Majestic Marquees</a>

                <div class="relative group">
                    <a href="/our-tents" class="spa-link nav-link link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors inline-flex items-center gap-1 text-forest-800 hover:text-tan-500" data-nav-base="/our-tents">
                        Our Tents
                        <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true" class="opacity-70"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"></path></svg>
                    </a>
                    <div class="absolute left-0 top-full pt-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity z-50">
                        <div class="bg-cream-50 border border-cream-200 min-w-[260px] py-2 shadow-lg">
                            <a href="/our-tents/stretch-nomadic-bedouin" class="spa-link nav-link block px-5 py-3 text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-exact>Stretch / Nomadic / Bedouin</a>
                            <a href="/our-tents/sailcloth-silhouette" class="spa-link nav-link block px-5 py-3 text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-exact>Sailcloth / Silhouette</a>
                            <a href="/our-tents/custom-bespoke" class="spa-link nav-link block px-5 py-3 text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-exact>Custom / Bespoke</a>
                        </div>
                    </div>
                </div>

                <a href="/gallery" class="spa-link nav-link link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-base="/gallery">Gallery</a>
                <a href="/faq" class="spa-link nav-link link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-base="/faq">FAQ</a>
                <a href="/about" class="spa-link nav-link link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-base="/about">About</a>
                <a href="/contact-get-a-quote" class="spa-link nav-link link-underline text-[16px] leading-[1.5] capitalize tracking-wider transition-colors text-forest-800 hover:text-tan-500" data-nav-base="/contact-get-a-quote">Contact / Get a Quote</a>

                <a href="https://blog.majesticmarquees.clickdigim.com/" class="link-underline text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 hover:text-tan-500">Blog</a>
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary">Inquire Today</a>
            </nav>

            <button type="button" aria-label="Menu Toggle" class="lg:hidden p-2 text-forest-800" data-menu-toggle>
                <span class="block w-6 h-px bg-forest-800 mb-1.5"></span>
                <span class="block w-6 h-px bg-forest-800 mb-1.5"></span>
                <span class="block w-6 h-px bg-forest-800"></span>
            </button>
        </div>

        <div class="lg:hidden border-t border-cream-200 bg-cream-50 hidden" data-mobile-menu>
            <div class="container-x py-6 flex flex-col gap-2">
                <a href="/" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Majestic Marquees</a>
                <div class="flex flex-col">
                    <div class="flex items-center justify-between">
                        <a href="/our-tents" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Our Tents</a>
                        <button type="button" aria-label="Toggle submenu" class="p-2 text-forest-800" data-submenu-toggle>
                            <svg width="12" height="8" viewBox="0 0 10 6" aria-hidden="true" class="transition-transform"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"></path></svg>
                        </button>
                    </div>
                    <div class="pl-4 flex flex-col gap-2 pb-2 hidden" data-submenu>
                        <a href="/our-tents/stretch-nomadic-bedouin" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-700 py-1">Stretch / Nomadic / Bedouin</a>
                        <a href="/our-tents/sailcloth-silhouette" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-700 py-1">Sailcloth / Silhouette</a>
                        <a href="/our-tents/custom-bespoke" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-700 py-1">Custom / Bespoke</a>
                    </div>
                </div>
                <a href="/gallery" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Gallery</a>
                <a href="/faq" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">FAQ</a>
                <a href="/about" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">About</a>
                <a href="/contact-get-a-quote" class="spa-link text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Contact / Get a Quote</a>
                <a href="https://blog.majesticmarquees.clickdigim.com/" class="text-[16px] leading-[1.5] capitalize tracking-wider text-forest-800 py-2">Blog</a>
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary w-fit mt-2">Inquire Today</a>
            </div>
        </div>
    </header>

    <main id="content">
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
                    <li><a href="/our-tents" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Our Tents</a></li>
                    <li><a href="/gallery" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Gallery</a></li>
                    <li><a href="/contact-get-a-quote" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Contact / Get a Quote</a></li>
                </ul>
            </div>
            <div>
                <ul class="space-y-1.5">
                    <li><a href="/about" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">About</a></li>
                    <li><a href="/faq" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">FAQ</a></li>
                    <li><a href="https://blog.majesticmarquees.clickdigim.com/" class="link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Blog</a></li>
                </ul>
                <p class="mt-8 uppercase text-[16px] font-light text-[#2c2c2c]">Speak With Us</p>
                <a href="tel:+6282342464312" class="inline-block link-underline mt-2 text-[16px] font-light text-[#2c2c2c] hover:text-tan-500 transition-colors">+62 823-4246-4312</a>
            </div>
            <div>
                <ul class="space-y-1.5">
                    <li><a href="/terms-conditions" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Terms and Condition</a></li>
                    <li><a href="/privacy-policy-2" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Privacy Policy</a></li>
                    <li><a href="/cookie-policy" class="spa-link link-underline uppercase text-[16px] font-light leading-[1.7] text-[#2c2c2c] hover:text-tan-500 transition-colors">Cookie Policy</a></li>
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
</body>
</html>
