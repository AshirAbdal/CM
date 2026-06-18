<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
?>
<script type="application/json" id="page-meta">
{
    "title": "Custom & Bespoke Tents - Majestic Marquees & Tents",
    "name": {
        "description": "Made-to-measure 3D engineered canopies and bespoke tents designed exactly to your location's measurements.",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "Custom & Bespoke Tents - Majestic Marquees & Tents",
        "og:description": "Made-to-measure 3D engineered canopies and bespoke tents designed exactly to your location's measurements.",
        "og:type": "website"
    }
}
</script>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[360px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        <img src="/assets/images/custom-hero-bg.jpg" alt="Hero Background" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center">
        <div class="container-x">
            <div class="max-w-2xl text-white">
                <h1 class="heading-xl text-white">A Truly unique experience</h1>
                <p class="mt-3 italic text-secondary-ttl text-white/90">
                    Our made to measure tents are exceptional in every detail
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Intro -->
<section class="section">
    <div class="container-x text-center max-w-4xl mx-auto">
        <h2 class="heading-l">Custom - Bespoke - Made to Measure</h2>
        <p class="mt-6 text-forest-700/85 leading-relaxed">
            The Canopy will be custom designed for you and made exactly to the 3D measurements of
            your location.
        </p>
    </div>
</section>

<!-- Image gallery carousel -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x">
        <?php carousel_open(); ?>
        <?php for ($i = 1; $i <= 11; $i++): ?>
            <div class="shrink-0 grow-0 basis-full sm:basis-1/2 lg:basis-1/3 px-2">
                <img src="/assets/images/custom-carousel-<?= $i ?>.jpg" alt="Bespoke <?= $i ?>" class="w-full aspect-[4/3] object-cover" loading="lazy">
            </div>
        <?php endfor; ?>
        <?php carousel_close(); ?>
    </div>
</section>

<!-- 3D Architecture + Quote Card -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
        <div>
            <h2 class="heading-m">3D Architecture</h2>
            <p class="mt-6 text-forest-700/85 leading-relaxed">
                From Carports to exclusively engineered canopies for hospitality or municipal buildings.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                We create a completely 3D engineered canopy made to the exact measurements of the location.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                To create this 3D architecture we can make use of several different cloth types to
                serve the need of the specific location.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                Get in touch or book a discovery call to get more information regarding this product.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                We exclusively use high-quality roofs that are manufactured by QTents (
                <a href="https://www.qtents.com" class="text-tan-500 underline" target="_blank" rel="noopener noreferrer">www.qtents.com</a>
                ), a top-level manufacturer renowned for their expertise and innovation, hailing from
                the Netherlands.
            </p>
        </div>

        <div id="contact-form" class="relative overflow-hidden border border-forest-800/15">
            <img src="/assets/images/custom-quote-bg.jpg" alt="" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
            <div class="absolute inset-0 bg-forest-800/55" aria-hidden="true"></div>

            <div class="relative p-8 sm:p-12">
                <h2 class="heading-l text-cream-50 font-display">Request Your<br>Personalized Quote</h2>
                <p class="mt-6 text-cream-50/90 text-body max-w-md">
                    If you’re excited to learn more about our innovative 3D architecture, we can’t wait
                    to connect with you! Fill out the form below, and let’s embark on this inspiring
                    journey together.
                </p>

                <form class="mt-8 space-y-4 max-w-md" onsubmit="return false;">
                    <label class="block">
                        <span class="sr-only">Name</span>
                        <input type="text" name="name" placeholder="Name*" required
                               class="w-full bg-cream-50 border-0 rounded-md px-5 py-2.5 text-sm text-forest-800 placeholder:text-forest-700/50 focus:outline-none focus:ring-2 focus:ring-tan-500">
                    </label>
                    <label class="block">
                        <span class="sr-only">Email</span>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-forest-700/50" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 7l9 6 9-6"></path></svg>
                            </span>
                            <input type="email" name="email" placeholder="Your best email address*" required
                                   class="w-full bg-cream-50 border-0 rounded-md pl-10 pr-5 py-2.5 text-sm text-forest-800 placeholder:text-forest-700/50 focus:outline-none focus:ring-2 focus:ring-tan-500">
                        </div>
                    </label>
                    <label class="flex items-start gap-3 pt-1">
                        <input type="checkbox" name="agree" required class="mt-0.5 w-4 h-4 accent-tan-500">
                        <span class="text-cream-50/90 text-xs sm:text-sm">By submitting this form, you agree to our Terms and Conditions.</span>
                    </label>
                    <div class="pt-3">
                        <button type="submit" class="btn-primary px-10">Send Inquiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
