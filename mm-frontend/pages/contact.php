<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$contactStatus = handle_contact_submit();
$contactSuccess = $contactStatus && $contactStatus['ok'];
$contactError   = $contactStatus && !$contactStatus['ok'] ? $contactStatus['message'] : '';
?>

<script type="application/json" id="page-meta">
{
    "title": "Contact & Get a Quote — Majestic Marquees & Tents",
    "name": {
        "description": "Get in touch with Majestic Marquees & Tents to discuss your vision and request a personalized quote for your outdoor event.",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "Contact & Get a Quote — Majestic Marquees & Tents",
        "og:description": "Get in touch with Majestic Marquees & Tents to discuss your vision and request a personalized quote for your outdoor event.",
        "og:type": "website"
    },
    "schema": {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "Majestic Marquees & Tents",
        "url": "https://majesticmarquees.com",
        "email": "Hello@MajesticMarquees.com",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Jalan Raya Kuta 32, Desa/Kelurahan Kuta, Kec. Kuta",
            "addressRegion": "Bali",
            "postalCode": "80361",
            "addressCountry": "ID"
        }
    }
}
</script>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[360px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        <img src="/assets/images/contact-hero-bg.jpg" alt="Hero Background" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center px-4">
        <div class="text-center max-w-4xl mx-auto text-cream-50">
            <p class="font-display italic text-secondary-ttl mb-6">Experience Elevated Luxury</p>
            <h1 class="heading-xl text-cream-50">Indulge in Exceptional Outdoor Celebrations</h1>
            <p class="mt-8 text-cream-50/90 text-body max-w-2xl mx-auto">
                Transform your events into extraordinary experiences. Our bespoke outdoor setups blend
                craftsmanship with nature’s beauty, creating unforgettable moments under the stars.
            </p>
            <div class="mt-10 flex flex-wrap justify-center gap-4">
                <a href="/our-tents" class="spa-link btn-primary">Discover More</a>
                <a href="#contact-form" class="btn border border-cream-50 text-cream-50 hover:bg-cream-50 hover:text-forest-800 rounded-sm">Get a Quote</a>
            </div>
        </div>
    </div>
</section>

<section class="section bg-[#f5f1e8]">
    <div class="container-x grid lg:grid-cols-2 gap-12 items-start">
        <div>
            <p class="eyebrow mb-2">Connect With Us</p>
            <h2 class="heading-l">Get in Touch</h2>
            <p class="mt-6 text-forest-700/80 text-body">
                We’re here to assist you in creating unforgettable moments. Reach out to discuss
                your vision, and let’s explore how we can bring it to life together.
            </p>
            <ul class="mt-8 space-y-4 text-forest-700">
                <li>
                    <a href="https://api.leadconnectorhq.com/widget/booking/zDy1DyutNxQSN011ks7g" class="hover:text-tan-500">📅 Book a 30 minute discovery meeting</a>
                </li>
                <li>
                    <a href="mailto:Hello@MajesticMarquees.com" class="hover:text-tan-500">✉️ Hello@MajesticMarquees.com</a>
                </li>
                <li>
                    <span>📍 Jalan Raya Kuta 32, Desa/Kelurahan Kuta, Kec. Kuta, Kab. Badung, Provinsi Bali 80361</span>
                </li>
                <li>
                    <a href="https://wa.me/6282342464312" class="hover:text-tan-500">💬 Message us on WhatsApp</a>
                </li>
            </ul>
            <div class="mt-8 flex flex-wrap gap-5 items-center">
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
                <a href="https://g.page/r/CaQq7Kj2DzyQEAE/review" aria-label="Google Reviews" class="text-forest-800 hover:text-tan-500 transition-colors">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 9l1.2-3.2A2 2 0 0 1 7.07 4.5h9.86a2 2 0 0 1 1.87 1.3L20 9"></path><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"></path><path d="M4 9h16"></path><path d="M9 14h6"></path><text x="12" y="8.2" font-size="3.4" font-weight="700" text-anchor="middle" fill="currentColor" stroke="none">G</text></svg>
                </a>
            </div>
        </div>
        <div class="w-full h-[400px] overflow-hidden border border-forest-800/15">
            <iframe
                src="https://maps.google.com/maps?ll=-8.719086,115.17545&amp;q=Jalan+Raya+Kuta+32,+Desa/Kelurahan+Kuta,+Kec.+Kuta,+Kab.+Badung,+Provinsi+Bali+80361&amp;z=10&amp;output=embed"
                class="w-full h-full"
                style="border:0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Majestic Marquees &amp; Tents location map"
                allowfullscreen></iframe>
        </div>
    </div>
</section>

<section id="contact-form" class="section bg-[#f5f1e8]">
    <div class="container-x max-w-xl mx-auto">
        <div class="border border-forest-800/30 bg-[#f5f1e8] px-14 sm:px-24 py-20 sm:py-28">
            <h2 class="heading-m text-center font-display">Get in Touch</h2>
            <?php if ($contactSuccess): ?>
                <p class="mt-10 text-center text-forest-800 font-medium">Thank you! We'll be in touch soon.</p>
            <?php else: ?>
                <form class="mt-10 space-y-5" method="POST" action="/contact-get-a-quote#contact-form">
                    <?= csrf_field() ?>
                    <label class="block">
                        <span class="text-sm font-medium text-forest-800">Your Name <span class="text-tan-500">*</span></span>
                        <input type="text" name="name" required
                               class="mt-2 w-full bg-[#f5f1e8] border border-forest-800/30 rounded-md px-4 py-2.5 text-sm text-forest-800 focus:outline-none focus:border-tan-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-forest-800">Your Email <span class="text-tan-500">*</span></span>
                        <div class="relative mt-2">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-forest-700/50" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 7l9 6 9-6"></path></svg>
                            </span>
                            <input type="email" name="email" required
                                   class="w-full bg-[#f5f1e8] border border-forest-800/30 rounded-md pl-10 pr-4 py-2.5 text-sm text-forest-800 focus:outline-none focus:border-tan-500">
                        </div>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-forest-800">Your Message <span class="text-tan-500">*</span></span>
                        <textarea name="message" required rows="5"
                                  class="mt-2 w-full bg-[#f5f1e8] border border-forest-800/30 rounded-md px-4 py-2.5 text-sm text-forest-800 focus:outline-none focus:border-tan-500 resize-none"></textarea>
                    </label>

                    <?php if ($contactError !== ''): ?><p class="text-red-600 text-sm"><?= e($contactError) ?></p><?php endif; ?>

                    <button type="submit" class="btn-primary w-full disabled:opacity-60">Send Inquiry</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php render_testimonials('What Our Clients Say', 'Our clients appreciate our approach, discover their thoughts!'); ?>

<!-- Let's Bring Your Vision to Life -->
<section class="section bg-[#f5f1e8]">
    <div class="container-x grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div class="relative h-[520px] sm:h-[600px]">
            <img src="/assets/images/contact-vision-1.jpg" alt="Event Setup" class="absolute left-[15%] top-0 w-[42%] aspect-[4/3] object-cover" loading="lazy">
            <img src="/assets/images/contact-vision-2.jpg" alt="Marquee Detail" class="absolute right-0 top-[12%] w-[55%] aspect-[4/3] object-cover" loading="lazy">
            <img src="/assets/images/contact-vision-3.jpg" alt="Outdoor Event" class="absolute left-0 bottom-0 w-[48%] aspect-[1/1] object-cover" loading="lazy">
            <img src="/assets/images/contact-vision-4.jpg" alt="Wedding Tent" class="absolute right-[10%] bottom-[5%] w-[35%] aspect-[4/3] object-cover" loading="lazy">
        </div>

        <div>
            <h2 class="heading-xl font-display">Let’s Bring Your Vision to Life</h2>
            <p class="mt-8 text-forest-700/80 text-body max-w-prose">
                Now is the perfect moment to connect with us and discover how we make your event
                unforgettable.
            </p>
            <div class="mt-10">
                <a href="#contact-form" class="btn-primary">Get in Touch</a>
            </div>
        </div>
    </div>
</section>
