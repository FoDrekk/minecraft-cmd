<?php
// ================================================
// lib/ui.php — reusable server-side UI helpers
// ------------------------------------------------
// Small render functions so pages describe *what* they show
// instead of repeating markup. Everything here is plain PHP —
// no template engine, no build step.
// ================================================
require_once __DIR__ . '/mc.php';
require_once __DIR__ . '/data/blocks.php';
require_once __DIR__ . '/textures.php';

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Opening <head> … <body> plus navigation. */
function ui_head(string $title, string $subtitle = '', string $extraCss = ''): void
{
    $root = dirname(__DIR__);
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">' . "\n";
    echo '<title>' . e($title) . ' — Minecraft CMD</title>' . "\n";
    include $root . '/style.php';
    if ($extraCss) echo "<style>\n$extraCss\n</style>\n";
    echo "</head>\n<body>\n";
    include $root . '/nav.php';
}

function ui_page_header(string $icon, string $title, string $sub = ''): void
{
    echo '<div class="page-header"><div class="page-title">' . $icon . ' <span>' . e($title) . '</span></div>';
    if ($sub) echo '<div class="page-sub">' . e($sub) . '</div>';
    echo '</div>';
}

function ui_foot(): void
{
    echo "\n</body>\n</html>";
}

/** Card wrapper. Pass a closure that renders the body. */
function ui_card(string $title, callable $body, string $accent = 'green', string $extraClass = ''): void
{
    echo '<div class="card ' . e($extraClass) . '">';
    if ($title !== '') {
        echo '<div class="card-header"><span class="card-header-dot" style="background:var(--' . e($accent) . ')"></span>' . e($title) . '</div>';
    }
    echo '<div class="card-body">';
    $body();
    echo '</div></div>';
}

/** Labelled form field. */
function ui_field(string $label, string $control, string $hint = ''): string
{
    $h = $hint ? '<div class="hint">' . e($hint) . '</div>' : '';
    return '<div class="field"><label>' . e($label) . '</label>' . $control . $h . '</div>';
}

function ui_select(string $id, array $options, string $selected = '', string $attrs = ''): string
{
    $out = '<select id="' . e($id) . '" ' . $attrs . '>';
    foreach ($options as $val => $text) {
        $sel = ((string)$val === (string)$selected) ? ' selected' : '';
        $out .= '<option value="' . e((string)$val) . '"' . $sel . '>' . e((string)$text) . '</option>';
    }
    return $out . '</select>';
}

function ui_input(string $id, string $value = '', string $attrs = ''): string
{
    return '<input id="' . e($id) . '" value="' . e($value) . '" ' . $attrs . '>';
}

/**
 * The shared command output surface.
 * JS drives it with MC.setCommand(id, text, {warnings:[…]}).
 */
function ui_cmdout(string $id, string $tab = 'cmd', array $actions = ['copy', 'copysave', 'fav']): void
{
    $labels = [
        'copy'     => ['📋 Copy',        'btn-green'],
        'copysave' => ['📋 Copy + Save', 'btn-gold'],
        'save'     => ['💾 Save',        'btn-ghost'],
        'fav'      => ['⭐ Library',     'btn-purple'],
    ];
    echo '<div class="cmdout" id="' . e($id) . '" data-cmdout data-tab="' . e($tab) . '">';
    echo '<div class="cmdout-head"><span class="cmdout-label">Generated command</span><span class="cmdout-meta" data-meta></span></div>';
    echo '<div class="cmdout-body" data-cmd></div>';
    echo '<div class="cmdout-warn" data-warn hidden></div>';
    echo '<div class="cmdout-actions">';
    foreach ($actions as $a) {
        if (!isset($labels[$a])) continue;
        [$text, $cls] = $labels[$a];
        echo '<button class="btn ' . $cls . '" data-act="' . e($a) . '">' . $text . '</button>';
    }
    echo '</div></div>';
}

function ui_warn(string $text, string $kind = 'warn'): string
{
    $icon = $kind === 'error' ? '⛔' : ($kind === 'info' ? 'ℹ️' : '⚠');
    $cls  = $kind === 'error' ? 'error-block' : ($kind === 'info' ? 'info-block' : 'warn-block');
    return '<div class="' . $cls . '">' . $icon . ' ' . $text . '</div>';
}

/**
 * A validation message — the shared shape for "here is what happened".
 * $kind: ok | warn | error | info. The icon is real text, not a CSS
 * pseudo-element, so the severity is never carried by colour alone.
 * $text is escaped; $title is optional and leads the line in bold.
 */
function ui_validation(string $kind, string $text, string $title = ''): string
{
    $icons = ['ok' => '✓', 'warn' => '⚠', 'error' => '✕', 'info' => 'ⓘ'];
    $words = ['ok' => 'Valid', 'warn' => 'Warning', 'error' => 'Invalid', 'info' => 'Information'];
    $kind  = isset($icons[$kind]) ? $kind : 'info';
    // role=alert only for the severities a user must not miss.
    $role  = in_array($kind, ['error', 'warn'], true) ? ' role="alert"' : '';

    return '<div class="validation validation-' . e($kind) . '"' . $role . '>'
        . '<i class="validation-icon" aria-hidden="true">' . $icons[$kind] . '</i>'
        . '<span class="sr-only">' . e($words[$kind]) . ': </span>'
        . '<span class="validation-body">'
        . ($title !== '' ? '<b class="validation-title">' . e($title) . '</b>' : '')
        . e($text) . '</span></div>';
}

/**
 * A material chip: texture, name and an optional quantity.
 * Resolves its visual through ui_material_chip(), so a texture dropped
 * into assets/textures/ appears here without touching the caller.
 */
function ui_matchip(string $name, ?int $qty = null, ?string $blockId = null, ?string $glyph = null, bool $compact = false): string
{
    return '<span class="matchip' . ($compact ? ' matchip-compact' : '') . '">'
        . ui_material_chip($blockId ?? $name, $glyph)
        . '<span class="matchip-name">' . e($name) . '</span>'
        . ($qty !== null ? '<span class="matchip-qty">×&nbsp;' . number_format($qty) . '</span>' : '')
        . '</span>';
}

/** Numbered wizard rail. $steps = ['Define area', 'Choose block', …] */
function ui_steps(array $steps, int $active = 1, string $id = ''): void
{
    echo '<div class="steps"' . ($id ? ' id="' . e($id) . '"' : '') . '>';
    foreach ($steps as $i => $label) {
        $n   = $i + 1;
        $cls = $n === $active ? ' active' : ($n < $active ? ' done' : '');
        echo '<div class="step' . $cls . '" data-step="' . $n . '"><span class="step-n">' . $n . '</span>' . e($label) . '</div>';
    }
    echo '</div>';
}

/**
 * Tickable materials list. State persists per key in localStorage.
 * Each item is either a plain string (existing behaviour) or
 * ['label' => ..., 'chip' => html] to show a visual tile — see
 * ui_material_chip(). The chip is decorative (aria-hidden); the
 * label text is always the source of truth for what the item is.
 */
function ui_checklist(string $key, array $items): void
{
    echo '<div class="checklist" data-checklist="' . e($key) . '">';
    foreach ($items as $i => $item) {
        $id    = 'ck_' . e($key) . '_' . $i;
        $label = is_array($item) ? $item['label'] : $item;
        $chip  = is_array($item) ? ($item['chip'] ?? '') : '';
        $cls   = 'check-row' . ($chip !== '' ? ' check-row-visual' : '');
        echo '<label class="' . $cls . '"><input type="checkbox" id="' . $id . '" data-ck="' . $i . '">' . $chip . '<span>' . e($label) . '</span></label>';
    }
    echo '</div>';
}

/**
 * A small visual tile for one material. Resolution order matches the
 * client renderer (assets/mcvisual.js): a real texture dropped into
 * assets/textures/ wins, then the block's approximate palette colour,
 * then a plain emoji glyph the caller supplies. Never a guessed or
 * fabricated texture, and never a broken image.
 */
function ui_material_chip(?string $blockId = null, ?string $glyph = null): string
{
    if ($blockId !== null) {
        $tex = textureResolve($blockId);
        $row = blocksAll()[$blockId] ?? null;
        $name = $row[0] ?? $blockId;

        if ($tex['src'] !== null) {
            return '<span class="mc-chip mc-chip-tex" title="' . e($name) . '" aria-hidden="true">'
                . '<img src="' . e($tex['src']) . '" alt="" loading="lazy" decoding="async"></span>';
        }
        if ($row) {
            return '<span class="mc-chip" style="background:' . e($row[2]) . '" title="' . e($name) . '" aria-hidden="true"></span>';
        }
    }
    if ($glyph !== null && $glyph !== '') {
        return '<span class="mc-chip mc-chip-glyph" aria-hidden="true">' . $glyph . '</span>';
    }
    return '<span class="mc-chip mc-chip-glyph" aria-hidden="true">◻️</span>';
}

function ui_empty(string $icon, string $title, string $sub = ''): string
{
    return '<div class="empty-state"><div class="empty-icon">' . $icon . '</div><div class="empty-title">' . e($title) . '</div>'
        . ($sub ? '<div class="empty-sub">' . e($sub) . '</div>' : '') . '</div>';
}

/** "1,247" -> "19 stacks + 31" — shared by the Material and Farm calculators. */
function ui_stacks(int $n): string
{
    $stacks = intdiv($n, 64);
    $rest   = $n % 64;
    if ($stacks === 0) return $rest . ($rest === 1 ? ' block' : ' blocks');
    $out = $stacks . ($stacks === 1 ? ' stack' : ' stacks');
    if ($rest > 0) $out .= ' + ' . $rest;
    return $out;
}

function ui_badge(string $text, string $color = 'green'): string
{
    return '<span class="badge badge-' . e($color) . '">' . e($text) . '</span>';
}

/** Difficulty pill used by farms and build ideas. */
function ui_difficulty(string $level): string
{
    $map = ['Easy' => 'green', 'Medium' => 'gold', 'Hard' => 'red', 'Expert' => 'purple'];
    return ui_badge($level, $map[$level] ?? 'blue');
}

/**
 * Material Calculator table. $rows = blueprintMaterialCounts() shape:
 * [{name, hex, count}, ...]. Real counts only — never call this with
 * guessed numbers; an idea/farm with no blueprint should show no
 * calculator at all rather than an invented one.
 */
function ui_material_table(array $rows): string
{
    if (!$rows) return '';
    $total = array_sum(array_column($rows, 'count'));
    $out = '<div class="mcalc"><table class="mcalc-table"><tbody>';
    foreach ($rows as $r) {
        $out .= '<tr><td><span class="mcalc-swatch" style="background:' . e($r['hex']) . '"></span></td>'
            . '<td class="mcalc-name">' . e($r['name']) . '</td>'
            . '<td class="mcalc-count">' . number_format($r['count']) . '</td>'
            . '<td class="mcalc-stacks">' . e(ui_stacks($r['count'])) . '</td></tr>';
    }
    $out .= '</tbody></table><div class="mcalc-total">' . number_format($total) . ' blocks total</div></div>';
    return $out;
}

/** Card linking to a tool / guide / idea. */
function ui_tile(string $href, string $icon, string $title, string $desc, string $accent = 'green'): string
{
    return '<a class="tile" href="' . e($href) . '" style="--tile-accent:var(--' . e($accent) . ')">'
        . '<div class="tile-icon">' . $icon . '</div>'
        . '<div class="tile-body"><div class="tile-title">' . e($title) . '</div>'
        . '<div class="tile-desc">' . e($desc) . '</div></div></a>';
}

/** Exports the version table to JS. Called once from style.php. */
function ui_runtime_data(): void
{
    // blockHex is the fallback palette the visual renderer falls back to
    // when no texture exists — ~4KB of id=>colour, not image data, so the
    // client can resolve any block id without a round trip. textures is
    // whatever is actually on disk (empty until assets are supplied), so
    // this costs nothing until it earns it.
    $blockHex = [];
    foreach (blocksAll() as $id => $row) $blockHex[$id] = $row[2];

    $data = [
        'versions'  => MC_VERSIONS,
        'syntax'    => MC_SYNTAX,
        'features'  => MC_FEATURES,
        'selectors' => MC_SELECTORS,
        'textures'  => texturesManifest(),
        'blockHex'  => $blockHex,
        'current'   => mcCurrentVersion(),
    ];
    // JSON_HEX_* matters because this is an inline <script>: without it a
    // string containing "</script>" would close the tag early. Most of
    // this payload is our own constants, but the texture manifest is
    // built from filenames on disk, so it must not be trusted to be
    // script-safe. Slashes stay unescaped to keep paths readable.
    $flags = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    echo '<script>window.MC_DATA=' . json_encode($data, $flags) . ';</script>' . "\n";
}
