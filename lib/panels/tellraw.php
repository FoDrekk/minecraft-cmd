<?php
// ================================================
// lib/panels/tellraw.php — structured /tellraw builder
// ------------------------------------------------
// Segments are edited as rows; the JSON is generated for you. Raw JSON
// editing is available behind a toggle for people who want it.
// ================================================
?>
<div class="panel" data-panel="tellraw" <?= $task !== 'tellraw' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">💬 Tellraw</div>
    <span class="panel-syntax">/tellraw &lt;target&gt; &lt;text component&gt;</span></div>
  <div class="panel-desc">
    Send a formatted chat message. Build it from segments — each one can have its own colour,
    styling and click or hover behaviour. You never have to write the JSON by hand.
  </div>

  <?php ui_card('Message', function () { ?>
    <div class="row">
      <?= ui_field('Send to', '<input id="tr-target" value="@a" oninput="rebuild()">', 'A player name or a selector like @a') ?>
    </div>
    <div class="pill-row" id="tr-target-chips" style="margin-bottom:14px"></div>

    <label>Segments</label>
    <div class="seg-list" id="tr-segments" style="margin:7px 0 9px"></div>
    <div class="pill-row">
      <button class="btn btn-ghost btn-sm" onclick="trAddSegment()">+ Add segment</button>
      <button class="btn btn-ghost btn-sm" onclick="trAddSegment('selector')">+ Player name</button>
      <button class="btn btn-ghost btn-sm" onclick="trAddSegment('score')">+ Score</button>
    </div>
  <?php }, 'pink'); ?>

  <?php ui_card('Preview', function () { ?>
    <div class="chat-preview" id="tr-preview"></div>
    <div class="hint" style="margin-top:8px">
      Approximate — the game's font and background differ, and selectors and scores show as placeholders.
    </div>
  <?php }, 'green'); ?>

  <?php ui_card('Advanced', function () { ?>
    <label class="check-row"><input type="checkbox" id="tr-raw" onchange="rebuild()"><span>Edit the JSON directly</span></label>
    <div class="hint" style="margin-bottom:9px">
      Turn this on to take over the text component yourself. The segment editor above is ignored while it is on.
    </div>
    <textarea id="tr-raw-json" class="doc-input" spellcheck="false" style="min-height:110px" oninput="rebuild()"></textarea>
  <?php }, 'purple'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-tellraw', 'tellraw'); ?></div>
</div>
