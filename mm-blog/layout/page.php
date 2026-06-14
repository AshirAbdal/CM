<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageMeta['title'] ?? 'Blog | Majestic Marquees') ?></title>
    <link rel="icon" href="data:,">
    <?php foreach ($pageMeta['name'] ?? [] as $k => $v): ?>
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

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
                        display: ['"Cormorant Garamond"', 'Playfair Display', 'Georgia', 'serif'],
                        sans:    ['Inter', 'system-ui', 'sans-serif']
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
            .hero-blog h1, .hero-blog h2, .hero-blog h3 { color: #ffffff !important; }
            .hero-blog, .hero-blog p, .hero-blog span, .hero-blog a { color: #ffffff; }
            .btn { @apply inline-flex items-center justify-center px-6 py-2 text-sm font-medium tracking-wider uppercase transition-colors; }
            .btn-primary { @apply btn bg-tan-500 text-white hover:bg-tan-600 rounded-sm py-3.5; }
            .btn-outline { @apply btn border border-forest-700 text-forest-800 hover:bg-forest-700 hover:text-cream-50; }
            .section { @apply py-16 sm:py-20 lg:py-28; }
            .eyebrow { @apply font-display italic text-lg text-forest-700; }
            .prose-blog { @apply max-w-prose text-forest-800/90 leading-relaxed; }
            .prose-blog p { @apply my-5; }
            .prose-blog h2 { @apply font-display text-3xl mt-12 mb-4 text-forest-800; }
            .prose-blog h3 { @apply font-display text-2xl mt-10 mb-3 text-forest-800; }
            .prose-blog ul { @apply list-disc pl-6 my-5 space-y-2; }
            .prose-blog a { @apply text-tan-500 underline underline-offset-2 hover:text-tan-600; }
            .prose-blog blockquote { @apply border-l-2 border-tan-500 pl-5 italic font-display text-xl text-forest-700 my-8; }
        }
    </style>
</head>
<body>
<?= $pageContent ?>
</body>
</html>
