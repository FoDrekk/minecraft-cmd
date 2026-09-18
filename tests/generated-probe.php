<?php
// ================================================
// tests/generated-probe.php — test fixture, not a page
// ------------------------------------------------
// Dumps lib/generated.php's behaviour as JSON against three states —
// no generated data, valid generated data, and malformed generated
// data — so tests/generated-data.test.mjs can assert the real PHP
// behaviour instead of a JS copy of it. Nothing in the app links here.
// ================================================
require_once __DIR__ . '/../lib/generated.php';

$missingDir   = __DIR__ . '/fixtures/does-not-exist';
$validDir     = __DIR__ . '/fixtures/generated';
$malformedDir = __DIR__ . '/fixtures/generated-malformed';

echo json_encode([
    // No data/generated/ at all — the default state of a fresh clone.
    'missing' => [
        'available'    => generatedAvailable($missingDir),
        'versions'     => generatedVersions($missingDir),
        'itemSummary'  => generatedSummary('diamond_sword', 'item', $missingDir),
        'blockSummary' => generatedSummary('oak_planks', 'block', $missingDir),
    ],

    // A valid, small generated set (tests/fixtures/generated/).
    'valid' => [
        'available'           => generatedAvailable($validDir),
        'defaultVersion'      => generatedVersions($validDir)['default_version'] ?? null,
        'knownItem'           => generatedSummary('diamond_sword', 'item', $validDir),
        'knownItemNamespaced' => generatedSummary('minecraft:Diamond_Sword', 'item', $validDir),
        'unknownItem'         => generatedSummary('totally_made_up_item', 'item', $validDir),
        'knownBlock'          => generatedSummary('oak_planks', 'block', $validDir),
        'changedBlock'        => generatedSummary('candle', 'block', $validDir),
        'sourcesForSword'     => generatedAssetSources('diamond_sword', 'item', $validDir),
    ],

    // versions.json is fine but items.json is corrupted — must degrade
    // per-file, never fatal, never leak a PHP warning into the page.
    'malformed' => [
        'available'   => generatedAvailable($malformedDir),
        'itemSummary' => generatedSummary('diamond_sword', 'item', $malformedDir),
    ],
], JSON_UNESCAPED_SLASHES);
