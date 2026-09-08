<?php
// ================================================
// lib/mc.php — Minecraft version registry & syntax profiles
// ------------------------------------------------
// Single source of truth for "what syntax does this version use?".
// Everything version-aware in the app reads from here, and the whole
// table is exported to JavaScript by lib/ui.php so client-side
// generators stay in sync with the server.
// ================================================

// ── SYNTAX PROFILES ──────────────────────────────
// Three real syntax eras exist for Java Edition items:
//
//   legacy_nbt      ≤ 1.20.4   /give @p diamond_sword{Enchantments:[{id:"minecraft:sharpness",lvl:5}]}
//   component_json  1.20.5 –   /give @p diamond_sword[enchantments={levels:{sharpness:5}}]
//                   1.21.4     ...text inside components is a quoted JSON string
//   component_snbt  1.21.5 +   /give @p diamond_sword[enchantments={sharpness:5}]
//                              ...text inside components is a real SNBT compound
//
// 1.21.5 was the break: NBT gained heterogeneous lists, so text components
// stopped being stored as JSON strings, `levels` was dropped from the
// enchantments component, and per-component `show_in_tooltip` was replaced
// by the single `tooltip_display` component.
const MC_SYNTAX = [
    'legacy_nbt' => [
        'label'        => 'Legacy NBT',
        'items'        => 'nbt',          // item{...}
        'text_in_nbt'  => 'json_string',  // Name:'{"text":"Hi"}'
        'enchant'      => 'nbt_list',     // Enchantments:[{id:"...",lvl:5}]
        'hide'         => 'hideflags',    // HideFlags:63
        'sign_text'    => 'json_string',
    ],
    'component_json' => [
        'label'        => 'Components (JSON text)',
        'items'        => 'components',   // item[...]
        'text_in_nbt'  => 'json_string',  // custom_name='{"text":"Hi"}'
        'enchant'      => 'levels',       // enchantments={levels:{sharpness:5}}
        'hide'         => 'show_in_tooltip',
        'sign_text'    => 'json_string',
    ],
    'component_snbt' => [
        'label'        => 'Components (SNBT text)',
        'items'        => 'components',
        'text_in_nbt'  => 'snbt',         // custom_name={text:"Hi"}
        'enchant'      => 'flat',         // enchantments={sharpness:5}
        'hide'         => 'tooltip_display',
        'sign_text'    => 'snbt',
    ],
    'bedrock' => [
        'label'        => 'Bedrock',
        'items'        => 'bedrock',      // /give @p diamond_sword 1 0
        'text_in_nbt'  => 'none',
        'enchant'      => 'none',
        'hide'         => 'none',
        'sign_text'    => 'none',
    ],
];

// ── VERSION REGISTRY ─────────────────────────────
// `rank` is a simple chronological index — compare ranks, never version
// strings. Minecraft moved to a year.drop.hotfix scheme in 2026, so
// "26.1" sorts *after* "1.21.11" even though it looks smaller.
const MC_VERSIONS = [
    '26.2' => [
        'label' => '26.2', 'name' => 'Chaos Cubed', 'edition' => 'java',
        'syntax' => 'component_snbt', 'rank' => 90, 'released' => '2026-06-16',
    ],
    '26.1' => [
        'label' => '26.1', 'name' => 'Tiny Takeover', 'edition' => 'java',
        'syntax' => 'component_snbt', 'rank' => 85, 'released' => '2026-03-24',
    ],
    '1.21.11' => [
        'label' => '1.21.11', 'name' => 'Mounts of Mayhem', 'edition' => 'java',
        'syntax' => 'component_snbt', 'rank' => 80, 'released' => '2025-12-09',
    ],
    '1.21.9' => [
        'label' => '1.21.9', 'name' => 'The Copper Age', 'edition' => 'java',
        'syntax' => 'component_snbt', 'rank' => 75, 'released' => '2025-09-30',
    ],
    '1.21.5' => [
        'label' => '1.21.5', 'name' => 'Spring to Life', 'edition' => 'java',
        'syntax' => 'component_snbt', 'rank' => 70, 'released' => '2025-03-25',
    ],
    '1.21.4' => [
        'label' => '1.21.4', 'name' => 'The Garden Awakens', 'edition' => 'java',
        'syntax' => 'component_json', 'rank' => 60, 'released' => '2024-12-03',
    ],
    '1.21.1' => [
        'label' => '1.21.1', 'name' => 'Tricky Trials', 'edition' => 'java',
        'syntax' => 'component_json', 'rank' => 55, 'released' => '2024-08-08',
    ],
    '1.20.6' => [
        'label' => '1.20.6', 'name' => 'Armored Paws', 'edition' => 'java',
        'syntax' => 'component_json', 'rank' => 50, 'released' => '2024-04-29',
    ],
    '1.20.4' => [
        'label' => '1.20.4', 'name' => 'Trails & Tales', 'edition' => 'java',
        'syntax' => 'legacy_nbt', 'rank' => 40, 'released' => '2023-12-07',
    ],
    '1.19.4' => [
        'label' => '1.19.4', 'name' => 'The Wild Update', 'edition' => 'java',
        'syntax' => 'legacy_nbt', 'rank' => 30, 'released' => '2023-03-14',
    ],
    'bedrock' => [
        'label' => 'Bedrock', 'name' => 'Current release', 'edition' => 'bedrock',
        'syntax' => 'bedrock', 'rank' => 10, 'released' => '',
    ],
];

const MC_DEFAULT_VERSION = '26.2';

// ── FEATURE GATES ────────────────────────────────
// Minimum rank at which a feature/argument exists. Keyed by feature name so
// generators can ask "can I use this here?" instead of hardcoding versions.
const MC_FEATURES = [
    'item_components'   => 50,  // 1.20.5+  item[component=value]
    'snbt_text'         => 70,  // 1.21.5+  text components stored as SNBT
    'tooltip_display'   => 70,  // 1.21.5+  tooltip_display component
    'flat_enchantments' => 70,  // 1.21.5+  enchantments={id:lvl}
    'transfer_command'  => 55,  // 1.20.5+ /transfer
    // Signs gained double-sided text (front_text / back_text) in 1.20; the
    // 1.19.4 and earlier block entity format is flat Text1-Text4 + GlowingText.
    // Rank 40 is 1.20.4, the oldest 1.20.x version in our table.
    'sign_front_back'   => 40,  // 1.20+   front_text / back_text
    'locate_biome'      => 30,  // 1.19+   /locate biome
    'attribute_command' => 40,  // 1.20.3+ /attribute (kept conservative)

    // /particle options moved from space-separated extras to SNBT in 1.20.5:
    //   dust 1 0 0 1  →  dust{color:[1,0,0],scale:1}
    'particle_snbt'     => 50,  // 1.20.5+

    // /attribute lost the uuid+name pair in favour of a single namespaced id,
    // and the operations were renamed (add → add_value, and so on) in 1.21.
    'attribute_id_arg'  => 55,  // 1.21+
    'attribute_new_ops' => 55,  // 1.21+

    // Attribute ids dropped the "generic." prefix during the 1.21 cycle.
    // Sources disagree on whether that landed in 1.21 or 1.21.2, so this is
    // gated at 1.21.4 — the first version in our list that is un-prefixed
    // under either reading. Versions in the gap get a warning instead.
    'attribute_no_prefix' => 60, // 1.21.4+
    'attribute_prefix_unclear' => 55, // 1.21–1.21.3: flag the ambiguity

    // Text component events were renamed in 1.21.5:
    //   clickEvent → click_event, hoverEvent → hover_event,
    //   show_text contents → value, run_command value → command,
    //   open_url value → url
    'text_event_snake'  => 70,  // 1.21.5+
];

/** All versions, newest first. */
function mcVersions(): array
{
    $v = MC_VERSIONS;
    uasort($v, fn($a, $b) => $b['rank'] <=> $a['rank']);
    return $v;
}

/** Look up one version, falling back to the default for unknown ids. */
function mcVersion(?string $id): array
{
    $id = $id ?: MC_DEFAULT_VERSION;
    $v  = MC_VERSIONS[$id] ?? MC_VERSIONS[MC_DEFAULT_VERSION];
    return $v + ['id' => isset(MC_VERSIONS[$id]) ? $id : MC_DEFAULT_VERSION];
}

/** The syntax profile a version uses. */
function mcSyntax(?string $id): array
{
    return MC_SYNTAX[mcVersion($id)['syntax']];
}

/** Does this version have the named feature? */
function mcHas(?string $id, string $feature): bool
{
    $min = MC_FEATURES[$feature] ?? PHP_INT_MAX;
    return mcVersion($id)['rank'] >= $min;
}

/** The version id currently selected by the visitor. */
function mcCurrentVersion(): string
{
    $id = $_COOKIE['mc_version'] ?? MC_DEFAULT_VERSION;
    return isset(MC_VERSIONS[$id]) ? $id : MC_DEFAULT_VERSION;
}
