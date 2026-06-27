<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[360px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        <img src="/assets/images/about-hero-bg.jpg" alt="<?= e(get_image_alt('images/about-hero-bg.jpg', 'Hero Background')) ?>" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-cream-50/60"></div>
    <div class="absolute inset-0 flex items-center justify-center px-4">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="heading-xl text-forest-800">Discover our Elegance</h1>
            <p class="mt-8 text-forest-800/90 text-body max-w-3xl mx-auto">
                Explore the art of luxury outdoor events, where every detail is crafted to perfection.
                Our services bring together nature’s beauty and refined design, creating
                unforgettable experiences that breathe elegance and warmth. Let us help you create a
                setting that reflects your vision and embodies quality.
            </p>
        </div>
    </div>
</section>

<section class="section bg-[#f5f1e8]">
    <div class="container-x grid lg:grid-cols-2 gap-12 items-start">
        <div>
            <h2 class="heading-m">Our Craft, Your Celebration</h2>
            <p class="mt-6 text-forest-700/80 text-body">
                Majestic Marquees &amp; Tents began with a simple vision: to transform outdoor spaces
                into extraordinary experiences. Our commitment to craftsmanship and elegance has
                guided us from our early days to now. Each event we curate reflects the beauty of
                nature intertwined with moments worth celebrating, ensuring that our clients receive
                not only the highest quality products but also the joy of personalized service.
            </p>
        </div>
        <div>
            <h2 class="heading-m">Tailored Outdoor Experiences</h2>
            <p class="mt-6 text-forest-700/80 text-body">
                We believe every event deserves its own unique touch. From the initial design to the
                final installation, our team meticulously crafts an atmosphere that not only reflects
                our clients’ vision but also harmonizes with the natural surroundings. With each
                bespoke marquee we create a space where elegance meets comfort.
            </p>
        </div>
    </div>
</section>

<?php render_testimonials('What Our Clients Say', 'Trusted voices share their experiences with us.'); ?>

<section class="relative section bg-cover bg-center" style="background-image:url('/assets/images/about-cta-bg.webp');">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative container-x grid lg:grid-cols-2 gap-8 items-stretch">
        <div class="bg-[#f5f1e8]/90 border border-forest-800/20 p-8 sm:p-10">
            <div class="flex flex-col items-center text-center">
                <img src="/assets/images/about-founder-portrait.jpg" alt="<?= e(get_image_alt('images/about-founder-portrait.jpg', 'Vincent Klinkenberg')) ?>" class="!w-28 !h-28 rounded-full object-cover" loading="lazy">
                <h2 class="mt-4 heading-m">Vincent Klinkenberg</h2>
            </div>
            <div class="mt-5 space-y-3 text-body text-forest-700/80">
                <p>Hi, I am Vincent, the owner and founder of Majestic Marquees and Tents.</p>
                <p>I created this company after being the Head of Sales and Head of Business developement at QTents for 2,5 years.</p>
                <p>
                    I noticed that to offer <strong class="text-forest-800">Superior Tents</strong> accompanied by
                    <strong class="text-forest-800">Exceptional Service</strong> in SE-Asia and Oceania, the only logical solution was to re-locate from Europe to a central point in SE-Asia.
                </p>
                <p>This is why our current hub is located in Indonesia</p>
                <p>I am looking forward to meet you and, as our team continue to grows, to keep to our promise and deliver you.</p>
                <p class="italic">Superior Tents &amp; Exceptional Service</p>
            </div>
        </div>

        <div class="bg-[#f5f1e8]/90 border border-forest-800/20 p-8 sm:p-10 flex flex-col items-center justify-center text-center">
            <img src="/logo-original.webp" alt="Majestic Marquees & Tents" class="h-28 w-auto object-contain mb-6" loading="lazy">
            <h2 class="heading-m font-display">Let’s Create Your Perfect Outdoor Event</h2>
            <p class="mt-5 text-body text-forest-700/80 max-w-md">
                Connect with us to explore how our elegant solutions can elevate your gathering into a serene experience.
            </p>
            <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary mt-6">Get in Touch</a>
        </div>
    </div>
</section>
