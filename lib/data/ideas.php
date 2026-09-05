<?php
// ================================================
// lib/data/ideas.php — Build Ideas
// ------------------------------------------------
// Each idea gives a concept, a realistic footprint, a palette
// preset to start from, and the design decisions that make it
// work — enough to start building without copying a tutorial.
// ================================================
require_once __DIR__ . '/palettes.php';

function ideasAll(): array
{
    return [
        'starter-house' => [
            'title' => 'Starter House', 'icon' => '🏠', 'difficulty' => 'Easy',
            'size' => '9 × 7, walls 4 high', 'palette' => 'medieval-village',
            'concept' => 'The first night house done properly — small, warm, and good enough that you will not tear it down on day three.',
            'features' => [
                'Two-block-wide doorway with a small roof over it',
                'Stone base course two blocks high, blending into timber above',
                'Pitched stair roof with a one-block overhang',
                'Windows recessed one block, trapdoor shutters either side',
            ],
            'tips' => ['footprint', 'gradient', 'entrance', 'roof-pitch'],
            'note' => 'Nine by seven feels large at first and correct once a bed, furnace row and chest wall are in.',
        ],
        'medieval-house' => [
            'title' => 'Medieval Townhouse', 'icon' => '🏘', 'difficulty' => 'Medium',
            'size' => '11 × 9, two floors', 'palette' => 'medieval-village',
            'concept' => 'Timber-framed house with an upper floor that overhangs the ground floor — the classic jettied street front.',
            'features' => [
                'Upper floor pushed out one block on the front, supported by stair brackets',
                'Exposed dark oak framing over white or light plaster infill',
                'Steep roof, roughly half the wall height',
                'Chimney breast pulled out from one side wall',
            ],
            'tips' => ['depth', 'beams', 'overhang', 'mistake-symmetry'],
            'note' => 'The overhang is what makes it read as medieval rather than generic. Do not skip it.',
        ],
        'watchtower' => [
            'title' => 'Watchtower', 'icon' => '🗼', 'difficulty' => 'Easy',
            'size' => '7 × 7 base, 18–24 tall', 'palette' => 'medieval-keep',
            'concept' => 'A tall, simple landmark. Good practice for vertical proportion and a genuinely useful navigation aid.',
            'features' => [
                'Base wider than the shaft — step in by one block after four or five blocks of height',
                'Corner pillars in a darker block running the full height',
                'Overhanging top platform with crenellations',
                'A beacon, lantern ring or campfire at the top so it reads at night',
            ],
            'tips' => ['silhouette', 'contrast', 'depth'],
            'note' => 'Towers fail when they are the same width all the way up. Vary it at least twice.',
        ],
        'bridge' => [
            'title' => 'Stone Bridge', 'icon' => '🌉', 'difficulty' => 'Medium',
            'size' => 'Span to suit, 5–7 wide', 'palette' => 'medieval-keep',
            'concept' => 'An arched crossing that looks like it carries weight, rather than a flat plank over a gap.',
            'features' => [
                'Arches built from stairs, widest at the centre of the span',
                'Piers that widen where they meet the water',
                'Raised walls or fences along both edges',
                'Lanterns on posts at regular intervals',
            ],
            'tips' => ['depth', 'beams', 'terrain'],
            'note' => 'Build the arch first and the deck second. Doing it the other way round never lines up.',
        ],
        'barn' => [
            'title' => 'Barn', 'icon' => '🚜', 'difficulty' => 'Easy',
            'size' => '15 × 9, tall roof', 'palette' => 'rustic-farm',
            'concept' => 'A big simple volume with a dominant roof. The easiest way to make a farm look like a farm.',
            'features' => [
                'Large double door on the short end, three blocks wide',
                'Roof taller than usual — barns are the exception to the roof rule',
                'Hay bales, barrels and open trapdoor vents in the gable',
                'Fenced paddock attached to one side',
            ],
            'tips' => ['scale', 'texture-mix', 'paths'],
            'note' => 'A barn roof can be up to the wall height and still look right, because the walls are low.',
        ],
        'storage-room' => [
            'title' => 'Storage Room', 'icon' => '📦', 'difficulty' => 'Easy',
            'size' => '11 × 11 minimum', 'palette' => 'cozy-cabin',
            'concept' => 'Sorted storage that is pleasant to be in — the room you will spend the most time in all game.',
            'features' => [
                'Double chests in a continuous row with item frames above each',
                'A one-block walkway behind the wall for hoppers, if you sort automatically',
                'Hidden lighting so no torches interrupt the wall',
                'A crafting and smelting corner within reach of the chests',
            ],
            'tips' => ['lighting-hidden', 'interior-first', 'palette-size'],
            'note' => 'Leave a spare wall. Every storage room outgrows its first plan.',
        ],
        'workshop' => [
            'title' => 'Workshop', 'icon' => '🔨', 'difficulty' => 'Medium',
            'size' => '13 × 11, high ceiling', 'palette' => 'industrial-works',
            'concept' => 'A working building — furnaces, anvils and brewing, with exposed structure and machinery.',
            'features' => [
                'High ceiling with visible roof beams',
                'Furnace bank set into a stone chimney breast',
                'Copper and iron detailing, chains hanging from the ceiling',
                'Large windows or a roof lantern so the interior is bright',
            ],
            'tips' => ['beams', 'contrast', 'lighting-hidden'],
            'note' => 'Smoke from a lit campfire under the chimney sells it from outside.',
        ],
        'tavern' => [
            'title' => 'Tavern', 'icon' => '🍺', 'difficulty' => 'Medium',
            'size' => '15 × 11, two floors', 'palette' => 'medieval-village',
            'concept' => 'A busy, warm building with a big ground-floor room and small rooms above.',
            'features' => [
                'Bar counter from stairs and slabs along one wall',
                'Central fireplace or hearth with a chimney through the roof',
                'Hanging sign on a chain outside the door',
                'Upper windows smaller and more regular than the ground floor',
            ],
            'tips' => ['entrance', 'windows', 'lighting-hidden', 'mistake-detail-spam'],
            'note' => 'Ground floors want big openings, upper floors want small ones. That contrast reads as a real building.',
        ],
        'castle' => [
            'title' => 'Small Castle', 'icon' => '🏰', 'difficulty' => 'Hard',
            'size' => '40 × 40 curtain wall', 'palette' => 'medieval-keep',
            'concept' => 'A compact keep with a walled courtyard. Scale is the whole challenge.',
            'features' => [
                'Curtain wall at least 8 blocks tall and 3 thick, with a walkway on top',
                'Corner towers wider than the wall, projecting outward',
                'Gatehouse with two towers, a recessed arch and a portcullis of iron bars',
                'Keep set at the back of the courtyard, taller than everything else',
            ],
            'tips' => ['scale', 'silhouette', 'depth', 'contrast'],
            'note' => 'Build the wall and gatehouse first. If the wall is too thin, everything inside looks oversized.',
        ],
        'village' => [
            'title' => 'Village Extension', 'icon' => '🏡', 'difficulty' => 'Medium',
            'size' => 'A cluster of 5–8 buildings', 'palette' => 'medieval-village',
            'concept' => 'Grow an existing village rather than starting from nothing — the paths and terrain are already there.',
            'features' => [
                'One shared palette across every building, varied by proportion not colour',
                'Buildings turned at slightly different angles along a curved path',
                'A well, market stall or notice board as a central focus',
                'Gardens, fences and washing lines filling the gaps between houses',
            ],
            'tips' => ['palette-size', 'paths', 'planting', 'mistake-symmetry'],
            'note' => 'The gaps between buildings matter more than the buildings. Fill them.',
        ],
        'underground-base' => [
            'title' => 'Underground Base', 'icon' => '⛏', 'difficulty' => 'Medium',
            'size' => '21 × 21 × 9 excavated', 'palette' => 'deep-dark',
            'concept' => 'A carved-out hall below Y=0 that feels like a place rather than a hole.',
            'features' => [
                'Vaulted ceiling — arch the roof rather than leaving it flat',
                'Pillars every 5–7 blocks, even where they carry nothing',
                'Deliberate cold lighting; dark corners left dark on purpose',
                'A cave or ravine used as a natural entrance',
            ],
            'tips' => ['depth', 'lighting-hidden', 'contrast'],
            'note' => 'Excavate one layer wider than you need, then build walls inward. Raw excavation never looks right.',
        ],
        'modern-house' => [
            'title' => 'Modern House', 'icon' => '🏢', 'difficulty' => 'Medium',
            'size' => '17 × 13, two levels', 'palette' => 'modern-warm',
            'concept' => 'Interlocking boxes, large glazing and a flat roof. Precision matters more than detail here.',
            'features' => [
                'Two or three offset rectangular volumes rather than one box',
                'Full-height glass on the best-facing wall, framed in a dark block',
                'Flat roof with a slab lip and a roof terrace',
                'A carport, pool or deck grounding it into the site',
            ],
            'tips' => ['palette-size', 'contrast', 'mistake-detail-spam', 'terrain'],
            'note' => 'Modern builds fail from clutter, not from lack of it. Keep surfaces flat and edges crisp.',
        ],
        'japanese-house' => [
            'title' => 'Japanese House', 'icon' => '🎋', 'difficulty' => 'Hard',
            'size' => '15 × 11, single storey', 'palette' => 'japanese-teahouse',
            'concept' => 'Low, wide and horizontal, with a heavy roof and a raised timber deck.',
            'features' => [
                'Deck raised one block, wrapping at least two sides',
                'Wide roof overhang — two blocks or more, with visible rafters',
                'Roof edges curved upward using stairs and slabs at the corners',
                'Paper-screen walls suggested with white terracotta and dark frames',
            ],
            'tips' => ['overhang', 'beams', 'contrast', 'planting'],
            'note' => 'Keep it low. Height is what breaks the style faster than anything else.',
        ],
        'fantasy-tower' => [
            'title' => 'Fantasy Tower', 'icon' => '🧙', 'difficulty' => 'Hard',
            'size' => '9 × 9 base, 30+ tall', 'palette' => 'fantasy-tower',
            'concept' => 'A leaning, irregular tower that ignores structural sense on purpose.',
            'features' => [
                'Diameter changing several times up the height, wider at the top than the middle',
                'Sections rotated or offset so no two floors align',
                'Exterior stairs, balconies and bridges to other structures',
                'Strong coloured light from soul lanterns or a glowing roof',
            ],
            'tips' => ['silhouette', 'depth', 'contrast'],
            'note' => 'Build the outline in one block up the full height first. Fantasy shapes are hard to fix later.',
        ],
    ];
}

function ideas_search_entries(): array
{
    $out = [];
    foreach (ideasAll() as $id => $i) {
        $out[] = [
            'id' => 'idea-' . $id, 'icon' => $i['icon'], 'title' => $i['title'], 'cat' => 'idea',
            'href' => 'knowledge.php?t=ideas&idea=' . urlencode($id),
            'desc' => $i['concept'],
            'keywords' => 'build idea what to build ' . strtolower($i['title'] . ' ' . $i['difficulty'] . ' ' . $i['concept']),
        ];
    }
    return $out;
}
