<?php
// ================================================
// build.php — Build tools
// ------------------------------------------------
// The block-placing commands plus the maths that goes with them.
// Fill, Clear, Replace and Clone all share one region control, so a
// region typed once carries across the whole section.
// ================================================
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/data/blocks.php';
require_once __DIR__ . '/lib/data/palettes.php';

$task = $_GET['t'] ?? 'fill';

$TASKS = [
    'Place blocks' => [
        'fill'     => ['🧱', 'Fill area'],
        'clear'    => ['💨', 'Clear area'],
        'replace'  => ['🔁', 'Replace blocks'],
        'setblock' => ['⬛', 'Set block'],
        'clone'    => ['📑', 'Clone area'],
    ],
    'Plan' => [
        'area'    => ['📐', 'Area calculator'],
        'coords'  => ['🧮', 'Coordinate helper'],
        'planner' => ['📝', 'Build planner'],
    ],
];
$valid = [];
foreach ($TASKS as $g) $valid = array_merge($valid, array_keys($g));
if (!in_array($task, $valid, true)) $task = 'fill';

/** Two corners plus a live size readout — the control every region tool shares. */
function ui_region(string $p, array $from = ['~', '~', '~'], array $to = ['~10', '~5', '~10']): void
{
    echo '<div class="region">';
    echo '<div class="region-col"><div class="region-label">From (first corner)</div><div class="coord-row">';
    foreach (['x', 'y', 'z'] as $i => $axis) {
        echo '<div class="coord"><span>' . strtoupper($axis) . '</span><input id="' . e($p) . '-' . $axis . '1" value="' . e($from[$i]) . '" oninput="rebuild()"></div>';
    }
    echo '</div></div>';
    echo '<div class="region-col"><div class="region-label">To (opposite corner)</div><div class="coord-row">';
    foreach (['x', 'y', 'z'] as $i => $axis) {
        echo '<div class="coord"><span>' . strtoupper($axis) . '</span><input id="' . e($p) . '-' . $axis . '2" value="' . e($to[$i]) . '" oninput="rebuild()"></div>';
    }
    echo '</div></div>';
    echo '</div>';
    echo '<div class="volume" id="' . e($p) . '-volume"></div>';
}

/** Block dropdown grouped by category, plus a free-text override. */
function ui_block_select(string $id, string $selected = 'stone'): string
{
    $byCat = [];
    foreach (blocksList() as $b) $byCat[$b['category']][] = $b;
    $out = '<select id="' . e($id) . '" onchange="rebuild()">';
    foreach ($byCat as $cat => $items) {
        $out .= '<optgroup label="' . e($cat) . '">';
        foreach ($items as $b) {
            $out .= '<option value="' . e($b['id']) . '"' . ($b['id'] === $selected ? ' selected' : '') . '>' . e($b['name']) . '</option>';
        }
        $out .= '</optgroup>';
    }
    return $out . '</select>';
}

$css = <<<CSS
.build-layout { display:grid; grid-template-columns:206px 1fr; gap:18px; max-width:1240px; margin:0 auto; padding:18px 24px 56px; position:relative; z-index:1 }
@media(max-width:900px){ .build-layout{grid-template-columns:1fr} }
.task-rail { position:sticky; top:70px; align-self:start }
.task-group-label { font-size:9.5px; font-family:var(--mono); letter-spacing:1.8px; text-transform:uppercase; color:var(--text3); font-weight:700; margin:14px 0 5px 8px }
.task-group-label:first-child { margin-top:0 }
.task-link { display:flex; align-items:center; gap:9px; padding:7px 11px; margin-bottom:2px; border-radius:var(--r-sm);
  text-decoration:none; color:var(--text3); font-size:13px; border:1px solid transparent; transition:all .13s }
.task-link:hover { background:rgba(255,255,255,0.04); color:var(--text2) }
.task-link.active { background:rgba(93,190,122,0.09); border-color:rgba(93,190,122,0.22); color:var(--green); font-weight:600 }
.panel-head { display:flex; align-items:baseline; gap:10px; flex-wrap:wrap; margin-bottom:4px }
.panel-title { font-size:20px; font-weight:800; letter-spacing:-.3px }
.panel-syntax { font-family:var(--mono); font-size:12px; color:var(--text3) }
.panel-desc { font-size:13px; color:var(--text3); margin-bottom:16px; line-height:1.55 }

.region { display:grid; grid-template-columns:1fr 1fr; gap:14px }
@media(max-width:680px){ .region{grid-template-columns:1fr} }
.region-label { font-size:10px; font-family:var(--mono); letter-spacing:1.4px; text-transform:uppercase; color:var(--text3); font-weight:600; margin-bottom:6px }
.coord-row { display:grid; grid-template-columns:repeat(3,1fr); gap:6px }
.coord { position:relative }
.coord span { position:absolute; left:9px; top:50%; transform:translateY(-50%); font-family:var(--mono); font-size:10px; color:var(--text3); pointer-events:none; font-weight:700 }
.coord input { padding-left:24px !important; font-family:var(--mono) }
.volume { margin-top:12px; padding:11px 14px; background:var(--bg2); border:1px solid var(--border); border-radius:var(--r-sm) }
.volume-dims { font-family:var(--mono); font-size:15px; color:var(--green); font-weight:700 }
.volume-count { font-size:12.5px; color:var(--text3); margin-top:2px }
.volume.is-warn { border-color:rgba(232,169,74,0.3); background:rgba(232,169,74,0.05) }
.volume.is-warn .volume-dims { color:var(--gold) }
.volume.is-error { border-color:rgba(224,85,85,0.3); background:rgba(224,85,85,0.05) }
.volume.is-error .volume-dims { color:var(--red) }
.out-wrap { margin-top:18px }
.result-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px }
.result { background:var(--bg2); border:1px solid var(--border); border-radius:var(--r-sm); padding:11px 13px }
.result-val { font-family:var(--mono); font-size:19px; font-weight:800; color:var(--green); line-height:1.25; word-break:break-all }
.result-lbl { font-size:10px; font-family:var(--mono); letter-spacing:1.2px; text-transform:uppercase; color:var(--text3); margin-top:3px }
.result.copyable { cursor:pointer; transition:all .12s }
.result.copyable:hover { border-color:rgba(93,190,122,0.35); background:rgba(93,190,122,0.05) }
.mat-row { display:flex; justify-content:space-between; gap:12px; padding:7px 0; border-bottom:1px solid var(--border); font-size:13px }
.mat-row:last-child { border-bottom:none }
.mat-row b { font-family:var(--mono); color:var(--gold) }
.swatch-row { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px }
.swatch { width:34px; height:34px; border-radius:var(--r-xs); border:1px solid rgba(255,255,255,0.12) }
CSS;

ui_head('Build', '', $css);
?>
<div class="build-layout">

  <div class="task-rail">
    <?php foreach ($TASKS as $group => $items): ?>
      <div class="task-group-label"><?= e($group) ?></div>
      <?php foreach ($items as $id => [$icon, $label]): ?>
        <a class="task-link <?= $task === $id ? 'active' : '' ?>" href="build.php?t=<?= e($id) ?>"><?= $icon ?> <?= e($label) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <div>

  <!-- ═══════════ FILL ═══════════ -->
  <div class="panel" data-panel="fill" <?= $task !== 'fill' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🧱 Fill area</div>
      <span class="panel-syntax">/fill &lt;from&gt; &lt;to&gt; &lt;block&gt; [mode]</span></div>
    <div class="panel-desc">Fills everything between two corners. Both corners are included, so ~ to ~10 is eleven blocks, not ten.</div>

    <?php ui_card('Region', function () { ui_region('fill'); }, 'green'); ?>

    <?php ui_card('Block and mode', function () { ?>
      <div class="row">
        <?= ui_field('Block', ui_block_select('fill-block', 'stone_bricks')) ?>
        <?= ui_field('Block state (optional)', '<input id="fill-state" placeholder="e.g. facing=north,half=top" oninput="rebuild()">') ?>
      </div>
      <?= ui_field('Mode', ui_select('fill-mode', [
            'replace' => 'Replace — fill everything (default)',
            'hollow'  => 'Hollow — outer shell only, inside becomes air',
            'outline' => 'Outline — outer shell only, inside untouched',
            'keep'    => 'Keep — only fill air, leave existing blocks alone',
            'destroy' => 'Destroy — break existing blocks and drop them',
          ], 'replace', 'onchange="rebuild()"')) ?>
      <div id="fill-filter-wrap" hidden style="margin-top:12px">
        <?= ui_field('Only replace this block', ui_block_select('fill-filter', 'air'), 'Leave the mode on Replace and set a filter to swap one block for another') ?>
      </div>
      <label class="check-row" style="margin-top:10px"><input type="checkbox" id="fill-usefilter" onchange="rebuild()"><span>Only replace a specific block</span></label>
    <?php }, 'green'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-fill', 'fill'); ?></div>
  </div>

  <!-- ═══════════ CLEAR AREA ═══════════ -->
  <div class="panel" data-panel="clear" <?= $task !== 'clear' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">💨 Clear area</div>
      <span class="panel-syntax">/fill &lt;from&gt; &lt;to&gt; air …</span></div>
    <div class="panel-desc">Flatten ground, remove a forest, or empty a space to build in. Clearing only the leaves and logs is usually what you want — it leaves the terrain intact.</div>

    <?php ui_card('Region', function () { ?>
      <div class="pill-row" style="margin-bottom:12px">
        <button class="pill active" data-clr-region="corners">Two corners</button>
        <button class="pill" data-clr-region="quick">Around me</button>
      </div>

      <div id="clr-quick-wrap" hidden>
        <div class="pill-row" style="margin-bottom:12px">
          <button class="pill" data-clr-size="3">3 × 3</button>
          <button class="pill active" data-clr-size="5">5 × 5</button>
          <button class="pill" data-clr-size="10">10 × 10</button>
        </div>
        <div class="row">
          <?= ui_field('Size (blocks across)', '<input id="clr-size" type="number" min="1" max="128" value="5" oninput="clrQuickApply()">',
              'The footprint centred on where you stand') ?>
          <?= ui_field('Height', '<input id="clr-height" type="number" min="1" max="128" value="3" oninput="clrQuickApply()">') ?>
          <?= ui_field('Direction', ui_select('clr-dir', [
              'around' => 'Around me',
              'up'     => 'Above me',
              'down'   => 'Below me',
          ], 'around', 'onchange="clrQuickApply()"')) ?>
        </div>
        <p class="hint">Uses ~ relative coordinates, so the region follows wherever you are standing when you run it.</p>
      </div>

      <div id="clr-corners-wrap"><?php ui_region('clr'); ?></div>
    <?php }, 'blue'); ?>

    <?php ui_card('What to remove', function () { ?>
      <div class="pill-row" style="margin-bottom:12px">
        <button class="pill active" data-clear="all">Everything</button>
        <button class="pill" data-clear="trees">Trees only</button>
        <button class="pill" data-clear="water">Water &amp; lava</button>
        <button class="pill" data-clear="one">One block type</button>
      </div>
      <div id="clr-one-wrap" hidden><?= ui_field('Block to remove', ui_block_select('clr-block', 'grass_block')) ?></div>
      <?= ui_warn('Clearing cannot be undone. Stand outside the region before you run it, and check the size below.') ?>
    <?php }, 'blue'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-clear', 'clear-area'); ?></div>
  </div>

  <!-- ═══════════ REPLACE ═══════════ -->
  <div class="panel" data-panel="replace" <?= $task !== 'replace' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🔁 Replace blocks</div>
      <span class="panel-syntax">/fill &lt;from&gt; &lt;to&gt; &lt;new&gt; replace &lt;old&gt;</span></div>
    <div class="panel-desc">Swap one block for another inside a region. Everything else is left exactly as it is.</div>

    <?php ui_card('Region', function () { ui_region('rep'); }, 'orange'); ?>

    <?php ui_card('Swap', function () { ?>
      <div class="row">
        <?= ui_field('Replace this', ui_block_select('rep-from', 'cobblestone')) ?>
        <?= ui_field('With this', ui_block_select('rep-to', 'stone_bricks')) ?>
      </div>
      <?= ui_field('New block state (optional)', '<input id="rep-state" placeholder="e.g. axis=y" oninput="rebuild()">') ?>
    <?php }, 'orange'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-replace', 'replace'); ?></div>
  </div>

  <!-- ═══════════ SET BLOCK ═══════════ -->
  <div class="panel" data-panel="setblock" <?= $task !== 'setblock' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">⬛ Set block</div>
      <span class="panel-syntax">/setblock &lt;x y z&gt; &lt;block&gt; [mode]</span></div>
    <div class="panel-desc">Place exactly one block, including block states you cannot place by hand.</div>

    <?php ui_card('Position and block', function () { ?>
      <div class="coord-row" style="max-width:340px">
        <div class="coord"><span>X</span><input id="sb-x" value="~" oninput="rebuild()"></div>
        <div class="coord"><span>Y</span><input id="sb-y" value="~" oninput="rebuild()"></div>
        <div class="coord"><span>Z</span><input id="sb-z" value="~" oninput="rebuild()"></div>
      </div>
      <div class="row" style="margin-top:12px">
        <?= ui_field('Block', ui_block_select('sb-block', 'stone')) ?>
        <?= ui_field('Mode', ui_select('sb-mode', [
              'replace' => 'Replace (default)',
              'keep'    => 'Keep — only place into air',
              'destroy' => 'Destroy — break and drop what is there',
            ], 'replace', 'onchange="rebuild()"')) ?>
      </div>
      <?= ui_field('Block state (optional)', '<input id="sb-state" placeholder="e.g. facing=north,waterlogged=true" oninput="rebuild()">',
            'Written in square brackets after the block, e.g. oak_stairs[facing=north,half=top]') ?>
    <?php }, 'purple'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-setblock', 'setblock'); ?></div>
  </div>

  <!-- ═══════════ CLONE ═══════════ -->
  <div class="panel" data-panel="clone" <?= $task !== 'clone' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">📑 Clone area</div>
      <span class="panel-syntax">/clone &lt;from&gt; &lt;to&gt; &lt;destination&gt; …</span></div>
    <div class="panel-desc">Copies a region elsewhere. The destination is where the <em>lowest north-west corner</em> of the copy lands, not its centre.</div>

    <?php ui_card('Source region', function () { ui_region('cln'); }, 'teal'); ?>

    <?php ui_card('Destination and mode', function () { ?>
      <div class="region-label">Destination corner</div>
      <div class="coord-row" style="max-width:340px">
        <div class="coord"><span>X</span><input id="cln-dx" value="~20" oninput="rebuild()"></div>
        <div class="coord"><span>Y</span><input id="cln-dy" value="~" oninput="rebuild()"></div>
        <div class="coord"><span>Z</span><input id="cln-dz" value="~" oninput="rebuild()"></div>
      </div>
      <div class="row" style="margin-top:14px">
        <?= ui_field('What to copy', ui_select('cln-mask', [
              'replace'  => 'Everything, including air',
              'masked'   => 'Skip air — paste over what is there',
              'filtered' => 'Only one block type',
            ], 'replace', 'onchange="rebuild()"')) ?>
        <?= ui_field('Source afterwards', ui_select('cln-mode', [
              'normal' => 'Leave the original in place',
              'move'   => 'Move — clear the original',
              'force'  => 'Force — allow overlapping regions',
            ], 'normal', 'onchange="rebuild()"')) ?>
      </div>
      <div id="cln-filter-wrap" hidden><?= ui_field('Only copy this block', ui_block_select('cln-filter', 'stone')) ?></div>
      <div id="clone-explain" class="info-block" style="margin-top:12px"></div>
    <?php }, 'teal'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-clone', 'clone'); ?></div>
  </div>

  <!-- ═══════════ AREA CALCULATOR ═══════════ -->
  <div class="panel" data-panel="area" <?= $task !== 'area' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">📐 Area calculator</div></div>
    <div class="panel-desc">Type two corners and find out how big the region actually is and how many blocks you need to fill it.</div>

    <?php ui_card('Region', function () { ui_region('area', ['0', '64', '0'], ['18', '68', '18']); }, 'gold'); ?>

    <?php ui_card('Results', function () { ?>
      <div class="result-grid" id="area-results"></div>
      <hr class="divider">
      <div class="sec-title">If you filled it</div>
      <div id="area-materials"></div>
      <div class="pill-row" style="margin-top:14px">
        <button class="btn btn-green btn-sm" onclick="sendToFill()">Send to Fill →</button>
        <button class="btn btn-blue btn-sm" onclick="sendToClear()">Send to Clear →</button>
      </div>
    <?php }, 'gold'); ?>
  </div>

  <!-- ═══════════ COORDINATE HELPER ═══════════ -->
  <div class="panel" data-panel="coords" <?= $task !== 'coords' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🧮 Coordinate helper</div></div>
    <div class="panel-desc">
      <b class="mono">~</b> is relative to where the command runs. <b class="mono">^</b> is relative to where you are looking —
      <span class="mono">^ ^ ^5</span> means five blocks straight ahead. Press F3 in game to see your position.
    </div>

    <?php ui_card('Two points', function () { ?>
      <div class="region">
        <div class="region-col"><div class="region-label">Point A</div><div class="coord-row">
          <div class="coord"><span>X</span><input id="co-x1" value="100" oninput="rebuild()"></div>
          <div class="coord"><span>Y</span><input id="co-y1" value="64" oninput="rebuild()"></div>
          <div class="coord"><span>Z</span><input id="co-z1" value="-20" oninput="rebuild()"></div>
        </div></div>
        <div class="region-col"><div class="region-label">Point B</div><div class="coord-row">
          <div class="coord"><span>X</span><input id="co-x2" value="118" oninput="rebuild()"></div>
          <div class="coord"><span>Y</span><input id="co-y2" value="72" oninput="rebuild()"></div>
          <div class="coord"><span>Z</span><input id="co-z2" value="-2" oninput="rebuild()"></div>
        </div></div>
      </div>
      <div class="result-grid" style="margin-top:14px" id="co-results"></div>
      <div class="hint" style="margin-top:8px">Click any result to copy it.</div>
    <?php }, 'blue'); ?>

    <?php ui_card('Convert to relative', function () { ?>
      <div class="region">
        <div class="region-col"><div class="region-label">Where you are standing</div><div class="coord-row">
          <div class="coord"><span>X</span><input id="co-px" value="100" oninput="rebuild()"></div>
          <div class="coord"><span>Y</span><input id="co-py" value="64" oninput="rebuild()"></div>
          <div class="coord"><span>Z</span><input id="co-pz" value="-20" oninput="rebuild()"></div>
        </div></div>
        <div class="region-col"><div class="region-label">Target</div><div class="coord-row">
          <div class="coord"><span>X</span><input id="co-tx" value="118" oninput="rebuild()"></div>
          <div class="coord"><span>Y</span><input id="co-ty" value="72" oninput="rebuild()"></div>
          <div class="coord"><span>Z</span><input id="co-tz" value="-2" oninput="rebuild()"></div>
        </div></div>
      </div>
      <div class="result-grid" style="margin-top:14px" id="co-rel-results"></div>
    <?php }, 'teal'); ?>
  </div>

  <!-- ═══════════ BUILD PLANNER ═══════════ -->
  <div class="panel" data-panel="planner" <?= $task !== 'planner' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">📝 Build planner</div></div>
    <div class="panel-desc">Rough numbers before you start digging — how much space you need, and roughly how many blocks to gather.</div>

    <?php ui_card('The build', function () { ?>
      <div class="row">
        <?= ui_field('Width (X)', '<input id="pl-w" type="number" min="3" value="11" oninput="rebuild()">') ?>
        <?= ui_field('Length (Z)', '<input id="pl-l" type="number" min="3" value="9" oninput="rebuild()">') ?>
        <?= ui_field('Wall height per floor', '<input id="pl-h" type="number" min="2" value="4" oninput="rebuild()">') ?>
      </div>
      <div class="row">
        <?= ui_field('Floors', '<input id="pl-floors" type="number" min="1" max="10" value="1" oninput="rebuild()">') ?>
        <?= ui_field('Wall thickness', '<input id="pl-thick" type="number" min="1" max="4" value="1" oninput="rebuild()">') ?>
        <?= ui_field('Clearance around it', '<input id="pl-clear" type="number" min="0" value="3" oninput="rebuild()">', 'Space to leave free for landscaping') ?>
      </div>
      <?= ui_field('Roof', ui_select('pl-roof', [
            'pitched' => 'Pitched — stairs, about half the wall height',
            'flat'    => 'Flat — slabs or full blocks',
            'none'    => 'None — open top',
          ], 'pitched', 'onchange="rebuild()"')) ?>
      <?= ui_field('Style', ui_select('pl-style', array_combine(array_keys(paletteStyles()), array_map(fn($s) => $s['label'], paletteStyles())), 'medieval', 'onchange="rebuild()"')) ?>
    <?php }, 'green'); ?>

    <?php ui_card('Plan', function () { ?>
      <div class="result-grid" id="pl-results"></div>
      <hr class="divider">
      <div class="sec-title">Rough material list</div>
      <div id="pl-materials"></div>
      <hr class="divider">
      <div class="sec-title">Suggested palette</div>
      <div id="pl-palette"></div>
      <div class="pill-row" style="margin-top:12px">
        <button class="btn btn-ghost btn-sm" onclick="rebuild()">🎲 Another palette</button>
        <a class="btn btn-ghost btn-sm" href="knowledge.php?t=palette" style="text-decoration:none">Palette builder →</a>
      </div>
      <hr class="divider">
      <div class="sec-title">Clear the site</div>
      <div class="hint" style="margin-bottom:8px">Run this standing at the north-west corner of where the build will go.</div>
      <?php ui_cmdout('out-planner', 'planner', ['copy', 'copysave']); ?>
    <?php }, 'gold'); ?>
  </div>

  </div>
</div>

<script>
var BLOCK_DATA = <?= json_encode(array_map(fn($b) => ['id' => $b['id'], 'name' => $b['name'], 'hex' => $b['hex']], blocksList())) ?>;
var PALETTE_POOLS = <?= json_encode(paletteRolePools()) ?>;
var TASK = <?= json_encode($task) ?>;
</script>
<script src="assets/build.js"></script>
<?php ui_foot(); ?>
