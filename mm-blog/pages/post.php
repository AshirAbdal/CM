<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$slug = (string) ($_GET['slug'] ?? '');
$resp = blog_api('/wl/public/blog/posts/' . rawurlencode($slug));
if (!$resp || empty($resp['data'])) {
    http_response_code(404);
    require __DIR__ . '/not-found.php';
    return;
}

$p = $resp['data'];
$related = is_array($resp['related'] ?? null) ? $resp['related'] : [];

$desc = (string) (
    $p['meta_description']
    ?? $p['excerpt']
    ?? $p['subtitle']
    ?? ''
);
$canonical = 'https://blog.majesticmarquees.com/' . ($p['slug'] ?? $slug);

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $p['title'] ?? '',
    'description' => $desc,
    'image' => $p['featured_image_url'] ?? null,
    'datePublished' => $p['published_at'] ?? null,
    'dateModified' => $p['updated_at'] ?? null,
    'author' => [
        '@type' => 'Person',
        'name' => $p['author']['name'] ?? 'Majestic Marquees',
    ],
    'mainEntityOfPage' => $canonical,
];

$meta = [
    'title' => (($p['meta_title'] ?? $p['title'] ?? 'Blog') . ' | Majestic Marquees'),
    'name' => [
        'description' => $desc,
        'robots' => 'index, follow',
    ],
    'property' => [
        'og:title' => $p['title'] ?? 'Blog',
        'og:description' => $desc,
        'og:type' => 'article',
        'og:image' => $p['featured_image_url'] ?? '',
    ],
    'schema' => $schema,
    'canonical' => $canonical,
];

$fmt = function (?string $iso): string {
    return $iso ? date('F j, Y', strtotime($iso)) : '';
};
$fmtT = function (?string $iso): string {
    return $iso ? date('g:i a', strtotime($iso)) : '';
};
$publishedDate = $fmt($p['published_at'] ?? null);
$publishedTime = $fmtT($p['published_at'] ?? null);
?>
<script type="application/json" id="page-meta"><?= json_encode($meta, JSON_UNESCAPED_SLASHES) ?></script>

<article class="section">
    <div class="container-x">
        <h1 class="font-display font-semibold tracking-tight leading-[1.15] text-[32px] sm:text-[40px] lg:text-[48px] text-forest-800"><?= e($p['title'] ?? '') ?></h1>
        <ul class="mt-5 flex flex-wrap items-center gap-x-7 gap-y-2 text-[16px] text-[#d7c7a5]">
            <li class="inline-flex items-center gap-2">
                <svg class="text-[#a67b5b]" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9.5"></circle><circle cx="12" cy="10" r="3"></circle><path d="M6.2 19a6 6 0 0 1 11.6 0"></path></svg>
                <?= e($p['author']['name'] ?? 'Majestic Marquees') ?>
            </li>
            <?php if ($publishedDate !== ''): ?>
            <li class="inline-flex items-center gap-2">
                <svg class="text-[#a67b5b]" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"></rect><path d="M3 9h18M8 2v4M16 2v4"></path></svg>
                <?= e($publishedDate) ?>
            </li>
            <?php endif; ?>
            <?php if ($publishedTime !== ''): ?>
            <li class="inline-flex items-center gap-2">
                <svg class="text-[#a67b5b]" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                <?= e($publishedTime) ?>
            </li>
            <?php endif; ?>
        </ul>

        <?php if (!empty($p['featured_image_url'])): ?>
        <img src="<?= e($p['featured_image_url']) ?>" alt="<?= e($p['featured_image_alt'] ?? $p['title'] ?? '') ?>" class="mt-8 w-full h-[300px] sm:h-[400px] lg:h-[500px] object-cover">
        <?php endif; ?>

        <div class="prose-blog !max-w-4xl mt-10"><?= $p['content'] ?? '' ?></div>

        <?php if (!empty($p['tags']) && is_array($p['tags'])): ?>
        <div class="mt-10 flex flex-wrap gap-2">
            <?php foreach ($p['tags'] as $t): ?>
            <span class="rounded-sm bg-cream-100 px-3 py-1 text-xs text-forest-700"><?= e($t['name'] ?? '') ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</article>

<?php if ($related): ?>
<section class="section pt-0">
    <div class="container-x">
        <h2 class="heading-m mb-10">Related stories</h2>
        <div class="grid gap-10 sm:grid-cols-3">
            <?php foreach ($related as $r): ?>
            <a href="/<?= e($r['slug'] ?? '') ?>" class="group block">
                <?php if (!empty($r['featured_image_url'])): ?>
                <img src="<?= e($r['featured_image_url']) ?>" alt="<?= e($r['title'] ?? '') ?>" class="aspect-[16/10] w-full rounded-sm object-cover" loading="lazy">
                <?php endif; ?>
                <h3 class="text-primary-ttl mt-4 text-tan-500 group-hover:text-tan-600"><?= e($r['title'] ?? '') ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
