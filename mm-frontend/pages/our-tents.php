<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$quoteStatus = handle_quote_submit('our-tents');

$tentCards = [
    ['name' => 'Stretch / Nomadic / Bedouin', 'to' => '/our-tents/stretch-nomadic-bedouin', 'slug' => 'our-tents-stretch-card', 'aspect' => 'aspect-[3/2]'],
    ['name' => 'Sailcloth / Silhouette',       'to' => '/our-tents/sailcloth-silhouette',    'slug' => 'our-tents-sailcloth-card', 'aspect' => 'aspect-[3/2]'],
    ['name' => 'Custom / Made to Measure',     'to' => '/our-tents/custom-bespoke',          'slug' => 'our-tents-custom-card', 'aspect' => 'aspect-square'],
];

$steps = [
    ['n' => '01', 'img' => 'our-tents-step-1', 'title' => 'Timeless Design', 'text' => 'Our tents are crafted to provide an unforgettable ambiance that seamlessly complements every event theme.'],
    ['n' => '02', 'img' => 'our-tents-step-2', 'title' => 'Premium Materials', 'text' => 'Sustainable and luxurious fabrics ensure durability and style, creating a serene space for your gatherings.'],
    ['n' => '03', 'img' => 'our-tents-step-3', 'title' => 'Exceptional Service', 'text' => 'Our calm, professional team is dedicated to seamless execution so you can focus on what matters.'],
];

$faqs = [
    ['q' => 'What sizes of tents do you offer?', 'a' => 'From 4.5 m stretch units to 20 m sailcloth structures with double rows of king poles, plus fully bespoke designs.'],
    ['q' => 'Can you customize the setup?', 'a' => 'Yes, every event is tailored. We adapt configuration, accessories and styling to your venue and guest count.'],
    ['q' => 'What is included in the rental?', 'a' => 'Setup, takedown and the structure itself. Lighting, walls and flooring are available as add-ons.'],
    ['q' => 'Is there a weather guarantee?', 'a' => 'Our tents are engineered for variable weather. We pre-assess conditions and recommend appropriate configurations.'],
    ['q' => 'How far in advance should I book?', 'a' => 'For peak season we recommend 3–6 months. Smaller events can often be accommodated on shorter timelines.'],
];
?>

<!-- Hero -->
<section class="relative">
    <img src="/assets/images/our-tents-hero-bg.jpg" alt="<?= e(get_image_alt('images/our-tents-hero-bg.jpg', 'Hero Background')) ?>" class="w-full h-[320px] sm:h-[360px] lg:h-[400px] object-cover" loading="lazy">
    <div class="absolute inset-0 bg-black/45"></div>
    <div class="absolute inset-0 z-10 flex items-center justify-center">
        <div class="container-x text-center text-white">
            <h1 class="heading-xl text-white max-w-5xl mx-auto drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)]">
                Elevate Your Event Experience
            </h1>
            <p class="font-display italic text-secondary-ttl mt-5">Immerse yourself in the luxury of nature’s serene beauty.</p>
        </div>
    </div>
</section>

<section class="section pb-4">
    <div class="container-x text-center max-w-4xl mx-auto">
        <h2 class="heading-l">Explore Our Tent Styles</h2>
        <p class="mt-4 text-secondary-ttl text-forest-700/80 italic">Discover elegant tents designed for unforgettable moments.</p>
    </div>
</section>

<section class="section pt-8 pb-16">
    <div class="container-x">
        <?php carousel_open(); ?>
        <?php for ($i = 1; $i <= 11; $i++): ?>
            <div class="shrink-0 grow-0 basis-full sm:basis-1/2 lg:basis-1/3 px-2">
                <img src="/assets/images/our-tents-carousel-<?= $i ?>.jpg" alt="<?= e(get_image_alt('images/our-tents-carousel-' . $i . '.jpg', 'Tent showcase ' . $i)) ?>" class="w-full aspect-[4/3] object-cover" loading="lazy">
            </div>
        <?php endfor; ?>
        <?php carousel_close(); ?>
    </div>
</section>

<section class="section bg-cream-50">
    <div class="container-x grid lg:grid-cols-2 gap-12 items-start">
        <div>
            <h2 class="heading-m">Tent Styles</h2>
            <p class="mt-6 text-forest-700/80 text-body">
                We currently offer three distinct types of tents that cater to a variety of needs
                and preferences. Click on a tent below to explore detailed information.
            </p>
            <ul class="mt-6 space-y-4 text-forest-700/80 text-body">
                <li>
                    <a href="/our-tents/stretch-nomadic-bedouin" class="spa-link text-tan-500 hover:text-tan-600 font-medium">Stretch / Nomadic / Bedouin</a>
                    <span>A playful option that combines traditional craftsmanship with modern design for an unforgettable experience.</span>
                </li>
                <li>
                    <a href="/our-tents/sailcloth-silhouette" class="spa-link text-tan-500 hover:text-tan-600 font-medium">Sailcloth / Silhouette</a>
                    <span>A unique and elegant choice designed to bring sophistication and style to any outdoor event.</span>
                </li>
                <li>
                    <a href="/our-tents/custom-bespoke" class="spa-link text-tan-500 hover:text-tan-600 font-medium">Custom / Bespoke / Made to Measure</a>
                    <span>We ensure that we can meet your specific requirements and vision for your event, venue or end-user.</span>
                </li>
            </ul>
            <p class="mt-8 text-body-s text-forest-500">
                For all tents we exclusively use high-quality roofs manufactured by
                <a href="https://www.qtents.com" target="_blank" rel="noopener noreferrer" class="text-tan-500 hover:text-tan-600">QTents</a>
                a top-level Netherlands-based manufacturer renowned for expertise and innovation.
            </p>
            <div class="mt-8 flex justify-end">
                <img src="/assets/images/stretch-qtents-logo.webp" alt="<?= e(get_image_alt('images/stretch-qtents-logo.webp', 'QTents')) ?>" class="h-[180px] w-auto object-contain" loading="lazy">
            </div>
        </div>

        <div class="grid gap-10">
            <?php foreach ($tentCards as $t): ?>
                <div class="text-center border-[1.5px] border-[#333333] p-[10px]">
                    <h3 class="heading-m text-forest-800 mb-5"><?= e($t['name']) ?></h3>
                    <a href="<?= e($t['to']) ?>" class="spa-link group block overflow-hidden">
                        <div class="<?= e($t['aspect']) ?> overflow-hidden">
                            <img src="/assets/images/<?= e($t['slug']) ?>.jpg" alt="<?= e(get_image_alt('images/' . $t['slug'] . '.jpg', $t['name'])) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section bg-cover bg-center" style="background-image:url('/assets/images/our-tents-discover-bg.webp');">
    <div class="container-x">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h5 class="font-display text-[18px] text-[#2c2c2c] mb-2">Elevate Your Experience</h5>
            <h2 class="heading-l">Discover the Art of Exquisite Tents for Your Event</h2>
            <p class="mt-4 text-forest-700/80 text-body">
                Our tents blend natural elegance with top-tier craftsmanship. Experience the warmth
                of a well-curated atmosphere created by distinguished designs, perfect for any
                high-end occasion.
            </p>
        </div>
        <div class="grid gap-8 md:grid-cols-3">
            <?php foreach ($steps as $s): ?>
                <div class="relative bg-cover bg-center min-h-[440px] flex items-end" style="background-image:url('/assets/images/<?= e($s['img']) ?>.webp');">
                    <div class="m-4 p-8 w-full bg-[#f7f7f7]/85">
                        <div class="font-display text-5xl text-forest-800"><?= e($s['n']) ?></div>
                        <h3 class="mt-4 text-primary-ttl"><?= e($s['title']) ?></h3>
                        <p class="mt-3 text-forest-700/80 text-body"><?= e($s['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php render_testimonials('What Our Clients Say', 'See why clients trust us for their most memorable events.'); ?>

<?php
render_quote_form([
    'id'          => 'contact-form',
    'source'      => 'our-tents',
    'variant'     => 'bgImage',
    'eyebrow'     => '',
    'title'       => 'Request Your Personalized Quote',
    'subtitle'    => "We would love to learn more about your upcoming event. Fill out the form below, and let's start the conversation together.",
    'submitLabel' => 'Request Quote',
    'bgImage'     => '/assets/images/stretch-quote-bg.webp',
    'status'      => $quoteStatus,
]);
?>

<section class="section bg-[#f5f1e8]">
    <div class="container-x">
        <div class="mb-14 lg:mb-16">
            <h2 class="heading-l text-forest-800">Common Questions</h2>
            <p class="mt-4 text-secondary-ttl text-forest-800/90">
                Your queries about tent rentals answered here.
            </p>
        </div>
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
            <div class="bg-[#eee9df] p-8 sm:p-10">
                <h3 class="heading-m">Need further assistance?</h3>
                <p class="mt-5 text-body text-forest-700/90">
                    We understand that planning an event comes with many questions. Whether it's about
                    our tents, setup services, or special requests, we're here to help. Reach out with
                    any questions you might have, and we'll ensure you feel confident in your choices.
                </p>
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary mt-8 inline-block">Get in Touch</a>
            </div>
            <?php render_accordion($faqs, 'lined'); ?>
        </div>
    </div>
</section>
