<?php
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign Text Generator — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.sign-layout{display:grid;grid-template-columns:1fr 380px;gap:20px}
@media(max-width:920px){.sign-layout{grid-template-columns:1fr}}
.sign-preview{
  background:linear-gradient(135deg,#8B6914,#6B4F10,#8B6914);
  border:3px solid #4a3008;border-radius:6px;
  padding:16px 20px;min-height:130px;
  display:flex;flex-direction:column;justify-content:center;gap:2px;
  box-shadow:inset 0 2px 8px rgba(0,0,0,0.4),0 4px 16px rgba(0,0,0,0.5);
  position:relative;margin-bottom:12px;
}
.sign-preview::before{
  content:'';position:absolute;inset:3px;
  background:linear-gradient(135deg,#9B7A1A,#7B5A10);
  border-radius:3px;z-index:0;opacity:.3;
}
.sign-line{
  font-family:'Share Tech Mono',monospace;font-size:15px;font-weight:700;
  text-align:center;min-height:22px;
  position:relative;z-index:1;
  text-shadow:1px 1px 2px rgba(0,0,0,0.8);
  word-break:break-all;line-height:1.4;
  transition:all .15s;
}
.sign-stub{
  width:40px;height:60px;
  background:linear-gradient(180deg,#6B4F10,#4a3008);
  margin:8px auto 0;border-radius:2px;
  box-shadow:2px 2px 4px rgba(0,0,0,0.4);
}
.line-editor{
  background:var(--bg3);border:1px solid var(--border2);border-radius:10px;
  padding:12px;margin-bottom:8px;
  transition:border-color .15s;
}
.line-editor:focus-within{border-color:var(--border3)}
.line-num{font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px}
.line-tools{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px}
.lt-btn{
  padding:3px 9px;font-size:11px;font-weight:700;
  border:1px solid var(--border2);background:var(--bg2);
  color:var(--text2);border-radius:5px;cursor:pointer;transition:all .12s;
  font-family:var(--mono);
}
.lt-btn:hover{border-color:var(--border3);color:var(--text)}
.color-swatches{display:flex;flex-wrap:wrap;gap:3px;margin-bottom:6px}
.cs-small{width:18px;height:18px;border-radius:3px;cursor:pointer;border:1px solid transparent;transition:all .1s;flex-shrink:0}
.cs-small:hover{border-color:rgba(255,255,255,0.5);transform:scale(1.15)}
.cs-small.active{border-color:#fff}
.sign-type-tabs{display:flex;gap:4px;margin-bottom:12px;flex-wrap:wrap}
.sttab{padding:5px 12px;font-size:12px;font-weight:600;background:var(--bg3);border:1px solid var(--border2);color:var(--text3);border-radius:var(--radius-sm);cursor:pointer;transition:all .12s;font-family:var(--body)}
.sttab:hover{border-color:var(--orange);color:var(--orange)}
.sttab.active{background:rgba(240,135,74,0.1);border-color:rgba(240,135,74,0.35);color:var(--orange)}
.char-count{font-size:10px;font-family:var(--mono);color:var(--text3);float:right}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">🪧 <span>Sign Text Generator</span></div>
  <div class="page-sub">// Make a sign with coloured text — generates a /setblock or /data command</div>
</div>

<div class="content">
<div class="sign-layout">

<!-- LEFT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- SIGN TYPE & POSITION -->
  <div class="card">
    <div class="card-header"><span style="color:var(--orange)">●</span> JENIS SIGN & POSISI</div>
    <div class="card-body">
      <div class="sign-type-tabs">
        <?php
        $signs = [
          ['oak_sign','🪵 Oak'],['spruce_sign','🌲 Spruce'],
          ['birch_sign','🍂 Birch'],['jungle_sign','🌴 Jungle'],
          ['acacia_sign','🌵 Acacia'],['dark_oak_sign','🌑 Dark Oak'],
          ['mangrove_sign','🌿 Mangrove'],['bamboo_sign','🎋 Bamboo'],
          ['crimson_sign','🔴 Crimson'],['warped_sign','🔵 Warped'],
        ];
        foreach($signs as [$id,$label]):
        ?>
        <button class="sttab <?= $id==='oak_sign'?'active':'' ?>" onclick="setSignType('<?= $id ?>',this)"><?= $label ?></button>
        <?php endforeach; ?>
      </div>
      <div class="row">
        <div class="field"><label>X</label><input type="text" id="sg-x" value="~" oninput="buildSign()"></div>
        <div class="field"><label>Y</label><input type="text" id="sg-y" value="~" oninput="buildSign()"></div>
        <div class="field"><label>Z</label><input type="text" id="sg-z" value="~" oninput="buildSign()"></div>
        <div class="field"><label>Glowing Text</label>
          <select id="sg-glow" onchange="buildSign()">
            <option value="false">No</option>
            <option value="true">Yes ✨</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- LINE EDITORS -->
  <?php for($i=1;$i<=4;$i++): ?>
  <div class="card">
    <div class="card-header"><span style="color:var(--pink)">●</span> LINE <?= $i ?></div>
    <div class="card-body">
      <div class="line-editor">
        <div class="line-num">
          LINE <?= $i ?>
          <span class="char-count" id="cc-<?= $i ?>">0 / 15</span>
        </div>
        <div class="line-tools">
          <button class="lt-btn" onclick="fmt(<?= $i ?>,'bold')" title="Bold"><b>B</b></button>
          <button class="lt-btn" onclick="fmt(<?= $i ?>,'italic')" title="Italic"><i>I</i></button>
          <button class="lt-btn" onclick="fmt(<?= $i ?>,'underlined')" title="Underline"><u>U</u></button>
          <button class="lt-btn" onclick="fmt(<?= $i ?>,'strikethrough')" title="Strike"><s>S</s></button>
          <button class="lt-btn" onclick="fmt(<?= $i ?>,'obfuscated')" title="Scramble">§k</button>
          <button class="lt-btn" onclick="clearLine(<?= $i ?>)" style="color:var(--red);border-color:rgba(240,96,96,0.3)">✕ Clear</button>
        </div>
        <div class="color-swatches" id="cs-<?= $i ?>"></div>
        <input type="text" id="line-<?= $i ?>" placeholder="Line <?= $i ?> text..." oninput="updateLine(<?= $i ?>)" maxlength="50">
        <input type="hidden" id="color-<?= $i ?>" value="white">
        <input type="hidden" id="bold-<?= $i ?>" value="false">
        <input type="hidden" id="italic-<?= $i ?>" value="false">
        <input type="hidden" id="underlined-<?= $i ?>" value="false">
        <input type="hidden" id="strikethrough-<?= $i ?>" value="false">
        <input type="hidden" id="obfuscated-<?= $i ?>" value="false">
      </div>
    </div>
  </div>
  <?php endfor; ?>

</div><!-- /left -->

<!-- RIGHT -->
<div>
  <div class="card" style="position:sticky;top:70px">
    <div class="card-header"><span style="color:var(--teal)">●</span> PREVIEW & OUTPUT</div>
    <div class="card-body">

      <!-- SIGN PREVIEW -->
      <div style="text-align:center;margin-bottom:14px">
        <div class="sign-preview">
          <div class="sign-line" id="prev-1" style="color:#fff">Line 1</div>
          <div class="sign-line" id="prev-2" style="color:#fff">Line 2</div>
          <div class="sign-line" id="prev-3" style="color:#fff">Line 3</div>
          <div class="sign-line" id="prev-4" style="color:#fff">Line 4</div>
        </div>
        <div class="sign-stub"></div>
      </div>

      <div class="cmd-output">
        <div class="cmd-output-header"><span>// /setblock command</span></div>
        <div class="cmd-output-body" id="sign-output" style="font-size:11px;word-break:break-all;white-space:pre-wrap">/setblock ~ ~ ~ oak_sign</div>
        <div class="cmd-output-actions">
          <button class="btn btn-green" onclick="copyText(document.getElementById('sign-output').textContent)">📋 Copy</button>
        </div>
      </div>

      <div class="divider"></div>
      <div style="font-size:11px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:8px">QUICK PRESETS</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px">
        <button class="btn btn-ghost btn-sm" onclick="loadSignPreset('welcome')">👋 Welcome</button>
        <button class="btn btn-ghost btn-sm" onclick="loadSignPreset('shop')">🏪 Shop</button>
        <button class="btn btn-ghost btn-sm" onclick="loadSignPreset('warning')">⚠️ Warning</button>
        <button class="btn btn-ghost btn-sm" onclick="loadSignPreset('info')">ℹ️ Info</button>
      </div>

      <div style="margin-top:12px;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px">💡 CARA GUNA</div>
        <div style="font-size:11px;color:var(--text2);line-height:1.9;font-family:var(--mono)">
          Copy command → paste dalam chat<br>
          Sign akan diplace di koordinat XYZ<br>
          Guna ~ untuk posisi semasa
        </div>
      </div>
    </div>
  </div>
</div>

</div><!-- /sign-layout -->
</div>

<script>
const MC_COLORS = [
  {code:'white',hex:'#FFFFFF'},{code:'yellow',hex:'#FFFF55'},
  {code:'gold',hex:'#FFAA00'},{code:'red',hex:'#FF5555'},
  {code:'dark_red',hex:'#AA0000'},{code:'green',hex:'#55FF55'},
  {code:'dark_green',hex:'#00AA00'},{code:'aqua',hex:'#55FFFF'},
  {code:'dark_aqua',hex:'#00AAAA'},{code:'blue',hex:'#5555FF'},
  {code:'light_purple',hex:'#FF55FF'},{code:'dark_purple',hex:'#AA00AA'},
  {code:'gray',hex:'#AAAAAA'},{code:'dark_gray',hex:'#555555'},
  {code:'black',hex:'#000000'},
];

let signType = 'oak_sign';

function initColorSwatches() {
  for(let i=1;i<=4;i++) {
    const wrap = document.getElementById('cs-'+i);
    wrap.innerHTML = '';
    MC_COLORS.forEach(c => {
      const s = document.createElement('div');
      s.className = 'cs-small' + (c.code==='white'?' active':'');
      s.style.background = c.hex;
      s.title = c.code;
      s.onclick = () => {
        document.getElementById('color-'+i).value = c.code;
        wrap.querySelectorAll('.cs-small').forEach(x=>x.classList.remove('active'));
        s.classList.add('active');
        updateLine(i);
      };
      wrap.appendChild(s);
    });
  }
}

function setSignType(type, btn) {
  signType = type;
  document.querySelectorAll('.sttab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  buildSign(); playClick();
}

function v(id){ return document.getElementById(id)?.value||'' }

function fmt(line, prop) {
  const el = document.getElementById(prop+'-'+line);
  el.value = el.value==='true' ? 'false' : 'true';
  updateLine(line); playClick();
}

function clearLine(line) {
  document.getElementById('line-'+line).value = '';
  ['bold','italic','underlined','strikethrough','obfuscated'].forEach(p=>{
    document.getElementById(p+'-'+line).value='false';
  });
  document.getElementById('color-'+line).value='white';
  const wrap=document.getElementById('cs-'+line);
  wrap.querySelectorAll('.cs-small').forEach((s,i)=>s.classList.toggle('active',i===0));
  updateLine(line);
}

function updateLine(line) {
  const text = v('line-'+line);
  const color = v('color-'+line) || 'white';
  const bold = v('bold-'+line)==='true';
  const italic = v('italic-'+line)==='true';
  const underlined = v('underlined-'+line)==='true';
  const c = MC_COLORS.find(x=>x.code===color);
  const prev = document.getElementById('prev-'+line);
  prev.textContent = text || '';
  prev.style.color = c?.hex || '#fff';
  prev.style.fontWeight = bold ? '900' : '700';
  prev.style.fontStyle = italic ? 'italic' : 'normal';
  prev.style.textDecoration = underlined ? 'underline' : 'none';
  document.getElementById('cc-'+line).textContent = text.length+' / 15';
  buildSign();
}

document.addEventListener('mc:version', () => buildSign());

function buildSign() {
  const x=v('sg-x')||'~', y=v('sg-y')||'~', z=v('sg-z')||'~';
  const glow = v('sg-glow');

  const lines = [];
  for(let i=1;i<=4;i++) {
    const text=v('line-'+i), color=v('color-'+i)||'white';
    const bold=v('bold-'+i)==='true', italic=v('italic-'+i)==='true';
    const underlined=v('underlined-'+i)==='true';
    const strikethrough=v('strikethrough-'+i)==='true';
    const obfuscated=v('obfuscated-'+i)==='true';
    const obj = {text};
    if(color!=='white') obj.color=color;
    if(bold) obj.bold=true;
    if(italic) obj.italic=true;
    if(underlined) obj.underlined=true;
    if(strikethrough) obj.strikethrough=true;
    if(obfuscated) obj.obfuscated=true;
    lines.push(MC.raw(MC.nbtText(obj)));
  }

  // Block entity data stays in braces on every version — components are
  // for items. What did change in 1.21.5 is how the messages themselves
  // are written, which MC.nbtText handles.
  //
  // Signs only gained double-sided text (front_text/back_text, one side
  // per player-facing surface) in 1.20. On 1.19.4 and earlier the block
  // entity is flat: Text1-Text4 plus a top-level GlowingText byte — there
  // is no front_text wrapper at all.
  const glowByte = MC.byte(glow === 'true' || glow === true || glow === '1' ? 1 : 0);
  let nbt;
  if (MC.has('sign_front_back')) {
    nbt = MC.snbt({ front_text: { messages: lines, has_glowing_text: glowByte } });
  } else {
    const flat = {};
    lines.forEach((line, i) => { flat['Text' + (i + 1)] = line; });
    flat.GlowingText = glowByte;
    nbt = MC.snbt(flat);
  }
  const cmd = `/setblock ${x} ${y} ${z} ${signType}${nbt}`;
  document.getElementById('sign-output').textContent = cmd;
}

const SIGN_PRESETS = {
  welcome:{lines:[
    {t:'✦ WELCOME ✦',c:'gold',b:true},{t:'to the server',c:'yellow',b:false},
    {t:'nizkbiits',c:'aqua',b:false},{t:'',c:'white',b:false}
  ]},
  shop:{lines:[
    {t:'[ SHOP ]',c:'green',b:true},{t:'Diamond: 5 em',c:'aqua',b:false},
    {t:'Netherite: 20',c:'gold',b:false},{t:'↓ Click to buy',c:'yellow',b:false}
  ]},
  warning:{lines:[
    {t:'⚠ WARNING ⚠',c:'red',b:true},{t:'Danger zone',c:'dark_red',b:false},
    {t:'Enter at',c:'gray',b:false},{t:'your own risk',c:'gray',b:false}
  ]},
  info:{lines:[
    {t:'ℹ INFO',c:'aqua',b:true},{t:'',c:'white',b:false},
    {t:'Rules apply',c:'white',b:false},{t:'Have fun!',c:'green',b:false}
  ]},
};

function loadSignPreset(key) {
  const p = SIGN_PRESETS[key]; if(!p) return;
  p.lines.forEach((line,i)=>{
    const n=i+1;
    document.getElementById('line-'+n).value=line.t;
    document.getElementById('color-'+n).value=line.c;
    document.getElementById('bold-'+n).value=line.b?'true':'false';
    const wrap=document.getElementById('cs-'+n);
    wrap.querySelectorAll('.cs-small').forEach(s=>{
      s.classList.toggle('active',s.title===line.c);
    });
    updateLine(n);
  });
  playSelect(); showToast('✅ Preset loaded!');
}

initColorSwatches();
buildSign();
</script>
</body>
</html>
