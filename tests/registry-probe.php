<?php
// ================================================
// tests/registry-probe.php — test fixture, not a page
// ------------------------------------------------
// Dumps the canonical registries as JSON so
// tests/registry.test.mjs can assert against the REAL PHP data
// instead of a JavaScript copy of it. Nothing in the app links here.
// ================================================
require_once __DIR__ . '/../lib/data/items.php';
require_once __DIR__ . '/../lib/data/enchantments.php';
require_once __DIR__ . '/../lib/data/blocks.php';

header('Content-Type: application/json');

$items = [];
foreach (itemsList() as $id => $row) {
    $items[] = [
        'id'     => $id,
        'name'   => $row['name'],
        'cat'    => $row['cat'],
        'family' => $row['family'] ?? null,
    ];
}

// Aliases must not shadow a real canonical id.
$aliasCollisions = [];
foreach (itemsRegistry() as $id => $row) {
    foreach ($row['aliases'] ?? [] as $alias) {
        $n = mcNormaliseId($alias);
        if (isset(itemsRegistry()[$n]) && $n !== $id) $aliasCollisions[] = "$id:$alias";
    }
}

$meta = enchantMeta();
$applicability = enchantApplicability();

// Every enchantment named by the applicability table must have metadata.
$unknownEnchants = [];
foreach ($applicability as $slot => $rows) {
    foreach ($rows as [$eid, $lvl]) {
        if (!isset($meta[$eid])) $unknownEnchants[] = "$slot:$eid";
    }
}

// Recommendations must only name enchantments that slot can actually take.
$badRecommends = [];
$probe = [
    'Sword' => 'diamond_sword', 'Axe' => 'diamond_axe', 'Pickaxe' => 'diamond_pickaxe',
    'Shovel' => 'diamond_shovel', 'Hoe' => 'diamond_hoe', 'Bow' => 'bow',
    'Crossbow' => 'crossbow', 'Trident' => 'trident', 'Mace' => 'mace',
    'Helmet' => 'diamond_helmet', 'Chestplate' => 'diamond_chestplate',
    'Leggings' => 'diamond_leggings', 'Boots' => 'diamond_boots',
    'Fishing Rod' => 'fishing_rod', 'Shield' => 'shield', 'Elytra' => 'elytra',
];
foreach ($probe as $slot => $itemId) {
    $allowed = array_column($applicability[$slot] ?? [], 0);
    foreach (enchantRecommended($itemId) as $eid) {
        if (!in_array($eid, $allowed, true)) $badRecommends[] = "$slot:$eid";
    }
}

// Presets must be applicable to the slot they claim.
$slotAliases = ['armor' => 'Chestplate'];
$badPresets = [];
foreach (enchant_presets() as $pid => $preset) {
    $slotKey = $preset['slot'] ?? '';
    $slotKey = $slotAliases[$slotKey] ?? ucwords(str_replace('_', ' ', $slotKey));
    $allowed = array_column($applicability[$slotKey] ?? [], 0);
    if (!$allowed) { $badPresets[] = "$pid:unknown-slot:" . ($preset['slot'] ?? ''); continue; }
    foreach ($preset['enchants'] as $e) {
        if (!in_array($e['id'], $allowed, true)) $badPresets[] = "$pid:{$e['id']}";
    }
}

$badConflicts = [];
foreach (enchantConflictGroups() as $group) {
    foreach ($group as $eid) if (!isset($meta[$eid])) $badConflicts[] = $eid;
}

$norm = [];
foreach (['minecraft:diamond_sword', 'Diamond Sword', 'DIAMOND_SWORD', 'diamond_sword'] as $t) {
    $norm[$t] = mcNormaliseId($t);
}

$slotOf = [];
foreach (['minecraft:bow', 'diamond_sword', 'diamond'] as $t) {
    $slotOf[$t] = itemSlot($t);
}

$durIds = ['diamond_sword', 'netherite_pickaxe', 'golden_sword', 'wooden_axe', 'stone_shovel',
           'iron_sword', 'diamond_chestplate', 'netherite_helmet', 'chainmail_boots',
           'iron_boots', 'elytra', 'mace', 'diamond'];
$dur = [];
foreach ($durIds as $id) $dur[$id] = itemDurability($id);

$stackIds = ['diamond', 'diamond_sword', 'iron_helmet', 'ender_pearl', 'honey_bottle',
             'rabbit_stew', 'definitely_not_an_item'];
$stack = [];
foreach ($stackIds as $id) $stack[$id] = itemStackSize($id);

$maxIds = ['sharpness', 'protection', 'mending', 'sweeping_edge', 'density', 'breach',
           'wind_burst', 'efficiency', 'feather_falling', 'not_an_enchantment'];
$maxLevel = [];
foreach ($maxIds as $id) $maxLevel[$id] = enchantMaxLevel($id);

$food = [];
foreach (['cooked_beef', 'golden_carrot', 'diamond'] as $id) $food[$id] = itemFood($id);

// Version gating: the mace only exists from 1.21 onward.
$maceOn = [];
$itemsOn = [];
foreach (['1.20.4', '1.21.1', '26.2'] as $v) {
    $maceOn[$v]  = isset(itemsList($v)['mace']);
    $itemsOn[$v] = count(itemsList($v));
}

echo json_encode([
    'items'           => $items,
    'categories'      => itemCategories(),
    'families'        => MC_ITEM_FAMILIES,
    'applicability'   => $applicability,
    'enchantIds'      => array_keys($meta),
    'blockIds'        => array_keys(blocksAll()),
    'aliasCollisions' => $aliasCollisions,
    'unknownEnchants' => $unknownEnchants,
    'badRecommends'   => $badRecommends,
    'badPresets'      => $badPresets,
    'badConflicts'    => $badConflicts,
    'norm'            => $norm,
    'canonical'       => mcItemId('Diamond Sword'),
    'slotOf'          => $slotOf,
    'dur'             => $dur,
    'stack'           => $stack,
    'maxLevel'        => $maxLevel,
    'food'            => $food,
    'maceOn'          => $maceOn,
    'itemsOn'         => $itemsOn,
    'blockItem'       => ['oak_planks' => mcNormaliseId('minecraft:oak_planks')],
    'textureKey'      => ['minecraft:Diamond Sword' => itemTextureKey('minecraft:Diamond Sword')],
], JSON_UNESCAPED_SLASHES);
