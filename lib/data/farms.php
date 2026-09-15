<?php
// ================================================
// lib/data/farms.php — Farm Lab guides
// ------------------------------------------------
// Every guide states the edition and the version its mechanics
// were checked against, because farm mechanics change between
// releases far more often than command syntax does.
//
// Fields: title, icon, tier, difficulty, edition, checked,
//         output, materials[], prep, steps[], how, testing,
//         troubles[{problem, causes[], fixes[]}]
// ================================================

function farmsAll(): array
{
    return [

// ═══════════ EARLY GAME ═══════════
'wheat' => [
    'title' => 'Semi-Automatic Crop Farm', 'icon' => '🌾', 'tier' => 'Early game',
    'difficulty' => 'Easy', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'blueprint' => 'wheat-farm',
    'output' => 'Wheat, carrots, potatoes or beetroot. One 9×9 plot fills a chest over a few harvests and feeds you indefinitely.',
    'materials' => [
        '1 Water Bucket', '2 Chests', '4 Hoppers', '1 Hoe',
        '~80 Building Blocks for the plot edge', '9 Dirt or Farmland blocks minimum',
        '1 Stack of the seed or crop you want', '2 Fence or wall blocks',
    ],
    'prep' => 'Pick flat ground with sky access, or light the area to level 15 if it is indoors. Crops need light level 9 or more to grow, and farmland must be within 4 blocks of water in every direction.',
    'steps' => [
        ['Dig the plot', 'Clear a 9×9 area and dig it one block deep. The walls of the pit stop water spreading past the plot.'],
        ['Place the water', 'Dig one more block down in the exact centre and place a water source there. It will hydrate the whole 9×9.'],
        ['Till the soil', 'Right-click every dirt block with a hoe. Hydrated farmland turns dark — if any stays pale, it is out of water range.'],
        ['Plant', 'Plant your seeds or crops on every farmland block. Leave the centre water block alone.'],
        ['Add the collection row', 'Dig a one-block trench along one edge, put hoppers in it feeding into a chest, and cover them with slabs so you can walk over.'],
        ['Fence it', 'Fence or wall the plot so mobs cannot trample the farmland. A single fence around the edge is enough.'],
    ],
    'how' => 'Farmland stays hydrated within 4 blocks of a water source, and hydrated soil grows crops several times faster. Crops advance a growth stage on random ticks, which is why the randomTickSpeed gamerule changes growth speed. Harvesting on top of hoppers means the drops fall straight into your chest instead of scattering.',
    'testing' => 'Come back after a few minutes. If crops have grown at all, the farm works. If the soil turned back to dirt, your water is too far away or something walked on it.',
    'troubles' => [
        ['Farmland turns back to dirt', ['Something is walking on it', 'No water within 4 blocks'],
         ['Fence the plot, or place a fence gate you jump over rather than a gap', 'Move the water source to the exact centre of the 9×9']],
        ['Crops never grow', ['Light level below 9', 'Crop was planted on unhydrated soil'],
         ['Add torches or open the roof — crops need light 9+, not full daylight', 'Check the farmland is dark brown, not pale']],
        ['Drops are not reaching the chest', ['Hoppers face the wrong way', 'Slabs are the top half instead of the bottom'],
         ['Sneak and right-click the chest with each hopper so it points into the chest', 'Place slabs on the lower half so items still fall through']],
    ],
],

'sugar-cane' => [
    'title' => 'Automatic Sugar Cane Farm', 'icon' => '🎋', 'tier' => 'Early game',
    'difficulty' => 'Easy', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Sugar cane for paper, books, rockets and sugar. A 12-wide row is plenty for one player.',
    'materials' => [
        '12 Observers', '12 Pistons', '12 Redstone Dust or a solid block row',
        '12 Sand or Dirt blocks', '1 Water Bucket', '12 Hoppers', '2 Chests',
        '~40 Building Blocks', '12 Sugar Cane',
    ],
    'prep' => 'Build it next to water, or bring a bucket. Sugar cane only grows on sand, red sand, dirt, grass, moss, mud or podzol that touches water on at least one side.',
    'steps' => [
        ['Lay the base', 'Make a row of sand or dirt with a one-block water channel running alongside it.'],
        ['Plant the cane', 'Plant sugar cane along the whole row.'],
        ['Place observers', 'Put an observer behind each cane block, at the height of the second cane block, facing the cane.'],
        ['Add pistons', 'Put a piston behind each observer, facing the cane, so the observer output powers it.'],
        ['Collection', 'Run a hopper line under the water channel into a double chest, and let the water push the broken cane along to it.'],
        ['Cover it', 'Roof the observers so cane cannot grow above them and jam the design.'],
    ],
    'how' => 'Sugar cane grows a block at a time on random ticks. The observer watches the block in front of it and pulses when a new cane appears, firing the piston, which breaks the cane above the base block. The water stream carries the drops to hoppers. The base block itself is never broken, so it regrows forever.',
    'testing' => 'Stand nearby for a minute. You should hear pistons firing and see cane arriving in the chest. Nothing happening usually means the observer is at the wrong height.',
    'troubles' => [
        ['Pistons never fire', ['Observer at the wrong height', 'Observer facing the wrong way'],
         ['The observer must watch the block where the second cane segment appears — one above the base', 'The observer face with the dot points at the cane']],
        ['Cane is dropping but not collected', ['Water is not reaching the drops', 'Hopper line does not run under the full row'],
         ['Extend the water channel to the whole row', 'Check every hopper points along the line, then into the chest']],
        ['Very slow output', ['Chunk is unloaded when you walk away', 'Low random tick speed'],
         ['Sugar cane only grows in loaded chunks — build it near where you play', 'Growth is genuinely slow. Make the row longer rather than waiting']],
    ],
],

'chicken' => [
    'title' => 'Automatic Chicken Cooker', 'icon' => '🍗', 'tier' => 'Early game',
    'difficulty' => 'Easy', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'A permanent supply of cooked chicken and feathers with no input after setup.',
    'materials' => [
        '1 Dispenser', '1 Hopper (plus 2 more for collection)', '2 Chests', '1 Comparator',
        '1 Redstone Repeater', '4 Redstone Dust', '1 Lava Bucket', '2 Slabs',
        '1 Trapdoor', '~30 Building Blocks', '4 Chickens or 4 Eggs',
    ],
    'prep' => 'Build it away from anything flammable — there is lava in the design. Two chambers stacked vertically: adults on top, chicks below.',
    'steps' => [
        ['Build the lower chamber', 'A 2×2 pit with a hopper floor feeding a chest. This is where chicks land and are cooked.'],
        ['Add the lava', 'Suspend lava one block above the chamber floor, held in place by a slab or trapdoor. Chicks are small enough to be cooked; the drop lands in the hoppers below.'],
        ['Build the adult pen above', 'A sealed 2×2 box with a hopper floor, directly over the lower chamber. Adults live here permanently.'],
        ['Wire the egg thrower', 'The pen hopper feeds a dispenser aimed down into the chamber. Put a comparator on the hopper into a repeater into the dispenser, so it fires when eggs build up.'],
        ['Add the chickens', 'Drop four chickens into the pen and seal it.'],
    ],
    'how' => 'Adult chickens lay an egg every 5–10 minutes. The eggs fall into the hopper under the pen; the comparator reads the hopper and pulses the dispenser, which throws the eggs. Roughly one egg in eight hatches a chick. Chicks are under one block tall, so the suspended lava cooks them while adults above stay safe, and the cooked chicken drops into the hoppers.',
    'testing' => 'Wait five minutes. You should hear the dispenser fire and see cooked chicken appear. No eggs at all means your adults are not in a loaded chunk.',
    'troubles' => [
        ['Adults are being cooked too', ['Lava is at the wrong height', 'Pen floor is not solid'],
         ['The lava must only reach the chick chamber. Adults must be fully separated by a solid floor', 'Replace the pen floor with hoppers only — no gaps to the lava']],
        ['Dispenser never fires', ['Comparator facing the wrong way', 'Not enough eggs in the hopper yet'],
         ['The comparator arrow points away from the hopper toward the repeater', 'It needs a few eggs before the signal is strong enough. Wait longer']],
        ['Chicks escape', ['Chamber is not sealed'], ['Close every gap — chicks fit through spaces adults cannot']],
    ],
],

// ═══════════ MID GAME ═══════════
'iron' => [
    'title' => 'Iron Farm', 'icon' => '⚙️', 'tier' => 'Mid game',
    'difficulty' => 'Medium', 'edition' => 'Java', 'checked' => '1.21 – 26.x (Java)',
    'output' => 'Iron ingots and poppies, continuously. A single-cell farm produces enough iron for hoppers, rails and armour without ever mining again.',
    'materials' => [
        '3 Villagers', '3 Beds', '1 Zombie (name-tagged so it never despawns)',
        '~120 Building Blocks', '2 Water Buckets', '8 Hoppers', '2 Chests',
        '~20 Slabs', '1 Nametag', '4 Trapdoors', 'Lava Bucket or Campfires for the kill chamber',
        '1 Boat or Minecart to transport villagers',
    ],
    'prep' => 'Build it at least 64 blocks from any village, or the golems will spawn there instead. Java only — Bedrock golem spawning uses completely different rules (20 beds, 10 villagers) and this design will not work there.',
    'steps' => [
        ['Build the villager pod', 'A sealed 3×1 platform with three beds, high in the air or far from other villages. Villagers must be able to reach and sleep in their beds.'],
        ['Bring the villagers', 'Move three villagers in by boat or minecart. Let them sleep at least once — the farm will not spawn golems until they have.'],
        ['Add the zombie', 'Put a name-tagged zombie in a sealed 1×1 cell where the villagers can see it but it cannot reach them. Glass or a gap with a solid floor works.'],
        ['Build the spawn platform', 'Leave a clear 16×6×16 area below and around the village centre, with the spawn floor about 2 blocks under the villagers. Golems need a 3×3 space to appear in.'],
        ['Add the collection', 'Water streams push golems into a fall chute or a lava blade. Hoppers under the kill point feed a double chest.'],
        ['Seal everything', 'Slab or light every surface where hostile mobs could spawn, or your farm will fill with zombies.'],
    ],
    'how' => 'A Java village with at least three villagers who have slept and worked in the last day will attempt to spawn an iron golem every few seconds, provided at least one villager is panicking. The name-tagged zombie keeps them permanently panicked. Golems appear in a 16×6×16 box centred on the village meeting point, which is worked out from the beds and workstations — so controlling where the beds are controls where the golems land.',
    'testing' => 'Watch the spawn platform for two or three minutes. If no golem appears, check that all three villagers have actually slept and that they can see the zombie.',
    'troubles' => [
        ['No golems spawn at all', ['Villagers have not slept', 'They cannot see the zombie', 'Too close to another village', 'No valid 3×3 spawn space'],
         ['Wait for a night cycle and confirm each villager links to a bed', 'The zombie must be within 16 blocks and in line of sight', 'Move at least 64 blocks from any other village, or 100+ to be safe', 'Clear the spawn platform — golems need a 3-block-tall gap']],
        ['Golems spawn in the wrong place', ['The village centre is not where you think'],
         ['The centre is calculated from beds and workstations. Remove any stray beds nearby and rebuild the pod tightly']],
        ['Hostile mobs are filling the farm', ['Unlit surfaces inside the spawn area'],
         ['Slab or light every flat surface in and around the farm. Slabs are better than torches because they cannot be knocked off']],
        ['It worked, then stopped', ['A villager died or the zombie despawned'],
         ['Name-tag the zombie so it never despawns, and check all three villagers are alive']],
    ],
],

'villager-crop' => [
    'title' => 'Villager Crop Farm', 'icon' => '🧑‍🌾', 'tier' => 'Mid game',
    'difficulty' => 'Medium', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Fully automatic carrots, potatoes or wheat. One farmer villager keeps a plot planted and harvested while you are elsewhere.',
    'materials' => [
        '1 Villager (will become a Farmer)', '1 Composter', '~60 Building Blocks',
        '1 Water Bucket', '12 Hoppers', '2 Chests', '~30 Farmland worth of dirt',
        '1 Bed (optional but keeps the villager healthy)', 'A stack of the crop to seed the plot',
    ],
    'prep' => 'The villager must be able to reach every farmland block. Keep the plot compact — a 9×9 with the villager standing in a trench along one side works well.',
    'steps' => [
        ['Build the plot', 'A standard 9×9 hydrated farmland plot, as in the basic crop farm.'],
        ['Cut a trench', 'Dig a one-block trench across the middle of the plot so the villager can reach both halves.'],
        ['Add the composter', 'Place a composter in the trench. This makes the villager a Farmer and is their workstation.'],
        ['Line the trench with hoppers', 'Hoppers under the trench feed a double chest. The villager throws surplus food, and it lands here.'],
        ['Add the villager', 'Drop the villager into the trench and seal it so they cannot wander off.'],
        ['Seed the plot', 'Plant the first crop yourself and give the villager a stack of the same crop to start.'],
    ],
    'how' => 'Farmer villagers harvest fully grown crops and replant them automatically. Their inventory is limited, so once it is full they throw surplus food on the ground, where your hoppers collect it. The composter is the workstation that makes them a farmer and keeps them working.',
    'testing' => 'Watch for a minute — the villager should walk the plot and break grown crops. If they stand still, they have not linked to the composter.',
    'troubles' => [
        ['Villager will not farm', ['Not linked to the composter', 'Cannot reach the crops', 'Already has a different profession'],
         ['Break and replace the composter next to them while they are close', 'Make sure the trench reaches within one block of every farmland tile', 'Only unemployed villagers or existing farmers will work — a villager with a locked trade cannot change job']],
        ['Nothing reaches the chest', ['The villager is not full yet'],
         ['Farmers only throw surplus once their inventory fills. Give it 10–15 minutes of loaded time']],
        ['Villager keeps escaping', ['Trench is not sealed'], ['Cover the trench with slabs or trapdoors — villagers cannot path through them']],
    ],
],

'bamboo' => [
    'title' => 'Bamboo & Fuel Farm', 'icon' => '🎍', 'tier' => 'Mid game',
    'difficulty' => 'Easy', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Bamboo for scaffolding, planks and — crafted into blocks — a renewable furnace fuel that never runs out.',
    'materials' => [
        '8 Observers', '8 Pistons', '8 Bamboo', '8 Dirt or Sand',
        '16 Hoppers', '2 Chests', '~50 Building Blocks', '1 Water Bucket',
    ],
    'prep' => 'Bamboo grows tall and fast, so this needs vertical space or a piston at the right height to keep breaking it. Light it if it is indoors — bamboo needs light level 9.',
    'steps' => [
        ['Plant a row', 'Plant bamboo along a row of dirt or sand with a water channel beside it.'],
        ['Set the break height', 'Place a piston facing each bamboo stalk, 2–3 blocks above the base.'],
        ['Add observers', 'Put an observer above each piston watching the block where bamboo will grow into, wired so it fires the piston.'],
        ['Collect', 'Water carries the drops to a hopper line and into a double chest.'],
        ['Optional — auto smelt', 'Feed the chest into a crafter or a manual crafting step to turn bamboo into blocks, then into a furnace array as fuel.'],
    ],
    'how' => 'Bamboo grows extremely quickly on random ticks — much faster than sugar cane. The observer detects each new segment and fires the piston, which breaks everything above the base. Two bamboo blocks smelt more items than a piece of coal per unit of effort, which is why it is the standard renewable fuel.',
    'testing' => 'Bamboo grows fast enough that you should see collection within a minute of standing nearby.',
    'troubles' => [
        ['Bamboo grows past the piston', ['Piston height is wrong'], ['Lower the piston so it breaks the stalk before it outgrows the observer']],
        ['Nothing grows', ['Light level too low'], ['Bamboo needs light 9+. Add torches or open the roof']],
        ['Farm jams', ['Bamboo is growing into the redstone'], ['Roof over the observer and piston row with solid blocks']],
    ],
],

'mob-tower' => [
    'title' => 'General Mob & XP Farm', 'icon' => '💀', 'tier' => 'Mid game',
    'difficulty' => 'Medium', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Bones, arrows, string, rotten flesh and steady XP. The most useful all-round farm in the mid game.',
    'materials' => [
        '~600 Building Blocks', '4 Water Buckets', '12 Hoppers', '2 Chests',
        '~40 Slabs', '~20 Signs or Trapdoors', '~30 Ladders (optional descent)',
        'Blocks for a 24-block drop chute',
    ],
    'prep' => 'Build it over an ocean or high in the sky. The spawn rate depends on how few other places mobs can spawn nearby, so a platform in open water massively outperforms one built on land next to caves.',
    'steps' => [
        ['Build the platforms', 'Stack 2–4 spawning platforms, each about 20×20, with 3 blocks of clear space above each. Space them at least 4 blocks apart vertically.'],
        ['Make them dark', 'Roof each platform. Mobs need light level 0 to spawn, so no light may reach the floor.'],
        ['Add water channels', 'Run water from each edge toward a central hole so mobs are washed into the chute.'],
        ['Build the drop chute', 'A 1×1 shaft exactly 22–23 blocks tall above the kill floor. That drop leaves most mobs at half a heart, so one punch kills and you get the XP.'],
        ['Kill floor and collection', 'Hoppers under the landing spot feed a double chest. Stand at the chute exit to hit mobs as they land.'],
        ['Light everything else', 'Light or slab every cave and surface within 128 blocks below, or those places will steal your spawns.'],
    ],
    'how' => 'Hostile mobs spawn on solid blocks in light level 0, between 24 and 128 blocks from the player. Standing inside that band while every other nearby spawnable surface is lit forces almost all spawns onto your platforms. A 23-block fall does 21 hearts of damage — enough to leave most mobs on their last half heart, so your hit gets the XP and the looting bonus.',
    'testing' => 'AFK at the kill spot for five minutes. If almost nothing arrives, the problem is nearly always caves nearby, not the farm.',
    'troubles' => [
        ['Very few mobs', ['Caves nearby are taking the spawns', 'You are standing too close or too far', 'Platforms are not fully dark'],
         ['Light or slab every cave within 128 blocks, or move the farm over open ocean', 'Stand 24–32 blocks from the platforms — closer than 24 stops spawns entirely', 'Check for light leaking in at the edges, especially at dawn']],
        ['Mobs are not dying', ['Drop is too short or too long'],
         ['22–23 blocks of fall leaves mobs at half a heart. Shorter and they survive; longer and you lose the XP']],
        ['Mobs will not go down the chute', ['Water is not reaching the hole', 'Something is blocking the flow'],
         ['Water flows exactly 8 blocks — you may need a second source part way', 'Signs or open trapdoors hold water back where you need a step down']],
        ['Creepers are exploding in the farm', ['They are being hit by something, not falling'],
         ['A fall-damage farm will not set off creepers. If they are exploding, something is attacking them']],
    ],
],

// ═══════════ ADVANCED ═══════════
'creeper' => [
    'title' => 'Creeper Farm', 'icon' => '💥', 'tier' => 'Advanced',
    'difficulty' => 'Hard', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Gunpowder for rockets and TNT. Roughly 500–1000 gunpowder per hour from a decent design.',
    // The only farm here with a stated numeric rate, so the only one
    // that gets a calculator (see the Farm Calculator UI in farms.php) —
    // every other farm's output is deliberately left qualitative rather
    // than inventing a number for it.
    'rate' => ['unit' => 'gunpowder', 'low' => 500, 'high' => 1000, 'per' => 'hour'],
    'materials' => [
        '~800 Building Blocks', '~120 Trapdoors', '4 Water Buckets',
        '12 Hoppers', '2 Chests', '1 Cat (tamed, or an ocelot in a boat)',
        '~40 Slabs', 'Blocks for a 24-block drop or a campfire kill chamber',
    ],
    'prep' => 'Best built over an ocean, high above sea level. You need a cat — bring one in a boat or breed one from a village.',
    'steps' => [
        ['Build the spawn platforms', 'Large flat platforms, each with exactly 2 blocks of vertical space, roofed over.'],
        ['Trapdoor the ceiling', 'Place trapdoors on the underside of the ceiling across the whole platform. This is the key step.'],
        ['Add water channels', 'Water flows from the edges toward a central drop hole.'],
        ['Place the cat', 'Put a tamed cat in a boat or on a block at the centre, where creepers being funnelled will pass close to it.'],
        ['Kill chamber', 'A 23-block drop, or campfires under the landing point. Campfires kill creepers without destroying the drops.'],
        ['Collect', 'Hoppers under the kill point into a double chest.'],
    ],
    'how' => 'Creepers are 1.7 blocks tall; zombies and skeletons are 2. Hanging trapdoors from the ceiling cuts the usable headroom to just under 2 blocks, so only creepers (and spiders, which need a 3×3 floor) can spawn. Cats frighten creepers, which makes them flee toward the collection point instead of milling about. Campfires are a good kill method because they do not blow anything up.',
    'testing' => 'Check the chest after ten minutes of AFK. Gunpowder with no bones or rotten flesh mixed in means the trapdoor filter is working.',
    'troubles' => [
        ['Zombies and skeletons are spawning', ['Trapdoors are missing or placed on the floor'],
         ['Trapdoors go on the ceiling, hanging down. Cover the whole platform — one gap is enough to let 2-block mobs in']],
        ['Creepers are exploding', ['They are being hit by a player or damaged by something other than the fall'],
         ['Use fall damage or campfires only, and never stand within 3 blocks of a creeper in the farm']],
        ['Rates are poor', ['Other spawnable surfaces nearby', 'Standing outside the 24–128 block band'],
         ['Build over open ocean and light everything below', 'Move your AFK spot to 24–32 blocks from the platforms']],
        ['Creepers will not move to the collection point', ['The cat is too far away, or is not a cat'],
         ['Creepers flee from cats within about 6 blocks. An ocelot is not the same as a tamed cat']],
    ],
],

'gold' => [
    'title' => 'Nether Gold Farm', 'icon' => '🪙', 'tier' => 'Advanced',
    'difficulty' => 'Hard', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Gold nuggets, gold swords and XP at a very high rate. One of the best XP farms in the game.',
    'materials' => [
        '~1500 Blocks that do not burn (blackstone, nether bricks, cobblestone)',
        '4 Water Buckets (they will evaporate in the Nether — use them at the build stage only via ice, or build the collection in the Overworld)',
        '~20 Hoppers', '4 Chests', '~60 Slabs', '~200 Magma Blocks or a fall chute',
        '1 Bed is useless here — bring a respawn anchor instead', '~30 Trapdoors',
    ],
    'prep' => 'Build it on the Nether roof or in a fully cleared area of nether wastes, crimson forest or warped forest at least 128 blocks from any bastion. Bring fire resistance potions and gold armour so piglins stay neutral while you work.',
    'steps' => [
        ['Clear a large area', 'Remove every block within a 128-block sphere of the farm centre that zombified piglins could spawn on, or build on the Nether roof where nothing else exists.'],
        ['Build spawn platforms', 'Flat platforms of any nether-safe block, stacked with 3 blocks of clearance each.'],
        ['Funnel the mobs', 'Use open trapdoors at the platform edges so mobs walk off, or push them with a nether-safe flow of soul sand bubble columns.'],
        ['Build the drop', 'A 23-block fall into a 1×1 landing spot.'],
        ['Kill and collect', 'Hoppers into chests. Add a manual kill spot so you get the XP.'],
        ['Seal it', 'Cover every surface you are not using so spawns concentrate on your platforms.'],
    ],
    'how' => 'Zombified piglins spawn in nether wastes, crimson forest and warped forest at any light level, so you do not need to make the platforms dark — only to remove every competing spawn surface. They drop gold nuggets, occasional gold ingots and gold swords, and they give good XP. The Nether roof is popular because there is nothing above it to compete for spawns.',
    'testing' => 'AFK for five minutes. High rates mean your clearing worked; low rates almost always mean uncleared caves within 128 blocks.',
    'troubles' => [
        ['Piglins are attacking you', ['You hit one, or you are not wearing gold'],
         ['Zombified piglins anger as a group and stay angry. Leave, wait, and wear at least one piece of gold armour']],
        ['Low rates', ['Uncleared spawn surfaces within 128 blocks'],
         ['This farm lives or dies on clearing. Every remaining nether block in range steals spawns']],
        ['Water disappears', ['Water evaporates in the Nether'],
         ['Use trapdoor funnels, soul sand bubble columns or gravity instead of water streams']],
        ['Farm caught fire', ['Ghast fireball or lava'],
         ['Build entirely from non-flammable blocks and wall off the outside against ghasts']],
    ],
],

'guardian' => [
    'title' => 'Guardian Farm', 'icon' => '🔱', 'tier' => 'Advanced',
    'difficulty' => 'Expert', 'edition' => 'Java', 'checked' => '1.21 – 26.x (Java)',
    'output' => 'Prismarine, prismarine crystals, cooked cod and enormous amounts of XP. The highest-output XP farm available.',
    'materials' => [
        'An Ocean Monument', '~2000 Building Blocks', 'Many Sponges (from the monument itself)',
        '20+ Hoppers', '4 Chests', 'Water Buckets', 'Conduit + 16 Prismarine + Nautilus Shells (strongly recommended)',
        'Doors or Sponges for draining', 'Potions of Water Breathing and Night Vision',
    ],
    'prep' => 'This is a multi-session project. Find an ocean monument, kill the elder guardians first (their Mining Fatigue makes everything else impossible), and set up a conduit for unlimited underwater breathing and mining speed.',
    'steps' => [
        ['Kill the elder guardians', 'There are three. Until they are dead you cannot mine at any useful speed anywhere in the monument.'],
        ['Set up a conduit', 'Build a conduit near your work area. It gives water breathing, night vision and haste in a large radius.'],
        ['Drain or enclose', 'Either drain the monument entirely with sponges, or wall off the spawn area and drain only the collection shaft. Draining the whole thing is slower but simpler.'],
        ['Build the collection', 'Guardians spawn in the water volume of the old monument. Flush them into a central shaft with water streams.'],
        ['Kill chamber', 'A long fall, or lava blades. Hoppers underneath into chests.'],
        ['Clear the surroundings', 'Remove or light every other spawnable surface nearby so the monument volume gets all the spawns.'],
    ],
    'how' => 'Guardians spawn only in the water volume that the original ocean monument occupied — that region keeps its special spawn rules even after you remove the building. That is why the monument cannot be relocated and why the farm has to be built where the monument was.',
    'testing' => 'Once the collection shaft is working you should see guardians arriving continuously. If not, you have removed or blocked part of the original monument volume.',
    'troubles' => [
        ['Mining is impossibly slow', ['An elder guardian is still alive'],
         ['All three must die. Mining Fatigue III lasts five minutes and reapplies on sight']],
        ['No guardians spawn', ['You built outside the original monument volume', 'The spawn area has no water'],
         ['Guardians only spawn in the monument\'s own region. Map its footprint before you demolish anything', 'Keep the spawn area flooded — guardians need water']],
        ['Drowning while building', ['No conduit'], ['Set up a conduit early. It changes this build from painful to pleasant']],
    ],
],

'raid' => [
    'title' => 'Raid Farm', 'icon' => '⚔️', 'tier' => 'Advanced',
    'difficulty' => 'Expert', 'edition' => 'Java', 'checked' => '1.21 – 26.x (Java)',
    'output' => 'Emeralds, totems of undying, and enchanted gear. Still the only renewable source of totems.',
    'materials' => [
        '1 Villager', '1 Bed', '~1000 Building Blocks', 'Water Buckets',
        '20 Hoppers', '4 Chests', 'Ominous Bottles (see the version note)',
        'Redstone for a drinking clock', 'Lava or a fall chute for the kill chamber',
    ],
    'prep' => 'Read the version note first — this farm changed substantially in 1.21 and older tutorials will not work. Build it far from any real village so raid waves spawn where you want them.',
    'version_note' => 'From 1.21, killing a raid captain no longer gives you Bad Omen directly. Outpost captains drop Ominous Bottles instead, which you have to drink, and Bad Omen becomes Raid Omen only when you are in a village. You cannot obtain ominous bottles during a raid, so continuous raid farms now need a separate ominous bottle farm feeding them. Rates are meaningfully lower than pre-1.21 designs.',
    'steps' => [
        ['Build the village', 'One villager and one bed, in a sealed box high above the kill chamber. This is what the raid targets.'],
        ['Build the spawn platform', 'Raiders spawn on valid surfaces around the village. Provide one controlled platform and block every other option.'],
        ['Funnel and kill', 'Water streams push raiders to a drop or lava blade. Hoppers into chests below.'],
        ['Set up bottle supply', 'Store your ominous bottles somewhere reachable from the AFK spot, or automate drinking with a dispenser-free redstone clock that pauses you at the right interval.'],
        ['Trigger a raid', 'Drink an ominous bottle while inside the village boundary. The Raid Omen converts after about 30 seconds and the raid begins.'],
    ],
    'how' => 'A raid spawns waves of pillagers, vindicators, witches, ravagers and evokers near the village that triggered it. Evokers drop totems of undying. Concentrating the village into a single tightly controlled point means every wave spawns where your collection system is.',
    'testing' => 'Trigger one raid manually and watch a full set of waves complete. If waves spawn away from your platform, there is another valid spawn surface nearby.',
    'troubles' => [
        ['No raid starts', ['No Bad Omen, or you are not inside a village', 'Trying to get bottles during a raid'],
         ['You must drink an ominous bottle while a villager and bed are in range', 'Ominous bottles only drop from outpost captains outside of raids — stock up first']],
        ['Raiders spawn in the wrong place', ['Other valid spawn surfaces near the village'],
         ['Cover or remove every solid surface in a wide radius except your platform']],
        ['Much slower than a tutorial promised', ['The tutorial predates 1.21'],
         ['Post-1.21 raid farms are limited by ominous bottle supply. Build a bottle farm as well, and expect lower totals']],
        ['Ravagers break the farm', ['Ravagers destroy leaves and some plants'],
         ['Do not use leaf blocks anywhere in the spawn or funnel area']],
    ],
],

'enderman' => [
    'title' => 'Enderman XP Farm', 'icon' => '🟣', 'tier' => 'Advanced',
    'difficulty' => 'Hard', 'edition' => 'Both', 'checked' => '1.21 – 26.x',
    'output' => 'Ender pearls and the fastest XP in the game — full enchanting levels in a couple of minutes.',
    'materials' => [
        '~600 Building Blocks (end stone or obsidian)', '~30 Slabs',
        '12 Hoppers', '2 Chests', 'Blocks for a 42-block drop chute',
        '1 Endermite in a minecart (optional but doubles the rate)', 'Water Buckets',
    ],
    'prep' => 'Build it in the End, on one of the outer islands or over the void away from the main island. Bring a pumpkin to wear if you want to work without provoking endermen.',
    'steps' => [
        ['Choose the site', 'The outer End islands are ideal — nothing else spawns there to compete.'],
        ['Build the platform', 'A large flat platform with 3 blocks of clearance. Endermen spawn at any light level in the End.'],
        ['Add the lure', 'Put an endermite in a minecart at the collection point. Endermen path toward it, which concentrates them.'],
        ['Build the drop', 'A 42-block fall — endermen have 40 health, so this leaves them nearly dead.'],
        ['Kill and collect', 'A one-block-high kill slot so you can hit their legs without them teleporting away. Hoppers into a chest.'],
        ['Seal the platform edges', 'Slab or wall the edges so endermen cannot wander off the platform.'],
    ],
    'how' => 'Endermen spawn freely in the End at any light level, which removes the darkness requirement entirely. They teleport when damaged unless they cannot reach a valid landing spot, which is why the kill slot is only one block tall. The endermite lure works because endermen actively hunt endermites.',
    'testing' => 'AFK for two minutes. If endermen are landing but teleporting away, the kill slot is too tall.',
    'troubles' => [
        ['Endermen teleport away', ['Kill chamber gives them room to escape'],
         ['Make the kill space exactly one block tall, and hit them from outside through a gap at foot level']],
        ['They are angry and attacking', ['You looked at one'],
         ['Wear a carved pumpkin, or only ever look at their legs']],
        ['Low rates', ['Other spawnable surfaces on the island'],
         ['Build over the void, or slab everything else on the island']],
        ['The endermite despawned', ['Endermites despawn quickly'],
         ['Name-tag it, and keep it in a minecart so it cannot move']],
    ],
],

    ];
}

function farmsTiers(): array
{
    return ['Early game', 'Mid game', 'Advanced'];
}

function farms_search_entries(): array
{
    $out = [];
    foreach (farmsAll() as $id => $f) {
        $out[] = [
            'id' => 'farm-' . $id, 'icon' => $f['icon'], 'title' => $f['title'], 'cat' => 'farm',
            'href' => 'farms.php?farm=' . urlencode($id),
            'desc' => $f['output'],
            'keywords' => 'farm automatic ' . strtolower($f['title'] . ' ' . $f['tier'] . ' ' . $f['difficulty'] . ' ' . $f['output']),
        ];
    }
    return $out;
}
