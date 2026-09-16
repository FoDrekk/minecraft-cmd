<?php
// ================================================
// index.php — Home. A launchpad, not a dashboard.
// ================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/registry.php';

$ver     = mcVersion(mcCurrentVersion());
$history = historyGet(6);
$saved   = favGet();

// Quick actions — the things players reach for most
$quickIds = ['give', 'clear-area', 'teleport', 'time', 'gamemode', 'effect', 'kill', 'doctor'];
$byId     = [];
foreach (registry_tools() as $t) $byId[$t['id']] = $t;

$css = <<<CSS
.home { max-width:1120px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }

/* ── HERO ── */
.hero { text-align:center; padding:30px 0 36px }
.hero h1 { font-size:28px; font-weight:800; letter-spacing:-.5px; margin-bottom:6px; color:var(--text) }
.hero h1 span { color:var(--green) }
.hero p { color:var(--text3); font-size:14px; max-width:400px; margin:0 auto }
.hero-search {
  max-width:540px; margin:22px auto 0; display:flex; align-items:center; gap:11px;
  background:var(--card); border:1px solid var(--border2); border-radius:var(--r-lg);
  padding:13px 18px; cursor:text; transition:all .15s;
}
.hero-search:hover { border-color:var(--border3); box-shadow:var(--shadow) }
.hero-search span { color:var(--text3); font-size:14px; flex:1; text-align:left }

/* ── PRIMARY ACTIONS ── */
.primary-actions { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:32px }
@media(max-width:700px){ .primary-actions { grid-template-columns:repeat(2,1fr) } }
.primary-action {
  display:flex; flex-direction:column; align-items:center; gap:10px;
  padding:24px 16px 20px; text-align:center;
  background:var(--card); border:1px solid var(--border2); border-radius:var(--r-lg);
  text-decoration:none; transition:all .18s; cursor:pointer; position:relative; overflow:hidden;
}
.primary-action::before {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
  background:var(--pa-color); opacity:0; transition:opacity .18s;
}
.primary-action:hover { border-color:var(--pa-color); transform:translateY(-2px); box-shadow:var(--shadow) }
.primary-action:hover::before { opacity:1 }
.primary-action-icon {
  width:48px; height:48px; border-radius:var(--r);
  display:flex; align-items:center; justify-content:center;
  font-size:22px; background:rgba(255,255,255,0.03); border:1px solid var(--border);
}
.primary-action-title { font-size:14px; font-weight:700; color:var(--text) }
.primary-action-desc { font-size:12px; color:var(--text3); line-height:1.4 }

/* ── SECTIONS ── */
.home-sec { margin-top:30px }
.home-sec .tile-grid { grid-template-columns:repeat(auto-fill,minmax(200px,1fr)) }
@media(min-width:1000px){ .home-sec .tile-grid { grid-template-columns:repeat(4,1fr) } }
.home-sec-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:11px }
.home-sec-title { font-size:11px; font-family:var(--mono); letter-spacing:2px; text-transform:uppercase; color:var(--text3); font-weight:700 }
.home-sec-link { font-size:12px; color:var(--text3); text-decoration:none }
.home-sec-link:hover { color:var(--green) }

/* ── RECENT + SIDEBAR ── */
.home-split { display:grid; grid-template-columns:1fr 340px; gap:20px; margin-top:30px }
@media(max-width:860px){ .home-split { grid-template-columns:1fr } }

.recent-row {
  display:flex; align-items:center; gap:10px; padding:9px 12px;
  border:1px solid var(--border); border-radius:var(--r-sm); background:var(--card);
  margin-bottom:5px; text-decoration:none; transition:all .12s;
}
.recent-row:hover { border-color:var(--border3); background:var(--card2) }
.recent-cmd { font-family:var(--mono); font-size:12px; color:var(--gold); word-break:break-all; flex:1; line-height:1.5 }
.recent-meta { font-size:10px; font-family:var(--mono); color:var(--text3); flex-shrink:0 }

.surprise-card { text-align:center; padding:22px 18px }
.surprise-out { margin-top:14px; min-height:64px }
.surprise-kind {
  font-size:10px; font-family:var(--mono); letter-spacing:1.6px; text-transform:uppercase;
  color:var(--purple); font-weight:700; margin-bottom:6px;
}
.surprise-title { font-size:16px; font-weight:700; color:var(--text); margin-bottom:5px }
.surprise-body { font-size:13px; color:var(--text2); line-height:1.65; max-width:520px; margin:0 auto }

.ver-strip {
  display:flex; align-items:center; gap:11px; flex-wrap:wrap;
  padding:11px 16px; border:1px solid rgba(232,169,74,0.2);
  background:rgba(232,169,74,0.04); border-radius:var(--r); margin-top:26px;
}
.ver-strip b { color:var(--gold); font-family:var(--mono) }
CSS;

ui_head('Home', '', $css);
?>
<div class="home">

  <div class="hero">
    <h1>What are you <span>building</span> today?</h1>
    <p>Commands, builds, farms — everything your world needs.</p>
    <div class="hero-search" onclick="document.getElementById('global-search-btn').click()">
      🔍 <span>Search anything… "god sword", "clear trees", "iron farm"</span> <kbd>/</kbd>
    </div>
  </div>

  <!-- PRIMARY ACTIONS -->
  <div class="primary-actions">
    <a class="primary-action" href="enchantments.php" style="--pa-color:var(--gold)">
      <div class="primary-action-icon">✨</div>
      <div class="primary-action-title">Enhance Item</div>
      <div class="primary-action-desc">Enchant a sword, pickaxe, armor — with conflict checks</div>
    </a>
    <a class="primary-action" href="build.php" style="--pa-color:var(--green)">
      <div class="primary-action-icon">🧱</div>
      <div class="primary-action-title">Build</div>
      <div class="primary-action-desc">Fill, clear, replace, plan builds with blueprints</div>
    </a>
    <a class="primary-action" href="farms.php" style="--pa-color:var(--teal)">
      <div class="primary-action-icon">🌾</div>
      <div class="primary-action-title">Farm</div>
      <div class="primary-action-desc">Step-by-step farm guides with material lists</div>
    </a>
    <a class="primary-action" href="commands.php" style="--pa-color:var(--blue)">
      <div class="primary-action-icon">⚡</div>
      <div class="primary-action-title">Command</div>
      <div class="primary-action-desc">Give, teleport, effect, summon — any command</div>
    </a>
  </div>

  <!-- QUICK ACTIONS -->
  <div class="home-sec">
    <div class="home-sec-head">
      <div class="home-sec-title">Quick actions</div>
      <a class="home-sec-link" href="commands.php">All commands →</a>
    </div>
    <div class="tile-grid">
      <?php foreach ($quickIds as $id):
          if (!isset($byId[$id])) continue;
          $t = $byId[$id];
          echo ui_tile($t['href'], $t['icon'], $t['title'], $t['desc'], $t['accent']);
      endforeach; ?>
    </div>
  </div>

  <!-- CONTINUE + SURPRISE -->
  <div class="home-split">
    <div>
      <div class="home-sec-head">
        <div class="home-sec-title">Continue where you left off</div>
        <button class="home-sec-link" style="background:none;border:none;cursor:pointer;font-family:inherit"
                onclick="MC.forgetAll();renderRecent()">Clear</button>
      </div>
      <div id="recent-list"></div>

      <?php if ($history): ?>
      <div class="home-sec-head" style="margin-top:22px">
        <div class="home-sec-title">Recently generated</div>
        <a class="home-sec-link" href="mystuff.php">History →</a>
      </div>
      <?php foreach ($history as $h): ?>
      <div class="recent-row" style="cursor:pointer" onclick="MC.copy(this.dataset.cmd)" data-cmd="<?= e($h['command']) ?>">
        <span class="recent-cmd"><?= e($h['command']) ?></span>
        <span class="recent-meta"><?= e($h['mc_version'] ?: '') ?></span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="stack">
      <?php ui_card('Surprise me', function () { ?>
        <div class="surprise-card">
          <div class="muted" style="font-size:12.5px">Not sure what to do next?</div>
          <button class="btn btn-purple btn-lg" style="margin-top:12px" onclick="surprise()">🎲 Surprise me</button>
          <div class="surprise-out" id="surprise-out"></div>
        </div>
      <?php }, 'purple'); ?>

      <?php ui_card('Saved commands', function () use ($saved) { ?>
        <?php if (!$saved): ?>
          <?= ui_empty('⭐', 'Nothing saved yet', 'Hit "Library" under any generated command to keep it here.') ?>
        <?php else: ?>
          <?php foreach (array_slice($saved, 0, 6) as $f): ?>
          <div class="recent-row" style="cursor:pointer" onclick="MC.copy(this.dataset.cmd)" data-cmd="<?= e($f['command']) ?>">
            <span class="recent-cmd"><?= e($f['name'] ?: $f['command']) ?></span>
            <span class="recent-meta">📋</span>
          </div>
          <?php endforeach; ?>
          <a class="home-sec-link" href="mystuff.php" style="display:inline-block;margin-top:8px">Open library →</a>
        <?php endif; ?>
      <?php }, 'gold'); ?>
    </div>
  </div>

  <div class="ver-strip">
    <span>🧩 Generating for <b><?= e($ver['label']) ?></b> <?= $ver['name'] ? '· ' . e($ver['name']) : '' ?></span>
    <span class="muted" style="font-size:12.5px">Every tool follows this. Change it in the sidebar whenever you switch worlds.</span>
  </div>

</div>

<script>
function renderRecent() {
  var list = MC.recents().filter(function (r) { return r.type !== 'command'; }).slice(0, 6);
  var el = document.getElementById('recent-list');
  if (!list.length) {
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">🧭</div>' +
      '<div class="empty-title">Nothing here yet</div>' +
      '<div class="empty-sub">Tools, guides and palettes you open will show up here.</div></div>';
    return;
  }
  el.innerHTML = list.map(function (r) {
    var ago = timeAgo(r.at);
    return '<a class="recent-row" href="' + MC.escapeHtml(r.href) + '">' +
      '<span style="font-size:16px">' + (r.icon || '📄') + '</span>' +
      '<span class="recent-cmd" style="color:var(--text)">' + MC.escapeHtml(r.title) + '</span>' +
      '<span class="recent-meta">' + ago + '</span></a>';
  }).join('');
}

function timeAgo(ts) {
  if (!ts) return '';
  var s = Math.floor((Date.now() - ts) / 1000);
  if (s < 60) return 'just now';
  if (s < 3600) return Math.floor(s / 60) + 'm ago';
  if (s < 86400) return Math.floor(s / 3600) + 'h ago';
  return Math.floor(s / 86400) + 'd ago';
}

function surprise() {
  var out = document.getElementById('surprise-out');
  out.innerHTML = '<div class="muted" style="font-size:12.5px">Thinking…</div>';
  fetch('api.php?action=surprise')
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d.ok || !d.pick) throw new Error();
      var p = d.pick;
      out.innerHTML =
        '<div class="surprise-kind">' + MC.escapeHtml(p.kind) + '</div>' +
        '<div class="surprise-title">' + MC.escapeHtml(p.title) + '</div>' +
        '<div class="surprise-body">' + MC.escapeHtml(p.body) + '</div>' +
        (p.href ? '<a class="btn btn-ghost btn-sm" style="margin-top:12px;display:inline-block;text-decoration:none" href="' +
          MC.escapeHtml(p.href) + '">Open →</a>' : '');
      if (window.playSelect) playSelect();
    })
    .catch(function () {
      out.innerHTML = '<div class="muted" style="font-size:12.5px">Could not load a suggestion right now. Try again in a moment.</div>';
    });
}

renderRecent();
</script>
<?php ui_foot(); ?>
