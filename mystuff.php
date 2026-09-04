<?php
// ================================================
// mystuff.php — saved commands, history, palettes, kits
// ================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/ui.php';
require_once __DIR__ . '/lib/data/palettes.php';
require_once __DIR__ . '/lib/data/blocks.php';

$tab = $_GET['t'] ?? 'library';
if (!in_array($tab, ['library', 'history', 'palettes', 'kits'], true)) $tab = 'library';

$saved    = favGet();
$history  = historyGet(60);
$palettes = paletteGet();
$kits     = kitGet();
$status   = dbStatus();

$categories = ['Starter', 'Admin', 'Building', 'Farms', 'Redstone', 'Effects', 'Fun', 'Map making'];

$css = <<<CSS
.ms-page { max-width:1080px; margin:0 auto; padding:20px 24px 56px; position:relative; z-index:1 }
.ms-tabs { display:flex; gap:6px; margin-bottom:18px; flex-wrap:wrap }
.ms-tab { padding:8px 15px; border-radius:var(--r-sm); border:1px solid var(--border2); background:var(--card);
  color:var(--text3); text-decoration:none; font-size:13.5px; font-weight:600; transition:all .13s }
.ms-tab:hover { border-color:var(--border3); color:var(--text2) }
.ms-tab.active { background:rgba(232,169,74,0.09); border-color:rgba(232,169,74,0.3); color:var(--gold) }
.ms-tab .n { font-family:var(--mono); font-size:11px; opacity:.7; margin-left:4px }

.ms-controls { display:flex; gap:9px; flex-wrap:wrap; align-items:center; margin-bottom:14px }
.ms-controls input, .ms-controls select { max-width:280px }

.entry { border:1px solid var(--border); border-radius:var(--r); background:var(--card);
  padding:12px 14px; margin-bottom:8px; transition:all .12s }
.entry:hover { border-color:var(--border3) }
.entry-top { display:flex; align-items:center; gap:9px; margin-bottom:6px; flex-wrap:wrap }
.entry-name { font-size:13.5px; font-weight:700; color:var(--text) }
.entry-cmd { font-family:var(--mono); font-size:12px; color:var(--gold); word-break:break-all;
  line-height:1.55; background:var(--bg2); border-radius:var(--r-xs); padding:8px 11px; cursor:pointer }
.entry-cmd:hover { background:var(--bg3) }
.entry-note { font-size:12px; color:var(--text3); margin-top:6px }
.entry-actions { display:flex; gap:5px; margin-left:auto }
.entry-btn { background:transparent; border:1px solid var(--border2); color:var(--text3);
  border-radius:var(--r-xs); cursor:pointer; font-size:11px; padding:3px 8px; transition:all .12s }
.entry-btn:hover { border-color:var(--border3); color:var(--text2) }
.entry-btn.danger:hover { border-color:var(--red); color:var(--red) }
.tag { font-size:9.5px; font-family:var(--mono); letter-spacing:1px; text-transform:uppercase;
  border:1px solid var(--border2); color:var(--text3); padding:2px 6px; border-radius:4px }
.db-note { font-size:12px; color:var(--text3); margin-top:20px; padding-top:14px; border-top:1px solid var(--border) }
CSS;

ui_head('My Stuff', '', $css);

/** One saved or historic command row. */
function ms_entry(array $row, string $kind): void
{
    $cmd = $row['command'];
    echo '<div class="entry" data-search="' . e(strtolower($cmd . ' ' . ($row['name'] ?? '') . ' ' . ($row['note'] ?? '') . ' ' . ($row['tags'] ?? ''))) . '"'
       . ' data-category="' . e($row['category'] ?? '') . '">';
    echo '<div class="entry-top">';
    if (!empty($row['name'])) echo '<span class="entry-name">' . e($row['name']) . '</span>';
    if (!empty($row['category'])) echo '<span class="tag">' . e($row['category']) . '</span>';
    if (!empty($row['tab'])) echo '<span class="tag">' . e($row['tab']) . '</span>';
    if (!empty($row['mc_version'])) echo '<span class="tag">' . e($row['mc_version']) . '</span>';
    echo '<span class="entry-actions">';
    echo '<button class="entry-btn" data-copy="' . e($cmd) . '">📋 Copy</button>';
    if ($kind === 'library') {
        echo '<button class="entry-btn" data-edit="' . (int)$row['id'] . '">✎ Edit</button>';
        echo '<button class="entry-btn danger" data-del-fav="' . (int)$row['id'] . '">✕</button>';
    } else {
        echo '<button class="entry-btn" data-save-fav="' . e($cmd) . '" data-tab="' . e($row['tab'] ?? '') . '">⭐ Save</button>';
        echo '<button class="entry-btn danger" data-del-hist="' . (int)$row['id'] . '">✕</button>';
    }
    echo '</span></div>';
    echo '<div class="entry-cmd" data-copy="' . e($cmd) . '">' . e($cmd) . '</div>';
    if (!empty($row['note'])) echo '<div class="entry-note">📝 ' . e($row['note']) . '</div>';
    if (!empty($row['created_at'])) echo '<div class="entry-note" style="font-family:var(--mono);font-size:10.5px">' . e($row['created_at']) . '</div>';
    echo '</div>';
}
?>
<div class="ms-page">
  <?php ui_page_header('⭐', 'My Stuff', 'Everything you have saved, in one place.'); ?>

  <div class="ms-tabs">
    <a class="ms-tab <?= $tab === 'library' ? 'active' : '' ?>" href="mystuff.php?t=library">⭐ Command library<span class="n"><?= count($saved) ?></span></a>
    <a class="ms-tab <?= $tab === 'history' ? 'active' : '' ?>" href="mystuff.php?t=history">🕘 History<span class="n"><?= count($history) ?></span></a>
    <a class="ms-tab <?= $tab === 'palettes' ? 'active' : '' ?>" href="mystuff.php?t=palettes">🎨 Palettes<span class="n"><?= count($palettes) ?></span></a>
    <a class="ms-tab <?= $tab === 'kits' ? 'active' : '' ?>" href="mystuff.php?t=kits">🎒 Kits<span class="n"><?= count($kits) ?></span></a>
  </div>

<?php if ($tab === 'library'): ?>
  <div class="ms-controls">
    <input type="text" id="ms-search" placeholder="Search your saved commands…" autocomplete="off">
    <select id="ms-cat">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <span class="muted" style="font-size:12px" id="ms-count"></span>
  </div>
  <div id="ms-list">
    <?php if (!$saved): ?>
      <?= ui_empty('⭐', 'Your library is empty',
          'Generate a command anywhere in the app and press “Library” underneath it. Saved commands keep their name, category and Minecraft version.') ?>
    <?php else: foreach ($saved as $row) ms_entry($row, 'library'); endif; ?>
  </div>

<?php elseif ($tab === 'history'): ?>
  <div class="ms-controls">
    <input type="text" id="ms-search" placeholder="Search recent commands…" autocomplete="off">
    <button class="btn btn-ghost btn-sm" id="hist-clear">Clear all history</button>
    <span class="muted" style="font-size:12px" id="ms-count"></span>
  </div>
  <div id="ms-list">
    <?php if (!$history): ?>
      <?= ui_empty('🕘', 'No history yet',
          'Commands you save from a generator land here. Use “Copy + Save” to keep a record as you go.') ?>
    <?php else: foreach ($history as $row) ms_entry($row, 'history'); endif; ?>
  </div>

<?php elseif ($tab === 'palettes'): ?>
  <?php if (!$palettes): ?>
    <?= ui_empty('🎨', 'No saved palettes', 'Build one in Knowledge → Palettes and press Save.') ?>
  <?php else: $blocks = blocksList(); ?>
    <div class="tile-grid">
    <?php foreach ($palettes as $p): ?>
      <div class="entry">
        <div class="entry-top">
          <span class="entry-name"><?= e($p['palette_name']) ?></span>
          <span class="tag"><?= e($p['style'] ?? '') ?></span>
          <span class="entry-actions">
            <a class="entry-btn" style="text-decoration:none" href="knowledge.php?t=palette&style=<?= e($p['style'] ?? '') ?>">Open</a>
            <button class="entry-btn danger" data-del-pal="<?= (int)$p['id'] ?>">✕</button>
          </span>
        </div>
        <div class="swatch-row" style="display:flex;gap:5px;margin-bottom:8px">
          <?php foreach ((array)$p['palette_data'] as $bid): $b = $blocks[$bid] ?? null; ?>
            <i style="display:block;width:30px;height:30px;border-radius:5px;background:<?= e($b['hex'] ?? '#3a3a3a') ?>;border:1px solid rgba(255,255,255,0.12)"
               title="<?= e($b['name'] ?? $bid) ?>"></i>
          <?php endforeach; ?>
        </div>
        <div class="entry-note">
          <?php $names = [];
          foreach ((array)$p['palette_data'] as $role => $bid) $names[] = ucfirst($role) . ': ' . ($blocks[$bid]['name'] ?? $bid);
          echo e(implode(' · ', $names)); ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php else: ?>
  <?php if (!$kits): ?>
    <?= ui_empty('🎒', 'No saved kits', 'Build a loadout in the Kit Builder and save it there.') ?>
  <?php else: foreach ($kits as $k): ?>
    <div class="entry">
      <div class="entry-top">
        <span class="entry-name"><?= e($k['kit_name']) ?></span>
        <span class="tag"><?= count((array)$k['kit_data']) ?> slots</span>
        <span class="entry-actions">
          <a class="entry-btn" style="text-decoration:none" href="kit.php">Open kit builder</a>
        </span>
      </div>
      <div class="entry-note"><?= e($k['updated_at'] ?? '') ?></div>
    </div>
  <?php endforeach; endif; ?>
<?php endif; ?>

  <div class="db-note">
    <?php if ($status['connected']): ?>
      Stored in <?= e($status['driver'] === 'sqlite' ? 'a local SQLite file (data/minecraft_cmd.sqlite)' : 'MySQL') ?>.
      <?= (int)$status['history'] ?> in history, <?= (int)$status['favourites'] ?> saved,
      <?= (int)$status['kits'] ?> kits, <?= (int)$status['palettes'] ?> palettes.
    <?php else: ?>
      <?= ui_warn(e($status['error'] ?? 'The database is not available, so nothing can be saved right now.'), 'error') ?>
    <?php endif; ?>
  </div>
</div>

<script>
var MS_TAB = <?= json_encode($tab) ?>;
var MS_CATEGORIES = <?= json_encode($categories) ?>;
</script>
<script src="assets/mystuff.js"></script>
<?php ui_foot(); ?>
