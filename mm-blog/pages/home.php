<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
$q     = trim((string) ($_GET['s'] ?? ''));
$path  = '/wl/public/blog/posts?page=1&per_page=12';
if ($q !== '') {
    $path .= '&search=' . rawurlencode($q);
}
$resp  = blog_api($path);
$posts = is_array($resp['data'] ?? null) ? $resp['data'] : [];
$hero  = $q === '' ? ($posts[0] ?? null) : null;
$fmt = function (?string $iso): string {
    return $iso ? date('j F Y', strtotime($iso)) : '';
};
$fmtT = function (?string $iso): string {
    return $iso ? date('H:i', strtotime($iso)) : '';
};
?>
<script type="application/json" id="page-meta">
{
    "title": "Blog | Majestic Marquees",
    "name": {
        "description": "Stories about luxury events, outdoor elegance, and extraordinary gatherings",
        "robots": "index, follow"
    },
    "property": {
        "og:title": "Blog | Majestic Marquees",
        "og:description": "Stories about luxury events, outdoor elegance, and extraordinary gatherings",
        "og:type": "website"
    }
}
</script>

<?php if ($hero): ?>
<section class="hero-blog relative min-h-[55vh] flex items-end bg-forest-900">
    <?php if (!empty($hero['featured_image_url'])): ?>
    <img src="<?= e($hero['featured_image_url']) ?>" alt="<?= e($hero['featured_image_alt'] ?? $hero['title']) ?>" class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20"></div>
    <?php endif; ?>
    <div class="container-x relative py-16 sm:py-24 lg:py-28">
        <a href="/<?= e($hero['slug']) ?>">
            <h1 class="heading-m max-w-3xl"><?= e($hero['title']) ?></h1>
        </a>
        <?php if (!empty($hero['subtitle'])): ?>
        <p class="mt-4 max-w-2xl text-lg"><?= e($hero['subtitle']) ?></p>
        <?php endif; ?>
        <ul class="mt-6 flex flex-wrap items-center gap-6 text-sm">
            <li class="inline-flex items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
                <?= e($hero['author']['name'] ?? 'Majestic Marquees') ?>
            </li>
            <li class="inline-flex items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"></rect><path d="M3 9h18M8 2v4M16 2v4"></path></svg>
                <?= e($fmt($hero['published_at'] ?? null)) ?>
            </li>
            <li class="inline-flex items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                <?= e($fmtT($hero['published_at'] ?? null)) ?>
            </li>
        </ul>
        <a href="/<?= e($hero['slug']) ?>" class="btn-primary mt-8">Read More</a>
    </div>
</section>
<?php endif; ?>

<section class="section pb-0">
    <div class="container-x">
        <form method="get" action="/" role="search" class="mx-auto flex max-w-4xl items-stretch overflow-hidden rounded-sm border border-forest-800/15 bg-white shadow-sm">
            <label for="blog-search" class="sr-only">Search</label>
            <input id="blog-search" type="search" name="s" value="<?= e($q) ?>" placeholder="Type to start searching..." autocomplete="off"
                   class="min-w-0 flex-1 bg-transparent px-5 py-4 text-[15px] text-forest-800 placeholder-forest-700/45 focus:outline-none">
            <button type="submit" class="btn-primary rounded-none px-8">Search</button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container-x">
        <?php if ($q !== ''): ?>
        <p class="mb-10 text-body-s text-forest-700/70">
            <?= count($posts) ?> result<?= count($posts) === 1 ? '' : 's' ?> for &ldquo;<span class="text-forest-800 font-medium"><?= e($q) ?></span>&rdquo;
            &middot; <a href="/" class="text-tan-500 hover:text-tan-600">Clear</a>
        </p>
        <?php endif; ?>
        <?php if (!$posts): ?>
        <div class="text-center py-16">
            <p class="text-body-s text-forest-700/80">
                <?= $q !== '' ? 'No posts match your search.' : 'No posts published yet.' ?>
            </p>
        </div>
        <?php else: ?>
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($posts as $p): ?>
            <article class="group flex flex-col">
                <a href="/<?= e($p['slug']) ?>" class="block overflow-hidden rounded-sm <?= empty($p['featured_image_url']) ? 'bg-forest-900 aspect-[16/10]' : '' ?>">
                    <?php if (!empty($p['featured_image_url'])): ?>
                    <img src="<?= e($p['featured_image_url']) ?>" alt="<?= e($p['featured_image_alt'] ?? $p['title']) ?>"
                         class="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                    <?php endif; ?>
                </a>
                <h3 class="text-primary-ttl mt-5">
                    <a href="/<?= e($p['slug']) ?>" class="text-tan-500 hover:text-tan-600"><?= e($p['title']) ?></a>
                </h3>
                <p class="text-body-s mt-2 text-forest-700/70">
                    <?= e($p['author']['name'] ?? 'Majestic Marquees') ?> &middot;
                    <?= e($fmt($p['published_at'] ?? null)) ?> &middot;
                    <?= e($fmtT($p['published_at'] ?? null)) ?>
                </p>
                <p class="prose-blog mt-3 line-clamp-3"><?= e($p['excerpt'] ?? '') ?></p>
                <a href="/<?= e($p['slug']) ?>" class="mt-4 text-sm font-medium text-tan-500 hover:text-tan-600">Read More &raquo;</a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
