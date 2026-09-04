<?php
require_once __DIR__ . '/items.php';
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NBT Builder — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.nbt-layout{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:900px){.nbt-layout{grid-template-columns:1fr}}
.color-grid{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px}
.color-swatch{width:26px;height:26px;border-radius:5px;cursor:pointer;border:2px solid transparent;transition:all .15s;flex-shrink:0}
.color-swatch:hover,.color-swatch.active{border-color:#fff;transform:scale(1.15)}
.lore-row{display:flex;gap:6px;margin-bottom:6px;align-items:center}
.lore-row input{flex:1}
.lore-del{background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);color:var(--red);border-radius:5px;padding:4px 8px;cursor:pointer;font-size:12px}
.ench-row{display:grid;grid-template-columns:1fr 80px 30px;gap:6px;align-items:center;margin-bottom:6px}
.ench-del{background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);color:var(--red);border-radius:5px;padding:4px 8px;cursor:pointer;font-size:12px}
.preview-name{font-size:18px;font-weight:700;margin-bottom:6px;font-family:var(--mono)}
.preview-lore{font-size:12px;color:#aaa;font-family:var(--mono);line-height:1.8;margin-bottom:8px}
.preview-ench{font-size:12px;color:#b088f5;font-family:var(--mono);line-height:1.8}
.preview-box{background:#1a1a1a;border:2px solid #555;border-radius:6px;padding:14px 16px;min-height:100px;position:relative}
.preview-box::before{content:'Item Preview';position:absolute;top:-9px;left:10px;background:#1a1a1a;padding:0 6px;font-size:10px;color:#666;font-family:var(--mono)}
.item-search-wrap{position:relative;margin-bottom:10px}
.item-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:13px}
.item-search{padding-left:30px !important}
.item-select-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:5px;max-height:200px;overflow-y:auto;padding:2px}
.item-sel-card{display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 3px;border-radius:7px;border:1px solid var(--border);background:var(--bg3);cursor:pointer;transition:all .15s;text-align:center}
.item-sel-card:hover{border-color:var(--green2)}
.item-sel-card.selected{border-color:var(--green);background:rgba(74,222,128,0.1)}
.item-sel-card img{width:28px;height:28px;image-rendering:pixelated}
.item-sel-card span{font-size:9px;color:var(--text2);line-height:1.2;word-break:break-word}
.tag-section{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:10px}
.tag-section-title{font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;gap:8px}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">🔧 <span>NBT Tag Builder</span></div>
  <div class="page-sub">// Custom name, lore and enchantments — all in one /give command</div>
</div>

<div class="content">
<div class="nbt-layout">

<!-- LEFT: CONFIG -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- TARGET & ITEM -->
  <div class="card">
    <div class="card-header"><span style="color:var(--gold)">●</span> TARGET & ITEM</div>
    <div class="card-body">
      <div class="row">
        <div class="field">
          <label>Player / Selector</label>
          <input type="text" id="nbt-target" value="<?= $defaultTarget ?>" oninput="buildNbt()">
        </div>
        <div class="field">
          <label>Quantity</label>
          <input type="number" id="nbt-qty" value="1" min="1" max="64" oninput="buildNbt()">
        </div>
      </div>
      <div class="field" style="margin-bottom:8px">
        <label>Search Item</label>
        <div class="item-search-wrap">
          <span class="item-search-icon">🔍</span>
          <input class="item-search" type="text" id="nbt-item-search" placeholder="Search item..." oninput="filterNbtItems()">
        </div>
      </div>
      <div class="item-select-grid" id="nbt-item-grid"></div>
      <div style="margin-top:8px;font-family:var(--mono);font-size:11px;color:var(--text3)">
        Selected: <span id="nbt-selected-id" style="color:var(--gold)">diamond_sword</span>
      </div>
    </div>
  </div>

  <!-- CUSTOM NAME -->
  <div class="card">
    <div class="card-header"><span style="color:var(--green)">●</span> CUSTOM NAME</div>
    <div class="card-body">
      <div class="field" style="margin-bottom:10px">
        <label>Item Name</label>
        <input type="text" id="nbt-name" placeholder="e.g. Excalibur" oninput="buildNbt()">
      </div>
      <div class="field">
        <label>Name Color</label>
        <div class="color-grid" id="name-colors"></div>
      </div>
      <div style="margin-top:10px;display:flex;align-items:center;gap:10px">
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="nbt-bold" onchange="buildNbt()" style="width:auto;accent-color:var(--green)"> Bold
        </label>
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="nbt-italic" checked onchange="buildNbt()" style="width:auto;accent-color:var(--green)"> Italic
        </label>
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="nbt-underline" onchange="buildNbt()" style="width:auto;accent-color:var(--green)"> Underline
        </label>
      </div>
    </div>
  </div>

  <!-- LORE -->
  <div class="card">
    <div class="card-header">
      <span style="color:var(--purple)">●</span> LORE LINES
      <button class="btn btn-purple btn-sm" style="margin-left:auto" onclick="addLore()">+ Add Line</button>
    </div>
    <div class="card-body">
      <div id="lore-container"></div>
      <p class="hint">💡 Lore = teks yang muncul bawah nama item · Max ~5 baris nampak cantik</p>
    </div>
  </div>

  <!-- ENCHANTS -->
  <div class="card">
    <div class="card-header">
      <span style="color:var(--pink)">●</span> ENCHANTMENTS
      <button class="btn btn-sm" style="margin-left:auto;background:rgba(244,114,182,0.1);border-color:rgba(244,114,182,0.4);color:var(--pink)" onclick="addEnch()">+ Add Enchant</button>
    </div>
    <div class="card-body">
      <div id="ench-container"></div>
      <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text2);cursor:pointer;margin-top:8px">
        <input type="checkbox" id="nbt-unbreakable" onchange="buildNbt()" style="width:auto;accent-color:var(--green)">
        Unbreakable (item tak akan rosak)
      </label>
      <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text2);cursor:pointer;margin-top:6px">
        <input type="checkbox" id="nbt-hide-flags" onchange="buildNbt()" style="width:auto;accent-color:var(--green)">
        Hide attribute flags (sembunyikan enchant tooltip)
      </label>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT: PREVIEW + OUTPUT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- PREVIEW -->
  <div class="card">
    <div class="card-header"><span style="color:var(--teal)">●</span> LIVE PREVIEW</div>
    <div class="card-body">
      <div class="preview-box">
        <div class="preview-name" id="prev-name" style="color:#55ff55">Diamond Sword</div>
        <div class="preview-lore" id="prev-lore"></div>
        <div class="preview-ench" id="prev-ench"></div>
        <div id="prev-tags" style="font-size:11px;color:#888;font-family:var(--mono);margin-top:6px"></div>
      </div>
    </div>
  </div>

  <!-- OUTPUT -->
  <div class="card" style="position:sticky;top:76px">
    <div class="card-header"><span style="color:var(--gold)">●</span> GENERATED COMMAND</div>
    <div class="card-body">

      <div style="margin-bottom:10px">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px">VERSION</div>
        <div style="display:flex;gap:6px">
          <span class="otab active" id="ver-note" title="Set the version in the top-right of the page">Syntax follows the version selector</span>
        </div>
      </div>

      <div class="cmd-output">
        <div class="cmd-output-header">
          <span>// /give command with NBT</span>
          <span id="cmd-len">0 chars</span>
        </div>
        <div class="cmd-output-body" id="nbt-output" style="font-size:12px;word-break:break-all;white-space:pre-wrap">/give nizkbiits diamond_sword 1</div>
        <div class="cmd-output-actions">
          <button class="btn btn-green" onclick="copyNbt()">📋 Copy Command</button>
          <button class="btn btn-gold btn-sm" onclick="copyJustNbt()">Copy NBT only</button>
        </div>
      </div>

      <!-- PRESETS -->
      <div class="divider"></div>
      <div style="font-size:11px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:8px">QUICK PRESETS</div>
      <div style="display:flex;flex-wrap:wrap;gap:5px">
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="loadNbtPreset('godSword')">⚔️ God Sword</button>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="loadNbtPreset('godPick')">⛏️ God Pickaxe</button>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="loadNbtPreset('kingHelm')">👑 King Helmet</button>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="loadNbtPreset('trollSword')">😈 Troll Item</button>
      </div>

    </div>
  </div>

</div><!-- /right -->
</div><!-- /nbt-layout -->
</div><!-- /content -->

<script>
const ALL_ITEMS = <?= json_encode($ITEMS_FLAT) ?>;
const ALL_ENCHANTS_LIST = <?= json_encode(array_merge(...array_values($ENCHANTS))) ?>;

// Unique enchants
const ENCH_OPTS = [...new Map(ALL_ENCHANTS_LIST.map(e=>[e[0],e])).values()];

const MC_COLORS = [
  {code:'black',hex:'#000000',name:'Black'},
  {code:'dark_blue',hex:'#0000AA',name:'Dark Blue'},
  {code:'dark_green',hex:'#00AA00',name:'Dark Green'},
  {code:'dark_aqua',hex:'#00AAAA',name:'Dark Aqua'},
  {code:'dark_red',hex:'#AA0000',name:'Dark Red'},
  {code:'dark_purple',hex:'#AA00AA',name:'Dark Purple'},
  {code:'gold',hex:'#FFAA00',name:'Gold'},
  {code:'gray',hex:'#AAAAAA',name:'Gray'},
  {code:'dark_gray',hex:'#555555',name:'Dark Gray'},
  {code:'blue',hex:'#5555FF',name:'Blue'},
  {code:'green',hex:'#55FF55',name:'Green'},
  {code:'aqua',hex:'#55FFFF',name:'Aqua'},
  {code:'red',hex:'#FF5555',name:'Red'},
  {code:'light_purple',hex:'#FF55FF',name:'Light Purple'},
  {code:'yellow',hex:'#FFFF55',name:'Yellow'},
  {code:'white',hex:'#FFFFFF',name:'White'},
];

const LORE_COLORS = [
  {code:'gray',hex:'#AAAAAA'},{code:'dark_gray',hex:'#555555'},
  {code:'aqua',hex:'#55FFFF'},{code:'yellow',hex:'#FFFF55'},
  {code:'green',hex:'#55FF55'},{code:'red',hex:'#FF5555'},
  {code:'light_purple',hex:'#FF55FF'},{code:'gold',hex:'#FFAA00'},
  {code:'white',hex:'#FFFFFF'},
];

let selectedItem = 'diamond_sword';
let selectedNameColor = 'green';
let loreCount = 0;
let enchCount = 0;

// Build color swatches
function initColors(){
  const wrap = document.getElementById('name-colors');
  MC_COLORS.forEach(c=>{
    const s = document.createElement('div');
    s.className='color-swatch'+(c.code===selectedNameColor?' active':'');
    s.style.background=c.hex;
    s.title=c.name;
    s.onclick=()=>{
      selectedNameColor=c.code;
      document.querySelectorAll('.color-swatch').forEach(x=>x.classList.remove('active'));
      s.classList.add('active');
      buildNbt(); playClick();
    };
    wrap.appendChild(s);
  });
}

// Build item grid
function renderNbtItems(search=''){
  const grid=document.getElementById('nbt-item-grid');
  grid.innerHTML='';
  const s=search.toLowerCase();
  const list=ALL_ITEMS.filter(([id,name])=>!s||name.toLowerCase().includes(s)||id.includes(s)).slice(0,60);
  list.forEach(([id,name])=>{
    const d=document.createElement('div');
    d.className='item-sel-card'+(id===selectedItem?' selected':'');
    d.innerHTML=`<img src="https://static.minecraftitemids.com/64/${id}.png" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2228%22 height=%2228%22><rect width=%2228%22 height=%2228%22 fill=%22%23252535%22 rx=%224%22/><text x=%2214%22 y=%2220%22 font-size=%2216%22 text-anchor=%22middle%22>📦</text></svg>'" alt="${name}"><span>${name}</span>`;
    d.onclick=()=>{
      selectedItem=id;
      document.getElementById('nbt-selected-id').textContent=id;
      document.querySelectorAll('.item-sel-card').forEach(c=>c.classList.remove('selected'));
      d.classList.add('selected');
      buildNbt(); playSelect();
    };
    grid.appendChild(d);
  });
}
function filterNbtItems(){ renderNbtItems(document.getElementById('nbt-item-search').value) }

function addLore(text='',color='gray'){
  loreCount++;
  const id='lore_'+loreCount;
  const row=document.createElement('div');
  row.className='lore-row'; row.id=id;
  const colorOpts=LORE_COLORS.map(c=>`<option value="${c.code}" style="background:${c.hex};color:#000" ${c.code===color?'selected':''}>${c.code}</option>`).join('');
  row.innerHTML=`
    <input type="text" value="${text}" placeholder="Lore line..." oninput="buildNbt()" style="flex:1">
    <select onchange="buildNbt()" style="width:110px;font-size:11px">${colorOpts}</select>
    <button class="lore-del" onclick="document.getElementById('${id}').remove();buildNbt()">✕</button>`;
  document.getElementById('lore-container').appendChild(row);
  buildNbt(); playClick();
}

function addEnch(enchId='sharpness', level=1){
  enchCount++;
  const id='ench_'+enchCount;
  const row=document.createElement('div');
  row.className='ench-row'; row.id=id;
  const enchOpts=ENCH_OPTS.map(([eid,max])=>`<option value="${eid}" ${eid===enchId?'selected':''}>${eid} (max ${max})</option>`).join('');
  row.innerHTML=`
    <select onchange="buildNbt()" style="font-size:11px">${enchOpts}</select>
    <input type="number" value="${level}" min="1" max="255" oninput="buildNbt()" style="font-size:12px;padding:5px 8px" title="Level">
    <button class="ench-del" onclick="document.getElementById('${id}').remove();buildNbt()">✕</button>`;
  document.getElementById('ench-container').appendChild(row);
  buildNbt(); playClick();
}

function v(id){ return document.getElementById(id)?.value||'' }
function chk(id){ return document.getElementById(id)?.checked||false }

function buildNbt(){
  const target = v('nbt-target')||'@p';
  const qty = v('nbt-qty')||1;
  const name = v('nbt-name');
  const bold = chk('nbt-bold');
  const italic = chk('nbt-italic');
  const underline = chk('nbt-underline');
  const unbreakable = chk('nbt-unbreakable');
  const hideFlags = chk('nbt-hide-flags');

  // Collect lore
  const loreRows = document.querySelectorAll('.lore-row');
  const lores = [];
  loreRows.forEach(row=>{
    const [inp,,sel] = [row.querySelector('input'),null,row.querySelector('select')];
    if(inp && inp.value.trim()) lores.push({text:inp.value.trim(),color:sel?sel.value:'gray'});
  });

  // Collect enchants
  const enchRows = document.querySelectorAll('.ench-row');
  const enchs = [];
  enchRows.forEach(row=>{
    const sel=row.querySelector('select');
    const inp=row.querySelector('input[type=number]');
    if(sel && inp) enchs.push({id:sel.value, lvl:parseInt(inp.value)||1});
  });

  // Update preview
  const prevName = document.getElementById('prev-name');
  if(name){
    const color = MC_COLORS.find(c=>c.code===selectedNameColor);
    prevName.style.color = color?.hex||'#55FF55';
    prevName.style.fontWeight = bold?'900':'700';
    prevName.style.fontStyle = italic?'italic':'normal';
    prevName.style.textDecoration = underline?'underline':'none';
    prevName.textContent = name;
  } else {
    prevName.style.color='#55FF55';
    prevName.textContent=selectedItem.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
  }

  document.getElementById('prev-lore').innerHTML = lores.map(l=>{
    const c=LORE_COLORS.find(x=>x.code===l.color);
    return `<div style="color:${c?.hex||'#aaa'}">${l.text}</div>`;
  }).join('');

  document.getElementById('prev-ench').innerHTML = enchs.map(e=>`<div>✨ ${e.id} ${e.lvl}</div>`).join('');

  const tags=[];
  if(unbreakable) tags.push('<span style="color:var(--blue)">Unbreakable</span>');
  if(hideFlags) tags.push('<span style="color:var(--text3)">Flags Hidden</span>');
  document.getElementById('prev-tags').innerHTML=tags.join(' · ');

  // Build command — MC.item writes NBT or components to match the
  // version chosen in the top-right, so this no longer guesses.
  const spec = {};
  if (name) spec.name = MC.text(name, { color: selectedNameColor, bold, italic, underlined: underline });
  if (lores.length) spec.lore = lores.map(l => MC.text(l.text, { color: l.color, italic: false }));
  if (enchs.length) spec.enchants = enchs.map(e => ({ id: e.id, lvl: e.lvl }));
  if (unbreakable) spec.unbreakable = true;
  if (hideFlags) spec.hideFlags = true;

  const cmd = `/give ${target} ${MC.item(selectedItem, spec)}${qty > 1 ? ' ' + qty : ''}`;

  const note = document.getElementById('ver-note');
  if (note) note.textContent = MC.v().label + ' — ' + MC.syn().label;

  document.getElementById('nbt-output').textContent = cmd;
  document.getElementById('cmd-len').textContent = cmd.length+' chars';
}

document.addEventListener('mc:version', () => buildNbt());

function copyNbt(){
  const cmd=document.getElementById('nbt-output').textContent;
  copyText(cmd);
}

function copyJustNbt(){
  const cmd=document.getElementById('nbt-output').textContent;
  const match=cmd.match(/\{.+\}/);
  if(!match){ playError(); showToast('⚠️ No NBT data','var(--red)'); return; }
  copyText(match[0]);
  showToast('✅ NBT data copied!');
}

// Presets
const NBT_PRESETS = {
  godSword:{item:'netherite_sword',name:'⚔ Excalibur',nameColor:'aqua',bold:true,italic:false,
    lores:[['The Legendary Blade','gray'],['Forged in the Nether','dark_red'],['Owner: nizkbiits','gold']],
    enchs:[['sharpness',5],['fire_aspect',2],['looting',3],['unbreaking',3],['mending',1]],unbreakable:true},
  godPick:{item:'netherite_pickaxe',name:'⛏ Mjolnir',nameColor:'yellow',bold:true,italic:false,
    lores:[['God-tier Mining Tool','gray'],['Breaks anything','dark_gray']],
    enchs:[['efficiency',5],['fortune',3],['unbreaking',3],['mending',1]],unbreakable:true},
  kingHelm:{item:'netherite_helmet',name:'👑 Crown of nizkbiits',nameColor:'gold',bold:true,italic:true,
    lores:[['Worn by the King','yellow'],['None shall challenge','dark_purple']],
    enchs:[['protection',4],['respiration',3],['aqua_affinity',1],['unbreaking',3],['mending',1]],unbreakable:true},
  trollSword:{item:'wooden_sword',name:'§k▓▒░ LEGENDARY ░▒▓§k',nameColor:'dark_red',bold:true,italic:false,
    lores:[['This sword is VERY POWERFUL','red'],['...trust me','dark_gray']],
    enchs:[['sharpness',1]],unbreakable:false},
};

function loadNbtPreset(key){
  const p=NBT_PRESETS[key]; if(!p) return;
  selectedItem=p.item;
  document.getElementById('nbt-selected-id').textContent=p.item;
  document.querySelectorAll('.item-sel-card').forEach(c=>c.classList.remove('selected'));
  document.getElementById('nbt-name').value=p.name;
  selectedNameColor=p.nameColor;
  document.querySelectorAll('.color-swatch').forEach((s,i)=>s.classList.toggle('active',MC_COLORS[i]?.code===p.nameColor));
  document.getElementById('nbt-bold').checked=p.bold;
  document.getElementById('nbt-italic').checked=p.italic;
  document.getElementById('nbt-unbreakable').checked=p.unbreakable||false;
  document.getElementById('lore-container').innerHTML=''; loreCount=0;
  document.getElementById('ench-container').innerHTML=''; enchCount=0;
  p.lores.forEach(([t,c])=>addLore(t,c));
  p.enchs.forEach(([id,lvl])=>addEnch(id,lvl));
  renderNbtItems();
  buildNbt(); playSelect();
  showToast('✅ Preset loaded!');
}

// Init
initColors();
renderNbtItems();
buildNbt();
</script>
</body>
</html>
