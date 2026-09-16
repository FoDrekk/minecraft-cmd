<?php
// ================================================
// items.php — legacy globals, now DERIVED
// ------------------------------------------------
// The canonical item data lives in lib/data/items.php (itemsRegistry())
// and the canonical enchantment applicability table lives in
// lib/data/enchantments.php (enchantApplicability()).
//
// This file used to hold all of it as hand-written arrays, and several
// pages still `require_once` it and read the globals directly. Rather
// than break those callers, the globals are now generated from the
// registries above, in the exact shapes the pages already expect:
//
//   $ITEMS       category => [[id, name], …]
//   $ITEMS_FLAT  [[id, name], …]               (the same rows, flattened)
//   $ENCHANTS    slot => [[enchant, max], …]
//
// Because they are derived, they can no longer drift from the registry:
// adding an item in one place updates the pickers, /give, the Item
// Builder and Knowledge together.
//
// New code should call itemsList() / itemsByCategory() / itemName() /
// enchantApplicability() instead of reading these globals.
// ================================================
require_once __DIR__ . '/lib/data/items.php';
require_once __DIR__ . '/lib/data/enchantments.php';

$ITEMS = [];
foreach (itemsByCategory() as $cat => $rows) {
    foreach ($rows as $row) {
        $ITEMS[$cat][] = [$row['id'], $row['name']];
    }
}

$ITEMS_FLAT = [];
foreach ($ITEMS as $list) {
    foreach ($list as $item) {
        $ITEMS_FLAT[] = $item;
    }
}

$ENCHANTS = enchantApplicability();
