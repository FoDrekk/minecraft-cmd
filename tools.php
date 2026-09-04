<?php
// ================================================
// tools.php — every generator in one place
// ================================================
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/registry.php';

$groups = [
    'command' => ['Commands',       'Task-based builders for the commands players use most.'],
    'build'   => ['Build tools',    'Areas, coordinates and the block-placing commands.'],
    'doctor'  => ['Fix & explain',  'Paste a command in and find out what is wrong with it.'],
    'tool'    => ['Item & display', 'Custom items, text, fireworks and scoreboards.'],
    'knowledge' => ['Knowledge',    'Blocks, palettes, techniques and what to build next.'],
    'farm'    => ['Farms',          'Step-by-step farm guides.'],
];

$byCat = [];
foreach (registry_tools() as $t) $byCat[$t['cat']][] = $t;

$css = <<<CSS
.tools-page { max-width:1240px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }
.tools-filter { display:flex; gap:10px; align-items:center; margin-bottom:20px; flex-wrap:wrap }
.tools-filter input {
  flex:1; min-width:220px; max-width:420px; background:var(--card);
  border:1px solid var(--border2); border-radius:var(--r-sm);
  padding:9px 13px; color:var(--text); font-family:var(--body); font-size:13.5px;
}
.tools-filter input:focus { outline:none; border-color:rgba(93,190,122,0.4) }
.tools-group { margin-bottom:28px }
.tools-group-head { margin-bottom:10px }
.tools-group-title { font-size:15px; font-weight:700; color:var(--text) }
.tools-group-desc { font-size:12.5px; color:var(--text3); margin-top:2px }
.tools-group[hidden] { display:none }
CSS;

ui_head('Tools', '', $css);
?>
<div class="tools-page">
  <?php ui_page_header('🧰', 'Tools', 'Every generator in Minecraft CMD. All of them follow the version selector in the top-right.'); ?>

  <div class="tools-filter">
    <input type="text" id="tool-filter" placeholder="Filter tools…" autocomplete="off">
    <span class="muted" style="font-size:12px" id="tool-count"></span>
  </div>

  <?php foreach ($groups as $cat => [$title, $desc]):
      if (empty($byCat[$cat])) continue; ?>
  <div class="tools-group" data-group>
    <div class="tools-group-head">
      <div class="tools-group-title"><?= e($title) ?></div>
      <div class="tools-group-desc"><?= e($desc) ?></div>
    </div>
    <div class="tile-grid">
      <?php foreach ($byCat[$cat] as $t): ?>
        <div data-tool data-search="<?= e(strtolower($t['title'] . ' ' . $t['desc'] . ' ' . $t['keywords'])) ?>">
          <?= ui_tile($t['href'], $t['icon'], $t['title'], $t['desc'], $t['accent']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
(function () {
  var input = document.getElementById('tool-filter');
  var count = document.getElementById('tool-count');
  var tools = Array.prototype.slice.call(document.querySelectorAll('[data-tool]'));
  var groups = Array.prototype.slice.call(document.querySelectorAll('[data-group]'));

  function apply() {
    var q = input.value.trim().toLowerCase();
    var shown = 0;
    tools.forEach(function (t) {
      var hit = !q || t.dataset.search.indexOf(q) !== -1;
      t.hidden = !hit;
      if (hit) shown++;
    });
    groups.forEach(function (g) {
      g.hidden = !g.querySelector('[data-tool]:not([hidden])');
    });
    count.textContent = q ? shown + ' of ' + tools.length : tools.length + ' tools';
  }

  input.addEventListener('input', apply);
  apply();
})();
</script>
<?php ui_foot(); ?>
