<?php
// ================================================
// lib/panels/playsound.php — /playsound builder
// ================================================
?>
<div class="panel" data-panel="playsound" <?= $task !== 'playsound' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">🔊 Playsound</div>
    <span class="panel-syntax">/playsound &lt;sound&gt; &lt;source&gt; &lt;target&gt; [pos] [volume] [pitch] [min]</span></div>
  <div class="panel-desc">
    Play any sound in the game to chosen players. Volume sets how far it carries rather than how loud
    it is — at 1 that is roughly 16 blocks.
  </div>

  <?php ui_card('Sound', function () { ?>
    <input type="text" id="ps-search" placeholder="Search sounds — bell, thunder, level up…" autocomplete="off" style="margin-bottom:8px">
    <div class="picker-list" id="ps-list" style="max-height:200px"></div>
    <div class="picked" id="ps-picked" style="margin-top:9px">minecraft:block.note_block.pling</div>
    <div class="hint" style="margin-top:7px">
      Any sound id works — type one in the box below if it is not in the list.
    </div>
    <?= ui_field('Custom sound id', '<input id="ps-custom" placeholder="e.g. entity.villager.trade" oninput="rebuild()">') ?>
  <?php }, 'orange'); ?>

  <?php ui_card('Who hears it', function () { ?>
    <div class="row">
      <?= ui_field('Target', '<input id="ps-target" value="@a" oninput="rebuild()">', 'Who the sound is played to') ?>
      <?= ui_field('Sound category', ui_select('ps-source',
            array_map(fn($l) => $l, gameSoundSources()), 'master', 'onchange="rebuild()"'),
            'Which volume slider in the options menu controls it') ?>
    </div>

    <details class="adv">
      <summary>Advanced options</summary>
      <div class="adv-body">
        <label class="check-row" style="margin-bottom:10px">
          <input type="checkbox" id="ps-usepos" onchange="rebuild()"><span>Play it at a fixed position</span>
        </label>
        <div class="coord-row" style="max-width:340px" id="ps-pos-row" hidden>
          <div class="coord"><span>X</span><input id="ps-x" value="~" oninput="rebuild()"></div>
          <div class="coord"><span>Y</span><input id="ps-y" value="~" oninput="rebuild()"></div>
          <div class="coord"><span>Z</span><input id="ps-z" value="~" oninput="rebuild()"></div>
        </div>
        <div class="hint" style="margin:7px 0 12px">
          Leave this off and the sound plays wherever each target is standing.
        </div>
        <div class="row">
          <?= ui_field('Volume', '<input id="ps-volume" type="number" step="0.1" min="0" value="1" oninput="rebuild()">', 'Range, not loudness. 1 ≈ 16 blocks.') ?>
          <?= ui_field('Pitch', '<input id="ps-pitch" type="number" step="0.1" min="0" max="2" value="1" oninput="rebuild()">', '0.5 is an octave down, 2 an octave up') ?>
          <?= ui_field('Minimum volume', '<input id="ps-min" type="number" step="0.1" min="0" max="1" value="0" oninput="rebuild()">', 'What players outside the range still hear') ?>
        </div>
      </div>
    </details>
  <?php }, 'blue'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-playsound', 'playsound'); ?></div>
</div>
