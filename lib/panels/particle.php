<?php
// ================================================
// lib/panels/particle.php — /particle builder
// ------------------------------------------------
// Particle options moved from space-separated extras to SNBT in 1.20.5,
// so the extra fields below are written differently per version.
// ================================================
?>
<div class="panel" data-panel="particle" <?= $task !== 'particle' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">✨ Particle</div>
    <span class="panel-syntax">/particle &lt;name&gt; [pos] [delta] [speed] [count] [mode] [viewers]</span></div>
  <div class="panel-desc">
    Spawn particles at a position. Delta spreads them out around that point; with a count of 0 it
    becomes a direction to fire them in instead.
  </div>

  <?php ui_card('Particle', function () { ?>
    <input type="text" id="pt-search" placeholder="Search particles — flame, heart, smoke…" autocomplete="off" style="margin-bottom:8px">
    <div class="picker-list" id="pt-list" style="max-height:190px"></div>
    <div class="picked" id="pt-picked" style="margin-top:9px">minecraft:flame</div>

    <div id="pt-options" hidden style="margin-top:12px">
      <label>Particle options</label>
      <div class="hint" id="pt-options-note" style="margin-bottom:8px"></div>
      <div class="row" data-pt-opt="color" hidden>
        <div class="field"><label>Colour</label><input type="color" id="pt-color" value="#ff0000" oninput="rebuild()" style="height:34px;padding:2px"></div>
        <?= ui_field('Size', '<input id="pt-scale" type="number" step="0.1" min="0.01" value="1" oninput="rebuild()">') ?>
      </div>
      <div class="row" data-pt-opt="color2" hidden>
        <div class="field"><label>Fade to</label><input type="color" id="pt-color2" value="#0000ff" oninput="rebuild()" style="height:34px;padding:2px"></div>
      </div>
      <div class="row" data-pt-opt="block" hidden>
        <?= ui_field('Block', '<input id="pt-block" value="stone" oninput="rebuild()">', 'The block whose texture the particle uses') ?>
      </div>
      <div class="row" data-pt-opt="item" hidden>
        <?= ui_field('Item', '<input id="pt-item" value="apple" oninput="rebuild()">', 'The item whose texture the particle uses') ?>
      </div>
      <div class="row" data-pt-opt="number" hidden>
        <?= ui_field('Value', '<input id="pt-number" type="number" step="0.1" value="1" oninput="rebuild()">') ?>
      </div>
    </div>
  <?php }, 'purple'); ?>

  <?php ui_card('Where and how many', function () { ?>
    <div class="region-label">Position</div>
    <div class="coord-row" style="max-width:340px">
      <div class="coord"><span>X</span><input id="pt-x" value="~" oninput="rebuild()"></div>
      <div class="coord"><span>Y</span><input id="pt-y" value="~" oninput="rebuild()"></div>
      <div class="coord"><span>Z</span><input id="pt-z" value="~" oninput="rebuild()"></div>
    </div>

    <div class="region-label" style="margin-top:14px">Spread (delta)</div>
    <div class="coord-row" style="max-width:340px">
      <div class="coord"><span>X</span><input id="pt-dx" type="number" step="0.1" value="0.5" oninput="rebuild()"></div>
      <div class="coord"><span>Y</span><input id="pt-dy" type="number" step="0.1" value="0.5" oninput="rebuild()"></div>
      <div class="coord"><span>Z</span><input id="pt-dz" type="number" step="0.1" value="0.5" oninput="rebuild()"></div>
    </div>

    <div class="row" style="margin-top:14px">
      <?= ui_field('Speed', '<input id="pt-speed" type="number" step="0.01" min="0" value="0" oninput="rebuild()">', 'How fast each particle moves') ?>
      <?= ui_field('Count', '<input id="pt-count" type="number" min="0" value="20" oninput="rebuild()">', '0 turns delta into a direction') ?>
    </div>

    <details class="adv">
      <summary>Advanced options</summary>
      <div class="adv-body">
        <?= ui_field('Display mode', ui_select('pt-mode', [
              'normal' => 'Normal — visible up to 32 blocks away',
              'force'  => 'Force — visible up to 512 blocks, even on low particle settings',
            ], 'normal', 'onchange="rebuild()"')) ?>
        <?= ui_field('Who sees it', '<input id="pt-viewers" placeholder="Leave empty for everyone nearby" oninput="rebuild()">',
              'A player name or selector. Needs a display mode to be set as well.') ?>
      </div>
    </details>
  <?php }, 'blue'); ?>

  <?php ui_card('Preview', function () { ?>
    <div class="particle-preview" id="pt-preview"></div>
    <div class="hint" style="margin-top:8px">A rough impression of colour, count and spread — not the real particle texture.</div>
  <?php }, 'green'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-particle', 'particle'); ?></div>
</div>
