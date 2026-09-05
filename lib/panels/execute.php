<?php
// ================================================
// lib/panels/execute.php — /execute chain builder
// ------------------------------------------------
// The chain is edited as a list of blocks rather than a text field. The
// plain-language summary comes from the existing Command Explainer
// (api.php?action=explain), so the builder and the Doctor always agree.
// ================================================
?>
<div class="panel" data-panel="execute" <?= $task !== 'execute' ? 'hidden' : '' ?>>
  <div class="panel-head"><div class="panel-title">⛓️ Execute</div>
    <span class="panel-syntax">/execute &lt;chain…&gt; run &lt;command&gt;</span></div>
  <div class="panel-desc">
    The most powerful command in the game. Each link changes who the command runs as, where it runs
    from, or whether it runs at all — and the last link is the command itself.
  </div>

  <?php ui_card('Chain', function () { ?>
    <div class="chain" id="ex-chain"></div>
    <div id="ex-empty" class="empty-state" hidden>
      <div class="empty-icon">⛓️</div>
      <div class="empty-title">Nothing in the chain yet</div>
      <div class="empty-sub">Add a link below. A chain normally starts with <b>as</b> or <b>at</b> and ends with <b>run</b>.</div>
    </div>

    <div class="chain-add">
      <div class="chain-add-group">
        <span class="chain-add-label">Who</span>
        <button class="btn btn-ghost btn-sm" data-add="as">as</button>
      </div>
      <div class="chain-add-group">
        <span class="chain-add-label">Where</span>
        <button class="btn btn-ghost btn-sm" data-add="at">at</button>
        <button class="btn btn-ghost btn-sm" data-add="positioned">positioned</button>
        <button class="btn btn-ghost btn-sm" data-add="positioned_as">positioned as</button>
        <button class="btn btn-ghost btn-sm" data-add="in">in</button>
      </div>
      <div class="chain-add-group">
        <span class="chain-add-label">Facing</span>
        <button class="btn btn-ghost btn-sm" data-add="rotated">rotated</button>
        <button class="btn btn-ghost btn-sm" data-add="rotated_as">rotated as</button>
        <button class="btn btn-ghost btn-sm" data-add="facing">facing</button>
        <button class="btn btn-ghost btn-sm" data-add="facing_entity">facing entity</button>
        <button class="btn btn-ghost btn-sm" data-add="anchored">anchored</button>
      </div>
      <div class="chain-add-group">
        <span class="chain-add-label">Condition</span>
        <button class="btn btn-ghost btn-sm" data-add="if_entity">if entity</button>
        <button class="btn btn-ghost btn-sm" data-add="if_block">if block</button>
        <button class="btn btn-ghost btn-sm" data-add="if_score">if score</button>
      </div>
      <div class="chain-add-group">
        <span class="chain-add-label">Result</span>
        <button class="btn btn-ghost btn-sm" data-add="store">store</button>
        <button class="btn btn-green btn-sm" data-add="run">run</button>
      </div>
    </div>
  <?php }, 'green'); ?>

  <?php ui_card('What this does', function () { ?>
    <div id="ex-summary" class="ex-summary"></div>
  <?php }, 'blue'); ?>

  <div class="out-wrap"><?php ui_cmdout('out-execute', 'execute'); ?></div>
</div>
