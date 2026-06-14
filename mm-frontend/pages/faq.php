<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$faqs = [
    ['q' => 'What services do you provide?', 'a' => 'Marquee and stretch tent hire and sales, bespoke 3D-engineered canopies, plus full setup and styling support across South-East Asia and Oceania.'],
    ['q' => 'Can you assist with event planning?', 'a' => 'Yes, from layout planning to vendor coordination, our team supports your event end to end with calm, professional service.'],
    ['q' => 'What is your booking process?', 'a' => 'Share your event details, we send a tailored proposal, you confirm with a deposit to secure the date, and we handle the rest.'],
    ['q' => 'What are your payment options?', 'a' => 'A deposit secures the date with the balance due closer to your event. Custom payment schedules can be arranged on request.'],
    ['q' => 'What areas do you service?', 'a' => 'Headquartered in Bali, we operate across South-East Asia and Oceania. Reach out about your venue and we will confirm availability.'],
];
?>

<script type="application/json" id="page-meta">
{
    "title": "FAQ — Majestic Marquees & Tents",
    "name": {
        "description": "Answers to frequently asked questions about our marquee and stretch tent services, bookings and coverage areas.",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "FAQ — Majestic Marquees & Tents",
        "og:description": "Answers to frequently asked questions about our marquee and stretch tent services, bookings and coverage areas.",
        "og:type": "website"
    }
}
</script>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[360px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        <img src="/assets/images/faq-hero-bg.jpg" alt="Hero Background" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="absolute inset-0 flex items-center justify-center px-4">
        <div class="text-center max-w-4xl mx-auto">
            <h2 class="text-white/90 text-sm uppercase tracking-widest font-display">Explore Our Expertise</h2>
            <h1 class="heading-xl text-white mt-4">Elevate Your Outdoor Experience</h1>
            <p class="mt-8 text-white/95 text-body max-w-3xl mx-auto">
                Discover answers to your questions and explore how we transform outdoor events into
                extraordinary experiences. Our expertise and commitment to elegance ensure that every
                detail is meticulously crafted, inviting you to imagine the possibilities. Let's
                create something remarkable together.
            </p>
            <div class="mt-10 flex flex-wrap justify-center gap-4">
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary">Get Started</a>
                <a href="/our-tents" class="spa-link btn-outline">Learn More</a>
            </div>
        </div>
    </div>
</section>

<section class="section bg-[#f5f1e8]">
    <div class="container-x">
        <div class="mb-14 lg:mb-16">
            <h2 class="heading-l text-forest-800">Your Questions Answered</h2>
            <p class="mt-4 text-secondary-ttl text-forest-800/90">
                Frequently Asked Questions about Our Services and Offerings
            </p>
        </div>
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
            <div class="bg-[#eee9df] p-8 sm:p-10">
                <h3 class="heading-m">Still have questions for us?</h3>
                <p class="mt-5 text-body text-forest-700/90">
                    We understand that planning your next outdoor event can raise various questions.
                    We have compiled common inquiries from valued clients to help you navigate your
                    choices seamlessly. If you still have questions, please reach out.
                </p>
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary mt-8 inline-block">Get in Touch</a>
            </div>
            <?php render_accordion($faqs, 'lined'); ?>
        </div>
    </div>
</section>

<section class="section bg-[#f5f1e8]">
    <div class="container-x grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div class="relative h-[460px] sm:h-[520px]">
            <img src="/assets/images/faq-collage-1.jpg" alt="Decorated marquee at night" class="absolute left-[24%] top-0 w-[24%] h-[22%] object-cover" loading="lazy">
            <img src="/assets/images/faq-collage-2.jpg" alt="Large elegant outdoor marquee" class="absolute right-0 top-[4%] w-[46%] h-[46%] object-cover" loading="lazy">
            <img src="/assets/images/faq-collage-3.jpg" alt="Evening tent event setup" class="absolute left-0 bottom-0 w-[40%] h-[48%] object-cover" loading="lazy">
            <img src="/assets/images/faq-collage-4.jpg" alt="Small marquee exterior" class="absolute left-[46%] bottom-[2%] w-[24%] h-[18%] object-cover" loading="lazy">
        </div>

        <div>
            <h2 class="heading-xl text-forest-800">Ready to Elevate Your Event Experience?</h2>
            <p class="mt-6 text-forest-700/90 text-body max-w-2xl">
                Let’s discuss how we can create an unforgettable atmosphere tailored to your vision.
                Reach out today for personalized support.
            </p>
            <div class="mt-10">
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary">Get in Touch</a>
            </div>
        </div>
    </div>
</section>
