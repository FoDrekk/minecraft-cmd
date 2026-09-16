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
