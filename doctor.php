<?php
// ================================================
// doctor.php — Command Doctor and Command Explainer
// ------------------------------------------------
// Paste a command in. The Doctor tells you what is wrong and
// hands back a fixed version; the Explainer says what it does.
// ================================================
require_once __DIR__ . '/lib/ui.php';

$mode = ($_GET['mode'] ?? '') === 'explain' ? 'explain' : 'doctor';
$ver  = mcVersion(mcCurrentVersion());

$examples = [
    ['/give @p diamond_sword{Enchantments:[{id:"minecraft:sharpness",lvl:5}]}', 'Old NBT syntax'],
    ['/effect @p speed 30 1', 'Bedrock syntax on Java'],
    ['/gamerule keepinventory true', 'Wrong capitalisation'],
    ['/execute as @a at @s if entity @e[type=zombie,distance=..10] run say Zombie nearby', 'A full execute chain'],
    ['/fill 0 0 0 200 50 200 stone', 'Too big for one command'],
];

$css = <<<CSS
.doc-page { max-width:920px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }
.doc-tabs { display:flex; gap:6px; margin-bottom:18px }
.doc-tab {
  padding:8px 16px; border-radius:var(--r-sm); border:1px solid var(--border2);
  background:var(--card); color:var(--text3); text-decoration:none; font-size:13.5px;
  font-weight:600; transition:all .13s;
}
.doc-tab:hover { border-color:var(--border3); color:var(--text2) }
.doc-tab.active { background:rgba(93,190,122,0.09); border-color:rgba(93,190,122,0.3); color:var(--green) }
.doc-input {
  width:100%; min-height:96px; resize:vertical; font-family:var(--mono); font-size:13.5px;
  line-height:1.6; background:var(--bg2); border:1px solid var(--border2);
  border-radius:var(--r-sm); color:var(--gold); padding:13px 15px;
}
.doc-input:focus { outline:none; border-color:rgba(93,190,122,0.4) }
.doc-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:11px }
.example-row { display:flex; gap:6px; flex-wrap:wrap; margin-top:14px }
.example {
  font-size:11.5px; padding:4px 10px; border-radius:20px; cursor:pointer;
  border:1px solid var(--border2); background:transparent; color:var(--text3);
  font-family:var(--body); transition:all .12s;
}
.example:hover { border-color:var(--border3); color:var(--text2) }

.finding { border:1px solid var(--border2); border-radius:var(--r); overflow:hidden; margin-bottom:11px; background:var(--card) }
.finding-head { display:flex; align-items:center; gap:9px; padding:11px 15px; border-bottom:1px solid var(--border) }
.finding-icon { font-size:15px; flex-shrink:0 }
.finding-title { font-size:14px; font-weight:700; color:var(--text) }
.finding-body { padding:12px 15px; display:flex; flex-direction:column; gap:11px }
.finding-part { font-size:13px; line-height:1.6 }
.finding-label {
  font-size:9.5px; font-family:var(--mono); letter-spacing:1.6px; text-transform:uppercase;
  color:var(--text3); font-weight:700; display:block; margin-bottom:3px;
}
.finding.is-error { border-color:rgba(224,85,85,0.3) }
.finding.is-error .finding-head { background:rgba(224,85,85,0.06) }
.finding.is-error .finding-title { color:var(--red) }
.finding.is-warn { border-color:rgba(232,169,74,0.3) }
.finding.is-warn .finding-head { background:rgba(232,169,74,0.05) }
.finding.is-warn .finding-title { color:var(--gold) }
.finding.is-info .finding-head { background:rgba(91,156,246,0.05) }
.finding.is-info .finding-title { color:var(--blue) }
.ver-tag {
  margin-left:auto; font-size:9.5px; font-family:var(--mono); letter-spacing:1px;
  text-transform:uppercase; color:var(--purple); border:1px solid rgba(155,120,240,0.3);
  background:rgba(155,120,240,0.07); padding:2px 7px; border-radius:4px; flex-shrink:0;
}
.all-clear { border:1px solid rgba(93,190,122,0.3); background:rgba(93,190,122,0.05);
  border-radius:var(--r); padding:18px; text-align:center }
.all-clear-title { font-size:15px; font-weight:700; color:var(--green); margin-bottom:4px }

.step-list { display:flex; flex-direction:column; gap:0 }
.exp-step { display:grid; grid-template-columns:150px 1fr; gap:14px; padding:11px 0; border-bottom:1px solid var(--border) }
.exp-step:last-child { border-bottom:none }
.exp-step-label { font-family:var(--mono); font-size:12px; color:var(--green); font-weight:700; word-break:break-word }
.exp-step-text { font-size:13.5px; color:var(--text2); line-height:1.6 }
@media(max-width:620px){ .exp-step{grid-template-columns:1fr; gap:3px} }
.exp-summary { font-size:15px; color:var(--text); line-height:1.6; margin-bottom:14px; font-weight:500 }
CSS;

ui_head($mode === 'explain' ? 'Command Explainer' : 'Command Doctor', '', $css);
?>
<div class="doc-page">
  <?php ui_page_header('🩺', 'Command Doctor',
        'Paste a command that is not working. You get the problem, the reason, and a fixed version to copy.'); ?>

  <div class="doc-tabs">
    <a class="doc-tab <?= $mode === 'doctor' ? 'active' : '' ?>" href="doctor.php">🩺 Fix a command</a>
    <a class="doc-tab <?= $mode === 'explain' ? 'active' : '' ?>" href="doctor.php?mode=explain">💬 Explain a command</a>
  </div>

  <?php ui_card('Your command', function () use ($examples, $mode) { ?>
    <textarea class="doc-input" id="doc-input" spellcheck="false"
      placeholder="<?= $mode === 'explain'
        ? 'e.g. /execute as @a at @s if entity @e[type=zombie,distance=..10] run say Zombie nearby'
        : 'Paste the command that is not working…' ?>"></textarea>
    <div class="doc-actions">
      <button class="btn btn-green" id="doc-run"><?= $mode === 'explain' ? '💬 Explain it' : '🩺 Check it' ?></button>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('doc-input').value='';run()">Clear</button>
      <span class="muted" style="font-size:12px">Checked against the version selected in the top-right.</span>
    </div>
    <div class="example-row">
      <span class="muted" style="font-size:11.5px;align-self:center">Try:</span>
      <?php foreach ($examples as [$cmd, $label]): ?>
        <button class="example" data-example="<?= e($cmd) ?>"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>
  <?php }, 'red'); ?>

  <div id="doc-output" style="margin-top:18px"></div>
</div>

<script>
var MODE = <?= json_encode($mode) ?>;
</script>
<script src="assets/doctor.js"></script>
<?php ui_foot(); ?>
