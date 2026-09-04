<?php
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Command Sequencer — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.seq-layout{display:grid;grid-template-columns:1fr 380px;gap:20px}
@media(max-width:900px){.seq-layout{grid-template-columns:1fr}}
.seq-list{display:flex;flex-direction:column;gap:6px;min-height:200px;max-height:560px;overflow-y:auto;padding:4px}
.seq-item{
  display:flex;align-items:center;gap:10px;
  background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:10px 14px;
  cursor:grab;transition:all .2s;user-select:none;
}
.seq-item:active{cursor:grabbing}
.seq-item.dragging{opacity:.4;border-color:var(--green);background:rgba(74,222,128,0.05)}
.seq-item.drag-over{border-color:var(--blue);background:rgba(96,165,250,0.06)}
.seq-num{font-size:11px;font-family:var(--mono);color:var(--text3);min-width:24px;text-align:center;flex-shrink:0}
.seq-drag{color:var(--text3);font-size:16px;cursor:grab;flex-shrink:0}
.seq-cmd{font-family:var(--mono);font-size:13px;color:var(--gold);flex:1;word-break:break-all;line-height:1.4}
.seq-del{flex-shrink:0;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);color:var(--red);border-radius:5px;padding:4px 8px;cursor:pointer;font-size:12px}
.seq-del:hover{background:rgba(248,113,113,0.2)}
.seq-empty{text-align:center;padding:40px;color:var(--text3);font-family:var(--mono);font-size:12px;line-height:2;border:2px dashed var(--border);border-radius:10px}
.delay-badge{font-size:10px;font-family:var(--mono);background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.25);color:var(--blue);padding:2px 6px;border-radius:4px;flex-shrink:0}
.quick-btns{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px}
.quick-btn{padding:7px 10px;font-size:11px;font-weight:700;background:var(--bg3);border:1px solid var(--border);color:var(--text2);border-radius:8px;cursor:pointer;transition:all .15s;font-family:var(--body);text-align:left}
.quick-btn:hover{border-color:var(--green);color:var(--green)}
.output-tabs{display:flex;gap:4px;margin-bottom:10px}
.otab{padding:6px 14px;font-size:12px;font-weight:700;background:transparent;border:1px solid var(--border);color:var(--text3);border-radius:8px;cursor:pointer;font-family:var(--body);transition:all .15s}
.otab.active{background:rgba(74,222,128,0.1);border-color:var(--green);color:var(--green)}
.output-area{background:var(--bg);border:1px solid var(--border2);border-radius:8px;padding:14px;font-family:var(--mono);font-size:12px;color:var(--gold);white-space:pre-wrap;word-break:break-all;max-height:300px;overflow-y:auto;line-height:1.7;min-height:80px}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">📋 <span>Command Sequencer</span></div>
  <div class="page-sub">// Susun command dalam urutan — drag to reorder — export pelbagai format</div>
</div>

<div class="content">
<div class="seq-layout">

<!-- LEFT: SEQUENCE -->
<div style="display:flex;flex-direction:column;gap:16px">

  <!-- ADD COMMAND -->
  <div class="card">
    <div class="card-header"><span style="color:var(--green)">●</span> ADD COMMAND</div>
    <div class="card-body">
      <div class="row" style="align-items:end">
        <div class="field" style="grid-column:1/-1">
          <label>Command (boleh letak / atau tak)</label>
          <input type="text" id="cmd-input" placeholder="e.g. give nizkbiits diamond_sword 1" onkeydown="if(event.key==='Enter')addCmd()">
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button class="btn btn-green" onclick="addCmd()">＋ Add to Sequence</button>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);cursor:pointer">
          <input type="checkbox" id="auto-slash" checked style="width:auto;accent-color:var(--green)"> Auto-prefix /
        </label>
        <div class="field" style="margin:0;display:flex;align-items:center;gap:6px">
          <label style="white-space:nowrap;font-size:11px;color:var(--text3);margin:0">Delay (tick):</label>
          <input type="number" id="cmd-delay" value="0" min="0" max="200" style="width:70px;padding:5px 8px;font-size:12px">
        </div>
      </div>

      <div style="margin-top:14px">
        <div class="sec-title" style="color:var(--blue)">⚡ Quick Add</div>
        <div class="quick-btns">
          <?php
          $quick = [
            ["give {t} netherite_sword 1",         "🗡️ Netherite Sword"],
            ["give {t} diamond_pickaxe 1",          "⛏️ Diamond Pickaxe"],
            ["effect give {t} strength 60 1",       "💪 Strength II (60s)"],
            ["effect give {t} regeneration 60 1",   "❤️ Regen II (60s)"],
            ["effect give {t} speed 60 1",          "🏃 Speed II (60s)"],
            ["effect give {t} night_vision 300 0",  "👁️ Night Vision"],
            ["gamemode creative {t}",               "🎨 Creative Mode"],
            ["gamemode survival {t}",               "⚔️ Survival Mode"],
            ["tp {t} ~ ~5 ~",                       "⬆️ TP Up 5 blocks"],
            ["xp add {t} 30L",                      "⭐ Give 30 Levels"],
            ["time set day",                        "☀️ Set Time Day"],
            ["weather clear",                       "🌤️ Clear Weather"],
          ];
          foreach($quick as [$cmd, $label]): ?>
          <button class="quick-btn" onclick="quickAdd('<?= htmlspecialchars($cmd) ?>')"><?= $label ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- SEQUENCE LIST -->
  <div class="card">
    <div class="card-header">
      <span style="color:var(--purple)">●</span> SEQUENCE
      <span id="seq-count" class="badge badge-green" style="margin-left:6px">0 commands</span>
      <div style="margin-left:auto;display:flex;gap:6px">
        <button class="btn btn-sm" style="background:rgba(96,165,250,0.08);border-color:rgba(96,165,250,0.3);color:var(--blue)" onclick="sortAlpha()">A→Z</button>
        <button class="btn btn-red btn-sm" onclick="clearAll()">🗑 Clear All</button>
      </div>
    </div>
    <div class="card-body">
      <div class="seq-list" id="seq-list">
        <div class="seq-empty">📭<br>Tiada command lagi.<br>Add command di atas atau guna Quick Add.</div>
      </div>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT: EXPORT -->
<div style="display:flex;flex-direction:column;gap:16px">
  <div class="card" style="position:sticky;top:76px">
    <div class="card-header"><span style="color:var(--gold)">●</span> EXPORT</div>
    <div class="card-body">

      <div class="output-tabs">
        <button class="otab active" onclick="setOutTab('plain',this)">Plain</button>
        <button class="otab" onclick="setOutTab('slash',this)">With /</button>
        <button class="otab" onclick="setOutTab('mcfunction',this)">.mcfunction</button>
        <button class="otab" onclick="setOutTab('numbered',this)">Numbered</button>
      </div>

      <div class="output-area" id="output-area">// Tambah commands untuk export</div>

      <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
        <button class="btn btn-green btn-lg" onclick="copyOutput()">📋 Copy Export</button>
        <button class="btn btn-gold" onclick="downloadMcfunction()" title="Download as .mcfunction file">⬇️ .mcfunction</button>
      </div>

      <div class="divider"></div>

      <!-- STATS -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px">
        <div style="text-align:center;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px">
          <div style="font-size:22px;font-weight:800;font-family:var(--mono);color:var(--green)" id="exp-count">0</div>
          <div style="font-size:10px;color:var(--text3);font-family:var(--mono)">Commands</div>
        </div>
        <div style="text-align:center;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px">
          <div style="font-size:22px;font-weight:800;font-family:var(--mono);color:var(--blue)" id="exp-ticks">0</div>
          <div style="font-size:10px;color:var(--text3);font-family:var(--mono)">Total Ticks</div>
        </div>
        <div style="text-align:center;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px">
          <div style="font-size:22px;font-weight:800;font-family:var(--mono);color:var(--gold)" id="exp-chars">0</div>
          <div style="font-size:10px;color:var(--text3);font-family:var(--mono)">Characters</div>
        </div>
      </div>

      <!-- TIPS -->
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:8px">FORMAT GUIDE</div>
        <div style="font-size:11px;color:var(--text2);line-height:2;font-family:var(--mono)">
          <b style="color:var(--green)">Plain</b> — untuk chat biasa<br>
          <b style="color:var(--green)">With /</b> — untuk command block<br>
          <b style="color:var(--green)">.mcfunction</b> — untuk datapack<br>
          <b style="color:var(--green)">Numbered</b> — untuk rujukan
        </div>
      </div>

    </div>
  </div>
</div>

</div><!-- /seq-layout -->
</div><!-- /content -->

<script>
let sequence = [];
let dragIdx = null;
let outTab = 'plain';

function v(id){ return document.getElementById(id)?.value || '' }

function addCmd(){
  const raw = document.getElementById('cmd-input').value.trim();
  if(!raw){ playError(); return; }
  const autoSlash = document.getElementById('auto-slash').checked;
  const delay = parseInt(document.getElementById('cmd-delay').value)||0;
  let cmd = raw;
  if(autoSlash && !cmd.startsWith('/')) cmd = '/'+cmd;
  sequence.push({cmd, delay});
  document.getElementById('cmd-input').value='';
  render();
  playSelect();
}

function quickAdd(cmd){
  const target = document.getElementById('target')?.value || 'nizkbiits';
  const autoSlash = document.getElementById('auto-slash').checked;
  let c = cmd.replace(/\{t\}/g, target);
  if(autoSlash && !c.startsWith('/')) c='/'+c;
  sequence.push({cmd:c, delay:0});
  render();
  playSelect();
}

function removeCmd(i){
  sequence.splice(i,1);
  render();
  playClick();
}

function clearAll(){
  if(sequence.length===0) return;
  if(!confirm('Clear semua commands?')) return;
  sequence=[];
  render();
  playClick();
}

function sortAlpha(){
  sequence.sort((a,b)=>a.cmd.localeCompare(b.cmd));
  render();
  playClick();
}

function render(){
  const list = document.getElementById('seq-list');
  document.getElementById('seq-count').textContent = sequence.length+' command'+(sequence.length!==1?'s':'');

  if(sequence.length===0){
    list.innerHTML='<div class="seq-empty">📭<br>Tiada command lagi.<br>Add command di atas atau guna Quick Add.</div>';
    updateOutput(); return;
  }

  list.innerHTML = sequence.map((item,i)=>`
    <div class="seq-item" draggable="true"
      ondragstart="dragStart(${i})" ondragover="dragOver(event,${i})" ondrop="drop(${i})" ondragend="dragEnd()"
      id="sitem_${i}">
      <span class="seq-drag">⠿</span>
      <span class="seq-num">${i+1}</span>
      <span class="seq-cmd">${item.cmd}</span>
      ${item.delay>0?`<span class="delay-badge">+${item.delay}t</span>`:''}
      <button class="seq-del" onclick="removeCmd(${i})">✕</button>
    </div>`).join('');

  updateOutput();
}

function updateOutput(){
  const cmds = sequence.map(s=>s.cmd);
  const totalTicks = sequence.reduce((a,s)=>a+s.delay,0);
  const totalChars = cmds.join('\n').length;

  document.getElementById('exp-count').textContent = cmds.length;
  document.getElementById('exp-ticks').textContent = totalTicks;
  document.getElementById('exp-chars').textContent = totalChars;

  if(cmds.length===0){
    document.getElementById('output-area').textContent='// Tambah commands untuk export';
    return;
  }

  let out='';
  if(outTab==='plain') out = cmds.map(c=>c.replace(/^\//,'')).join('\n');
  else if(outTab==='slash') out = cmds.map(c=>c.startsWith('/')?c:'/'+c).join('\n');
  else if(outTab==='mcfunction') out = '# Generated by Minecraft CMDGen\n# nizkbiits\n\n'+cmds.map(c=>c.replace(/^\//,'')).join('\n');
  else if(outTab==='numbered') out = cmds.map((c,i)=>`${i+1}. ${c}`).join('\n');

  document.getElementById('output-area').textContent = out;
}

function setOutTab(tab, btn){
  outTab=tab;
  document.querySelectorAll('.otab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  updateOutput();
  playClick();
}

function copyOutput(){
  const out = document.getElementById('output-area').textContent;
  if(!out || out.startsWith('//')){ playError(); showToast('⚠️ No commands!','var(--red)'); return; }
  navigator.clipboard.writeText(out).catch(()=>{});
  playCopy();
  showToast(`✅ ${sequence.length} commands copied!`);
}

function downloadMcfunction(){
  if(sequence.length===0){ playError(); return; }
  const content = '# Generated by Minecraft CMDGen\n# Player: nizkbiits\n\n'
    + sequence.map(s=>s.cmd.replace(/^\//,'')).join('\n');
  const blob = new Blob([content],{type:'text/plain'});
  const a = document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download='commands.mcfunction';
  a.click();
  playSave();
  showToast('⬇️ Downloaded commands.mcfunction');
}

// DRAG & DROP
function dragStart(i){ dragIdx=i; setTimeout(()=>document.getElementById('sitem_'+i)?.classList.add('dragging'),0) }
function dragEnd(){ document.querySelectorAll('.seq-item').forEach(el=>el.classList.remove('dragging','drag-over')); dragIdx=null }
function dragOver(e,i){ e.preventDefault(); if(dragIdx===null||dragIdx===i) return; document.querySelectorAll('.seq-item').forEach(el=>el.classList.remove('drag-over')); document.getElementById('sitem_'+i)?.classList.add('drag-over') }
function drop(i){
  if(dragIdx===null||dragIdx===i) return;
  const moved=sequence.splice(dragIdx,1)[0];
  sequence.splice(i,0,moved);
  render();
  playSelect();
}

render();
</script>
</body>
</html>
