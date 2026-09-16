<?php
// ================================================
// lib/data/palettes.php — block palettes
// ------------------------------------------------
// Curated palettes plus per-style role pools. Random palettes
// draw from the pools rather than the whole block list, so a
// "random" medieval palette still looks deliberate.
//
// Roles: main · secondary · accent · detail · lighting
// ================================================
require_once __DIR__ . '/blocks.php';

function paletteStyles(): array
{
    return [
        'medieval'   => ['label' => 'Medieval',   'icon' => '🏰', 'desc' => 'Stone brick walls, dark timber framing, warm lantern light.'],
        'rustic'     => ['label' => 'Rustic',     'icon' => '🌾', 'desc' => 'Weathered wood, mud brick and worn stone. Farms and cottages.'],
        'modern'     => ['label' => 'Modern',     'icon' => '🏢', 'desc' => 'Flat concrete, big glass, black outlines, very little texture.'],
        'japanese'   => ['label' => 'Japanese',   'icon' => '🎋', 'desc' => 'Pale timber, white plaster, dark trim, restrained detail.'],
        'fantasy'    => ['label' => 'Fantasy',    'icon' => '🧙', 'desc' => 'Deep purples and mossy stone with strong coloured light.'],
        'industrial' => ['label' => 'Industrial', 'icon' => '⚙️', 'desc' => 'Iron, copper, blackstone and exposed structure.'],
        'cozy'       => ['label' => 'Cosy',       'icon' => '🔥', 'desc' => 'Warm wood, soft light and lots of small detail.'],
        'underground'=> ['label' => 'Underground','icon' => '⛏', 'desc' => 'Deepslate and tuff, cold light, tight spaces.'],
        'nether'     => ['label' => 'Nether',     'icon' => '🔥', 'desc' => 'Blackstone, crimson and soul light.'],
        'castle'     => ['label' => 'Castle',     'icon' => '🛡', 'desc' => 'Heavy stone, cracked masonry, iron and banners.'],
        'desert'     => ['label' => 'Desert',     'icon' => '🏜', 'desc' => 'Sandstone, terracotta and mud brick under harsh sun.'],
        'ocean'      => ['label' => 'Ocean',      'icon' => '🌊', 'desc' => 'Prismarine, copper verdigris and sea lanterns.'],
    ];
}

/**
 * Hand-picked palettes. Each is a set of block ids by role.
 * These are the ones offered as presets and used by Build Ideas.
 */
function palettePresets(): array
{
    return [
        'medieval-village' => [
            'name' => 'Medieval Village', 'style' => 'medieval',
            'desc' => 'The reliable timber-and-stone cottage look.',
            'blocks' => ['main' => 'stone_bricks', 'secondary' => 'spruce_planks', 'accent' => 'stripped_dark_oak_log',
                         'detail' => 'mossy_stone_bricks', 'lighting' => 'lantern'],
        ],
        'medieval-keep' => [
            'name' => 'Stone Keep', 'style' => 'castle',
            'desc' => 'Heavier and colder — for walls, gates and towers.',
            'blocks' => ['main' => 'stone_bricks', 'secondary' => 'cobblestone', 'accent' => 'deepslate_bricks',
                         'detail' => 'cracked_stone_bricks', 'lighting' => 'lantern'],
        ],
        'rustic-farm' => [
            'name' => 'Farmstead', 'style' => 'rustic',
            'desc' => 'Barns, stables and anything that should look worked-in.',
            'blocks' => ['main' => 'spruce_planks', 'secondary' => 'mud_bricks', 'accent' => 'stripped_spruce_log',
                         'detail' => 'hay_block', 'lighting' => 'lantern'],
        ],
        'modern-mono' => [
            'name' => 'Monochrome Modern', 'style' => 'modern',
            'desc' => 'White, grey, black and glass. Keep surfaces flat and lines straight.',
            'blocks' => ['main' => 'white_concrete', 'secondary' => 'gray_concrete', 'accent' => 'black_concrete',
                         'detail' => 'smooth_stone', 'lighting' => 'sea_lantern'],
        ],
        'modern-warm' => [
            'name' => 'Warm Modern', 'style' => 'modern',
            'desc' => 'Modern shapes, softened with timber so it does not feel clinical.',
            'blocks' => ['main' => 'white_concrete', 'secondary' => 'birch_planks', 'accent' => 'black_concrete',
                         'detail' => 'polished_andesite', 'lighting' => 'ochre_froglight'],
        ],
        'japanese-teahouse' => [
            'name' => 'Teahouse', 'style' => 'japanese',
            'desc' => 'Pale timber and plaster with dark trim. Detail comes from shape, not colour.',
            'blocks' => ['main' => 'white_terracotta', 'secondary' => 'stripped_birch_log', 'accent' => 'dark_oak_planks',
                         'detail' => 'bamboo_planks', 'lighting' => 'lantern'],
        ],
        'cherry-blossom' => [
            'name' => 'Cherry Blossom', 'style' => 'japanese',
            'desc' => 'Soft pink against near-black trim. Very high contrast — keep the pink to large flat areas.',
            'blocks' => ['main' => 'cherry_planks', 'secondary' => 'white_terracotta', 'accent' => 'polished_blackstone_bricks',
                         'detail' => 'stripped_birch_log', 'lighting' => 'lantern'],
        ],
        'fantasy-tower' => [
            'name' => 'Wizard Tower', 'style' => 'fantasy',
            'desc' => 'Cold stone with purple light. Works best tall and narrow.',
            'blocks' => ['main' => 'deepslate_bricks', 'secondary' => 'stone_bricks', 'accent' => 'purpur_block',
                         'detail' => 'mossy_stone_bricks', 'lighting' => 'soul_lantern'],
        ],
        'industrial-works' => [
            'name' => 'Ironworks', 'style' => 'industrial',
            'desc' => 'Exposed structure, copper roofs, plenty of chains and bars.',
            'blocks' => ['main' => 'polished_blackstone_bricks', 'secondary' => 'iron_block', 'accent' => 'exposed_copper',
                         'detail' => 'iron_bars', 'lighting' => 'redstone_lamp'],
        ],
        'cozy-cabin' => [
            'name' => 'Cosy Cabin', 'style' => 'cozy',
            'desc' => 'Small, warm and full of trapdoor detail.',
            'blocks' => ['main' => 'spruce_planks', 'secondary' => 'stripped_spruce_log', 'accent' => 'cobblestone',
                         'detail' => 'bookshelf', 'lighting' => 'campfire'],
        ],
        'deep-dark' => [
            'name' => 'Deep Delve', 'style' => 'underground',
            'desc' => 'For bases below Y=0. Add plenty of light or it reads as a black hole.',
            'blocks' => ['main' => 'deepslate_tiles', 'secondary' => 'cobbled_deepslate', 'accent' => 'tuff',
                         'detail' => 'polished_deepslate', 'lighting' => 'soul_lantern'],
        ],
        'nether-outpost' => [
            'name' => 'Nether Outpost', 'style' => 'nether',
            'desc' => 'Fireproof and dark. Crimson stands in for wood.',
            'blocks' => ['main' => 'polished_blackstone_bricks', 'secondary' => 'blackstone', 'accent' => 'crimson_planks',
                         'detail' => 'red_nether_bricks', 'lighting' => 'shroomlight'],
        ],
        'desert-town' => [
            'name' => 'Desert Town', 'style' => 'desert',
            'desc' => 'Flat roofs, thick walls, small windows.',
            'blocks' => ['main' => 'smooth_sandstone', 'secondary' => 'mud_bricks', 'accent' => 'orange_terracotta',
                         'detail' => 'white_terracotta', 'lighting' => 'lantern'],
        ],
        'ocean-temple' => [
            'name' => 'Ocean Temple', 'style' => 'ocean',
            'desc' => 'Sea greens with copper going green to match.',
            'blocks' => ['main' => 'prismarine_bricks', 'secondary' => 'dark_prismarine', 'accent' => 'oxidized_copper',
                         'detail' => 'calcite', 'lighting' => 'sea_lantern'],
        ],
    ];
}

/**
 * Role pools per style for the random generator.
 * Mains are calm, accents carry the colour, and lighting always
 * matches the mood — that is what stops random from looking wrong.
 */
function paletteRolePools(): array
{
    return [
        'medieval' => [
            'main'      => ['stone_bricks', 'cobblestone', 'andesite', 'stone'],
            'secondary' => ['spruce_planks', 'oak_planks', 'dark_oak_planks', 'bricks'],
            'accent'    => ['stripped_dark_oak_log', 'stripped_spruce_log', 'deepslate_bricks', 'brown_terracotta'],
            'detail'    => ['mossy_stone_bricks', 'cracked_stone_bricks', 'chiseled_stone_bricks', 'trapdoor'],
            'lighting'  => ['lantern', 'campfire', 'candle'],
        ],
        'rustic' => [
            'main'      => ['spruce_planks', 'oak_planks', 'mud_bricks', 'terracotta'],
            'secondary' => ['cobblestone', 'coarse_dirt', 'bricks', 'gravel'],
            'accent'    => ['stripped_spruce_log', 'stripped_dark_oak_log', 'brown_terracotta'],
            'detail'    => ['hay_block', 'barrel', 'trapdoor', 'mossy_cobblestone'],
            'lighting'  => ['lantern', 'campfire'],
        ],
        'modern' => [
            'main'      => ['white_concrete', 'light_gray_concrete', 'smooth_stone', 'polished_diorite'],
            'secondary' => ['gray_concrete', 'birch_planks', 'polished_andesite', 'smooth_basalt'],
            'accent'    => ['black_concrete', 'netherite_block', 'gray_stained_glass', 'red_concrete'],
            'detail'    => ['glass', 'tinted_glass', 'iron_block', 'quartz_bricks'],
            'lighting'  => ['sea_lantern', 'ochre_froglight', 'end_rod', 'redstone_lamp'],
        ],
        'japanese' => [
            'main'      => ['white_terracotta', 'calcite', 'birch_planks', 'polished_diorite'],
            'secondary' => ['stripped_birch_log', 'bamboo_planks', 'cherry_planks'],
            'accent'    => ['dark_oak_planks', 'polished_blackstone_bricks', 'black_terracotta'],
            'detail'    => ['stripped_spruce_log', 'trapdoor', 'flower_pot'],
            'lighting'  => ['lantern', 'candle'],
        ],
        'fantasy' => [
            'main'      => ['deepslate_bricks', 'stone_bricks', 'blackstone', 'prismarine_bricks'],
            'secondary' => ['mossy_stone_bricks', 'dark_prismarine', 'mangrove_planks', 'crimson_planks'],
            'accent'    => ['purpur_block', 'crying_obsidian', 'oxidized_copper', 'red_nether_bricks'],
            'detail'    => ['moss_block', 'chain', 'azalea_leaves'],
            'lighting'  => ['soul_lantern', 'shroomlight', 'glowstone', 'sea_lantern'],
        ],
        'industrial' => [
            'main'      => ['polished_blackstone_bricks', 'gray_concrete', 'deepslate_tiles', 'smooth_stone'],
            'secondary' => ['iron_block', 'blackstone', 'light_gray_concrete'],
            'accent'    => ['exposed_copper', 'copper_block', 'orange_concrete', 'yellow_concrete'],
            'detail'    => ['iron_bars', 'chain', 'scaffolding', 'tinted_glass'],
            'lighting'  => ['redstone_lamp', 'lantern', 'sea_lantern'],
        ],
        'cozy' => [
            'main'      => ['spruce_planks', 'oak_planks', 'white_terracotta', 'cherry_planks'],
            'secondary' => ['stripped_spruce_log', 'bricks', 'stone_bricks'],
            'accent'    => ['dark_oak_planks', 'brown_terracotta', 'mossy_cobblestone'],
            'detail'    => ['bookshelf', 'barrel', 'trapdoor', 'flower_pot'],
            'lighting'  => ['campfire', 'lantern', 'candle', 'shroomlight'],
        ],
        'underground' => [
            'main'      => ['deepslate_tiles', 'cobbled_deepslate', 'polished_deepslate', 'tuff'],
            'secondary' => ['deepslate_bricks', 'stone', 'smooth_basalt'],
            'accent'    => ['calcite', 'copper_block', 'moss_block'],
            'detail'    => ['chain', 'iron_bars', 'gravel'],
            'lighting'  => ['soul_lantern', 'lantern', 'ochre_froglight', 'glowstone'],
        ],
        'nether' => [
            'main'      => ['polished_blackstone_bricks', 'blackstone', 'nether_bricks'],
            'secondary' => ['basalt', 'netherrack', 'red_nether_bricks'],
            'accent'    => ['crimson_planks', 'warped_planks', 'gold_block'],
            'detail'    => ['chain', 'soul_soil', 'crying_obsidian'],
            'lighting'  => ['shroomlight', 'soul_lantern', 'glowstone'],
        ],
        'castle' => [
            'main'      => ['stone_bricks', 'deepslate_bricks', 'andesite'],
            'secondary' => ['cobblestone', 'polished_blackstone_bricks', 'stone'],
            'accent'    => ['cracked_stone_bricks', 'dark_oak_planks', 'brown_terracotta'],
            'detail'    => ['mossy_stone_bricks', 'iron_bars', 'chain', 'chiseled_stone_bricks'],
            'lighting'  => ['lantern', 'campfire'],
        ],
        'desert' => [
            'main'      => ['smooth_sandstone', 'sandstone', 'mud_bricks'],
            'secondary' => ['white_terracotta', 'orange_terracotta', 'terracotta'],
            'accent'    => ['red_terracotta', 'yellow_terracotta', 'brown_terracotta'],
            'detail'    => ['mud_brick_wall', 'trapdoor', 'chain'],
            'lighting'  => ['lantern', 'campfire'],
        ],
        'ocean' => [
            'main'      => ['prismarine_bricks', 'dark_prismarine', 'calcite'],
            'secondary' => ['smooth_stone', 'light_gray_concrete', 'cyan_terracotta'],
            'accent'    => ['oxidized_copper', 'weathered_copper', 'cyan_concrete'],
            'detail'    => ['glass', 'iron_bars', 'chain'],
            'lighting'  => ['sea_lantern', 'lantern'],
        ],
    ];
}

/** One sensible random palette for a style. */
function paletteRandom(string $style = ''): array
{
    $pools = paletteRolePools();
    if ($style === '' || !isset($pools[$style])) {
        $style = array_rand($pools);
    }
    $pick = [];
    foreach ($pools[$style] as $role => $options) {
        $pick[$role] = $options[array_rand($options)];
    }
    return ['style' => $style, 'blocks' => $pick];
}

function palettes_search_entries(): array
{
    $out = [];
    foreach (palettePresets() as $id => $p) {
        $out[] = [
            'id' => 'palette-' . $id, 'icon' => '🎨', 'title' => $p['name'] . ' palette', 'cat' => 'palette',
            'href' => 'knowledge.php?t=palette&preset=' . urlencode($id),
            'desc' => $p['desc'],
            'keywords' => 'palette blocks ' . $p['style'] . ' ' . strtolower($p['name']) . ' ' . implode(' ', $p['blocks']),
        ];
    }
    return $out;
}
