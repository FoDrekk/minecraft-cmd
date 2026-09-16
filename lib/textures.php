<?php
// ================================================
// lib/textures.php — authentic Minecraft texture resolver
// ------------------------------------------------
// The single place that answers "is there a real Minecraft texture
// for this id?". Everything visual in the app resolves through here
// (or its client-side mirror, MC.textureSrc() in mccmd.js) rather
// than hardcoding an image path or URL per page.
//
// DROP-IN PATH
// ------------
// Put PNG (or WEBP) files — real, legitimately obtained Minecraft
// textures — in the folder matching their kind, named by their
// canonical Minecraft id. This mirrors the game's own resource-pack
// layout, so a texture pulled straight out of a resource pack or the
// client jar needs no renaming:
//
//   assets/textures/item/diamond_sword.png
//   assets/textures/item/apple.png
//   assets/textures/block/oak_planks.png
//   assets/textures/block/grass_block.png
//
// and they are picked up automatically — no code change, no manifest
// to regenerate. The repository intentionally ships none: Mojang's
// textures are not ours to redistribute, so the owner supplies their
// own legitimately obtained copies. Until then every lookup falls
// through to the honest, clearly-not-a-texture fallback the caller
// already renders (a flat colour, a generic glyph, an initial) —
// this file never invents a replacement image.
// ================================================

require_once __DIR__ . '/mc.php';

const MC_TEXTURE_KINDS = ['item', 'block'];
const MC_TEXTURE_EXTS  = ['png', 'webp'];

/** `minecraft:diamond_sword` / `Diamond Sword` -> `diamond_sword`. */
function textureNormaliseId(string $id): string
{
    $id = strtolower(trim($id));
    $id = preg_replace('/^minecraft:/', '', $id);
    $id = preg_replace('/[^a-z0-9_]+/', '_', $id);
    return trim($id, '_');
}

/**
 * Every texture present on disk for one kind: normalised id => web path.
 * Scanned once per request per kind — the directories are small, and
 * the result is a list of names, not image data.
 */
function texturesManifest(string $kind): array
{
    static $manifests = [];
    if (isset($manifests[$kind])) return $manifests[$kind];
    if (!in_array($kind, MC_TEXTURE_KINDS, true)) return $manifests[$kind] = [];

    $manifest = [];
    $relDir = 'assets/textures/' . $kind;
    $dir = dirname(__DIR__) . '/' . $relDir;
    if (!is_dir($dir)) return $manifests[$kind] = $manifest;

    foreach (scandir($dir) ?: [] as $file) {
        // Only plain filenames become web paths. A name with quotes,
        // angle brackets or path separators is skipped rather than
        // escaped downstream — the path ends up in HTML attributes and
        // an inline script, and the safest handling is to never let an
        // odd one in.
        if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $file)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, MC_TEXTURE_EXTS, true)) continue;
        $id = textureNormaliseId(pathinfo($file, PATHINFO_FILENAME));
        if ($id === '') continue;
        // First extension in MC_TEXTURE_EXTS order wins, so a png and a
        // webp of the same id resolve predictably instead of by readdir order.
        if (isset($manifest[$id])) continue;
        $manifest[$id] = $relDir . '/' . $file;
    }
    ksort($manifest);
    return $manifests[$kind] = $manifest;
}

/**
 * Resolve one id to a real, on-disk texture path, or null if none
 * exists. $kind is 'item' or 'block' — the two the game itself keeps
 * separate (a block's item-form icon can differ from its placed
 * texture, so a caller that means the inventory icon should ask for
 * 'item' even for a block).
 */
function textureResolve(string $id, string $kind = 'item'): ?string
{
    $id = textureNormaliseId($id);
    return texturesManifest($kind)[$id] ?? null;
}

/** Does a real texture exist for this id? */
function textureHas(string $id, string $kind = 'item'): bool
{
    return textureResolve($id, $kind) !== null;
}

/** Every kind's manifest, for exporting to the client in one shot. */
function texturesManifestAll(): array
{
    $out = [];
    foreach (MC_TEXTURE_KINDS as $kind) $out[$kind] = texturesManifest($kind);
    return $out;
}

/** Every kind's modern-variant manifest — see texturesManifestModern(). */
function texturesManifestModernAll(): array
{
    $out = [];
    foreach (MC_TEXTURE_KINDS as $kind) $out[$kind] = texturesManifestModern($kind);
    return $out;
}

// ── VERSION-AWARE ASSET STATUS ───────────────────
// Two separate, small pieces of version-awareness, kept apart because
// they answer different questions:
//
//  1. Does this id exist at all in the selected version? That's game
//     data, not a texture concern — it already lives on the block/item
//     registries (blocksAll()'s 6th tuple element, itemsRegistry()'s
//     'min' key), the same 'min' => <rank> convention used throughout
//     the app. This file stays registry-agnostic (no require of
//     lib/data/*) and just accepts the caller's already-known minimum
//     rank, so it works for any future kind without new coupling.
//
//  2. Given the id exists, is there a *different* authentic texture for
//     the newer art era? A handful of vanilla textures were redrawn in
//     the 1.20.5+ era (see MC_FEATURES['modern_textures']) — for those,
//     assets/textures/<kind>/_modern/<id>.png overrides the base file.
//     This is opt-in per id: absent a _modern file, the base texture is
//     used for every version, which is correct for the ~95% of ids
//     whose art hasn't changed.

/**
 * Every texture present in a kind's modern-variant folder — same shape
 * and caching as texturesManifest(), scanned separately since it's a
 * different (and much smaller) directory.
 */
function texturesManifestModern(string $kind): array
{
    static $manifests = [];
    if (isset($manifests[$kind])) return $manifests[$kind];
    if (!in_array($kind, MC_TEXTURE_KINDS, true)) return $manifests[$kind] = [];

    $manifest = [];
    $relDir = 'assets/textures/' . $kind . '/_modern';
    $dir = dirname(__DIR__) . '/' . $relDir;
    if (!is_dir($dir)) return $manifests[$kind] = $manifest;

    foreach (scandir($dir) ?: [] as $file) {
        if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $file)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, MC_TEXTURE_EXTS, true)) continue;
        $id = textureNormaliseId(pathinfo($file, PATHINFO_FILENAME));
        if ($id === '' || isset($manifest[$id])) continue;
        $manifest[$id] = $relDir . '/' . $file;
    }
    ksort($manifest);
    return $manifests[$kind] = $manifest;
}

/**
 * Resolve one id to a texture path, preferring the modern-era variant
 * when the selected version has 'modern_textures' and one exists.
 * $versionId is passed through to mcHas() — null means "the visitor's
 * current version" (mcCurrentVersion()'s default).
 */
function textureResolveForVersion(string $id, string $kind = 'item', ?string $versionId = null): ?string
{
    $norm = textureNormaliseId($id);
    if (mcHas($versionId, 'modern_textures')) {
        $modern = texturesManifestModern($kind)[$norm] ?? null;
        if ($modern !== null) return $modern;
    }
    return textureResolve($id, $kind);
}

/**
 * The full FOUND / MISSING / UNAVAILABLE_FOR_VERSION answer for one id.
 * $minRank is the id's own 'min' rank from its registry (0 if it has
 * none, i.e. always available) — the caller supplies it rather than
 * this file reaching into blocksAll()/itemsRegistry() itself, so a
 * future 'entity' or 'mob_effect' kind needs no change here.
 */
function textureStatus(string $id, string $kind, int $minRank = 0, ?string $versionId = null): array
{
    if ($minRank > mcVersion($versionId)['rank']) {
        return ['status' => 'unavailable_for_version', 'path' => null];
    }
    $path = textureResolveForVersion($id, $kind, $versionId);
    return $path !== null
        ? ['status' => 'found', 'path' => $path]
        : ['status' => 'missing', 'path' => null];
}
