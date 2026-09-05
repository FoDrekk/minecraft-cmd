<?php
// ================================================
// lib/panels/team.php — /team builder
// ================================================
?>
<div class="panel" data-panel="team" <?= $task !== 'team' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">🏳️ Team</div>
    <span class="panel-syntax">/team &lt;action&gt; …</span></div>
  <div class="panel-desc">
    Teams colour player names, control friendly fire and collision, and decide who can see whose
    nametag. They are also how you give a group a glow colour.
  </div>

  <?php ui_card('Action', function () { ?>
    <div class="pill-row" style="margin-bottom:14px">
      <button class="pill active" data-team-op="add">Create</button>
      <button class="pill" data-team-op="join">Add players</button>
      <button class="pill" data-team-op="leave">Remove players</button>
      <button class="pill" data-team-op="modify">Modify</button>
      <button class="pill" data-team-op="empty">Empty</button>
      <button class="pill" data-team-op="remove">Delete</button>
      <button class="pill" data-team-op="list">List</button>
    </div>

    <div data-team-field="name">
      <?= ui_field('Team id', '<input id="tm-name" value="red" oninput="rebuild()">', 'Internal name — no spaces') ?>
    </div>
    <div data-team-field="display" hidden style="margin-top:12px">
      <?= ui_field('Display name (optional)', '<input id="tm-display" placeholder="Shown in place of the id" oninput="rebuild()">') ?>
    </div>
    <div data-team-field="members" hidden style="margin-top:12px">
      <?= ui_field('Players', '<input id="tm-members" value="@s" oninput="rebuild()">', 'A player name or selector') ?>
    </div>
  <?php }, 'teal'); ?>

  <div data-team-field="modify" hidden>
    <?php ui_card('What to change', function () { ?>
      <?php // No inline onchange: tmRenderValue() rebuilds after it has
            // swapped in the control this option needs. ?>
      <?= ui_field('Setting', '<select id="tm-option">'
            . implode('', array_map(
                fn($k, $o) => '<option value="' . e($k) . '">' . e($o['label']) . '</option>',
                array_keys(gameTeamOptions()), gameTeamOptions()))
            . '</select>') ?>
      <div class="rule-desc" id="tm-option-desc"></div>
      <div style="margin-top:12px" id="tm-value-wrap"></div>
    <?php }, 'purple'); ?>
  </div>

  <div class="out-wrap"><?php ui_cmdout('out-team', 'team'); ?></div>
</div>
