<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$nav = [
  ['index',      '⚡', 'Commands',   ''],
  ['kit',        '🎒', 'Kit Builder', ''],
  ['sequencer',  '📋', 'Sequencer',  ''],
  ['nbt',        '🔧', 'NBT Builder', ''],
  ['title',      '✍️',  'Title Gen',  ''],
  ['firework',   '🎆', 'Firework',   ''],
  ['scoreboard', '📊', 'Scoreboard', ''],
  ['sign',       '🪧', 'Sign',       ''],
  ['book',       '📖', 'Book Writer',''],
];
?>
<nav class="topnav">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon">⛏</div>
    <span class="nav-logo-text">CMD<span class="nav-logo-accent">GEN</span></span>
  </a>
  <div class="nav-divider"></div>
  <?php foreach($nav as [$page,$icon,$label,$_]): ?>
  <a href="<?= $page ?>.php" class="nav-link <?= $currentPage===$page?'active':'' ?>">
    <?= $icon ?> <?= $label ?>
  </a>
  <?php endforeach; ?>
  <div class="nav-right">
    <div class="nav-divider"></div>
    <button class="sound-toggle on" id="sound-btn" onclick="toggleSound(this)">🔊 Sound ON</button>
    <div class="nav-user">
      <div class="nav-dot"></div>
      <span class="nav-uname">nizkbiits</span>
    </div>
  </div>
</nav>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const btn = document.getElementById('sound-btn');
  const on  = localStorage.getItem('mc_sound') !== 'off';
  btn.textContent = on ? '🔊 Sound ON' : '🔇 Sound OFF';
  btn.classList.toggle('on', on);
});
</script>
