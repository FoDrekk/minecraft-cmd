<?php
// ================================================
// lib/data/items.php — the canonical Item Registry
// ------------------------------------------------
// ONE source of truth for "what items exist and what are they like".
// Before this file, item data lived in the repository root as bare
// globals ($ITEMS/$ITEMS_FLAT/$ENCHANTS in items.php) shaped as
// positional [id, name] tuples, with the item *family* inferred by
// suffix matching in lib/data/enchantments.php and re-listed by hand in
// four other places. That made it impossible to attach a stack size,
// durability, edition or texture key to an item.
//
// Everything here is verified against the Minecraft Wiki (Java Edition
// unless a field says otherwise). Where the Wiki does not state a
// value, the field is left null / omitted rather than guessed — see
// itemStackSize() and itemDurability() which return null for "not
// applicable" instead of inventing a number.
//
// CONVENTIONS (deliberately the same ones the rest of the app uses):
//
//   * ids are stored BARE (diamond_sword), never namespaced. Use
//     mcItemId() to render the canonical minecraft:diamond_sword form
//     and mcNormaliseId() to accept user shorthand.
//   * version gating uses 'min' => <rank>, the same convention as
//     gameFilter() in lib/data/game.php and the 6th element of
//     blocksAll(). Ranks come from MC_VERSIONS in lib/mc.php.
//   * display names are stored literally, because several are NOT
//     mechanically derivable from the id ("Steak" for cooked_beef,
//     "Nether Quartz" for quartz, "Block of Iron" for iron_block).
//
// ADDING AN ITEM: add one row to itemsRegistry(). Do not add item data
// anywhere else — tests/registry.test.mjs enforces that families,
// categories and enchantment references all resolve.
// ================================================
require_once __DIR__ . '/../mc.php';

// ── ITEM FAMILIES ────────────────────────────────
// A family is the normalised "what kind of equipment is this" concept
// the app already needed but had written down nowhere. It is what
// drives enchantment applicability, the equipment icon, and the
// Enhance Item recommendations.
//
// 'slot' is the legacy display key that $ENCHANTS / EQUIPMENT_ICONS /
// enchantRecommended() were keyed by, kept so existing code and the
// existing SVG icon set keep working unchanged.
const MC_ITEM_FAMILIES = [
    'sword'       => ['slot' => 'Sword',       'label' => 'Sword',       'kind' => 'weapon'],
    'pickaxe'     => ['slot' => 'Pickaxe',     'label' => 'Pickaxe',     'kind' => 'tool'],
    'axe'         => ['slot' => 'Axe',         'label' => 'Axe',         'kind' => 'tool'],
    'shovel'      => ['slot' => 'Shovel',      'label' => 'Shovel',      'kind' => 'tool'],
    'hoe'         => ['slot' => 'Hoe',         'label' => 'Hoe',         'kind' => 'tool'],
    'bow'         => ['slot' => 'Bow',         'label' => 'Bow',         'kind' => 'weapon'],
    'crossbow'    => ['slot' => 'Crossbow',    'label' => 'Crossbow',    'kind' => 'weapon'],
    'trident'     => ['slot' => 'Trident',     'label' => 'Trident',     'kind' => 'weapon'],
    'mace'        => ['slot' => 'Mace',        'label' => 'Mace',        'kind' => 'weapon'],
    'helmet'      => ['slot' => 'Helmet',      'label' => 'Helmet',      'kind' => 'armor'],
    'chestplate'  => ['slot' => 'Chestplate',  'label' => 'Chestplate',  'kind' => 'armor'],
    'leggings'    => ['slot' => 'Leggings',    'label' => 'Leggings',    'kind' => 'armor'],
    'boots'       => ['slot' => 'Boots',       'label' => 'Boots',       'kind' => 'armor'],
    'elytra'      => ['slot' => 'Elytra',      'label' => 'Elytra',      'kind' => 'armor'],
    'shield'      => ['slot' => 'Shield',      'label' => 'Shield',      'kind' => 'combat'],
    'fishing_rod' => ['slot' => 'Fishing Rod', 'label' => 'Fishing Rod', 'kind' => 'tool'],
];

// ── MATERIAL TIERS ───────────────────────────────
// Durability per tier, verified against https://minecraft.wiki/w/Durability
// Tools/weapons share one number within a tier. Armour uses the
// 11:16:15:13 helmet:chestplate:leggings:boots ratio, so it is stored
// per piece rather than derived, to stay explicit.
const MC_TOOL_DURABILITY = [
    'wooden'    => 59,
    'stone'     => 131,
    'golden'    => 32,
    'iron'      => 250,
    'diamond'   => 1561,
    'netherite' => 2031,
];

const MC_ARMOR_DURABILITY = [
    // material  => [helmet, chestplate, leggings, boots]
    'leather'   => [55,  80,  75,  65],
    'golden'    => [77,  112, 105, 91],
    'chainmail' => [165, 240, 225, 195],
    'iron'      => [165, 240, 225, 195],
    'diamond'   => [363, 528, 495, 429],
    'netherite' => [407, 592, 555, 481],
];

/**
 * The canonical item table.
 *
 * Row fields (omit a field when it does not apply — do NOT guess):
 *   name      string  display name, exactly as the game shows it
 *   cat       string  one of itemCategories()
 *   family    ?string key in MC_ITEM_FAMILIES, for equipment only
 *   stack     int     max stack size: 64, 16 or 1
 *   dur       ?int    max durability; absent = not damageable
 *   food      ?array  [nutrition, saturation] — saturation is the
 *                     already-doubled value the game stores, as shown
 *                     in the Wiki's "Saturation" column
 *   min       ?int    minimum MC_VERSIONS rank (version gate)
 *   edition   ?string 'java' when the item is Java-only
 *   aliases   ?array  extra search terms players actually type
 *
 * Sources: https://minecraft.wiki/w/Item  (stacking)
 *          https://minecraft.wiki/w/Durability
 *          https://minecraft.wiki/w/Food
 */
function itemsRegistry(): array
{
    static $rows = null;
    if ($rows !== null) return $rows;

    $rows = [];

    // ── WEAPONS ──────────────────────────────────
    foreach (['wooden', 'stone', 'iron', 'golden', 'diamond', 'netherite'] as $mat) {
        $rows[$mat . '_sword'] = [
            'name' => ucfirst($mat === 'wooden' ? 'Wooden' : ($mat === 'golden' ? 'Golden' : $mat)) . ' Sword',
            'cat' => 'Weapons', 'family' => 'sword', 'stack' => 1,
            'dur' => MC_TOOL_DURABILITY[$mat],
        ];
    }
    $rows['bow']            = ['name' => 'Bow',            'cat' => 'Weapons', 'family' => 'bow',      'stack' => 1, 'dur' => 384];
    $rows['crossbow']       = ['name' => 'Crossbow',       'cat' => 'Weapons', 'family' => 'crossbow', 'stack' => 1, 'dur' => 465];
    $rows['trident']        = ['name' => 'Trident',        'cat' => 'Weapons', 'family' => 'trident',  'stack' => 1, 'dur' => 250];
    // Mace — added in Java 1.21 "Tricky Trials" (rank 55 = 1.21.1, the
    // oldest 1.21.x entry in MC_VERSIONS). Durability was raised to 500
    // in snapshot 24w18a. https://minecraft.wiki/w/Mace
    $rows['mace']           = ['name' => 'Mace',           'cat' => 'Weapons', 'family' => 'mace',     'stack' => 1, 'dur' => 500, 'min' => 55];
    $rows['arrow']          = ['name' => 'Arrow',          'cat' => 'Weapons', 'stack' => 64];
    $rows['spectral_arrow'] = ['name' => 'Spectral Arrow', 'cat' => 'Weapons', 'stack' => 64];

    // ── TOOLS ────────────────────────────────────
    // Wiki-accurate material coverage: there is no golden_shovel/hoe or
    // stone_hoe entry in the original list, and that gap is preserved
    // rather than silently inventing rows the UI never offered.
    $toolMats = [
        'pickaxe' => ['wooden', 'stone', 'iron', 'golden', 'diamond', 'netherite'],
        'axe'     => ['wooden', 'stone', 'iron', 'golden', 'diamond', 'netherite'],
        'shovel'  => ['wooden', 'stone', 'iron', 'diamond', 'netherite'],
        'hoe'     => ['wooden', 'iron', 'diamond', 'netherite'],
    ];
    foreach ($toolMats as $fam => $mats) {
        foreach ($mats as $mat) {
            $label = $mat === 'wooden' ? 'Wooden' : ($mat === 'golden' ? 'Golden' : ucfirst($mat));
            $rows[$mat . '_' . $fam] = [
                'name' => $label . ' ' . ucfirst($fam),
                'cat' => 'Tools', 'family' => $fam, 'stack' => 1,
                'dur' => MC_TOOL_DURABILITY[$mat],
            ];
        }
    }
    // Java fishing rod is 64 uses; Bedrock's is 384. We model Java.
    // https://minecraft.wiki/w/Fishing_Rod
    $rows['fishing_rod']     = ['name' => 'Fishing Rod',     'cat' => 'Tools', 'family' => 'fishing_rod', 'stack' => 1, 'dur' => 64];
    $rows['flint_and_steel'] = ['name' => 'Flint and Steel', 'cat' => 'Tools', 'stack' => 1, 'dur' => 64];
    $rows['shears']          = ['name' => 'Shears',          'cat' => 'Tools', 'stack' => 1, 'dur' => 238];
    $rows['compass']         = ['name' => 'Compass',         'cat' => 'Tools', 'stack' => 64];
    $rows['clock']           = ['name' => 'Clock',           'cat' => 'Tools', 'stack' => 64];
    $rows['map']             = ['name' => 'Map',             'cat' => 'Tools', 'stack' => 64];
    $rows['spyglass']        = ['name' => 'Spyglass',        'cat' => 'Tools', 'stack' => 1];
    $rows['lead']            = ['name' => 'Lead',            'cat' => 'Tools', 'stack' => 64];
    $rows['name_tag']        = ['name' => 'Name Tag',        'cat' => 'Tools', 'stack' => 64];
    $rows['shield']          = ['name' => 'Shield',          'cat' => 'Tools', 'family' => 'shield', 'stack' => 1, 'dur' => 336];

    // ── ARMOUR ───────────────────────────────────
    $pieces = ['helmet' => 0, 'chestplate' => 1, 'leggings' => 2, 'boots' => 3];
    foreach (['leather', 'chainmail', 'iron', 'golden', 'diamond', 'netherite'] as $mat) {
        foreach ($pieces as $piece => $idx) {
            $rows[$mat . '_' . $piece] = [
                'name' => ucfirst($mat) . ' ' . ucfirst($piece),
                'cat' => 'Armour', 'family' => $piece, 'stack' => 1,
                'dur' => MC_ARMOR_DURABILITY[$mat][$idx],
            ];
        }
    }
    $rows['elytra']        = ['name' => 'Elytra',        'cat' => 'Armour', 'family' => 'elytra', 'stack' => 1, 'dur' => 432];
    // Turtle shell helmet — helmet-only material, 275 uses.
    $rows['turtle_helmet'] = ['name' => 'Turtle Helmet', 'cat' => 'Armour', 'family' => 'helmet', 'stack' => 1, 'dur' => 275,
                              'aliases' => ['turtle shell', 'scute helmet']];

    // ── FOOD ─────────────────────────────────────
    // [nutrition, saturation] straight from https://minecraft.wiki/w/Food
    $food = [
        'apple'                  => ['Apple',                  4,  2.4,  64],
        'golden_apple'           => ['Golden Apple',           4,  9.6,  64],
        'enchanted_golden_apple' => ['Enchanted Golden Apple',  4,  9.6,  64],
        'bread'                  => ['Bread',                  5,  6.0,  64],
        'carrot'                 => ['Carrot',                 3,  3.6,  64],
        'golden_carrot'          => ['Golden Carrot',           6, 14.4,  64],
        'potato'                 => ['Potato',                 1,  0.6,  64],
        'baked_potato'           => ['Baked Potato',            5,  6.0,  64],
        'pumpkin_pie'            => ['Pumpkin Pie',             8,  4.8,  64],
        'cookie'                 => ['Cookie',                  2,  0.4,  64],
        'cooked_beef'            => ['Steak',                   8, 12.8,  64],
        'cooked_porkchop'        => ['Cooked Porkchop',         8, 12.8,  64],
        'cooked_chicken'         => ['Cooked Chicken',          6,  7.2,  64],
        'cooked_mutton'          => ['Cooked Mutton',           6,  9.6,  64],
        'cooked_cod'             => ['Cooked Cod',              5,  6.0,  64],
        'cooked_salmon'          => ['Cooked Salmon',           6,  9.6,  64],
        'dried_kelp'             => ['Dried Kelp',              1,  0.6,  64],
        // Stews and cake do not stack; honey bottle stacks to 16.
        'mushroom_stew'          => ['Mushroom Stew',           6,  7.2,   1],
        'rabbit_stew'            => ['Rabbit Stew',            10, 12.0,   1],
        'beetroot_soup'          => ['Beetroot Soup',           6,  7.2,   1],
        'cake'                   => ['Cake',                   14,  2.8,   1],
        'honey_bottle'           => ['Honey Bottle',            6,  1.2,  16],
    ];
    foreach ($food as $id => [$name, $nut, $sat, $stack]) {
        $rows[$id] = ['name' => $name, 'cat' => 'Food', 'stack' => $stack, 'food' => [$nut, $sat]];
    }

    // ── MATERIALS ────────────────────────────────
    $materials = [
        'diamond'          => ['Diamond',          64],
        'emerald'          => ['Emerald',          64],
        'netherite_ingot'  => ['Netherite Ingot',  64],
        'gold_ingot'       => ['Gold Ingot',       64],
        'iron_ingot'       => ['Iron Ingot',       64],
        'copper_ingot'     => ['Copper Ingot',     64],
        'redstone'         => ['Redstone',         64],
        'lapis_lazuli'     => ['Lapis Lazuli',     64],
        'quartz'           => ['Nether Quartz',    64],
        'coal'             => ['Coal',             64],
        'amethyst_shard'   => ['Amethyst Shard',   64],
        'echo_shard'       => ['Echo Shard',       64],
        'nether_star'      => ['Nether Star',      64],
        'dragon_egg'       => ['Dragon Egg',       64],
        'end_crystal'      => ['End Crystal',      64],
        'beacon'           => ['Beacon',           64],
        'conduit'          => ['Conduit',          64],
        'totem_of_undying' => ['Totem of Undying',  1],
    ];
    foreach ($materials as $id => [$name, $stack]) {
        $rows[$id] = ['name' => $name, 'cat' => 'Materials', 'stack' => $stack];
    }
    $rows['quartz']['aliases']       = ['nether quartz'];
    $rows['lapis_lazuli']['aliases'] = ['lapis'];
    $rows['cooked_beef']['aliases']  = ['steak', 'beef'];

    // Redstone / utility items the give picker previously could not
    // offer at all, even though Build and Knowledge talk about them.
    $rows['ender_pearl']    = ['name' => 'Ender Pearl',    'cat' => 'Utility',  'stack' => 16];
    $rows['redstone_torch'] = ['name' => 'Redstone Torch', 'cat' => 'Redstone', 'stack' => 64];
    $rows['repeater']       = ['name' => 'Redstone Repeater', 'cat' => 'Redstone', 'stack' => 64, 'aliases' => ['redstone repeater']];
    $rows['comparator']     = ['name' => 'Redstone Comparator', 'cat' => 'Redstone', 'stack' => 64, 'aliases' => ['redstone comparator']];
    $rows['observer']       = ['name' => 'Observer',       'cat' => 'Redstone', 'stack' => 64];
    $rows['piston']         = ['name' => 'Piston',         'cat' => 'Redstone', 'stack' => 64];
    $rows['sticky_piston']  = ['name' => 'Sticky Piston',  'cat' => 'Redstone', 'stack' => 64];
    $rows['hopper']         = ['name' => 'Hopper',         'cat' => 'Redstone', 'stack' => 64];
    $rows['dispenser']      = ['name' => 'Dispenser',      'cat' => 'Redstone', 'stack' => 64];
    $rows['lever']          = ['name' => 'Lever',          'cat' => 'Redstone', 'stack' => 64];

    return $rows;
}

// ── ID NORMALISATION ─────────────────────────────

/**
 * Accepts what a user might type or paste and returns the app's bare
 * canonical id: 'minecraft:Diamond_Sword' / 'Diamond Sword' -> 'diamond_sword'.
 * This is the one normaliser — before it existed, eight separate inline
 * preg_replace('/^minecraft:/','') calls did half the job.
 */
function mcNormaliseId(string $id): string
{
    $id = strtolower(trim($id));
    $id = preg_replace('/^minecraft:/', '', $id);
    $id = preg_replace('/[^a-z0-9_]+/', '_', $id);
    return trim($id, '_');
}

/** The namespaced form for generated commands: 'diamond_sword' -> 'minecraft:diamond_sword'. */
function mcItemId(string $id): string
{
    return 'minecraft:' . mcNormaliseId($id);
}

/**
 * One item row (with 'id' injected), or null when the id is unknown.
 * Accepts namespaced, shorthand or display-name input.
 */
function itemGet(string $id): ?array
{
    $id   = mcNormaliseId($id);
    $rows = itemsRegistry();
    if (!isset($rows[$id])) {
        // fall back to an alias match before giving up
        $id = itemResolveAlias($id) ?? $id;
        if (!isset($rows[$id])) return null;
    }
    return $rows[$id] + ['id' => $id];
}

/** Does this id name a real registry item? */
function itemExists(string $id): bool
{
    return itemGet($id) !== null;
}

/** Resolve an alias/display name to a canonical id, or null. */
function itemResolveAlias(string $needle): ?string
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (itemsRegistry() as $id => $row) {
            $map[mcNormaliseId($row['name'])] = $id;
            foreach ($row['aliases'] ?? [] as $a) $map[mcNormaliseId($a)] = $id;
        }
    }
    return $map[mcNormaliseId($needle)] ?? null;
}

/**
 * The canonical display name. Falls back to title-casing the id so an
 * unknown-but-valid Minecraft id still renders sensibly instead of
 * leaking a raw snake_case string into the UI.
 */
function itemName(string $id): string
{
    $row = itemGet($id);
    if ($row) return $row['name'];
    return ucwords(str_replace('_', ' ', mcNormaliseId($id)));
}

// ── FAMILIES ─────────────────────────────────────

/**
 * The item's family key ('sword', 'boots', …) or null when the item is
 * not equipment. Registry lookup first, then the historical suffix
 * match so ids that are valid Minecraft but absent from the registry
 * (copper_sword, a future material) still resolve.
 */
function itemFamily(string $id): ?string
{
    $id  = mcNormaliseId($id);
    $row = itemGet($id);
    if ($row && isset($row['family'])) return $row['family'];

    foreach (['sword', 'pickaxe', 'axe', 'shovel', 'hoe', 'helmet', 'chestplate', 'leggings', 'boots'] as $fam) {
        // pickaxe before axe: '_axe' would otherwise swallow '_pickaxe'.
        if ($fam === 'axe' && str_ends_with($id, '_pickaxe')) continue;
        if (str_ends_with($id, '_' . $fam)) return $fam;
    }
    return isset(MC_ITEM_FAMILIES[$id]) ? $id : null;
}

/** The legacy TitleCase slot key ('Sword', 'Fishing Rod') for an item id, or null. */
function itemSlot(string $id): ?string
{
    $fam = itemFamily($id);
    return $fam ? (MC_ITEM_FAMILIES[$fam]['slot'] ?? null) : null;
}

/** The material tier prefix ('diamond', 'netherite', …) or null. */
function itemMaterial(string $id): ?string
{
    $id = mcNormaliseId($id);
    foreach (['wooden', 'stone', 'golden', 'iron', 'diamond', 'netherite', 'leather', 'chainmail'] as $mat) {
        if (str_starts_with($id, $mat . '_')) return $mat;
    }
    return null;
}

// ── PROPERTIES ───────────────────────────────────

/** Max stack size, or null when the item is unknown (never guessed). */
function itemStackSize(string $id): ?int
{
    $row = itemGet($id);
    return $row['stack'] ?? null;
}

/** Max durability, or null when the item is not damageable / unknown. */
function itemDurability(string $id): ?int
{
    $row = itemGet($id);
    return $row['dur'] ?? null;
}

/** [nutrition, saturation] for food, or null when not a food item. */
function itemFood(string $id): ?array
{
    $row = itemGet($id);
    return $row['food'] ?? null;
}

/**
 * The texture key for an item — the asset/renderer lookup key. Kept a
 * separate concept from the id so the resolver can be pointed at real
 * local assets later without touching item data. It is NOT a promise
 * that a texture exists; assets/textures.js decides that and falls back
 * visibly when it does not.
 */
function itemTextureKey(string $id): string
{
    return mcNormaliseId($id);
}

// ── CATEGORIES & LISTS ───────────────────────────

/** The category order the UI shows. */
function itemCategories(): array
{
    return ['Weapons', 'Tools', 'Armour', 'Food', 'Materials', 'Redstone', 'Utility'];
}

/**
 * Every item, version-filtered, as associative rows. This is what
 * pages should consume.
 */
function itemsList(?string $version = null): array
{
    $rank = $version !== null ? mcVersion($version)['rank'] : PHP_INT_MAX;
    $out  = [];
    foreach (itemsRegistry() as $id => $row) {
        if (($row['min'] ?? 0) > $rank) continue;
        $out[$id] = $row + ['id' => $id];
    }
    return $out;
}

/** Items grouped by category, version-filtered, in itemCategories() order. */
function itemsByCategory(?string $version = null): array
{
    $out = array_fill_keys(itemCategories(), []);
    foreach (itemsList($version) as $id => $row) {
        $out[$row['cat']][] = $row;
    }
    return array_filter($out, fn($rows) => $rows !== []);
}

/** Items that can hold an enchantment, version-filtered. */
function itemsEnchantable(?string $version = null): array
{
    return array_filter(itemsList($version), fn($r) => isset($r['family']));
}
