<?php
// ================================================
// lib/panels/bossbar.php — /bossbar builder
// ================================================
?>
<div class="panel" data-panel="bossbar" <?= $task !== 'bossbar' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">📛 Boss bar</div>
    <span class="panel-syntax">/bossbar &lt;action&gt; …</span></div>
  <div class="panel-desc">
    The bar across the top of the screen. Create one, then set its value as things happen — a timer,
    a boss's health, or how much of an objective is done.
  </div>

  <?php ui_card('Action', function () { ?>
    <div class="pill-row" style="margin-bottom:14px">
      <button class="pill active" data-bb-op="add">Create</button>
      <button class="pill" data-bb-op="set">Set</button>
      <button class="pill" data-bb-op="get">Get</button>
      <button class="pill" data-bb-op="remove">Delete</button>
      <button class="pill" data-bb-op="list">List</button>
    </div>

    <div data-bb-field="id">
      <?= ui_field('Boss bar id', '<input id="bb-id" value="minecraft:timer" oninput="rebuild()">',
            'Namespaced, e.g. minecraft:timer. Without a namespace the game adds minecraft: for you.') ?>
    </div>

    <div data-bb-field="set" hidden style="margin-top:12px">
      <?= ui_field('What to set', ui_select('bb-prop', [
            'name'    => 'Title',
            'value'   => 'Current value',
            'max'     => 'Maximum value',
            'color'   => 'Colour',
            'style'   => 'Style',
            'visible' => 'Visible',
            'players' => 'Who can see it',
          ], 'value', 'onchange="rebuild()"')) ?>
    </div>

    <div data-bb-field="get" hidden style="margin-top:12px">
      <?= ui_field('What to read', ui_select('bb-get', [
            'value'   => 'Current value',
            'max'     => 'Maximum value',
            'visible' => 'Whether it is visible',
            'players' => 'How many players see it',
          ], 'value', 'onchange="rebuild()"')) ?>
    </div>
  <?php }, 'red'); ?>

  <div data-bb-field="detail" hidden>
    <?php ui_card('Appearance', function () { ?>
      <div class="row">
        <?= ui_field('Title', '<input id="bb-title" value="Time left" oninput="rebuild()">') ?>
        <?= ui_field('Colour', ui_select('bb-color',
              array_map(fn($c) => $c['label'], gameBossbarColors()), 'yellow', 'onchange="rebuild()"')) ?>
      </div>
      <div class="row">
        <?= ui_field('Style', ui_select('bb-style',
              array_map(fn($s) => $s['label'], gameBossbarStyles()), 'progress', 'onchange="rebuild()"')) ?>
        <?= ui_field('Value', '<input id="bb-value" type="number" min="0" value="30" oninput="rebuild()">') ?>
        <?= ui_field('Maximum', '<input id="bb-max" type="number" min="1" value="60" oninput="rebuild()">') ?>
      </div>
      <div class="row">
        <?= ui_field('Who sees it', '<input id="bb-players" value="@a" oninput="rebuild()">', 'Leave empty to show it to nobody') ?>
        <?= ui_field('Visible', ui_select('bb-visible', ['true' => 'Yes', 'false' => 'No'], 'true', 'onchange="rebuild()"')) ?>
      </div>
    <?php }, 'gold'); ?>

    <?php ui_card('Preview', function () { ?>
      <div class="bb-preview">
        <div class="bb-title" id="bb-preview-title">Time left</div>
        <div class="bb-track" id="bb-preview-track"><div class="bb-fill" id="bb-preview-fill"></div></div>
        <div class="bb-meta" id="bb-preview-meta"></div>
      </div>
    <?php }, 'green'); ?>
  </div>

  <div class="out-wrap"><?php ui_cmdout('out-bossbar', 'bossbar'); ?></div>
</div>
