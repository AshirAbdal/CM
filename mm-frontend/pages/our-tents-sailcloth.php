<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$quoteStatus = handle_quote_submit('sailcloth');

$canvasInfo = [
    '100% waterproof top coated',
    'Genuine sailcloth, no vinyl',
    'Extremely light and strong fabric with high tearing resistance due to the heavy duty polyester core',
    'Anti fungus and algae treatment',
    'UV resistance 9',
    'Wind resistance force 9 Beaufort',
    'Full colour print on both sides possible',
    'Fire retardant B1, M2 – NF P 92-503',
    'Canvas look for authentic character',
    'Weight: 400 g/m²',
    'Colour: ivory with darker reinforced details',
];

$whyQtents = [
    ['title' => 'Our Fabrics', 'icon' => 'stretch-why-fabrics', 'text' => 'High-quality materials that meet the strictest standards, with special treatment to prevent discolouration and quality loss.'],
    ['title' => '100% Waterproof', 'icon' => 'stretch-why-waterproof', 'text' => 'Due to the special coating and the lack of stitching and seams, our sailcloth tents are guaranteed to remain waterproof. Interim treatment is not necessary.'],
    ['title' => 'Fire Safe', 'icon' => 'stretch-why-firesafe', 'text' => 'All materials meet the required standards. Every Qtent has a fire certificate. B1 / M2 All our Sailcloth tents meet the European standard NEN-EN 8020-41:2012.'],
    ['title' => 'UV Resistant', 'icon' => 'stretch-why-uv', 'text' => 'A special top coating prevents the canvas from discolouring or deteriorating and remains beautiful year after year.'],
    ['title' => 'Algae & Mould Proof', 'icon' => 'stretch-why-algae', 'text' => 'Canvas impregnated on the inside and outside with a coating to which algae and mould cannot adhere.'],
    ['title' => 'Printing Possible', 'icon' => 'stretch-why-printing', 'text' => 'Canvas can be printed in full colour on both sides, ideal for maximum exposure or corporate identity.'],
    ['title' => 'Easy Setup', 'icon' => 'stretch-why-instructions', 'text' => 'Designed to be set up easily, with clear instructions. Municipal permitting books can be provided on request.'],
];

$sizeGroups = [
    ['label' => '6 Meter', 'variants' => [
        ['label' => '6x6', 'images' => ['/assets/images/sailcloth-config-6x6.jpg'], 'size' => '6×6 meter', 'seated' => '10 persons', 'cocktail' => '16 Persons', 'cinema' => '23 Persons', 'surface' => '23 m²', 'coating' => 'coated', 'weight' => '10.8kg', 'packed' => '50x50x100cm', 'colours' => 'Ivory'],
        ['label' => '6x12', 'images' => ['/assets/images/sailcloth-config-6x12.jpg'], 'size' => '6×12 meter', 'seated' => '24 persons', 'cocktail' => '38 Persons', 'cinema' => '54 Persons', 'surface' => '46 m²', 'coating' => 'coated', 'weight' => '18kg', 'packed' => '55x55x120cm', 'colours' => 'Ivory'],
        ['label' => '6x18', 'images' => ['/assets/images/sailcloth-config-6x18.jpg'], 'size' => '6×18 meter', 'seated' => '40 persons', 'cocktail' => '64 Persons', 'cinema' => '90 Persons', 'surface' => '69 m²', 'coating' => 'coated', 'weight' => '26kg', 'packed' => '60x60x130cm', 'colours' => 'Ivory'],
        ['label' => '6x24', 'images' => ['/assets/images/sailcloth-config-6x24.jpg'], 'size' => '6×24 meter', 'seated' => '56 persons', 'cocktail' => '90 Persons', 'cinema' => '128 Persons', 'surface' => '92 m²', 'coating' => 'coated', 'weight' => '34kg', 'packed' => '60x60x140cm', 'colours' => 'Ivory'],
        ['label' => '6x30', 'images' => ['/assets/images/sailcloth-config-6x30.jpg'], 'size' => '6×30 meter', 'seated' => '72 persons', 'cocktail' => '115 Persons', 'cinema' => '162 Persons', 'surface' => '115 m²', 'coating' => 'coated', 'weight' => '42kg', 'packed' => '60x60x150cm', 'colours' => 'Ivory'],
    ]],
    ['label' => '8 Meter', 'variants' => [
        ['label' => '8x8', 'images' => ['/assets/images/sailcloth-config-8x8.jpg'], 'size' => '8×8 meter', 'seated' => '20 persons', 'cocktail' => '32 Persons', 'cinema' => '45 Persons', 'surface' => '40 m²', 'coating' => 'coated', 'weight' => '16kg', 'packed' => '55x55x120cm', 'colours' => 'Ivory'],
        ['label' => '8x16', 'images' => ['/assets/images/sailcloth-config-8x16.jpg'], 'size' => '8×16 meter', 'seated' => '46 persons', 'cocktail' => '72 Persons', 'cinema' => '100 Persons', 'surface' => '80 m²', 'coating' => 'coated', 'weight' => '30kg', 'packed' => '60x60x140cm', 'colours' => 'Ivory'],
        ['label' => '8x24', 'images' => ['/assets/images/sailcloth-config-8x24.jpg'], 'size' => '8×24 meter', 'seated' => '72 persons', 'cocktail' => '115 Persons', 'cinema' => '160 Persons', 'surface' => '120 m²', 'coating' => 'coated', 'weight' => '46kg', 'packed' => '65x65x150cm', 'colours' => 'Ivory'],
        ['label' => '8x32', 'images' => ['/assets/images/sailcloth-config-8x32.jpg'], 'size' => '8×32 meter', 'seated' => '98 persons', 'cocktail' => '155 Persons', 'cinema' => '220 Persons', 'surface' => '160 m²', 'coating' => 'coated', 'weight' => '60kg', 'packed' => '70x70x160cm', 'colours' => 'Ivory'],
    ]],
    ['label' => '10 meter', 'variants' => [
        ['label' => '10x10', 'images' => ['/assets/images/sailcloth-config-10x10.jpg'], 'size' => '10×10 meter', 'seated' => '35 persons', 'cocktail' => '55 Persons', 'cinema' => '78 Persons', 'surface' => '63 m²', 'coating' => 'coated', 'weight' => '24kg', 'packed' => '60x60x140cm', 'colours' => 'Ivory'],
        ['label' => '10x20', 'images' => ['/assets/images/sailcloth-config-10x20.jpg'], 'size' => '10×20 meter', 'seated' => '80 persons', 'cocktail' => '128 Persons', 'cinema' => '180 Persons', 'surface' => '126 m²', 'coating' => 'coated', 'weight' => '46kg', 'packed' => '65x65x150cm', 'colours' => 'Ivory'],
        ['label' => '10x30', 'images' => ['/assets/images/sailcloth-config-10x30.jpg'], 'size' => '10×30 meter', 'seated' => '125 persons', 'cocktail' => '200 Persons', 'cinema' => '280 Persons', 'surface' => '188 m²', 'coating' => 'coated', 'weight' => '68kg', 'packed' => '70x70x160cm', 'colours' => 'Ivory'],
    ]],
    ['label' => '12 Meter', 'variants' => [
        ['label' => '12x12', 'images' => ['/assets/images/sailcloth-config-12x12.jpg'], 'size' => '12×12 meter', 'seated' => '50 persons', 'cocktail' => '80 Persons', 'cinema' => '115 Persons', 'surface' => '90 m²', 'coating' => 'coated', 'weight' => '34kg', 'packed' => '65x65x150cm', 'colours' => 'Ivory'],
        ['label' => '12x24', 'images' => ['/assets/images/sailcloth-config-12x24.jpg'], 'size' => '12×24 meter', 'seated' => '115 persons', 'cocktail' => '184 Persons', 'cinema' => '260 Persons', 'surface' => '180 m²', 'coating' => 'coated', 'weight' => '64kg', 'packed' => '70x70x160cm', 'colours' => 'Ivory'],
        ['label' => '12x36', 'images' => ['/assets/images/sailcloth-config-12x36.jpg'], 'size' => '12×36 meter', 'seated' => '180 persons', 'cocktail' => '290 Persons', 'cinema' => '405 Persons', 'surface' => '270 m²', 'coating' => 'coated', 'weight' => '95kg', 'packed' => '75x75x170cm', 'colours' => 'Ivory'],
    ]],
    ['label' => '14 Meter', 'variants' => [
        ['label' => '14x14', 'images' => ['/assets/images/sailcloth-config-14x14.jpg'], 'size' => '14×14 meter', 'seated' => '70 persons', 'cocktail' => '110 Persons', 'cinema' => '160 Persons', 'surface' => '126 m²', 'coating' => 'coated', 'weight' => '48kg', 'packed' => '70x70x160cm', 'colours' => 'Ivory'],
        ['label' => '14x28', 'images' => ['/assets/images/sailcloth-config-14x28.jpg'], 'size' => '14×28 meter', 'seated' => '160 persons', 'cocktail' => '255 Persons', 'cinema' => '360 Persons', 'surface' => '252 m²', 'coating' => 'coated', 'weight' => '90kg', 'packed' => '75x75x170cm', 'colours' => 'Ivory'],
        ['label' => '14x42', 'images' => ['/assets/images/sailcloth-config-14x42.jpg'], 'size' => '14×42 meter', 'seated' => '250 persons', 'cocktail' => '400 Persons', 'cinema' => '560 Persons', 'surface' => '378 m²', 'coating' => 'coated', 'weight' => '135kg', 'packed' => '80x80x180cm', 'colours' => 'Ivory'],
    ]],
    ['label' => '20 Meter', 'variants' => [
        ['label' => '20x20', 'images' => ['/assets/images/sailcloth-config-20x20.jpg'], 'size' => '20×20 meter', 'seated' => '134 persons', 'cocktail' => '224 Persons', 'cinema' => '335 Persons', 'surface' => '335 m²', 'coating' => 'coated', 'weight' => '120kg', 'packed' => '75x100x150cm', 'colours' => 'Ivory'],
        ['label' => '20x26', 'images' => ['/assets/images/sailcloth-config-20x26.jpg'], 'size' => '20×26 meter', 'seated' => '182 persons', 'cocktail' => '304 Persons', 'cinema' => '455 Persons', 'surface' => '455 m²', 'coating' => 'coated', 'weight' => '156kg', 'packed' => '50x75x150cm', 'colours' => 'Ivory'],
        ['label' => '20x32', 'images' => ['/assets/images/sailcloth-config-20x32.jpg'], 'size' => '20×32 meter', 'seated' => '230 persons', 'cocktail' => '384 Persons', 'cinema' => '575 Persons', 'surface' => '575 m²', 'coating' => 'coated', 'weight' => '192kg', 'packed' => '75x100x150cm', 'colours' => 'Ivory'],
        ['label' => '20x38', 'images' => ['/assets/images/sailcloth-config-20x38.jpg'], 'size' => '20×38 meter', 'seated' => '278 persons', 'cocktail' => '464 Persons', 'cinema' => '695 Persons', 'surface' => '695 m²', 'coating' => 'coated', 'weight' => '228kg', 'packed' => '75x100x150cm', 'colours' => 'Ivory'],
    ]],
];

$faqs = [
    ['q' => 'What sizes of tents do you offer?', 'a' => 'Sailcloth widths run from 6 m up to 20 m with double-row king poles, and lengths can be extended in modular bays.'],
    ['q' => 'What is the cost of a Sailcloth tent?', 'a' => 'Pricing varies by size, configuration and accessories. Share your event details and we will tailor a quote.'],
    ['q' => 'How do I know which size sailcloth tent best suits my situation', 'a' => 'Our team will help match guest counts, seating styles and venue constraints to the right footprint and accessories.'],
    ['q' => 'What is the quality of the material of a sailcloth tent?', 'a' => 'Genuine 400 g/m² sailcloth (no vinyl) with a heavy-duty polyester core, top-coated for waterproofing, UV and fire resistance.'],
    ['q' => 'Where does the name Sailcloth originates from', 'a' => 'The fabric and silhouette descend from traditional ship sails, pole-tensioned canvas that catches light and wind with sculptural elegance.'],
];
?>

<script type="application/json" id="page-meta">
{
    "title": "Sailcloth / Silhouette Tents - Majestic Marquees & Tents",
    "name": {
        "description": "Genuine sailcloth tents in widths from 6 m to 20 m - elegance, style and sophistication for memorable outdoor events.",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "Sailcloth / Silhouette Tents - Majestic Marquees & Tents",
        "og:description": "Genuine sailcloth tents in widths from 6 m to 20 m - elegance, style and sophistication for memorable outdoor events.",
        "og:type": "website"
    }
}
</script>

<!-- Hero -->
<section class="relative">
    <div class="relative w-full h-[360px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        <img src="/assets/images/sailcloth-hero-bg.jpg" alt="Hero Background" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    </div>
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="container-x text-center text-white">
            <h1 class="heading-xl text-white">Sailcloth / Silhoutte</h1>
            <p class="mt-4 italic text-secondary-ttl text-white/90">Elegance, Style and Sophistication</p>
        </div>
    </div>
</section>

<!-- Intro tagline -->
<section class="section">
    <div class="container-x max-w-4xl mx-auto text-center">
        <p class="text-forest-700/85 leading-relaxed">
            Elegance, style, and sophistication are all intricately woven together in the details,
            reflecting a deeper sense of artistry and grace.
        </p>
    </div>
</section>

<!-- Image gallery carousel -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x">
        <?php carousel_open(); ?>
        <?php for ($i = 1; $i <= 9; $i++): ?>
            <div class="shrink-0 grow-0 basis-full sm:basis-1/2 lg:basis-1/3 px-2">
                <img src="/assets/images/sailcloth-carousel-<?= $i ?>.jpg" alt="Sailcloth <?= $i ?>" class="w-full aspect-[4/3] object-cover" loading="lazy">
            </div>
        <?php endfor; ?>
        <?php carousel_close(); ?>
    </div>
</section>

<!-- Configurations & Canvas Info -->
<section class="pb-16 sm:pb-20 lg:pb-24">
    <div class="container-x grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
        <div>
            <h2 class="heading-m">Configurations</h2>
            <p class="mt-6 text-forest-700/85 leading-relaxed">
                The Sailcloth / Silhouette tent can be purchased in several different widths and lengts.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                From the small and cosy 6 meter width to the big and majestic 20 meter width with a
                double row of kingpoles.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                All the tents have the same characteristics, they offer an exquisite outdoor setting
                with organic shapes, translucent fabric, and charming details like festive flags and
                wooden poles. Made from high-quality, waterproof coated sailcloth, they ensure
                <strong class="font-semibold">durability</strong> and protection against all
                weather conditions.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                Available in various standard sizes and large festival models, our tents feature
                adjustable side walls for year-round use while maintaining an open, airy feel. The
                reinforced seams and
                <strong class="font-semibold">lightweight</strong> yet tear-resistant fabric
                guarantee reliability, even in tough conditions.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                The Qtents Sailcloth tents come with closed or transparent sidewalls, offering a
                customizable solution for any event.
            </p>
            <p class="mt-4 text-forest-700/85 leading-relaxed">
                Backed by a <strong class="font-semibold">2-year warranty</strong>, these tents
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
            <img src="/assets/images/stretch-qtents-logo.webp" alt="Qtents" class="mt-10 h-[300px] w-auto object-contain" loading="lazy">
        </div>
        <div>
            <h2 class="heading-m">Canvas Information</h2>
            <ul class="mt-6 space-y-2 text-forest-700/85">
                <?php foreach ($canvasInfo as $line): ?>
                    <li class="flex gap-3">
                        <span class="text-tan-500 mt-1.5 h-1.5 w-1.5 rounded-full bg-tan-500 shrink-0" aria-hidden="true"></span>
                        <span><?= e($line) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <img src="/assets/images/sailcloth-canvas-layers.webp" alt="Genuine sailcloth canvas layers" class="mt-10 w-full max-w-[586px] object-contain" loading="lazy">
        </div>
    </div>
</section>

<!-- Why Qtents -->
<section class="relative pb-16 sm:pb-20 lg:pb-24 pt-16 sm:pt-20 lg:pt-24 bg-cover bg-center" style="background-image:url('/assets/images/sailcloth-why-bg.webp');">
    <div class="absolute inset-0 bg-black/45"></div>
    <div class="relative container-x">
        <div class="text-center mb-10">
            <h2 class="heading-l uppercase tracking-wide max-w-3xl mx-auto text-white">
                Why a sailcloth tent from Qtents is the best choice
            </h2>
        </div>
        <div class="bg-cream-50/95 border border-forest-800/15 p-6 sm:p-10">
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

<!-- Sailcloth Configurator -->
<section class="py-16 sm:py-20 lg:py-24 bg-[#D7C7A5]">
    <div class="container-x">
        <div class="text-center mb-10 text-cream-50">
            <h2 class="heading-l text-cream-50">Sailcloth Configurator</h2>
            <p class="mt-4 text-cream-50/90 italic text-secondary-ttl">
                See why clients trust us for their most memorable events.
            </p>
        </div>
        <?php render_configurator($sizeGroups, [
            '/assets/images/sailcloth-carousel-1.jpg',
            '/assets/images/sailcloth-carousel-2.jpg',
            '/assets/images/sailcloth-carousel-3.jpg',
            '/assets/images/sailcloth-carousel-4.jpg',
        ]); ?>
    </div>
</section>

<?php
render_quote_form([
    'id'          => 'contact-form',
    'source'      => 'sailcloth',
    'variant'     => 'bgImage',
    'bgImage'     => '/assets/images/sailcloth-quote-bg.webp',
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
