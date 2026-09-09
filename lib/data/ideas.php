<?php
// ================================================
// lib/data/ideas.php — Build Ideas
// ------------------------------------------------
// Each idea gives a concept, a realistic footprint, a palette
// preset to start from, an ordered build sequence, and the design
// decisions that make it work — enough to start building without
// copying a tutorial. 'steps' are construction-sequence advice
// (this app's own technique guidance, same voice as tips.php), not
// claims about Minecraft mechanics.
//
// Only 'starter-house' has a hand-verified blueprint so far
// (lib/data/blueprints.php) — every other idea intentionally has no
// 'blueprint' key rather than a fabricated one. See blueprints.php's
// header for why.
// ================================================
require_once __DIR__ . '/palettes.php';

function ideasAll(): array
{
    return [
        'starter-house' => [
            'title' => 'Starter House', 'icon' => '🏠', 'difficulty' => 'Easy', 'category' => 'Houses',
            'size' => '9 × 7, walls 4 high', 'palette' => 'medieval-village', 'blueprint' => 'starter-house',
            'concept' => 'The first night house done properly — small, warm, and good enough that you will not tear it down on day three.',
            'features' => [
                'Two-block-wide doorway with a small roof over it',
                'Stone base course two blocks high, blending into timber above',
                'Pitched stair roof with a one-block overhang',
                'Windows recessed one block, trapdoor shutters either side',
            ],
            'steps' => [
                'Mark and clear a 9×7 footprint, and walk it before building up',
                'Lay a two-block-high stone base course around the perimeter, leaving a two-wide gap for the door',
                'Build the timber walls above the base to a total wall height of 4, with recessed windows on the side walls',
                'Frame the doorway and add a small roof over it',
                'Build a pitched stair roof with a one-block overhang, then light the interior',
            ],
            'tips' => ['footprint', 'gradient', 'entrance', 'roof-pitch'],
            'note' => 'Nine by seven feels large at first and correct once a bed, furnace row and chest wall are in.',
        ],
        'medieval-house' => [
            'title' => 'Medieval Townhouse', 'icon' => '🏘', 'difficulty' => 'Medium', 'category' => 'Houses',
            'size' => '11 × 9, two floors', 'palette' => 'medieval-village',
            'concept' => 'Timber-framed house with an upper floor that overhangs the ground floor — the classic jettied street front.',
            'features' => [
                'Upper floor pushed out one block on the front, supported by stair brackets',
                'Exposed dark oak framing over white or light plaster infill',
                'Steep roof, roughly half the wall height',
                'Chimney breast pulled out from one side wall',
            ],
            'steps' => [
                'Lay the ground-floor footprint (11×9) and build walls to the first-floor height',
                'Push the upper floor out one block on the front and support it with stair brackets',
                'Frame the upper floor in exposed dark oak over light plaster infill',
                'Add a steep roof, roughly half the wall height',
                'Pull the chimney breast out from one side wall and finish with a stack',
            ],
            'tips' => ['depth', 'beams', 'overhang', 'mistake-symmetry'],
            'note' => 'The overhang is what makes it read as medieval rather than generic. Do not skip it.',
        ],
        'watchtower' => [
            'title' => 'Watchtower', 'icon' => '🗼', 'difficulty' => 'Easy', 'category' => 'Large Builds',
            'size' => '7 × 7 base, 18–24 tall', 'palette' => 'medieval-keep',
            'concept' => 'A tall, simple landmark. Good practice for vertical proportion and a genuinely useful navigation aid.',
            'features' => [
                'Base wider than the shaft — step in by one block after four or five blocks of height',
                'Corner pillars in a darker block running the full height',
                'Overhanging top platform with crenellations',
                'A beacon, lantern ring or campfire at the top so it reads at night',
            ],
            'steps' => [
                'Build a wide base (7×7) for four or five blocks of height',
                'Step the shaft in by one block and continue upward',
                'Run corner pillars in a darker block the full height for contrast',
                'Cap it with an overhanging platform and crenellations',
                'Add a beacon, lantern ring or campfire at the top so it reads at night',
            ],
            'tips' => ['silhouette', 'contrast', 'depth'],
            'note' => 'Towers fail when they are the same width all the way up. Vary it at least twice.',
        ],
        'bridge' => [
            'title' => 'Stone Bridge', 'icon' => '🌉', 'difficulty' => 'Medium', 'category' => 'Landscape',
            'size' => 'Span to suit, 5–7 wide', 'palette' => 'medieval-keep',
            'concept' => 'An arched crossing that looks like it carries weight, rather than a flat plank over a gap.',
            'features' => [
                'Arches built from stairs, widest at the centre of the span',
                'Piers that widen where they meet the water',
                'Raised walls or fences along both edges',
                'Lanterns on posts at regular intervals',
            ],
            'steps' => [
                'Set the span and build the piers first, widening them where they meet the water',
                'Build the arch from stairs, working from both piers to the centre so it meets in the middle',
                'Lay the deck across the top of the arch',
                'Add raised walls or fences along both edges',
                'Add lanterns on posts at regular intervals',
            ],
            'tips' => ['depth', 'beams', 'terrain'],
            'note' => 'Build the arch first and the deck second. Doing it the other way round never lines up.',
        ],
        'barn' => [
            'title' => 'Barn', 'icon' => '🚜', 'difficulty' => 'Easy', 'category' => 'Functional',
            'size' => '15 × 9, tall roof', 'palette' => 'rustic-farm',
            'concept' => 'A big simple volume with a dominant roof. The easiest way to make a farm look like a farm.',
            'features' => [
                'Large double door on the short end, three blocks wide',
                'Roof taller than usual — barns are the exception to the roof rule',
                'Hay bales, barrels and open trapdoor vents in the gable',
                'Fenced paddock attached to one side',
            ],
            'steps' => [
                'Lay a 15×9 footprint and build the low walls',
                'Frame a large double door, three blocks wide, on the short end',
                'Build the roof taller than usual — barns are the exception to the roof-pitch rule',
                'Add hay bales, barrels and open trapdoor vents in the gable',
                'Fence a paddock attached to one side',
            ],
            'tips' => ['scale', 'texture-mix', 'paths'],
            'note' => 'A barn roof can be up to the wall height and still look right, because the walls are low.',
        ],
        'storage-room' => [
            'title' => 'Storage Room', 'icon' => '📦', 'difficulty' => 'Easy', 'category' => 'Functional',
            'size' => '11 × 11 minimum', 'palette' => 'cozy-cabin',
            'concept' => 'Sorted storage that is pleasant to be in — the room you will spend the most time in all game.',
            'features' => [
                'Double chests in a continuous row with item frames above each',
                'A one-block walkway behind the wall for hoppers, if you sort automatically',
                'Hidden lighting so no torches interrupt the wall',
                'A crafting and smelting corner within reach of the chests',
            ],
            'steps' => [
                'Lay an 11×11 footprint, leaving a spare wall for future expansion',
                'Line one wall with double chests and item frames labelling each',
                'Run a walkway behind that wall for hoppers if you plan to sort automatically',
                'Hide the lighting so no torches interrupt the storage wall',
                'Add a crafting and smelting corner within reach of the chests',
            ],
            'tips' => ['lighting-hidden', 'interior-first', 'palette-size'],
            'note' => 'Leave a spare wall. Every storage room outgrows its first plan.',
        ],
        'workshop' => [
            'title' => 'Workshop', 'icon' => '🔨', 'difficulty' => 'Medium', 'category' => 'Functional',
            'size' => '13 × 11, high ceiling', 'palette' => 'industrial-works',
            'concept' => 'A working building — furnaces, anvils and brewing, with exposed structure and machinery.',
            'features' => [
                'High ceiling with visible roof beams',
                'Furnace bank set into a stone chimney breast',
                'Copper and iron detailing, chains hanging from the ceiling',
                'Large windows or a roof lantern so the interior is bright',
            ],
            'steps' => [
                'Lay a 13×11 footprint with a high ceiling',
                'Build a furnace bank into a stone chimney breast',
                'Add copper and iron detailing, with chains hanging from the ceiling',
                'Add large windows or a roof lantern so the interior stays bright',
                'Light a campfire under the chimney so smoke sells it from outside',
            ],
            'tips' => ['beams', 'contrast', 'lighting-hidden'],
            'note' => 'Smoke from a lit campfire under the chimney sells it from outside.',
        ],
        'tavern' => [
            'title' => 'Tavern', 'icon' => '🍺', 'difficulty' => 'Medium', 'category' => 'Large Builds',
            'size' => '15 × 11, two floors', 'palette' => 'medieval-village',
            'concept' => 'A busy, warm building with a big ground-floor room and small rooms above.',
            'features' => [
                'Bar counter from stairs and slabs along one wall',
                'Central fireplace or hearth with a chimney through the roof',
                'Hanging sign on a chain outside the door',
                'Upper windows smaller and more regular than the ground floor',
            ],
            'steps' => [
                'Lay the 15×11 footprint and build the big ground-floor room first',
                'Build a bar counter from stairs and slabs along one wall',
                'Add a central fireplace and run the chimney through the roof',
                'Build the smaller upper-floor rooms with smaller, more regular windows',
                'Hang a sign on a chain outside the door',
            ],
            'tips' => ['entrance', 'windows', 'lighting-hidden', 'mistake-detail-spam'],
            'note' => 'Ground floors want big openings, upper floors want small ones. That contrast reads as a real building.',
        ],
        'castle' => [
            'title' => 'Small Castle', 'icon' => '🏰', 'difficulty' => 'Hard', 'category' => 'Large Builds',
            'size' => '40 × 40 curtain wall', 'palette' => 'medieval-keep',
            'concept' => 'A compact keep with a walled courtyard. Scale is the whole challenge.',
            'features' => [
                'Curtain wall at least 8 blocks tall and 3 thick, with a walkway on top',
                'Corner towers wider than the wall, projecting outward',
                'Gatehouse with two towers, a recessed arch and a portcullis of iron bars',
                'Keep set at the back of the courtyard, taller than everything else',
            ],
            'steps' => [
                'Build the curtain wall first — at least 8 blocks tall, 3 thick, with a walkway on top',
                'Build the gatehouse with two towers, a recessed arch and an iron-bar portcullis',
                'Add corner towers that project outward, wider than the wall',
                'Build the keep at the back of the courtyard, taller than everything else',
                'Detail the walls last, once the scale reads correctly',
            ],
            'tips' => ['scale', 'silhouette', 'depth', 'contrast'],
            'note' => 'Build the wall and gatehouse first. If the wall is too thin, everything inside looks oversized.',
        ],
        'village' => [
            'title' => 'Village Extension', 'icon' => '🏡', 'difficulty' => 'Medium', 'category' => 'Large Builds',
            'size' => 'A cluster of 5–8 buildings', 'palette' => 'medieval-village',
            'concept' => 'Grow an existing village rather than starting from nothing — the paths and terrain are already there.',
            'features' => [
                'One shared palette across every building, varied by proportion not colour',
                'Buildings turned at slightly different angles along a curved path',
                'A well, market stall or notice board as a central focus',
                'Gardens, fences and washing lines filling the gaps between houses',
            ],
            'steps' => [
                'Pick an existing village site so the paths and terrain are already there',
                'Choose one shared palette and vary buildings by proportion, not colour',
                'Place 5–8 buildings at slightly different angles along a curved path',
                'Add a well, market stall or notice board as a central focus',
                'Fill the gaps between houses with gardens, fences and washing lines',
            ],
            'tips' => ['palette-size', 'paths', 'planting', 'mistake-symmetry'],
            'note' => 'The gaps between buildings matter more than the buildings. Fill them.',
        ],
        'underground-base' => [
            'title' => 'Underground Base', 'icon' => '⛏', 'difficulty' => 'Medium', 'category' => 'Functional',
            'size' => '21 × 21 × 9 excavated', 'palette' => 'deep-dark',
            'concept' => 'A carved-out hall below Y=0 that feels like a place rather than a hole.',
            'features' => [
                'Vaulted ceiling — arch the roof rather than leaving it flat',
                'Pillars every 5–7 blocks, even where they carry nothing',
                'Deliberate cold lighting; dark corners left dark on purpose',
                'A cave or ravine used as a natural entrance',
            ],
            'steps' => [
                'Find or dig a cave or ravine to use as a natural entrance',
                'Excavate one layer wider than you need, roughly 21×21×9',
                'Build walls inward from the excavation rather than leaving it raw',
                'Arch the ceiling instead of leaving it flat, with pillars every 5–7 blocks',
                'Light deliberately in cold tones, leaving some corners dark on purpose',
            ],
            'tips' => ['depth', 'lighting-hidden', 'contrast'],
            'note' => 'Excavate one layer wider than you need, then build walls inward. Raw excavation never looks right.',
        ],
        'modern-house' => [
            'title' => 'Modern House', 'icon' => '🏢', 'difficulty' => 'Medium', 'category' => 'Houses',
            'size' => '17 × 13, two levels', 'palette' => 'modern-warm',
            'concept' => 'Interlocking boxes, large glazing and a flat roof. Precision matters more than detail here.',
            'features' => [
                'Two or three offset rectangular volumes rather than one box',
                'Full-height glass on the best-facing wall, framed in a dark block',
                'Flat roof with a slab lip and a roof terrace',
                'A carport, pool or deck grounding it into the site',
            ],
            'steps' => [
                'Lay out two or three offset rectangular volumes rather than one box',
                'Build to the first-level height, then set back or push out the upper volume',
                'Add full-height glass on the best-facing wall, framed in a dark block',
                'Cap it with a flat roof, a slab lip and a roof terrace',
                'Ground it with a carport, pool or deck',
            ],
            'tips' => ['palette-size', 'contrast', 'mistake-detail-spam', 'terrain'],
            'note' => 'Modern builds fail from clutter, not from lack of it. Keep surfaces flat and edges crisp.',
        ],
        'japanese-house' => [
            'title' => 'Japanese House', 'icon' => '🎋', 'difficulty' => 'Hard', 'category' => 'Houses',
            'size' => '15 × 11, single storey', 'palette' => 'japanese-teahouse',
            'concept' => 'Low, wide and horizontal, with a heavy roof and a raised timber deck.',
            'features' => [
                'Deck raised one block, wrapping at least two sides',
                'Wide roof overhang — two blocks or more, with visible rafters',
                'Roof edges curved upward using stairs and slabs at the corners',
                'Paper-screen walls suggested with white terracotta and dark frames',
            ],
            'steps' => [
                'Lay a low, wide 15×11 footprint — height is what breaks this style fastest',
                'Raise a timber deck one block, wrapping at least two sides',
                'Build the walls, suggesting paper screens with white terracotta and dark frames',
                'Build a wide roof overhang of two blocks or more with visible rafters',
                'Curve the roof edges upward at the corners using stairs and slabs',
            ],
            'tips' => ['overhang', 'beams', 'contrast', 'planting'],
            'note' => 'Keep it low. Height is what breaks the style faster than anything else.',
        ],
        'fantasy-tower' => [
            'title' => 'Fantasy Tower', 'icon' => '🧙', 'difficulty' => 'Hard', 'category' => 'Large Builds',
            'size' => '9 × 9 base, 30+ tall', 'palette' => 'fantasy-tower',
            'concept' => 'A leaning, irregular tower that ignores structural sense on purpose.',
            'features' => [
                'Diameter changing several times up the height, wider at the top than the middle',
                'Sections rotated or offset so no two floors align',
                'Exterior stairs, balconies and bridges to other structures',
                'Strong coloured light from soul lanterns or a glowing roof',
            ],
            'steps' => [
                'Build the full outline in one block up the entire height first',
                'Change the diameter several times, wider at the top than the middle',
                'Rotate or offset sections so no two floors align',
                'Add exterior stairs, balconies and bridges to other structures',
                'Finish with strong coloured light from soul lanterns or a glowing roof',
            ],
            'tips' => ['silhouette', 'depth', 'contrast'],
            'note' => 'Build the outline in one block up the full height first. Fantasy shapes are hard to fix later.',
        ],
    ];
}

function ideasCategories(): array
{
    $cats = [];
    foreach (ideasAll() as $i) $cats[$i['category']] = true;
    return array_keys($cats);
}

function ideas_search_entries(): array
{
    $out = [];
    foreach (ideasAll() as $id => $i) {
        $out[] = [
            'id' => 'idea-' . $id, 'icon' => $i['icon'], 'title' => $i['title'], 'cat' => 'idea',
            'href' => 'knowledge.php?t=ideas&idea=' . urlencode($id),
            'desc' => $i['concept'],
            'keywords' => 'build idea what to build ' . strtolower($i['category'] . ' ' . $i['title'] . ' ' . $i['difficulty'] . ' ' . $i['concept']),
        ];
    }
    return $out;
}
