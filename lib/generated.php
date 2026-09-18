<?php
// ================================================
// lib/generated.php — reads data/generated/*.json
// ------------------------------------------------
// The one place the PHP app talks to the Python data engine's output
// (python/minecraft_engine/ — see python/README.md). Everything here
// is read-only and additive:
//
//   * data/generated/ is a build artifact, not a second source of
//     truth. It is never required — every function below returns a
//     safe "unknown" value (null / false / []) when the directory or
//     a file is missing, unreadable or not valid JSON, so a fresh
//     clone with no generated data behaves exactly like the app did
//     before this file existed.
//   * It never decides display names, categories or styles — those
//     stay hand-curated in lib/data/*.php. This file only answers a
//     narrower question: "did a real, scanned Minecraft archive
//     confirm this id has an asset, and did its art change between
//     scanned versions?" — the same thing python/registry.py records.
//   * No path is ever built from request input — the four filenames
//     read here are fixed string literals, matching the shape
//     registry.py's write_generated() produces.
//
// CONTRACT (produced by `python3 -m minecraft_engine.generate`):
//   versions.json      {versions:{id:{label,name,edition,syntax,rank,released}}, features:{name:rank}, default_version}
//   items.json          [{id, type, texture, source}, ...]   (item_texture, item_definition, item_model, ...)
//   blocks.json         [{id, type, texture, source}, ...]   (block_texture, blockstate, block_model, ...)
//   textures.json        every *_texture record, flattened
//   version-diff.json   [{id, type, status: added|removed|changed|unchanged, ...}, ...] — only when two sources were scanned
// ================================================
require_once __DIR__ . '/textures.php';

/** Where a Python `generate.py` run wrote its output, by convention. */
function generatedDir(): string
{
    return dirname(__DIR__) . '/data/generated';
}

/**
 * Decode one generated JSON file. Returns null — never throws, never
 * warns into the page — when the directory doesn't exist, the file is
 * absent, or its contents aren't valid JSON: all three are the normal
 * state of a fresh clone that hasn't run the Python engine yet, and a
 * malformed file is data corruption, not something the app fails on.
 */
function generatedLoad(string $file, ?string $dir = null): ?array
{
    static $cache = [];
    $dir  = $dir ?? generatedDir();
    $path = rtrim($dir, '/\\') . '/' . $file;

    if (array_key_exists($path, $cache)) return $cache[$path];
    if (!is_file($path)) return $cache[$path] = null;

    $raw = @file_get_contents($path);
    if ($raw === false) return $cache[$path] = null;

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) return $cache[$path] = null;

    return $cache[$path] = $data;
}

/** Is there any generated data to read at all? */
function generatedAvailable(?string $dir = null): bool
{
    return generatedLoad('versions.json', $dir) !== null;
}

/** The versions.json payload — a snapshot of lib/mc.php as of the last engine run, or null. */
function generatedVersions(?string $dir = null): ?array
{
    return generatedLoad('versions.json', $dir);
}

/**
 * id => [records] from items.json or blocks.json, for O(1) lookups.
 * $kind is 'item' or 'block'. Records missing an id/source are skipped
 * defensively — a malformed individual entry should not take down the
 * whole index, only itself.
 */
function generatedAssetIndex(string $kind, ?string $dir = null): array
{
    static $indexes = [];
    $cacheKey = $kind . '|' . ($dir ?? generatedDir());
    if (isset($indexes[$cacheKey])) return $indexes[$cacheKey];

    $file    = $kind === 'block' ? 'blocks.json' : 'items.json';
    $records = generatedLoad($file, $dir) ?? [];
    $out     = [];
    foreach ($records as $r) {
        if (!is_array($r) || !isset($r['id'], $r['source']) || !is_string($r['id'])) continue;
        $out[$r['id']][] = $r;
    }
    return $indexes[$cacheKey] = $out;
}

/** Every distinct source label that confirmed this id exists, or []. */
function generatedAssetSources(string $id, string $kind = 'item', ?string $dir = null): array
{
    $id  = textureNormaliseId($id);
    $idx = generatedAssetIndex($kind, $dir);
    if (!isset($idx[$id])) return [];
    return array_values(array_unique(array_column($idx[$id], 'source')));
}

/** Was this id seen at all by a real, scanned Minecraft archive? */
function generatedAssetConfirmed(string $id, string $kind = 'item', ?string $dir = null): bool
{
    return generatedAssetSources($id, $kind, $dir) !== [];
}

/**
 * Did this id's texture change between the two scanned sources in
 * version-diff.json? Only meaningful when generate.py was run with
 * --source-b (a two-source diff) — absent that, this always returns
 * false rather than guessing.
 */
function generatedTextureChanged(string $id, string $kind = 'item', ?string $dir = null): bool
{
    $diff = generatedLoad('version-diff.json', $dir);
    if ($diff === null) return false;

    $id   = textureNormaliseId($id);
    $type = $kind . '_texture';
    foreach ($diff as $d) {
        if (!is_array($d)) continue;
        if (($d['id'] ?? null) === $id && ($d['type'] ?? null) === $type) {
            return ($d['status'] ?? null) === 'changed';
        }
    }
    return false;
}

/**
 * The one call Knowledge pages actually need: a small, always-present
 * summary for one id, safe to merge into a block/item row regardless
 * of whether generated data exists. `verified` is the only field the
 * UI should branch on — `sources`/`changed` are display detail.
 */
function generatedSummary(string $id, string $kind = 'item', ?string $dir = null): array
{
    $sources = generatedAssetSources($id, $kind, $dir);
    return [
        'verified' => $sources !== [],
        'sources'  => $sources,
        'changed'  => $sources !== [] && generatedTextureChanged($id, $kind, $dir),
    ];
}
