<?php
require_once __DIR__ . '/db.php';
$dbStatus = dbStatus();
$dbOk     = $dbStatus['connected'];
$history  = $dbOk ? historyGet(5)  : [];
$favs     = $dbOk ? favGet()        : [];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Minecraft CMD Gen</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
/* ── DASHBOARD ── */
.dash-layout {
  position:relative;z-index:1;
  padding:20px 24px 60px;max-width:1360px;margin:0 auto;
}
.dash-hero {
  display:flex;align-items:center;justify-content:space-between;
  padding:28px 32px;border-radius:var(--r-xl);
  background:linear-gradient(135deg,rgba(93,190,122,0.08),rgba(56,196,184,0.05));
  border:1px solid rgba(93,190,122,0.15);
  margin-bottom:24px;gap:24px;flex-wrap:wrap;
}
.dash-hero-left h1 {
  font-size:28px;font-weight:900;letter-spacing:-.5px;
  background:linear-gradient(120deg,var(--green),var(--teal));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  margin-bottom:6px;
}
.dash-hero-left p { font-size:13px;color:var(--text3);font-family:var(--mono); }
.dash-stats { display:flex;gap:12px;flex-wrap:wrap; }
.stat-pill {
  display:flex;align-items:center;gap:8px;
  padding:8px 14px;border-radius:20px;
  background:rgba(255,255,255,0.03);border:1px solid var(--border2);
}
.stat-num { font-size:18px;font-weight:800;font-family:var(--mono); }
.stat-lbl { font-size:11px;color:var(--text3);font-family:var(--mono);letter-spacing:.5px; }

/* TOOL GRID */
.tools-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
  gap:16px;margin-bottom:24px;
}
.tool-card {
  background:var(--card);border:1px solid var(--border2);
  border-radius:var(--r-lg);overflow:hidden;
  transition:all .2s;cursor:pointer;text-decoration:none;display:block;
}
.tool-card:hover { border-color:var(--accent,var(--green));transform:translateY(-2px);box-shadow:var(--shadow-lg) }
.tool-card-head {
  padding:16px 18px 14px;
  display:flex;align-items:center;gap:12px;
  border-bottom:1px solid var(--border);
}
.tool-icon {
  width:40px;height:40px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;flex-shrink:0;
}
.tool-name { font-size:15px;font-weight:800;margin-bottom:2px; }
.tool-desc { font-size:11px;color:var(--text3);font-family:var(--mono); }
.tool-body { padding:14px 18px; }
.tool-chips { display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px; }
.tool-chip {
  font-size:10px;font-family:var(--mono);
  padding:3px 8px;border-radius:4px;font-weight:600;
  background:rgba(255,255,255,0.04);border:1px solid var(--border2);
  color:var(--text3);
}
.tool-footer {
  display:flex;align-items:center;justify-content:space-between;
  padding:10px 18px;border-top:1px solid var(--border);
  background:rgba(255,255,255,0.01);
}
.tool-open-btn {
  font-size:11px;font-weight:700;font-family:var(--mono);
  color:var(--accent,var(--green));
  display:flex;align-items:center;gap:5px;
}

/* BOTTOM PANELS */
.bottom-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
@media(max-width:800px){ .bottom-grid{grid-template-columns:1fr} .tools-grid{grid-template-columns:1fr} }
.recent-cmd {
  font-family:var(--mono);font-size:12px;color:var(--gold);
  padding:8px 10px;background:var(--bg3);border-radius:var(--r-sm);
  border:1px solid var(--border);margin-bottom:6px;word-break:break-all;
  cursor:pointer;transition:all .12s;
  display:flex;align-items:center;justify-content:space-between;gap:8px;
}
.recent-cmd:hover { border-color:rgba(232,169,74,0.35);background:rgba(232,169,74,0.04) }
.recent-cmd:last-child { margin-bottom:0 }
.cmd-text { flex:1;line-height:1.4 }
.cmd-copy-btn { font-size:11px;flex-shrink:0;color:var(--text3);transition:color .12s; }
.recent-cmd:hover .cmd-copy-btn { color:var(--green) }
.tag-dot {
  width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;
}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>

<div class="dash-layout">

  <!-- HERO -->
  <div class="dash-hero">
    <div class="dash-hero-left">
      <h1>⛏ Minecraft CMD Generator</h1>
      <p>// semua tools dalam satu tempat · Java Edition 1.20+</p>
    </div>
    <div class="dash-stats">
      <div class="stat-pill">
        <span class="stat-num" style="color:var(--green)"><?= $dbOk ? $dbStatus['history'] : '—' ?></span>
        <span class="stat-lbl">COMMANDS<br>SAVED</span>
      </div>
      <div class="stat-pill">
        <span class="stat-num" style="color:var(--gold)"><?= $dbOk ? $dbStatus['favourites'] : '—' ?></span>
        <span class="stat-lbl">FAVOURITES</span>
      </div>
      <div class="stat-pill">
        <span class="stat-num" style="color:var(--purple)"><?= $dbOk ? $dbStatus['kits'] : '—' ?></span>
        <span class="stat-lbl">KITS<br>BUILT</span>
      </div>
      <div class="stat-pill">
        <span class="stat-num" style="color:var(--teal)">9</span>
        <span class="stat-lbl">TOOLS<br>AVAILABLE</span>
      </div>
    </div>
  </div>

  <!-- TOOLS GRID -->
  <div class="tools-grid">

    <a href="index.php" class="tool-card" style="--accent:var(--green)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(93,190,122,0.12);border:1px solid rgba(93,190,122,0.2)">⚡</div>
        <div><div class="tool-name" style="color:var(--green)">Command Generator</div><div class="tool-desc">10 command types · target selector · live preview</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">/give</span><span class="tool-chip">/effect</span><span class="tool-chip">/tp</span>
          <span class="tool-chip">/gamemode</span><span class="tool-chip">/enchant</span><span class="tool-chip">/summon</span>
          <span class="tool-chip">/xp</span><span class="tool-chip">/kill</span><span class="tool-chip">/clear</span><span class="tool-chip">+time</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Most used</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="kit.php" class="tool-card" style="--accent:var(--gold)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(232,169,74,0.1);border:1px solid rgba(232,169,74,0.2)">🎒</div>
        <div><div class="tool-name" style="color:var(--gold)">Kit Builder</div><div class="tool-desc">build full player kits · preset god/pvp/starter</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">Weapons</span><span class="tool-chip">Armour</span>
          <span class="tool-chip">Food</span><span class="tool-chip">Effects</span><span class="tool-chip">XP</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)"><?= $dbOk ? $dbStatus['kits'] : '?' ?> kits saved</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="sequencer.php" class="tool-card" style="--accent:var(--blue)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(91,156,246,0.1);border:1px solid rgba(91,156,246,0.2)">📋</div>
        <div><div class="tool-name" style="color:var(--blue)">Command Sequencer</div><div class="tool-desc">drag-and-drop · delay support · export as function</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">Drag &amp; Drop</span><span class="tool-chip">Delay</span>
          <span class="tool-chip">.mcfunction</span><span class="tool-chip">Batch export</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Chain commands</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="nbt.php" class="tool-card" style="--accent:var(--purple)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(155,120,240,0.1);border:1px solid rgba(155,120,240,0.2)">🔧</div>
        <div><div class="tool-name" style="color:var(--purple)">NBT Builder</div><div class="tool-desc">custom item names, lore, enchants, attributes</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">Custom Name</span><span class="tool-chip">Lore</span>
          <span class="tool-chip">Enchants</span><span class="tool-chip">Attributes</span><span class="tool-chip">NBT tags</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Advanced items</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="title.php" class="tool-card" style="--accent:var(--pink)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(232,111,168,0.1);border:1px solid rgba(232,111,168,0.2)">✍️</div>
        <div><div class="tool-name" style="color:var(--pink)">Title Generator</div><div class="tool-desc">title/subtitle/actionbar · colour codes · timing</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">/title</span><span class="tool-chip">/subtitle</span>
          <span class="tool-chip">/actionbar</span><span class="tool-chip">Colour codes</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">On-screen text</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="firework.php" class="tool-card" style="--accent:var(--orange)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(232,132,74,0.1);border:1px solid rgba(232,132,74,0.2)">🎆</div>
        <div><div class="tool-name" style="color:var(--orange)">Firework Builder</div><div class="tool-desc">shape, colours, effects, flight height</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">Shapes</span><span class="tool-chip">Colours</span>
          <span class="tool-chip">Trail</span><span class="tool-chip">Flicker</span><span class="tool-chip">Power</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Celebrations</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="scoreboard.php" class="tool-card" style="--accent:var(--teal)">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(56,196,184,0.1);border:1px solid rgba(56,196,184,0.2)">📊</div>
        <div><div class="tool-name" style="color:var(--teal)">Scoreboard</div><div class="tool-desc">objectives, display, team management</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">/scoreboard</span><span class="tool-chip">Objectives</span>
          <span class="tool-chip">Teams</span><span class="tool-chip">Display</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Minigames & stats</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="sign.php" class="tool-card" style="--accent:#a8d8a0">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(168,216,160,0.1);border:1px solid rgba(168,216,160,0.2)">🪧</div>
        <div><div class="tool-name" style="color:#a8d8a0">Sign Editor</div><div class="tool-desc">4-line sign text · front &amp; back · colour support</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">4 lines</span><span class="tool-chip">Front/Back</span>
          <span class="tool-chip">Colour</span><span class="tool-chip">Glow ink</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Custom signs</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

    <a href="book.php" class="tool-card" style="--accent:#b8b0e8">
      <div class="tool-card-head">
        <div class="tool-icon" style="background:rgba(184,176,232,0.1);border:1px solid rgba(184,176,232,0.2)">📖</div>
        <div><div class="tool-name" style="color:#b8b0e8">Book Writer</div><div class="tool-desc">multi-page book · author · title · formatting</div></div>
      </div>
      <div class="tool-body">
        <div class="tool-chips">
          <span class="tool-chip">Pages</span><span class="tool-chip">Author</span>
          <span class="tool-chip">Formatting</span><span class="tool-chip">Give command</span>
        </div>
      </div>
      <div class="tool-footer">
        <span style="font-size:11px;color:var(--text3);font-family:var(--mono)">Written books</span>
        <span class="tool-open-btn">Open →</span>
      </div>
    </a>

  </div><!-- /tools-grid -->

  <!-- BOTTOM PANELS -->
  <div class="bottom-grid">

    <!-- Recent History -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-dot" style="background:var(--gold)"></div>
        📜 RECENT COMMANDS
        <a href="index.php" style="margin-left:auto;font-size:10px;color:var(--green);font-family:var(--mono);text-decoration:none">view all →</a>
      </div>
      <div class="card-body" style="padding:12px">
        <?php if(!$dbOk): ?>
        <div class="hist-empty" style="text-align:center;padding:20px;color:var(--text3);font-size:12px;font-family:var(--mono);line-height:2">
          ⚠️ MySQL tidak connected
        </div>
        <?php elseif(empty($history)): ?>
        <div style="text-align:center;padding:20px;color:var(--text3);font-size:12px;font-family:var(--mono);line-height:2">
          📭 Tiada history lagi.<br>Pergi ke Command Generator untuk mula!
        </div>
        <?php else: ?>
        <?php foreach($history as $h): ?>
        <?php
          $colors = ['give'=>'#3ddc84','effect'=>'#9b7ff4','tp'=>'#4f9cf9','time'=>'#f0874a','gm'=>'#22d3c8','ench'=>'#f06fa0','summon'=>'#f06060','xp'=>'#f5a623','kill'=>'#f06060','clear'=>'#4f9cf9'];
          $tc = $colors[strtolower($h['tab'] ?? '')] ?? '#94a3b8';
        ?>
        <div class="recent-cmd" onclick="copyText('<?= htmlspecialchars(addslashes($h['command'])) ?>')">
          <div class="tag-dot" style="background:<?= $tc ?>"></div>
          <div class="cmd-text"><?= htmlspecialchars($h['command']) ?></div>
          <span class="cmd-copy-btn">📋</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Export Panel -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-dot" style="background:var(--blue)"></div>
        📦 EXPORT COMMANDS
      </div>
      <div class="card-body" style="padding:16px">
        <p style="font-size:12px;color:var(--text3);font-family:var(--mono);margin-bottom:14px;line-height:1.7">
          Export semua saved commands anda sebagai fail .txt atau .mcfunction untuk guna dalam Minecraft data pack.
        </p>
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
          <button class="btn btn-blue" onclick="exportCommands('txt')">
            📄 Export History sebagai .txt
          </button>
          <button class="btn" style="background:rgba(155,120,240,0.1);border-color:rgba(155,120,240,0.35);color:var(--purple)" onclick="exportCommands('mcfunction')">
            ⚙️ Export sebagai .mcfunction
          </button>
          <button class="btn btn-gold" onclick="exportCommands('favtxt')">
            ⭐ Export Favourites sebagai .txt
          </button>
        </div>
        <div style="background:rgba(91,156,246,0.05);border:1px solid rgba(91,156,246,0.15);border-radius:8px;padding:10px 12px">
          <p style="font-size:11px;color:var(--text3);font-family:var(--mono);line-height:1.8">
            💡 .mcfunction — letakkan dalam <code style="color:var(--blue)">data/&lt;namespace&gt;/functions/</code><br>
            💡 Gunakan dengan <code style="color:var(--blue)">/function namespace:filename</code>
          </p>
        </div>
      </div>
    </div>

  </div><!-- /bottom-grid -->

</div><!-- /dash-layout -->

<div class="toast" id="toast"></div>

<script>
async function exportCommands(type) {
  try {
    const r = await fetch('api.php?action=history_get');
    const favR = await fetch('api.php?action=fav_get');
    const data = await r.json();
    const favData = await favR.json();

    let commands = [];
    let filename = '';
    let content = '';

    if (type === 'favtxt') {
      commands = (favData.rows || []).map(f => f.command);
      filename = 'minecraft_favourites.txt';
      if (!commands.length) { showToast('⚠️ Tiada favourite untuk export!', 'var(--red)'); return; }
      content = `# Minecraft Favourite Commands\n# Exported: ${new Date().toLocaleString()}\n# Total: ${commands.length} commands\n\n`;
      content += commands.join('\n');
    } else if (type === 'mcfunction') {
      commands = (data.rows || []).map(h => h.command);
      filename = 'minecraft_commands.mcfunction';
      if (!commands.length) { showToast('⚠️ Tiada history untuk export!', 'var(--red)'); return; }
      content = `# Generated by Minecraft CMD Gen\n# ${new Date().toLocaleString()}\n# Place in: data/<namespace>/functions/\n\n`;
      content += commands.map(c => c.startsWith('/') ? c.slice(1) : c).join('\n');
    } else {
      commands = (data.rows || []).map(h => h.command);
      filename = 'minecraft_commands.txt';
      if (!commands.length) { showToast('⚠️ Tiada history untuk export!', 'var(--red)'); return; }
      content = `# Minecraft Commands History\n# Exported: ${new Date().toLocaleString()}\n# Total: ${commands.length} commands\n\n`;
      commands.forEach((cmd, i) => { content += `${i+1}. ${cmd}\n`; });
    }

    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    playCopy();
    showToast(`✅ Exported ${commands.length} commands!`);
  } catch(e) {
    showToast('⚠️ Export gagal: ' + e.message, 'var(--red)');
  }
}
</script>
</body>
</html>
