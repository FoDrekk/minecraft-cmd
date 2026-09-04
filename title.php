<?php
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Title Generator — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.title-layout{display:grid;grid-template-columns:1fr 420px;gap:20px}
@media(max-width:900px){.title-layout{grid-template-columns:1fr}}
/* MINECRAFT SCREEN PREVIEW */
.mc-preview{
  background:linear-gradient(180deg,#87CEEB 0%,#87CEEB 60%,#6B8E4E 60%,#6B8E4E 75%,#5D7A3E 75%,#5D7A3E 100%);
  border-radius:10px;border:2px solid var(--border);
  height:280px;position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px;
}
.mc-preview::before{
  content:'';position:absolute;inset:0;
  background:rgba(0,0,0,0.35);
}
.mc-title-text{
  position:relative;z-index:1;
  font-family:'Share Tech Mono',monospace;
  font-size:28px;font-weight:900;
  text-shadow:2px 2px 0 #000,-2px -2px 0 #000,2px -2px 0 #000,-2px 2px 0 #000;
  text-align:center;padding:0 16px;
  transition:all .3s;
  letter-spacing:1px;
}
.mc-subtitle-text{
  position:relative;z-index:1;
  font-family:'Share Tech Mono',monospace;
  font-size:16px;font-weight:700;
  text-shadow:1px 1px 0 #000,-1px -1px 0 #000;
  text-align:center;padding:0 16px;
  transition:all .3s;
  letter-spacing:.5px;
}
.mc-actionbar{
  position:absolute;bottom:30px;left:50%;transform:translateX(-50%);z-index:2;
  font-family:'Share Tech Mono',monospace;font-size:13px;font-weight:700;
  text-shadow:1px 1px 0 #000,-1px -1px 0 #000;
  white-space:nowrap;background:rgba(0,0,0,0.5);padding:3px 10px;border-radius:3px;
  transition:all .3s;
}
.mc-hud{position:absolute;bottom:0;left:0;right:0;height:40px;background:rgba(0,0,0,0.4);display:flex;align-items:center;padding:0 10px;gap:4px}
.mc-heart{font-size:12px}
.mc-hotbar{position:absolute;bottom:6px;left:50%;transform:translateX(-50%);display:flex;gap:3px}
.mc-slot{width:24px;height:24px;border:1px solid #888;background:rgba(50,50,50,0.7);border-radius:2px}
.mc-slot.active{border-color:#fff}
.color-row{display:flex;flex-wrap:wrap;gap:4px;margin-top:5px}
.cs{width:22px;height:22px;border-radius:4px;cursor:pointer;border:2px solid transparent;transition:all .12s;flex-shrink:0}
.cs:hover,.cs.active{border-color:#fff;transform:scale(1.15)}
.type-tabs{display:flex;gap:4px;margin-bottom:14px}
.ttab{padding:7px 14px;font-size:12px;font-weight:700;background:var(--bg3);border:1px solid var(--border);color:var(--text3);border-radius:8px;cursor:pointer;transition:all .15s;font-family:var(--body)}
.ttab:hover{border-color:var(--green);color:var(--green)}
.ttab.active{background:rgba(74,222,128,0.1);border-color:var(--green);color:var(--green)}
.timing-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.format-btns{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px}
.fmt-btn{padding:4px 10px;font-size:11px;font-weight:700;border-radius:6px;border:1px solid var(--border);background:var(--bg3);cursor:pointer;font-family:var(--mono);transition:all .15s}
.fmt-btn:hover{border-color:var(--purple);color:var(--purple)}
.multi-output{display:flex;flex-direction:column;gap:6px}
.multi-cmd{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px}
.multi-cmd-text{font-family:var(--mono);font-size:12px;color:var(--gold);flex:1;word-break:break-all}
.multi-cmd-copy{padding:4px 9px;font-size:10px;background:rgba(74,222,128,0.08);border:1px solid rgba(74,222,128,0.3);color:var(--green);border-radius:5px;cursor:pointer;font-family:var(--mono)}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">✍️ <span>Title Generator</span></div>
  <div class="page-sub">// Buat in-game title · subtitle · actionbar dengan live preview</div>
</div>

<div class="content">
<div class="title-layout">

<!-- LEFT: CONFIG -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- TARGET -->
  <div class="card">
    <div class="card-header"><span style="color:var(--gold)">●</span> TARGET</div>
    <div class="card-body">
      <div class="row">
        <div class="field">
          <label>Player / Selector</label>
          <input type="text" id="tg-target" value="<?= $defaultTarget ?>" oninput="buildTitle()">
        </div>
      </div>
      <div class="type-tabs">
        <button class="ttab active" onclick="setType('full',this)">Title + Subtitle</button>
        <button class="ttab" onclick="setType('title',this)">Title Only</button>
        <button class="ttab" onclick="setType('subtitle',this)">Subtitle Only</button>
        <button class="ttab" onclick="setType('actionbar',this)">Action Bar</button>
      </div>
    </div>
  </div>

  <!-- TITLE TEXT -->
  <div class="card" id="title-section">
    <div class="card-header"><span style="color:var(--green)">●</span> TITLE TEXT</div>
    <div class="card-body">
      <div class="field" style="margin-bottom:8px">
        <label>Title</label>
        <div class="format-btns">
          <button class="fmt-btn" onclick="insertFormat('title','bold')"><b>B</b></button>
          <button class="fmt-btn" onclick="insertFormat('title','italic')"><i>I</i></button>
          <button class="fmt-btn" onclick="insertFormat('title','underlined')"><u>U</u></button>
          <button class="fmt-btn" onclick="insertFormat('title','strikethrough')"><s>S</s></button>
          <button class="fmt-btn" onclick="insertFormat('title','obfuscated')" title="Scrambled text">§k</button>
        </div>
        <input type="text" id="title-text" placeholder="e.g. WELCOME" oninput="buildTitle()">
      </div>
      <div class="field">
        <label>Title Color</label>
        <div class="color-row" id="title-colors"></div>
      </div>
      <div style="margin-top:10px;display:flex;gap:12px;flex-wrap:wrap">
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="title-bold" onchange="buildTitle()" style="width:auto;accent-color:var(--green)"> Bold
        </label>
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="title-italic" onchange="buildTitle()" style="width:auto;accent-color:var(--green)"> Italic
        </label>
      </div>
    </div>
  </div>

  <!-- SUBTITLE TEXT -->
  <div class="card" id="subtitle-section">
    <div class="card-header"><span style="color:var(--blue)">●</span> SUBTITLE TEXT</div>
    <div class="card-body">
      <div class="field" style="margin-bottom:8px">
        <label>Subtitle</label>
        <div class="format-btns">
          <button class="fmt-btn" onclick="insertFormat('subtitle','bold')"><b>B</b></button>
          <button class="fmt-btn" onclick="insertFormat('subtitle','italic')"><i>I</i></button>
          <button class="fmt-btn" onclick="insertFormat('subtitle','underlined')"><u>U</u></button>
        </div>
        <input type="text" id="subtitle-text" placeholder="e.g. to the server" oninput="buildTitle()">
      </div>
      <div class="field">
        <label>Subtitle Color</label>
        <div class="color-row" id="subtitle-colors"></div>
      </div>
      <div style="margin-top:10px;display:flex;gap:12px;flex-wrap:wrap">
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="sub-bold" onchange="buildTitle()" style="width:auto;accent-color:var(--green)"> Bold
        </label>
        <label style="font-size:12px;color:var(--text2);cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="sub-italic" onchange="buildTitle()" style="width:auto;accent-color:var(--green)"> Italic
        </label>
      </div>
    </div>
  </div>

  <!-- ACTIONBAR -->
  <div class="card" id="actionbar-section" style="display:none">
    <div class="card-header"><span style="color:var(--orange)">●</span> ACTION BAR TEXT</div>
    <div class="card-body">
      <div class="field" style="margin-bottom:8px">
        <label>Action Bar Message</label>
        <input type="text" id="ab-text" placeholder="e.g. ❤ Health: 20/20" oninput="buildTitle()">
      </div>
      <div class="field">
        <label>Color</label>
        <div class="color-row" id="ab-colors"></div>
      </div>
    </div>
  </div>

  <!-- TIMING -->
  <div class="card" id="timing-section">
    <div class="card-header"><span style="color:var(--teal)">●</span> TIMING (ticks, 20 ticks = 1 saat)</div>
    <div class="card-body">
      <div class="timing-grid">
        <div class="field">
          <label>Fade In (ticks)</label>
          <input type="number" id="tg-fadein" value="10" min="0" max="100" oninput="buildTitle()">
        </div>
        <div class="field">
          <label>Stay (ticks)</label>
          <input type="number" id="tg-stay" value="70" min="1" max="1000" oninput="buildTitle()">
        </div>
        <div class="field">
          <label>Fade Out (ticks)</label>
          <input type="number" id="tg-fadeout" value="20" min="0" max="100" oninput="buildTitle()">
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="setTiming(5,40,10)">Quick</button>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="setTiming(10,70,20)">Normal</button>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="setTiming(20,100,20)">Slow</button>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2)" onclick="setTiming(20,200,30)">Dramatic</button>
      </div>
    </div>
  </div>

  <!-- QUICK PRESETS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--pink)">●</span> QUICK PRESETS</div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
        <?php
        $titlePresets = [
          ['Welcome',       '🎉 Welcome',    'WELCOME',      'gold',   'to the server', 'yellow'],
          ['Game Start',    '⚔️ Game Start', 'GAME STARTS',  'green',  'Good luck!', 'white'],
          ['Game Over',     '💀 Game Over',  'GAME OVER',    'red',    'Better luck next time','gray'],
          ['Winner',        '🏆 Winner',     'YOU WIN!',     'gold',   '🎊 Congratulations','yellow'],
          ['Night Falls',   '🌙 Night',      'NIGHT FALLS',  'dark_purple','Beware the dark','gray'],
          ['Boss Warning',  '⚠️ Boss',       'BOSS INCOMING','dark_red','Run... or fight','red'],
        ];
        foreach($titlePresets as [$key,$label,$title,$tcolor,$sub,$scolor]):
        ?>
        <button class="btn btn-sm" style="background:var(--bg3);border-color:var(--border2);color:var(--text2);text-align:left"
          onclick="loadTitlePreset('<?= $title ?>','<?= $tcolor ?>','<?= $sub ?>','<?= $scolor ?>')">
          <?= $label ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT: PREVIEW + OUTPUT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- PREVIEW -->
  <div class="card">
    <div class="card-header"><span style="color:var(--teal)">●</span> MINECRAFT PREVIEW</div>
    <div class="card-body" style="padding:12px">
      <div class="mc-preview">
        <div class="mc-title-text" id="prev-title" style="color:#FFD700">WELCOME</div>
        <div class="mc-subtitle-text" id="prev-subtitle" style="color:#FFFFFF">to the server</div>
        <div class="mc-actionbar" id="prev-actionbar" style="display:none"></div>
        <div class="mc-hotbar">
          <?php for($i=0;$i<9;$i++): ?>
          <div class="mc-slot <?= $i===0?'active':'' ?>"></div>
          <?php endfor; ?>
        </div>
      </div>
      <div style="text-align:center;margin-top:8px;font-size:10px;color:var(--text3);font-family:var(--mono)">
        Preview (approximate) · Actual ingame mungkin berbeza sedikit
      </div>
    </div>
  </div>

  <!-- OUTPUT -->
  <div class="card" style="position:sticky;top:76px">
    <div class="card-header"><span style="color:var(--gold)">●</span> GENERATED COMMANDS</div>
    <div class="card-body">

      <div class="multi-output" id="title-outputs"></div>

      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <button class="btn btn-green btn-lg" onclick="copyAllTitle()">📋 Copy All</button>
        <button class="btn btn-gold btn-sm" onclick="copyTitleSeq()">⚡ One-liner</button>
      </div>

      <div class="divider"></div>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px">💡 CARA GUNA</div>
        <div style="font-size:11px;color:var(--text2);line-height:1.9;font-family:var(--mono)">
          1. Set the timing first with /title times<br>
          2. Then send /title subtitle<br>
          3. Finally send /title title<br>
          4. Subtitle mesti sebelum title!
        </div>
      </div>
    </div>
  </div>

</div><!-- /right -->
</div><!-- /title-layout -->
</div><!-- /content -->

<script>
const MC_COLORS = [
  {code:'black',hex:'#000000'},{code:'dark_blue',hex:'#0000AA'},
  {code:'dark_green',hex:'#00AA00'},{code:'dark_aqua',hex:'#00AAAA'},
  {code:'dark_red',hex:'#AA0000'},{code:'dark_purple',hex:'#AA00AA'},
  {code:'gold',hex:'#FFAA00'},{code:'gray',hex:'#AAAAAA'},
  {code:'dark_gray',hex:'#555555'},{code:'blue',hex:'#5555FF'},
  {code:'green',hex:'#55FF55'},{code:'aqua',hex:'#55FFFF'},
  {code:'red',hex:'#FF5555'},{code:'light_purple',hex:'#FF55FF'},
  {code:'yellow',hex:'#FFFF55'},{code:'white',hex:'#FFFFFF'},
];

let titleColor='gold', subtitleColor='white', abColor='yellow';
let curType='full';
let generatedCmds=[];

function initColorRow(containerId, selectedCode, setter){
  const wrap=document.getElementById(containerId);
  MC_COLORS.forEach(c=>{
    const s=document.createElement('div');
    s.className='cs'+(c.code===selectedCode?' active':'');
    s.style.background=c.hex; s.title=c.code;
    s.onclick=()=>{
      setter(c.code);
      wrap.querySelectorAll('.cs').forEach(x=>x.classList.remove('active'));
      s.classList.add('active');
      buildTitle(); playClick();
    };
    wrap.appendChild(s);
  });
}

initColorRow('title-colors','gold',c=>titleColor=c);
initColorRow('subtitle-colors','white',c=>subtitleColor=c);
initColorRow('ab-colors','yellow',c=>abColor=c);

function v(id){ return document.getElementById(id)?.value||'' }
function chk(id){ return document.getElementById(id)?.checked||false }

function setType(type, btn){
  curType=type;
  document.querySelectorAll('.ttab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('title-section').style.display=(type==='subtitle'||type==='actionbar')?'none':'';
  document.getElementById('subtitle-section').style.display=(type==='title'||type==='actionbar')?'none':'';
  document.getElementById('actionbar-section').style.display=type==='actionbar'?'':'none';
  document.getElementById('timing-section').style.display=type==='actionbar'?'none':'';
  buildTitle(); playClick();
}

function setTiming(fi,stay,fo){
  document.getElementById('tg-fadein').value=fi;
  document.getElementById('tg-stay').value=stay;
  document.getElementById('tg-fadeout').value=fo;
  buildTitle(); playClick();
}

function makeJson(text, color, bold, italic){
  const obj={text, color};
  if(bold) obj.bold=true;
  if(italic) obj.italic=true;
  return JSON.stringify(obj);
}

function insertFormat(field, fmt){
  const input = document.getElementById(field+'-text')||document.getElementById('ab-text');
  if(!input) return;
  const map={bold:'**',italic:'_',underlined:'__',strikethrough:'~~',obfuscated:'??'};
  // Just append a hint since Minecraft uses JSON not markdown
  // For simplicity insert format indicator in placeholder
  input.focus();
}

function buildTitle(){
  const target=v('tg-target')||'@p';
  const fi=v('tg-fadein')||10;
  const stay=v('tg-stay')||70;
  const fo=v('tg-fadeout')||20;
  const titleTxt=v('title-text');
  const subTxt=v('subtitle-text');
  const abTxt=v('ab-text');
  const titleBold=chk('title-bold');
  const titleItalic=chk('title-italic');
  const subBold=chk('sub-bold');
  const subItalic=chk('sub-italic');

  const cmds=[];

  if(curType==='actionbar'){
    const txt=abTxt||'Action Bar Text';
    const json=makeJson(txt,abColor,false,false);
    cmds.push(`/title ${target} actionbar ${json}`);
    // Update preview
    document.getElementById('prev-title').style.display='none';
    document.getElementById('prev-subtitle').style.display='none';
    const ab=document.getElementById('prev-actionbar');
    ab.style.display='';
    const c=MC_COLORS.find(x=>x.code===abColor);
    ab.style.color=c?.hex||'#FFFF55';
    ab.textContent=txt;
  } else {
    // Clear actionbar preview
    document.getElementById('prev-actionbar').style.display='none';
    document.getElementById('prev-title').style.display='';
    document.getElementById('prev-subtitle').style.display='';

    // Times
    cmds.push(`/title ${target} times ${fi} ${stay} ${fo}`);

    // Subtitle first (must be before title)
    if((curType==='full'||curType==='subtitle') && subTxt){
      const json=makeJson(subTxt,subtitleColor,subBold,subItalic);
      cmds.push(`/title ${target} subtitle ${json}`);
    }

    // Title
    if((curType==='full'||curType==='title') && titleTxt){
      const json=makeJson(titleTxt,titleColor,titleBold,titleItalic);
      cmds.push(`/title ${target} title ${json}`);
    }

    // Update preview
    const tc=MC_COLORS.find(x=>x.code===titleColor);
    const sc=MC_COLORS.find(x=>x.code===subtitleColor);
    const prevT=document.getElementById('prev-title');
    const prevS=document.getElementById('prev-subtitle');
    prevT.textContent=titleTxt||'Your Title Here';
    prevT.style.color=tc?.hex||'#FFAA00';
    prevT.style.fontWeight=titleBold?'900':'700';
    prevT.style.fontStyle=titleItalic?'italic':'normal';
    prevS.textContent=subTxt||'Your subtitle here';
    prevS.style.color=sc?.hex||'#FFFFFF';
    prevS.style.fontWeight=subBold?'900':'400';
    prevS.style.fontStyle=subItalic?'italic':'normal';
  }

  generatedCmds=cmds;

  // Render output
  const out=document.getElementById('title-outputs');
  if(cmds.length===0){
    out.innerHTML='<div style="color:var(--text3);font-family:var(--mono);font-size:12px">// Fill in text above</div>';
    return;
  }
  out.innerHTML=cmds.map((cmd,i)=>`
    <div class="multi-cmd">
      <span style="font-size:10px;font-family:var(--mono);color:var(--text3);min-width:20px">${i+1}.</span>
      <span class="multi-cmd-text">${cmd}</span>
      <button class="multi-cmd-copy" onclick="copyText('${cmd.replace(/'/g,"\\'").replace(/"/g,'&quot;')}')">Copy</button>
    </div>`).join('');
}

function copyAllTitle(){
  if(!generatedCmds.length){ playError(); return; }
  const all=generatedCmds.join('\n');
  navigator.clipboard.writeText(all).catch(()=>{});
  playCopy();
  showToast(`✅ ${generatedCmds.length} commands copied!`);
}

function copyTitleSeq(){
  if(!generatedCmds.length){ playError(); return; }
  const seq=generatedCmds.map(c=>c.replace(/^\//,'')).join(' && ');
  navigator.clipboard.writeText(seq).catch(()=>{});
  playCopy();
  showToast('✅ Sequence copied!');
}

function loadTitlePreset(title,tcolor,sub,scolor){
  document.getElementById('title-text').value=title;
  document.getElementById('subtitle-text').value=sub;
  titleColor=tcolor; subtitleColor=scolor;
  // Update color swatches
  ['title-colors','subtitle-colors'].forEach((cid,i)=>{
    const col=i===0?tcolor:scolor;
    document.querySelectorAll('#'+cid+' .cs').forEach((s,j)=>{
      s.classList.toggle('active',MC_COLORS[j]?.code===col);
    });
  });
  // Ensure full type
  document.querySelectorAll('.ttab')[0].click();
  buildTitle(); playSelect();
  showToast('✅ Preset loaded!');
}

buildTitle();
</script>
</body>
</html>
