<?php
// ================================================
// lib/data/tips.php — Building Tips
// ------------------------------------------------
// Short, practical technique notes. Each tip states the problem,
// what to do about it, and something you can try immediately.
// ================================================

function tipsAll(): array
{
    return [
        // ── PLANNING ─────────────────────────────
        'footprint' => [
            'group' => 'Planning', 'title' => 'Mark the footprint before you build up',
            'summary' => 'Lay out the ground plan in a cheap block first, then walk it.',
            'body' => 'Outline the whole floor plan with any spare block and stand inside it. A room that looks generous on paper is often cramped once you add a staircase and furniture. Walking the space costs a minute and saves an hour of rebuilding.',
            'try' => 'Outline your plan, stand in the doorway, and check you can see across the room without a wall in the way.',
        ],
        'scale' => [
            'group' => 'Planning', 'title' => 'Build one size bigger than feels right',
            'summary' => 'Almost every first draft is too small.',
            'body' => 'Minecraft blocks are a metre each, so a 5×5 room is a small bathroom. Interiors need roughly 7×7 before furniture stops fighting for space, and a house reads better at 3 blocks of wall height than 2. If a build looks flat and toy-like, scale is usually the cause before detail is.',
            'try' => 'Add one block of wall height and two blocks of floor width to your next house and compare.',
        ],
        'odd-numbers' => [
            'group' => 'Planning', 'title' => 'Use odd widths for anything symmetrical',
            'summary' => 'Odd numbers give you a true centre block.',
            'body' => 'A 9-wide wall has a single middle column; an 8-wide wall does not. Doors, windows, chimneys and towers all sit better when there is an exact centre to line them up with. Save even widths for things that are deliberately off-centre.',
            'try' => 'Next time a door looks slightly wrong, count the wall width — it is probably even.',
        ],
        'silhouette' => [
            'group' => 'Planning', 'title' => 'Get the silhouette right before detailing',
            'summary' => 'If the shape is wrong, no amount of trim will save it.',
            'body' => 'Block out the whole form in one plain material and look at it from a distance and from the ground. The outline against the sky is what people notice first. Only once that reads well should you spend time on windows, beams and texture.',
            'try' => 'Build the whole shape in one block, fly 60 blocks away, and screenshot it. Fix what looks wrong at that distance.',
        ],

        // ── SHAPE ────────────────────────────────
        'depth' => [
            'group' => 'Shape', 'title' => 'Never leave a wall perfectly flat',
            'summary' => 'Push and pull sections in and out by one block.',
            'body' => 'A flat wall reads as a texture, not a building. Pull a section forward one block, recess a window bay, add a buttress or a chimney breast. Even a single block of movement creates shadow, and shadow is what makes a build look three-dimensional.',
            'try' => 'Pick the longest wall on your current build and pull the middle third forward by one block.',
        ],
        'roof-pitch' => [
            'group' => 'Shape', 'title' => 'Match roof height to the building below',
            'summary' => 'Oversized roofs are the most common beginner mistake.',
            'body' => 'A roof taller than the walls it sits on makes a house look like a tent. As a rough guide, keep the roof between a third and two-thirds the height of the walls. Stairs give a 45° pitch; using slabs and stairs together lets you make shallower, more elegant angles.',
            'try' => 'Count your wall height and roof height. If the roof is taller, take a layer off.',
        ],
        'overhang' => [
            'group' => 'Shape', 'title' => 'Let roofs overhang the walls',
            'summary' => 'One or two blocks of overhang instantly looks intentional.',
            'body' => 'Real roofs project past the walls to throw water clear. In Minecraft the same overhang casts a shadow line along the top of the wall, separating roof from body. Support it visibly with beams or brackets and you get free detail as well.',
            'try' => 'Extend your roof one block past each wall and add a stripped log bracket underneath.',
        ],
        'entrance' => [
            'group' => 'Shape', 'title' => 'Make the entrance obvious',
            'summary' => 'The door should be the thing your eye lands on.',
            'body' => 'Frame the doorway with a different material, recess it into a porch, raise it on a step, or put a lantern either side. A door flush in a plain wall disappears. Making it two blocks wide is the fastest single improvement to a build.',
            'try' => 'Widen your door to two blocks and add a small roof over it.',
        ],
        'windows' => [
            'group' => 'Shape', 'title' => 'Recess windows, do not just cut holes',
            'summary' => 'Set glass one block back from the wall face.',
            'body' => 'Glass placed flush with the wall looks like a sticker. Push it back a block and add a trapdoor shutter, a slab sill or a stair lintel, and the window reads as an opening with thickness. Grouping windows in rows also looks far better than scattering them.',
            'try' => 'Take one window, move the glass back a block, and put a trapdoor either side.',
        ],

        // ── MATERIALS ────────────────────────────
        'palette-size' => [
            'group' => 'Materials', 'title' => 'Three to five blocks, not fifteen',
            'summary' => 'Restraint reads as skill.',
            'body' => 'Pick a main block for most of the surface, a secondary for a third of it, and one or two accents for trim and detail. Adding more block types rarely makes a build richer — it usually makes it noisy. If a build feels chaotic, count the blocks in it.',
            'try' => 'List every block in your current build. If it is more than six, remove the least-used one.',
        ],
        'contrast' => [
            'group' => 'Materials', 'title' => 'Contrast your trim, not your walls',
            'summary' => 'Keep large areas calm and put the contrast on the edges.',
            'body' => 'Big surfaces want quiet, low-texture blocks. Save your darkest or brightest material for corners, beams, window frames and roof edges. That is how real buildings read too — plain fields of material with defined edges.',
            'try' => 'Outline the corners of your build with a block two shades darker than the walls.',
        ],
        'texture-mix' => [
            'group' => 'Materials', 'title' => 'Blend related blocks, not random ones',
            'summary' => 'Mix within a family: stone bricks with cracked and mossy stone bricks.',
            'body' => 'Speckling a wall with a related variant adds age and interest without changing the colour. Mixing unrelated blocks — cobblestone into oak planks — just looks like a mistake. A good rule is roughly 70% main, 20% variant, 10% second variant.',
            'try' => 'Replace one in five stone bricks with cracked stone bricks and step back.',
        ],
        'gradient' => [
            'group' => 'Materials', 'title' => 'Fade materials where they meet the ground',
            'summary' => 'Buildings look planted when the base is heavier than the top.',
            'body' => 'Use a darker, rougher block for the bottom one or two layers — cobblestone, deepslate, or terracotta under timber. Fade it upward by mixing the two for a layer. It suggests a stone foundation and stops the build looking like it is hovering.',
            'try' => 'Add a two-block stone base to a wooden house, blending the top layer.',
        ],

        // ── DETAIL ───────────────────────────────
        'trapdoors' => [
            'group' => 'Detail', 'title' => 'Trapdoors are the best detail block',
            'summary' => 'Shutters, awnings, panelling, furniture, roof edges.',
            'body' => 'Open trapdoors sit half a block off a surface, which is exactly what you need for shutters beside windows, an awning over a door, panelling on a plain wall, or the underside of a roof edge. They come in every wood and iron, so they fit any palette.',
            'try' => 'Put open trapdoors either side of every front-facing window.',
        ],
        'beams' => [
            'group' => 'Detail', 'title' => 'Show the structure',
            'summary' => 'Beams, posts and brackets explain how the building stands up.',
            'body' => 'Run stripped logs vertically at corners and horizontally where floors meet walls. Under overhangs and balconies, add a diagonal bracket from stairs. Buildings look believable when you can see what is holding them up.',
            'try' => 'Add a corner post of stripped log to each corner of your build.',
        ],
        'lighting-hidden' => [
            'group' => 'Detail', 'title' => 'Hide your light sources',
            'summary' => 'Torches on every wall ruin an otherwise good interior.',
            'body' => 'Put glowstone or froglights under carpet, behind a trapdoor, above a slab ceiling or inside a beam. Use lanterns and campfires where the light should be visible, and hide the rest. Warm light for cosy interiors, soul lanterns and sea lanterns for cold or modern ones.',
            'try' => 'Replace the torches in one room with light hidden under carpet, and add two lanterns for looks.',
        ],
        'interior-first' => [
            'group' => 'Detail', 'title' => 'Plan windows from the inside',
            'summary' => 'Rooms decide where windows go, not the façade.',
            'body' => 'If you place windows purely for the outside look, you end up with beds against glass and staircases cutting through openings. Sketch the room layout first, then put windows where a room actually wants light.',
            'try' => 'Stand inside each room and check every window is somewhere you would want one.',
        ],

        // ── ENVIRONMENT ──────────────────────────
        'terrain' => [
            'group' => 'Environment', 'title' => 'Build into the terrain, not on top of it',
            'summary' => 'Flattening everything is what makes builds look pasted on.',
            'body' => 'Let a corner of the build sink into a slope, add a retaining wall, or step the floor levels. Where the build meets the ground, break the straight line with rocks, coarse dirt and plants so there is no hard seam.',
            'try' => 'Add a few blocks of stone and coarse dirt where your walls meet the ground.',
        ],
        'paths' => [
            'group' => 'Environment', 'title' => 'Paths make a build feel used',
            'summary' => 'Nothing sells a settlement faster than a worn route to the door.',
            'body' => 'Run a path of dirt path, gravel or cobblestone from the entrance and let it wander rather than run straight. Vary the width, let grass creep into the edges, and connect it to wherever you actually walk.',
            'try' => 'Lay a two-wide dirt path from your door to your farm, with uneven edges.',
        ],
        'planting' => [
            'group' => 'Environment', 'title' => 'Plant in clumps, not evenly',
            'summary' => 'Nature is patchy — spread things unevenly and vary height.',
            'body' => 'Put groups of grass, flowers and bushes against walls and corners, and leave open ground elsewhere. Mixing leaf blocks, azalea and moss at different heights around the base of a build softens it far more than a neat row of flowers.',
            'try' => 'Add a clump of tall grass and two bushes in the corner where two walls meet.',
        ],

        // ── MISTAKES ─────────────────────────────
        'mistake-symmetry' => [
            'group' => 'Common mistakes', 'title' => 'Perfect symmetry looks lifeless',
            'summary' => 'Keep the main mass symmetrical, break the detail.',
            'body' => 'A symmetrical façade is fine, but if every window, plant and lantern mirrors exactly, the build feels artificial. Offset the chimney, add an extension on one side, or vary the planting. Small asymmetries make the symmetry read as deliberate.',
            'try' => 'Add a small lean-to or wood store to one side of your house only.',
        ],
        'mistake-box' => [
            'group' => 'Common mistakes', 'title' => 'The floating box',
            'summary' => 'A rectangle on flat ground with a flat roof and no base.',
            'body' => 'This is the default first build and every fix is quick: add a heavier base course, break one wall outward, put a pitched or stepped roof on, and cut the ground line with planting. Doing all four takes ten minutes and changes the build completely.',
            'try' => 'Take your oldest build and apply all four fixes to it.',
        ],
        'mistake-detail-spam' => [
            'group' => 'Common mistakes', 'title' => 'Detail everywhere is the same as detail nowhere',
            'summary' => 'Contrast needs calm areas to work against.',
            'body' => 'If every surface has trapdoors, buttons, chains and vines, the eye has nothing to rest on. Leave large plain sections and concentrate detail around entrances, roof lines and corners — the places people look.',
            'try' => 'Remove half the small detail from your busiest wall and see if it improves.',
        ],

        // ── REDSTONE ─────────────────────────────
        // Long-standing mechanics, not version-specific unless noted.
        'redstone-diagonal' => [
            'group' => 'Redstone', 'title' => 'Redstone dust never runs diagonally',
            'summary' => 'A line has to step through a shared edge, not just a shared corner.',
            'body' => 'Redstone dust only connects to a block it directly touches on a side, so a diagonal gap in a line simply breaks the signal. If a wire that looks continuous will not power the far end, look for a diagonal step in the layout, not a strength problem.',
            'try' => 'Trace your dust line block by block — a wire that "looks" straight often steps diagonally by one.',
        ],
        'redstone-repeater-lock' => [
            'group' => 'Redstone', 'title' => 'A powered repeater from the side locks it',
            'summary' => 'Side-powering a repeater freezes its current output, ignoring its input.',
            'body' => 'Repeaters have two uses: the front input delays and passes a signal, but powering one from the side latches it — its output stays exactly as it was the instant it got locked, and changes to the front input are ignored until the side power is removed. This is the basis of most redstone latches.',
            'try' => 'Power a lit repeater from the side, then toggle its input — the output will not change while the lock holds.',
        ],
        'redstone-observer' => [
            'group' => 'Redstone', 'title' => 'Observers pulse on a block update, not a power level',
            'summary' => 'They fire once, briefly, whenever the watched block changes — not while it stays powered.',
            'body' => 'An observer watches the single block directly in front of its arrow and gives a one-tick pulse whenever that block changes state (a crop growing, a piston extending, a door opening). It does not read redstone power at all, so wiring one next to a redstone wire and expecting it to react to power changes will not work.',
            'try' => 'If an observer seems to do nothing, check it is facing a block that actually changes, not a redstone wire.',
        ],

        // ── COMMAND & SEARCH (using this app) ────
        // About Minecraft CMD's own tools, not Minecraft mechanics — kept
        // accurate by construction.
        'app-search' => [
            'group' => 'Command & Search', 'title' => 'Press / to search from anywhere',
            'summary' => 'The search shortcut works on every page, not just Home.',
            'body' => 'Global search finds commands, items, enchantments, blocks, build ideas, farms and tips in one box, and each result says what kind of thing it is. It is usually faster than clicking through the nav bar for anything you already know the name of.',
            'try' => 'Press / right now and type the name of something you want to build or generate.',
        ],
        'app-doctor-explain' => [
            'group' => 'Command & Search', 'title' => 'Command Doctor also just explains',
            'summary' => 'You do not need a broken command to use it.',
            'body' => 'Paste any working command into Command Doctor\'s Explain mode to see what each part actually does — useful for understanding a command you copied from somewhere else before you run it on your own world.',
            'try' => 'Paste a command you are unsure about into the Explainer instead of just running it.',
        ],
        'app-library' => [
            'group' => 'Command & Search', 'title' => 'Save commands you will reuse',
            'summary' => 'The Library keeps a command, its name, category and Minecraft version together.',
            'body' => 'Any generated command can be saved to My Stuff with "Copy + Save" or "Library", which is worth doing for anything you will run again — a kit giveaway, a scoreboard setup, a farm-related /fill. Saved commands remember which Minecraft version they were built for.',
            'try' => 'Next time you generate a command you will reuse, save it instead of re-building it later.',
        ],
    ];
}

function tipsGroups(): array
{
    $g = [];
    foreach (tipsAll() as $t) $g[$t['group']] = true;
    return array_keys($g);
}

function tips_search_entries(): array
{
    $out = [];
    foreach (tipsAll() as $id => $t) {
        $out[] = [
            'id' => 'tip-' . $id, 'icon' => '💡', 'title' => $t['title'], 'cat' => 'tip',
            'href' => 'knowledge.php?t=tips#tip-' . $id,
            'desc' => $t['summary'],
            'keywords' => 'building tip ' . strtolower($t['group'] . ' ' . $t['title'] . ' ' . $t['summary']),
        ];
    }
    return $out;
}
