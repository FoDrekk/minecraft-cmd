<?php
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Firework Designer — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.fw-layout{display:grid;grid-template-columns:1fr 380px;gap:20px}
@media(max-width:920px){.fw-layout{grid-template-columns:1fr}}
.color-palette{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px}
.fw-color{width:28px;height:28px;border-radius:6px;cursor:pointer;border:2px solid transparent;transition:all .15s;flex-shrink:0;position:relative}
.fw-color:hover{transform:scale(1.15);border-color:rgba(255,255,255,0.4)}
.fw-color.selected{border-color:#fff;box-shadow:0 0 8px rgba(255,255,255,0.4)}
.fw-color .check{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:13px;display:none}
.fw-color.selected .check{display:flex}
.color-section{background:var(--bg3);border:1px solid var(--border2);border-radius:10px;padding:12px;margin-bottom:10px}
.color-section-title{font-size:11px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:8px}
.shape-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:6px;margin-bottom:12px}
.shape-card{
  border:1px solid var(--border2);border-radius:8px;background:var(--bg3);
  padding:10px 8px;cursor:pointer;transition:all .15s;text-align:center;
}
.shape-card:hover{border-color:var(--border3)}
.shape-card.selected{border-color:var(--orange);background:rgba(240,135,74,0.08);box-shadow:0 0 10px rgba(240,135,74,0.1)}
.shape-icon{font-size:22px;margin-bottom:4px}
.shape-name{font-size:11px;font-weight:700;color:var(--text2)}
.shape-desc{font-size:10px;color:var(--text3);margin-top:2px}
/* FIREWORK PREVIEW CANVAS */
.fw-preview{
  background:radial-gradient(ellipse at center,#0a0a1a 0%,#000005 100%);
  border:1px solid var(--border2);border-radius:12px;
  height:280px;position:relative;overflow:hidden;
}
.fw-canvas{width:100%;height:100%}
.fx-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-family:var(--mono);background:var(--bg3);border:1px solid var(--border2);color:var(--text2);margin:3px}
.fx-badge.active{background:rgba(240,135,74,0.1);border-color:rgba(240,135,74,0.35);color:var(--orange)}
.effect-toggles{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">🎆 <span>Firework Designer</span></div>
  <div class="page-sub">// Design a firework rocket — colours, shape and effects — then copy the /give command</div>
</div>

<div class="content">
<div class="fw-layout">

<!-- LEFT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <div class="card">
    <div class="card-header"><span style="color:var(--gold)">●</span> TARGET & QUANTITY</div>
    <div class="card-body">
      <div class="row">
        <div class="field"><label>Player</label><input type="text" id="fw-target" value="<?= $defaultTarget ?>" oninput="buildFw()"></div>
        <div class="field"><label>Quantity (1–64)</label><input type="number" id="fw-qty" value="8" min="1" max="64" oninput="buildFw()"></div>
        <div class="field">
          <label>Flight Duration</label>
          <select id="fw-flight" onchange="buildFw()">
            <option value="1">1 — Low</option>
            <option value="2" selected>2 — Medium</option>
            <option value="3">3 — High</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- SHAPE -->
  <div class="card">
    <div class="card-header"><span style="color:var(--orange)">●</span> EXPLOSION SHAPE</div>
    <div class="card-body">
      <div class="shape-grid" id="shape-grid"></div>
    </div>
  </div>

  <!-- COLORS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--pink)">●</span> COLORS</div>
    <div class="card-body">
      <div class="color-section">
        <div class="color-section-title">🎨 Explosion Colors (pilih 1–8)</div>
        <div class="color-palette" id="primary-colors"></div>
      </div>
      <div class="color-section">
        <div class="color-section-title">✨ Fade Colors (optional)</div>
        <div class="color-palette" id="fade-colors"></div>
      </div>
    </div>
  </div>

  <!-- EFFECTS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--purple)">●</span> SPECIAL EFFECTS</div>
    <div class="card-body">
      <div class="effect-toggles">
        <button class="fx-badge" id="fx-trail" onclick="toggleFx('trail',this)">💫 Trail</button>
        <button class="fx-badge" id="fx-twinkle" onclick="toggleFx('twinkle',this)">✨ Twinkle</button>
      </div>
      <p class="hint">Trail = tinggal jejak · Twinkle = berkelip masa meletup</p>
    </div>
  </div>

  <!-- PRESET -->
  <div class="card">
    <div class="card-header"><span style="color:var(--teal)">●</span> QUICK PRESETS</div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:6px">
        <button class="btn btn-ghost" onclick="loadPreset('malaysia')">🇲🇾 Malaysia</button>
        <button class="btn btn-ghost" onclick="loadPreset('rainbow')">🌈 Rainbow</button>
        <button class="btn btn-ghost" onclick="loadPreset('gold')">🌟 Gold Star</button>
        <button class="btn btn-ghost" onclick="loadPreset('creeper')">💚 Creeper</button>
        <button class="btn btn-ghost" onclick="loadPreset('galaxy')">🌌 Galaxy</button>
        <button class="btn btn-ghost" onclick="loadPreset('fire')">🔥 Fire</button>
      </div>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- PREVIEW -->
  <div class="card">
    <div class="card-header"><span style="color:var(--teal)">●</span> LIVE PREVIEW</div>
    <div class="card-body" style="padding:10px">
      <div class="fw-preview">
        <canvas class="fw-canvas" id="fw-canvas"></canvas>
      </div>
      <div style="text-align:center;margin-top:8px">
        <button class="btn btn-sm btn-orange" style="background:rgba(240,135,74,0.1);border-color:rgba(240,135,74,0.35);color:var(--orange)" onclick="launchPreview()">🚀 Launch Preview</button>
      </div>
    </div>
  </div>

  <!-- OUTPUT -->
  <div class="card" style="position:sticky;top:70px">
    <div class="card-header"><span style="color:var(--gold)">●</span> GENERATED COMMAND</div>
    <div class="card-body">
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px 12px;margin-bottom:10px">
        <div style="font-size:10px;font-family:var(--mono);color:var(--text3);letter-spacing:1px;margin-bottom:6px">SUMMARY</div>
        <div style="font-size:12px;color:var(--text2);font-family:var(--mono);line-height:1.9" id="fw-summary">—</div>
      </div>
      <div class="cmd-output">
        <div class="cmd-output-header"><span>// /give command</span></div>
        <div class="cmd-output-body" id="fw-output">/give nizkbiits firework_rocket 8</div>
        <div class="cmd-output-actions">
          <button class="btn btn-green" onclick="copyText(document.getElementById('fw-output').textContent)">📋 Copy</button>
        </div>
      </div>
      <p class="hint">💡 Fireworks can be fired from a crossbow or thrown by hand</p>
    </div>
  </div>

</div>
</div><!-- /fw-layout -->
</div><!-- /content -->

<script>
// ── FIREWORK COLORS (Minecraft dye colors) ──
const FW_COLORS = [
  {name:'White',     id:'white',     hex:'#F9FFFE', val:16383998},
  {name:'Light Gray',id:'light_gray',hex:'#9D9D97', val:10329495},
  {name:'Gray',      id:'gray',      hex:'#474F52', val:4673362},
  {name:'Black',     id:'black',     hex:'#1D1D21', val:1973019},
  {name:'Brown',     id:'brown',     hex:'#835432', val:8606770},
  {name:'Red',       id:'red',       hex:'#B02E26', val:11743532},
  {name:'Orange',    id:'orange',    hex:'#F9801D', val:16351261},
  {name:'Yellow',    id:'yellow',    hex:'#FED83D', val:16701501},
  {name:'Lime',      id:'lime',      hex:'#80C71F', val:8439583},
  {name:'Green',     id:'green',     hex:'#5E7C16', val:6192150},
  {name:'Cyan',      id:'cyan',      hex:'#169C9C', val:1481884},
  {name:'Light Blue',id:'light_blue',hex:'#3AB3DA', val:3847130},
  {name:'Blue',      id:'blue',      hex:'#3C44AA', val:3949738},
  {name:'Purple',    id:'purple',    hex:'#8932B8', val:8991416},
  {name:'Magenta',   id:'magenta',   hex:'#C74EBD', val:13061821},
  {name:'Pink',      id:'pink',      hex:'#F38BAA', val:15961002},
];

const SHAPES = [
  {id:0, icon:'💥', name:'Small Ball',   desc:'Standard burst'},
  {id:1, icon:'🌸', name:'Large Ball',   desc:'Big explosion'},
  {id:2, icon:'⭐', name:'Star',         desc:'Star shape'},
  {id:3, icon:'💀', name:'Creeper',      desc:'Creeper face'},
  {id:4, icon:'💎', name:'Burst',        desc:'Feather burst'},
];

const PRESETS = {
  malaysia: {shape:1,primary:['red','white','yellow'],fade:[],trail:false,twinkle:false,flight:2},
  rainbow:  {shape:0,primary:['red','orange','yellow','lime','cyan','blue','purple','pink'],fade:[],trail:true,twinkle:false,flight:2},
  gold:     {shape:2,primary:['yellow','orange'],fade:['white'],trail:true,twinkle:true,flight:2},
  creeper:  {shape:3,primary:['lime','green'],fade:['black'],trail:false,twinkle:false,flight:1},
  galaxy:   {shape:1,primary:['blue','purple','light_blue'],fade:['white','light_gray'],trail:true,twinkle:true,flight:3},
  fire:     {shape:0,primary:['red','orange','yellow'],fade:['gray','black'],trail:true,twinkle:false,flight:2},
};

let selectedPrimary = new Set(['red','white']);
let selectedFade = new Set();
let selectedShape = 0;
let fxTrail = false;
let fxTwinkle = false;

// Build color palettes
function buildPalette(containerId, selectedSet) {
  const wrap = document.getElementById(containerId);
  wrap.innerHTML = '';
  FW_COLORS.forEach(c => {
    const d = document.createElement('div');
    d.className = 'fw-color' + (selectedSet.has(c.id) ? ' selected' : '');
    d.style.background = c.hex;
    d.title = c.name;
    d.innerHTML = `<span class="check">✓</span>`;
    d.onclick = () => {
      if(selectedSet.has(c.id)) { selectedSet.delete(c.id); d.classList.remove('selected'); }
      else { if(selectedSet.size < 8) { selectedSet.add(c.id); d.classList.add('selected'); } }
      buildFw(); playClick();
    };
    wrap.appendChild(d);
  });
}

function buildShapeGrid() {
  const grid = document.getElementById('shape-grid');
  grid.innerHTML = '';
  SHAPES.forEach(s => {
    const d = document.createElement('div');
    d.className = 'shape-card' + (s.id === selectedShape ? ' selected' : '');
    d.innerHTML = `<div class="shape-icon">${s.icon}</div><div class="shape-name">${s.name}</div><div class="shape-desc">${s.desc}</div>`;
    d.onclick = () => {
      selectedShape = s.id;
      document.querySelectorAll('.shape-card').forEach(c => c.classList.remove('selected'));
      d.classList.add('selected');
      buildFw(); playSelect();
    };
    grid.appendChild(d);
  });
}

function toggleFx(type, btn) {
  if(type==='trail') { fxTrail=!fxTrail; btn.classList.toggle('active',fxTrail); }
  else { fxTwinkle=!fxTwinkle; btn.classList.toggle('active',fxTwinkle); }
  buildFw(); playClick();
}

document.addEventListener('mc:version', () => buildFw());

function buildFw() {
  const target = document.getElementById('fw-target').value || '@p';
  const qty = document.getElementById('fw-qty').value || 1;
  const flight = document.getElementById('fw-flight').value || 2;

  const primColors = FW_COLORS.filter(c => selectedPrimary.has(c.id));
  const fadeColors = FW_COLORS.filter(c => selectedFade.has(c.id));

  if(primColors.length === 0) {
    document.getElementById('fw-output').textContent = '/give '+target+' firework_rocket '+qty;
    document.getElementById('fw-summary').textContent = '— Pick at least one colour —';
    return;
  }

  const colorVals = primColors.map(c => c.val);
  const fadeVals = fadeColors.map(c => c.val);

  // The component form names the shape; the old NBT form used a numeric
  // Type in the same order.
  const SHAPE_IDS = ['small_ball', 'large_ball', 'star', 'creeper', 'burst'];

  let item;
  if (MC.syn().items === 'components') {
    const explosion = { shape: SHAPE_IDS[selectedShape] || 'small_ball', colors: MC.intArray(colorVals) };
    if (fadeColors.length) explosion.fade_colors = MC.intArray(fadeVals);
    if (fxTrail) explosion.has_trail = true;
    if (fxTwinkle) explosion.has_twinkle = true;
    item = MC.item('firework_rocket', {
      components: { fireworks: { flight_duration: MC.byte(flight), explosions: [explosion] } }
    });
  } else {
    const explosion = { Type: MC.byte(selectedShape), Colors: MC.intArray(colorVals) };
    if (fadeColors.length) explosion.FadeColors = MC.intArray(fadeVals);
    if (fxTrail) explosion.Trail = MC.byte(1);
    if (fxTwinkle) explosion.Flicker = MC.byte(1);
    item = 'firework_rocket' + MC.snbt({ Fireworks: { Flight: MC.byte(flight), Explosions: [explosion] } });
  }

  const cmd = `/give ${target} ${item} ${qty}`;

  document.getElementById('fw-output').textContent = cmd;

  const shapeObj = SHAPES.find(s => s.id === selectedShape);
  document.getElementById('fw-summary').innerHTML =
    `Shape: <span style="color:var(--orange)">${shapeObj?.name}</span> &nbsp;·&nbsp; ` +
    `Colors: <span style="color:var(--pink)">${primColors.map(c=>`<span style="display:inline-block;width:10px;height:10px;background:${c.hex};border-radius:2px;vertical-align:middle"></span>`).join(' ')}</span> &nbsp;·&nbsp; ` +
    `Flight: <span style="color:var(--gold)">${flight}</span>` +
    (fxTrail ? ' &nbsp;·&nbsp; <span style="color:var(--purple)">Trail</span>' : '') +
    (fxTwinkle ? ' &nbsp;·&nbsp; <span style="color:var(--teal)">Twinkle</span>' : '');

  drawPreview(primColors.map(c=>c.hex));
}

function loadPreset(key) {
  const p = PRESETS[key]; if(!p) return;
  selectedPrimary = new Set(p.primary);
  selectedFade = new Set(p.fade);
  selectedShape = p.shape;
  fxTrail = p.trail; fxTwinkle = p.twinkle;
  document.getElementById('fw-flight').value = p.flight;
  document.getElementById('fx-trail').classList.toggle('active', fxTrail);
  document.getElementById('fx-twinkle').classList.toggle('active', fxTwinkle);
  buildPalette('primary-colors', selectedPrimary);
  buildPalette('fade-colors', selectedFade);
  buildShapeGrid();
  buildFw(); playSelect();
  showToast('✅ Preset loaded!');
}

// ── CANVAS PREVIEW ──
const canvas = document.getElementById('fw-canvas');
const ctx = canvas.getContext('2d');
let particles = [];
let animId = null;

function resizeCanvas() {
  canvas.width = canvas.offsetWidth;
  canvas.height = canvas.offsetHeight;
}

function launchPreview() {
  const primColors = FW_COLORS.filter(c => selectedPrimary.has(c.id));
  if(!primColors.length) { showToast('⚠️ Pick at least one colour first.','var(--red)'); return; }
  const colors = primColors.map(c => c.hex);
  resizeCanvas();
  spawnFirework(colors);
  if(!animId) animate();
  playSelect();
}

function spawnFirework(colors) {
  const cx = canvas.width * (0.3 + Math.random() * 0.4);
  const cy = canvas.height * (0.15 + Math.random() * 0.35);
  const count = selectedShape === 1 ? 80 : selectedShape === 2 ? 60 : 50;
  for(let i = 0; i < count; i++) {
    const angle = (i / count) * Math.PI * 2;
    const speed = 2 + Math.random() * 3;
    const color = colors[Math.floor(Math.random() * colors.length)];
    particles.push({
      x: cx, y: cy,
      vx: Math.cos(angle) * speed * (selectedShape === 2 ? 1.2 : 1),
      vy: Math.sin(angle) * speed * (selectedShape === 2 ? 1.2 : 1),
      alpha: 1, color, trail: fxTrail,
      twinkle: fxTwinkle, size: 2 + Math.random() * 2,
      life: 1, decay: 0.015 + Math.random() * 0.01,
      trailPoints: [],
    });
  }
}

function animate() {
  ctx.fillStyle = 'rgba(0,0,0,0.15)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  particles = particles.filter(p => p.life > 0);
  particles.forEach(p => {
    if(p.trail) {
      p.trailPoints.push({x:p.x, y:p.y, a:p.life});
      if(p.trailPoints.length > 8) p.trailPoints.shift();
      p.trailPoints.forEach((pt, i) => {
        ctx.globalAlpha = pt.a * (i / p.trailPoints.length) * 0.4;
        ctx.fillStyle = p.color;
        ctx.beginPath(); ctx.arc(pt.x, pt.y, p.size * 0.6, 0, Math.PI*2); ctx.fill();
      });
    }
    const twinkAlpha = p.twinkle ? p.life * (0.5 + 0.5 * Math.sin(p.life * 40)) : p.life;
    ctx.globalAlpha = twinkAlpha;
    ctx.fillStyle = p.color;
    ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI*2); ctx.fill();
    p.x += p.vx; p.y += p.vy;
    p.vy += 0.04; p.vx *= 0.98; p.vy *= 0.98;
    p.life -= p.decay;
  });
  ctx.globalAlpha = 1;
  if(particles.length > 0) { animId = requestAnimationFrame(animate); }
  else { animId = null; }
}

function drawPreview(colors) {
  resizeCanvas();
  // Just show a static sparkle hint
  ctx.clearRect(0,0,canvas.width,canvas.height);
  ctx.fillStyle = '#000005';
  ctx.fillRect(0,0,canvas.width,canvas.height);
  if(!colors.length) return;
  // Draw small dots
  for(let i = 0; i < 30; i++) {
    const c = colors[i % colors.length];
    ctx.globalAlpha = 0.3 + Math.random() * 0.5;
    ctx.fillStyle = c;
    ctx.beginPath();
    ctx.arc(Math.random()*canvas.width, Math.random()*canvas.height, 1+Math.random()*2, 0, Math.PI*2);
    ctx.fill();
  }
  ctx.globalAlpha = 1;
}

// Init
buildShapeGrid();
buildPalette('primary-colors', selectedPrimary);
buildPalette('fade-colors', selectedFade);
buildFw();
resizeCanvas();
</script>
</body>
</html>
