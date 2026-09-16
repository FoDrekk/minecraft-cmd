<?php
session_start();
require_once __DIR__ . '/items.php';

$defaultTarget = 'nizkbiits';

// Preset kits
$presets = [
  'God Kit' => [
    'weapon'     => 'netherite_sword',
    'helmet'     => 'netherite_helmet',
    'chestplate' => 'netherite_chestplate',
    'leggings'   => 'netherite_leggings',
    'boots'      => 'netherite_boots',
    'offhand'    => 'totem_of_undying',
    'food'       => 'enchanted_golden_apple',
    'food_count' => 16,
    'extra1'     => 'ender_pearl',
    'extra1_count'=> 16,
    'extra2'     => 'golden_apple',
    'extra2_count'=> 32,
    'effects'    => [
      ['strength','300','1'],
      ['resistance','300','1'],
      ['speed','300','2'],
      ['regeneration','300','1'],
    ],
    'xp'         => '100',
  ],
  'Starter Kit' => [
    'weapon'     => 'iron_sword',
    'helmet'     => 'iron_helmet',
    'chestplate' => 'iron_chestplate',
    'leggings'   => 'iron_leggings',
    'boots'      => 'iron_boots',
    'offhand'    => 'shield',
    'food'       => 'cooked_beef',
    'food_count' => 32,
    'extra1'     => 'torch',
    'extra1_count'=> 64,
    'extra2'     => 'iron_pickaxe',
    'extra2_count'=> 1,
    'effects'    => [],
    'xp'         => '0',
  ],
  'PvP Kit' => [
    'weapon'     => 'diamond_sword',
    'helmet'     => 'diamond_helmet',
    'chestplate' => 'diamond_chestplate',
    'leggings'   => 'diamond_leggings',
    'boots'      => 'diamond_boots',
    'offhand'    => 'totem_of_undying',
    'food'       => 'golden_carrot',
    'food_count' => 64,
    'extra1'     => 'ender_pearl',
    'extra1_count'=> 16,
    'extra2'     => 'golden_apple',
    'extra2_count'=> 8,
    'effects'    => [
      ['speed','300','1'],
      ['strength','300','0'],
    ],
    'xp'         => '30',
  ],
];

$effects = [
  'speed','haste','strength','jump_boost','regeneration','resistance',
  'fire_resistance','water_breathing','invisibility','night_vision',
  'health_boost','absorption','saturation','luck','slow_falling',
];

$weapons = array_merge($ITEMS['Weapons'], $ITEMS['Tools']);
$armours  = $ITEMS['Armour'];
$foods    = $ITEMS['Food'];
$allItems = $ITEMS_FLAT;
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kit Builder — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.kit-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:860px){.kit-grid{grid-template-columns:1fr}}
.slot-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.slot-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px;transition:border-color .2s}
.slot-card:hover{border-color:var(--border2)}
.slot-label{font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px;text-transform:uppercase;display:flex;align-items:center;gap:5px}
.effect-row{display:grid;grid-template-columns:1fr 80px 60px 30px;gap:6px;align-items:center;margin-bottom:6px}
.effect-row select,.effect-row input{margin:0}
.del-btn{background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);color:var(--red);border-radius:5px;padding:4px 7px;cursor:pointer;font-size:13px}
.preset-chips{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.preset-chip{padding:6px 14px;border-radius:20px;border:1px solid var(--border2);color:var(--text2);background:var(--bg3);font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;font-family:var(--body)}
.preset-chip:hover{border-color:var(--green);color:var(--green)}
.cmd-list{display:flex;flex-direction:column;gap:6px;margin-top:8px;max-height:420px;overflow-y:auto}
.cmd-item{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px}
.cmd-item-text{font-family:var(--mono);font-size:12px;color:var(--gold);flex:1;word-break:break-all;line-height:1.4}
.cmd-item-copy{flex-shrink:0;padding:4px 10px;font-size:11px;background:rgba(74,222,128,0.08);border:1px solid rgba(74,222,128,0.3);color:var(--green);border-radius:5px;cursor:pointer;font-family:var(--mono)}
.cmd-item-copy:hover{background:rgba(74,222,128,0.18)}
.cmd-num{font-size:10px;font-family:var(--mono);color:var(--text3);flex-shrink:0;min-width:24px}
.kit-summary{background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:14px;display:flex;gap:20px;flex-wrap:wrap}
.kit-summary-stat{text-align:center}
.kit-summary-num{font-size:24px;font-weight:800;font-family:var(--mono);color:var(--green)}
.kit-summary-label{font-size:10px;color:var(--text3);font-family:var(--mono);letter-spacing:.5px}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">🎒 <span>Kit Builder</span></div>
  <div class="page-sub">// Build a full loadout — generates every command at once</div>
</div>

<div class="content">
<div class="kit-grid">

<!-- LEFT: CONFIG -->
<div style="display:flex;flex-direction:column;gap:16px">

  <!-- TARGET -->
  <div class="card">
    <div class="card-header"><span style="color:var(--gold)">●</span> TARGET & PRESET</div>
    <div class="card-body">
      <div class="row" style="margin-bottom:12px">
        <div class="field">
          <label>Player Name / Selector</label>
          <input type="text" id="target" value="<?= $defaultTarget ?>" oninput="buildKit()">
        </div>
      </div>
      <div class="sec-title" style="color:var(--teal)">Quick Presets</div>
      <div class="preset-chips">
        <?php foreach(array_keys($presets) as $name): ?>
        <button class="preset-chip" onclick="loadPreset('<?= $name ?>')"><?= $name ?></button>
        <?php endforeach; ?>
        <button class="preset-chip" style="border-color:var(--red);color:var(--red)" onclick="clearKit()">🗑 Clear</button>
      </div>
    </div>
  </div>

  <!-- GEAR SLOTS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--purple)">●</span> GEAR SLOTS</div>
    <div class="card-body">
      <div class="slot-grid">
        <?php
        $slots = [
          ['weapon',    '⚔️ Main Hand (Weapon)'],
          ['offhand',   '🛡️ Off Hand'],
          ['helmet',    '⛑️ Helmet'],
          ['chestplate','🥋 Chestplate'],
          ['leggings',  '👖 Leggings'],
          ['boots',     '👟 Boots'],
        ];
        foreach($slots as [$id,$label]):
        ?>
        <div class="slot-card">
          <div class="slot-label"><?= $label ?></div>
          <select id="slot_<?= $id ?>" onchange="buildKit()">
            <option value="">— None —</option>
            <?php
            $pool = ($id==='weapon'||$id==='offhand') ? $weapons : $armours;
            if($id==='offhand') { $pool = array_merge($armours, $weapons, $foods, [['totem_of_undying','Totem of Undying'],['shield','Shield'],['arrow','Arrow'],['crossbow','Crossbow']]); }
            foreach($pool as [$val,$name]):
            ?>
            <option value="<?= $val ?>"><?= $name ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- FOOD & EXTRAS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--orange)">●</span> FOOD & EXTRA ITEMS</div>
    <div class="card-body">
      <div class="slot-grid">
        <?php
        $extras = [
          ['food','food_count','🍖 Food','food'],
          ['extra1','extra1_count','📦 Extra Item 1','all'],
          ['extra2','extra2_count','📦 Extra Item 2','all'],
        ];
        foreach($extras as [$id,$countId,$label,$pool]):
        ?>
        <div class="slot-card">
          <div class="slot-label"><?= $label ?></div>
          <select id="slot_<?= $id ?>" onchange="buildKit()" style="margin-bottom:6px">
            <option value="">— None —</option>
            <?php
            $items = $pool==='food' ? $foods : $allItems;
            foreach($items as [$val,$name]):
            ?>
            <option value="<?= $val ?>"><?= $name ?></option>
            <?php endforeach; ?>
          </select>
          <input type="number" id="slot_<?= $countId ?>" value="16" min="1" max="64" placeholder="Qty" oninput="buildKit()" style="font-size:12px;padding:5px 8px">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- EFFECTS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--purple)">●</span> POTION EFFECTS <span style="margin-left:auto"><button class="btn btn-purple btn-sm" onclick="addEffect()">+ Add Effect</button></span></div>
    <div class="card-body">
      <div id="effects-container">
        <!-- dynamically added -->
      </div>
      <p class="hint">💡 Effects akan diapply selepas gear diberikan</p>
    </div>
  </div>

  <!-- XP -->
  <div class="card">
    <div class="card-header"><span style="color:var(--gold)">●</span> BONUS XP</div>
    <div class="card-body">
      <div class="row">
        <div class="field">
          <label>XP Levels to Give</label>
          <input type="number" id="xp_amount" value="0" min="0" oninput="buildKit()">
        </div>
        <div class="field">
          <label>Gamemode</label>
          <select id="gm_mode" onchange="buildKit()">
            <option value="">— Don't change —</option>
            <option value="survival">Survival</option>
            <option value="creative">Creative</option>
            <option value="adventure">Adventure</option>
          </select>
        </div>
      </div>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT: OUTPUT -->
<div style="display:flex;flex-direction:column;gap:16px">
  <div class="card" style="position:sticky;top:76px">
    <div class="card-header"><span style="color:var(--green)">●</span> GENERATED COMMANDS</div>
    <div class="card-body">

      <div class="kit-summary">
        <div class="kit-summary-stat">
          <div class="kit-summary-num" id="stat-cmds">0</div>
          <div class="kit-summary-label">Commands</div>
        </div>
        <div class="kit-summary-stat">
          <div class="kit-summary-num" id="stat-items">0</div>
          <div class="kit-summary-label">Item Slots</div>
        </div>
        <div class="kit-summary-stat">
          <div class="kit-summary-num" id="stat-effects">0</div>
          <div class="kit-summary-label">Effects</div>
        </div>
      </div>

      <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
        <button class="btn btn-green btn-lg" onclick="copyAll()">📋 Copy All Commands</button>
        <button class="btn btn-gold" onclick="copySequential()">⚡ Copy as One-liner</button>
      </div>

      <div class="cmd-list" id="cmd-list">
        <div style="text-align:center;padding:30px;color:var(--text3);font-family:var(--mono)">
          Configure your kit above ↑
        </div>
      </div>

      <div style="margin-top:12px;padding:10px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:8px">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px">💡 CARA GUNA</div>
        <div style="font-size:11px;color:var(--text2);line-height:1.8;font-family:var(--mono)">
          1. Setup kit di sebelah kiri<br>
          2. Click "Copy All", or copy them one at a time<br>
          3. Paste into Minecraft chat or a command block<br>
          4. Guna command block to run them all in one go
        </div>
      </div>
    </div>
  </div>
</div>

</div><!-- /kit-grid -->
</div><!-- /content -->

<script>
const PRESETS = <?= json_encode($presets) ?>;
const EFFECTS_LIST = <?= json_encode($effects) ?>;
let effectCount = 0;

function v(id){ const el=document.getElementById(id); return el?el.value:'' }

function addEffect(effect='speed', duration='60', amplifier='0'){
  effectCount++;
  const id = 'eff_'+effectCount;
  const row = document.createElement('div');
  row.className = 'effect-row';
  row.id = id;
  row.innerHTML = `
    <select onchange="buildKit()">
      ${EFFECTS_LIST.map(e=>`<option value="${e}" ${e===effect?'selected':''}>${e}</option>`).join('')}
    </select>
    <input type="number" value="${duration}" min="1" max="1000000" placeholder="saat" oninput="buildKit()" title="Duration (seconds)">
    <input type="number" value="${amplifier}" min="0" max="9" placeholder="lvl" oninput="buildKit()" title="Amplifier (0=I)">
    <button class="del-btn" onclick="document.getElementById('${id}').remove();buildKit()">✕</button>`;
  document.getElementById('effects-container').appendChild(row);
  buildKit();
  playClick();
}

function loadPreset(name){
  const p = PRESETS[name];
  if(!p) return;
  // clear effects
  document.getElementById('effects-container').innerHTML='';
  effectCount=0;

  const slotMap = ['weapon','offhand','helmet','chestplate','leggings','boots','food','extra1','extra2'];
  slotMap.forEach(s=>{
    const el=document.getElementById('slot_'+s);
    if(el && p[s]!==undefined) el.value=p[s];
  });
  ['food_count','extra1_count','extra2_count'].forEach(s=>{
    const el=document.getElementById('slot_'+s);
    if(el && p[s]!==undefined) el.value=p[s];
  });
  if(p.effects) p.effects.forEach(([e,d,a])=>addEffect(e,d,a));
  if(p.xp !== undefined) document.getElementById('xp_amount').value=p.xp;

  buildKit();
  playSelect();
  showToast('✅ Preset "'+name+'" loaded!');
}

function clearKit(){
  ['weapon','offhand','helmet','chestplate','leggings','boots','food','extra1','extra2'].forEach(s=>{
    const el=document.getElementById('slot_'+s); if(el) el.value='';
  });
  document.getElementById('effects-container').innerHTML='';
  effectCount=0;
  document.getElementById('xp_amount').value='0';
  document.getElementById('gm_mode').value='';
  buildKit();
  playClick();
}

function buildKit(){
  const target = v('target')||'@p';
  const cmds = [];

  // Gamemode first
  const gm = v('gm_mode');
  if(gm) cmds.push(`/gamemode ${gm} ${target}`);

  // Gear slots
  const slots = ['weapon','offhand','helmet','chestplate','leggings','boots'];
  let itemCount = 0;
  slots.forEach(s=>{
    const item = v('slot_'+s);
    if(item){ cmds.push(`/give ${target} ${item} 1`); itemCount++; }
  });

  // Food & extras
  [['food','food_count'],['extra1','extra1_count'],['extra2','extra2_count']].forEach(([s,c])=>{
    const item=v('slot_'+s), qty=v('slot_'+c)||1;
    if(item){ cmds.push(`/give ${target} ${item} ${qty}`); itemCount++; }
  });

  // Effects
  let effCount=0;
  document.querySelectorAll('#effects-container .effect-row').forEach(row=>{
    const [effSel, durIn, ampIn] = row.querySelectorAll('select, input[type=number]');
    if(effSel && durIn && ampIn){
      cmds.push(`/effect give ${target} ${effSel.value} ${durIn.value} ${ampIn.value}`);
      effCount++;
    }
  });

  // XP
  const xp = parseInt(v('xp_amount'));
  if(xp > 0) cmds.push(`/xp add ${target} ${xp}L`);

  // Render
  const list = document.getElementById('cmd-list');
  document.getElementById('stat-cmds').textContent = cmds.length;
  document.getElementById('stat-items').textContent = itemCount;
  document.getElementById('stat-effects').textContent = effCount;

  if(cmds.length===0){
    list.innerHTML='<div style="text-align:center;padding:30px;color:var(--text3);font-family:var(--mono)">Configure your kit above ↑</div>';
    return;
  }

  list.innerHTML = cmds.map((cmd,i)=>`
    <div class="cmd-item">
      <span class="cmd-num">${i+1}.</span>
      <span class="cmd-item-text">${cmd}</span>
      <button class="cmd-item-copy" onclick="copyText('${cmd.replace(/'/g,"\\'")}')">Copy</button>
    </div>`).join('');
}

function copyAll(){
  const items = document.querySelectorAll('.cmd-item-text');
  if(!items.length){ playError(); showToast('⚠️ No commands yet!','var(--red)'); return; }
  const all = Array.from(items).map(el=>el.textContent).join('\n');
  navigator.clipboard.writeText(all).catch(()=>{
    const ta=document.createElement('textarea');ta.value=all;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);
  });
  playCopy();
  showToast(`✅ ${items.length} commands copied!`);
}

function copySequential(){
  const items = document.querySelectorAll('.cmd-item-text');
  if(!items.length){ playError(); return; }
  // Join with && for command blocks
  const all = Array.from(items).map(el=>el.textContent.replace(/^\//,'')).join('\n');
  navigator.clipboard.writeText(all).catch(()=>{});
  playCopy();
  showToast('✅ Copied as sequence!');
}

// Init
buildKit();
</script>
</main>
</body>
</html>
