<?php
// ================================================
// lib/data/enchantments.php — Enchantment Hub data
// ------------------------------------------------
// items.php already owns "which enchantments apply to which item slot,
// and their normal maximum level" ($ENCHANTS) — that stays the single
// source of truth and is reused here, not duplicated. This file only
// adds what does not exist anywhere else yet: display names,
// descriptions, a usefulness rating, and the conflict/incompatibility
// rules, all verified against current Minecraft Java Edition mechanics
// (see the PR description for sources — nothing here is guessed).
//
// "Usefulness" (tier, 1-5) is this app's own recommendation, not an
// official Minecraft rating — it is labelled as such in the UI.
// ================================================
require_once __DIR__ . '/../mc.php';

/**
 * Per-enchantment metadata keyed by Minecraft id. Max level and slot
 * applicability live in items.php's $ENCHANTS — this only supplies what
 * that table does not: name, description, category and usefulness tier.
 * gate names a feature key in MC_FEATURES (see lib/mc.php) that must be
 * present for this enchantment to be offered — currently only the three
 * mace-exclusive enchantments need this.
 */
function enchantMeta(): array
{
    static $data = null;
    if ($data !== null) return $data;

    $data = [
        // Melee damage — mutually exclusive as a group
        'sharpness'          => ['name' => 'Sharpness',           'category' => 'offense', 'tier' => 5, 'desc' => 'Extra damage against anything.'],
        'smite'              => ['name' => 'Smite',                'category' => 'offense', 'tier' => 3, 'desc' => 'Extra damage against undead mobs only.'],
        'bane_of_arthropods' => ['name' => 'Bane of Arthropods',   'category' => 'offense', 'tier' => 2, 'desc' => 'Extra damage against spiders, silverfish and endermites.'],

        'knockback'          => ['name' => 'Knockback',            'category' => 'offense', 'tier' => 3, 'desc' => 'Knocks targets back further when hit.'],
        'fire_aspect'        => ['name' => 'Fire Aspect',          'category' => 'offense', 'tier' => 3, 'desc' => 'Sets the target alight on hit.'],
        'looting'            => ['name' => 'Looting',              'category' => 'offense', 'tier' => 4, 'desc' => 'More mob drops, and a chance at rare drops.'],
        'sweeping'           => ['name' => 'Sweeping Edge',        'category' => 'offense', 'tier' => 3, 'desc' => 'Boosts the sweep-attack damage dealt to nearby mobs.'],

        // Mining
        'efficiency'         => ['name' => 'Efficiency',           'category' => 'utility', 'tier' => 5, 'desc' => 'Mines and tills faster.'],
        'silk_touch'         => ['name' => 'Silk Touch',           'category' => 'utility', 'tier' => 4, 'desc' => 'Blocks drop themselves instead of their usual item.'],
        'fortune'            => ['name' => 'Fortune',               'category' => 'utility', 'tier' => 5, 'desc' => 'More drops from ores and some blocks.'],

        // Bow / crossbow
        'power'              => ['name' => 'Power',                'category' => 'offense', 'tier' => 5, 'desc' => 'More arrow damage.'],
        'punch'              => ['name' => 'Punch',                'category' => 'offense', 'tier' => 2, 'desc' => 'Knocks targets back further when shot.'],
        'flame'              => ['name' => 'Flame',                'category' => 'offense', 'tier' => 2, 'desc' => 'Arrows set the target alight.'],
        'infinity'           => ['name' => 'Infinity',             'category' => 'utility', 'tier' => 4, 'desc' => 'Shoot without using arrows (needs at least one in your inventory).'],
        'multishot'          => ['name' => 'Multishot',            'category' => 'offense', 'tier' => 4, 'desc' => 'Fires 3 arrows at once for the cost of one.'],
        'piercing'           => ['name' => 'Piercing',             'category' => 'offense', 'tier' => 3, 'desc' => 'Arrows pass through multiple targets.'],
        'quick_charge'       => ['name' => 'Quick Charge',         'category' => 'utility', 'tier' => 3, 'desc' => 'Reloads the crossbow faster.'],

        // Armour — protections are mutually exclusive as a group
        'protection'            => ['name' => 'Protection',            'category' => 'defense', 'tier' => 5, 'desc' => 'Reduces most kinds of damage a little.'],
        'fire_protection'       => ['name' => 'Fire Protection',       'category' => 'defense', 'tier' => 2, 'desc' => 'Reduces fire and lava damage, and burn duration.'],
        'blast_protection'      => ['name' => 'Blast Protection',      'category' => 'defense', 'tier' => 2, 'desc' => 'Reduces explosion damage and knockback.'],
        'projectile_protection' => ['name' => 'Projectile Protection', 'category' => 'defense', 'tier' => 2, 'desc' => 'Reduces damage from arrows and other projectiles.'],

        'respiration'    => ['name' => 'Respiration',      'category' => 'utility', 'tier' => 2, 'desc' => 'Extends underwater breathing time.'],
        'aqua_affinity'  => ['name' => 'Aqua Affinity',    'category' => 'utility', 'tier' => 2, 'desc' => 'Normal mining speed underwater.'],
        'thorns'         => ['name' => 'Thorns',           'category' => 'defense', 'tier' => 1, 'desc' => 'A chance to reflect damage back at attackers.'],
        'swift_sneak'    => ['name' => 'Swift Sneak',      'category' => 'utility', 'tier' => 2, 'desc' => 'Move faster while sneaking.'],
        'feather_falling'=> ['name' => 'Feather Falling',  'category' => 'defense', 'tier' => 4, 'desc' => 'Reduces fall damage.'],
        'depth_strider'  => ['name' => 'Depth Strider',    'category' => 'utility', 'tier' => 3, 'desc' => 'Swim faster underwater.'],
        'frost_walker'   => ['name' => 'Frost Walker',     'category' => 'utility', 'tier' => 3, 'desc' => 'Freezes the water you walk over.'],
        'soul_speed'     => ['name' => 'Soul Speed',       'category' => 'utility', 'tier' => 3, 'desc' => 'Move faster over soul sand and soul soil.'],

        // Trident
        'channeling'     => ['name' => 'Channeling',       'category' => 'offense', 'tier' => 2, 'desc' => 'Calls down lightning on a hit target during a thunderstorm.'],
        'loyalty'        => ['name' => 'Loyalty',          'category' => 'utility', 'tier' => 4, 'desc' => 'The trident flies back to you after being thrown.'],
        'impaling'       => ['name' => 'Impaling',         'category' => 'offense', 'tier' => 4, 'desc' => 'Extra damage against aquatic mobs.'],
        'riptide'        => ['name' => 'Riptide',          'category' => 'utility', 'tier' => 3, 'desc' => 'Launches you with the throw while in water or rain.'],

        // Fishing rod
        'luck_of_the_sea'=> ['name' => 'Luck of the Sea',  'category' => 'utility', 'tier' => 3, 'desc' => 'Better fishing loot.'],
        'lure'           => ['name' => 'Lure',             'category' => 'utility', 'tier' => 3, 'desc' => 'Shortens the wait for a bite.'],

        // Universal
        'unbreaking'     => ['name' => 'Unbreaking',       'category' => 'utility', 'tier' => 5, 'desc' => 'A chance to ignore durability damage.'],
        'mending'        => ['name' => 'Mending',          'category' => 'utility', 'tier' => 5, 'desc' => 'Repairs the item using collected XP orbs instead of levelling up.'],

        // Mace-exclusive (Java 1.21+) — density/breach are mutually
        // exclusive with smite/bane_of_arthropods on a mace specifically.
        'density'   => ['name' => 'Density',    'category' => 'mace', 'tier' => 5, 'desc' => 'More Smash Attack damage per block fallen.', 'gate' => 'mace'],
        'breach'    => ['name' => 'Breach',     'category' => 'mace', 'tier' => 4, 'desc' => 'Ignores a portion of the target\'s armour.', 'gate' => 'mace'],
        'wind_burst'=> ['name' => 'Wind Burst', 'category' => 'mace', 'tier' => 3, 'desc' => 'Launches you upward after a Smash Attack — obtained from ominous vaults only.', 'gate' => 'mace'],
    ];
    return $data;
}

function enchantName(string $id): string
{
    return enchantMeta()[$id]['name'] ?? ucwords(str_replace('_', ' ', $id));
}

/**
 * Symmetric incompatibility groups. Two enchantments conflict if they
 * share a group. Verified against current vanilla Java Edition rules:
 * damage-type enchantments, mining enchantments, protections, and a
 * handful of named pairs (see each group's comment).
 */
function enchantConflictGroups(): array
{
    return [
        ['sharpness', 'smite', 'bane_of_arthropods', 'density', 'breach'], // damage type — sharpness never applies to a mace, density/breach never apply to a sword, so this group only ever bites on the item it is relevant to
        ['silk_touch', 'fortune'],
        ['frost_walker', 'depth_strider'],
        ['infinity', 'mending'],           // only ever both offered on a bow
        ['multishot', 'piercing'],
        ['riptide', 'channeling', 'loyalty'],
        ['protection', 'fire_protection', 'blast_protection', 'projectile_protection'],
    ];
}

function enchantConflicts(string $a, string $b): bool
{
    if ($a === $b) return false;
    foreach (enchantConflictGroups() as $group) {
        if (in_array($a, $group, true) && in_array($b, $group, true)) return true;
    }
    return false;
}

/** Every enchantment id currently selected that conflicts with $id. */
function enchantConflictsWith(string $id, array $selectedIds): array
{
    return array_values(array_filter($selectedIds, fn($other) => enchantConflicts($id, $other)));
}

/** Every other enchantment id this one is incompatible with. */
function enchantIncompatibleIds(string $id): array
{
    $meta = enchantMeta();
    return array_values(array_filter(array_keys($meta), fn($other) => enchantConflicts($id, $other)));
}

/** Which slot names (Sword, Boots, Mace, …) can take this enchantment. */
function enchantApplicableSlots(string $id): array
{
    $out = [];
    foreach (enchantSlots() as $slot => $rows) {
        foreach ($rows as [$eid, $max]) {
            if ($eid === $id) { $out[$slot] = $max; break; }
        }
    }
    return $out;
}

/**
 * The full enchantment encyclopedia for the Knowledge tab: every
 * entchantment with its metadata, max level, applicable slots and
 * incompatible enchantments resolved to names. Built entirely from
 * enchantMeta()/enchantSlots()/enchantConflictGroups() — no new data.
 */
function enchantEncyclopedia(): array
{
    $out = [];
    foreach (enchantMeta() as $id => $m) {
        $slots = enchantApplicableSlots($id);
        $out[] = [
            'id' => $id,
            'name' => $m['name'],
            'desc' => $m['desc'],
            'category' => $m['category'],
            'tier' => $m['tier'],
            'gate' => $m['gate'] ?? null,
            'maxLevel' => $slots ? max($slots) : 1,
            'slots' => array_keys($slots),
            'incompatible' => array_map('enchantName', enchantIncompatibleIds($id)),
        ];
    }
    return $out;
}

function enchant_search_entries(): array
{
    $out = [];
    foreach (enchantEncyclopedia() as $e) {
        $out[] = [
            'id' => 'ench-' . $e['id'], 'icon' => '✨', 'title' => $e['name'], 'cat' => 'enchantment',
            'href' => 'knowledge.php?t=enchants&q=' . urlencode($e['id']),
            'desc' => $e['desc'],
            'keywords' => 'enchant enchantment ' . strtolower($e['name'] . ' ' . $e['category'] . ' ' . implode(' ', $e['slots'])),
        ];
    }
    return $out;
}

function items_search_entries(): array
{
    $out = [];
    foreach (itemsData()['flat'] as [$id, $name]) {
        $slot = enchantSlotForItem($id);
        $out[] = [
            'id' => 'item-' . $id, 'icon' => '📦', 'title' => $name, 'cat' => 'item',
            'href' => 'knowledge.php?t=items&q=' . urlencode($id),
            'desc' => $slot ? 'Enchantable — ' . $slot : 'Item',
            'keywords' => 'item ' . strtolower($name) . ' ' . $id,
        ];
    }
    return $out;
}

/**
 * items.php declares $ITEMS/$ITEMS_FLAT/$ENCHANTS as bare globals
 * rather than exposing a function, so pulling them into a function's
 * local scope needs a real `require` (not `require_once` — once any
 * other function in the same request has *also* `require`d this file,
 * a later `require_once` here is silently skipped, because PHP tracks
 * every require/include against one shared list regardless of which
 * keyword was used, leaving the caller's $ITEMS_FLAT undefined). This
 * is the one place that does that `require`, cached so it only runs
 * once per request; every other function in this file reads from here.
 */
function itemsData(): array
{
    static $data = null;
    if ($data !== null) return $data;
    require __DIR__ . '/../../items.php';
    $data = ['items' => $ITEMS, 'flat' => $ITEMS_FLAT, 'enchants' => $ENCHANTS];
    return $data;
}

/**
 * items.php's $ENCHANTS extended with slots that table does not cover
 * yet (Hoe, Fishing Rod, Shield, Elytra, Mace) — see items.php for those.
 */
function enchantSlots(): array
{
    return itemsData()['enchants'];
}

/** Maps an item id (diamond_sword, netherite_boots, bow, …) to its slot key in enchantSlots(), or null if it is not enchantable. */
function enchantSlotForItem(string $itemId): ?string
{
    $exact = [
        'bow' => 'Bow', 'crossbow' => 'Crossbow', 'trident' => 'Trident', 'mace' => 'Mace',
        'fishing_rod' => 'Fishing Rod', 'shield' => 'Shield', 'elytra' => 'Elytra',
        'turtle_helmet' => 'Helmet',
    ];
    if (isset($exact[$itemId])) return $exact[$itemId];

    $suffixes = [
        '_sword' => 'Sword', '_pickaxe' => 'Pickaxe', '_axe' => 'Axe', '_shovel' => 'Shovel', '_hoe' => 'Hoe',
        '_helmet' => 'Helmet', '_chestplate' => 'Chestplate', '_leggings' => 'Leggings', '_boots' => 'Boots',
    ];
    foreach ($suffixes as $suffix => $slot) {
        if (str_ends_with($itemId, $suffix)) return $slot;
    }
    return null;
}

/**
 * Applicable enchantments for one item, filtered to what the selected
 * Minecraft version actually supports. Each row: id, name, max, tier,
 * category, desc.
 */
function enchantsForItem(string $itemId, ?string $version = null): array
{
    $slot = enchantSlotForItem($itemId);
    if (!$slot) return [];
    $meta = enchantMeta();
    $rows = [];
    foreach (enchantSlots()[$slot] ?? [] as [$id, $max]) {
        $m = $meta[$id] ?? null;
        if (!$m) continue;
        if (isset($m['gate']) && !mcHas($version, $m['gate'])) continue;
        $rows[] = ['id' => $id, 'name' => $m['name'], 'max' => $max, 'tier' => $m['tier'], 'category' => $m['category'], 'desc' => $m['desc']];
    }
    return $rows;
}

/**
 * A short curated shortlist per slot — the "Recommended" step. These are
 * the enchantments a typical player reaches for first, not an exhaustive
 * or "best possible" set (several recommended pairs, like Infinity and
 * Mending, are themselves mutually exclusive — the conflict checker
 * catches that if both are turned on).
 */
function enchantRecommended(string $itemId): array
{
    $why = [
        'Sword'   => ['sharpness', 'looting', 'unbreaking', 'mending', 'fire_aspect', 'sweeping'],
        'Axe'     => ['sharpness', 'efficiency', 'unbreaking', 'mending'],
        'Pickaxe' => ['efficiency', 'fortune', 'unbreaking', 'mending'],
        'Shovel'  => ['efficiency', 'unbreaking', 'mending'],
        'Hoe'     => ['efficiency', 'unbreaking', 'mending'],
        'Bow'     => ['power', 'unbreaking', 'infinity', 'mending'],
        'Crossbow'=> ['quick_charge', 'multishot', 'unbreaking', 'mending'],
        'Trident' => ['impaling', 'loyalty', 'unbreaking', 'mending'],
        'Fishing Rod' => ['luck_of_the_sea', 'unbreaking', 'mending'],
        'Shield'  => ['unbreaking', 'mending'],
        'Elytra'  => ['unbreaking', 'mending'],
        'Helmet'      => ['protection', 'unbreaking', 'mending', 'respiration', 'aqua_affinity'],
        'Chestplate'  => ['protection', 'unbreaking', 'mending'],
        'Leggings'    => ['protection', 'unbreaking', 'mending', 'swift_sneak'],
        'Boots'       => ['protection', 'feather_falling', 'unbreaking', 'mending', 'depth_strider', 'soul_speed'],
        'Mace'        => ['density', 'breach', 'unbreaking', 'mending'],
    ];
    $slot = enchantSlotForItem($itemId);
    return $slot ? ($why[$slot] ?? []) : [];
}
