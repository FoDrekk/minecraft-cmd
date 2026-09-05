<?php
// ================================================
// lib/panels/data.php — /data builder
// ================================================
?>
<div class="panel" data-panel="data" <?= $task !== 'data' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">🗂️ Data</div>
    <span class="panel-syntax">/data &lt;get|merge|modify|remove&gt; &lt;target&gt; …</span></div>
  <div class="panel-desc">
    Read and edit the NBT behind an entity, a block or a storage. Powerful and unforgiving — read a
    value first to see the shape of what you are about to change.
  </div>

  <?php ui_card('Target', function () { ?>
    <div class="pill-row" style="margin-bottom:14px">
      <button class="pill active" data-dt-target="entity">Entity</button>
      <button class="pill" data-dt-target="block">Block</button>
      <button class="pill" data-dt-target="storage">Storage</button>
    </div>

    <div data-dt-field="entity">
      <?= ui_field('Entity', '<input id="dt-entity" value="@s" oninput="rebuild()">', 'One entity only') ?>
    </div>
    <div data-dt-field="block" hidden>
      <div class="coord-row" style="max-width:340px">
        <div class="coord"><span>X</span><input id="dt-x" value="~" oninput="rebuild()"></div>
        <div class="coord"><span>Y</span><input id="dt-y" value="~" oninput="rebuild()"></div>
        <div class="coord"><span>Z</span><input id="dt-z" value="~" oninput="rebuild()"></div>
      </div>
    </div>
    <div data-dt-field="storage" hidden>
      <?= ui_field('Storage id', '<input id="dt-storage" value="minecraft:my_data" oninput="rebuild()">', 'Namespaced, e.g. mypack:counters') ?>
    </div>
  <?php }, 'gold'); ?>

  <?php ui_card('Operation', function () { ?>
    <div class="pill-row" style="margin-bottom:14px">
      <button class="pill active" data-dt-op="get">Get</button>
      <button class="pill" data-dt-op="modify">Modify</button>
      <button class="pill" data-dt-op="merge">Merge</button>
      <button class="pill" data-dt-op="remove">Remove</button>
    </div>

    <div data-dt-field="path">
      <?= ui_field('Path', '<input id="dt-path" value="Health" oninput="rebuild()">',
            'Dot-separated, e.g. Inventory[0].id or Attributes[0].Base') ?>
      <div class="pill-row" id="dt-path-presets" style="margin-top:8px"></div>
    </div>

    <div data-dt-field="modify" hidden style="margin-top:12px">
      <div class="row">
        <?= ui_field('How', ui_select('dt-mode', [
              'set'     => 'Set — replace what is there',
              'merge'   => 'Merge — combine with what is there',
              'append'  => 'Append — add to the end of a list',
              'prepend' => 'Prepend — add to the start of a list',
              'insert'  => 'Insert — add at a position in a list',
            ], 'set', 'onchange="rebuild()"')) ?>
        <div data-dt-field="index" hidden>
          <?= ui_field('Index', '<input id="dt-index" type="number" value="0" oninput="rebuild()">') ?>
        </div>
      </div>
      <div class="pill-row" style="margin-bottom:12px">
        <button class="pill active" data-dt-source="value">A value I type</button>
        <button class="pill" data-dt-source="from">Copy from somewhere else</button>
      </div>
      <div data-dt-field="value">
        <?= ui_field('Value (SNBT)', '<input id="dt-value" value="20.0f" oninput="rebuild()">',
              'A number, a quoted string, or a compound like {Name:"Bob"}') ?>
      </div>
      <div data-dt-field="from" hidden>
        <div class="row">
          <?= ui_field('Copy from', ui_select('dt-from-type', ['entity' => 'Entity', 'block' => 'Block', 'storage' => 'Storage'], 'entity', 'onchange="rebuild()"')) ?>
          <?= ui_field('Which', '<input id="dt-from-target" value="@p" oninput="rebuild()">') ?>
          <?= ui_field('Path', '<input id="dt-from-path" value="Health" oninput="rebuild()">') ?>
        </div>
      </div>
    </div>

    <div data-dt-field="merge" hidden style="margin-top:12px">
      <?= ui_field('NBT to merge (SNBT)', '<input id="dt-merge" value="{Invulnerable:1b}" oninput="rebuild()">',
            'A compound in braces — only the keys you list are changed') ?>
    </div>

    <details class="adv" data-dt-field="scale" style="margin-top:4px">
      <summary>Advanced options</summary>
      <div class="adv-body">
        <?= ui_field('Scale', '<input id="dt-scale" type="number" step="0.1" value="1" oninput="rebuild()">',
              'Multiplies the number before it is reported') ?>
      </div>
    </details>
  <?php }, 'blue'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-data', 'data'); ?></div>
</div>
