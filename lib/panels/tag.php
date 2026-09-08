<?php
// ================================================
// lib/panels/tag.php — /tag builder
// ================================================
?>
<div class="panel" data-panel="tag" <?= $task !== 'tag' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">🏷️ Tag</div>
    <span class="panel-syntax">/tag &lt;target&gt; &lt;add|remove|list&gt; [name]</span></div>
  <div class="panel-desc">
    Scoreboard tags mark entities so other commands can find them again — for example
    <code>@e[tag=quest_done]</code>. Tags are invisible to players and do not need a scoreboard.
  </div>

  <?php ui_card('Action', function () { ?>
    <div class="pill-row" style="margin-bottom:14px">
      <button class="pill active" data-tag-op="add">Add</button>
      <button class="pill" data-tag-op="remove">Remove</button>
      <button class="pill" data-tag-op="list">List</button>
    </div>

    <?= ui_field('Target', '<input id="tag-target" value="@s" oninput="rebuild()">', 'A player name or selector, e.g. @e[type=cow]') ?>
    <div data-tag-field="name" style="margin-top:12px">
      <?= ui_field('Tag name', '<input id="tag-name" placeholder="e.g. quest_done" oninput="rebuild()">', 'No spaces — use underscores instead') ?>
    </div>
  <?php }, 'teal'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-tag', 'tag'); ?></div>
</div>
