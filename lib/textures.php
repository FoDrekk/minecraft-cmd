<?php
// ================================================
// lib/textures.php — Minecraft asset registry
// ------------------------------------------------
// One place that answers "is there a real texture for this id?".
// Everything visual in the app resolves through here rather than
// hardcoding a path per page.
//
// DROP-IN PATH
// ------------
// Put PNG (or WEBP) files in assets/textures/, named by their
// Minecraft id:
//
//   assets/textures/diamond_sword.png
//   assets/textures/oak_planks.png
//   assets/textures/observer.png
//
// and they are picked up automatically — no code change, no manifest
// to regenerate. The repository intentionally ships none: Mojang's
// textures are not ours to redistribute, so the owner supplies their
// own legitimately obtained copies. Until then every lookup falls
// through to the honest colour/glyph fallback in assets/mcvisual.js,
// which never claims to be an authentic texture.
// ================================================

const MC_TEXTURE_DIR  = 'assets/textures';
const MC_TEXTURE_EXTS = ['png', 'webp'];

/**
 * Ids that are commonly written a second way. Kept small on purpose —
 * this is for genuine aliases, not for guessing at a lookalike texture.
 */
const MC_TEXTURE_ALIASES = [
    'grass'          => 'grass_block',
    'redstone_dust'  => 'redstone',
    'wood'           => 'oak_planks',
    'planks'         => 'oak_planks',
    'slab'           => 'oak_slab',
    'fence'          => 'oak_fence',
    'door'           => 'spruce_door',
];

/** `minecraft:diamond_sword` / `Diamond Sword` -> `diamond_sword`. */
function textureNormaliseId(string $id): string
{
    $id = strtolower(trim($id));
    $id = preg_replace('/^minecraft:/', '', $id);
    $id = preg_replace('/[^a-z0-9_]+/', '_', $id);
    return trim($id, '_');
}

/**
 * Every texture present on disk: normalised id => web path.
 * Scanned once per request — the directory is small, and the result is
 * a list of names, not image data.
 */
function texturesManifest(): array
{
    static $manifest = null;
    if ($manifest !== null) return $manifest;

    $manifest = [];
    $dir = dirname(__DIR__) . '/' . MC_TEXTURE_DIR;
    if (!is_dir($dir)) return $manifest;

    foreach (scandir($dir) ?: [] as $file) {
        // Only plain filenames become web paths. A name with quotes,
        // angle brackets or path separators is skipped rather than
        // escaped downstream — the path ends up in HTML attributes, CSS
        // url() and an inline script, and the safest handling is to
        // never let an odd one in.
        if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $file)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, MC_TEXTURE_EXTS, true)) continue;
        $id = textureNormaliseId(pathinfo($file, PATHINFO_FILENAME));
        if ($id === '') continue;
        // First extension in MC_TEXTURE_EXTS order wins, so a png and a
        // webp of the same id resolve predictably instead of by readdir order.
        if (isset($manifest[$id])) continue;
        $manifest[$id] = MC_TEXTURE_DIR . '/' . $file;
    }
    ksort($manifest);
    return $manifest;
}

/**
 * Resolve one id to a real asset, following the alias table once.
 * Returns ['id', 'src', 'source'] where source is:
 *   'asset'  — an exact file for this id
 *   'alias'  — a file found via MC_TEXTURE_ALIASES
 *   'none'   — nothing on disk; the caller falls back
 */
function textureResolve(string $id): array
{
    $id = textureNormaliseId($id);
    $manifest = texturesManifest();

    if (isset($manifest[$id])) {
        return ['id' => $id, 'src' => $manifest[$id], 'source' => 'asset'];
    }
    $alias = MC_TEXTURE_ALIASES[$id] ?? null;
    if ($alias !== null && isset($manifest[$alias])) {
        return ['id' => $alias, 'src' => $manifest[$alias], 'source' => 'alias'];
    }
    return ['id' => $id, 'src' => null, 'source' => 'none'];
}

/** Does a real asset exist for this id? */
function textureHas(string $id): bool
{
    return textureResolve($id)['source'] !== 'none';
}
