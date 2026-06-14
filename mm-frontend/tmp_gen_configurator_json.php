<?php
/**
 * One-off generator: extracts the exact configurator data from the two page
 * files and emits product rows (JSON) for the `products` table.
 * Each configuration gets its own image path.
 */

/** Pull a top-level `$var = [ ... ];` literal out of a page file and eval it. */
function extract_array(string $php, string $var)
{
    $needle = '$' . $var . ' = [';
    $start = strpos($php, $needle);
    if ($start === false) {
        throw new RuntimeException("Could not find \$$var");
    }
    $i = $start + strlen($needle) - 1; // position of the opening '['
    $depth = 0;
    $inStr = false;
    $strChar = '';
    $len = strlen($php);
    for (; $i < $len; $i++) {
        $ch = $php[$i];
        if ($inStr) {
            if ($ch === '\\') { $i++; continue; } // skip escaped char
            if ($ch === $strChar) { $inStr = false; }
            continue;
        }
        if ($ch === "'" || $ch === '"') { $inStr = true; $strChar = $ch; continue; }
        if ($ch === '[') { $depth++; }
        elseif ($ch === ']') {
            $depth--;
            if ($depth === 0) {
                $literal = substr($php, $start + strlen('$' . $var . ' = '), $i - ($start + strlen('$' . $var . ' = ')) + 1);
                return eval('return ' . $literal . ';');
            }
        }
    }
    throw new RuntimeException("Unbalanced brackets for \$$var");
}

function slugify(string $s): string
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/** Build one product row from a page file. */
function build_product(string $file, string $prefix, string $productName, string $slug, string $intro): array
{
    $php = file_get_contents($file);

    $sizeGroups = extract_array($php, 'sizeGroups');
    $canvasInfo = extract_array($php, 'canvasInfo');
    $whyQtents  = extract_array($php, 'whyQtents');

    // colors only exist on the stretch page
    $colors = [];
    if (strpos($php, '$colors = [') !== false) {
        $colors = extract_array($php, 'colors');
    }

    $configurations = [];
    foreach ($sizeGroups as $group) {
        foreach ($group['variants'] as $v) {
            $configurations[] = [
                'group'          => $group['label'],
                'label'          => $v['label'],
                'size'           => $v['size'],
                'image'          => '/assets/images/' . $prefix . '-config-' . slugify($v['label']) . '.jpg',
                'specifications' => [
                    'seated_dinner' => $v['seated'],
                    'cocktail'      => $v['cocktail'],
                    'cinema'        => $v['cinema'],
                    'surface'       => $v['surface'],
                    'coating'       => $v['coating'],
                    'weight'        => $v['weight'],
                    'packed_size'   => $v['packed'],
                    'colours'       => $v['colours'],
                ],
            ];
        }
    }

    $properties = [
        'type'                => 'tent_configurator',
        'slug'                => $slug,
        'introduction'        => $intro,
        'canvas_information'  => $canvasInfo,
        'why_qtents'          => array_map(fn($w) => [
            'title' => $w['title'],
            'icon'  => '/assets/images/' . $w['icon'] . '.webp',
            'text'  => $w['text'],
        ], $whyQtents),
        'configuration_count' => count($configurations),
        'configurations'      => $configurations,
    ];
    if ($colors) {
        $properties['colors'] = $colors;
    }

    return [
        'CCP_id'             => 1,
        'product_name'       => $productName,
        'unit_price'         => 0.00,
        'product_properties' => $properties,
    ];
}

$base = __DIR__ . '/pages/';

$products = [
    build_product(
        $base . 'our-tents-stretch.php',
        'stretch',
        'Stretch Tent Configurator',
        'stretch-nomadic-bedouin',
        'Stretchtents can be made to any size you want. Here is a selection of standard sizes.'
    ),
    build_product(
        $base . 'our-tents-sailcloth.php',
        'sailcloth',
        'Sailcloth Configurator',
        'sailcloth-silhouette',
        'Sailcloth tents in widths from 6 m to 20 m. Here is a selection of standard sizes.'
    ),
];

$json = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents(__DIR__ . '/configurator-products.json', $json . "\n");

echo "Wrote configurator-products.json\n";
foreach ($products as $p) {
    echo "- {$p['product_name']}: {$p['product_properties']['configuration_count']} configurations\n";
}
