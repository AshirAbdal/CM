<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$steps = [
    ['title' => 'Step 1: Consultation', 'text' => 'We start with a personal consultation to understand your vision. Your unique ideas and preferences shape the planning process, ensuring every detail reflects your style.'],
    ['title' => 'Step 2: Design & Planning', 'text' => "Next, our experts craft a tailored design that harmonizes with your event's surroundings. From layout to decor, every element is considered for a flawless atmosphere."],
    ['title' => 'Step 3: Execution', 'text' => 'Finally, our dedicated team brings your vision to life, overseeing all aspects of setup and execution. You can relax and enjoy your event, knowing we’re there to ensure everything runs smoothly.'],
];

$faqs = [
    ['q' => 'What services do you provide?', 'a' => 'We specialize in luxury outdoor marquee and stretch tent hire and sales, bespoke 3D-engineered canopies, and full styling support across South-East Asia and Oceania.'],
    ['q' => 'How can I request a quote?', 'a' => 'Share your event details through our contact form and our team will respond with a tailored proposal within 1–2 business days.'],
    ['q' => 'Do you offer customized services?', 'a' => 'Yes — every event is unique. We offer bespoke design, custom canopies, and tailored styling to match your vision.'],
    ['q' => 'What locations do you serve?', 'a' => 'We are headquartered in Bali and operate across South-East Asia and Oceania. Reach out and we will confirm availability for your venue.'],
    ['q' => 'What is your cancellation policy?', 'a' => 'Cancellation terms depend on event size and timing. Full details are shared in your booking agreement — please contact us for specifics.'],
];
?>

<script type="application/json" id="page-meta">
{
    "title": "Gallery — Majestic Marquees & Tents",
    "name": {
        "description": "Explore our event showcase — exquisite outdoor setups that transform any celebration into a breathtaking experience.",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "Gallery — Majestic Marquees & Tents",
        "og:description": "Explore our event showcase — exquisite outdoor setups that transform any celebration into a breathtaking experience.",
        "og:type": "website"
    }
}
</script>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[420px] sm:h-[480px] lg:h-[560px] overflow-hidden">
        <img src="/assets/images/gallery-hero-bg.jpg" alt="Hero Background" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-black/30"></div>
    <div class="absolute inset-0 flex items-center">
        <div class="container-x">
            <div class="max-w-2xl text-white">
                <p class="font-display italic text-secondary-ttl">Crafting Unforgettable Moments</p>
                <h1 class="mt-3 heading-xl text-white">Elegance Meets Nature’s Beauty</h1>
                <p class="mt-6 text-white/95 text-body max-w-xl">
                    Discover our exquisite outdoor setups that transform any celebration into a
                    breathtaking experience. Allow nature’s charm to enhance your special occasion.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#event-showcase" class="btn-primary">View Gallery</a>
                    <a href="/contact-get-a-quote#contact-form" class="spa-link btn border border-white text-white hover:bg-white hover:text-forest-800 rounded-sm">Get in Touch</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Event Showcase -->
<section id="event-showcase" class="section bg-[#f5f1e8]">
    <div class="container-x">
        <div class="text-center mb-12">
            <p class="eyebrow mb-2">Moments in Nature</p>
            <h2 class="heading-xl text-forest-800">Event Showcase</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-8">
            <?php for ($n = 1; $n <= 8; $n++): ?>
                <img src="/assets/images/gallery-showcase-<?= $n ?>.jpg" alt="Image <?= $n ?>" class="aspect-[4/5] w-full object-cover" loading="lazy">
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php render_testimonials('What Our Clients Say', 'See why clients trust us for their most memorable events.'); ?>

<!-- Let's Create Your Perfect Event Together -->
<section class="bg-[#d7c7a5] pt-0 pb-20 sm:pb-28">
    <div class="container-x text-center">
        <h2 class="heading-l text-forest-800">Let’s Create Your Perfect Event Together</h2>
        <div class="mt-8 mb-12">
            <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary">Get In Touch</a>
        </div>
    </div>
</section>

<!-- 3-step process -->
<section class="section bg-[#f5f1e8]">
    <div class="container-x grid lg:grid-cols-2 gap-12 lg:gap-16 items-start mt-12 mb-20">
        <div class="hidden lg:block">
            <img src="/assets/images/gallery-process-image.jpg" alt="Image" class="w-full aspect-[4/3.3] object-cover" loading="lazy">
        </div>
        <ol class="space-y-12">
            <?php foreach ($steps as $s): ?>
                <li class="flex gap-5">
                    <span class="text-tan-500 shrink-0 mt-1" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18.2 22 12 18l-6.2 4 1.7-7.2L2 10l7.1-1.1L12 2z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="heading-m text-forest-800"><?= e($s['title']) ?></h3>
                        <p class="mt-3 text-forest-700/90 text-body max-w-prose"><?= e($s['text']) ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- FAQ -->
<section class="section bg-[#f5f1e8] pt-0">
    <div class="container-x">
        <div class="mb-10">
            <h2 class="heading-l text-forest-800">Your Questions Answered</h2>
            <p class="mt-4 text-secondary-ttl text-forest-800/80">
                Explore our insights and clarifications about our services.
            </p>
        </div>
        <div class="border border-forest-800/40 p-6 sm:p-10">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-14 items-center">
                <img src="/assets/images/gallery-faq-image.jpg" alt="Image" class="w-full aspect-[4/5] sm:aspect-[3/4] lg:aspect-[3/3] object-cover" loading="lazy">
                <?php render_accordion($faqs, 'lined'); ?>
            </div>
        </div>
    </div>
</section>
