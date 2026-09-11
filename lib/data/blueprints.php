<?php
// ================================================
// lib/data/blueprints.php — 2D top-down build blueprints
// ------------------------------------------------
// One square = one Minecraft block. Each blueprint is a stack of
// Y-layers; each layer is a list of row strings, one character per
// block. Legend maps a character to a block id (looked up in
// lib/data/blocks.php so names/colours are never duplicated) or, for
// the handful of things the Material Library does not catalogue
// (water is a fluid, not a building material), an inline
// ['name' => ..., 'hex' => ...].
//
// Only builds/farms hand-verified against their own written
// description get a blueprint here — an idea or farm without one
// simply has no blueprint yet, shown as an honest empty state rather
// than a fabricated grid. See lib/data/ideas.php / farms.php for the
// source descriptions each blueprint below was checked against.
// ================================================
require_once __DIR__ . '/blocks.php';

function blueprintsAll(): array
{
    return [
        // Matches ideas.php 'starter-house': 9x7 footprint, stone base
        // course two blocks high blending into timber, a two-wide
        // doorway, recessed windows, one-block roof overhang.
        'starter-house' => [
            'idea' => 'starter-house',
            'title' => 'Starter House',
            'footprint' => ['x' => 9, 'z' => 7],
            'note' => 'The roof layers are wider than the walls — that is the one-block overhang.',
            'legend' => [
                'B' => 'stone_bricks',
                'W' => 'spruce_planks',
                'G' => 'glass_pane',
                'D' => 'spruce_door',
                'R' => 'spruce_planks',
            ],
            'layers' => [
                ['label' => 'Foundation', 'y' => 0, 'rows' => [
                    '...........',
                    '.BBBBBBBBB.',
                    '.BBBBBBBBB.',
                    '.BBBBBBBBB.',
                    '.BBBBBBBBB.',
                    '.BBBBBBBBB.',
                    '.BBBBBBBBB.',
                    '.BBBBBBBBB.',
                    '...........',
                ]],
                ['label' => 'Base walls (stone)', 'y' => 1, 'rows' => [
                    '...........',
                    '.BBBBBBBBB.',
                    '.B.......B.',
                    '.B.......B.',
                    '.B.......B.',
                    '.B.......B.',
                    '.B.......B.',
                    '.BBBBDDBBB.',
                    '...........',
                ]],
                ['label' => 'Base walls (stone, cont.)', 'y' => 2, 'rows' => [
                    '...........',
                    '.BBBBBBBBB.',
                    '.B.......B.',
                    '.B.......B.',
                    '.B.......B.',
                    '.B.......B.',
                    '.B.......B.',
                    '.BBBBDDBBB.',
                    '...........',
                ]],
                ['label' => 'Timber walls', 'y' => 3, 'rows' => [
                    '...........',
                    '.WWWWWWWWW.',
                    '.G.......G.',
                    '.W.......W.',
                    '.W.......W.',
                    '.G.......G.',
                    '.W.......W.',
                    '.WWWWWWWWW.',
                    '...........',
                ]],
                ['label' => 'Timber walls (top)', 'y' => 4, 'rows' => [
                    '...........',
                    '.WWWWWWWWW.',
                    '.G.......G.',
                    '.W.......W.',
                    '.W.......W.',
                    '.G.......G.',
                    '.W.......W.',
                    '.WWWWWWWWW.',
                    '...........',
                ]],
                ['label' => 'Roof (eave, overhangs the walls)', 'y' => 5, 'rows' => [
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                    'RRRRRRRRRRR',
                ]],
                ['label' => 'Roof ridge', 'y' => 6, 'rows' => [
                    '...........',
                    '....R......',
                    '....R......',
                    '....R......',
                    '....R......',
                    '....R......',
                    '....R......',
                    '....R......',
                    '...........',
                ]],
            ],
        ],

        // Matches farms.php 'wheat': 9x9 hydrated plot, water dead
        // centre, fence perimeter, hopper trench feeding a chest along
        // one edge, slabs over the trench to walk on.
        'wheat-farm' => [
            'farm' => 'wheat',
            'title' => 'Semi-Automatic Crop Farm',
            'footprint' => ['x' => 9, 'z' => 9],
            'note' => 'Water must be the exact centre of the 9x9 — every farmland tile needs to be within 4 blocks of it.',
            'legend' => [
                'F' => 'oak_fence',
                'T' => 'farmland',
                'P' => 'wheat',
                'H' => 'hopper',
                'C' => 'chest',
                'S' => 'oak_slab',
                'W' => ['name' => 'Water source', 'hex' => '#3d6fd6'],
            ],
            // Built from loops, not hand-typed ASCII, so the 9x9 plot is
            // exactly 9x9 and the water is exactly centred — both are
            // load-bearing facts here, not just visual approximations.
            'layers' => (function () {
                $farmRow  = 'F' . str_repeat('T', 9) . 'F';
                $waterRow = 'F' . str_repeat('T', 4) . 'W' . str_repeat('T', 4) . 'F';
                $ground = ['FFFFF.FFFFF'];
                for ($z = 1; $z <= 9; $z++) $ground[] = $z === 5 ? $waterRow : $farmRow;
                $ground[] = 'F' . str_repeat('H', 9) . 'C';

                $cropRow  = '.' . str_repeat('P', 9) . '.';
                $gapRow   = '.' . str_repeat('P', 4) . '.' . str_repeat('P', 4) . '.';
                $above = ['...........'];
                for ($z = 1; $z <= 9; $z++) $above[] = $z === 5 ? $gapRow : $cropRow;
                $above[] = '.' . str_repeat('S', 9) . '.';

                return [
                    ['label' => 'Ground — fence, farmland, hopper trench', 'y' => 0, 'rows' => $ground],
                    ['label' => 'Crops + walkway', 'y' => 1, 'rows' => $above],
                ];
            })(),
        ],

        // Matches farms.php 'sugar-cane' steps exactly: a 12-wide row,
        // water channel on one side, an observer/piston pair behind
        // each cane column (observer at the height of the 2nd segment,
        // piston at the height of the 1st so it clears the whole
        // stack), a hopper line under the water into a double chest,
        // and a roof over the observers. Each layer is tagged to the
        // farm's own numbered step — farms.php attaches the step's
        // title/text at render time so that prose lives in one place.
        'sugar-cane-farm' => [
            'farm' => 'sugar-cane',
            'title' => 'Automatic Sugar Cane Farm',
            'footprint' => ['x' => 12, 'z' => 3],
            'note' => 'The observer/piston column sits one row south of the cane, at the height of the 1st and 2nd cane segments respectively — shown as two views of that row since they are placed on different steps.',
            'legend' => [
                'S' => 'sand',
                'G' => 'sugar_cane',
                'O' => 'observer',
                'P' => 'piston',
                'H' => 'hopper',
                'X' => 'chest',
                'R' => ['name' => 'Building Blocks (any)', 'hex' => '#6b6b6b'],
                'W' => ['name' => 'Water channel', 'hex' => '#3d6fd6'],
            ],
            'layers' => [
                ['label' => 'Base — water channel + sand row', 'y' => 0, 'step' => 1, 'highlight' => ['W', 'S'], 'rows' => [
                    str_repeat('W', 12),
                    str_repeat('S', 12),
                    str_repeat('.', 12),
                ]],
                ['label' => 'Cane planted on the sand row', 'y' => 1, 'step' => 2, 'highlight' => ['G'], 'rows' => [
                    str_repeat('.', 12),
                    str_repeat('G', 12),
                    str_repeat('.', 12),
                ]],
                ['label' => 'Observers — height of the 2nd segment', 'y' => 2, 'step' => 3, 'highlight' => ['O'], 'facing' => 'N', 'rows' => [
                    str_repeat('.', 12),
                    str_repeat('.', 12),
                    str_repeat('O', 12),
                ]],
                ['label' => 'Pistons — height of the 1st segment', 'y' => 1, 'step' => 4, 'highlight' => ['P'], 'facing' => 'N', 'rows' => [
                    str_repeat('.', 12),
                    str_repeat('.', 12),
                    str_repeat('P', 12),
                ]],
                ['label' => 'Hopper line under the water, into a double chest', 'y' => -1, 'step' => 5, 'highlight' => ['H', 'X'], 'facing' => 'E', 'rows' => [
                    str_repeat('H', 12) . 'XX',
                ]],
                ['label' => 'Roof over the observers', 'y' => 4, 'step' => 6, 'highlight' => ['R'], 'rows' => [
                    str_repeat('R', 12),
                    str_repeat('R', 12),
                    str_repeat('R', 12),
                ]],
            ],
        ],

        // A single repeatable spawn-platform module for farms.php
        // 'creeper', at the 9x9 size its own step 1 text recommends.
        // This is one module, not the whole multi-platform farm — see
        // the blueprint's own note and the farm's "repeat and stack
        // it" guidance, so it never claims to be the full build.
        'creeper-farm-module' => [
            'farm' => 'creeper',
            'title' => 'Creeper Farm — one spawn platform module',
            'footprint' => ['x' => 9, 'z' => 9],
            'note' => 'One 9×9 platform module. Stack several of these (4 blocks apart) for a full farm — this blueprint intentionally does not invent an exact platform count.',
            'legend' => [
                'T' => 'trapdoor',
                'H' => 'hopper',
                'X' => 'chest',
                'F' => 'campfire',
                'R' => ['name' => 'Building Blocks (any)', 'hex' => '#6b6b6b'],
                'W' => ['name' => 'Water channel', 'hex' => '#3d6fd6'],
                'V' => ['name' => 'Drop shaft (open to the kill chamber below)', 'hex' => '#0d0d10'],
                'A' => ['name' => 'Tamed cat (in a boat)', 'hex' => '#d9853b'],
            ],
            // Every layer below shows only the material newly placed at
            // that step — nothing is redrawn across layers — so the
            // "whole build" material total (blueprintMaterialCounts())
            // never double-counts a block shown at more than one step.
            'layers' => (function () {
                $floor = [];       // y=0: the solid platform, with the hole cut through it
                $waterOnly = [];   // y=1: water sitting on top of the floor, nothing else
                for ($z = 0; $z < 9; $z++) {
                    $floor[] = $z === 4 ? substr_replace(str_repeat('R', 9), 'V', 4, 1) : str_repeat('R', 9);
                    if ($z === 4) {
                        $waterOnly[] = substr_replace(str_repeat('W', 9), '.', 4, 1); // the hole itself stays open, not water
                    } else {
                        $waterOnly[] = substr_replace(str_repeat('.', 9), 'W', 4, 1);
                    }
                }
                $catOnly = str_repeat('.', 4) . 'A' . str_repeat('.', 4);
                return [
                    ['label' => 'Platform floor, with the drop shaft opening', 'y' => 0, 'step' => 1, 'highlight' => ['R'], 'rows' => $floor],
                    ['label' => 'Ceiling — trapdoors hanging underneath, 2 blocks up', 'y' => 2, 'step' => 2, 'highlight' => ['T'], 'rows' => [
                        str_repeat('T', 9), str_repeat('T', 9), str_repeat('T', 9), str_repeat('T', 9), str_repeat('T', 9),
                        str_repeat('T', 9), str_repeat('T', 9), str_repeat('T', 9), str_repeat('T', 9),
                    ]],
                    ['label' => 'Water channels toward the centre hole', 'y' => 1, 'step' => 3, 'highlight' => ['W'], 'rows' => $waterOnly],
                    ['label' => 'Cat platform beside the funnel', 'y' => 1, 'step' => 4, 'highlight' => ['A'], 'rows' => [
                        str_repeat('.', 9), str_repeat('.', 9), str_repeat('.', 9), $catOnly, str_repeat('.', 9),
                        str_repeat('.', 9), str_repeat('.', 9), str_repeat('.', 9), str_repeat('.', 9),
                    ]],
                    ['label' => 'Kill chamber, far below the shaft', 'y' => -23, 'step' => 5, 'highlight' => ['R', 'F'], 'rows' => [
                        'RRR', 'RFR', 'RRR',
                    ]],
                    ['label' => 'Collection — hoppers into a double chest', 'y' => -24, 'step' => 6, 'highlight' => ['H', 'X'], 'facing' => 'E', 'rows' => [
                        'HHHXX',
                    ]],
                ];
            })(),
        ],
    ];
}

/** One blueprint by id, with block metadata resolved into the legend. */
function blueprintGet(string $id): ?array
{
    $bp = blueprintsAll()[$id] ?? null;
    if (!$bp) return null;

    $blocks = blocksAll();
    $legend = [];
    foreach ($bp['legend'] as $ch => $ref) {
        if (is_array($ref)) {
            $legend[$ch] = ['id' => null, 'name' => $ref['name'], 'hex' => $ref['hex']];
        } else {
            $row = $blocks[$ref] ?? null;
            $legend[$ch] = ['id' => $ref, 'name' => $row[0] ?? $ref, 'hex' => $row[2] ?? '#5a6478'];
        }
    }
    $bp['legend'] = $legend;
    return $bp;
}

/** Block counts across every layer, grouped by legend character (excludes '.' / air). */
function blueprintMaterialCounts(string $id): array
{
    $bp = blueprintGet($id);
    if (!$bp) return [];
    $counts = [];
    foreach ($bp['layers'] as $layer) {
        foreach ($layer['rows'] as $row) {
            foreach (str_split($row) as $ch) {
                if ($ch === '.') continue;
                $counts[$ch] = ($counts[$ch] ?? 0) + 1;
            }
        }
    }
    // Group by the resolved block (id, or name for the water-style
    // specials), not by legend character — two characters can be the
    // same block (e.g. wall timber and roof timber both spruce_planks)
    // and must not be double-listed.
    $grouped = [];
    foreach ($counts as $ch => $n) {
        $meta = $bp['legend'][$ch] ?? null;
        if (!$meta) continue;
        $key = $meta['id'] ?? $meta['name'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = ['id' => $meta['id'], 'name' => $meta['name'], 'hex' => $meta['hex'], 'count' => 0];
        }
        $grouped[$key]['count'] += $n;
    }
    $out = array_values($grouped);
    usort($out, fn($a, $b) => $b['count'] <=> $a['count']);
    return $out;
}

function blueprints_search_entries(): array
{
    $out = [];
    foreach (blueprintsAll() as $id => $b) {
        $href = isset($b['idea'])
            ? 'knowledge.php?t=ideas&idea=' . urlencode($b['idea']) . '#blueprint'
            : 'farms.php?farm=' . urlencode($b['farm']) . '#blueprint';
        $out[] = [
            'id' => 'blueprint-' . $id, 'icon' => '📐', 'title' => $b['title'] . ' blueprint', 'cat' => 'blueprint',
            'href' => $href, 'desc' => 'Layer-by-layer block grid — ' . $b['footprint']['x'] . '×' . $b['footprint']['z'],
            'keywords' => 'blueprint grid layers plan ' . strtolower($b['title']),
        ];
    }
    return $out;
}
