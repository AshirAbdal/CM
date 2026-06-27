<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * SEO engine - single source of truth for every <head> signal.
 *
 * Pure PHP, no framework. Required once by public/index.php (after helpers.php,
 * so the ORIGIN constant is available for absolute URLs).
 *
 * Rules (per product spec):
 *   - <title>            = the page's FIRST <h1> (brand appended when it fits).
 *   - meta description   = the page's FIRST meaningful <p>, trimmed to <=160.
 *   - meta keywords      = curated per route (see seo_config()).
 *   - schema.org @graph  = LocalBusiness + WebSite + WebPage + BreadcrumbList,
 *                          plus FAQPage when the page has FAQs and Product when
 *                          the route describes a product.
 *
 * Meta-description length: research (Moz / Google SERP pixel limit ~920px ≈
 * 155-160 chars) puts the sweet spot at 120-160 characters - longer text is
 * truncated in results. We cap at 160 and prefer a clean sentence/word boundary.
 */

// ── Brand / contact facts (verified from the live site, not assumed) ─────────
define('SEO_SITE_NAME',       'Majestic Marquees & Tents');
define('SEO_SITE_NAME_SHORT', 'Majestic Marquees');
define('SEO_TAGLINE',         'Superior Tents, Exceptional Service');
define('SEO_SITE_DESCRIPTION', 'Majestic Marquees & Tents offers luxury marquee and stretch tent hire and sales for weddings, celebrations and corporate events across Bali, Indonesia and Southeast Asia.');
define('SEO_LOCALE',      'en_US');
define('SEO_THEME_COLOR', '#3a4a3a');
define('SEO_EMAIL',       'Hello@MajesticMarquees.com');
define('SEO_PHONE',       '+6282342464312');
define('SEO_LOGO',          '/logo.png');
define('SEO_DEFAULT_IMAGE',  '/assets/images/home-hero-bg.jpg');

// ── Tunables ────────────────────────────────────────────────────────────────
define('SEO_TITLE_MAX', 60);   // chars before Google truncates the SERP title
define('SEO_DESC_MAX',  160);  // chars before Google truncates the SERP snippet
define('SEO_DESC_MIN',  50);   // a <p> must be at least this long to be a description

// ── Verified social profiles (schema sameAs) ────────────────────────────────
function seo_same_as(): array {
    return [
        'https://www.facebook.com/MajesticMarqueesAndTents/',
        'https://www.instagram.com/ptmajesticmarqueesandtents',
        'https://www.linkedin.com/company/majesticmarqueesandtents/',
        'https://www.youtube.com/@MajesticMarqueesAndTents',
    ];
}

/**
 * Per-route SEO configuration. Only routes listed here are managed by the
 * engine; everything else keeps its own behaviour (see seo_is_managed()).
 * Each entry may define: keywords[], image, image_w, image_h, type (schema
 * WebPage subtype), og_type, breadcrumb[], title (override), description
 * (override), robots, faqs, product{}.
 */
function seo_config(string $path): array {
    $map = [
        '/' => [
            'type'       => 'WebPage',
            'keywords'   => [
                'luxury marquee hire',
                'stretch tent hire',
                'wedding marquee Bali',
                'event tent rental Indonesia',
                'marquee sales Southeast Asia',
                'bedouin tent hire',
                'sailcloth tent',
                'bespoke marquees',
                'outdoor event tents',
                'Majestic Marquees & Tents',
            ],
            'image'      => '/assets/images/home-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home', 'path' => '/'],
            ],
        ],

        '/about' => [
            'type'       => 'AboutPage',
            'keywords'   => [
                'about Majestic Marquees',
                'luxury tent company Bali',
                'marquee specialists Indonesia',
                'stretch tent experts Southeast Asia',
                'bespoke tent makers',
                'Qtents partner',
                'outdoor event company',
            ],
            'image'      => '/assets/images/about-hero-bg.jpg',
            'image_w'    => 1600,
            'image_h'    => 1068,
            'breadcrumb' => [
                ['name' => 'Home',  'path' => '/'],
                ['name' => 'About', 'path' => '/about'],
            ],
        ],

        '/our-tents' => [
            'type'       => 'CollectionPage',
            'keywords'   => [
                'event tents',
                'tent styles',
                'stretch tent',
                'sailcloth tent',
                'bespoke marquee',
                'Qtents roofs',
                'marquee hire Bali',
                'event tent rental Indonesia',
            ],
            'image'      => '/assets/images/our-tents-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home',      'path' => '/'],
                ['name' => 'Our Tents', 'path' => '/our-tents'],
            ],
        ],

        '/our-tents/stretch-nomadic-bedouin' => [
            'keywords'   => [
                'stretch tent hire',
                'nomadic tent',
                'bedouin tent',
                'stretch marquee Bali',
                'stretch tent sales',
                'waterproof stretch tent',
                'event stretch tent Southeast Asia',
            ],
            'image'      => '/assets/images/stretch-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home',                        'path' => '/'],
                ['name' => 'Our Tents',                   'path' => '/our-tents'],
                ['name' => 'Stretch / Nomadic / Bedouin', 'path' => '/our-tents/stretch-nomadic-bedouin'],
            ],
            'product'    => [
                'name'        => 'Stretch / Nomadic / Bedouin Tents',
                'description' => 'Contemporary stretch, nomadic and bedouin tents in premium 750 g/m2 Qtents canvas with a 5-year fabric warranty, UV and fire resistance, available in six colours.',
                'category'    => 'Event Tent',
                'specs'       => [
                    'Fabric weight'   => '750 g/m2',
                    'UV resistance'   => 'Class 9',
                    'Wind resistance' => 'Force 9 Beaufort',
                    'Fire rating'     => 'B1 / M2 (NF P 92-503)',
                    'Colours'         => 'Sand, Platinum Grey, Taupe, Black, Red, White',
                ],
            ],
        ],

        '/our-tents/sailcloth-silhouette' => [
            'keywords'   => [
                'sailcloth tent hire',
                'silhouette tent',
                'sailcloth marquee',
                'sailcloth tent Bali',
                'sailcloth tent sales',
                'king pole tent',
                'elegant wedding tent',
            ],
            'image'      => '/assets/images/sailcloth-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home',                    'path' => '/'],
                ['name' => 'Our Tents',               'path' => '/our-tents'],
                ['name' => 'Sailcloth / Silhouette',  'path' => '/our-tents/sailcloth-silhouette'],
            ],
            'product'    => [
                'name'        => 'Sailcloth / Silhouette Tents',
                'description' => 'Genuine 400 g/m2 sailcloth tents (no vinyl) with a heavy-duty polyester core, top-coated for waterproofing, UV and fire resistance, in widths from 6 m to 20 m.',
                'category'    => 'Event Tent',
                'specs'       => [
                    'Fabric weight'   => '400 g/m2',
                    'Material'        => 'Genuine sailcloth, no vinyl',
                    'UV resistance'   => 'Class 9',
                    'Wind resistance' => 'Force 9 Beaufort',
                    'Fire rating'     => 'B1 / M2 (NF P 92-503)',
                    'Colour'          => 'Ivory',
                ],
            ],
        ],

        '/our-tents/custom-bespoke' => [
            'keywords'   => [
                'custom tent',
                'bespoke marquee',
                'made to measure tent',
                '3D engineered canopy',
                'custom canopy Bali',
                'bespoke event structures',
                'architectural tent design',
            ],
            'image'      => '/assets/images/custom-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home',                 'path' => '/'],
                ['name' => 'Our Tents',            'path' => '/our-tents'],
                ['name' => 'Custom / Bespoke',     'path' => '/our-tents/custom-bespoke'],
            ],
            'product'    => [
                'name'        => 'Custom & Bespoke Tents',
                'description' => 'Made-to-measure, 3D-engineered bespoke canopies designed to the exact measurements of your location, built with premium Qtents roofs.',
                'category'    => 'Bespoke Event Structure',
                'specs'       => [
                    'Design' => '3D-engineered, made to measure',
                    'Roof'   => 'Premium Qtents',
                ],
            ],
        ],

        '/gallery' => [
            'type'       => 'CollectionPage',
            'keywords'   => [
                'marquee gallery',
                'event tent showcase',
                'wedding marquee photos',
                'outdoor event setups Bali',
                'tent inspiration',
                'luxury event gallery',
            ],
            'image'      => '/assets/images/gallery-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home',    'path' => '/'],
                ['name' => 'Gallery', 'path' => '/gallery'],
            ],
        ],

        '/faq' => [
            'keywords'   => [
                'marquee FAQ',
                'tent hire questions',
                'stretch tent FAQ',
                'event tent booking',
                'marquee rental information',
                'tent hire Bali FAQ',
            ],
            'image'      => '/assets/images/faq-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home', 'path' => '/'],
                ['name' => 'FAQ',  'path' => '/faq'],
            ],
        ],

        '/contact-get-a-quote' => [
            'type'       => 'ContactPage',
            'keywords'   => [
                'contact Majestic Marquees',
                'get a marquee quote',
                'tent hire quote Bali',
                'event tent enquiry',
                'marquee booking Indonesia',
                'request tent quote',
            ],
            'image'      => '/assets/images/contact-hero-bg.jpg',
            'image_w'    => 1080,
            'image_h'    => 720,
            'breadcrumb' => [
                ['name' => 'Home',               'path' => '/'],
                ['name' => 'Contact & Get a Quote', 'path' => '/contact-get-a-quote'],
            ],
        ],

        '/terms-conditions' => [
            'description' => 'Read the terms and conditions for marquee and stretch tent hire, sales and services by PT Majestic Marquees and Tents in Bali, Indonesia.',
            'keywords'    => [
                'terms and conditions',
                'hire terms',
                'marquee rental terms',
                'Majestic Marquees terms',
            ],
            'breadcrumb' => [
                ['name' => 'Home',                 'path' => '/'],
                ['name' => 'Terms & Conditions',   'path' => '/terms-conditions'],
            ],
        ],

        '/privacy-policy-2' => [
            'description' => "Learn how Majestic Marquees & Tents collects, uses and protects your personal data in line with the GDPR, CPRA and Indonesia's UU PDP.",
            'keywords'    => [
                'privacy policy',
                'data protection',
                'GDPR',
                'personal data',
                'Majestic Marquees privacy',
            ],
            'breadcrumb' => [
                ['name' => 'Home',           'path' => '/'],
                ['name' => 'Privacy Policy', 'path' => '/privacy-policy-2'],
            ],
        ],

        '/cookie-policy' => [
            'description' => 'Understand how Majestic Marquees & Tents uses cookies, and how to manage your cookie preferences and consent on our website.',
            'keywords'    => [
                'cookie policy',
                'cookie consent',
                'website cookies',
                'Majestic Marquees cookies',
            ],
            'breadcrumb' => [
                ['name' => 'Home',          'path' => '/'],
                ['name' => 'Cookie Policy', 'path' => '/cookie-policy'],
            ],
        ],
    ];
    return $map[$path] ?? [];
}

/** Routes whose <head> is generated by this engine. */
function seo_is_managed(string $path): bool {
    return array_key_exists($path, [
        '/'                                  => true,
        '/about'                             => true,
        '/our-tents'                         => true,
        '/our-tents/stretch-nomadic-bedouin' => true,
        '/our-tents/sailcloth-silhouette'    => true,
        '/our-tents/custom-bespoke'          => true,
        '/gallery'                           => true,
        '/faq'                               => true,
        '/contact-get-a-quote'               => true,
        '/terms-conditions'                  => true,
        '/privacy-policy-2'                  => true,
        '/cookie-policy'                     => true,
    ]);
}

// ── Primitives ──────────────────────────────────────────────────────────────

/** Absolute site base URL (no trailing slash), from the ORIGIN constant. */
function seo_base_url(): string {
    return rtrim(defined('ORIGIN') ? ORIGIN : '', '/');
}

/** Resolve a path (or pass through an absolute URL) to an absolute URL. */
function seo_abs(string $path): string {
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return seo_base_url() . '/' . ltrim($path, '/');
}

/** Strip tags + decode entities + collapse whitespace to clean plain text. */
function seo_clean_text(string $html): string {
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string) $text);
}

/** The page's first <h1>, as clean text (empty string when none). */
function seo_first_h1(string $html): string {
    if (preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $m)) {
        return seo_clean_text($m[1]);
    }
    return '';
}

/**
 * The page's first meaningful <p> (>= SEO_DESC_MIN chars). Skips short eyebrow
 * / caption paragraphs; falls back to the longest paragraph found.
 */
function seo_first_paragraph(string $html): string {
    if (!preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $m)) {
        return '';
    }
    $longest = '';
    foreach ($m[1] as $raw) {
        $text = seo_clean_text($raw);
        if (mb_strlen($text) >= SEO_DESC_MIN) {
            return $text;
        }
        if (mb_strlen($text) > mb_strlen($longest)) {
            $longest = $text;
        }
    }
    return $longest;
}

/** Trim a description to <=$max chars, preferring a sentence then word boundary. */
function seo_truncate_description(string $text, int $max = SEO_DESC_MAX): string {
    $text = trim((string) preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $slice = mb_substr($text, 0, $max);
    // Prefer ending on a sentence boundary that still yields a useful length.
    if (preg_match('/^(.{60,}?[.!?])\s/u', $slice . ' ', $m)) {
        return rtrim($m[1]);
    }
    // Otherwise cut on the last word boundary and append a real ellipsis.
    $cut = mb_substr($slice, 0, $max - 1);
    if (preg_match('/^(.*)\s\S*$/u', $cut, $m)) {
        $cut = $m[1];
    }
    return rtrim($cut, " ,;:-") . "\u{2026}";
}

/** Build the <title>: first <h1> (or override), brand appended when it fits 60. */
function seo_build_title(string $h1, ?string $override = null): string {
    $base = ($override !== null && $override !== '') ? $override : $h1;
    if ($base === '') {
        $base = SEO_SITE_NAME;
    }
    if (stripos($base, SEO_SITE_NAME_SHORT) !== false) {
        return $base;
    }
    $withBrand = $base . ' | ' . SEO_SITE_NAME_SHORT;
    return mb_strlen($withBrand) <= SEO_TITLE_MAX ? $withBrand : $base;
}

// ── schema.org @graph nodes ─────────────────────────────────────────────────

/** LocalBusiness node - the organisation identity (real, verified NAP data). */
function seo_organization_node(): array {
    $base = seo_base_url();
    return [
        '@type'       => 'LocalBusiness',
        '@id'         => $base . '/#organization',
        'name'        => SEO_SITE_NAME,
        'url'         => $base . '/',
        'logo'        => ['@type' => 'ImageObject', 'url' => seo_abs(SEO_LOGO)],
        'image'       => seo_abs(SEO_DEFAULT_IMAGE),
        'description' => SEO_SITE_DESCRIPTION,
        'slogan'      => SEO_TAGLINE,
        'email'       => SEO_EMAIL,
        'telephone'   => SEO_PHONE,
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Jalan Raya Kuta 32, Desa/Kelurahan Kuta, Kec. Kuta, Kab. Badung',
            'addressLocality' => 'Kuta',
            'addressRegion'   => 'Bali',
            'postalCode'      => '80361',
            'addressCountry'  => 'ID',
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => '-8.719086',
            'longitude' => '115.17545',
        ],
        'areaServed' => [
            ['@type' => 'Country', 'name' => 'Indonesia'],
            ['@type' => 'Place',   'name' => 'Southeast Asia'],
            ['@type' => 'Place',   'name' => 'Oceania'],
        ],
        'sameAs' => seo_same_as(),
    ];
}

/** WebSite node. */
function seo_website_node(): array {
    $base = seo_base_url();
    return [
        '@type'       => 'WebSite',
        '@id'         => $base . '/#website',
        'url'         => $base . '/',
        'name'        => SEO_SITE_NAME,
        'description' => SEO_SITE_DESCRIPTION,
        'inLanguage'  => 'en-US',
        'publisher'   => ['@id' => $base . '/#organization'],
    ];
}

/** WebPage (or subtype) node for the current URL. */
function seo_webpage_node(string $url, string $title, string $desc, string $image, string $type, bool $hasBreadcrumb): array {
    $base = seo_base_url();
    $node = [
        '@type'              => $type,
        '@id'                => $url . '#webpage',
        'url'                => $url,
        'name'               => $title,
        'description'        => $desc,
        'isPartOf'           => ['@id' => $base . '/#website'],
        'about'              => ['@id' => $base . '/#organization'],
        'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image],
        'inLanguage'         => 'en-US',
    ];
    if ($hasBreadcrumb) {
        $node['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
    }
    return $node;
}

/** BreadcrumbList node. */
function seo_breadcrumb_node(string $url, array $items): array {
    $list = [];
    $pos  = 1;
    foreach ($items as $it) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => $it['name'],
            'item'     => seo_abs($it['path']),
        ];
    }
    return [
        '@type'           => 'BreadcrumbList',
        '@id'             => $url . '#breadcrumb',
        'itemListElement' => $list,
    ];
}

/** FAQPage node from an array of ['q'=>, 'a'=>] (or question/answer) pairs. */
function seo_faq_node(array $faqs): array {
    $items = [];
    foreach ($faqs as $f) {
        if (!is_array($f)) {
            continue;
        }
        $q = $f['q'] ?? $f['question'] ?? '';
        $a = $f['a'] ?? $f['answer']   ?? '';
        if ($q === '' || $a === '') {
            continue;
        }
        $items[] = [
            '@type'          => 'Question',
            'name'           => seo_clean_text($q),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => seo_clean_text($a)],
        ];
    }
    return ['@type' => 'FAQPage', 'mainEntity' => $items];
}

/** Product node (no fabricated price - quotes are on request). */
function seo_product_node(array $p, string $fallbackImage, string $url): array {
    $node = [
        '@type'       => 'Product',
        'name'        => $p['name'] ?? SEO_SITE_NAME,
        'description' => $p['description'] ?? SEO_SITE_DESCRIPTION,
        'image'       => isset($p['image']) ? seo_abs($p['image']) : $fallbackImage,
        'brand'       => ['@type' => 'Brand', 'name' => SEO_SITE_NAME],
        'url'         => $url,
    ];
    if (!empty($p['category'])) {
        $node['category'] = $p['category'];
    }
    if (!empty($p['specs']) && is_array($p['specs'])) {
        $props = [];
        foreach ($p['specs'] as $k => $v) {
            $props[] = ['@type' => 'PropertyValue', 'name' => $k, 'value' => (string) $v];
        }
        $node['additionalProperty'] = $props;
    }
    return $node;
}

// ── Orchestrator ────────────────────────────────────────────────────────────

/**
 * Build the full $pageMeta structure consumed by layout/page.php (server render)
 * and public/spa.js (client-side navigation).
 *
 * @param string $path Request path (e.g. '/').
 * @param string $html Rendered page HTML (used to derive title + description).
 * @param array  $opts ['faqs'=>[], 'robots'=>'...', 'file'=>'...'].
 */
function seo_build_meta(string $path, string $html, array $opts = []): array {
    $cfg = seo_config($path);

    // Never read <script>/<style> bodies (e.g. JSON meta blocks) as page text.
    $clean = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
    $clean = is_string($clean) ? $clean : $html;

    $h1    = seo_first_h1($clean);
    $title = seo_build_title($h1, $cfg['title'] ?? null);

    $desc = $cfg['description'] ?? seo_first_paragraph($clean);
    if ($desc === '') {
        $desc = SEO_SITE_DESCRIPTION;
    }
    $desc = seo_truncate_description($desc, SEO_DESC_MAX);

    $base  = seo_base_url();
    $url   = $base . ($path === '/' ? '/' : $path);
    $image = seo_abs($cfg['image'] ?? SEO_DEFAULT_IMAGE);

    $robots   = $opts['robots'] ?? ($cfg['robots'] ?? 'index, follow');
    $keywords = !empty($cfg['keywords']) ? implode(', ', $cfg['keywords']) : '';

    // <meta name="..."> tags (includes Twitter Card, which uses name=).
    $name = [
        'description' => $desc,
        'robots'      => $robots,
        'author'      => SEO_SITE_NAME,
        'theme-color' => SEO_THEME_COLOR,
        'image'       => $image,
    ];
    if ($keywords !== '') {
        $name['keywords'] = $keywords;
    }
    $name['twitter:card']        = 'summary_large_image';
    $name['twitter:title']       = $title;
    $name['twitter:description'] = $desc;
    $name['twitter:image']       = $image;

    // <meta property="..."> tags (Open Graph).
    $property = [
        'og:type'        => $cfg['og_type'] ?? 'website',
        'og:url'         => $url,
        'og:title'       => $title,
        'og:description' => $desc,
        'og:image'       => $image,
        'og:site_name'   => SEO_SITE_NAME,
        'og:locale'      => SEO_LOCALE,
    ];
    if (!empty($cfg['image_w']) && !empty($cfg['image_h'])) {
        $property['og:image:width']  = (string) $cfg['image_w'];
        $property['og:image:height'] = (string) $cfg['image_h'];
    }

    // schema.org @graph.
    $graph   = [seo_organization_node(), seo_website_node()];
    $graph[] = seo_webpage_node($url, $title, $desc, $image, $cfg['type'] ?? 'WebPage', !empty($cfg['breadcrumb']));
    if (!empty($cfg['breadcrumb'])) {
        $graph[] = seo_breadcrumb_node($url, $cfg['breadcrumb']);
    }
    $faqs = $opts['faqs'] ?? ($cfg['faqs'] ?? null);
    if (!empty($faqs) && is_array($faqs)) {
        $graph[] = seo_faq_node($faqs);
    }
    if (!empty($cfg['product'])) {
        $graph[] = seo_product_node($cfg['product'], $image, $url);
    }

    return [
        'title'    => $title,
        'name'     => $name,
        'property' => $property,
        'link'     => ['canonical' => $url],
        'schema'   => ['@context' => 'https://schema.org', '@graph' => $graph],
    ];
}
