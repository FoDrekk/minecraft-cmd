<?php
// ================================================
// commands.php — Task → Options → Preview → Copy
// ------------------------------------------------
// One page, one task at a time. Every builder reads the global
// version selector, so the generated syntax always matches the
// version the player is actually running.
// ================================================
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/data/game.php';
require_once __DIR__ . '/items.php';

$ver  = mcCurrentVersion();
$task = $_GET['t'] ?? 'give';

$TASKS = [
    'Player' => [
        'give'       => ['📦', 'Give item',   'green'],
        'clear'      => ['🗑️', 'Clear items',  'blue'],
        'gamemode'   => ['🎮', 'Gamemode',    'teal'],
        'effect'     => ['⚗️', 'Effect',      'purple'],
        'experience' => ['⭐', 'Experience',  'gold'],
        'teleport'   => ['🌀', 'Teleport',    'blue'],
    ],
    'World' => [
        'time'       => ['🌤️', 'Time & weather', 'orange'],
        'gamerule'   => ['⚙️', 'Gamerule',      'purple'],
        'difficulty' => ['☠️', 'Difficulty',    'red'],
        'locate'     => ['🧭', 'Locate',        'teal'],
    ],
    'Entities' => [
        'summon'     => ['👾', 'Summon', 'red'],
        'kill'       => ['💀', 'Kill',   'red'],
    ],
];
$valid = [];
foreach ($TASKS as $g) $valid = array_merge($valid, array_keys($g));
if (!in_array($task, $valid, true)) $task = 'give';

/** <option> list with data-min so the client can re-filter on version change. */
function optionsWithMin(array $rows, string $selected = '', bool $grouped = true): string
{
    $out = ''; $groups = [];
    foreach ($rows as $id => $r) {
        $g = $grouped ? ($r['group'] ?? '') : '';
        $groups[$g][$id] = $r;
    }
    foreach ($groups as $g => $items) {
        if ($g !== '') $out .= '<optgroup label="' . e($g) . '">';
        foreach ($items as $id => $r) {
            $out .= '<option value="' . e((string)$id) . '" data-min="' . (int)($r['min'] ?? 0) . '"'
                 . ((string)$id === $selected ? ' selected' : '') . '>' . e($r['label']) . '</option>';
        }
        if ($g !== '') $out .= '</optgroup>';
    }
    return $out;
}

$css = <<<CSS
.cmd-layout { display:grid; grid-template-columns:214px 1fr; gap:18px; max-width:1240px; margin:0 auto; padding:18px 24px 56px; position:relative; z-index:1 }
@media(max-width:900px){ .cmd-layout{grid-template-columns:1fr} }

.task-rail { position:sticky; top:70px; align-self:start }
.task-group-label { font-size:9.5px; font-family:var(--mono); letter-spacing:1.8px; text-transform:uppercase; color:var(--text3); font-weight:700; margin:14px 0 5px 8px }
.task-group-label:first-child { margin-top:0 }
.task-link {
  display:flex; align-items:center; gap:9px; padding:7px 11px; margin-bottom:2px;
  border-radius:var(--r-sm); text-decoration:none; color:var(--text3);
  font-size:13px; border:1px solid transparent; transition:all .13s;
}
.task-link:hover { background:rgba(255,255,255,0.04); color:var(--text2) }
.task-link.active { background:rgba(93,190,122,0.09); border-color:rgba(93,190,122,0.22); color:var(--green); font-weight:600 }

.panel-head { display:flex; align-items:baseline; gap:10px; flex-wrap:wrap; margin-bottom:4px }
.panel-title { font-size:20px; font-weight:800; letter-spacing:-.3px }
.panel-syntax { font-family:var(--mono); font-size:12px; color:var(--text3) }
.panel-desc { font-size:13px; color:var(--text3); margin-bottom:16px; line-height:1.55 }

.target-bar { display:flex; align-items:center; gap:9px; flex-wrap:wrap; margin-bottom:12px }
.target-bar input { width:170px }

details.adv { margin-top:14px; border-top:1px solid var(--border); padding-top:12px }
details.adv > summary {
  cursor:pointer; font-size:12px; font-family:var(--mono); letter-spacing:1.2px;
  text-transform:uppercase; color:var(--text3); font-weight:600; list-style:none;
  display:flex; align-items:center; gap:6px; user-select:none;
}
details.adv > summary::-webkit-details-marker { display:none }
details.adv > summary::before { content:'▸'; transition:transform .15s }
details.adv[open] > summary::before { transform:rotate(90deg) }
details.adv > summary:hover { color:var(--text2) }
details.adv .adv-body { padding-top:12px }

.picker { position:relative }
.picker-list {
  max-height:230px; overflow-y:auto; border:1px solid var(--border);
  border-radius:var(--r-sm); background:var(--bg2); margin-top:6px;
}
.picker-item { padding:6px 11px; font-size:12.5px; cursor:pointer; display:flex; justify-content:space-between; gap:10px }
.picker-item:hover, .picker-item.sel { background:rgba(93,190,122,0.09) }
.picker-item code { font-family:var(--mono); font-size:11px; color:var(--text3) }
.picked {
  display:flex; align-items:center; gap:8px; padding:7px 11px; margin-bottom:8px;
  background:rgba(93,190,122,0.06); border:1px solid rgba(93,190,122,0.2);
  border-radius:var(--r-sm); font-family:var(--mono); font-size:12.5px; color:var(--green);
}
.rowlist { display:flex; flex-direction:column; gap:6px }
.rowitem { display:grid; grid-template-columns:1fr 86px 32px; gap:6px; align-items:center }
.rowitem button { background:transparent; border:1px solid var(--border2); color:var(--text3); border-radius:var(--r-xs); cursor:pointer; height:31px }
.rowitem button:hover { border-color:var(--red); color:var(--red) }
.rule-desc { font-size:12px; color:var(--text3); line-height:1.55; margin-top:8px }
.out-wrap { margin-top:18px }
CSS;

ui_head('Commands', '', $css);
?>
<div class="cmd-layout">

  <!-- TASK RAIL -->
  <div class="task-rail">
    <?php foreach ($TASKS as $group => $items): ?>
      <div class="task-group-label"><?= e($group) ?></div>
      <?php foreach ($items as $id => [$icon, $label, $accent]): ?>
        <a class="task-link <?= $task === $id ? 'active' : '' ?>" href="commands.php?t=<?= e($id) ?>"><?= $icon ?> <?= e($label) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <div>
  <?php ob_start(); ?>

  <!-- ═══════════ GIVE ═══════════ -->
  <div class="panel" data-panel="give" <?= $task !== 'give' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">📦 Give item</div>
      <span class="panel-syntax">/give &lt;target&gt; &lt;item&gt; [count]</span></div>
    <div class="panel-desc">Pick an item, set the amount, and open Advanced options to name it, add lore or enchant it.</div>

    <?php ui_card('Item', function () { ?>
      <div class="picked" id="give-picked">minecraft:diamond_sword</div>
      <div class="picker">
        <input type="text" id="give-search" placeholder="Search items — sword, pickaxe, elytra…" autocomplete="off">
        <div class="picker-list" id="give-list"></div>
      </div>
      <div class="row" style="margin-top:12px">
        <?= ui_field('Give to', '<input id="give-target" value="@p" oninput="rebuild()">', 'A player name or a selector like @a') ?>
        <?= ui_field('Amount', '<input id="give-count" type="number" min="1" max="6400" value="1" oninput="rebuild()">') ?>
      </div>
      <div class="pill-row" id="give-target-chips"></div>

      <details class="adv">
        <summary>Advanced options</summary>
        <div class="adv-body">
          <div class="row">
            <?= ui_field('Custom name', '<input id="give-name" placeholder="Leave empty for the normal name" oninput="rebuild()">') ?>
            <?= ui_field('Name colour', ui_select('give-name-color', array_merge(['' => 'Default'], array_combine(array_keys(gameColors()), array_map(fn($k) => ucwords(str_replace('_', ' ', $k)), array_keys(gameColors())))), '', 'onchange="rebuild()"')) ?>
          </div>
          <div class="inline" style="margin-bottom:12px">
            <label class="check-row"><input type="checkbox" id="give-unbreakable" onchange="rebuild()"><span>Unbreakable</span></label>
            <label class="check-row"><input type="checkbox" id="give-hide" onchange="rebuild()"><span>Hide extra tooltip</span></label>
          </div>

          <label>Lore lines</label>
          <div class="rowlist" id="give-lore" style="margin:6px 0 8px"></div>
          <button class="btn btn-ghost btn-sm" onclick="addLore()">+ Add lore line</button>

          <label style="margin-top:14px;display:block">Enchantments</label>
          <div class="rowlist" id="give-ench" style="margin:6px 0 8px"></div>
          <button class="btn btn-ghost btn-sm" onclick="addEnch()">+ Add enchantment</button>
        </div>
      </details>
    <?php }, 'green'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-give', 'give'); ?></div>
  </div>

  <!-- ═══════════ CLEAR ═══════════ -->
  <div class="panel" data-panel="clear" <?= $task !== 'clear' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🗑️ Clear items</div>
      <span class="panel-syntax">/clear &lt;target&gt; [item] [max]</span></div>
    <div class="panel-desc">Remove items from an inventory. With no item chosen it wipes everything the target is carrying.</div>

    <?php ui_card('What to clear', function () { ?>
      <div class="row">
        <?= ui_field('From', '<input id="clr-target" value="@p" oninput="rebuild()">') ?>
        <?= ui_field('Item (optional)', '<input id="clr-item" placeholder="Leave empty to clear everything" oninput="rebuild()">', 'e.g. dirt or minecraft:cobblestone') ?>
      </div>
      <div class="row">
        <?= ui_field('Max to remove', '<input id="clr-max" type="number" min="-1" value="-1" oninput="rebuild()">', '-1 means every match. 0 only counts them without removing anything.') ?>
      </div>
    <?php }, 'blue'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-clear', 'clear'); ?></div>
  </div>

  <!-- ═══════════ GAMEMODE ═══════════ -->
  <div class="panel" data-panel="gamemode" <?= $task !== 'gamemode' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🎮 Gamemode</div>
      <span class="panel-syntax">/gamemode &lt;mode&gt; [target]</span></div>
    <div class="panel-desc">The fastest command in the app. Pick a mode, pick who, copy.</div>

    <?php ui_card('Mode', function () { ?>
      <div class="pill-row" id="gm-modes" style="margin-bottom:14px">
        <button class="pill active" data-gm="survival">🗡 Survival</button>
        <button class="pill" data-gm="creative">🧱 Creative</button>
        <button class="pill" data-gm="adventure">🧭 Adventure</button>
        <button class="pill" data-gm="spectator">👁 Spectator</button>
      </div>
      <?= ui_field('Who', '<input id="gm-target" value="@s" oninput="rebuild()">', 'Leave as @s for yourself, or @a for everybody') ?>
      <div class="pill-row" id="gm-target-chips" style="margin-top:8px"></div>
    <?php }, 'teal'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-gamemode', 'gamemode'); ?></div>
  </div>

  <!-- ═══════════ EFFECT ═══════════ -->
  <div class="panel" data-panel="effect" <?= $task !== 'effect' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">⚗️ Effect</div>
      <span class="panel-syntax">/effect give &lt;target&gt; &lt;effect&gt; [seconds] [amplifier]</span></div>
    <div class="panel-desc">Apply or clear a potion effect. Remember the game counts levels from zero — level II is amplifier 1, and this builder does that conversion for you.</div>

    <?php ui_card('Effect', function () use ($ver) { ?>
      <div class="pill-row" style="margin-bottom:14px">
        <button class="pill active" data-eff-mode="give">Give</button>
        <button class="pill" data-eff-mode="clear">Clear</button>
      </div>
      <div class="row">
        <?= ui_field('Target', '<input id="eff-target" value="@p" oninput="rebuild()">') ?>
        <?= ui_field('Effect', '<select id="eff-type" onchange="rebuild()">' . optionsWithMin(gameEffects(), 'speed') . '</select>') ?>
      </div>
      <div class="row" data-eff-give>
        <?= ui_field('Duration (seconds)', '<input id="eff-dur" type="number" min="0" max="1000000" value="30" oninput="rebuild()">') ?>
        <?= ui_field('Level', '<input id="eff-level" type="number" min="1" max="256" value="1" oninput="rebuild()">', 'Level 1 = no amplifier. Level 3 = amplifier 2.') ?>
      </div>
      <div class="inline" data-eff-give>
        <label class="check-row"><input type="checkbox" id="eff-infinite" onchange="rebuild()"><span>Infinite duration</span></label>
        <label class="check-row"><input type="checkbox" id="eff-hide" onchange="rebuild()"><span>Hide particles</span></label>
      </div>
    <?php }, 'purple'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-effect', 'effect'); ?></div>
  </div>

  <!-- ═══════════ EXPERIENCE ═══════════ -->
  <div class="panel" data-panel="experience" <?= $task !== 'experience' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">⭐ Experience</div>
      <span class="panel-syntax">/experience &lt;add|set|query&gt; &lt;target&gt; …</span></div>
    <div class="panel-desc">Levels are what you spend on enchanting. Points are the raw XP inside the current level.</div>

    <?php ui_card('Experience', function () { ?>
      <div class="pill-row" style="margin-bottom:14px">
        <button class="pill active" data-xp-op="add">Add</button>
        <button class="pill" data-xp-op="set">Set</button>
        <button class="pill" data-xp-op="query">Query</button>
      </div>
      <div class="row">
        <?= ui_field('Target', '<input id="xp-target" value="@p" oninput="rebuild()">') ?>
        <?= ui_field('Amount', '<input id="xp-amount" type="number" value="30" oninput="rebuild()">') ?>
      </div>
      <div class="pill-row">
        <button class="pill active" data-xp-unit="levels">Levels</button>
        <button class="pill" data-xp-unit="points">Points</button>
      </div>
    <?php }, 'gold'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-experience', 'experience'); ?></div>
  </div>

  <!-- ═══════════ TELEPORT ═══════════ -->
  <div class="panel" data-panel="teleport" <?= $task !== 'teleport' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🌀 Teleport</div>
      <span class="panel-syntax">/tp &lt;target&gt; &lt;destination&gt;</span></div>
    <div class="panel-desc">Use ~ for “relative to where I am”. <span class="mono">~ ~10 ~</span> means ten blocks straight up.</div>

    <?php ui_card('Destination', function () { ?>
      <div class="pill-row" style="margin-bottom:14px">
        <button class="pill active" data-tp-mode="coords">Coordinates</button>
        <button class="pill" data-tp-mode="entity">Another player</button>
      </div>
      <?= ui_field('Who to move', '<input id="tp-target" value="@s" oninput="rebuild()">') ?>

      <div data-tp-coords style="margin-top:12px">
        <div class="row">
          <?= ui_field('X', '<input id="tp-x" value="~" oninput="rebuild()">') ?>
          <?= ui_field('Y', '<input id="tp-y" value="~" oninput="rebuild()">') ?>
          <?= ui_field('Z', '<input id="tp-z" value="~" oninput="rebuild()">') ?>
        </div>
        <div class="pill-row">
          <button class="btn btn-ghost btn-sm" onclick="tpPreset('~','~10','~')">10 blocks up</button>
          <button class="btn btn-ghost btn-sm" onclick="tpPreset('~','~-5','~')">5 blocks down</button>
          <button class="btn btn-ghost btn-sm" onclick="tpPreset('0','100','0')">World origin</button>
          <a class="btn btn-ghost btn-sm" href="build.php?t=coords" style="text-decoration:none">Coordinate helper →</a>
        </div>
        <details class="adv">
          <summary>Advanced options</summary>
          <div class="adv-body">
            <div class="row">
              <?= ui_field('Yaw (facing)', '<input id="tp-yaw" placeholder="optional, -180 to 180" oninput="rebuild()">', '-180 = north, -90 = east, 0 = south, 90 = west') ?>
              <?= ui_field('Pitch (up/down)', '<input id="tp-pitch" placeholder="optional, -90 to 90" oninput="rebuild()">', '-90 = straight up, 90 = straight down') ?>
            </div>
            <?= ui_field('Or face these coordinates', '<input id="tp-facing" placeholder="e.g. 100 64 -20" oninput="rebuild()">', 'Overrides yaw and pitch') ?>
          </div>
        </details>
      </div>

      <div data-tp-entity hidden style="margin-top:12px">
        <?= ui_field('Teleport to', '<input id="tp-dest" value="@p" oninput="rebuild()">', 'A player name or selector') ?>
      </div>
    <?php }, 'blue'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-teleport', 'teleport'); ?></div>
  </div>

  <!-- ═══════════ TIME & WEATHER ═══════════ -->
  <div class="panel" data-panel="time" <?= $task !== 'time' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🌤️ Time &amp; weather</div>
      <span class="panel-syntax">/time · /weather</span></div>
    <div class="panel-desc">A Minecraft day is 24000 ticks. Day starts at 1000, noon is 6000, night is 13000 and midnight is 18000.</div>

    <div class="grid-2">
    <?php ui_card('Time', function () { ?>
      <div class="pill-row" style="margin-bottom:12px">
        <button class="pill active" data-time-op="set">Set</button>
        <button class="pill" data-time-op="add">Add</button>
        <button class="pill" data-time-op="query">Query</button>
      </div>
      <div class="pill-row" style="margin-bottom:12px">
        <button class="btn btn-ghost btn-sm" onclick="setTime(1000)">☀️ Day</button>
        <button class="btn btn-ghost btn-sm" onclick="setTime(6000)">🌞 Noon</button>
        <button class="btn btn-ghost btn-sm" onclick="setTime(13000)">🌙 Night</button>
        <button class="btn btn-ghost btn-sm" onclick="setTime(18000)">🌑 Midnight</button>
      </div>
      <?= ui_field('Ticks', '<input id="time-ticks" type="number" value="1000" oninput="rebuild()">') ?>
      <div class="out-wrap"><?php ui_cmdout('out-time', 'time', ['copy', 'copysave']); ?></div>
    <?php }, 'orange'); ?>

    <?php ui_card('Weather', function () { ?>
      <div class="pill-row" style="margin-bottom:12px">
        <button class="pill active" data-weather="clear">☀️ Clear</button>
        <button class="pill" data-weather="rain">🌧 Rain</button>
        <button class="pill" data-weather="thunder">⛈ Thunder</button>
      </div>
      <?= ui_field('Duration (seconds, optional)', '<input id="weather-dur" type="number" min="0" placeholder="Leave empty for a random length" oninput="rebuild()">') ?>
      <div class="out-wrap"><?php ui_cmdout('out-weather', 'weather', ['copy', 'copysave']); ?></div>
    <?php }, 'blue'); ?>
    </div>
  </div>

  <!-- ═══════════ GAMERULE ═══════════ -->
  <div class="panel" data-panel="gamerule" <?= $task !== 'gamerule' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">⚙️ Gamerule</div>
      <span class="panel-syntax">/gamerule &lt;rule&gt; [value]</span></div>
    <div class="panel-desc">World settings. Search for what you want to change — the list only shows rules your selected version has.</div>

    <?php ui_card('Rule', function () { ?>
      <input type="text" id="rule-search" placeholder="Search rules — keep inventory, mob griefing, tick speed…" autocomplete="off" style="margin-bottom:8px">
      <select id="rule-select" size="10" onchange="rebuild()" style="height:auto;padding:4px">
        <?php
        $byGroup = [];
        foreach (gameRules() as $id => $r) $byGroup[$r['group']][$id] = $r;
        foreach ($byGroup as $g => $rules) {
            echo '<optgroup label="' . e($g) . '">';
            foreach ($rules as $id => $r) {
                echo '<option value="' . e($id) . '" data-min="' . (int)($r['min'] ?? 0) . '"'
                   . ' data-type="' . e($r['type']) . '" data-default="' . e($r['default']) . '"'
                   . ' data-desc="' . e($r['desc'] ?? '') . '"'
                   . ($id === 'keepInventory' ? ' selected' : '') . '>' . e($r['label']) . '</option>';
            }
            echo '</optgroup>';
        }
        ?>
      </select>
      <div class="rule-desc" id="rule-desc"></div>
      <div style="margin-top:12px" id="rule-value-wrap"></div>
    <?php }, 'purple'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-gamerule', 'gamerule'); ?></div>
  </div>

  <!-- ═══════════ DIFFICULTY ═══════════ -->
  <div class="panel" data-panel="difficulty" <?= $task !== 'difficulty' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">☠️ Difficulty</div>
      <span class="panel-syntax">/difficulty &lt;level&gt;</span></div>
    <div class="panel-desc">Peaceful removes hostile mobs entirely and refills hunger. Hard lets zombies break doors and starvation can kill you.</div>

    <?php ui_card('Level', function () { ?>
      <div class="pill-row">
        <button class="pill" data-diff="peaceful">🕊 Peaceful</button>
        <button class="pill" data-diff="easy">🙂 Easy</button>
        <button class="pill active" data-diff="normal">😐 Normal</button>
        <button class="pill" data-diff="hard">💀 Hard</button>
      </div>
    <?php }, 'red'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-difficulty', 'difficulty'); ?></div>
  </div>

  <!-- ═══════════ LOCATE ═══════════ -->
  <div class="panel" data-panel="locate" <?= $task !== 'locate' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">🧭 Locate</div>
      <span class="panel-syntax">/locate &lt;structure|biome&gt; &lt;id&gt;</span></div>
    <div class="panel-desc">Reports the coordinates of the nearest match. It searches from where the command runs, so run it in the dimension you want to search.</div>

    <?php ui_card('What to find', function () { ?>
      <div class="pill-row" style="margin-bottom:14px">
        <button class="pill active" data-locate="structure">Structure</button>
        <button class="pill" data-locate="biome">Biome</button>
      </div>
      <div data-locate-structure>
        <?= ui_field('Structure', '<select id="loc-structure" onchange="rebuild()">' . optionsWithMin(gameStructures(), 'stronghold') . '</select>') ?>
      </div>
      <div data-locate-biome hidden>
        <?= ui_field('Biome', '<select id="loc-biome" onchange="rebuild()">' . optionsWithMin(gameBiomes(), 'deep_dark') . '</select>') ?>
      </div>
    <?php }, 'teal'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-locate', 'locate'); ?></div>
  </div>

  <!-- ═══════════ SUMMON ═══════════ -->
  <div class="panel" data-panel="summon" <?= $task !== 'summon' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">👾 Summon</div>
      <span class="panel-syntax">/summon &lt;entity&gt; [x y z] [nbt]</span></div>
    <div class="panel-desc">Spawns an entity where you point it. The presets add the entity data most people are actually after.</div>

    <?php ui_card('Entity', function () { ?>
      <?= ui_field('Entity', '<select id="sum-entity" onchange="rebuild()">' . optionsWithMin(gameEntities(), 'zombie') . '</select>') ?>
      <div class="row" style="margin-top:12px">
        <?= ui_field('X', '<input id="sum-x" value="~" oninput="rebuild()">') ?>
        <?= ui_field('Y', '<input id="sum-y" value="~" oninput="rebuild()">') ?>
        <?= ui_field('Z', '<input id="sum-z" value="~" oninput="rebuild()">') ?>
      </div>
      <details class="adv">
        <summary>Advanced options</summary>
        <div class="adv-body">
          <?= ui_field('Custom name', '<input id="sum-name" placeholder="Shown above the entity" oninput="rebuild()">') ?>
          <div class="inline" style="margin-top:10px">
            <label class="check-row"><input type="checkbox" id="sum-namevisible" onchange="rebuild()"><span>Name always visible</span></label>
            <label class="check-row"><input type="checkbox" id="sum-noai" onchange="rebuild()"><span>No AI (frozen)</span></label>
            <label class="check-row"><input type="checkbox" id="sum-silent" onchange="rebuild()"><span>Silent</span></label>
            <label class="check-row"><input type="checkbox" id="sum-invuln" onchange="rebuild()"><span>Invulnerable</span></label>
            <label class="check-row"><input type="checkbox" id="sum-nogravity" onchange="rebuild()"><span>No gravity</span></label>
            <label class="check-row"><input type="checkbox" id="sum-glow" onchange="rebuild()"><span>Glowing</span></label>
            <label class="check-row"><input type="checkbox" id="sum-baby" onchange="rebuild()"><span>Baby</span></label>
            <label class="check-row"><input type="checkbox" id="sum-charged" onchange="rebuild()"><span>Charged (creeper only)</span></label>
          </div>
        </div>
      </details>
    <?php }, 'red'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-summon', 'summon'); ?></div>
  </div>

  <!-- ═══════════ KILL ═══════════ -->
  <div class="panel" data-panel="kill" <?= $task !== 'kill' ? 'hidden' : '' ?>>
    <div class="panel-head"><div class="panel-title">💀 Kill</div>
      <span class="panel-syntax">/kill &lt;target&gt;</span></div>
    <div class="panel-desc">Removes entities immediately. Read the target twice before you run it — this cannot be undone.</div>

    <?php ui_card('Target', function () { ?>
      <div class="pill-row" style="margin-bottom:14px">
        <button class="pill active" data-kill="@e[type=item]">Dropped items</button>
        <button class="pill" data-kill="@e[type=!player]">All mobs</button>
        <button class="pill" data-kill="@e[type=zombie,distance=..30]">Zombies nearby</button>
        <button class="pill" data-kill="@s">Yourself</button>
      </div>
      <?= ui_field('Selector', '<input id="kill-target" value="@e[type=item]" oninput="rebuild()">', 'Anything matching this selector will be removed') ?>
    <?php }, 'red'); ?>
    <div class="out-wrap"><?php ui_cmdout('out-kill', 'kill'); ?></div>
  </div>

  <?php echo ob_get_clean(); ?>
  </div>
</div>

<script>
var ITEMS = <?= json_encode(array_values(array_map(fn($i) => ['id' => $i[0], 'name' => $i[1]], $ITEMS_FLAT))) ?>;
<?php
// Highest level each enchantment reaches through normal play, so the
// builder can flag levels that only commands can produce.
$ENCH_MAX = [];
foreach ($ENCHANTS as $list) {
    foreach ($list as [$eid, $emax]) $ENCH_MAX[$eid] = max($ENCH_MAX[$eid] ?? 0, $emax);
}
ksort($ENCH_MAX);
?>
var ENCHANTS = <?= json_encode($ENCH_MAX) ?>;
var TASK = <?= json_encode($task) ?>;
</script>
<script src="assets/commands.js"></script>
<?php ui_foot(); ?>
