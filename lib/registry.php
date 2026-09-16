<?php
// ================================================
// lib/registry.php — the searchable catalogue
// ------------------------------------------------
// One list of everything the app can take you to. Global search,
// the Tools hub and Home's quick actions all read from here, so a
// new tool only has to be registered once.
// ================================================
require_once __DIR__ . '/mc.php';

/**
 * Tools and generators.
 * keywords = the words a player would actually type, including
 * intent phrasings ("make everyone creative") rather than only names.
 */
function registry_tools(): array
{
    return [
        // ── Commands ──────────────────────────────
        ['id' => 'give', 'icon' => '📦', 'title' => 'Give Item', 'cat' => 'command',
         'href' => 'commands.php?t=give', 'accent' => 'green',
         'desc' => 'Give any item, with enchantments, custom name and lore',
         'keywords' => 'give item spawn get diamond sword netherite gear loot enchanted book inventory'],

        ['id' => 'clear', 'icon' => '🗑️', 'title' => 'Clear Inventory', 'cat' => 'command',
         'href' => 'commands.php?t=clear', 'accent' => 'blue',
         'desc' => 'Remove items from a player, or wipe the whole inventory',
         'keywords' => 'clear remove delete inventory empty take away items wipe'],

        ['id' => 'gamemode', 'icon' => '🎮', 'title' => 'Gamemode', 'cat' => 'command',
         'href' => 'commands.php?t=gamemode', 'accent' => 'teal',
         'desc' => 'Switch survival, creative, adventure or spectator',
         'keywords' => 'gamemode creative survival adventure spectator gmc gms make everyone creative switch mode'],

        ['id' => 'effect', 'icon' => '⚗️', 'title' => 'Effect', 'cat' => 'command',
         'href' => 'commands.php?t=effect', 'accent' => 'purple',
         'desc' => 'Apply or clear potion effects with duration and level',
         'keywords' => 'effect potion speed strength night vision jump boost invisibility haste regeneration buff'],

        ['id' => 'experience', 'icon' => '⭐', 'title' => 'Experience', 'cat' => 'command',
         'href' => 'commands.php?t=experience', 'accent' => 'gold',
         'desc' => 'Add, set or query XP levels and points',
         'keywords' => 'xp experience levels points enchanting give xp query'],

        ['id' => 'teleport', 'icon' => '🌀', 'title' => 'Teleport', 'cat' => 'command',
         'href' => 'commands.php?t=teleport', 'accent' => 'blue',
         'desc' => 'Teleport to coordinates, a player, with rotation and facing',
         'keywords' => 'teleport tp warp move coordinates goto travel bring here'],

        ['id' => 'locate', 'icon' => '🧭', 'title' => 'Locate', 'cat' => 'command',
         'href' => 'commands.php?t=locate', 'accent' => 'teal',
         'desc' => 'Find structures, biomes and points of interest',
         'keywords' => 'locate find structure biome village stronghold mansion fortress bastion ancient city trial chamber'],

        ['id' => 'time', 'icon' => '🌤️', 'title' => 'Time & Weather', 'cat' => 'command',
         'href' => 'commands.php?t=time', 'accent' => 'orange',
         'desc' => 'Set day, night, rain, thunder or clear skies',
         'keywords' => 'time weather day night noon midnight rain thunder storm clear sun sunset stop rain'],

        ['id' => 'gamerule', 'icon' => '⚙️', 'title' => 'Gamerule', 'cat' => 'command',
         'href' => 'commands.php?t=gamerule', 'accent' => 'purple',
         'desc' => 'keepInventory, mob griefing, daylight cycle and more',
         'keywords' => 'gamerule keepinventory keep inventory mobgriefing daylight cycle random tick speed fire damage fall damage no drops'],

        ['id' => 'difficulty', 'icon' => '☠️', 'title' => 'Difficulty', 'cat' => 'command',
         'href' => 'commands.php?t=difficulty', 'accent' => 'red',
         'desc' => 'Peaceful, easy, normal or hard',
         'keywords' => 'difficulty peaceful easy normal hard no mobs stop mobs spawning'],

        ['id' => 'summon', 'icon' => '👾', 'title' => 'Summon', 'cat' => 'command',
         'href' => 'commands.php?t=summon', 'accent' => 'red',
         'desc' => 'Spawn any mob or entity, with presets',
         'keywords' => 'summon spawn mob entity zombie creeper villager armor stand wither ender dragon'],

        ['id' => 'kill', 'icon' => '💀', 'title' => 'Kill', 'cat' => 'command',
         'href' => 'commands.php?t=kill', 'accent' => 'red',
         'desc' => 'Remove entities by selector — with safety checks',
         'keywords' => 'kill remove entities despawn clear mobs delete items lag'],

        ['id' => 'tag', 'icon' => '🏷️', 'title' => 'Tag', 'cat' => 'command',
         'href' => 'commands.php?t=tag', 'accent' => 'teal',
         'desc' => 'Add, remove or list scoreboard tags on an entity',
         'keywords' => 'tag tags add remove list mark entity selector quest_done scoreboard'],

        ['id' => 'tellraw', 'icon' => '💬', 'title' => 'Tellraw', 'cat' => 'command',
         'href' => 'commands.php?t=tellraw', 'accent' => 'pink',
         'desc' => 'Formatted chat messages with colours, links and hover text',
         'keywords' => 'tellraw chat message json text component colour color hover click link announce say formatted rainbow'],

        ['id' => 'particle', 'icon' => '✨', 'title' => 'Particle', 'cat' => 'command',
         'href' => 'commands.php?t=particle', 'accent' => 'purple',
         'desc' => 'Spawn particles with colour, spread, speed and count',
         'keywords' => 'particle particles effect dust smoke flame sparkle visual decoration ambience'],

        ['id' => 'playsound', 'icon' => '🔊', 'title' => 'Playsound', 'cat' => 'command',
         'href' => 'commands.php?t=playsound', 'accent' => 'orange',
         'desc' => 'Play any sound to chosen players, with volume and pitch',
         'keywords' => 'playsound sound audio noise music play effect bell thunder ding alert'],

        ['id' => 'bossbar', 'icon' => '📛', 'title' => 'Boss Bar', 'cat' => 'command',
         'href' => 'commands.php?t=bossbar', 'accent' => 'red',
         'desc' => 'The bar across the top of the screen — timers and progress',
         'keywords' => 'bossbar boss bar health timer progress top screen countdown objective display'],

        ['id' => 'team', 'icon' => '🏳️', 'title' => 'Team', 'cat' => 'command',
         'href' => 'commands.php?t=team', 'accent' => 'teal',
         'desc' => 'Colour names, control friendly fire, collision and nametags',
         'keywords' => 'team teams colour color friendly fire collision nametag glow group pvp sides'],

        ['id' => 'execute', 'icon' => '⛓️', 'title' => 'Execute Builder', 'cat' => 'command',
         'href' => 'commands.php?t=execute', 'accent' => 'green',
         'desc' => 'Build an /execute chain visually, with a plain-language summary',
         'keywords' => 'execute as at positioned rotated facing if unless store run chain conditional detect nearby'],

        ['id' => 'attribute', 'icon' => '📈', 'title' => 'Attribute', 'cat' => 'command',
         'href' => 'commands.php?t=attribute', 'accent' => 'blue',
         'desc' => 'Change health, speed, damage, reach and other entity stats',
         'keywords' => 'attribute attributes max health speed damage reach scale gravity modifier stat buff'],

        ['id' => 'data', 'icon' => '🗂️', 'title' => 'Data', 'cat' => 'command',
         'href' => 'commands.php?t=data', 'accent' => 'gold',
         'desc' => 'Read and edit the NBT behind an entity, block or storage',
         'keywords' => 'data nbt get merge modify remove storage entity block tag path edit inspect'],

        ['id' => 'enchantments', 'icon' => '✨', 'title' => 'Enchantment Hub', 'cat' => 'enchant',
         'href' => 'enchantments.php', 'accent' => 'gold',
         'desc' => 'Select an item, see recommended enchantments, check conflicts, and generate the command',
         'keywords' => 'enchant enchantment enchanting hub sword pickaxe bow trident sharpness looting mending unbreaking fortune silk touch conflict best enchants'],

        // ── Build ─────────────────────────────────
        ['id' => 'fill', 'icon' => '🧱', 'title' => 'Fill Area', 'cat' => 'build',
         'href' => 'build.php?t=fill', 'accent' => 'green',
         'desc' => 'Fill a region with a block, with volume checks',
         'keywords' => 'fill area region blocks platform floor walls box hollow outline replace'],

        ['id' => 'clear-area', 'icon' => '💨', 'title' => 'Clear Area', 'cat' => 'build',
         'href' => 'build.php?t=clear', 'accent' => 'blue',
         'desc' => 'Flatten ground or remove trees, leaves and water',
         'keywords' => 'clear area clear trees remove leaves flatten ground dig out cut down forest empty space'],

        ['id' => 'replace', 'icon' => '🔁', 'title' => 'Replace Blocks', 'cat' => 'build',
         'href' => 'build.php?t=replace', 'accent' => 'orange',
         'desc' => 'Swap one block for another inside a region',
         'keywords' => 'replace swap change blocks convert stone to grass filter'],

        ['id' => 'setblock', 'icon' => '⬛', 'title' => 'Set Block', 'cat' => 'build',
         'href' => 'build.php?t=setblock', 'accent' => 'purple',
         'desc' => 'Place a single block with block states',
         'keywords' => 'setblock place single block state facing waterlogged command block'],

        ['id' => 'clone', 'icon' => '📑', 'title' => 'Clone Area', 'cat' => 'build',
         'href' => 'build.php?t=clone', 'accent' => 'teal',
         'desc' => 'Copy a region somewhere else, with masks and modes',
         'keywords' => 'clone copy duplicate move region paste mirror repeat building'],

        ['id' => 'area-calc', 'icon' => '📐', 'title' => 'Area Calculator', 'cat' => 'build',
         'href' => 'build.php?t=area', 'accent' => 'gold',
         'desc' => 'Dimensions and block counts between two corners',
         'keywords' => 'area calculator volume size blocks needed how many count dimensions 19x19 measure'],

        ['id' => 'coords', 'icon' => '🧮', 'title' => 'Coordinate Helper', 'cat' => 'build',
         'href' => 'build.php?t=coords', 'accent' => 'blue',
         'desc' => 'Midpoints, offsets, distance and relative coordinates',
         'keywords' => 'coordinates coords relative tilde caret centre center midpoint distance offset direction'],

        ['id' => 'planner', 'icon' => '📝', 'title' => 'Build Planner', 'cat' => 'build',
         'href' => 'build.php?t=planner', 'accent' => 'green',
         'desc' => 'Plan footprint, floors, clearance and rough materials',
         'keywords' => 'build planner plan house size floors dimensions materials needed footprint clearance'],

        // ── Doctor & knowledge ────────────────────
        ['id' => 'doctor', 'icon' => '🩺', 'title' => 'Command Doctor', 'cat' => 'doctor',
         'href' => 'doctor.php', 'accent' => 'red',
         'desc' => 'Paste a broken command and get the fix explained',
         'keywords' => 'doctor fix broken command error not working invalid syntax why help debug repair'],

        ['id' => 'explain', 'icon' => '💬', 'title' => 'Command Explainer', 'cat' => 'doctor',
         'href' => 'doctor.php?mode=explain', 'accent' => 'blue',
         'desc' => 'Paste a command and read what it actually does',
         'keywords' => 'explain understand what does this command do read learn teach execute'],

        ['id' => 'materials', 'icon' => '🪨', 'title' => 'Material Library', 'cat' => 'knowledge',
         'href' => 'knowledge.php?t=materials', 'accent' => 'gold',
         'desc' => 'Searchable block reference with uses and styles',
         'keywords' => 'materials blocks library reference stone wood concrete terracotta deepslate what block'],

        ['id' => 'palette', 'icon' => '🎨', 'title' => 'Block Palette Builder', 'cat' => 'knowledge',
         'href' => 'knowledge.php?t=palette', 'accent' => 'purple',
         'desc' => 'Build and save palettes, or start from a style preset',
         'keywords' => 'palette blocks colours colors medieval modern japanese rustic combination which blocks go together'],

        ['id' => 'tips', 'icon' => '💡', 'title' => 'Building Tips', 'cat' => 'knowledge',
         'href' => 'knowledge.php?t=tips', 'accent' => 'teal',
         'desc' => 'Depth, proportion, detailing and common mistakes',
         'keywords' => 'building tips techniques improve house better builds depth detail roof proportions mistakes ugly'],

        ['id' => 'ideas', 'icon' => '🏰', 'title' => 'Build Ideas', 'cat' => 'knowledge',
         'href' => 'knowledge.php?t=ideas', 'accent' => 'green',
         'desc' => 'What to build next, with dimensions and palettes',
         'keywords' => 'build ideas what to build inspiration starter house medieval tower castle barn bridge bored'],

        ['id' => 'farms', 'icon' => '🌾', 'title' => 'Farm Lab', 'cat' => 'farm',
         'href' => 'farms.php', 'accent' => 'green',
         'desc' => 'Step-by-step farm guides with material checklists',
         'keywords' => 'farm farms automatic iron gold xp mob creeper villager crops afk resource'],

        // ── Existing specialist generators ────────
        ['id' => 'kit', 'icon' => '🎒', 'title' => 'Kit Builder', 'cat' => 'tool',
         'href' => 'kit.php', 'accent' => 'gold',
         'desc' => 'Build a full loadout and hand it out in one go',
         'keywords' => 'kit loadout starter pvp gear set armour armor equipment pack'],

        ['id' => 'nbt', 'icon' => '🔧', 'title' => 'Custom Item Builder', 'cat' => 'tool',
         'href' => 'nbt.php', 'accent' => 'purple',
         'desc' => 'Custom name, lore, enchants and item components',
         'keywords' => 'nbt components custom item name lore enchantments unbreakable rename'],

        ['id' => 'mystuff', 'icon' => '⭐', 'title' => 'My Stuff', 'cat' => 'tool',
         'href' => 'mystuff.php', 'accent' => 'gold',
         'desc' => 'Saved commands, history, palettes and favourites',
         'keywords' => 'saved my stuff library history favourites favorites recent bookmarks'],
    ];
}

/** Everything searchable: tools plus content from the data files. */
function registry_all(): array
{
    $entries = registry_tools();

    $content = [
        'blocks.php'      => ['blocks_search_entries'],
        'palettes.php'    => ['palettes_search_entries'],
        'tips.php'        => ['tips_search_entries'],
        'ideas.php'       => ['ideas_search_entries'],
        'farms.php'       => ['farms_search_entries'],
        'blueprints.php'  => ['blueprints_search_entries'],
        'enchantments.php'=> ['enchant_search_entries', 'items_search_entries', 'enchant_presets_search_entries'],
    ];
    foreach ($content as $file => $fns) {
        $path = __DIR__ . '/data/' . $file;
        if (!is_file($path)) continue;
        require_once $path;
        foreach ($fns as $fn) {
            if (function_exists($fn)) {
                $entries = array_merge($entries, $fn());
            }
        }
    }
    return $entries;
}

/**
 * Intent-first ranked search.
 * Matches title, description and keywords, so "make everyone creative"
 * finds the gamemode builder even though those words are not its name.
 */
function registry_search(string $query, int $limit = 12): array
{
    $q = trim(mb_strtolower($query));
    if ($q === '') return [];

    $terms = array_values(array_filter(preg_split('/\s+/', $q)));
    $hits  = [];

    foreach (registry_all() as $entry) {
        $title = mb_strtolower($entry['title']);
        $desc  = mb_strtolower($entry['desc'] ?? '');
        $keys  = mb_strtolower($entry['keywords'] ?? '');
        $hay   = $title . ' ' . $desc . ' ' . $keys;

        $score = 0;
        if ($title === $q)                    $score += 120;
        elseif (str_starts_with($title, $q))  $score += 80;
        elseif (str_contains($title, $q))     $score += 55;
        if (str_contains($keys, $q))          $score += 40;
        if (str_contains($desc, $q))          $score += 18;

        $matched = 0;
        foreach ($terms as $t) {
            if (mb_strlen($t) < 2) continue;
            if (str_contains($title, $t)) { $score += 14; $matched++; continue; }
            if (str_contains($keys,  $t)) { $score += 9;  $matched++; continue; }
            if (str_contains($desc,  $t)) { $score += 5;  $matched++; }
        }
        // Every word landing somewhere is a strong signal for phrase queries.
        if ($matched === count($terms) && count($terms) > 1) $score += 25;
        if ($score <= 0) continue;

        // Tools before reference content when scores are close.
        if (in_array($entry['cat'], ['command', 'build', 'doctor'], true)) $score += 4;

        $entry['score'] = $score;
        $hits[] = $entry;
    }

    usort($hits, fn($a, $b) => $b['score'] <=> $a['score'] ?: strcmp($a['title'], $b['title']));
    return array_slice($hits, 0, $limit);
}
