<?php
// ================================================
// enchantments.php — Enchantment Hub
// ------------------------------------------------
// Select item -> Recommended -> Customize -> Conflict check ->
// Command preview -> Copy -> Save. Reuses the existing item catalogue
// (items.php), version/feature-gate system (lib/mc.php) and the
// shared item/command builder (MC.item() in assets/mccmd.js) rather
// than inventing a parallel command generator.
// ================================================
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/data/enchantments.php';
require_once __DIR__ . '/items.php';

// Only items an enchantment slot actually exists for are offered —
// arrows, food, blocks etc. in items.php stay out of the picker.
$enchItemGroups = [];
foreach ($ITEMS as $cat => $list) {
    foreach ($list as [$id, $name]) {
        $slot = enchantSlotForItem($id);
        if ($slot) $enchItemGroups[$cat][] = ['id' => $id, 'name' => $name, 'slot' => $slot];
    }
}

$css = <<<CSS
.eh-page { max-width:1180px; margin:0 auto; padding:20px 24px 64px; position:relative; z-index:1 }
.eh-layout { display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start }
@media(max-width:980px){ .eh-layout{ grid-template-columns:1fr } }

.eh-search { margin-bottom:12px }
.eh-cat-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px }
.eh-cat-tab { padding:6px 13px; font-size:12px; font-weight:700; background:var(--bg3); border:1px solid var(--border2);
  color:var(--text3); border-radius:var(--r-sm); cursor:pointer; transition:all .13s }
.eh-cat-tab:hover { border-color:var(--green); color:var(--green) }
.eh-cat-tab.active { background:rgba(93,190,122,0.1); border-color:rgba(93,190,122,0.35); color:var(--green) }

.eh-item-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:8px; max-height:420px; overflow-y:auto; padding:2px }
.eh-item-card { display:flex; flex-direction:column; align-items:center; gap:7px; padding:12px 8px;
  background:var(--card); border:1px solid var(--border); border-radius:var(--r); cursor:pointer; transition:all .13s; text-align:center }
.eh-item-card:hover { border-color:var(--border3); background:var(--card2); transform:translateY(-1px) }
.eh-item-card.sel { border-color:var(--green); background:rgba(93,190,122,0.08); box-shadow:var(--glow-green) }
.eh-item-card.locked { opacity:.4; cursor:not-allowed }
.eh-item-card.locked:hover { transform:none; border-color:var(--border) }
.eh-item-name { font-size:11.5px; font-weight:600; color:var(--text2); line-height:1.3 }
.eh-item-lock { font-size:9.5px; font-family:var(--mono); color:var(--text3) }

.eh-empty-side { text-align:center; padding:40px 18px; color:var(--text3) }

.eh-selected-head { display:flex; align-items:center; gap:12px; margin-bottom:14px }
.eh-selected-name { font-size:16px; font-weight:800 }
.eh-selected-slot { font-size:11.5px; color:var(--text3) }

.eh-rec-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:8px }
.eh-rec-card { display:block; text-align:left; padding:10px 12px; background:var(--bg3); border:1px solid var(--border2);
  border-radius:var(--r-sm); cursor:pointer; transition:all .13s; width:100%; font-family:var(--body) }
.eh-rec-card:hover { border-color:var(--gold); }
.eh-rec-card.on { border-color:var(--gold); background:rgba(232,169,74,0.1) }
.eh-rec-name { font-size:13px; font-weight:700; color:var(--text); margin-bottom:2px }
.eh-rec-stars { font-size:11px; color:var(--gold); letter-spacing:1px; margin-bottom:4px }
.eh-rec-why { font-size:11px; color:var(--text3); line-height:1.4 }

.eh-ench-list { display:flex; flex-direction:column; gap:5px }
.eh-ench-row { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:10px;
  padding:8px 11px; background:var(--bg3); border:1px solid var(--border2); border-radius:var(--r-sm) }
.eh-ench-row.conflict { border-color:rgba(224,85,85,0.5); background:rgba(224,85,85,0.06) }
.eh-ench-row input[type=checkbox] { width:16px; height:16px; accent-color:var(--gold); cursor:pointer }
.eh-ench-main { min-width:0 }
.eh-ench-name { font-size:12.5px; font-weight:700; color:var(--text) }
.eh-ench-stars { font-size:10px; color:var(--gold); letter-spacing:1px }
.eh-ench-desc { font-size:10.5px; color:var(--text3); line-height:1.35; margin-top:1px }
.eh-ench-level { display:flex; align-items:center; gap:5px }
.eh-ench-level input { width:52px; text-align:center }
.eh-ench-level .max { font-size:10px; font-family:var(--mono); color:var(--text3) }

.eh-conflict-banner { background:rgba(224,85,85,0.08); border:1px solid rgba(224,85,85,0.35); border-radius:var(--r);
  padding:12px 14px; margin-bottom:12px }
.eh-conflict-banner .title { font-weight:800; color:var(--red); margin-bottom:6px; display:flex; align-items:center; gap:7px }
.eh-conflict-line { font-size:12.5px; color:var(--text2); line-height:1.6 }

.eh-lore-lines { display:flex; flex-direction:column; gap:6px }
.eh-lore-lines input { width:100% }

.star-note { font-size:10px; color:var(--text3); font-style:italic; margin-top:2px }

details.adv { margin-top:14px; border-top:1px solid var(--border); padding-top:12px }
details.adv > summary {
  cursor:pointer; font-size:12px; font-family:var(--mono); letter-spacing:1.2px;
  text-transform:uppercase; color:var(--text3); font-weight:600; list-style:none;
  display:flex; align-items:center; gap:6px; user-select:none;
}
details.adv > summary::-webkit-details-marker { display:none }
details.adv > summary::before { content:'▸'; transition:transform .15s }
details.adv[open] > summary::before { transform:rotate(90deg) }
details.adv > summary:hover { color:var(--text2) }
details.adv .adv-body { padding-top:12px }
CSS;

ui_head('Enchantment Hub', '', $css);

// Check for preset parameter
$presetId = $_GET['preset'] ?? '';
$presetItem = $_GET['item'] ?? '';
?>
<div class="eh-page">
  <?php ui_page_header('✨', 'Enhance Item', 'Choose an item, pick enchantments, check conflicts, generate the command.'); ?>

  <!-- Step indicator -->
  <?php ui_step_indicator(['Target', 'Item', 'Enchant', 'Preview'], 1, 'eh-steps'); ?>

  <div class="eh-layout">
    <!-- LEFT: item picker + recommended + customize -->
    <div style="display:flex;flex-direction:column;gap:14px">

      <?php ui_card('1. Choose item', function () use ($enchItemGroups) { ?>
        <input type="text" id="eh-search" class="eh-search" placeholder="Search item — sword, boots, trident…" autocomplete="off">
        <div class="eh-cat-tabs" id="eh-cat-tabs">
          <button class="eh-cat-tab active" data-eh-cat="">All</button>
          <?php foreach (array_keys($enchItemGroups) as $cat): ?>
          <button class="eh-cat-tab" data-eh-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
          <?php endforeach; ?>
        </div>
        <div class="eh-item-grid" id="eh-item-grid"></div>
      <?php }, 'green'); ?>

      <div id="eh-picked-wrap" hidden>
        <?php ui_card('2. Recommended', function () { ?>
          <div class="eh-selected-head" id="eh-selected-head"></div>
          <div class="eh-rec-grid" id="eh-rec-grid"></div>
          <p class="star-note">★ Usefulness is this app's own suggestion, not an official Minecraft rating.</p>
        <?php }, 'gold'); ?>

        <?php ui_card('3. Customize', function () { ?>
          <label class="check-row" style="margin-bottom:10px">
            <input type="checkbox" id="eh-advanced" onchange="ehToggleAdvanced()">
            <span>Advanced — allow levels above the normal maximum (command-only, works in survival too but not obtainable from an enchanting table or anvil)</span>
          </label>
          <div id="eh-conflict-banner" hidden></div>
          <div class="eh-ench-list" id="eh-ench-list"></div>
        <?php }, 'purple'); ?>

        <?php ui_card('4. Target & details', function () { ?>
          <div class="row">
            <?= ui_field('Target', '<input id="eh-target" value="@p" placeholder="@p, @s, @a, or player name" oninput="ehRebuild()">') ?>
            <?= ui_field('Count', '<input id="eh-count" type="number" min="1" max="6400" value="1" oninput="ehRebuild()">') ?>
          </div>
          <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px">
            <?php foreach (MC_SELECTORS as $sel => $info): if (!$info['quick']) continue; ?>
            <button type="button" class="chip" data-eh-target="<?= e($sel) ?>"><?= e($sel) ?> — <?= e($info['label']) ?></button>
            <?php endforeach; ?>
          </div>
          <div id="eh-target-says" style="margin-bottom:12px"></div>
          <div class="row">
            <?= ui_field('Custom name (optional)', '<input id="eh-name" placeholder="e.g. Boss Killer" oninput="ehRebuild()">') ?>
            <?= ui_field('Name colour', '<select id="eh-name-color" onchange="ehRebuild()"></select>') ?>
          </div>
          <details class="adv">
            <summary>Lore (optional)</summary>
            <div class="adv-body">
              <div class="eh-lore-lines" id="eh-lore-lines"></div>
              <button class="btn btn-ghost btn-sm" style="margin-top:8px" onclick="ehAddLore()">+ Add line</button>
              <p class="hint">A short line or two shown under the item name in the tooltip.</p>
            </div>
          </details>
        <?php }, 'blue'); ?>
      </div>

    </div>

    <!-- RIGHT: preview -->
    <div>
      <div class="card" style="position:sticky;top:70px">
        <div class="card-header"><span style="color:var(--gold)">●</span> COMMAND PREVIEW</div>
        <div class="card-body">
          <div id="eh-version-note" class="hint" style="margin-bottom:10px"></div>
          <?php ui_cmdout('out-ench', 'enchant'); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var ENCH_ITEM_GROUPS   = <?= json_encode($enchItemGroups) ?>;
var ENCH_META          = <?= json_encode(enchantMeta()) ?>;
var ENCH_SLOTS         = <?= json_encode(enchantSlots()) ?>;
var ENCH_CONFLICT_GROUPS = <?= json_encode(enchantConflictGroups()) ?>;
var ENCH_RECOMMENDED   = <?php
    $bySlot = [];
    foreach ($enchItemGroups as $cat => $items) {
        foreach ($items as $it) $bySlot[$it['slot']] = enchantRecommended($it['id']);
    }
    echo json_encode($bySlot);
?>;
var ENCH_PRESETS        = <?= json_encode(enchant_presets()) ?>;
var ENCH_PRESET_ID      = <?= json_encode($presetId) ?>;
var ENCH_PRESET_ITEM    = <?= json_encode($presetItem) ?>;
</script>
<script src="assets/enchantments.js"></script>
<?php ui_foot(); ?>
