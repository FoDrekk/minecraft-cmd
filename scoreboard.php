<?php
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Scoreboard Generator — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.sb-layout{display:grid;grid-template-columns:1fr 360px;gap:20px}
@media(max-width:920px){.sb-layout{grid-template-columns:1fr}}
.mode-tabs{display:flex;gap:4px;margin-bottom:14px;flex-wrap:wrap}
.mtab{padding:6px 14px;font-size:12px;font-weight:700;background:var(--bg3);border:1px solid var(--border2);color:var(--text3);border-radius:var(--radius-sm);cursor:pointer;transition:all .15s;font-family:var(--body)}
.mtab:hover{border-color:var(--blue);color:var(--blue)}
.mtab.active{background:rgba(79,156,249,0.1);border-color:rgba(79,156,249,0.35);color:var(--blue)}
.cmd-list{display:flex;flex-direction:column;gap:6px;max-height:500px;overflow-y:auto}
.cmd-row{background:var(--bg3);border:1px solid var(--border2);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px}
.cmd-row-text{font-family:var(--mono);font-size:12px;color:var(--gold);flex:1;word-break:break-all;line-height:1.5}
.cmd-row-copy{padding:4px 9px;font-size:10px;background:rgba(61,220,132,0.08);border:1px solid rgba(61,220,132,0.3);color:var(--green);border-radius:5px;cursor:pointer;font-family:var(--mono);flex-shrink:0}
.cmd-row-copy:hover{background:rgba(61,220,132,0.18)}
.preset-list{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.preset-btn{padding:8px 10px;font-size:11px;font-weight:600;background:var(--bg3);border:1px solid var(--border2);color:var(--text2);border-radius:var(--radius-sm);cursor:pointer;transition:all .15s;text-align:left;font-family:var(--body)}
.preset-btn:hover{border-color:var(--blue);color:var(--blue)}
.crit-info{background:rgba(79,156,249,0.05);border:1px solid rgba(79,156,249,0.15);border-radius:8px;padding:10px 12px;margin-top:8px}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">📊 <span>Scoreboard Generator</span></div>
  <div class="page-sub">// Create, remove and display scoreboard objectives</div>
</div>

<div class="content">
<div class="sb-layout">

<!-- LEFT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- MODE TABS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--blue)">●</span> MODE</div>
    <div class="card-body">
      <div class="mode-tabs">
        <button class="mtab active" onclick="setMode('create',this)">➕ Create Objective</button>
        <button class="mtab" onclick="setMode('display',this)">📺 Display</button>
        <button class="mtab" onclick="setMode('score',this)">🎯 Set Score</button>
        <button class="mtab" onclick="setMode('remove',this)">🗑️ Remove</button>
        <button class="mtab" onclick="setMode('team',this)">👥 Teams</button>
      </div>

      <!-- CREATE -->
      <div id="mode-create">
        <div class="row">
          <div class="field">
            <label>Objective Name</label>
            <input type="text" id="obj-name" placeholder="e.g. kills" oninput="buildSb()" maxlength="16">
          </div>
          <div class="field">
            <label>Criteria</label>
            <select id="obj-criteria" onchange="buildSb()">
              <optgroup label="Common">
                <option value="dummy">dummy — Custom (most common)</option>
                <option value="health">health — Player HP</option>
                <option value="level">level — XP Level</option>
                <option value="food">food — Hunger</option>
                <option value="air">air — Air/Oxygen</option>
                <option value="armor">armor — Armor points</option>
              </optgroup>
              <optgroup label="Kills">
                <option value="playerKillCount">playerKillCount — Player kills</option>
                <option value="totalKillCount">totalKillCount — All kills</option>
                <option value="deathCount">deathCount — Deaths</option>
              </optgroup>
              <optgroup label="Stats">
                <option value="minecraft.mined:minecraft.diamond_ore">Diamonds mined</option>
                <option value="minecraft.killed:minecraft.zombie">Zombies killed</option>
                <option value="minecraft.custom:minecraft.play_time">Play time</option>
                <option value="minecraft.custom:minecraft.jump">Jumps</option>
                <option value="minecraft.custom:minecraft.walk_one_cm">Distance walked</option>
              </optgroup>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Display Name (optional)</label>
          <input type="text" id="obj-display" placeholder='e.g. {"text":"Kills","color":"red"}' oninput="buildSb()">
        </div>
        <p class="hint">💡 Nama objective max 16 aksara, tiada spasi</p>
      </div>

      <!-- DISPLAY -->
      <div id="mode-display" style="display:none">
        <div class="row">
          <div class="field">
            <label>Objective Name</label>
            <input type="text" id="disp-obj" placeholder="e.g. kills" oninput="buildSb()">
          </div>
          <div class="field">
            <label>Slot</label>
            <select id="disp-slot" onchange="buildSb()">
              <option value="sidebar">sidebar — Sidebar kanan</option>
              <option value="list">list — Tab list</option>
              <option value="belowName">belowName — Bawah nama player</option>
              <option value="sidebar.team.red">sidebar.team.red</option>
              <option value="sidebar.team.blue">sidebar.team.blue</option>
              <option value="sidebar.team.green">sidebar.team.green</option>
              <option value="sidebar.team.yellow">sidebar.team.yellow</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label>Title (JSON, optional)</label>
            <input type="text" id="disp-title" placeholder='{"text":"Leaderboard","color":"gold"}' oninput="buildSb()">
          </div>
        </div>
      </div>

      <!-- SCORE -->
      <div id="mode-score" style="display:none">
        <div class="row">
          <div class="field">
            <label>Target</label>
            <input type="text" id="score-target" value="<?= $defaultTarget ?>" oninput="buildSb()">
          </div>
          <div class="field">
            <label>Objective</label>
            <input type="text" id="score-obj" placeholder="e.g. kills" oninput="buildSb()">
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label>Operation</label>
            <select id="score-op" onchange="buildSb()">
              <option value="set">Set (exact value)</option>
              <option value="add">Add (tambah)</option>
              <option value="remove">Remove (tolak)</option>
              <option value="reset">Reset</option>
              <option value="enable">Enable (trigger)</option>
            </select>
          </div>
          <div class="field">
            <label>Value</label>
            <input type="number" id="score-val" value="0" oninput="buildSb()">
          </div>
        </div>
      </div>

      <!-- REMOVE -->
      <div id="mode-remove" style="display:none">
        <div class="row">
          <div class="field">
            <label>Objective Name</label>
            <input type="text" id="rm-obj" placeholder="e.g. kills" oninput="buildSb()">
          </div>
        </div>
        <div style="background:rgba(240,96,96,0.05);border:1px solid rgba(240,96,96,0.15);border-radius:8px;padding:10px;margin-top:4px">
          <p class="hint" style="color:var(--red)">⚠️ This deletes the objective and every score attached to it.</p>
        </div>
      </div>

      <!-- TEAMS -->
      <div id="mode-team" style="display:none">
        <div class="row">
          <div class="field">
            <label>Team Action</label>
            <select id="team-action" onchange="buildSb()">
              <option value="add">Create Team</option>
              <option value="remove">Remove Team</option>
              <option value="join">Add Player to Team</option>
              <option value="leave">Remove Player from Team</option>
              <option value="color">Set Team Color</option>
              <option value="option_friendly">Friendly Fire</option>
            </select>
          </div>
          <div class="field">
            <label>Team Name</label>
            <input type="text" id="team-name" placeholder="e.g. RedTeam" oninput="buildSb()">
          </div>
        </div>
        <div class="row">
          <div class="field" id="team-player-wrap">
            <label>Player (for join/leave)</label>
            <input type="text" id="team-player" value="<?= $defaultTarget ?>" oninput="buildSb()">
          </div>
          <div class="field" id="team-color-wrap">
            <label>Color (for color)</label>
            <select id="team-color" onchange="buildSb()">
              <option value="red">red</option><option value="blue">blue</option>
              <option value="green">green</option><option value="yellow">yellow</option>
              <option value="aqua">aqua</option><option value="white">white</option>
              <option value="black">black</option><option value="gold">gold</option>
              <option value="gray">gray</option><option value="dark_red">dark_red</option>
              <option value="dark_blue">dark_blue</option><option value="dark_green">dark_green</option>
              <option value="dark_aqua">dark_aqua</option><option value="dark_purple">dark_purple</option>
              <option value="light_purple">light_purple</option>
            </select>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- PRESETS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--teal)">●</span> COMMON PRESETS</div>
    <div class="card-body">
      <div class="preset-list">
        <button class="preset-btn" onclick="loadSbPreset('killboard')">🗡️ Kill Leaderboard</button>
        <button class="preset-btn" onclick="loadSbPreset('health')">❤️ Health Display</button>
        <button class="preset-btn" onclick="loadSbPreset('pvp')">⚔️ PvP Setup</button>
        <button class="preset-btn" onclick="loadSbPreset('minigame')">🎮 Minigame Score</button>
        <button class="preset-btn" onclick="loadSbPreset('level')">⭐ XP Level Board</button>
        <button class="preset-btn" onclick="loadSbPreset('teams')">👥 Red vs Blue</button>
      </div>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT -->
<div>
  <div class="card" style="position:sticky;top:70px">
    <div class="card-header"><span style="color:var(--gold)">●</span> GENERATED COMMANDS</div>
    <div class="card-body">
      <div class="cmd-list" id="sb-cmd-list">
        <div style="color:var(--text3);font-family:var(--mono);font-size:12px;padding:12px">// Fill in details above</div>
      </div>
      <div style="display:flex;gap:7px;margin-top:12px;flex-wrap:wrap">
        <button class="btn btn-green" onclick="copyAllSb()">📋 Copy All</button>
      </div>
      <div class="crit-info">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px">ℹ️ QUICK REFERENCE</div>
        <div style="font-size:11px;color:var(--text2);line-height:1.9;font-family:var(--mono)">
          <b style="color:var(--blue)">/scoreboard objectives add</b> — create objective<br>
          <b style="color:var(--blue)">/scoreboard objectives setdisplay</b> — show on screen<br>
          <b style="color:var(--blue)">/scoreboard players set</b> — set score value<br>
          <b style="color:var(--blue)">/scoreboard players add</b> — increment score<br>
          <b style="color:var(--blue)">/scoreboard objectives remove</b> — delete objective
        </div>
      </div>
    </div>
  </div>
</div>

</div><!-- /sb-layout -->
</div>

<script>
let curMode = 'create';
let generatedCmds = [];

function v(id){ return document.getElementById(id)?.value?.trim()||'' }

function setMode(mode, btn) {
  curMode = mode;
  document.querySelectorAll('.mtab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  ['create','display','score','remove','team'].forEach(m=>{
    document.getElementById('mode-'+m).style.display = m===mode?'':'none';
  });
  buildSb(); playClick();
}

function buildSb() {
  const cmds = [];
  if(curMode==='create') {
    const name = v('obj-name'), crit = v('obj-criteria'), disp = v('obj-display');
    if(name) {
      cmds.push(`/scoreboard objectives add ${name} ${crit}${disp?' "'+disp+'"':''}`);
      cmds.push(`/scoreboard objectives setdisplay sidebar ${name}`);
    }
  } else if(curMode==='display') {
    const obj = v('disp-obj'), slot = v('disp-slot'), title = v('disp-title');
    if(obj) {
      if(title) cmds.push(`/scoreboard objectives modify ${obj} displayname ${title}`);
      cmds.push(`/scoreboard objectives setdisplay ${slot} ${obj}`);
    }
  } else if(curMode==='score') {
    const t=v('score-target'), obj=v('score-obj'), op=v('score-op'), val=v('score-val');
    if(t&&obj) {
      if(op==='reset') cmds.push(`/scoreboard players reset ${t} ${obj}`);
      else if(op==='enable') cmds.push(`/scoreboard players enable ${t} ${obj}`);
      else cmds.push(`/scoreboard players ${op} ${t} ${obj} ${val}`);
    }
  } else if(curMode==='remove') {
    const obj = v('rm-obj');
    if(obj) {
      cmds.push(`/scoreboard objectives setdisplay sidebar`);
      cmds.push(`/scoreboard objectives remove ${obj}`);
    }
  } else if(curMode==='team') {
    const action=v('team-action'), name=v('team-name'), player=v('team-player'), color=v('team-color');
    if(name) {
      if(action==='add') cmds.push(`/team add ${name}`);
      else if(action==='remove') cmds.push(`/team remove ${name}`);
      else if(action==='join') cmds.push(`/team join ${name} ${player}`);
      else if(action==='leave') cmds.push(`/team leave ${player}`);
      else if(action==='color') cmds.push(`/team modify ${name} color ${color}`);
      else if(action==='option_friendly') {
        cmds.push(`/team modify ${name} friendlyFire false`);
        cmds.push(`/team modify ${name} seeFriendlyInvisibles true`);
      }
    }
  }

  generatedCmds = cmds;
  const list = document.getElementById('sb-cmd-list');
  if(!cmds.length) {
    list.innerHTML = '<div style="color:var(--text3);font-family:var(--mono);font-size:12px;padding:12px">// Fill in details above</div>';
    return;
  }
  list.innerHTML = cmds.map((cmd,i) => `
    <div class="cmd-row">
      <span style="font-size:10px;font-family:var(--mono);color:var(--text3);flex-shrink:0">${i+1}.</span>
      <span class="cmd-row-text">${cmd}</span>
      <button class="cmd-row-copy" onclick="copyText('${cmd.replace(/'/g,"\\'").replace(/"/g,'&quot;')}')">Copy</button>
    </div>`).join('');
}

function copyAllSb() {
  if(!generatedCmds.length) { playError(); return; }
  navigator.clipboard.writeText(generatedCmds.join('\n')).catch(()=>{});
  playCopy(); showToast(`✅ ${generatedCmds.length} commands copied!`);
}

const SB_PRESETS = {
  killboard:[
    {mode:'create',obj:'kills',crit:'playerKillCount',disp:'{"text":"☠ Kill Count","color":"red"}'},
    {mode:'display',obj:'kills',slot:'sidebar'},
  ],
  health:[
    {mode:'create',obj:'health',crit:'health',disp:'{"text":"❤ Health","color":"red"}'},
    {mode:'display',obj:'health',slot:'belowName'},
  ],
  pvp:[
    {mode:'create',obj:'pvp_kills',crit:'playerKillCount',disp:'{"text":"⚔ PvP Kills","color":"gold"}'},
    {mode:'create',obj:'pvp_deaths',crit:'deathCount',disp:'{"text":"💀 Deaths","color":"gray"}'},
    {mode:'display',obj:'pvp_kills',slot:'sidebar'},
  ],
  minigame:[
    {mode:'create',obj:'score',crit:'dummy',disp:'{"text":"🎮 Score","color":"aqua"}'},
    {mode:'display',obj:'score',slot:'sidebar'},
  ],
  level:[
    {mode:'create',obj:'levels',crit:'level',disp:'{"text":"⭐ XP Level","color":"yellow"}'},
    {mode:'display',obj:'levels',slot:'sidebar'},
  ],
  teams:[
    {mode:'team',action:'add',name:'RedTeam',player:'',color:'red'},
    {mode:'team',action:'color',name:'RedTeam',color:'red'},
    {mode:'team',action:'add',name:'BlueTeam',player:'',color:'blue'},
    {mode:'team',action:'color',name:'BlueTeam',color:'blue'},
  ],
};

function loadSbPreset(key) {
  const steps = SB_PRESETS[key]; if(!steps) return;
  const allCmds = [];
  steps.forEach(s=>{
    if(s.mode==='create') {
      allCmds.push(`/scoreboard objectives add ${s.obj} ${s.crit} "${s.disp}"`);
      allCmds.push(`/scoreboard objectives setdisplay sidebar ${s.obj}`);
    } else if(s.mode==='display') {
      allCmds.push(`/scoreboard objectives setdisplay ${s.slot} ${s.obj}`);
    } else if(s.mode==='team') {
      if(s.action==='add') allCmds.push(`/team add ${s.name}`);
      if(s.action==='color') allCmds.push(`/team modify ${s.name} color ${s.color}`);
    }
  });
  generatedCmds = allCmds;
  document.getElementById('sb-cmd-list').innerHTML = allCmds.map((cmd,i)=>`
    <div class="cmd-row">
      <span style="font-size:10px;font-family:var(--mono);color:var(--text3);flex-shrink:0">${i+1}.</span>
      <span class="cmd-row-text">${cmd}</span>
      <button class="cmd-row-copy" onclick="copyText('${cmd.replace(/'/g,"\\'").replace(/"/g,'&quot;')}')">Copy</button>
    </div>`).join('');
  playSelect(); showToast('✅ Preset loaded!');
}

buildSb();
</script>
</body>
</html>
