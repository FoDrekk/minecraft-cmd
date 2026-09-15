<?php
// ================================================
// lib/data/blocks.php — Material Library
// ------------------------------------------------
// Blocks a builder actually reaches for, with an approximate
// average colour so palettes can be previewed, plus the build
// styles each block suits. Fields:
//   [name, category, hex, styles, note]
// ================================================
require_once __DIR__ . '/../mc.php';

function blocksAll(): array
{
    return [
        // ── STONE ────────────────────────────────
        'stone'                    => ['Stone',                    'Stone', '#7d7d7d', 'modern,underground',        'Plain grey base. Smelt cobblestone to get it.'],
        'cobblestone'              => ['Cobblestone',              'Stone', '#7a7a7a', 'medieval,rustic,underground','Busy texture — best as an accent, not a whole wall.'],
        'mossy_cobblestone'        => ['Mossy Cobblestone',        'Stone', '#71806a', 'medieval,rustic,fantasy',   'Instant age. Craft with vines.'],
        'stone_bricks'             => ['Stone Bricks',             'Stone', '#7a7a7a', 'medieval,castle,fantasy',   'The default castle block. Break it up with cracked and mossy.'],
        'cracked_stone_bricks'     => ['Cracked Stone Bricks',     'Stone', '#767676', 'medieval,castle,ruins',     'Smelt stone bricks. Great for ruined walls.'],
        'mossy_stone_bricks'       => ['Mossy Stone Bricks',       'Stone', '#75806f', 'medieval,fantasy,ruins',    'Mix ~20% into stone brick walls for age.'],
        'chiselled_stone_bricks'   => ['Chiselled Stone Bricks',   'Stone', '#787878', 'medieval,castle',           'Use sparingly as a detail band.'],
        'smooth_stone'             => ['Smooth Stone',             'Stone', '#9f9f9f', 'modern,industrial',         'Clean and bright. Slabs make excellent trim.'],
        'andesite'                 => ['Andesite',                 'Stone', '#8a8a86', 'modern,rustic',             'Neutral filler that hides seams well.'],
        'polished_andesite'        => ['Polished Andesite',        'Stone', '#a0a2a0', 'modern,industrial',         'Light grey — a good secondary next to stone bricks.'],
        'diorite'                  => ['Diorite',                  'Stone', '#bdbdbd', 'modern',                    'Very bright and speckly. Polished reads much cleaner.'],
        'polished_diorite'         => ['Polished Diorite',         'Stone', '#cfcfcf', 'modern,japanese',           'Near-white. Handy where quartz feels too expensive.'],
        'granite'                  => ['Granite',                  'Stone', '#9a6250', 'rustic,fantasy',            'Warm pink-brown. Pairs with dark oak and brick.'],
        'polished_granite'         => ['Polished Granite',         'Stone', '#a5705d', 'rustic,modern',             'Cleaner warm tone for floors.'],
        'deepslate'                => ['Deepslate',                'Stone', '#4d4d51', 'underground,industrial',    'Very dark. Found below Y=0.'],
        'cobbled_deepslate'        => ['Cobbled Deepslate',        'Stone', '#4a4a4d', 'underground,castle',        'Dark, rough. Strong contrast with light stone.'],
        'polished_deepslate'       => ['Polished Deepslate',       'Stone', '#48484c', 'modern,underground',        'The dark equivalent of polished andesite.'],
        'deepslate_bricks'         => ['Deepslate Bricks',         'Stone', '#4a4a4e', 'castle,underground,fantasy','Excellent for dark castles and dwarven builds.'],
        'deepslate_tiles'          => ['Deepslate Tiles',          'Stone', '#3d3d40', 'modern,underground',        'Darkest tile texture in the game.'],
        'tuff'                     => ['Tuff',                     'Stone', '#6d6e64', 'underground,rustic',        'Muted olive-grey. Sits between stone and deepslate.'],
        'calcite'                  => ['Calcite',                  'Stone', '#dfdedb', 'modern,japanese',           'Off-white. Softer than quartz.'],
        'blackstone'               => ['Blackstone',               'Stone', '#2b262c', 'nether,industrial',         'Nether black stone. Great outline block.'],
        'polished_blackstone_bricks'=>['Polished Blackstone Bricks','Stone','#312c33', 'nether,castle,fantasy',     'Dark, formal brick. Superb with gold trim.'],
        'basalt'                   => ['Basalt',                   'Stone', '#4c4a50', 'nether,industrial',         'Directional pillar texture.'],
        'smooth_basalt'            => ['Smooth Basalt',            'Stone', '#48474d', 'modern,underground',        'Dark, flat, and very clean.'],
        'gravel'                   => ['Gravel',                   'Stone', '#7f7b78', 'rustic,underground',        'Paths and rough ground. Falls — support it.'],

        // ── WOOD ─────────────────────────────────
        'oak_planks'               => ['Oak Planks',               'Wood', '#b08a52', 'rustic,medieval,cozy',       'The default warm wood. Everywhere for a reason.'],
        'spruce_planks'            => ['Spruce Planks',            'Wood', '#7a5b36', 'rustic,medieval,cozy',       'Darker and calmer than oak. Great for roofs and beams.'],
        'birch_planks'             => ['Birch Planks',             'Wood', '#d7c185', 'modern,japanese,cozy',       'Pale and clean. Easily looks flat in bulk.'],
        'jungle_planks'            => ['Jungle Planks',            'Wood', '#b88762', 'fantasy,rustic',             'Pinkish brown, unusual and underused.'],
        'acacia_planks'            => ['Acacia Planks',            'Wood', '#ba6337', 'savanna,modern',             'Bold orange. Use as an accent, not a base.'],
        'dark_oak_planks'          => ['Dark Oak Planks',          'Wood', '#4b3621', 'medieval,fantasy,cozy',      'Deep brown — the classic timber-frame block.'],
        'mangrove_planks'          => ['Mangrove Planks',          'Wood', '#763a3a', 'fantasy,rustic',             'Red-brown. Sits nicely with crimson.'],
        'cherry_planks'            => ['Cherry Planks',            'Wood', '#e2b3a4', 'japanese,cozy,fantasy',      'Soft pink. Beautiful with white and dark trim.'],
        'bamboo_planks'            => ['Bamboo Planks',            'Wood', '#c2a84e', 'japanese,modern',            'Fine vertical grain. Excellent floors.'],
        'crimson_planks'           => ['Crimson Planks',           'Wood', '#6a344b', 'nether,fantasy',             'Purple-red. Does not burn.'],
        'warped_planks'            => ['Warped Planks',            'Wood', '#2b6b62', 'nether,fantasy',            'Teal. Does not burn.'],
        'oak_log'                  => ['Oak Log',                  'Wood', '#9c7a4a', 'rustic,medieval',            'Use stripped logs as posts and beams.'],
        'stripped_spruce_log'      => ['Stripped Spruce Log',      'Wood', '#8a6742', 'rustic,medieval,cozy',       'The best beam block in the game.'],
        'stripped_dark_oak_log'    => ['Stripped Dark Oak Log',    'Wood', '#503f2b', 'medieval,fantasy',           'Dark structural framing.'],
        'stripped_birch_log'       => ['Stripped Birch Log',       'Wood', '#c8b177', 'japanese,modern',            'Light framing and pillars.'],

        // ── BRICKS ───────────────────────────────
        'bricks'                   => ['Bricks',                   'Bricks', '#96604a', 'rustic,medieval,industrial','Warm red. Chimneys, bases, Victorian walls.'],
        'mud_bricks'               => ['Mud Bricks',               'Bricks', '#8c6d51', 'rustic,desert',            'Earthy. Ideal for desert and savanna builds.'],
        'nether_bricks'            => ['Nether Bricks',            'Bricks', '#2d161a', 'nether,castle',            'Near-black with a red tint.'],
        'red_nether_bricks'        => ['Red Nether Bricks',        'Bricks', '#460709', 'nether,fantasy',           'Deep blood red. Very strong accent.'],
        'prismarine_bricks'        => ['Prismarine Bricks',        'Bricks', '#63ab9a', 'ocean,fantasy',            'Sea green. Underwater and temple builds.'],
        'dark_prismarine'          => ['Dark Prismarine',          'Bricks', '#33604f', 'ocean,fantasy',            'Dark teal companion to prismarine.'],
        'end_stone_bricks'         => ['End Stone Bricks',         'Bricks', '#dbdf9e', 'end,fantasy',              'Pale yellow. Reads bright at distance.'],
        'quartz_bricks'            => ['Quartz Bricks',            'Bricks', '#e6e0d7', 'modern,japanese',          'Clean white brick pattern.'],
        'resin_bricks'             => ['Resin Bricks',             'Bricks', '#c86427', 'fantasy,rustic', 'Bright amber brick from creaking hearts.', 60],

        // ── CONCRETE ─────────────────────────────
        'white_concrete'           => ['White Concrete',           'Concrete', '#cfd5d6', 'modern,japanese',        'The flattest, cleanest white. Modern staple.'],
        'light_gray_concrete'      => ['Light Grey Concrete',      'Concrete', '#7d7d73', 'modern,industrial',      'Neutral mid grey.'],
        'gray_concrete'            => ['Grey Concrete',            'Concrete', '#36393d', 'modern,industrial',      'Near-black. Strong modern roof and trim.'],
        'black_concrete'           => ['Black Concrete',           'Concrete', '#08090d', 'modern,industrial',      'The darkest block. Use for outlines and windows.'],
        'blue_concrete'            => ['Blue Concrete',            'Concrete', '#2c2e8f', 'modern',                 'Saturated. A little goes a long way.'],
        'cyan_concrete'            => ['Cyan Concrete',            'Concrete', '#157788', 'modern,ocean',           'Good for pools and signage.'],
        'lime_concrete'            => ['Lime Concrete',            'Concrete', '#5ea818', 'modern',                 'Very loud — accents only.'],
        'red_concrete'             => ['Red Concrete',             'Concrete', '#8e2121', 'modern,industrial',      'Deep red. Works as brick replacement.'],
        'orange_concrete'          => ['Orange Concrete',          'Concrete', '#e06100', 'modern,industrial',      'Construction orange.'],
        'yellow_concrete'          => ['Yellow Concrete',          'Concrete', '#f1af15', 'modern',                 'Bright warning yellow.'],

        // ── TERRACOTTA ───────────────────────────
        'terracotta'               => ['Terracotta',               'Terracotta', '#985e43', 'rustic,desert',        'Muted clay. Excellent neutral filler.'],
        'white_terracotta'         => ['White Terracotta',         'Terracotta', '#d1b1a1', 'japanese,desert,cozy', 'Warm off-white. Kinder than concrete.'],
        'light_gray_terracotta'    => ['Light Grey Terracotta',    'Terracotta', '#876b62', 'rustic,modern',        'Dusty mauve-grey.'],
        'gray_terracotta'          => ['Grey Terracotta',          'Terracotta', '#3a2c25', 'rustic,industrial',    'Very dark brown-grey.'],
        'brown_terracotta'         => ['Brown Terracotta',         'Terracotta', '#4d3323', 'rustic,medieval',      'Rich soil brown. Great roof underlay.'],
        'red_terracotta'           => ['Red Terracotta',           'Terracotta', '#8e3c2e', 'rustic,desert',        'Muted brick red — softer than concrete.'],
        'orange_terracotta'        => ['Orange Terracotta',        'Terracotta', '#a05325', 'desert,rustic',        'Badlands staple.'],
        'yellow_terracotta'        => ['Yellow Terracotta',        'Terracotta', '#ba8523', 'desert,rustic',        'Sandy gold.'],
        'green_terracotta'         => ['Green Terracotta',         'Terracotta', '#4c532a', 'rustic,fantasy',       'Olive. Good roof colour.'],
        'cyan_terracotta'          => ['Cyan Terracotta',          'Terracotta', '#575c5c', 'modern,ocean',         'Desaturated blue-grey.'],
        'purple_terracotta'        => ['Purple Terracotta',        'Terracotta', '#764656', 'fantasy',              'Dusty plum.'],
        'black_terracotta'         => ['Black Terracotta',         'Terracotta', '#251610', 'modern,industrial',    'Warm black — softer than black concrete.'],

        // ── GLASS ────────────────────────────────
        'glass'                    => ['Glass',                    'Glass', '#d3e5ea', 'modern,japanese',           'Plain windows. Panes read thinner.'],
        'glass_pane'               => ['Glass Pane',               'Glass', '#d3e5ea', 'modern,medieval',           'Thin windows — better in small openings.'],
        'tinted_glass'             => ['Tinted Glass',             'Glass', '#37333d', 'modern,industrial',         'Blocks light but you can see through it.'],
        'black_stained_glass'      => ['Black Stained Glass',      'Glass', '#191919', 'modern,industrial',         'Dark window fill for modern builds.'],
        'gray_stained_glass'       => ['Grey Stained Glass',       'Glass', '#4c4c4c', 'modern',                    'Neutral tint for big windows.'],
        'brown_stained_glass'      => ['Brown Stained Glass',      'Glass', '#664c33', 'medieval,cozy',             'Warm, lamp-lit look from outside.'],

        // ── METAL ────────────────────────────────
        'iron_block'               => ['Block of Iron',            'Metal', '#d8d8d8', 'modern,industrial',         'Bright metal. Reads almost white.'],
        'copper_block'             => ['Block of Copper',          'Metal', '#c06d51', 'industrial,steampunk',      'Fresh copper — oxidises over time.'],
        'exposed_copper'           => ['Exposed Copper',           'Metal', '#a2705d', 'industrial,rustic',         'First oxidation stage. Wax to lock it.'],
        'weathered_copper'         => ['Weathered Copper',         'Metal', '#6e9077', 'fantasy,industrial',        'Green-brown. The nicest copper stage for roofs.'],
        'oxidised_copper'          => ['Oxidised Copper',          'Metal', '#53a486', 'fantasy,ocean',             'Full verdigris green. Iconic roof colour.'],
        'cut_copper'               => ['Cut Copper',               'Metal', '#c06d51', 'industrial,modern',         'Tighter pattern than plain copper.'],
        'gold_block'               => ['Block of Gold',            'Metal', '#f5cf42', 'fantasy,nether',            'Extremely loud. Trim only.'],
        'netherite_block'          => ['Block of Netherite',       'Metal', '#443f42', 'modern,industrial',         'Dark, expensive, subtle.'],
        'chain'                    => ['Chain',                    'Metal', '#3b3f4a', 'medieval,industrial',       'Hanging lanterns and supports.'],
        'iron_bars'                => ['Iron Bars',                'Metal', '#61666e', 'medieval,industrial',       'Windows, cages, railings.'],

        // ── LIGHTING ─────────────────────────────
        'lantern'                  => ['Lantern',                  'Lighting', '#e0a94a', 'medieval,rustic,cozy',   'Level 15. Hangs from blocks or chains.'],
        'soul_lantern'             => ['Soul Lantern',             'Lighting', '#4fd3e0', 'nether,fantasy',         'Level 10, cold blue light.'],
        'sea_lantern'              => ['Sea Lantern',              'Lighting', '#b4d6cd', 'ocean,modern',           'Level 15, full block. Great hidden lighting.'],
        'glowstone'                => ['Glowstone',                'Lighting', '#f7d27b', 'nether,fantasy',         'Level 15. Hide it behind carpets or slabs.'],
        'shroomlight'              => ['Shroomlight',              'Lighting', '#f19b3b', 'nether,fantasy,cozy',    'Level 15, warm orange. Beautiful in ceilings.'],
        'campfire'                 => ['Campfire',                 'Lighting', '#c8722d', 'rustic,cozy',            'Level 15 plus smoke. Douse it to keep the look.'],
        'candle'                   => ['Candle',                   'Lighting', '#e2d5b8', 'cozy,medieval',          'Low light, great on tables and shelves.'],
        'redstone_lamp'            => ['Redstone Lamp',            'Lighting', '#8b5a2e', 'modern,industrial',      'Switchable level 15.'],
        'ochre_froglight'          => ['Ochre Froglight',          'Lighting', '#eae5c0', 'modern,fantasy',         'Level 15 full block. Very bright and clean.'],
        'end_rod'                  => ['End Rod',                  'Lighting', '#e6e2d3', 'modern,end',             'Level 14. Slim modern lighting.'],

        // ── NATURE ───────────────────────────────
        'grass_block'              => ['Grass Block',              'Nature', '#79a54b', 'landscaping',              'Terrain surface.'],
        'dirt_path'                => ['Dirt Path',                'Nature', '#977f4b', 'rustic,landscaping',       'The cheapest way to make a build look lived-in.'],
        'coarse_dirt'              => ['Coarse Dirt',              'Nature', '#77553a', 'rustic,landscaping',       'Grass will not spread onto it — good for worn ground.'],
        'podzol'                   => ['Podzol',                   'Nature', '#5c4020', 'rustic,fantasy',           'Forest floor. Lets you plant mushrooms in light.'],
        'rooted_dirt'              => ['Rooted Dirt',              'Nature', '#906d51', 'landscaping',              'Nice under lush cave builds.'],
        'moss_block'               => ['Moss Block',               'Nature', '#5a7527', 'fantasy,landscaping',      'Bonemeal it to spread greenery fast.'],
        'sand'                     => ['Sand',                     'Nature', '#dbd3a0', 'desert,ocean',             'Falls — support it.'],
        'red_sand'                 => ['Red Sand',                 'Nature', '#bf6b28', 'desert',                   'Badlands ground.'],
        'sandstone'                => ['Sandstone',                'Nature', '#dbd3a0', 'desert,rustic',            'Desert building base.'],
        'smooth_sandstone'         => ['Smooth Sandstone',         'Nature', '#e0d8a8', 'desert,modern',            'Clean face — good for large walls.'],
        'snow_block'               => ['Snow Block',               'Nature', '#f0f7f7', 'snowy,modern',             'Brightest block available.'],
        'packed_ice'               => ['Packed Ice',               'Nature', '#a1c0f0', 'snowy,fantasy',            'Does not melt.'],
        'oak_leaves'               => ['Oak Leaves',               'Nature', '#4f7f2f', 'landscaping',              'Custom trees and hedges.'],
        'azalea_leaves'            => ['Azalea Leaves',            'Nature', '#6a8a3a', 'fantasy,landscaping',      'Denser, richer green.'],
        'hay_block'                => ['Hay Bale',                 'Nature', '#b79c1c', 'rustic,farm',              'Barns, stables, market stalls.'],

        // ── NETHER & END ─────────────────────────
        'netherrack'               => ['Netherrack',               'Nether', '#6c2c2c', 'nether',                   'Cheap nether filler.'],
        'crimson_nylium'           => ['Crimson Nylium',           'Nether', '#82322f', 'nether',                   'Red nether ground.'],
        'warped_nylium'            => ['Warped Nylium',            'Nether', '#2b7367', 'nether',                   'Teal nether ground.'],
        'soul_sand'                => ['Soul Sand',                'Nether', '#54402f', 'nether',                   'Slows you down. Bubble column source.'],
        'soul_soil'                => ['Soul Soil',                'Nether', '#4b3a2c', 'nether',                   'Like soul sand but no slowdown.'],
        'end_stone'                => ['End Stone',                'End',    '#dcdea0', 'end,fantasy',              'Pale End ground.'],
        'purpur_block'             => ['Purpur Block',             'End',    '#a97ba9', 'end,fantasy',              'Soft purple. Nice with end stone bricks.'],
        'obsidian'                 => ['Obsidian',                 'End',    '#100d1b', 'end,nether,industrial',    'Near-black with purple flecks.'],
        'crying_obsidian'          => ['Crying Obsidian',          'End',    '#25123c', 'nether,fantasy',           'Emits light level 10 and purple particles.'],

        // ── DECORATION ───────────────────────────
        'bookshelf'                => ['Bookshelf',                'Decoration', '#7b6242', 'cozy,medieval',        'Libraries and studies.'],
        'barrel'                   => ['Barrel',                   'Decoration', '#87642f', 'rustic,medieval',      'Storage that also reads as furniture.'],
        'flower_pot'               => ['Flower Pot',               'Decoration', '#8f5138', 'cozy,japanese',        'Small detail that lifts interiors.'],
        'item_frame'               => ['Item Frame',               'Decoration', '#9c7a4a', 'cozy,medieval',        'Wall detail, shop labels, hidden storage markers.'],
        'armor_stand'              => ['Armour Stand',             'Decoration', '#b9a77e', 'medieval,fantasy',     'Displays and statues.'],
        'scaffolding'              => ['Scaffolding',              'Decoration', '#c8a34e', 'industrial,rustic',    'Also the fastest way to build vertically.'],
        'mud_brick_wall'           => ['Mud Brick Wall',           'Decoration', '#8c6d51', 'desert,rustic',        'Fences and parapets.'],
        'trapdoor'                 => ['Trapdoor (any wood)',      'Decoration', '#9c7a4a', 'medieval,rustic,cozy', 'Shutters, awnings, furniture — the detail workhorse.'],

        // ── FUNCTIONAL ───────────────────────────
        'crafting_table'           => ['Crafting Table',           'Functional', '#8a6440', 'any',                  ''],
        'furnace'                  => ['Furnace',                  'Functional', '#767676', 'any',                  ''],
        'chest'                    => ['Chest',                    'Functional', '#8a6a35', 'any',                  ''],
        'hopper'                   => ['Hopper',                   'Functional', '#4a4a4e', 'any',                  'Moves items. Core of nearly every farm.'],
        'observer'                 => ['Observer',                 'Functional', '#5f5f5f', 'any',                  'Detects block updates in front of it.'],
        'dispenser'                => ['Dispenser',                'Functional', '#767676', 'any',                  'Fires items. Water buckets, arrows, fireworks.'],
        'note_block'               => ['Note Block',               'Functional', '#6b4b2c', 'any',                  ''],
        'command_block'            => ['Command Block',            'Functional', '#c19a6b', 'any',                  'Creative only. /give yourself one.'],
        'oak_fence'                => ['Oak Fence',                 'Functional', '#b08a52', 'any',                  'Stops mobs walking onto farmland — 1.5 blocks tall.'],
        'oak_slab'                 => ['Oak Slab',                  'Functional', '#b08a52', 'any',                  'Walkable cover for a hopper line underneath.'],
        'spruce_door'              => ['Spruce Door',               'Functional', '#7a5b36', 'any',                  'Two blocks tall — the doorway itself.'],

        // ── FARMING ──────────────────────────────
        'farmland'                 => ['Farmland',                 'Farming', '#5b3a21', 'any',                     'Tilled with a hoe. Needs water within 4 blocks to stay hydrated.'],
        'wheat'                    => ['Wheat (crop)',              'Farming', '#d4c04a', 'any',                     'Grows on farmland. Harvest at the fully golden stage.'],
    ];
}

/** Normalised rows: id, name, category, hex, styles[], note. */
function blocksList(string $version = ''): array
{
    $rank = $version ? mcVersion($version)['rank'] : PHP_INT_MAX;
    $out  = [];
    foreach (blocksAll() as $id => $row) {
        [$name, $cat, $hex, $styles, $note] = $row;
        $min = $row[5] ?? 0;
        if ($min > $rank) continue;
        $out[$id] = [
            'id' => $id, 'name' => $name, 'category' => $cat, 'hex' => $hex,
            'styles' => array_filter(explode(',', $styles)), 'note' => $note,
        ];
    }
    return $out;
}

function blocksCategories(): array
{
    $cats = [];
    foreach (blocksAll() as $row) $cats[$row[1]] = true;
    return array_keys($cats);
}

/** Blocks that suit a named build style. */
function blocksByStyle(string $style, string $version = ''): array
{
    return array_filter(blocksList($version), fn($b) => in_array($style, $b['styles'], true));
}

function blocks_search_entries(): array
{
    $out = [];
    foreach (blocksList() as $b) {
        $out[] = [
            'id' => 'block-' . $b['id'], 'icon' => '🪨', 'title' => $b['name'], 'cat' => 'block',
            'href' => 'knowledge.php?t=materials&q=' . urlencode($b['id']),
            'desc' => $b['category'] . ($b['note'] ? ' — ' . $b['note'] : ''),
            'keywords' => $b['id'] . ' ' . strtolower($b['name']) . ' ' . strtolower($b['category']) . ' ' . implode(' ', $b['styles']),
        ];
    }
    return $out;
}
