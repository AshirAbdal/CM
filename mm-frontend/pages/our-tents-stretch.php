<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$quoteStatus = handle_quote_submit('stretch');

$canvasInfo = [
    'Anti fungus and algae treatment',
    'UV resistance 9',
    'Wind resistance force 9 Beaufort',
    'Full colour print on both sides possible',
    'Fire retardant B1, M2 – NF P 92-503',
    'Canvas look for authentic character',
    'Weight: 750 g/m²',
    'Colours: Sand / Platinum Grey / Taupe / Black / Red / White',
];

$colors = [
    ['name' => 'Sand', 'hex' => '#e3d6bf'],
    ['name' => 'Platinum Grey', 'hex' => '#c9c9c9'],
    ['name' => 'Taupe', 'hex' => '#a89a85'],
    ['name' => 'Black', 'hex' => '#1a1a1a'],
    ['name' => 'Red', 'hex' => '#9b2c2c'],
    ['name' => 'White', 'hex' => '#f6f3ec'],
];

$whyQtents = [
    ['title' => 'Our Fabrics', 'icon' => 'stretch-why-fabrics', 'text' => 'The tent canvas we use comes from a premium technical textile manufacturer in Europe. Guaranteeing you the best possible quality.'],
    ['title' => '100% Waterproof', 'icon' => 'stretch-why-waterproof', 'text' => 'Due to the special coating and the lack of stitching and seams, our stretch tents are guaranteed to remain waterproof. Interim treatment is not necessary.'],
    ['title' => 'Fire Safe', 'icon' => 'stretch-why-firesafe', 'text' => 'All materials meet the required fire certificate B1 / M2. All our tents comply with NEN-EN 8020-41:2012.'],
    ['title' => 'UV Resistant', 'icon' => 'stretch-why-uv', 'text' => 'A special top coating prevents the tent canvas from discolouring or deteriorating, keeping every Qtent beautiful year after year.'],
    ['title' => 'Algae & Mold Proof', 'icon' => 'stretch-why-algae', 'text' => 'The canvas is impregnated inside and outside with a coating algae and mold cannot adhere to.'],
    ['title' => 'Printing Possible', 'icon' => 'stretch-why-printing', 'text' => 'Canvas can be printed in full colour on both sides, ideal for maximum exposure or corporate identity.'],
    ['title' => 'Easy Setup', 'icon' => 'stretch-why-instructions', 'text' => 'Designed to be set up easily, with clear instructions. Municipal permitting books can be provided on request.'],
];

$sizeGroups = [
    ['label' => '4.5 / 6 meter', 'variants' => [
        ['label' => '4.5x4.5', 'images' => ['/assets/images/stretch-config-4-5x4-5.jpg'], 'size' => '4.5×4.5 meter', 'seated' => '8 persons', 'cocktail' => '14 Persons', 'cinema' => '20 Persons', 'surface' => '20.25 m²', 'coating' => 'single coated', 'weight' => '15kg', 'packed' => '50x50x100cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '4.5x7.5', 'images' => ['/assets/images/stretch-config-4-5x7-5.jpg'], 'size' => '4.5×7.5 meter', 'seated' => '14 persons', 'cocktail' => '24 Persons', 'cinema' => '34 Persons', 'surface' => '33.75 m²', 'coating' => 'single coated', 'weight' => '22kg', 'packed' => '50x50x120cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '6x6', 'images' => ['/assets/images/stretch-config-6x6.jpg'], 'size' => '6×6 meter', 'seated' => '16 persons', 'cocktail' => '28 Persons', 'cinema' => '40 Persons', 'surface' => '36 m²', 'coating' => 'single coated', 'weight' => '26kg', 'packed' => '55x55x120cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '6x10.5', 'images' => ['/assets/images/stretch-config-6x10-5.jpg'], 'size' => '6×10.5 meter', 'seated' => '28 persons', 'cocktail' => '48 Persons', 'cinema' => '68 Persons', 'surface' => '63 m²', 'coating' => 'single coated', 'weight' => '40kg', 'packed' => '60x60x130cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '6x15', 'images' => ['/assets/images/stretch-config-6x15.jpg'], 'size' => '6×15 meter', 'seated' => '40 persons', 'cocktail' => '70 Persons', 'cinema' => '98 Persons', 'surface' => '90 m²', 'coating' => 'single coated', 'weight' => '55kg', 'packed' => '60x60x150cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
    ]],
    ['label' => '7.5 / 9 meter', 'variants' => [
        ['label' => '7.5x7.5', 'images' => ['/assets/images/stretch-config-7-5x7-5.jpg'], 'size' => '7.5×7.5 meter', 'seated' => '32 persons', 'cocktail' => '56 Persons', 'cinema' => '80 Persons', 'surface' => '56.25 m²', 'coating' => 'double coated', 'weight' => '35kg', 'packed' => '60x60x130cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '7.5x10.5', 'images' => ['/assets/images/stretch-config-7-5x10-5.jpg'], 'size' => '7.5×10.5 meter', 'seated' => '45 persons', 'cocktail' => '78 Persons', 'cinema' => '110 Persons', 'surface' => '78.75 m²', 'coating' => 'double coated', 'weight' => '48kg', 'packed' => '65x65x140cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '9x9', 'images' => ['/assets/images/stretch-config-9x9.jpg'], 'size' => '9×9 meter', 'seated' => '50 persons', 'cocktail' => '90 Persons', 'cinema' => '128 Persons', 'surface' => '81 m²', 'coating' => 'double coated', 'weight' => '52kg', 'packed' => '65x65x150cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '9x15', 'images' => ['/assets/images/stretch-config-9x15.jpg'], 'size' => '9×15 meter', 'seated' => '85 persons', 'cocktail' => '148 Persons', 'cinema' => '210 Persons', 'surface' => '135 m²', 'coating' => 'double coated', 'weight' => '80kg', 'packed' => '70x70x160cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
    ]],
    ['label' => '10.5 / 12 meter', 'variants' => [
        ['label' => '10.5x10.5', 'images' => ['/assets/images/stretch-config-10-5x10-5.jpg'], 'size' => '10.5×10.5 meter', 'seated' => '64 persons', 'cocktail' => '110 Persons', 'cinema' => '156 Persons', 'surface' => '110.25 m²', 'coating' => 'double coated', 'weight' => '70kg', 'packed' => '70x70x160cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '12x12', 'images' => ['/assets/images/stretch-config-12x12.jpg'], 'size' => '12×12 meter', 'seated' => '90 persons', 'cocktail' => '155 Persons', 'cinema' => '220 Persons', 'surface' => '144 m²', 'coating' => 'double coated', 'weight' => '95kg', 'packed' => '75x75x170cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '12x18', 'images' => ['/assets/images/stretch-config-12x18.jpg'], 'size' => '12×18 meter', 'seated' => '140 persons', 'cocktail' => '240 Persons', 'cinema' => '340 Persons', 'surface' => '216 m²', 'coating' => 'double coated', 'weight' => '140kg', 'packed' => '80x80x180cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
    ]],
    ['label' => '15 / 18 Meter', 'variants' => [
        ['label' => '15x15', 'images' => ['/assets/images/stretch-config-15x15.jpg'], 'size' => '15×15 meter', 'seated' => '130 persons', 'cocktail' => '225 Persons', 'cinema' => '320 Persons', 'surface' => '225 m²', 'coating' => 'heavy duty', 'weight' => '150kg', 'packed' => '80x80x190cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '18x18', 'images' => ['/assets/images/stretch-config-18x18.jpg'], 'size' => '18×18 meter', 'seated' => '195 persons', 'cocktail' => '335 Persons', 'cinema' => '475 Persons', 'surface' => '324 m²', 'coating' => 'heavy duty', 'weight' => '210kg', 'packed' => '85x85x200cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
    ]],
    ['label' => '21 Meter', 'variants' => [
        ['label' => '21x21', 'images' => ['/assets/images/stretch-config-21x21.jpg'], 'size' => '21×21 meter', 'seated' => '250 persons', 'cocktail' => '440 Persons', 'cinema' => '620 Persons', 'surface' => '441 m²', 'coating' => 'heavy duty', 'weight' => '290kg', 'packed' => '90x90x210cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '21x25.5', 'images' => ['/assets/images/stretch-config-21x25-5.jpg'], 'size' => '21×25.5 meter', 'seated' => '214 persons', 'cocktail' => '357 Persons', 'cinema' => '536 Persons', 'surface' => '535.5 m²', 'coating' => 'single coated', 'weight' => '402 kg', 'packed' => '75x100x150cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
        ['label' => '21x30', 'images' => ['/assets/images/stretch-config-21x30.jpg'], 'size' => '21×30 meter', 'seated' => '252 persons', 'cocktail' => '420 Persons', 'cinema' => '630 Persons', 'surface' => '630 m²', 'coating' => 'single coated', 'weight' => '473 kg', 'packed' => '75x100x150cm', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
    ]],
    ['label' => 'Specials', 'variants' => [
        ['label' => 'Custom', 'images' => ['/assets/images/stretch-config-custom.jpg'], 'size' => 'Made to measure', 'seated' => 'Variable', 'cocktail' => 'Variable', 'cinema' => 'Variable', 'surface' => 'Tailored', 'coating' => 'Tailored', 'weight' => 'Tailored', 'packed' => 'Tailored', 'colours' => 'Sand / Platinum Grey / Taupe / Black / Red / White'],
    ]],
];

$faqs = [
    ['q' => 'What does a stretch tent cost', 'a' => 'Pricing depends on size, configuration and the length of your hire. We tailor each quote to your event. Share your details and we will respond promptly.'],
    ['q' => 'Can I also rent a stretch tent?', 'a' => 'Yes. Both rental and sales are available across South-East Asia and Oceania. Speak with our team to find the right fit.'],
    ['q' => 'What is the material quality of a stretch tent', 'a' => 'We use premium Qtents canvas (750 g/m²) with HF welded seams, UV resistance 9, fire certification B1 / M2 and a 5-year fabric warranty.'],
    ['q' => 'Are stretch tents suitable for events', 'a' => 'Absolutely, weddings, festivals, corporate gatherings, terrace solutions and private celebrations. The sculptural form elevates every setting.'],
    ['q' => 'Can I order side walls for my stretch tent', 'a' => 'Yes. Side walls, windows, doors and connector panels are available to customise weather protection and privacy.'],
];
?>

<script type="application/json" id="page-meta">
{
    "title": "Stretch / Nomadic / Bedouin Tents — Majestic Marquees & Tents",
    "name": {
        "description": "Contemporary, playful and sophisticated stretch tents with a 5-year fabric warranty, available in six colours.",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "Stretch / Nomadic / Bedouin Tents — Majestic Marquees & Tents",
        "og:description": "Contemporary, playful and sophisticated stretch tents with a 5-year fabric warranty, available in six colours.",
        "og:type": "website"
    }
}
</script>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[360px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        <img src="/assets/images/stretch-hero-bg.jpg" alt="Hero Background" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="container-x text-center text-white">
            <h1 class="heading-xl text-white">Stretch / Nomadic / Bedouin</h1>
            <p class="mt-4 italic text-secondary-ttl text-white/90">Contemporary, playful and sophisticated</p>
        </div>
    </div>
</section>

<!-- Product Introduction -->
<section class="section">
    <div class="container-x">
        <h2 class="heading-l text-center">Stretch / Nomadic / Bedouin</h2>
        <p class="mt-8 text-forest-700/85 leading-relaxed text-center">
            The stretch tent, rooted in African traditions, has evolved into a contemporary tent
            concept sought after in various settings. Our high-quality stretch tents are made from
            state-of-the-art fabric that enhances durability, providing optimal resistance to UV
            rays, wind, and waterproofing for varied weather conditions. Their open and flexible
            structure makes them versatile, perfect for gatherings like weddings, festivals, and
            terrace solutions. Stretch tents seamlessly enhance ambiance while offering elegant
            protection year-round, making them an exceptional choice for corporate events and
            private celebrations alike.
        </p>
    </div>
</section>

<!-- Image gallery carousel -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x">
        <?php carousel_open(); ?>
        <?php for ($i = 1; $i <= 9; $i++): ?>
            <div class="shrink-0 grow-0 basis-full sm:basis-1/2 lg:basis-1/3 px-2">
                <img src="/assets/images/stretch-carousel-<?= $i ?>.jpg" alt="Stretch Tent <?= $i ?>" class="w-full aspect-[4/3] object-cover" loading="lazy">
            </div>
        <?php endfor; ?>
        <?php carousel_close(); ?>
    </div>
</section>

<!-- Quality & Canvas Info -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
        <div>
            <h2 class="heading-m">Quality &amp; Durability</h2>
            <p class="mt-6 text-forest-700/85 leading-relaxed">
                We make a clear statement about quality with a
                <strong class="font-semibold">5-year warranty on our stretch tent fabrics,
                setting us apart from competitors</strong>. Choosing a Qtent stretchtent is a
                conscious decision for sustainability: the high-quality canvas lasts 8–13 years,
                making it a long-term investment you can rely on.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                What truly distinguishes a Qtents stretch tents goes beyond the fabric itself. With
                the patented High Frequency welded seams, every tent is built to withstand repeated
                use season after season. This ensures maximum durability, reusability, and elegance,
                whether for events, festivals, or stylish terrace solutions.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                Available in six beautiful colors – beige, taupe, white, black, light grey, and red –
                our stretch tents combine flexibility with modern design, offering both functional
                protection and aesthetic appeal.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                Backed by a <strong class="font-semibold">5-year warranty</strong>, these tents
                combine style, strength and sustainability, creating an authentic, sophisticated
                atmosphere for memorable outdoor venues.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                For all these types of tents, we exclusively use high-quality roofs that are
                manufactured by QTents (
                <a href="https://www.qtents.com" class="text-tan-500 underline" target="_blank" rel="noopener noreferrer">www.qtents.com</a>
                ), a top-level manufacturer renowned for their expertise and innovation, hailing from
                the Netherlands.
            </p>
            <div class="mt-10 flex justify-center">
                <img src="/assets/images/stretch-qtents-logo.webp" alt="Qtents" class="h-[300px] w-auto object-contain" loading="lazy">
            </div>
        </div>
        <div>
            <h2 class="heading-m">Canvas Information</h2>
            <div class="mt-6">
                <img src="/assets/images/stretch-canvas-tent.webp" alt="HQ8 Heavy Stretch canvas layers" class="w-full max-w-[500px] mx-auto object-contain" loading="lazy">
            </div>
            <ul class="mt-6 space-y-2 text-forest-700/85">
                <?php foreach ($canvasInfo as $line): ?>
                    <li class="flex gap-3">
                        <span class="text-tan-500 mt-1.5 h-1.5 w-1.5 rounded-full bg-tan-500 shrink-0" aria-hidden="true"></span>
                        <span><?= e($line) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <h3 class="mt-10 text-primary-ttl text-forest-800 text-center">Colors on stock</h3>
            <div class="mt-6">
                <img src="/assets/images/stretch-colors.webp" alt="Stretch tent canvas colours" class="w-full object-cover" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- Why Qtents -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x">
        <div class="text-center mb-10">
            <h2 class="heading-l uppercase tracking-wide max-w-3xl mx-auto">
                Why Qtents is the best supplier for your stretch tent
            </h2>
        </div>
        <div class="bg-[#efe7d5]/60 border border-forest-800/15 p-6 sm:p-10">
            <?php carousel_open(); ?>
            <?php foreach ($whyQtents as $f): ?>
                <div class="shrink-0 grow-0 basis-full sm:basis-1/2 px-4 sm:px-8">
                    <div class="text-center">
                        <p class="text-forest-700/85 italic font-display max-w-md mx-auto"><?= e($f['text']) ?></p>
                        <img src="/assets/images/<?= e($f['icon']) ?>.webp" alt="<?= e($f['title']) ?>" class="mt-8 mx-auto w-28 h-28 object-contain" loading="lazy">
                        <h4 class="mt-6 text-primary-ttl text-forest-800"><?= e($f['title']) ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php carousel_close(); ?>
        </div>
    </div>
</section>

<!-- Stretch Tent Configurator -->
<section class="py-16 sm:py-20 lg:py-24 bg-[#D7C7A5]">
    <div class="container-x">
        <div class="text-center mb-10 text-cream-50">
            <h2 class="heading-l text-cream-50">Stretch Tent Configurator</h2>
            <p class="mt-4 text-cream-50/90 italic text-secondary-ttl">
                Stretchtents can be made to any size you want. Here is a selection of standard sizes
            </p>
        </div>
        <?php render_configurator($sizeGroups, [
            '/assets/images/stretch-carousel-1.jpg',
            '/assets/images/stretch-carousel-2.jpg',
            '/assets/images/stretch-carousel-3.jpg',
            '/assets/images/stretch-carousel-4.jpg',
        ]); ?>
    </div>
</section>

<?php
render_quote_form([
    'id'          => 'contact-form',
    'source'      => 'stretch',
    'variant'     => 'bgImage',
    'bgImage'     => '/assets/images/stretch-quote-bg.webp',
    'eyebrow'     => '',
    'title'       => 'Request Your Personalized Quote',
    'subtitle'    => "We would love to learn more about your upcoming event. Fill out the form below, and let's start the conversation together.",
    'submitLabel' => 'Send Inquiry',
    'status'      => $quoteStatus,
]);
?>

<!-- FAQ + Contact -->
<section class="py-16 sm:py-24 bg-cream-50">
    <div class="container-x max-w-5xl mx-auto">
        <div class="grid lg:grid-cols-[1fr_1.5fr] gap-10 lg:gap-16 items-start">
            <div class="bg-[#efe7d5]/60 border border-forest-800/15 p-8 sm:p-10">
                <h3 class="heading-m">Need further assistance?</h3>
                <p class="mt-4 text-forest-700/85 leading-relaxed">
                    We understand that planning an event comes with many questions. Whether it’s about
                    our tents, setup services, or special requests, we’re here to help. Reach out with
                    any questions you might have, and we’ll ensure you feel confident in your choices.
                </p>
                <a href="/contact-get-a-quote#contact-form" class="spa-link btn-primary mt-8 inline-block">Get in Touch</a>
            </div>
            <?php render_accordion($faqs, 'lined'); ?>
        </div>
    </div>
</section>
