<?php
// ================================================
// nav.php — global navigation, version selector, search
// ================================================
require_once __DIR__ . '/lib/ui.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Task-based primary navigation. `match` lists sibling pages that should
// keep the section highlighted.
$nav = [
    ['index',     '🏠', 'Home',      []],
    ['tools',     '🧰', 'Tools',     ['commands', 'kit', 'nbt', 'enchantments']],
    ['build',     '🧱', 'Build',     []],
    ['knowledge', '📚', 'Knowledge', []],
    ['farms',     '🌾', 'Farms',     []],
    ['doctor',    '🩺', 'Doctor',    []],
    ['mystuff',   '⭐', 'My Stuff',  ['dashboard']],
];

$mcVer = mcCurrentVersion();
?>
<nav class="topnav">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon">⛏</div>
    <span class="nav-logo-text">MC<span class="nav-logo-accent">CMD</span></span>
  </a>
  <div class="nav-divider"></div>
  <?php foreach ($nav as [$page, $icon, $label, $match]):
      // Sections appear as they are built, so navigation is never broken.
      if (!is_file(__DIR__ . '/' . $page . '.php')) continue;
      $active = ($currentPage === $page || in_array($currentPage, $match, true)); ?>
  <a href="<?= $page ?>.php" class="nav-link <?= $active ? 'active' : '' ?>">
    <?= $icon ?> <span class="nav-link-text"><?= $label ?></span>
  </a>
  <?php endforeach; ?>

  <div class="nav-right">
    <button class="nav-search-btn" id="global-search-btn" title="Search everything (press /)">
      🔍 <span class="nav-link-text">Search</span> <kbd>/</kbd>
    </button>
    <div class="nav-divider"></div>
    <label class="nav-version" title="Minecraft version — every generator follows this">
      <span class="nav-version-tag">MC</span>
      <select id="mc-version-select">
        <?php foreach (mcVersions() as $id => $v): ?>
        <option value="<?= e($id) ?>" <?= $id === $mcVer ? 'selected' : '' ?>><?= e($v['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="sound-toggle on" id="sound-btn" onclick="toggleSound(this)">🔊</button>
  </div>
</nav>

<!-- GLOBAL SEARCH OVERLAY -->
<div class="search-overlay" id="search-overlay" hidden>
  <div class="search-modal">
    <div class="search-modal-input">
      <span>🔍</span>
      <input type="text" id="global-search-input" placeholder="What do you want to do? e.g. clear trees, iron farm, make everyone creative" autocomplete="off">
      <kbd>Esc</kbd>
    </div>
    <div class="search-results" id="global-search-results"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('sound-btn');
  if (btn) {
    var on = localStorage.getItem('mc_sound') !== 'off';
    btn.textContent = on ? '🔊' : '🔇';
    btn.classList.toggle('on', on);
  }
});
</script>
