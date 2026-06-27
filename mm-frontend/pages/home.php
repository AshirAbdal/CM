<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$quoteStatus = handle_quote_submit('home');

$cards = [
    ['title' => 'Elegant Marquee Hire (Indonesia)', 'text' => 'Transform your event with exquisite marquees crafted for elegance and comfort. We help you create an enchanting atmosphere tailored to your vision.', 'cta' => 'Coming Soon', 'to' => '#'],
    ['title' => 'Exclusive Sales (SE-Asia / Oceania)', 'text' => 'We import the best quality tents from top manufacturers worldwide for seamless service and short delivery times.', 'cta' => 'Get in Touch', 'to' => '/our-tents'],
    ['title' => 'Superior Service', 'text' => 'From replacement materials to first-time setup training, we strive to give you the best service we can offer.', 'cta' => 'See Our Work', 'to' => '/gallery'],
    ['title' => 'Custom Catering Options (Indonesia)', 'text' => 'Delight your guests with tailored catering that blends elegance and flavor, crafted with fresh, seasonal ingredients.', 'cta' => 'Coming Soon', 'to' => '#'],
    ['title' => 'Exclusive Venue Partnerships (Indonesia)', 'text' => 'We collaborate with stunning venues that embody luxury, ensuring your event is perfectly set in an enchanting environment.', 'cta' => 'Coming Soon', 'to' => '#'],
    ['title' => 'Tailored Event Support', 'text' => 'From start to finish we provide personalized support, ensuring your event is flawless and filled with memorable moments.', 'cta' => 'Contact Us', 'to' => '/contact-get-a-quote'],
];
?>

<!-- Hero -->
<section class="relative bg-black">
    <img src="/assets/images/home-hero-bg.jpg" alt="<?= e(get_image_alt('images/home-hero-bg.jpg', 'Hero Background')) ?>" class="w-full min-h-screen object-cover" loading="lazy">
    <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-8">
        <div class="bg-cream-50 px-6 sm:px-12 py-12 sm:py-16 text-center max-w-2xl w-full shadow-xl">
            <h5 class="font-display text-[18px] text-[#2c2c2c] mb-4">Elevate Your Experience</h5>
            <h1 class="heading-xl">
                Transforming Outdoor Events Into Lasting Memories
            </h1>
            <p class="mt-6 text-forest-700/80 text-body">
                Craft unforgettable moments in nature’s embrace. Our designs harmonize elegance and
                comfort, creating inviting atmospheres that breathe life into every celebration.
                Experience the art of outdoor luxury, where every detail speaks to our commitment to
                quality and craftsmanship. Let us help you add warmth and sophistication to your
                special occasion.
            </p>
            <div class="mt-8">
                <a href="/our-tents" class="spa-link btn-primary">Explore Our Tents</a>
            </div>
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="section bg-[#f5f1e8]">
    <div class="container-x grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div class="relative h-[520px] sm:h-[600px]">
            <img src="/assets/images/home-story-1.jpg" alt="<?= e(get_image_alt('images/home-story-1.jpg', 'Story Image 1')) ?>" class="absolute left-0 bottom-0 w-[55%] aspect-[4/5] object-cover" loading="lazy">
            <img src="/assets/images/home-story-2.jpg" alt="<?= e(get_image_alt('images/home-story-2.jpg', 'Story Image 2')) ?>" class="absolute right-0 top-0 w-[40%] aspect-[2/3] object-cover" loading="lazy">
        </div>
        <div>
            <h1 class="heading-xl">Our Story</h1>
            <p class="mt-8 text-forest-700/80 text-body">
                Majestic Marquees &amp; Tents specializes in creating luxurious outdoor spaces that
                shine with elegance. We partnered with manufacturers that offer the highest
                craftsmanship combined with a deep understanding of nature to offer products that
                transform every event into an extraordinary experience. From intimate weddings to
                grand celebrations, we prioritize a serene and exceptional service, tailoring each
                detail to our clients’ vision.
            </p>
            <p class="mt-4 text-forest-700/80 text-body">
                With a focus on quality and atmosphere, we seamlessly blend nature with sophisticated
                design, ensuring that your event is not just memorable, but truly enchanting.
            </p>
            <blockquote class="group mt-8 border-l-[8px] hover:border-l-[16px] border-tan-500 pl-6 italic text-forest-700 transition-[border-left-width] duration-300 ease-out">
                "Exceptional service, breathtaking environments, this is where luxury and nature
                harmoniously meet. Each occasion is enhanced by the grace and craftsmanship we
                provide, leaving a lasting impression on all who attend.”
                <div class="mt-3 not-italic text-body-s uppercase tracking-wider text-forest-500">Emily Carter</div>
            </blockquote>
            <div class="mt-8">
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary">Get a Quote</a>
            </div>
        </div>
    </div>
</section>

<!-- Feature card grid -->
<section class="relative bg-[#e8e2d4]">
    <img src="/assets/images/home-features-bg.jpg" alt="<?= e(get_image_alt('images/home-features-bg.jpg', 'Field Background')) ?>" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    <div class="relative container-x py-24 sm:py-32 lg:py-40">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-px bg-tan-500/50 max-w-[1280px] mx-auto">
            <?php foreach ($cards as $c): ?>
                <article class="bg-cream-100 px-5 py-5 lg:px-6 lg:py-6 flex flex-col items-center text-center min-h-[220px]">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" class="text-tan-500 mb-5" aria-hidden="true">
                        <path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18.2 22 12 18.3 5.8 22l1.7-7.2L2 10l7.1-1.1L12 2z" />
                    </svg>
                    <h3 class="text-secondary-ttl text-forest-800 leading-snug"><?= e($c['title']) ?></h3>
                    <p class="mt-4 text-body text-forest-700/80 flex-1"><?= e($c['text']) ?></p>
                    <a href="<?= e($c['to']) ?>" class="<?= str_starts_with($c['to'], '/') ? 'spa-link ' : '' ?>btn-outline mt-8 text-sm"><?= e($c['cta']) ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php render_testimonials('What Clients Say', null, 'bg-[#F5F1E8]'); ?>

<!-- Our Tents carousel -->
<section class="section bg-[#d7c8a5] pb-6 sm:pb-8">
    <div class="container-x">
        <div class="text-center mb-12">
            <p class="eyebrow mb-2">Explore Elegance</p>
            <h2 class="heading-l">Our Tents</h2>
        </div>
        <div class="max-w-6xl mx-auto">
            <?php carousel_open(['arrows' => true]); ?>
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <div class="shrink-0 grow-0 basis-full px-2">
                    <img src="/assets/images/home-tent-carousel-<?= $i ?>.jpg" alt="<?= e(get_image_alt('images/home-tent-carousel-' . $i . '.jpg', 'Tent ' . $i)) ?>" class="w-full aspect-[16/10] min-h-[420px] sm:min-h-[520px] lg:min-h-[620px] object-cover" loading="lazy">
                </div>
            <?php endfor; ?>
            <?php carousel_close(); ?>
        </div>
        <div class="text-center mt-10">
            <a href="/our-tents" class="spa-link btn-outline">View All Tents</a>
        </div>
    </div>
</section>

<?php
render_quote_form([
    'id'          => 'tailored-quote',
    'source'      => 'home',
    'variant'     => 'compact',
    'eyebrow'     => '',
    'title'       => 'Request a Tailored Quote Today',
    'subtitle'    => 'Discover how our exquisite outdoor settings can elevate your next event. Let us create an experience where elegance meets nature’s beauty.',
    'submitLabel' => 'Request Quote',
    'status'      => $quoteStatus,
]);
?>
