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

// The eight things players reach for most.
$quickIds = ['give', 'enchantments', 'fill', 'doctor', 'palette', 'farms', 'tips', 'ideas'];
$byId     = [];
foreach (registry_tools() as $t) $byId[$t['id']] = $t;

$css = <<<CSS
.home { max-width:1240px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }
.hero { text-align:center; padding:26px 0 30px }
.hero h1 { font-size:29px; font-weight:800; letter-spacing:-.6px; margin-bottom:7px }
.hero h1 span { color:var(--green) }
.hero p { color:var(--text3); font-size:14px }
.hero-search {
  max-width:560px; margin:20px auto 0; display:flex; align-items:center; gap:11px;
  background:var(--card); border:1px solid var(--border2); border-radius:var(--r-lg);
  padding:13px 18px; cursor:text; transition:all .15s;
}
.hero-search:hover { border-color:var(--border3); box-shadow:var(--shadow) }
.hero-search span { color:var(--text3); font-size:14.5px; flex:1; text-align:left }
.home-sec { margin-top:30px }
/* Eight quick actions, so cap at four columns to get two even rows. */
.home-sec .tile-grid { grid-template-columns:repeat(auto-fill,minmax(215px,1fr)) }
@media(min-width:1120px){ .home-sec .tile-grid { grid-template-columns:repeat(4,1fr) } }
.home-sec-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:11px }
.home-sec-title { font-size:11px; font-family:var(--mono); letter-spacing:2px; text-transform:uppercase; color:var(--text3); font-weight:700 }
.home-sec-link { font-size:12px; color:var(--text3); text-decoration:none }
.home-sec-link:hover { color:var(--green) }
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
    <h1>Your Minecraft <span>toolbox</span></h1>
    <p>Build commands, plan builds, fix what is broken, learn the farms.</p>
    <div class="hero-search" onclick="document.getElementById('global-search-btn').click()">
      🔍 <span>What do you want to do? Try “clear a 19×19 area”…</span> <kbd>/</kbd>
    </div>
  </div>

  <div class="home-sec">
    <div class="home-sec-head">
      <div class="home-sec-title">Quick actions</div>
      <a class="home-sec-link" href="tools.php">All tools →</a>
    </div>
    <div class="tile-grid">
      <?php foreach ($quickIds as $id):
          if (!isset($byId[$id])) continue;
          $t = $byId[$id];
          echo ui_tile($t['href'], $t['icon'], $t['title'], $t['desc'], $t['accent']);
      endforeach; ?>
    </div>
  </div>

  <div class="split home-sec">
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
          <?= ui_empty('⭐', 'Nothing saved yet', 'Hit “Library” under any generated command to keep it here.') ?>
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
    <span class="muted" style="font-size:12.5px">Every tool follows this. Change it in the top-right whenever you switch worlds.</span>
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
