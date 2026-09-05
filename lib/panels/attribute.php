<?php
// ================================================
// lib/panels/attribute.php — /attribute builder
// ------------------------------------------------
// Three things changed here during the 1.21 cycle: attribute ids lost
// their generic./player./horse. prefix, modifiers swapped a uuid+name
// pair for a single id, and the operations were renamed. All three are
// driven by lib/mc.php gates rather than hardcoded here.
// ================================================
?>
<div class="panel" data-panel="attribute" <?= $task !== 'attribute' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">📈 Attribute</div>
    <span class="panel-syntax">/attribute &lt;target&gt; &lt;attribute&gt; …</span></div>
  <div class="panel-desc">
    Change an entity's underlying stats — health, speed, damage, reach. A <b>base</b> value is the
    stat itself; a <b>modifier</b> sits on top of it and can be removed again later.
  </div>

  <?php ui_card('Target and attribute', function () { ?>
    <div class="row">
      <?= ui_field('Entity', '<input id="at-target" value="@s" oninput="rebuild()">', 'One entity only — this command does not accept a group') ?>
      <?= ui_field('Attribute', '<select id="at-attr" onchange="rebuild()">'
            . optionsWithMin(gameAttributes(), 'max_health') . '</select>') ?>
    </div>
    <div class="rule-desc" id="at-attr-desc"></div>
    <div class="mono" id="at-attr-id" style="font-size:11.5px;color:var(--text3);margin-top:6px"></div>
  <?php }, 'blue'); ?>

  <?php ui_card('What to do', function () { ?>
    <div class="pill-row" style="margin-bottom:14px">
      <button class="pill active" data-at-op="get">Read the value</button>
      <button class="pill" data-at-op="base-set">Set the base</button>
      <button class="pill" data-at-op="modifier-add">Add a modifier</button>
      <button class="pill" data-at-op="modifier-remove">Remove a modifier</button>
      <button class="pill" data-at-op="modifier-get">Read a modifier</button>
    </div>

    <div data-at-field="value" hidden>
      <?= ui_field('Value', '<input id="at-value" type="number" step="0.1" value="20" oninput="rebuild()">') ?>
    </div>

    <div data-at-field="modifier" hidden style="margin-top:12px">
      <div class="row">
        <?= ui_field('Modifier id', '<input id="at-mod-id" value="minecraft:my_boost" oninput="rebuild()">',
              'A namespaced id you choose. Use the same one to remove it later.') ?>
        <?= ui_field('Operation', '<select id="at-mod-op" onchange="rebuild()"></select>') ?>
      </div>
      <div id="at-mod-legacy" hidden>
        <?= ui_field('Modifier name', '<input id="at-mod-name" value="my_boost" oninput="rebuild()">',
              'Older versions need a name alongside the UUID') ?>
      </div>
      <div class="rule-desc" id="at-op-desc"></div>
    </div>

    <details class="adv" data-at-field="scale" hidden>
      <summary>Advanced options</summary>
      <div class="adv-body">
        <?= ui_field('Scale', '<input id="at-scale" type="number" step="0.1" value="1" oninput="rebuild()">',
              'Multiplies the number before it is reported. Leave at 1 for the raw value.') ?>
      </div>
    </details>
  <?php }, 'purple'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-attribute', 'attribute'); ?></div>
</div>
