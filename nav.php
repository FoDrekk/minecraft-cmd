<?php
// ================================================
// nav.php — global navigation, version selector, search
// ================================================
require_once __DIR__ . '/lib/ui.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Task-based primary navigation. `match` lists sibling pages that should
// keep the section highlighted.
$nav = [
    ['index',        '🏠', 'Home',       []],
    ['commands',     '⚡', 'Commands',   ['kit', 'nbt', 'enchantments', 'tools']],
    ['build',        '🧱', 'Build',      []],
    ['knowledge',    '📚', 'Knowledge',  []],
    ['farms',        '🌾', 'Farms',      []],
    ['---'],
    ['mystuff',      '⭐', 'My Stuff',   ['dashboard']],
    ['doctor',       '🩺', 'Doctor',     []],
];

$mcVer = mcCurrentVersion();
?>
<nav class="sidebar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon">⛏</div>
    <span class="nav-logo-text">MC<span class="nav-logo-accent">CMD</span></span>
  </a>
  
  <div class="nav-links">
    <?php foreach ($nav as $item): 
        if (isset($item[0]) && $item[0] === '---') {
            echo '<div class="nav-divider-hz"></div>';
            continue;
        }
        [$page, $icon, $label, $match] = $item;
        $active = ($currentPage === $page || in_array($currentPage, $match, true));
    ?>
    <a href="<?= $page ?>.php" class="nav-link <?= $active ? 'active' : '' ?>">
      <span class="nav-icon"><?= $icon ?></span> <span class="nav-link-text"><?= $label ?></span>
    </a>
    <?php endforeach; ?>
    
    <button class="nav-search-btn" id="global-search-btn" title="Search everything (press /)">
      🔍 <span class="nav-link-text">Search</span> <kbd>/</kbd>
    </button>
  </div>

  <div class="nav-bottom">
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

<nav class="bottom-nav">
  <?php 
  $mobileNav = ['index', 'commands', 'build', 'farms'];
  foreach ($nav as $item):
      if (isset($item[0]) && in_array($item[0], $mobileNav)):
          [$page, $icon, $label, $match] = $item;
          $active = ($currentPage === $page || in_array($currentPage, $match, true));
  ?>
  <a href="<?= $page ?>.php" class="bottom-nav-link <?= $active ? 'active' : '' ?>">
    <span class="bottom-nav-icon"><?= $icon ?></span>
    <span class="bottom-nav-label"><?= $label ?></span>
  </a>
  <?php endif; endforeach; ?>
  <button class="bottom-nav-link" onclick="document.getElementById('mobile-drawer').classList.toggle('open')">
    <span class="bottom-nav-icon">☰</span>
    <span class="bottom-nav-label">More</span>
  </button>
</nav>

<div class="mobile-drawer" id="mobile-drawer">
  <?php 
  $drawerNav = ['knowledge', 'mystuff', 'doctor'];
  foreach ($nav as $item):
      if (isset($item[0]) && in_array($item[0], $drawerNav)):
          [$page, $icon, $label, $match] = $item;
          $active = ($currentPage === $page || in_array($currentPage, $match, true));
  ?>
  <a href="<?= $page ?>.php" class="nav-link <?= $active ? 'active' : '' ?>">
    <span class="nav-icon"><?= $icon ?></span> <span class="nav-link-text"><?= $label ?></span>
  </a>
  <?php endif; endforeach; ?>
</div>

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
