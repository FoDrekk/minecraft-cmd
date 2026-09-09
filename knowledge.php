<?php
// ================================================
// knowledge.php — Materials, Palettes, Tips, Build Ideas
// ------------------------------------------------
// The building half of the toolkit. Everything here cross-links:
// a material opens in the palette builder, a palette opens in a
// build idea, an idea links to the techniques it depends on.
// ================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/data/blocks.php';
require_once __DIR__ . '/lib/data/palettes.php';
require_once __DIR__ . '/lib/data/tips.php';
require_once __DIR__ . '/lib/data/ideas.php';
require_once __DIR__ . '/lib/data/blueprints.php';
require_once __DIR__ . '/lib/data/enchantments.php';
require_once __DIR__ . '/items.php';

$tab  = $_GET['t'] ?? 'materials';
if (!in_array($tab, ['materials', 'palette', 'tips', 'ideas', 'items', 'enchants'], true)) $tab = 'materials';

$blocks   = blocksList(mcCurrentVersion());
$styles   = paletteStyles();
$presets  = palettePresets();
$saved    = paletteGet();
$openIdea = $_GET['idea'] ?? '';

$css = <<<CSS
.kn-page { max-width:1240px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }
.kn-tabs { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap }
.kn-tab { padding:8px 15px; border-radius:var(--r-sm); border:1px solid var(--border2); background:var(--card);
  color:var(--text3); text-decoration:none; font-size:13.5px; font-weight:600; transition:all .13s }
.kn-tab:hover { border-color:var(--border3); color:var(--text2) }
.kn-tab.active { background:rgba(93,190,122,0.09); border-color:rgba(93,190,122,0.3); color:var(--green) }

/* Materials */
.mat-controls { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px }
.mat-controls input { flex:1; min-width:200px; max-width:360px }
.mat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(232px,1fr)); gap:10px }
.mat-card { display:flex; gap:11px; padding:11px 13px; background:var(--card); border:1px solid var(--border);
  border-radius:var(--r); transition:all .13s }
.mat-card:hover { border-color:var(--border3); background:var(--card2) }
.mat-swatch { width:42px; height:42px; border-radius:var(--r-xs); flex-shrink:0;
  border:1px solid rgba(255,255,255,0.14); box-shadow:inset 0 -8px 14px rgba(0,0,0,0.25) }
.mat-name { font-size:13.5px; font-weight:700; color:var(--text) }
.mat-id { font-family:var(--mono); font-size:10.5px; color:var(--text3); cursor:pointer; word-break:break-all }
.mat-id:hover { color:var(--green) }
.mat-note { font-size:11.5px; color:var(--text3); line-height:1.45; margin-top:4px }
.mat-styles { display:flex; gap:3px; flex-wrap:wrap; margin-top:5px }
.mat-style { font-size:9px; font-family:var(--mono); letter-spacing:.6px; text-transform:uppercase;
  color:var(--text3); border:1px solid var(--border2); padding:1px 5px; border-radius:3px }

/* Palette */
.pal-roles { display:grid; grid-template-columns:repeat(auto-fit,minmax(132px,1fr)); gap:9px }
@media(min-width:760px){ .pal-roles { grid-template-columns:repeat(5,1fr) } }
.pal-role { background:var(--bg2); border:1px solid var(--border); border-radius:var(--r-sm); padding:10px }
.pal-role-label { font-size:9.5px; font-family:var(--mono); letter-spacing:1.4px; text-transform:uppercase;
  color:var(--text3); font-weight:700; margin-bottom:7px }
.pal-role-swatch { height:52px; border-radius:var(--r-xs); margin-bottom:8px;
  border:1px solid rgba(255,255,255,0.12); box-shadow:inset 0 -12px 20px rgba(0,0,0,0.28) }
.pal-role select { font-size:12px; padding:6px 8px }
.wall { display:grid; grid-template-columns:repeat(26,1fr); gap:1px; border-radius:var(--r-sm);
  overflow:hidden; border:1px solid var(--border); padding:2px; background:var(--bg) }
.wall i { aspect-ratio:1; border-radius:1px; display:block }
.pal-actions { display:flex; gap:7px; flex-wrap:wrap; margin-top:14px }
.saved-pal { display:flex; align-items:center; gap:10px; padding:9px 12px; border:1px solid var(--border);
  border-radius:var(--r-sm); background:var(--card); margin-bottom:6px }
.saved-pal-swatches { display:flex; gap:3px }
.saved-pal-swatches i { width:17px; height:17px; border-radius:3px; border:1px solid rgba(255,255,255,0.12) }

/* Tips */
.tip-group-title { font-size:11px; font-family:var(--mono); letter-spacing:2px; text-transform:uppercase;
  color:var(--text3); font-weight:700; margin:24px 0 10px }
.tip { background:var(--card); border:1px solid var(--border); border-radius:var(--r); margin-bottom:9px; overflow:hidden }
.tip summary { padding:13px 16px; cursor:pointer; list-style:none; display:flex; align-items:flex-start; gap:10px }
.tip summary::-webkit-details-marker { display:none }
.tip summary::before { content:'▸'; color:var(--text3); transition:transform .15s; flex-shrink:0; margin-top:1px }
.tip[open] summary::before { transform:rotate(90deg) }
.tip summary:hover { background:rgba(255,255,255,0.02) }
.tip-title { font-size:14px; font-weight:700; color:var(--text) }
.tip-summary { font-size:12.5px; color:var(--text3); margin-top:2px }
.tip-body { padding:0 16px 15px 42px; font-size:13.5px; color:var(--text2); line-height:1.7 }
.tip-try { margin-top:11px; padding:10px 13px; background:rgba(93,190,122,0.06);
  border:1px solid rgba(93,190,122,0.2); border-radius:var(--r-sm); font-size:13px; color:var(--green3) }
.tip-try b { color:var(--green); font-size:10px; font-family:var(--mono); letter-spacing:1.4px;
  text-transform:uppercase; display:block; margin-bottom:3px }

/* Ideas */
.idea-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(272px,1fr)); gap:12px }
.idea-card { background:var(--card); border:1px solid var(--border); border-radius:var(--r);
  padding:15px; cursor:pointer; transition:all .14s }
.idea-card:hover { border-color:var(--border3); background:var(--card2); transform:translateY(-1px) }
.idea-icon { font-size:24px; margin-bottom:8px }
.idea-title { font-size:15px; font-weight:700; margin-bottom:5px }
.idea-meta { display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-bottom:8px }
.idea-size { font-family:var(--mono); font-size:11px; color:var(--text3) }
.idea-concept { font-size:12.5px; color:var(--text3); line-height:1.55 }
.idea-detail { background:var(--card); border:1px solid var(--border2); border-radius:var(--r-lg); padding:22px }
.idea-detail h2 { font-size:22px; margin-bottom:8px }
.feature-list, .tip-links { display:flex; flex-direction:column; gap:7px; margin-top:8px }
.feature-list li { font-size:13.5px; color:var(--text2); line-height:1.6; list-style:none; padding-left:19px; position:relative }
.feature-list li::before { content:'◆'; position:absolute; left:0; color:var(--green); font-size:9px; top:5px }
.step-list { display:flex; flex-direction:column; gap:9px; margin-top:8px; counter-reset:step; padding-left:0 }
.step-list li { font-size:13.5px; color:var(--text2); line-height:1.6; list-style:none; padding-left:30px; position:relative; counter-increment:step }
.step-list li::before {
  content:counter(step); position:absolute; left:0; top:0; width:21px; height:21px; border-radius:50%;
  background:rgba(93,190,122,0.12); border:1px solid rgba(93,190,122,0.3); color:var(--green);
  font-family:var(--mono); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center;
}
.tag { font-size:9.5px; font-family:var(--mono); letter-spacing:1px; text-transform:uppercase;
  border:1px solid var(--border2); color:var(--text3); padding:2px 6px; border-radius:4px }

/* Items + Enchantments knowledge cards */
.item-grid, .ench-know-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:10px }
.item-card, .ench-know-card { display:flex; gap:11px; padding:12px 13px; background:var(--card); border:1px solid var(--border);
  border-radius:var(--r); transition:all .13s }
.item-card:hover, .ench-know-card:hover { border-color:var(--border3); background:var(--card2) }
.item-card-body, .ench-know-body { min-width:0; flex:1 }
.item-name, .ench-know-name { font-size:13px; font-weight:700; color:var(--text) }
.item-id { font-family:var(--mono); font-size:10.5px; color:var(--text3); cursor:pointer; word-break:break-all }
.item-id:hover { color:var(--green) }
.ench-know-stars { font-size:10.5px; color:var(--gold); letter-spacing:1px; margin:2px 0 4px }
.ench-know-desc { font-size:11.5px; color:var(--text3); line-height:1.45; margin-bottom:5px }
.ench-know-facts { font-size:10.5px; color:var(--text3); line-height:1.7 }
.ench-know-facts b { color:var(--text2) }
.ench-know-link { font-size:10.5px; color:var(--green); text-decoration:none; font-weight:600 }
CSS;

ui_head('Knowledge', '', $css);
?>
<div class="kn-page">
  <?php ui_page_header('📚', 'Knowledge', 'Blocks, palettes, technique and what to build next.'); ?>

  <div class="kn-tabs">
    <a class="kn-tab <?= $tab === 'materials' ? 'active' : '' ?>" href="knowledge.php?t=materials">🪨 Materials</a>
    <a class="kn-tab <?= $tab === 'items' ? 'active' : '' ?>" href="knowledge.php?t=items">📦 Items</a>
    <a class="kn-tab <?= $tab === 'enchants' ? 'active' : '' ?>" href="knowledge.php?t=enchants">✨ Enchantments</a>
    <a class="kn-tab <?= $tab === 'palette' ? 'active' : '' ?>" href="knowledge.php?t=palette">🎨 Palettes</a>
    <a class="kn-tab <?= $tab === 'tips' ? 'active' : '' ?>" href="knowledge.php?t=tips">💡 Building tips</a>
    <a class="kn-tab <?= $tab === 'ideas' ? 'active' : '' ?>" href="knowledge.php?t=ideas">🏰 Build ideas</a>
  </div>

<?php if ($tab === 'materials'): ?>
  <div class="mat-controls">
    <input type="text" id="mat-search" placeholder="Search blocks — deepslate, copper, lantern…"
           value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
    <select id="mat-cat">
      <option value="">All categories</option>
      <?php foreach (blocksCategories() as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <select id="mat-style">
      <option value="">All styles</option>
      <?php foreach ($styles as $id => $s): ?><option value="<?= e($id) ?>"><?= e($s['label']) ?></option><?php endforeach; ?>
    </select>
    <span class="muted" style="font-size:12px" id="mat-count"></span>
  </div>
  <div class="mat-grid" id="mat-grid"></div>
  <div id="mat-empty"></div>

<?php elseif ($tab === 'palette'): ?>
  <div class="split">
    <div class="stack">
      <?php ui_card('Palette', function () use ($styles) { ?>
        <div class="inline" style="margin-bottom:14px">
          <div class="field" style="flex:1;min-width:180px"><label>Style</label>
            <select id="pal-style" onchange="applyStyle()">
              <?php foreach ($styles as $id => $s): ?>
                <option value="<?= e($id) ?>"><?= e($s['icon'] . ' ' . $s['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-purple" style="align-self:flex-end" onclick="randomise()">🎲 Random</button>
        </div>
        <div class="muted" style="font-size:12.5px;margin-bottom:14px" id="pal-style-desc"></div>
        <div class="pal-roles" id="pal-roles"></div>
        <div class="pal-actions">
          <button class="btn btn-green btn-sm" onclick="copyPalette()">📋 Copy palette</button>
          <button class="btn btn-gold btn-sm" onclick="savePalette()">💾 Save</button>
          <button class="btn btn-ghost btn-sm" onclick="randomise()">🎲 Another</button>
        </div>
      <?php }, 'purple'); ?>

      <?php ui_card('How it looks together', function () { ?>
        <div class="wall" id="pal-wall"></div>
        <div class="hint" style="margin-top:9px">
          Roughly the proportions to aim for: mostly the main block, a third secondary, accents on edges and detail
          only where the eye lands.
        </div>
      <?php }, 'green'); ?>
    </div>

    <div class="stack">
      <?php ui_card('Presets', function () use ($presets, $styles) { ?>
        <?php foreach ($presets as $id => $p): ?>
          <div class="saved-pal" style="cursor:pointer" data-preset="<?= e($id) ?>">
            <div class="saved-pal-swatches">
              <?php foreach ($p['blocks'] as $b): $bl = blocksList()[$b] ?? null; ?>
                <i style="background:<?= e($bl['hex'] ?? '#666') ?>"></i>
              <?php endforeach; ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:600"><?= e($p['name']) ?></div>
              <div style="font-size:11px;color:var(--text3)"><?= e($styles[$p['style']]['label'] ?? $p['style']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php }, 'gold'); ?>

      <?php ui_card('Saved palettes', function () use ($saved) { ?>
        <div id="saved-list">
        <?php if (!$saved): ?>
          <?= ui_empty('🎨', 'Nothing saved yet', 'Build a palette and hit Save to keep it.') ?>
        <?php endif; ?>
        </div>
      <?php }, 'blue'); ?>
    </div>
  </div>

<?php elseif ($tab === 'tips'): ?>
  <div class="mat-controls">
    <input type="text" id="tip-search" placeholder="Search tips — roof, redstone, palette…" autocomplete="off">
    <select id="tip-group">
      <option value="">All categories</option>
      <?php foreach (tipsGroups() as $g): ?><option value="<?= e($g) ?>"><?= e($g) ?></option><?php endforeach; ?>
    </select>
    <span class="muted" style="font-size:12px" id="tip-count"></span>
  </div>
  <div id="tips-list">
  <?php
  $byGroup = [];
  foreach (tipsAll() as $id => $t) $byGroup[$t['group']][$id] = $t;
  foreach ($byGroup as $group => $tips): ?>
    <div class="tip-group-title" data-tip-group-title="<?= e($group) ?>"><?= e($group) ?></div>
    <?php foreach ($tips as $id => $t): ?>
      <details class="tip" id="tip-<?= e($id) ?>" data-tip-group="<?= e($group) ?>"
                data-tip-search="<?= e(strtolower($t['title'] . ' ' . $t['summary'] . ' ' . $t['body'])) ?>">
        <summary>
          <div>
            <div class="tip-title"><?= e($t['title']) ?></div>
            <div class="tip-summary"><?= e($t['summary']) ?></div>
          </div>
        </summary>
        <div class="tip-body">
          <?= e($t['body']) ?>
          <div class="tip-try"><b>Try this now</b><?= e($t['try']) ?></div>
        </div>
      </details>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'ideas'): ?>
  <?php if ($openIdea && isset(ideasAll()[$openIdea])):
      $i = ideasAll()[$openIdea];
      $pal = $presets[$i['palette']] ?? null; ?>
    <a class="btn btn-ghost btn-sm" href="knowledge.php?t=ideas" style="text-decoration:none;margin-bottom:14px;display:inline-block">← All ideas</a>
    <div class="idea-detail">
      <div class="idea-icon" style="font-size:32px"><?= $i['icon'] ?></div>
      <h2><?= e($i['title']) ?></h2>
      <div class="idea-meta">
        <?= ui_difficulty($i['difficulty']) ?>
        <span class="idea-size"><?= e($i['size']) ?></span>
      </div>
      <p style="font-size:14.5px;color:var(--text2);line-height:1.7;margin-bottom:6px"><?= e($i['concept']) ?></p>
      <?php if (!empty($i['note'])): ?>
        <?= ui_warn(e($i['note']), 'info') ?>
      <?php endif; ?>

      <hr class="divider">
      <div class="sec-title">Key design features</div>
      <ul class="feature-list">
        <?php foreach ($i['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
      </ul>

      <?php if ($pal): ?>
      <hr class="divider">
      <div class="sec-title">Materials — Suggested palette: <?= e($pal['name']) ?></div>
      <div class="pal-roles" style="margin-top:8px">
        <?php foreach ($pal['blocks'] as $role => $bid): $b = $blocks[$bid] ?? null; ?>
          <div class="pal-role">
            <div class="pal-role-label"><?= e($role) ?></div>
            <div class="pal-role-swatch" style="background:<?= e($b['hex'] ?? '#666') ?>"></div>
            <div style="font-size:12px;font-weight:600"><?= e($b['name'] ?? $bid) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <a class="btn btn-ghost btn-sm" style="margin-top:11px;display:inline-block;text-decoration:none"
         href="knowledge.php?t=palette&preset=<?= e($i['palette']) ?>">Open in palette builder →</a>
      <?php endif; ?>

      <?php
      $bp = !empty($i['blueprint']) ? blueprintGet($i['blueprint']) : null;
      if ($bp) { $ideaBlueprintPayload = $bp; $ideaBlueprintPayload['materials'] = blueprintMaterialCounts($i['blueprint']); }
      if ($bp): ?>
      <hr class="divider">
      <div class="sec-title">Blueprint &amp; exact material count</div>
      <p class="hint" style="margin-bottom:10px"><?= e($bp['note']) ?></p>
      <div id="bp-viewport-idea" class="bp-widget"></div>
      <div style="margin-top:12px">
        <div style="font-size:11px;font-family:var(--mono);letter-spacing:1.5px;text-transform:uppercase;color:var(--text3);font-weight:700;margin-bottom:8px">Material Calculator — exact count for this blueprint</div>
        <?= ui_material_table(blueprintMaterialCounts($i['blueprint'])) ?>
      </div>
      <?php endif; ?>

      <hr class="divider">
      <div class="sec-title">Build steps</div>
      <ol class="step-list">
        <?php foreach ($i['steps'] as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
      </ol>

      <hr class="divider">
      <div class="sec-title">Techniques this leans on</div>
      <div class="tip-links">
        <?php foreach ($i['tips'] as $tid): $t = tipsAll()[$tid] ?? null; if (!$t) continue; ?>
          <a class="recent-row" style="text-decoration:none" href="knowledge.php?t=tips#tip-<?= e($tid) ?>">
            <span style="font-size:15px">💡</span>
            <span style="flex:1">
              <span style="font-size:13px;font-weight:600;color:var(--text)"><?= e($t['title']) ?></span><br>
              <span style="font-size:11.5px;color:var(--text3)"><?= e($t['summary']) ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>

      <hr class="divider">
      <div class="sec-title">Get started</div>
      <div class="pill-row">
        <a class="btn btn-green btn-sm" style="text-decoration:none" href="build.php?t=planner">Plan the footprint →</a>
        <a class="btn btn-blue btn-sm" style="text-decoration:none" href="build.php?t=clear">Clear the site →</a>
      </div>

      <hr class="divider">
      <div class="sec-title">Save to My Stuff</div>
      <div class="row" style="align-items:flex-end">
        <?= ui_field('Name this build', '<input id="build-save-name" placeholder="e.g. My starter house" value="' . e($i['title']) . '">') ?>
        <button class="btn btn-gold btn-sm" onclick="saveBuild('<?= e($openIdea) ?>', '<?= e($i['palette'] ?? '') ?>')">💾 Save build</button>
      </div>
      <p class="hint">Remembers this idea and its palette so you can find it again in My Stuff.</p>
    </div>
  <?php else: ?>
    <div class="mat-controls">
      <select id="idea-cat">
        <option value="">All categories</option>
        <?php foreach (ideasCategories() as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
      </select>
      <span class="muted" style="font-size:12px" id="idea-count"></span>
    </div>
    <div class="idea-grid" id="idea-grid">
      <?php foreach (ideasAll() as $id => $i): ?>
        <a class="idea-card" data-idea-cat="<?= e($i['category']) ?>" style="text-decoration:none;display:block" href="knowledge.php?t=ideas&idea=<?= e($id) ?>">
          <div class="idea-icon"><?= $i['icon'] ?></div>
          <div class="idea-title"><?= e($i['title']) ?></div>
          <div class="idea-meta"><?= ui_difficulty($i['difficulty']) ?><span class="tag"><?= e($i['category']) ?></span><span class="idea-size"><?= e($i['size']) ?></span></div>
          <div class="idea-concept"><?= e($i['concept']) ?></div>
          <?php if (!empty($i['blueprint'])): ?><span class="tag" style="color:var(--gold);border-color:rgba(232,169,74,0.3)">📐 Blueprint</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'items'): ?>
  <div class="mat-controls">
    <input type="text" id="item-search" placeholder="Search items — sword, apple, diamond…" autocomplete="off">
    <select id="item-cat">
      <option value="">All categories</option>
      <?php foreach (array_keys($ITEMS) as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <span class="muted" style="font-size:12px" id="item-count"></span>
  </div>
  <div class="item-grid" id="item-grid"></div>

<?php elseif ($tab === 'enchants'): ?>
  <div class="mat-controls">
    <input type="text" id="ench-search" placeholder="Search enchantments — sharpness, mending, silk touch…" autocomplete="off">
    <select id="ench-cat">
      <option value="">All categories</option>
      <?php foreach (array_unique(array_column(enchantMeta(), 'category')) as $c): ?>
        <option value="<?= e($c) ?>"><?= e(ucfirst($c)) ?></option>
      <?php endforeach; ?>
    </select>
    <span class="muted" style="font-size:12px" id="ench-count"></span>
  </div>
  <div class="ench-know-grid" id="ench-know-grid"></div>
<?php endif; ?>
</div>

<script>
var BLOCKS = <?= json_encode(array_values($blocks)) ?>;
<?php if ($tab === 'items'): ?>
var ITEMS_BY_CAT = <?php
    $itemsOut = [];
    foreach ($ITEMS as $cat => $list) {
        foreach ($list as [$id, $name]) $itemsOut[$cat][] = ['id' => $id, 'name' => $name, 'slot' => enchantSlotForItem($id)];
    }
    echo json_encode($itemsOut);
?>;
<?php endif; ?>
<?php if ($tab === 'enchants'): ?>
var ENCH_KNOWLEDGE = <?= json_encode(enchantEncyclopedia()) ?>;
<?php endif; ?>
var PALETTE_POOLS = <?= json_encode(paletteRolePools()) ?>;
var PALETTE_PRESETS = <?= json_encode($presets) ?>;
var PALETTE_STYLES = <?= json_encode($styles) ?>;
var SAVED_PALETTES = <?= json_encode($saved) ?>;
var KN_TAB = <?= json_encode($tab) ?>;
var KN_PRESET = <?= json_encode($_GET['preset'] ?? '') ?>;
var KN_STYLE = <?= json_encode($_GET['style'] ?? '') ?>;
<?php if (!empty($ideaBlueprintPayload)): ?>
window.IDEA_BLUEPRINT = <?= json_encode($ideaBlueprintPayload) ?>;
<?php endif; ?>
</script>
<script src="assets/knowledge.js"></script>
<?php ui_foot(); ?>
