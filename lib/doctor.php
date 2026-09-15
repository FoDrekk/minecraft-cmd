<?php
// ================================================
// lib/doctor.php — Command Doctor & Explainer
// ------------------------------------------------
// Reads a pasted command apart, reports what is wrong in plain
// language, and where the fix is unambiguous rewrites it for the
// selected version.
//
// Every problem is { level, title, why, fix, version }:
//   level   error | warn | info
//   why     explained for a player, not a parser
//   version true when the cause is a version difference
// ================================================
require_once __DIR__ . '/mc.php';
require_once __DIR__ . '/snbt.php';
require_once __DIR__ . '/data/game.php';
require_once __DIR__ . '/data/blocks.php';

const DOCTOR_COMMANDS = [
    'advancement', 'attribute', 'ban', 'bossbar', 'clear', 'clone', 'damage', 'data', 'datapack',
    'debug', 'defaultgamemode', 'difficulty', 'effect', 'enchant', 'execute', 'experience', 'xp',
    'fill', 'fillbiome', 'forceload', 'function', 'gamemode', 'gamerule', 'give', 'help', 'item',
    'kick', 'kill', 'list', 'locate', 'loot', 'me', 'msg', 'op', 'particle', 'place', 'playsound',
    'random', 'recipe', 'reload', 'return', 'ride', 'say', 'schedule', 'scoreboard', 'seed',
    'setblock', 'setworldspawn', 'spawnpoint', 'spectate', 'spreadplayers', 'stopsound', 'summon',
    'tag', 'team', 'teleport', 'tp', 'tell', 'tellraw', 'time', 'title', 'tick', 'transfer',
    'trigger', 'weather', 'whitelist', 'worldborder',
];

const SELECTOR_ARGS = [
    'x', 'y', 'z', 'dx', 'dy', 'dz', 'distance', 'scores', 'tag', 'team', 'limit', 'sort',
    'level', 'gamemode', 'name', 'x_rotation', 'y_rotation', 'type', 'nbt', 'advancements',
    'predicate',
];

/** Split a command into top-level arguments, keeping brackets and quotes together. */
function doctor_tokens(string $s): array
{
    $out = []; $buf = ''; $depth = 0; $quote = null;
    for ($i = 0; $i < strlen($s); $i++) {
        $c = $s[$i];
        if ($quote !== null) {
            $buf .= $c;
            if ($c === '\\' && $i + 1 < strlen($s)) { $buf .= $s[++$i]; continue; }
            if ($c === $quote) $quote = null;
            continue;
        }
        if ($c === '"' || $c === "'") { $quote = $c; $buf .= $c; continue; }
        if ($c === '{' || $c === '[') { $depth++; $buf .= $c; continue; }
        if ($c === '}' || $c === ']') { $depth--; $buf .= $c; continue; }
        if ($c === ' ' && $depth <= 0) {
            if ($buf !== '') { $out[] = $buf; $buf = ''; }
            continue;
        }
        $buf .= $c;
    }
    if ($buf !== '') $out[] = $buf;
    return $out;
}

/** Bracket and quote balance, reported as the player would need to fix it. */
function doctor_balance(string $s): ?array
{
    $stack = []; $quote = null; $quoteAt = 0;
    $pairs = ['}' => '{', ']' => '[', ')' => '('];
    for ($i = 0; $i < strlen($s); $i++) {
        $c = $s[$i];
        if ($quote !== null) {
            if ($c === '\\') { $i++; continue; }
            if ($c === $quote) $quote = null;
            continue;
        }
        if ($c === '"' || $c === "'") { $quote = $c; $quoteAt = $i; continue; }
        if ($c === '{' || $c === '[') { $stack[] = [$c, $i]; continue; }
        if (isset($pairs[$c])) {
            if (!$stack) {
                return ['level' => 'error', 'version' => false,
                        'title' => 'There is a closing ' . $c . ' with nothing to close',
                        'why'   => 'A ' . $c . ' appears at position ' . ($i + 1) . ' but nothing was opened before it.',
                        'fix'   => 'Remove that ' . $c . ', or add the matching ' . $pairs[$c] . ' earlier in the command.'];
            }
            $open = array_pop($stack);
            if ($open[0] !== $pairs[$c]) {
                return ['level' => 'error', 'version' => false,
                        'title' => 'Brackets are crossed over',
                        'why'   => 'A ' . $open[0] . ' opened at position ' . ($open[1] + 1) . ' is being closed with ' . $c . '.',
                        'fix'   => 'Close each bracket with its own partner: { with }, and [ with ].'];
            }
        }
    }
    if ($quote !== null) {
        return ['level' => 'error', 'version' => false,
                'title' => 'A quote is never closed',
                'why'   => 'The ' . $quote . ' at position ' . ($quoteAt + 1) . ' opens some text that is never closed.',
                'fix'   => 'Add a matching ' . $quote . ' at the end of that text. If the text itself contains a ' . $quote . ', put a backslash in front of it.'];
    }
    if ($stack) {
        $open = end($stack);
        $close = $open[0] === '{' ? '}' : ']';
        return ['level' => 'error', 'version' => false,
                'title' => 'A ' . $open[0] . ' is never closed',
                'why'   => 'The ' . $open[0] . ' at position ' . ($open[1] + 1) . ' is still open at the end of the command.',
                'fix'   => 'Add ' . $close . ' where that section should end. ' . count($stack) . ' bracket' . (count($stack) === 1 ? ' is' : 's are') . ' still open.'];
    }
    return null;
}

/** Nearest known name, for "did you mean" suggestions. */
function doctor_closest(string $needle, array $haystack, int $maxDistance = 3): ?string
{
    $best = null; $bestD = $maxDistance + 1;
    $n = strtolower($needle);
    foreach ($haystack as $h) {
        $d = levenshtein($n, strtolower((string)$h));
        if ($d < $bestD) { $bestD = $d; $best = $h; }
    }
    return $bestD <= $maxDistance ? $best : null;
}

/** Split `id[state]{nbt}` or `id{nbt}` into its parts. */
function doctor_split_item(string $arg): array
{
    $id = ''; $i = 0;
    while ($i < strlen($arg) && preg_match('/[A-Za-z0-9_.:#\/\-]/', $arg[$i])) $i++;
    $id = substr($arg, 0, $i);
    $rest = substr($arg, $i);
    $square = null; $curly = null;
    if ($rest !== '' && $rest[0] === '[') {
        $d = 0;
        for ($j = 0; $j < strlen($rest); $j++) {
            if ($rest[$j] === '[') $d++;
            elseif ($rest[$j] === ']') { $d--; if ($d === 0) { $square = substr($rest, 0, $j + 1); $rest = substr($rest, $j + 1); break; } }
        }
    }
    if ($rest !== '' && $rest[0] === '{') $curly = $rest;
    return ['id' => $id, 'square' => $square, 'curly' => $curly];
}

/** Normalise a text component node to how the target version stores it inside NBT. */
function doctor_text_node($node, array $syn)
{
    $wantSnbt = ($syn['text_in_nbt'] ?? 'json_string') === 'snbt';
    $isJsonString = $node['t'] === 'string';

    if ($wantSnbt && $isJsonString) {
        $decoded = json_decode($node['v'], true);
        if ($decoded === null) return $node;                 // not JSON — leave it alone
        return doctor_php_to_snbt($decoded);
    }
    if (!$wantSnbt && $node['t'] === 'compound') {
        return snbt_node_string(json_encode(snbt_plain($node), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "'");
    }
    return $node;
}

function doctor_php_to_snbt($v)
{
    if (is_bool($v))   return snbt_node_word($v ? 'true' : 'false');
    if (is_int($v))    return snbt_node_number((string)$v);
    if (is_float($v))  return snbt_node_number((string)$v);
    if (is_string($v)) return snbt_node_string($v);
    if (is_array($v)) {
        if ($v === [] || array_is_list($v)) return snbt_node_list(array_map('doctor_php_to_snbt', $v));
        $map = [];
        foreach ($v as $k => $c) $map[$k] = doctor_php_to_snbt($c);
        return snbt_node_compound($map);
    }
    return snbt_node_word('null');
}

/**
 * Convert legacy item NBT into components for the target syntax.
 * Returns [componentMap, notes[]] — notes name anything that could not
 * be converted safely, so nothing is silently mangled.
 */
function doctor_nbt_to_components(array $nbt, array $syn): array
{
    $comps = []; $notes = []; $hidden = [];
    $hide = $syn['hide'] ?? 'tooltip_display';
    $hideFlags = isset($nbt['HideFlags']);

    foreach ($nbt as $key => $node) {
        switch ($key) {
            case 'display':
                $d = $node['v'] ?? [];
                if (isset($d['Name'])) $comps['custom_name'] = doctor_text_node($d['Name'], $syn);
                if (isset($d['Lore'])) {
                    $comps['lore'] = snbt_node_list(array_map(fn($n) => doctor_text_node($n, $syn), $d['Lore']['v'] ?? []));
                }
                if (isset($d['color'])) $comps['dyed_color'] = $d['color'];
                break;

            case 'Enchantments':
            case 'StoredEnchantments':
                $levels = [];
                foreach (($node['v'] ?? []) as $entry) {
                    $e = snbt_plain($entry);
                    if (!isset($e['id'])) continue;
                    $levels[preg_replace('/^minecraft:/', '', $e['id'])] = snbt_node_number((string)($e['lvl'] ?? 1));
                }
                $name = $key === 'Enchantments' ? 'enchantments' : 'stored_enchantments';
                $map  = snbt_node_compound($levels);
                if (($syn['enchant'] ?? 'flat') === 'levels') {
                    $inner = ['levels' => $map];
                    if ($hideFlags && $hide === 'show_in_tooltip') $inner['show_in_tooltip'] = snbt_node_word('false');
                    $comps[$name] = snbt_node_compound($inner);
                } else {
                    $comps[$name] = $map;
                    if ($hideFlags) $hidden[] = 'minecraft:' . $name;
                }
                break;

            case 'Unbreakable':
                $comps['unbreakable'] = ($hideFlags && $hide === 'show_in_tooltip')
                    ? snbt_node_compound(['show_in_tooltip' => snbt_node_word('false')])
                    : snbt_node_compound([]);
                if ($hideFlags && $hide === 'tooltip_display') $hidden[] = 'minecraft:unbreakable';
                break;

            case 'HideFlags':
                break;   // handled alongside the components it hides

            case 'Damage':
                $comps['damage'] = $node;
                break;

            case 'RepairCost':
                $comps['repair_cost'] = $node;
                break;

            case 'CustomModelData':
                $comps['custom_model_data'] = $node;
                $notes[] = 'CustomModelData became a compound in 1.21.5 (floats, flags, strings, colors). The number has been carried over as-is — check it against your resource pack.';
                break;

            case 'AttributeModifiers':
                $notes[] = 'AttributeModifiers changed shape completely in 1.20.5 and again in 1.21. It has been left out — rebuild it with the attribute_modifiers component.';
                break;

            case 'Potion':
            case 'CustomPotionEffects':
            case 'custom_potion_effects':
                $notes[] = 'Potion data moved into the potion_contents component in 1.20.5. It has been left out — rebuild it there.';
                break;

            case 'SkullOwner':
                $notes[] = 'SkullOwner became the profile component in 1.20.5. It has been left out.';
                break;

            case 'BlockEntityTag':
                $comps['block_entity_data'] = $node;
                $notes[] = 'BlockEntityTag maps to block_entity_data, but several block types now have their own dedicated components. Check yours.';
                break;

            case 'CanDestroy':  $comps['can_break'] = $node;  $notes[] = 'CanDestroy became can_break, whose shape changed in 1.20.5. Check the predicate format.'; break;
            case 'CanPlaceOn':  $comps['can_place_on'] = $node; $notes[] = 'CanPlaceOn became can_place_on, whose shape changed in 1.20.5. Check the predicate format.'; break;

            default:
                $notes[] = 'Tag "' . $key . '" has no direct component equivalent and has been left out.';
        }
    }

    if ($hidden && $hide === 'tooltip_display') {
        $comps['tooltip_display'] = snbt_node_compound([
            'hidden_components' => snbt_node_list(array_map(fn($h) => snbt_node_string($h), $hidden)),
        ]);
    } elseif ($hideFlags && $hide === 'show_in_tooltip') {
        $comps['hide_additional_tooltip'] = snbt_node_compound([]);
    }

    return [$comps, $notes];
}

/** Write a component map back out as `[name=value,…]`. */
function doctor_components_string(array $comps): string
{
    if (!$comps) return '';
    $parts = [];
    foreach ($comps as $name => $node) $parts[] = $name . '=' . snbt_write($node);
    return '[' . implode(',', $parts) . ']';
}

/** Parse `[a=1,b={…}]` into a name => node map. */
function doctor_parse_components(string $square): ?array
{
    $inner = substr($square, 1, -1);
    if (trim($inner) === '') return [];
    // Reuse the compound parser by wrapping the pairs, swapping = for :
    $out = []; $i = 0; $len = strlen($inner);
    while ($i < $len) {
        while ($i < $len && (ctype_space($inner[$i]) || $inner[$i] === ',')) $i++;
        if ($i >= $len) break;
        $start = $i;
        while ($i < $len && $inner[$i] !== '=') $i++;
        if ($i >= $len) return null;
        $name = trim(substr($inner, $start, $i - $start));
        $i++;                                     // skip =
        try { $node = snbt_value($inner, $i); }
        catch (SnbtError $e) { return null; }
        $out[$name] = $node;
    }
    return $out;
}

// ================================================
// ANALYSIS
// ================================================

function doctor_problem(string $level, string $title, string $why, string $fix = '', bool $version = false): array
{
    return ['level' => $level, 'title' => $title, 'why' => $why, 'fix' => $fix, 'version' => $version];
}

/** Known ids for "did you mean" checks. */
function doctor_known_items(): array
{
    static $ids = null;
    if ($ids !== null) return $ids;
    require __DIR__ . '/../items.php';
    $ids = array_column($ITEMS_FLAT, 0);
    foreach (blocksList() as $b) $ids[] = $b['id'];
    $ids = array_values(array_unique($ids));
    return $ids;
}

function doctor_check_selector(string $token, string $version): array
{
    $problems = [];
    if ($token === '' || $token[0] !== '@') return $problems;

    if (!preg_match('/^@([a-z])(\[(.*)\])?$/s', $token, $m)) {
        $problems[] = doctor_problem('error', 'That selector is not written correctly',
            '"' . $token . '" does not look like a selector. They are a single letter after @, with optional arguments in square brackets.',
            'Use @p, @a, @r, @s or @e — for example @a[distance=..10].');
        return $problems;
    }

    $letter = $m[1];
    if (!in_array('@' . $letter, ['@p', '@a', '@r', '@s', '@e', '@n'], true)) {
        $problems[] = doctor_problem('error', '@' . $letter . ' is not a real selector',
            'Minecraft has @p (nearest player), @a (all players), @r (random player), @s (yourself) and @e (all entities).',
            'Replace @' . $letter . ' with the one you meant. @e is the usual choice for mobs.');
        return $problems;
    }
    if ($letter === 'n' && mcVersion($version)['rank'] < 50) {
        $problems[] = doctor_problem('warn', '@n only exists in newer versions',
            '@n (nearest entity) was added after ' . mcVersion($version)['label'] . '.',
            'Use @e[limit=1,sort=nearest] instead, which works everywhere.', true);
    }

    if (!isset($m[3]) || trim($m[3]) === '') return $problems;

    // Split arguments at top level so nested {} and [] survive
    $args = doctor_selector_args($m[3]);
    foreach ($args as $name => $value) {
        if (in_array($name, SELECTOR_ARGS, true)) continue;
        $alt = doctor_closest($name, SELECTOR_ARGS, 3);
        $problems[] = doctor_problem('error', '"' . $name . '" is not a selector argument',
            'The selector arguments Minecraft understands are ' . implode(', ', array_slice(SELECTOR_ARGS, 0, 8)) . ' and a few more.',
            $alt ? 'Did you mean ' . $alt . '?' : 'Remove it, or check the spelling.');
    }
    if (isset($args['type']) && $letter !== 'e' && $letter !== 'n') {
        $problems[] = doctor_problem('warn', 'type= only works on entity selectors',
            '@' . $letter . ' already only matches players, so filtering by type has no effect.',
            'Use @e[type=' . ltrim($args['type'], '!') . '] to select that entity.');
    }
    if (isset($args['distance']) && str_contains($args['distance'], '-') && !str_contains($args['distance'], '..')) {
        $problems[] = doctor_problem('warn', 'Distance ranges use two dots',
            'A range is written 5..10, ..10 (up to 10) or 5.. (10 or more).',
            'Change distance=' . $args['distance'] . ' to use .. instead of -.');
    }
    return $problems;
}

function doctor_selector_args(string $inner): array
{
    $out = []; $buf = ''; $depth = 0; $quote = null;
    $parts = [];
    for ($i = 0; $i < strlen($inner); $i++) {
        $c = $inner[$i];
        if ($quote !== null) { $buf .= $c; if ($c === '\\') { $buf .= $inner[++$i] ?? ''; } elseif ($c === $quote) $quote = null; continue; }
        if ($c === '"' || $c === "'") { $quote = $c; $buf .= $c; continue; }
        if ($c === '{' || $c === '[') { $depth++; $buf .= $c; continue; }
        if ($c === '}' || $c === ']') { $depth--; $buf .= $c; continue; }
        if ($c === ',' && $depth === 0) { $parts[] = $buf; $buf = ''; continue; }
        $buf .= $c;
    }
    if (trim($buf) !== '') $parts[] = $buf;
    foreach ($parts as $p) {
        $eq = strpos($p, '=');
        if ($eq === false) continue;
        $out[trim(substr($p, 0, $eq))] = trim(substr($p, $eq + 1));
    }
    return $out;
}

function doctor_check_coords(array $coords): array
{
    $locals = 0; $others = 0;
    foreach ($coords as $c) {
        if ($c === '') continue;
        if ($c[0] === '^') $locals++; else $others++;
    }
    if ($locals > 0 && $others > 0) {
        return [doctor_problem('error', 'World and local coordinates cannot be mixed',
            'You have used ^ on some axes and ~ or a plain number on others. Minecraft rejects the whole command when they are mixed.',
            'Use ^ on all three, or none at all. ^ ^ ^5 means five blocks in front of where you are looking.')];
    }
    return [];
}

/**
 * Analyse one command.
 * Returns ['ok', 'problems', 'fixed', 'command', 'version'].
 */
function doctorAnalyse(string $raw, string $version = ''): array
{
    $version = $version ?: mcCurrentVersion();
    $syn     = mcSyntax($version);
    $verInfo = mcVersion($version);
    $problems = [];
    $fixed = null;

    $cmd = trim($raw);
    if ($cmd === '') {
        return ['ok' => false, 'command' => '', 'version' => $verInfo,
                'problems' => [doctor_problem('info', 'Nothing to check', 'Paste a command in the box above.', '')],
                'fixed' => null];
    }

    $hadSlash = $cmd[0] === '/';
    if ($hadSlash) $cmd = substr($cmd, 1);

    // 1. Structure first — nothing else can be trusted until this passes.
    $bal = doctor_balance($cmd);
    if ($bal) {
        return ['ok' => false, 'command' => $raw, 'version' => $verInfo, 'problems' => [$bal], 'fixed' => null];
    }

    $tokens = doctor_tokens($cmd);
    $name   = strtolower($tokens[0] ?? '');

    if (!in_array($name, DOCTOR_COMMANDS, true)) {
        $alt = doctor_closest($name, DOCTOR_COMMANDS, 3);
        $problems[] = doctor_problem('error', '"' . $name . '" is not a Minecraft command',
            'Nothing in the game answers to that name.',
            $alt ? 'Did you mean /' . $alt . '?' : 'Check the spelling. Typing / in game lists everything available.');
        return ['ok' => false, 'command' => $raw, 'version' => $verInfo, 'problems' => $problems, 'fixed' => null];
    }

    if (!$hadSlash) {
        $problems[] = doctor_problem('info', 'No leading slash',
            'In chat a command needs to start with /. Inside a command block or a function file it must not.',
            'Add / at the front if you are typing this into chat.');
    }

    $rebuilt = $tokens;
    $changed = false;

    // 2. Selectors anywhere in the command
    foreach ($tokens as $t) {
        if ($t !== '' && $t[0] === '@') {
            $problems = array_merge($problems, doctor_check_selector($t, $version));
        }
    }

    // 3. Command-specific checks
    switch ($name) {
        case 'give':
            if (count($tokens) < 3) {
                $problems[] = doctor_problem('error', '/give is missing something',
                    'It needs a target and an item: /give <target> <item> [count].',
                    'For example /give @p diamond_sword 1');
                break;
            }
            [$itemFixed, $itemProblems] = doctor_check_item($tokens[2], $syn, $verInfo, 'item');
            $problems = array_merge($problems, $itemProblems);
            if ($itemFixed !== null) { $rebuilt[2] = $itemFixed; $changed = true; }
            if (isset($tokens[3]) && !is_numeric($tokens[3])) {
                $problems[] = doctor_problem('error', 'The count must be a number',
                    '"' . $tokens[3] . '" is where /give expects how many to hand over.',
                    'Put a number there, or remove it to give one.');
            }
            break;

        case 'setblock':
            if (count($tokens) < 5) {
                $problems[] = doctor_problem('error', '/setblock needs three coordinates and a block',
                    'The form is /setblock <x> <y> <z> <block> [mode].',
                    'For example /setblock ~ ~ ~ stone');
                break;
            }
            $problems = array_merge($problems, doctor_check_coords([$tokens[1], $tokens[2], $tokens[3]]));
            [$blockFixed, $blockProblems] = doctor_check_item($tokens[4], $syn, $verInfo, 'block');
            $problems = array_merge($problems, $blockProblems);
            if ($blockFixed !== null) { $rebuilt[4] = $blockFixed; $changed = true; }
            break;

        case 'fill':
            if (count($tokens) < 8) {
                $problems[] = doctor_problem('error', '/fill needs six coordinates and a block',
                    'The form is /fill <x1> <y1> <z1> <x2> <y2> <z2> <block> [mode].',
                    'For example /fill ~ ~ ~ ~10 ~5 ~10 stone');
                break;
            }
            $problems = array_merge($problems, doctor_check_coords(array_slice($tokens, 1, 6)));
            [$fb, $fp] = doctor_check_item($tokens[7], $syn, $verInfo, 'block');
            $problems = array_merge($problems, $fp);
            if ($fb !== null) { $rebuilt[7] = $fb; $changed = true; }
            $vol = doctor_fill_volume(array_slice($tokens, 1, 6));
            if ($vol !== null && $vol > 32768) {
                $problems[] = doctor_problem('error', 'That region is too big for one command',
                    number_format($vol) . ' blocks is over the 32,768-block limit a single /fill may change.',
                    'Split it into ' . ceil($vol / 32768) . ' smaller fills, or raise the limit first with /gamerule commandModificationBlockLimit ' . min(10000000, (int)ceil($vol / 1000) * 1000) . '.');
            }
            break;

        case 'effect':
            $problems = array_merge($problems, doctor_check_effect($tokens, $version));
            break;

        case 'gamerule':
            if (count($tokens) < 2) {
                $problems[] = doctor_problem('error', '/gamerule needs a rule name',
                    'The form is /gamerule <rule> [value]. With no value it reports the current setting.', '');
                break;
            }
            $rules = gameFilter(gameRules(), $version);
            if (!isset($rules[$tokens[1]])) {
                $all = array_keys(gameRules());
                $alt = doctor_closest($tokens[1], $all, 4);
                $existsLater = isset(gameRules()[$tokens[1]]);
                if ($existsLater) {
                    $problems[] = doctor_problem('error', $tokens[1] . ' does not exist in ' . $verInfo['label'],
                        'That gamerule was added in a later version.',
                        'Update the world, or pick a different rule.', true);
                } else {
                    $problems[] = doctor_problem('error', $tokens[1] . ' is not a gamerule',
                        'Gamerule names are case sensitive and use capital letters inside the word.',
                        $alt ? 'Did you mean ' . $alt . '?' : 'Type /gamerule in game to see the full list.');
                }
            } elseif (isset($tokens[2])) {
                $type = $rules[$tokens[1]]['type'];
                if ($type === 'bool' && !in_array($tokens[2], ['true', 'false'], true)) {
                    $problems[] = doctor_problem('error', $tokens[1] . ' takes true or false',
                        '"' . $tokens[2] . '" is not a value this rule understands.',
                        'Use true or false, in lower case.');
                }
                if ($type === 'int' && !preg_match('/^-?\d+$/', $tokens[2])) {
                    $problems[] = doctor_problem('error', $tokens[1] . ' takes a whole number',
                        '"' . $tokens[2] . '" is not a number.', 'Put a whole number there.');
                }
            }
            break;

        case 'summon':
            if (count($tokens) < 2) {
                $problems[] = doctor_problem('error', '/summon needs something to summon',
                    'The form is /summon <entity> [x y z] [nbt].', 'For example /summon zombie ~ ~ ~');
                break;
            }
            $eid = preg_replace('/^minecraft:/', '', $tokens[1]);
            $entities = gameFilter(gameEntities(), $version);
            if (!isset($entities[$eid])) {
                $alt = doctor_closest($eid, array_keys(gameEntities()), 3);
                if (isset(gameEntities()[$eid])) {
                    $problems[] = doctor_problem('error', $eid . ' does not exist in ' . $verInfo['label'],
                        'That entity was added in a later version.', 'Pick another mob, or change the version above.', true);
                } elseif ($alt) {
                    $problems[] = doctor_problem('warn', 'Unknown entity "' . $eid . '"',
                        'This is not in the app\'s entity list.', 'Did you mean ' . $alt . '?');
                }
            }
            if (isset($tokens[2], $tokens[3], $tokens[4])) {
                $problems = array_merge($problems, doctor_check_coords([$tokens[2], $tokens[3], $tokens[4]]));
            }
            break;

        case 'tp':
        case 'teleport':
            // The coordinate block starts after the target, unless the
            // whole command is "/tp <destination>" or "/tp <target> <dest>".
            $coordStart = null;
            for ($i = 1; $i < count($tokens); $i++) {
                if (preg_match('/^[~^]|^-?\d+(\.\d+)?$/', $tokens[$i]) && isset($tokens[$i + 1], $tokens[$i + 2])
                    && preg_match('/^[~^]|^-?\d+(\.\d+)?$/', $tokens[$i + 1])
                    && preg_match('/^[~^]|^-?\d+(\.\d+)?$/', $tokens[$i + 2])) {
                    $coordStart = $i;
                    break;
                }
            }
            if ($coordStart !== null) {
                $problems = array_merge($problems, doctor_check_coords(array_slice($tokens, $coordStart, 3)));
            }
            if (count($tokens) < 2) {
                $problems[] = doctor_problem('error', '/tp needs somewhere to go',
                    'The form is /tp <target> <x> <y> <z> or /tp <target> <destination>.',
                    'For example /tp @s ~ ~10 ~');
            }
            break;

        case 'gamemode':
            if (isset($tokens[1]) && !in_array($tokens[1], ['survival', 'creative', 'adventure', 'spectator', '0', '1', '2', '3', 's', 'c', 'a', 'sp'], true)) {
                $alt = doctor_closest($tokens[1], ['survival', 'creative', 'adventure', 'spectator'], 4);
                $problems[] = doctor_problem('error', '"' . $tokens[1] . '" is not a game mode',
                    'The modes are survival, creative, adventure and spectator.',
                    $alt ? 'Did you mean ' . $alt . '?' : 'Pick one of the four modes.');
            }
            break;

        case 'difficulty':
            if (isset($tokens[1]) && !in_array($tokens[1], ['peaceful', 'easy', 'normal', 'hard'], true)) {
                $alt = doctor_closest($tokens[1], ['peaceful', 'easy', 'normal', 'hard'], 4);
                $problems[] = doctor_problem('error', '"' . $tokens[1] . '" is not a difficulty',
                    'The levels are peaceful, easy, normal and hard.',
                    $alt ? 'Did you mean ' . $alt . '?' : '');
            }
            break;

        case 'tellraw':
        case 'title':
            $jsonIndex = $name === 'tellraw' ? 2 : 3;
            if (isset($tokens[$jsonIndex])) {
                $t = $tokens[$jsonIndex];
                if (($t[0] === '{' || $t[0] === '[' || $t[0] === '"') && json_decode($t) === null) {
                    // SNBT is accepted from 1.21.5, so unquoted keys are only an error before that
                    if (mcHas($version, 'snbt_text')) {
                        $problems[] = doctor_problem('warn', 'That text component is not valid JSON',
                            'From 1.21.5 the game also accepts SNBT here, so unquoted keys are fine. Anything else is still a mistake.',
                            'Check for a missing comma, a stray bracket, or a quote inside the text that needs a backslash in front of it.');
                    } else {
                        $problems[] = doctor_problem('error', 'That text component is not valid JSON',
                            'Before 1.21.5 this argument must be strict JSON — every key in double quotes.',
                            'Write {"text":"Hello","color":"gold"} rather than {text:"Hello",color:gold}.', true);
                    }
                }
            }
            break;

        case 'xp':
        case 'experience':
            if (isset($tokens[1]) && !in_array($tokens[1], ['add', 'set', 'query'], true) && preg_match('/^-?\d+L?$/i', $tokens[1])) {
                $problems[] = doctor_problem('error', 'That is Bedrock syntax',
                    'Bedrock writes /xp <amount> <player>. Java needs add, set or query first.',
                    'On Java use /experience add <target> ' . rtrim($tokens[1], 'Ll') . ' levels', true);
            }
            break;

        case 'enchant':
            $problems[] = doctor_problem('info', '/enchant follows the survival rules',
                'It refuses levels above the normal maximum and enchantments the item cannot take.',
                'Use /give with the enchantments component if you want to go beyond that.');
            break;

        case 'execute':
            $problems = array_merge($problems, doctor_check_execute($tokens));
            break;
    }

    if ($changed) {
        $fixed = ($hadSlash ? '/' : '') . implode(' ', $rebuilt);
    }

    $hasError = false;
    foreach ($problems as $p) if ($p['level'] === 'error') $hasError = true;

    return [
        'ok'       => !$hasError,
        'command'  => $raw,
        'version'  => $verInfo,
        'problems' => $problems,
        'fixed'    => $fixed,
    ];
}

/** Check one item or block argument, and rewrite it for the target syntax if needed. */
function doctor_check_item(string $arg, array $syn, array $verInfo, string $kind): array
{
    $problems = [];
    $parts = doctor_split_item($arg);
    $id = preg_replace('/^minecraft:/', '', $parts['id']);

    if ($id !== '' && !str_starts_with($id, '#')) {
        $known = doctor_known_items();
        if (!in_array($id, $known, true)) {
            $alt = doctor_closest($id, $known, 2);
            if ($alt) {
                $problems[] = doctor_problem('warn', 'Unknown ' . $kind . ' "' . $id . '"',
                    'This is not in the app\'s ' . $kind . ' list, though the list does not cover everything.',
                    'Did you mean ' . $alt . '?');
            }
        }
    }

    // Legacy NBT written on a components version
    if ($parts['curly'] !== null && ($syn['items'] ?? '') === 'components' && $kind === 'item') {
        try {
            $nbt = snbt_parse($parts['curly']);
        } catch (SnbtError $e) {
            $problems[] = doctor_problem('error', 'The data after the item cannot be read',
                $e->getMessage(), 'Check the braces, commas and quotes inside it.');
            return [null, $problems];
        }
        [$comps, $notes] = doctor_nbt_to_components($nbt['v'] ?? [], $syn);
        $problems[] = doctor_problem('error', 'This uses the old NBT syntax',
            'From 1.20.5 items stopped using item{...} and moved to components written item[name=value]. '
            . 'On ' . $verInfo['label'] . ' the game will reject the curly-brace form.',
            'Rewritten below using components.', true);
        foreach ($notes as $n) $problems[] = doctor_problem('warn', 'Part of the data needs checking', $n, '');
        return [$parts['id'] . doctor_components_string($comps), $problems];
    }

    // Components written on a version that predates them
    if ($parts['square'] !== null && ($syn['items'] ?? '') === 'nbt' && $kind === 'item') {
        $problems[] = doctor_problem('error', 'This uses item components, which ' . $verInfo['label'] . ' does not have',
            'Components arrived in 1.20.5. Before that, items carried NBT in curly braces.',
            'Either update the world, or rewrite it as item{display:{Name:...},Enchantments:[...]}.', true);
        return [null, $problems];
    }

    // Component-era mismatches within the components themselves
    if ($parts['square'] !== null && ($syn['items'] ?? '') === 'components') {
        $comps = doctor_parse_components($parts['square']);
        if ($comps === null) return [null, $problems];
        $out = $comps; $touched = false;

        foreach (['enchantments', 'stored_enchantments'] as $ec) {
            if (!isset($out[$ec]) || $out[$ec]['t'] !== 'compound') continue;
            $hasLevels = isset($out[$ec]['v']['levels']);
            if (($syn['enchant'] ?? '') === 'flat' && $hasLevels) {
                $out[$ec] = $out[$ec]['v']['levels'];
                $touched = true;
                $problems[] = doctor_problem('error', 'The enchantments component no longer uses "levels"',
                    'In 1.21.5 the wrapper was removed, so it is written enchantments={sharpness:5} rather than enchantments={levels:{sharpness:5}}.',
                    'Wrapper removed below.', true);
            } elseif (($syn['enchant'] ?? '') === 'levels' && !$hasLevels) {
                $out[$ec] = snbt_node_compound(['levels' => $out[$ec]]);
                $touched = true;
                $problems[] = doctor_problem('error', 'The enchantments component needs a "levels" wrapper here',
                    'On ' . $verInfo['label'] . ' it is written enchantments={levels:{sharpness:5}}. The flat form arrived in 1.21.5.',
                    'Wrapper added below.', true);
            }
        }

        foreach (['custom_name', 'item_name'] as $tc) {
            if (!isset($out[$tc])) continue;
            $want = $syn['text_in_nbt'] ?? 'json_string';
            $isString = $out[$tc]['t'] === 'string';
            if ($want === 'snbt' && $isString && json_decode($out[$tc]['v'], true) !== null) {
                $out[$tc] = doctor_text_node($out[$tc], $syn);
                $touched = true;
                $problems[] = doctor_problem('warn', 'Text no longer has to be a quoted JSON string',
                    'From 1.21.5 text components inside item data are written as plain SNBT. The old quoted form still parses, but the new one is what the game produces.',
                    'Rewritten below.', true);
            } elseif ($want === 'json_string' && $out[$tc]['t'] === 'compound') {
                $out[$tc] = doctor_text_node($out[$tc], $syn);
                $touched = true;
                $problems[] = doctor_problem('error', 'Text here must be a quoted JSON string',
                    'On ' . $verInfo['label'] . ' text inside a component is stored as a JSON string in single quotes. Writing it as a bare compound only works from 1.21.5.',
                    'Rewritten below.', true);
            }
        }

        if (isset($out['tooltip_display']) && ($syn['hide'] ?? '') !== 'tooltip_display') {
            $problems[] = doctor_problem('error', 'tooltip_display does not exist in ' . $verInfo['label'],
                'It was added in 1.21.5 to replace the per-component show_in_tooltip flags.',
                'Use show_in_tooltip:false inside each component instead.', true);
        }

        if ($touched) return [$parts['id'] . doctor_components_string($out) . ($parts['curly'] ?? ''), $problems];
    }

    return [null, $problems];
}

function doctor_check_effect(array $tokens, string $version): array
{
    $problems = [];
    $sub = $tokens[1] ?? '';
    if (!in_array($sub, ['give', 'clear'], true)) {
        $problems[] = doctor_problem('error', '/effect needs give or clear first',
            'Java writes /effect give <target> <effect> … or /effect clear <target>. Bedrock leaves the word out, which is probably where this came from.',
            'Try /effect give ' . ($sub ?: '@p') . ' ' . ($tokens[2] ?? 'speed') . ' 30 0', true);
        return $problems;
    }
    if ($sub === 'give') {
        if (count($tokens) < 4) {
            $problems[] = doctor_problem('error', '/effect give needs a target and an effect',
                'The form is /effect give <target> <effect> [seconds] [amplifier] [hideParticles].', '');
            return $problems;
        }
        $eff = preg_replace('/^minecraft:/', '', $tokens[3]);
        $effects = gameFilter(gameEffects(), $version);
        if (!isset($effects[$eff])) {
            $alt = doctor_closest($eff, array_keys(gameEffects()), 3);
            if (isset(gameEffects()[$eff])) {
                $problems[] = doctor_problem('error', $eff . ' does not exist in ' . mcVersion($version)['label'],
                    'That effect was added in a later version.', 'Pick another effect, or change the version above.', true);
            } else {
                $problems[] = doctor_problem('error', '"' . $eff . '" is not an effect',
                    'Effect ids use underscores, like night_vision and fire_resistance.',
                    $alt ? 'Did you mean ' . $alt . '?' : 'Check the spelling.');
            }
        }
        if (isset($tokens[4]) && $tokens[4] === 'infinite' && mcVersion($version)['rank'] < 30) {
            $problems[] = doctor_problem('error', 'infinite is not available here',
                'The infinite keyword was added in 1.19.4.',
                'Use a long duration in seconds instead, up to 1000000.', true);
        }
        if (isset($tokens[5]) && preg_match('/^\d+$/', $tokens[5]) && (int)$tokens[5] > 0) {
            $lvl = (int)$tokens[5] + 1;
            $problems[] = doctor_problem('info', 'Amplifier ' . $tokens[5] . ' means level ' . $lvl,
                'The game counts amplifiers from zero, so 0 is level I and ' . $tokens[5] . ' is level ' . $lvl . '.',
                'That is probably what you want — just checking.');
        }
    }
    return $problems;
}

function doctor_check_execute(array $tokens): array
{
    $problems = [];
    $subs = ['as', 'at', 'positioned', 'rotated', 'facing', 'align', 'anchored', 'in', 'summon', 'on', 'if', 'unless', 'store', 'run'];
    $seenRun = false;
    for ($i = 1; $i < count($tokens); $i++) {
        if ($tokens[$i] === 'run') { $seenRun = true; break; }
    }
    if (!$seenRun) {
        $last = end($tokens);
        $isTest = false;
        foreach ($tokens as $t) if ($t === 'if' || $t === 'unless') $isTest = true;
        if (!$isTest) {
            $problems[] = doctor_problem('error', '/execute never says what to run',
                'Every execute chain ends with "run" followed by the command it should carry out — unless the whole point is an if/unless test.',
                'Add run and the command, for example: … run say hello');
        }
    }
    if (isset($tokens[1]) && !in_array($tokens[1], $subs, true)) {
        $alt = doctor_closest($tokens[1], $subs, 3);
        $problems[] = doctor_problem('error', '"' . $tokens[1] . '" is not part of /execute',
            'The chain is built from as, at, positioned, rotated, facing, if, unless, store and run.',
            $alt ? 'Did you mean ' . $alt . '?' : '');
    }
    return $problems;
}

/** Volume of a /fill region when every axis is comparable. Null when it is not. */
function doctor_fill_volume(array $c): ?int
{
    $size = [];
    for ($i = 0; $i < 3; $i++) {
        $a = $c[$i] ?? ''; $b = $c[$i + 3] ?? '';
        if ($a === '' || $b === '') return null;
        $ra = $a[0] === '~'; $rb = $b[0] === '~';
        if ($a[0] === '^' || $b[0] === '^' || $ra !== $rb) return null;
        $na = $ra ? ($a === '~' ? 0 : (float)substr($a, 1)) : (float)$a;
        $nb = $rb ? ($b === '~' ? 0 : (float)substr($b, 1)) : (float)$b;
        if (!is_numeric($ra ? ($a === '~' ? '0' : substr($a, 1)) : $a)) return null;
        $size[] = (int)abs(floor($nb) - floor($na)) + 1;
    }
    return $size[0] * $size[1] * $size[2];
}

// ================================================
// EXPLAINER
// ================================================

/** Plain-language description of a target selector. */
function doctor_say_target(string $t): string
{
    if ($t === '') return 'nobody in particular';
    if ($t[0] !== '@') return 'the player ' . $t;

    $base = [
        '@p' => 'the nearest player', '@a' => 'every player', '@r' => 'a random player',
        '@s' => 'whoever runs the command', '@e' => 'every entity', '@n' => 'the nearest entity',
    ][substr($t, 0, 2)] ?? 'a target';

    if (!preg_match('/\[(.*)\]$/s', $t, $m)) return $base;

    $args = doctor_selector_args($m[1]);
    $bits = [];
    foreach ($args as $k => $val) {
        switch ($k) {
            case 'type':
                $neg = str_starts_with($val, '!');
                $name = str_replace('_', ' ', preg_replace('/^!?(minecraft:)?/', '', $val));
                $bits[] = ($neg ? 'that is not a ' : 'that is a ') . $name;
                break;
            case 'distance':
                if (str_starts_with($val, '..')) $bits[] = 'within ' . substr($val, 2) . ' blocks';
                elseif (str_ends_with($val, '..')) $bits[] = 'at least ' . substr($val, 0, -2) . ' blocks away';
                elseif (str_contains($val, '..')) $bits[] = 'between ' . str_replace('..', ' and ', $val) . ' blocks away';
                else $bits[] = 'exactly ' . $val . ' blocks away';
                break;
            case 'limit':    $bits[] = 'at most ' . $val . ' of them'; break;
            case 'sort':     $bits[] = 'sorted by ' . $val; break;
            case 'tag':      $bits[] = str_starts_with($val, '!') ? 'without the tag ' . substr($val, 1) : 'tagged ' . $val; break;
            case 'team':     $bits[] = 'on the team ' . $val; break;
            case 'name':     $bits[] = 'named ' . trim($val, '"'); break;
            case 'gamemode': $bits[] = 'in ' . $val . ' mode'; break;
            case 'scores':   $bits[] = 'whose scores match ' . $val; break;
            case 'level':    $bits[] = 'at experience level ' . $val; break;
            case 'nbt':      $bits[] = 'whose data matches ' . $val; break;
            default:         $bits[] = $k . ' = ' . $val;
        }
    }
    if (!$bits) return $base;
    $last = array_pop($bits);
    return $base . ' ' . ($bits ? implode(', ', $bits) . ' and ' . $last : $last);
}

function doctor_say_coords(array $c): string
{
    if (count($c) < 3) return 'a position';
    if ($c[0] === '~' && $c[1] === '~' && $c[2] === '~') return 'where the command runs';
    if ($c[0][0] === '^') return 'a spot relative to the direction you are facing (' . implode(' ', $c) . ')';
    $rel = 0;
    foreach ($c as $v) if ($v[0] === '~') $rel++;
    if ($rel === 3) {
        $parts = [];
        $names = ['east/west', 'up/down', 'south/north'];
        foreach ($c as $i => $v) {
            $n = $v === '~' ? 0 : (float)substr($v, 1);
            if ($n === 0.0) continue;
            if ($i === 1) $parts[] = abs($n) . ' block' . (abs($n) == 1 ? '' : 's') . ($n > 0 ? ' up' : ' down');
            else $parts[] = abs($n) . ' block' . (abs($n) == 1 ? '' : 's') . ' away on ' . ($i === 0 ? 'X' : 'Z');
        }
        return $parts ? implode(' and ', $parts) . ' from where the command runs' : 'where the command runs';
    }
    return 'the position ' . implode(' ', $c);
}

/**
 * Explain a command in plain language.
 * Returns ['summary', 'steps' => [['label','text'], …], 'command'].
 */
function doctorExplain(string $raw, string $version = ''): array
{
    $version = $version ?: mcCurrentVersion();
    $cmd = ltrim(trim($raw), '/');
    if ($cmd === '') {
        return ['summary' => '', 'steps' => [], 'command' => $raw];
    }
    if (doctor_balance($cmd)) {
        return ['summary' => 'This command cannot be read — its brackets or quotes do not match up.',
                'steps' => [['Fix it first', 'Run it through the Doctor tab to find the unclosed bracket.']],
                'command' => $raw];
    }

    $tokens = doctor_tokens($cmd);
    $name = strtolower($tokens[0] ?? '');
    $steps = [];
    $summary = '';

    switch ($name) {
        case 'execute':
            return doctor_explain_execute($tokens, $raw, $version);

        case 'give':
            $summary = 'Puts an item straight into an inventory.';
            $steps[] = ['Who gets it', ucfirst(doctor_say_target($tokens[1] ?? '@p')) . '.'];
            $parts = doctor_split_item($tokens[2] ?? '');
            $steps[] = ['What', str_replace('_', ' ', preg_replace('/^minecraft:/', '', $parts['id'])) . (isset($tokens[3]) ? ' × ' . $tokens[3] : ' × 1') . '.'];
            if ($parts['square'] || $parts['curly']) {
                $steps[] = ['Extra data', 'The item carries custom data — name, lore, enchantments or similar. See the breakdown below.'];
                foreach (doctor_explain_item_data($parts) as $line) $steps[] = ['·', $line];
            }
            break;

        case 'tp':
        case 'teleport':
            $summary = 'Moves something to a different place.';
            $n = count($tokens);
            if ($n === 2) {
                $steps[] = ['Who moves', 'Whoever runs the command.'];
                $steps[] = ['Where to', ucfirst(doctor_say_target($tokens[1]))  . '.'];
            } elseif ($n >= 5 && preg_match('/^[~^-]|^\d/', $tokens[2])) {
                $steps[] = ['Who moves', ucfirst(doctor_say_target($tokens[1])) . '.'];
                $steps[] = ['Where to', ucfirst(doctor_say_coords(array_slice($tokens, 2, 3))) . '.'];
                if ($n >= 7) $steps[] = ['Facing', 'It also sets the direction they look: yaw ' . $tokens[5] . ', pitch ' . $tokens[6] . '.'];
            } else {
                $steps[] = ['Who moves', ucfirst(doctor_say_target($tokens[1] ?? '@s')) . '.'];
                $steps[] = ['Where to', 'To ' . doctor_say_target($tokens[2] ?? '@p') . '.'];
            }
            break;

        case 'effect':
            if (($tokens[1] ?? '') === 'clear') {
                $summary = 'Removes potion effects.';
                $steps[] = ['Who', ucfirst(doctor_say_target($tokens[2] ?? '@s')) . '.'];
                $steps[] = ['What', isset($tokens[3]) ? 'Only ' . str_replace('_', ' ', $tokens[3]) . '.' : 'Every effect they currently have.'];
            } else {
                $summary = 'Applies a potion effect.';
                $steps[] = ['Who', ucfirst(doctor_say_target($tokens[2] ?? '@p')) . '.'];
                $lvl = isset($tokens[5]) && is_numeric($tokens[5]) ? (int)$tokens[5] + 1 : 1;
                $steps[] = ['What', str_replace('_', ' ', preg_replace('/^minecraft:/', '', $tokens[3] ?? '')) . ' at level ' . $lvl . '.'];
                $dur = $tokens[4] ?? '30';
                $steps[] = ['How long', $dur === 'infinite' ? 'Forever, until it is cleared.' : $dur . ' seconds.'];
                if (($tokens[6] ?? '') === 'true') $steps[] = ['Particles', 'Hidden — no swirling particles will show.'];
            }
            break;

        case 'fill':
            $summary = 'Changes every block inside a box.';
            $steps[] = ['The region', 'From ' . implode(' ', array_slice($tokens, 1, 3)) . ' to ' . implode(' ', array_slice($tokens, 4, 3)) . '. Both corners are included.'];
            $vol = doctor_fill_volume(array_slice($tokens, 1, 6));
            if ($vol !== null) $steps[] = ['Size', number_format($vol) . ' blocks.'];
            $steps[] = ['Filled with', str_replace('_', ' ', preg_replace('/^minecraft:/', '', doctor_split_item($tokens[7] ?? '')['id'])) . '.'];
            $mode = $tokens[8] ?? 'replace';
            $modeText = [
                'replace' => 'Everything in the box is overwritten.',
                'hollow'  => 'Only the outer shell is filled — the inside is cleared to air.',
                'outline' => 'Only the outer shell is filled — the inside is left alone.',
                'keep'    => 'Only air is filled; existing blocks stay.',
                'destroy' => 'Existing blocks are broken and drop as items.',
            ][$mode] ?? 'Only ' . ($tokens[9] ?? '') . ' is replaced.';
            $steps[] = ['Mode', $modeText];
            break;

        case 'clone':
            $summary = 'Copies a region of blocks somewhere else.';
            $steps[] = ['Copy from', implode(' ', array_slice($tokens, 1, 3)) . ' to ' . implode(' ', array_slice($tokens, 4, 3)) . '.'];
            $steps[] = ['Paste at', implode(' ', array_slice($tokens, 7, 3)) . ' — this is where the lowest north-west corner of the copy lands.'];
            if (in_array('masked', $tokens, true)) $steps[] = ['Air', 'Air in the source is skipped, so the copy is pasted over what is already there.'];
            if (in_array('move', $tokens, true)) $steps[] = ['Afterwards', 'The original region is cleared to air.'];
            break;

        case 'gamemode':
            $summary = 'Changes the game mode.';
            $steps[] = ['Mode', ucfirst($tokens[1] ?? '') . '.'];
            $steps[] = ['Who', ucfirst(doctor_say_target($tokens[2] ?? '@s')) . '.'];
            break;

        case 'kill':
            $summary = 'Removes entities from the world instantly.';
            $steps[] = ['What is removed', ucfirst(doctor_say_target($tokens[1] ?? '@s')) . '.'];
            $steps[] = ['Warning', 'There is no undo. Anything matching that selector is gone.'];
            break;

        case 'summon':
            $summary = 'Spawns an entity.';
            $steps[] = ['What', str_replace('_', ' ', preg_replace('/^minecraft:/', '', $tokens[1] ?? '')) . '.'];
            $steps[] = ['Where', isset($tokens[4]) ? ucfirst(doctor_say_coords(array_slice($tokens, 2, 3))) . '.' : 'Where the command runs.'];
            if (isset($tokens[5]) || (isset($tokens[2]) && $tokens[2][0] === '{')) {
                $steps[] = ['Extra data', 'It is given custom entity data — a name, no AI, invulnerability or similar.'];
            }
            break;

        case 'gamerule':
            $rules = gameRules();
            $r = $rules[$tokens[1] ?? ''] ?? null;
            $summary = 'Changes a world setting.';
            $steps[] = ['Rule', ($r['label'] ?? ($tokens[1] ?? '')) . ($r && !empty($r['desc']) ? ' — ' . $r['desc'] : '')];
            $steps[] = ['Set to', isset($tokens[2]) ? $tokens[2] : 'nothing — it just reports the current value.'];
            break;

        case 'time':
            $summary = 'Changes the world time.';
            $steps[] = ['Action', ucfirst($tokens[1] ?? '') . ' ' . ($tokens[2] ?? '') . '.'];
            $ticks = $tokens[2] ?? '';
            $named = ['1000' => 'sunrise', '6000' => 'midday', '13000' => 'nightfall', '18000' => 'midnight'];
            if (isset($named[$ticks])) $steps[] = ['In plain terms', 'Tick ' . $ticks . ' is ' . $named[$ticks] . '.'];
            break;

        case 'scoreboard':
            $summary = 'Works with the scoreboard system.';
            $steps[] = ['Area', ($tokens[1] ?? '') === 'objectives' ? 'Objectives — the counters themselves.' : 'Players — the values held against each entity.'];
            $steps[] = ['Action', ucfirst($tokens[2] ?? '') . '.'];
            break;

        case 'tellraw':
            $summary = 'Sends a formatted message to chat.';
            $steps[] = ['Who sees it', ucfirst(doctor_say_target($tokens[1] ?? '@a')) . '.'];
            $steps[] = ['Message', 'Built from a text component, so it can carry colours, hover text and click actions.'];
            break;

        default:
            $summary = 'Runs the /' . $name . ' command.';
            foreach (array_slice($tokens, 1) as $i => $t) {
                $steps[] = ['Argument ' . ($i + 1), $t];
            }
    }

    return ['summary' => $summary, 'steps' => $steps, 'command' => $raw];
}

function doctor_explain_item_data(array $parts): array
{
    $lines = [];
    $source = $parts['square'] ?? $parts['curly'];
    if ($source === null) return $lines;

    if ($parts['square'] !== null) {
        $comps = doctor_parse_components($parts['square']);
        if (!$comps) return $lines;
        foreach ($comps as $name => $node) {
            $lines[] = match (preg_replace('/^minecraft:/', '', $name)) {
                'custom_name'   => 'Renamed to ' . doctor_text_preview($node) . '.',
                'item_name'     => 'Its default name is changed to ' . doctor_text_preview($node) . '.',
                'lore'          => 'Has ' . count($node['v'] ?? []) . ' line(s) of lore under the name.',
                'enchantments'  => 'Enchanted: ' . doctor_enchant_preview($node) . '.',
                'unbreakable'   => 'Unbreakable — it never loses durability.',
                'damage'        => 'Starts partly used (damage ' . ($node['v'] ?? '?') . ').',
                'tooltip_display' => 'Some tooltip lines are hidden.',
                default         => 'Carries the ' . $name . ' component.',
            };
        }
        return $lines;
    }

    try { $nbt = snbt_parse($parts['curly']); } catch (SnbtError $e) { return $lines; }
    foreach (($nbt['v'] ?? []) as $key => $node) {
        $lines[] = match ($key) {
            'display'      => 'Has a custom name and/or lore.',
            'Enchantments' => 'Enchanted with ' . count($node['v'] ?? []) . ' enchantment(s).',
            'Unbreakable'  => 'Unbreakable — it never loses durability.',
            'HideFlags'    => 'Some tooltip lines are hidden.',
            default        => 'Carries the ' . $key . ' tag.',
        };
    }
    return $lines;
}

function doctor_text_preview($node): string
{
    $plain = snbt_plain($node);
    if (is_string($plain)) {
        $decoded = json_decode($plain, true);
        if (is_array($decoded)) $plain = $decoded;
        else return '"' . $plain . '"';
    }
    if (is_array($plain) && isset($plain['text'])) {
        return '"' . $plain['text'] . '"' . (isset($plain['color']) ? ' in ' . str_replace('_', ' ', $plain['color']) : '');
    }
    return 'custom text';
}

function doctor_enchant_preview($node): string
{
    $plain = snbt_plain($node);
    if (isset($plain['levels'])) $plain = $plain['levels'];
    if (!is_array($plain)) return 'unknown';
    $bits = [];
    foreach ($plain as $id => $lvl) {
        if (!is_scalar($lvl)) continue;
        $bits[] = str_replace('_', ' ', preg_replace('/^minecraft:/', '', (string)$id)) . ' ' . $lvl;
    }
    return $bits ? implode(', ', $bits) : 'unknown';
}

/** /execute deserves its own walk-through — it is the command people ask about most. */
function doctor_explain_execute(array $tokens, string $raw, string $version): array
{
    $steps = [];
    $i = 1;
    $n = count($tokens);
    $context = ['who' => 'whoever ran it', 'where' => 'where it was run'];

    while ($i < $n) {
        $word = $tokens[$i];
        switch ($word) {
            case 'as':
                $t = $tokens[++$i] ?? '';
                $context['who'] = doctor_say_target($t);
                $steps[] = ['as ' . $t, 'Run the rest once for ' . doctor_say_target($t) . '. This changes who "@s" means, but not where the command happens.'];
                break;
            case 'at':
                $t = $tokens[++$i] ?? '';
                $context['where'] = 'at ' . doctor_say_target($t);
                $steps[] = ['at ' . $t, 'Move the point the command runs from to ' . doctor_say_target($t) . '. This changes where ~ ~ ~ means.'];
                break;
            case 'positioned':
                if (($tokens[$i + 1] ?? '') === 'as') {
                    $t = $tokens[$i + 2] ?? ''; $i += 2;
                    $steps[] = ['positioned as ' . $t, 'Take the position from ' . doctor_say_target($t) . ', but keep the current rotation.'];
                } else {
                    $c = array_slice($tokens, $i + 1, 3); $i += 3;
                    $steps[] = ['positioned ' . implode(' ', $c), 'Run from ' . doctor_say_coords($c) . '.'];
                }
                break;
            case 'rotated':
                if (($tokens[$i + 1] ?? '') === 'as') { $t = $tokens[$i + 2] ?? ''; $i += 2;
                    $steps[] = ['rotated as ' . $t, 'Face the same way as ' . doctor_say_target($t) . '.'];
                } else { $c = array_slice($tokens, $i + 1, 2); $i += 2;
                    $steps[] = ['rotated ' . implode(' ', $c), 'Face yaw ' . ($c[0] ?? '') . ', pitch ' . ($c[1] ?? '') . '.']; }
                break;
            case 'facing':
                if (($tokens[$i + 1] ?? '') === 'entity') { $t = $tokens[$i + 2] ?? ''; $i += 3;
                    $steps[] = ['facing entity ' . $t, 'Turn to look at ' . doctor_say_target($t) . '.'];
                } else { $c = array_slice($tokens, $i + 1, 3); $i += 3;
                    $steps[] = ['facing ' . implode(' ', $c), 'Turn to look at ' . doctor_say_coords($c) . '.']; }
                break;
            case 'in':
                $d = $tokens[++$i] ?? '';
                $steps[] = ['in ' . $d, 'Switch to the ' . str_replace('_', ' ', preg_replace('/^minecraft:/', '', $d)) . ' dimension.'];
                break;
            case 'anchored':
                $a = $tokens[++$i] ?? '';
                $steps[] = ['anchored ' . $a, 'Measure from the ' . $a . ' rather than the feet.'];
                break;
            case 'if':
            case 'unless':
                $kind = $tokens[++$i] ?? '';
                $rest = [];
                while ($i + 1 < $n && !in_array($tokens[$i + 1], ['run', 'if', 'unless', 'as', 'at', 'positioned', 'rotated', 'facing', 'store', 'in', 'anchored'], true)) {
                    $rest[] = $tokens[++$i];
                }
                $desc = match ($kind) {
                    'entity' => ($word === 'if'
                        ? 'Carry on only if there is at least one match for ' . doctor_say_target($rest[0] ?? '') . '.'
                        : 'Carry on only if there is no match for ' . doctor_say_target($rest[0] ?? '') . '.'),
                    'block'  => ($word === 'if' ? 'Carry on only if ' : 'Stop if ') . 'the block at ' . implode(' ', array_slice($rest, 0, 3)) . ' is ' . ($rest[3] ?? '') . '.',
                    'blocks' => 'Compare two regions of blocks and carry on ' . ($word === 'if' ? 'if they match.' : 'if they differ.'),
                    'score'  => 'Compare scoreboard values and carry on ' . ($word === 'if' ? 'if the test passes.' : 'if it fails.'),
                    'data'   => 'Carry on ' . ($word === 'if' ? 'if' : 'unless') . ' that data exists.',
                    default  => 'A ' . $word . ' ' . $kind . ' test.',
                };
                $steps[] = [$word . ' ' . $kind, $desc];
                break;
            case 'store':
                $rest = [];
                while ($i + 1 < $n && $tokens[$i + 1] !== 'run') $rest[] = $tokens[++$i];
                $steps[] = ['store', 'Save the result of the command into ' . implode(' ', array_slice($rest, 1, 3)) . ' rather than only running it.'];
                break;
            case 'run':
                $inner = implode(' ', array_slice($tokens, $i + 1));
                $steps[] = ['run', 'Finally, run: ' . $inner];
                $sub = doctorExplain($inner, $version);
                if ($sub['summary']) $steps[] = ['· what that does', $sub['summary']];
                $i = $n;
                break;
        }
        $i++;
    }

    $summary = 'Runs another command as ' . $context['who'] . ', ' . $context['where'] . '.';
    if (!$steps) $summary = 'An /execute chain — but nothing was recognised in it.';

    return ['summary' => $summary, 'steps' => $steps, 'command' => $raw];
}
