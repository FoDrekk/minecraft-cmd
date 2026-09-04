<?php
// ================================================
// farms.php — Farm Lab
// ------------------------------------------------
// Step-by-step farm guides. Every guide states the edition and the
// version its mechanics were checked against, because farm designs
// go stale far faster than command syntax does.
// ================================================
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/data/farms.php';

$farms = farmsAll();
$open  = $_GET['farm'] ?? '';
if ($open !== '' && !isset($farms[$open])) $open = '';

$css = <<<CSS
.farm-page { max-width:1000px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }
.tier-title { font-size:11px; font-family:var(--mono); letter-spacing:2px; text-transform:uppercase;
  color:var(--text3); font-weight:700; margin:24px 0 10px }
.farm-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(288px,1fr)); gap:11px }
.farm-card { display:block; background:var(--card); border:1px solid var(--border); border-radius:var(--r);
  padding:15px; text-decoration:none; transition:all .14s }
.farm-card:hover { border-color:var(--border3); background:var(--card2); transform:translateY(-1px) }
.farm-icon { font-size:24px; margin-bottom:8px }
.farm-name { font-size:15px; font-weight:700; color:var(--text); margin-bottom:6px }
.farm-meta { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; align-items:center }
.farm-out { font-size:12.5px; color:var(--text3); line-height:1.55 }
.ed-tag { font-size:9.5px; font-family:var(--mono); letter-spacing:1px; text-transform:uppercase;
  border:1px solid var(--border2); color:var(--text3); padding:2px 6px; border-radius:4px }

.guide { background:var(--card); border:1px solid var(--border2); border-radius:var(--r-lg); padding:24px }
.guide h1 { font-size:25px; letter-spacing:-.4px; margin-bottom:9px }
.guide-meta { display:flex; gap:7px; flex-wrap:wrap; align-items:center; margin-bottom:16px }
.guide-lead { font-size:14.5px; color:var(--text2); line-height:1.7 }
.guide-sec { margin-top:26px }
.guide-sec-title { font-size:11px; font-family:var(--mono); letter-spacing:2px; text-transform:uppercase;
  color:var(--green); font-weight:700; margin-bottom:11px; display:flex; align-items:center; gap:9px }
.guide-sec-title::after { content:''; flex:1; height:1px; background:var(--border) }
.guide-body { font-size:13.5px; color:var(--text2); line-height:1.75 }
.step { display:grid; grid-template-columns:30px 1fr; gap:13px; padding:12px 0; border-bottom:1px solid var(--border) }
.step:last-child { border-bottom:none }
.step-num { width:26px; height:26px; border-radius:50%; background:rgba(93,190,122,0.12);
  border:1px solid rgba(93,190,122,0.3); color:var(--green); display:flex; align-items:center;
  justify-content:center; font-family:var(--mono); font-size:12px; font-weight:700 }
.step-title { font-size:14px; font-weight:700; color:var(--text); margin-bottom:3px }
.step-text { font-size:13.5px; color:var(--text2); line-height:1.65 }
.trouble { border:1px solid var(--border); border-radius:var(--r); margin-bottom:9px; overflow:hidden; background:var(--bg2) }
.trouble summary { padding:11px 15px; cursor:pointer; list-style:none; font-size:13.5px;
  font-weight:600; color:var(--gold); display:flex; align-items:center; gap:9px }
.trouble summary::-webkit-details-marker { display:none }
.trouble summary::before { content:'▸'; transition:transform .15s; color:var(--text3) }
.trouble[open] summary::before { transform:rotate(90deg) }
.trouble-body { padding:0 15px 14px 33px }
.trouble-col-title { font-size:9.5px; font-family:var(--mono); letter-spacing:1.5px; text-transform:uppercase;
  color:var(--text3); font-weight:700; margin:9px 0 4px }
.trouble-body ul { margin:0; padding-left:18px }
.trouble-body li { font-size:13px; color:var(--text2); line-height:1.6; margin-bottom:3px }
.check-progress { font-size:12px; font-family:var(--mono); color:var(--text3); margin-top:9px }
CSS;

ui_head($open ? $farms[$open]['title'] : 'Farm Lab', '', $css);
?>
<div class="farm-page">

<?php if (!$open): ?>
  <?php ui_page_header('🌾', 'Farm Lab',
      'Farms worth building, with what to place, why it works, and what to check when it does not.'); ?>

  <?php foreach (farmsTiers() as $tier):
      $inTier = array_filter($farms, fn($f) => $f['tier'] === $tier);
      if (!$inTier) continue; ?>
    <div class="tier-title"><?= e($tier) ?></div>
    <div class="farm-grid">
      <?php foreach ($inTier as $id => $f): ?>
        <a class="farm-card" href="farms.php?farm=<?= e($id) ?>">
          <div class="farm-icon"><?= $f['icon'] ?></div>
          <div class="farm-name"><?= e($f['title']) ?></div>
          <div class="farm-meta">
            <?= ui_difficulty($f['difficulty']) ?>
            <span class="ed-tag"><?= e($f['edition']) ?></span>
            <span class="ed-tag"><?= e($f['checked']) ?></span>
          </div>
          <div class="farm-out"><?= e($f['output']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

<?php else: $f = $farms[$open]; ?>
  <a class="btn btn-ghost btn-sm" href="farms.php" style="text-decoration:none;margin-bottom:14px;display:inline-block">← All farms</a>

  <div class="guide">
    <div class="farm-icon" style="font-size:32px"><?= $f['icon'] ?></div>
    <h1><?= e($f['title']) ?></h1>
    <div class="guide-meta">
      <?= ui_difficulty($f['difficulty']) ?>
      <?= ui_badge($f['edition'], $f['edition'] === 'Java' ? 'blue' : ($f['edition'] === 'Both' ? 'green' : 'purple')) ?>
      <span class="ed-tag">Checked against <?= e($f['checked']) ?></span>
    </div>
    <div class="guide-lead"><?= e($f['output']) ?></div>

    <?php if (!empty($f['version_note'])): ?>
      <div style="margin-top:16px"><?= ui_warn('<b>Version note.</b> ' . e($f['version_note'])) ?></div>
    <?php endif; ?>

    <div class="guide-sec">
      <div class="guide-sec-title">Materials</div>
      <?php ui_checklist('farm_' . $open, $f['materials']); ?>
      <div class="check-progress" id="check-progress"></div>
    </div>

    <div class="guide-sec">
      <div class="guide-sec-title">Before you start</div>
      <div class="guide-body"><?= e($f['prep']) ?></div>
    </div>

    <div class="guide-sec">
      <div class="guide-sec-title">Build it</div>
      <?php foreach ($f['steps'] as $n => [$title, $text]): ?>
        <div class="step">
          <div class="step-num"><?= $n + 1 ?></div>
          <div>
            <div class="step-title"><?= e($title) ?></div>
            <div class="step-text"><?= e($text) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="guide-sec">
      <div class="guide-sec-title">How it works</div>
      <div class="guide-body"><?= e($f['how']) ?></div>
    </div>

    <div class="guide-sec">
      <div class="guide-sec-title">Testing it</div>
      <div class="guide-body"><?= e($f['testing']) ?></div>
    </div>

    <div class="guide-sec">
      <div class="guide-sec-title">Troubleshooting</div>
      <?php foreach ($f['troubles'] as [$problem, $causes, $fixes]): ?>
        <details class="trouble">
          <summary><?= e($problem) ?></summary>
          <div class="trouble-body">
            <div class="trouble-col-title">Likely causes</div>
            <ul><?php foreach ($causes as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul>
            <div class="trouble-col-title">What to do</div>
            <ul><?php foreach ($fixes as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
          </div>
        </details>
      <?php endforeach; ?>
    </div>

    <div class="guide-sec">
      <div class="guide-sec-title">Tools that help</div>
      <div class="pill-row">
        <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="build.php?t=area">Area calculator →</a>
        <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="build.php?t=clear">Clear the site →</a>
        <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="commands.php?t=gamerule">Gamerules →</a>
        <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="knowledge.php?t=materials">Material library →</a>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>

<script>
var FARM_ID = <?= json_encode($open) ?>;
</script>
<script src="assets/farms.js"></script>
<?php ui_foot(); ?>
