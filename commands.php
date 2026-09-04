<?php
require_once __DIR__ . '/db.php';

$defaultTarget = 'nizkbiits';

// Load from DB (fallback to empty if DB not connected)
$history  = historyGet(30);
$favs     = favGet();
$dbStatus = dbStatus();
$dbOk     = $dbStatus['connected'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Minecraft Command Generator</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
/* ── INDEX PAGE SPECIFIC ── */
.layout{
  position:relative;z-index:1;
  display:grid;grid-template-columns:1fr 310px;gap:18px;
  padding:18px 24px 48px;max-width:1360px;margin:0 auto;
}
@media(max-width:980px){.layout{grid-template-columns:1fr}}

/* TARGET BAR */
.target-bar{
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;
  padding:11px 16px;
  background:rgba(232,169,74,0.03);
  border-bottom:1px solid var(--border);
}
.target-label{font-size:10px;font-family:var(--mono);letter-spacing:1.5px;color:var(--gold);white-space:nowrap;text-transform:uppercase;font-weight:600}
.target-input{
  background:rgba(232,169,74,0.05);
  border:1px solid rgba(232,169,74,0.2);
  color:var(--gold);padding:6px 11px;
  border-radius:var(--r-sm);font-family:var(--mono);font-size:13px;width:160px;
  transition:all .15s;
}
.target-input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(232,169,74,0.08)}
.sel-chips{display:flex;gap:4px;flex-wrap:wrap}
.chip{
  padding:4px 10px;font-size:12px;font-weight:600;
  font-family:var(--mono);
  background:transparent;border:1px solid var(--border2);
  color:var(--text3);border-radius:var(--r-xs);cursor:pointer;transition:all .12s;
}
.chip:hover{border-color:var(--purple);color:var(--purple);background:rgba(155,120,240,0.06)}
.chip.chip-me{border-color:rgba(232,169,74,0.3);color:var(--gold);background:rgba(232,169,74,0.05)}
.chip.chip-me:hover{background:rgba(232,169,74,0.12)}

/* TABS */
.tabs{
  display:flex;flex-wrap:wrap;gap:2px;
  padding:8px 12px;background:rgba(255,255,255,0.015);border-bottom:1px solid var(--border);
}
.tab{
  display:flex;align-items:center;gap:5px;
  padding:5px 10px;font-size:12px;font-weight:500;
  background:transparent;border:1px solid transparent;
  color:var(--text3);border-radius:var(--r-xs);cursor:pointer;transition:all .15s;
  font-family:var(--body);
}
.tab:hover{color:var(--text2);background:rgba(255,255,255,0.04)}
.tab.active{font-weight:700}
.tab-give.active  {background:rgba(93,190,122,0.1); border-color:rgba(93,190,122,0.25); color:var(--green)}
.tab-effect.active{background:rgba(155,120,240,0.1);border-color:rgba(155,120,240,0.25);color:var(--purple)}
.tab-tp.active    {background:rgba(91,156,246,0.1); border-color:rgba(91,156,246,0.25); color:var(--blue)}
.tab-time.active  {background:rgba(232,132,74,0.1); border-color:rgba(232,132,74,0.25); color:var(--orange)}
.tab-gm.active    {background:rgba(56,196,184,0.1); border-color:rgba(56,196,184,0.25); color:var(--teal)}
.tab-ench.active  {background:rgba(232,111,168,0.1);border-color:rgba(232,111,168,0.25);color:var(--pink)}
.tab-summon.active{background:rgba(224,85,85,0.1);  border-color:rgba(224,85,85,0.25);  color:var(--red)}
.tab-xp.active    {background:rgba(232,169,74,0.1); border-color:rgba(232,169,74,0.25); color:var(--gold)}
.tab-kill.active  {background:rgba(224,85,85,0.1);  border-color:rgba(224,85,85,0.25);  color:var(--red)}
.tab-clear.active {background:rgba(91,156,246,0.1); border-color:rgba(91,156,246,0.25); color:var(--blue)}

/* SECTIONS */
.section{display:none;padding:16px;animation:fadeIn .15s ease}
.section.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}

/* SEARCH */
.search-wrap{position:relative;margin-bottom:10px}
.search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--text3);pointer-events:none}
.search-input{padding-left:34px !important}
.search-count{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:10px;color:var(--text3);font-family:var(--mono);pointer-events:none}

/* ITEM */
.item-id-display{
  font-family:var(--mono);font-size:12px;color:var(--text3);
  padding:6px 12px;background:var(--bg2);border-radius:var(--r-xs);margin-bottom:8px;
  border:1px solid var(--border);
}

/* ENCHANT */
.ench-level-title{font-size:10px;color:var(--text3);font-family:var(--mono);margin-bottom:8px;letter-spacing:1px;text-transform:uppercase;font-weight:600}
.level-row{display:flex;align-items:center;gap:12px}
.level-num{font-size:24px;font-weight:800;font-family:var(--mono);color:var(--pink);min-width:34px;text-align:center}
.ench-warning{font-size:11px;color:var(--gold);font-family:var(--mono);background:rgba(232,169,74,0.06);border:1px solid rgba(232,169,74,0.2);border-radius:var(--r-xs);padding:6px 10px}

/* OUTPUT */
.output-section{border-top:1px solid var(--border)}
.output-header{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;border-bottom:1px solid var(--border)}
.output-label{font-size:10px;font-family:var(--mono);letter-spacing:2px;color:var(--text3);text-transform:uppercase;font-weight:600}
.output-ver{font-size:10px;font-family:var(--mono);color:var(--text3)}
.cmd-box{padding:14px 16px;min-height:52px;font-family:var(--mono);font-size:14px;line-height:1.6;color:var(--gold);word-break:break-all}
.cmd-box::before{content:'›  ';color:var(--green2);opacity:.7}
.cmd-actions{display:flex;gap:7px;flex-wrap:wrap;padding:10px 16px;border-top:1px solid var(--border)}

/* SIDEBAR */
.panel-sticky{position:sticky;top:68px;display:flex;flex-direction:column;gap:14px}
.hist-list{padding:6px;max-height:420px;overflow-y:auto}
.hist-item{
  border:1px solid var(--border);border-radius:var(--r-sm);
  background:var(--bg3);padding:9px 12px;
  margin-bottom:5px;cursor:pointer;transition:all .12s;
}
.hist-item:hover{border-color:rgba(232,169,74,0.25);background:rgba(232,169,74,0.03)}
.hist-item:last-child{margin-bottom:0}
.hist-tag{display:inline-block;font-size:9px;font-family:var(--mono);padding:2px 6px;border-radius:3px;margin-bottom:4px;letter-spacing:.8px;font-weight:700}
.hist-cmd{font-family:var(--mono);font-size:11px;color:var(--gold);word-break:break-all;line-height:1.5}
.hist-time{font-size:10px;color:var(--text3);font-family:var(--mono);margin-top:3px}
.hist-empty{text-align:center;padding:28px 16px;color:var(--text3);font-size:12px;font-family:var(--mono);line-height:2}
.ref-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)}
.ref-row:last-child{border-bottom:none}
.ref-key{font-family:var(--mono);font-size:12px;color:var(--gold);font-weight:700}
.ref-val{font-size:12px;color:var(--text2)}
</style>

</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>

<!-- LAYOUT -->
<div class="layout">
<div>
<div class="card">

  <!-- TARGET BAR -->
  <div class="target-bar">
    <div class="target-label">🎯 Target:</div>
    <input class="target-input" type="text" id="global-target" value="<?= $defaultTarget ?>" oninput="syncTarget(this.value)">
    <div class="sel-chips">
      <button class="chip" onclick="setTarget('@p')">@p</button>
      <button class="chip" onclick="setTarget('@a')">@a</button>
      <button class="chip" onclick="setTarget('@r')">@r</button>
      <button class="chip" onclick="setTarget('@s')">@s</button>
      <button class="chip" onclick="setTarget('@e')">@e</button>
      <button class="chip chip-me" onclick="setTarget('<?= $defaultTarget ?>')">⭐ nizkbiits</button>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab tab-give active" onclick="setTab('give',this)">📦 Give</button>
    <button class="tab tab-effect" onclick="setTab('effect',this)">⚗️ Effect</button>
    <button class="tab tab-tp" onclick="setTab('tp',this)">🌀 TP</button>
    <button class="tab tab-time" onclick="setTab('time',this)">🌤️ Time</button>
    <button class="tab tab-gm" onclick="setTab('gm',this)">🎮 Gamemode</button>
    <button class="tab tab-ench" onclick="setTab('ench',this)">✨ Enchant</button>
    <button class="tab tab-summon" onclick="setTab('summon',this)">👾 Summon</button>
    <button class="tab tab-xp" onclick="setTab('xp',this)">⭐ XP</button>
    <button class="tab tab-kill" onclick="setTab('kill',this)">💀 Kill</button>
    <button class="tab tab-clear" onclick="setTab('clear',this)">🗑️ Clear</button>
  </div>

  <!-- ══ GIVE ══ -->
  <div id="sec-give" class="section active">
    <div class="sec-title" style="color:var(--green)">📦 Bagi Item</div>
    <div class="row" style="margin-bottom:12px">
      <div class="field">
        <label>Target</label>
        <input type="text" id="give-target" value="<?= $defaultTarget ?>" oninput="gen()">
      </div>
      <div class="field">
        <label>Jumlah (1–64)</label>
        <input type="number" id="give-count" value="1" min="1" max="64" oninput="gen()">
      </div>
    </div>
    <div class="row" style="margin-bottom:10px">
      <div class="field">
        <label>Category</label>
        <select id="give-cat" onchange="filterGiveDropdown()">
          <option value="">— All Items —</option>
        </select>
      </div>
      <div class="field">
        <label>Item</label>
        <select id="give-item-select" onchange="selectItemFromDropdown()">
        </select>
      </div>
    </div>
    <div class="search-wrap">
      <span class="search-icon">🔍</span>
      <input class="search-input" type="text" id="give-search" placeholder="Search item... (e.g. sword, diamond, oak)" oninput="filterGiveDropdown()">
      <span class="search-count" id="give-count-label"></span>
    </div>
    <div class="item-id-display">Selected: <span id="selected-item-id" style="color:var(--gold)">diamond_sword</span></div>
    <p class="hint">💡 Pilih category → pilih item dari dropdown · Jumlah 64 = 1 stack penuh</p>
  </div>

  <!-- ══ EFFECT ══ -->
  <div id="sec-effect" class="section">
    <div class="sec-title" style="color:var(--purple)">⚗️ Potion Effect</div>
    <div class="row">
      <div class="field"><label>Target</label><input type="text" id="eff-target" value="<?= $defaultTarget ?>" oninput="gen()"></div>
      <div class="field"><label>Effect</label>
        <select id="eff-type" onchange="gen()">
          <optgroup label="✅ Positif">
            <option value="speed">Speed — Laju bergerak</option>
            <option value="haste">Haste — Cepat menggali</option>
            <option value="strength">Strength — Damage lebih</option>
            <option value="instant_health">Instant Health — Terus sembuh</option>
            <option value="jump_boost">Jump Boost — Lompat tinggi</option>
            <option value="regeneration">Regeneration — HP naik sendiri</option>
            <option value="resistance">Resistance — Tahan damage</option>
            <option value="fire_resistance">Fire Resistance — Tahan api</option>
            <option value="water_breathing">Water Breathing — Boleh bernafas dalam air</option>
            <option value="invisibility">Invisibility — Tak nampak</option>
            <option value="night_vision">Night Vision — Nampak dalam gelap</option>
            <option value="health_boost">Health Boost — Max HP lebih</option>
            <option value="absorption">Absorption — Extra HP sementara</option>
            <option value="saturation">Saturation — Lapar tak turun</option>
            <option value="luck">Luck — Looting lebih bertuah</option>
            <option value="slow_falling">Slow Falling — Jatuh perlahan</option>
            <option value="conduit_power">Conduit Power — Underwater boost</option>
            <option value="dolphins_grace">Dolphin's Grace — Laju dalam air</option>
            <option value="hero_of_the_village">Hero of the Village</option>
          </optgroup>
          <optgroup label="❌ Negatif">
            <option value="slowness">Slowness — Perlahan</option>
            <option value="mining_fatigue">Mining Fatigue — Lambat menggali</option>
            <option value="instant_damage">Instant Damage — Terus kena damage</option>
            <option value="nausea">Nausea — Skrin putar</option>
            <option value="blindness">Blindness — Buta</option>
            <option value="hunger">Hunger — Lapar laju</option>
            <option value="weakness">Weakness — Damage kurang</option>
            <option value="poison">Poison — Keracunan</option>
            <option value="wither">Wither — HP terkikis</option>
            <option value="glowing">Glowing — Nampak dari jauh</option>
            <option value="levitation">Levitation — Naik ke atas</option>
            <option value="bad_omen">Bad Omen — Trigger Raid</option>
          </optgroup>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="field"><label>Durasi (saat)</label><input type="number" id="eff-dur" value="30" min="1" max="1000000" oninput="gen()"></div>
      <div class="field"><label>Level (0=I, 1=II...)</label><input type="number" id="eff-amp" value="0" min="0" max="255" oninput="gen()"></div>
      <div class="field"><label>Sembunyi Partikel</label>
        <select id="eff-hide" onchange="gen()"><option value="false">Tidak</option><option value="true">Ya</option></select>
      </div>
    </div>
    <button class="btn btn-red btn-sm" onclick="genClearEffect()" style="margin-top:4px">🚫 Clear Effect</button>
  </div>

  <!-- ══ TELEPORT ══ -->
  <div id="sec-tp" class="section">
    <div class="sec-title" style="color:var(--blue)">🌀 Teleport</div>
    <div class="row">
      <div class="field"><label>Target</label><input type="text" id="tp-target" value="<?= $defaultTarget ?>" oninput="gen()"></div>
      <div class="field"><label>Mod</label>
        <select id="tp-mode" onchange="toggleTpMode();gen()">
          <option value="coord">Koordinat XYZ</option>
          <option value="player">Ke Player Lain</option>
        </select>
      </div>
    </div>
    <div id="tp-coord-row" class="row">
      <div class="field"><label>X</label><input type="text" id="tp-x" value="~" oninput="gen()"></div>
      <div class="field"><label>Y</label><input type="text" id="tp-y" value="~" oninput="gen()"></div>
      <div class="field"><label>Z</label><input type="text" id="tp-z" value="~" oninput="gen()"></div>
    </div>
    <div id="tp-player-row" style="display:none" class="row">
      <div class="field"><label>Nama Player</label><input type="text" id="tp-player" value="Steve" oninput="gen()"></div>
    </div>
    <p class="hint">💡 ~ = posisi semasa &nbsp;·&nbsp; ~10 = +10 dari posisi &nbsp;·&nbsp; ^ ^ ^ = arah pandang</p>
  </div>

  <!-- ══ TIME/WEATHER ══ -->
  <div id="sec-time" class="section">
    <div class="sec-title" style="color:var(--orange)">🌤️ Masa & Cuaca</div>
    <div class="row">
      <div class="field"><label>Jenis</label>
        <select id="tw-type" onchange="toggleTwType();gen()">
          <option value="time">⏰ Masa (Time)</option>
          <option value="weather">🌧️ Cuaca (Weather)</option>
        </select>
      </div>
    </div>
    <div id="tw-time-opts">
      <div class="row">
        <div class="field"><label>Tindakan</label>
          <select id="tw-action" onchange="toggleTwTime();gen()">
            <option value="set">Set (tepat)</option>
            <option value="add">Add (tambah)</option>
            <option value="query">Query (semak)</option>
          </select>
        </div>
        <div class="field" id="tw-preset-f"><label>Preset Masa</label>
          <select id="tw-preset" onchange="applyTimePreset()">
            <option value="">-- Preset --</option>
            <option value="0">🌅 Subuh (0)</option>
            <option value="1000">☀️ Pagi (1000)</option>
            <option value="6000">🌞 Tengah Hari (6000)</option>
            <option value="12000">🌇 Petang (12000)</option>
            <option value="13000">🌆 Senja (13000)</option>
            <option value="18000">🌙 Tengah Malam (18000)</option>
          </select>
        </div>
        <div class="field" id="tw-tick-f"><label>Tick (0–24000)</label>
          <input type="number" id="tw-tick" value="6000" min="0" max="24000" oninput="gen()">
        </div>
      </div>
    </div>
    <div id="tw-weather-opts" style="display:none">
      <div class="row">
        <div class="field"><label>Cuaca</label>
          <select id="tw-weather" onchange="gen()">
            <option value="clear">☀️ Cerah (Clear)</option>
            <option value="rain">🌧️ Hujan (Rain)</option>
            <option value="thunder">⛈️ Ribut (Thunder)</option>
          </select>
        </div>
        <div class="field"><label>Durasi (saat, optional)</label>
          <input type="number" id="tw-wdur" value="" min="1" placeholder="Default" oninput="gen()">
        </div>
      </div>
    </div>
  </div>

  <!-- ══ GAMEMODE ══ -->
  <div id="sec-gm" class="section">
    <div class="sec-title" style="color:var(--teal)">🎮 Gamemode</div>
    <div class="row">
      <div class="field"><label>Target</label><input type="text" id="gm-target" value="<?= $defaultTarget ?>" oninput="gen()"></div>
      <div class="field"><label>Mod</label>
        <select id="gm-mode" onchange="gen()">
          <option value="survival">⚔️ Survival</option>
          <option value="creative">🎨 Creative</option>
          <option value="adventure">🗺️ Adventure</option>
          <option value="spectator">👻 Spectator</option>
        </select>
      </div>
    </div>
    <p class="hint">💡 Guna @a untuk tukar mode semua player serentak</p>
  </div>

  <!-- ══ ENCHANT ══ -->
  <div id="sec-ench" class="section">
    <div class="sec-title" style="color:var(--pink)">✨ Enchant</div>
    <div class="row" style="margin-bottom:12px">
      <div class="field">
        <label>Target</label>
        <input type="text" id="enc-target" value="<?= $defaultTarget ?>" oninput="gen()">
      </div>
      <div class="field">
        <label>Equipment Type</label>
        <select id="ench-cat-select" onchange="onEnchCatChange()">
          <option value="All">— All Enchantments —</option>
          <option value="Sword & Axe">⚔️ Sword & Axe</option>
          <option value="Axe">🪓 Axe (tools)</option>
          <option value="Pickaxe">⛏️ Pickaxe</option>
          <option value="Shovel">🪣 Shovel</option>
          <option value="Hoe">🌾 Hoe</option>
          <option value="Armour">🛡️ Armour (All)</option>
          <option value="Helmet">⛑️ Helmet</option>
          <option value="Boots">👟 Boots</option>
          <option value="Bow">🏹 Bow</option>
          <option value="Crossbow">🎯 Crossbow</option>
          <option value="Trident">🔱 Trident</option>
          <option value="Fishing Rod">🎣 Fishing Rod</option>
        </select>
      </div>
    </div>
    <div class="row" style="margin-bottom:12px">
      <div class="field" style="grid-column:1/-1">
        <label>Enchantment</label>
        <select id="ench-select" onchange="onEnchSelect()" size="1">
        </select>
      </div>
    </div>

    <!-- Enchant detail card -->
    <div id="ench-detail-card" style="display:none;background:var(--bg2);border:1px solid rgba(240,111,160,0.25);border-radius:10px;padding:14px 16px;margin-bottom:12px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px">
        <div>
          <div style="font-size:16px;font-weight:800;color:var(--pink)" id="ench-detail-name">—</div>
          <div style="font-size:12px;color:var(--text3);margin-top:3px;font-family:var(--mono)" id="ench-detail-desc">—</div>
        </div>
        <span style="font-size:11px;font-family:var(--mono);background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.3);color:var(--gold);padding:3px 9px;border-radius:20px;white-space:nowrap" id="ench-detail-max">Max I</span>
      </div>
      <div class="ench-level-title">LEVEL</div>
      <div class="level-row" style="margin-top:6px">
        <span class="level-num" id="ench-lvl-num">1</span>
        <input type="range" id="enc-level" min="1" max="5" value="1" oninput="updateEnchLevel();gen()" style="flex:1">
        <span style="font-size:13px;font-weight:700;font-family:var(--mono);color:var(--pink);min-width:28px;text-align:right" id="ench-lvl-roman">I</span>
      </div>
      <div class="ench-warning" id="ench-max-warn" style="display:none;margin-top:8px">⚠️ Level above normal max — may overflow in Survival</div>
    </div>

    <p class="hint">💡 Hold item in hand · /enchant works without Anvil · combine with NBT Builder for full custom items</p>
  </div>

  <!-- ══ SUMMON ══ -->
  <div id="sec-summon" class="section">
    <div class="sec-title" style="color:var(--red)">👾 Summon Entity</div>
    <div class="row">
      <div class="field"><label>Entity</label>
        <select id="sum-entity" onchange="gen()">
          <optgroup label="⚔️ Hostile">
            <option value="zombie">Zombie</option><option value="skeleton">Skeleton</option>
            <option value="creeper">Creeper</option><option value="spider">Spider</option>
            <option value="cave_spider">Cave Spider</option><option value="enderman">Enderman</option>
            <option value="witch">Witch</option><option value="blaze">Blaze</option>
            <option value="ghast">Ghast</option><option value="wither_skeleton">Wither Skeleton</option>
            <option value="piglin_brute">Piglin Brute</option><option value="warden">Warden</option>
            <option value="elder_guardian">Elder Guardian</option><option value="ravager">Ravager</option>
            <option value="evoker">Evoker</option><option value="vindicator">Vindicator</option>
            <option value="phantom">Phantom</option><option value="drowned">Drowned</option>
            <option value="husk">Husk</option><option value="stray">Stray</option>
            <option value="pillager">Pillager</option><option value="shulker">Shulker</option>
            <option value="guardian">Guardian</option><option value="magma_cube">Magma Cube</option>
            <option value="slime">Slime</option><option value="endermite">Endermite</option>
            <option value="silverfish">Silverfish</option><option value="zombie_villager">Zombie Villager</option>
          </optgroup>
          <optgroup label="🐄 Passive">
            <option value="cow">Cow</option><option value="pig">Pig</option>
            <option value="sheep">Sheep</option><option value="chicken">Chicken</option>
            <option value="horse">Horse</option><option value="villager">Villager</option>
            <option value="cat">Cat</option><option value="wolf">Wolf</option>
            <option value="axolotl">Axolotl</option><option value="allay">Allay</option>
            <option value="bee">Bee</option><option value="fox">Fox</option>
            <option value="panda">Panda</option><option value="turtle">Turtle</option>
            <option value="dolphin">Dolphin</option><option value="cod">Cod</option>
            <option value="salmon">Salmon</option><option value="squid">Squid</option>
            <option value="glow_squid">Glow Squid</option><option value="bat">Bat</option>
            <option value="donkey">Donkey</option><option value="mule">Mule</option>
            <option value="llama">Llama</option><option value="parrot">Parrot</option>
            <option value="rabbit">Rabbit</option><option value="ocelot">Ocelot</option>
          </optgroup>
          <optgroup label="💀 Boss">
            <option value="ender_dragon">Ender Dragon</option>
            <option value="wither">Wither</option>
          </optgroup>
          <optgroup label="🔮 Lain-lain">
            <option value="lightning_bolt">Lightning Bolt ⚡</option>
            <option value="fireball">Fireball 🔥</option>
            <option value="armor_stand">Armor Stand</option>
            <option value="item_frame">Item Frame</option>
            <option value="glow_item_frame">Glow Item Frame</option>
            <option value="painting">Painting</option>
            <option value="boat">Boat</option>
            <option value="minecart">Minecart</option>
            <option value="chest_minecart">Chest Minecart</option>
            <option value="tnt">TNT 💣</option>
          </optgroup>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="field"><label>X</label><input type="text" id="sum-x" value="~" oninput="gen()"></div>
      <div class="field"><label>Y</label><input type="text" id="sum-y" value="~" oninput="gen()"></div>
      <div class="field"><label>Z</label><input type="text" id="sum-z" value="~" oninput="gen()"></div>
    </div>
    <p class="hint">⚠️ Ender Dragon & Wither akan terus menyerang — berhati-hati!</p>
  </div>

  <!-- ══ XP ══ -->
  <div id="sec-xp" class="section">
    <div class="sec-title" style="color:var(--gold)">⭐ Experience (XP)</div>
    <div class="row">
      <div class="field"><label>Target</label><input type="text" id="xp-target" value="<?= $defaultTarget ?>" oninput="gen()"></div>
      <div class="field"><label>Operasi</label>
        <select id="xp-op" onchange="gen()">
          <option value="add">➕ Tambah</option>
          <option value="set">🔢 Set</option>
          <option value="query">🔍 Semak</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="field"><label>Jumlah</label><input type="number" id="xp-amount" value="100" min="0" oninput="gen()"></div>
      <div class="field"><label>Jenis</label>
        <select id="xp-type" onchange="gen()">
          <option value="points">XP Points</option>
          <option value="levels">Level (L)</option>
        </select>
      </div>
    </div>
    <p class="hint">💡 Guna "levels" untuk naik level terus · "points" untuk XP bar</p>
  </div>

  <!-- ══ KILL ══ -->
  <div id="sec-kill" class="section">
    <div class="sec-title" style="color:#ff6666">💀 Kill Entity</div>
    <div class="field" style="margin-bottom:12px"><label>Target</label>
      <select id="kill-target" onchange="toggleKillCustom();gen()">
        <option value="@e[type=!player]">@e[type=!player] — Semua mob (bukan player)</option>
        <option value="@e">@e — Semua entity</option>
        <option value="@p">@p — Player terdekat</option>
        <option value="@a">@a — Semua player</option>
        <option value="@e[type=zombie]">@e[type=zombie] — Semua Zombie</option>
        <option value="@e[type=skeleton]">@e[type=skeleton] — Semua Skeleton</option>
        <option value="@e[type=creeper]">@e[type=creeper] — Semua Creeper</option>
        <option value="@e[type=item]">@e[type=item] — Item drops</option>
        <option value="custom">✏️ Custom...</option>
      </select>
    </div>
    <div id="kill-custom-wrap" style="display:none" class="field">
      <label>Custom Target</label>
      <input type="text" id="kill-custom" placeholder="cth: @e[type=creeper,r=10]" oninput="gen()">
    </div>
    <div style="background:rgba(248,113,113,0.06);border:1px solid rgba(248,113,113,0.2);border-radius:8px;padding:10px 12px;margin-top:8px">
      <p class="hint" style="color:var(--red)">⚠️ Berhati-hati dengan @a dan @e — boleh kill semua player!</p>
    </div>
  </div>

  <!-- ══ CLEAR ══ -->
  <div id="sec-clear" class="section">
    <div class="sec-title" style="color:var(--blue)">🗑️ Clear Inventory</div>
    <div class="row">
      <div class="field"><label>Target</label><input type="text" id="clr-target" value="<?= $defaultTarget ?>" oninput="gen()"></div>
      <div class="field"><label>Item (kosong = semua)</label>
        <input type="text" id="clr-item" placeholder="cth: diamond_sword" oninput="gen()">
      </div>
    </div>
    <p class="hint">💡 Biar kosong untuk clear semua inventory · Atau isi ID item untuk clear item tertentu sahaja</p>
  </div>

  <!-- OUTPUT -->
  <div class="output-section">
    <div class="output-header">
      <span class="output-label">// Output Command</span>
      <span class="output-ver">Java Edition 1.20+</span>
    </div>
    <div class="cmd-box" id="cmd-display">give nizkbiits diamond_sword 1</div>
    <div class="cmd-actions">
      <button class="btn btn-green" onclick="copyCmd()">📋 Copy Command</button>
      <button class="btn btn-gold" onclick="saveToHistory()">💾 Simpan</button>
      <button class="btn" style="background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.4);color:var(--gold2)" onclick="addFav()">⭐ Fav</button>
    </div>
  </div>

</div><!-- /card -->
</div><!-- /main col -->

<!-- SIDE PANEL -->
<div class="panel-sticky">

  <!-- DB STATUS -->
  <?php if(!$dbOk): ?>
  <div style="background:rgba(240,96,96,0.08);border:1px solid rgba(240,96,96,0.25);border-radius:8px;padding:10px 12px;font-size:11px;font-family:var(--mono);color:var(--red)">
    ⚠️ MySQL tidak connected.<br>
    <span style="color:var(--text3)">Pastikan XAMPP MySQL running dan database <b>minecraft_cmd</b> dah import.</span>
  </div>
  <?php elseif(!empty($dbStatus['tables_missing'])): ?>
  <div style="background:rgba(245,166,35,0.08);border:1px solid rgba(245,166,35,0.3);border-radius:8px;padding:10px 12px;font-size:11px;font-family:var(--mono);color:var(--gold)">
    ⚠️ Database connected tapi tables belum wujud.<br>
    <span style="color:var(--text3)">Import fail <b>database.sql</b> dalam phpMyAdmin → Import → Go.</span>
  </div>
  <?php else: ?>
  <div style="background:rgba(61,220,132,0.06);border:1px solid rgba(61,220,132,0.2);border-radius:8px;padding:8px 12px;font-size:11px;font-family:var(--mono);color:var(--green);display:flex;align-items:center;gap:8px">
    <span style="width:6px;height:6px;border-radius:50%;background:var(--green);display:inline-block;flex-shrink:0"></span>
    MySQL ✓ · <?= $dbStatus['history'] ?> history · <?= $dbStatus['favourites'] ?> favs · <?= $dbStatus['kits'] ?> kits
  </div>
  <?php endif; ?>

  <!-- HISTORY -->
  <div class="card">
    <div class="card-header">
      <div class="card-header-dot" style="background:var(--gold)"></div>
      📜 HISTORY
      <button class="btn btn-red btn-sm" style="margin-left:auto" onclick="historyClear()">Padam</button>
    </div>
    <div class="hist-list" id="history-list">
      <div class="hist-empty">📭<br>Tiada sejarah lagi.<br>Generate & klik "Simpan".</div>
    </div>
  </div>

  <!-- QUICK REF -->
  <div class="card">
    <div class="card-header">
      <div class="card-header-dot" style="background:var(--blue)"></div>
      📖 QUICK REFERENCE
    </div>
    <div class="card-body" style="padding:14px 16px">
      <div class="ref-row"><span class="ref-key">@p</span><span class="ref-val">Player terdekat</span></div>
      <div class="ref-row"><span class="ref-key">@a</span><span class="ref-val">Semua player</span></div>
      <div class="ref-row"><span class="ref-key">@r</span><span class="ref-val">Player rawak</span></div>
      <div class="ref-row"><span class="ref-key">@s</span><span class="ref-val">Diri sendiri</span></div>
      <div class="ref-row"><span class="ref-key">@e</span><span class="ref-val">Semua entity</span></div>
      <div style="height:8px"></div>
      <div class="ref-row"><span class="ref-key">~</span><span class="ref-val">Posisi semasa</span></div>
      <div class="ref-row"><span class="ref-key">~10</span><span class="ref-val">+10 dari posisi</span></div>
      <div class="ref-row"><span class="ref-key">^ ^ ^</span><span class="ref-val">Arah pandang</span></div>
      <div style="height:8px"></div>
      <div class="ref-row"><span class="ref-key" style="color:var(--red)">cheats</span><span class="ref-val">Mesti ON</span></div>
    </div>
  </div>

  <!-- FAVOURITES -->
  <div class="card">
    <div class="card-header">
      <div class="card-header-dot" style="background:var(--gold2)"></div>
      ⭐ FAVOURITES
      <span style="margin-left:auto;font-size:10px;color:var(--green);font-family:var(--mono)">MySQL</span>
    </div>
    <div style="padding:10px 12px;border-bottom:1px solid var(--border);display:flex;gap:8px">
      <input type="text" id="fav-note" placeholder="Optional note..."
        style="flex:1;font-size:12px;padding:5px 8px;border-radius:6px;background:var(--bg2);border:1px solid var(--border);color:var(--text);font-family:var(--mono)">
      <button class="btn btn-gold btn-sm" onclick="addFav()">⭐ Save</button>
    </div>
    <div class="hist-list" id="fav-list">
      <div class="hist-empty">📭<br>Tiada favourite lagi.</div>
    </div>
  </div>

</div>
</div><!-- /layout -->

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ═══════════════════════════════════════════
// ITEM DATABASE (expanded)
// ═══════════════════════════════════════════
const ITEMS = {
  'Weapons':[
    ['wooden_sword','Wooden Sword'],['stone_sword','Stone Sword'],
    ['iron_sword','Iron Sword'],['golden_sword','Golden Sword'],
    ['diamond_sword','Diamond Sword'],['netherite_sword','Netherite Sword'],
    ['bow','Bow'],['crossbow','Crossbow'],['trident','Trident'],
    ['arrow','Arrow'],['spectral_arrow','Spectral Arrow'],
    ['tipped_arrow','Tipped Arrow'],['firework_rocket','Firework Rocket'],
  ],
  'Tools':[
    ['wooden_pickaxe','Wooden Pickaxe'],['stone_pickaxe','Stone Pickaxe'],
    ['iron_pickaxe','Iron Pickaxe'],['golden_pickaxe','Golden Pickaxe'],
    ['diamond_pickaxe','Diamond Pickaxe'],['netherite_pickaxe','Netherite Pickaxe'],
    ['wooden_axe','Wooden Axe'],['stone_axe','Stone Axe'],
    ['iron_axe','Iron Axe'],['golden_axe','Golden Axe'],
    ['diamond_axe','Diamond Axe'],['netherite_axe','Netherite Axe'],
    ['wooden_shovel','Wooden Shovel'],['stone_shovel','Stone Shovel'],
    ['iron_shovel','Iron Shovel'],['diamond_shovel','Diamond Shovel'],['netherite_shovel','Netherite Shovel'],
    ['wooden_hoe','Wooden Hoe'],['stone_hoe','Stone Hoe'],
    ['iron_hoe','Iron Hoe'],['diamond_hoe','Diamond Hoe'],['netherite_hoe','Netherite Hoe'],
    ['fishing_rod','Fishing Rod'],['flint_and_steel','Flint and Steel'],
    ['shears','Shears'],['compass','Compass'],['clock','Clock'],
    ['map','Map'],['spyglass','Spyglass'],['lead','Lead'],
    ['name_tag','Name Tag'],['book','Book'],['writable_book','Book & Quill'],
    ['shield','Shield'],
  ],
  'Armour':[
    ['leather_helmet','Leather Helmet'],['leather_chestplate','Leather Chestplate'],
    ['leather_leggings','Leather Leggings'],['leather_boots','Leather Boots'],
    ['chainmail_helmet','Chainmail Helmet'],['chainmail_chestplate','Chainmail Chestplate'],
    ['chainmail_leggings','Chainmail Leggings'],['chainmail_boots','Chainmail Boots'],
    ['iron_helmet','Iron Helmet'],['iron_chestplate','Iron Chestplate'],
    ['iron_leggings','Iron Leggings'],['iron_boots','Iron Boots'],
    ['golden_helmet','Golden Helmet'],['golden_chestplate','Golden Chestplate'],
    ['golden_leggings','Golden Leggings'],['golden_boots','Golden Boots'],
    ['diamond_helmet','Diamond Helmet'],['diamond_chestplate','Diamond Chestplate'],
    ['diamond_leggings','Diamond Leggings'],['diamond_boots','Diamond Boots'],
    ['netherite_helmet','Netherite Helmet'],['netherite_chestplate','Netherite Chestplate'],
    ['netherite_leggings','Netherite Leggings'],['netherite_boots','Netherite Boots'],
    ['elytra','Elytra'],['turtle_helmet','Turtle Helmet'],
  ],
  'Stone & Dirt':[
    ['stone','Stone'],['granite','Granite'],['polished_granite','Polished Granite'],
    ['diorite','Diorite'],['polished_diorite','Polished Diorite'],
    ['andesite','Andesite'],['polished_andesite','Polished Andesite'],
    ['cobblestone','Cobblestone'],['mossy_cobblestone','Mossy Cobblestone'],
    ['dirt','Dirt'],['coarse_dirt','Coarse Dirt'],['podzol','Podzol'],
    ['rooted_dirt','Rooted Dirt'],['grass_block','Grass Block'],
    ['mycelium','Mycelium'],['sand','Sand'],['red_sand','Red Sand'],
    ['gravel','Gravel'],['clay','Clay'],['mud','Mud'],
    ['packed_mud','Packed Mud'],['muddy_mangrove_roots','Muddy Mangrove Roots'],
    ['bedrock','Bedrock'],['obsidian','Obsidian'],['crying_obsidian','Crying Obsidian'],
  ],
  'Wood & Nature':[
    ['oak_log','Oak Log'],['spruce_log','Spruce Log'],['birch_log','Birch Log'],
    ['jungle_log','Jungle Log'],['acacia_log','Acacia Log'],['dark_oak_log','Dark Oak Log'],
    ['mangrove_log','Mangrove Log'],['cherry_log','Cherry Log'],
    ['oak_planks','Oak Planks'],['spruce_planks','Spruce Planks'],
    ['birch_planks','Birch Planks'],['jungle_planks','Jungle Planks'],
    ['acacia_planks','Acacia Planks'],['dark_oak_planks','Dark Oak Planks'],
    ['mangrove_planks','Mangrove Planks'],['cherry_planks','Cherry Planks'],
    ['oak_leaves','Oak Leaves'],['spruce_leaves','Spruce Leaves'],
    ['bamboo_block','Bamboo Block'],['bamboo','Bamboo'],
    ['vine','Vine'],['lily_pad','Lily Pad'],['moss_block','Moss Block'],
    ['shroomlight','Shroomlight'],['brown_mushroom','Brown Mushroom'],
    ['red_mushroom','Red Mushroom'],
  ],
  'Ores':[
    ['coal_ore','Coal Ore'],['deepslate_coal_ore','Deepslate Coal Ore'],
    ['iron_ore','Iron Ore'],['deepslate_iron_ore','Deepslate Iron Ore'],
    ['copper_ore','Copper Ore'],['deepslate_copper_ore','Deepslate Copper Ore'],
    ['gold_ore','Gold Ore'],['deepslate_gold_ore','Deepslate Gold Ore'],
    ['redstone_ore','Redstone Ore'],['deepslate_redstone_ore','Deepslate Redstone Ore'],
    ['lapis_ore','Lapis Ore'],['deepslate_lapis_ore','Deepslate Lapis Ore'],
    ['diamond_ore','Diamond Ore'],['deepslate_diamond_ore','Deepslate Diamond Ore'],
    ['emerald_ore','Emerald Ore'],['deepslate_emerald_ore','Deepslate Emerald Ore'],
    ['nether_gold_ore','Nether Gold Ore'],['nether_quartz_ore','Nether Quartz Ore'],
    ['ancient_debris','Ancient Debris'],
  ],
  'Materials':[
    ['coal','Coal'],['charcoal','Charcoal'],
    ['iron_ingot','Iron Ingot'],['iron_nugget','Iron Nugget'],
    ['copper_ingot','Copper Ingot'],['raw_copper','Raw Copper'],
    ['gold_ingot','Gold Ingot'],['gold_nugget','Gold Nugget'],['raw_gold','Raw Gold'],
    ['redstone','Redstone'],['lapis_lazuli','Lapis Lazuli'],
    ['diamond','Diamond'],['emerald','Emerald'],
    ['netherite_ingot','Netherite Ingot'],['netherite_scrap','Netherite Scrap'],
    ['quartz','Nether Quartz'],['raw_iron','Raw Iron'],
    ['amethyst_shard','Amethyst Shard'],['echo_shard','Echo Shard'],
    ['prismarine_shard','Prismarine Shard'],['prismarine_crystals','Prismarine Crystals'],
    ['nautilus_shell','Nautilus Shell'],['heart_of_the_sea','Heart of the Sea'],
    ['nether_star','Nether Star'],['dragon_egg','Dragon Egg'],
    ['end_crystal','End Crystal'],['beacon','Beacon'],
    ['conduit','Conduit'],
  ],
  'Food':[
    ['apple','Apple'],['golden_apple','Golden Apple'],
    ['enchanted_golden_apple','Enchanted Golden Apple'],
    ['bread','Bread'],['carrot','Carrot'],['golden_carrot','Golden Carrot'],
    ['potato','Potato'],['baked_potato','Baked Potato'],
    ['melon_slice','Melon Slice'],['glistering_melon_slice','Glistering Melon Slice'],
    ['pumpkin_pie','Pumpkin Pie'],['cookie','Cookie'],['cake','Cake'],
    ['beef','Raw Beef'],['cooked_beef','Steak'],
    ['porkchop','Raw Porkchop'],['cooked_porkchop','Cooked Porkchop'],
    ['chicken','Raw Chicken'],['cooked_chicken','Cooked Chicken'],
    ['mutton','Raw Mutton'],['cooked_mutton','Cooked Mutton'],
    ['rabbit','Raw Rabbit'],['cooked_rabbit','Cooked Rabbit'],
    ['cod','Raw Cod'],['cooked_cod','Cooked Cod'],
    ['salmon','Raw Salmon'],['cooked_salmon','Cooked Salmon'],
    ['tropical_fish','Tropical Fish'],['pufferfish','Pufferfish'],
    ['mushroom_stew','Mushroom Stew'],['rabbit_stew','Rabbit Stew'],
    ['beetroot','Beetroot'],['beetroot_soup','Beetroot Soup'],
    ['sweet_berries','Sweet Berries'],['glow_berries','Glow Berries'],
    ['honey_bottle','Honey Bottle'],['suspicious_stew','Suspicious Stew'],
    ['dried_kelp','Dried Kelp'],
  ],
  'Special':[
    ['totem_of_undying','Totem of Undying'],['ender_pearl','Ender Pearl'],
    ['eye_of_ender','Eye of Ender'],['blaze_rod','Blaze Rod'],
    ['blaze_powder','Blaze Powder'],['magma_cream','Magma Cream'],
    ['ghast_tear','Ghast Tear'],['gunpowder','Gunpowder'],
    ['fermented_spider_eye','Fermented Spider Eye'],['spider_eye','Spider Eye'],
    ['phantom_membrane','Phantom Membrane'],['rabbit_foot','Rabbit Foot'],
    ['experience_bottle','Bottle o Enchanting'],['saddle','Saddle'],
    ['shulker_shell','Shulker Shell'],['turtle_egg','Turtle Egg'],
    ['sniffer_egg','Sniffer Egg'],['disc_fragment_5','Disc Fragment 5'],
    ['music_disc_13','Music Disc 13'],['music_disc_cat','Music Disc Cat'],
    ['music_disc_otherside','Music Disc Otherside'],
    ['ominous_bottle','Ominous Bottle'],
  ],
  'Redstone':[
    ['redstone','Redstone'],['redstone_block','Redstone Block'],
    ['redstone_torch','Redstone Torch'],['redstone_lamp','Redstone Lamp'],
    ['lever','Lever'],['stone_button','Stone Button'],
    ['oak_button','Oak Button'],['tripwire_hook','Tripwire Hook'],
    ['observer','Observer'],['dispenser','Dispenser'],['dropper','Dropper'],
    ['hopper','Hopper'],['comparator','Comparator'],['repeater','Repeater'],
    ['daylight_detector','Daylight Detector'],['target','Target'],
    ['sticky_piston','Sticky Piston'],['piston','Piston'],
    ['slime_block','Slime Block'],['honey_block','Honey Block'],
    ['tnt','TNT'],['command_block','Command Block'],
    ['structure_block','Structure Block'],
  ],
  'Nether & End':[
    ['netherrack','Netherrack'],['soul_sand','Soul Sand'],['soul_soil','Soul Soil'],
    ['nether_bricks','Nether Bricks'],['red_nether_bricks','Red Nether Bricks'],
    ['magma_block','Magma Block'],['glowstone','Glowstone'],
    ['basalt','Basalt'],['polished_basalt','Polished Basalt'],
    ['blackstone','Blackstone'],['polished_blackstone','Polished Blackstone'],
    ['gilded_blackstone','Gilded Blackstone'],['warped_stem','Warped Stem'],
    ['crimson_stem','Crimson Stem'],['warped_planks','Warped Planks'],
    ['crimson_planks','Crimson Planks'],['nether_wart','Nether Wart'],
    ['end_stone','End Stone'],['end_stone_bricks','End Stone Bricks'],
    ['purpur_block','Purpur Block'],['chorus_fruit','Chorus Fruit'],
    ['popped_chorus_fruit','Popped Chorus Fruit'],['shulker_box','Shulker Box'],
    ['dragon_breath','Dragon Breath'],
  ],
  'Decoration':[
    ['white_wool','White Wool'],['orange_wool','Orange Wool'],
    ['magenta_wool','Magenta Wool'],['light_blue_wool','Light Blue Wool'],
    ['yellow_wool','Yellow Wool'],['lime_wool','Lime Wool'],
    ['pink_wool','Pink Wool'],['gray_wool','Gray Wool'],
    ['cyan_wool','Cyan Wool'],['purple_wool','Purple Wool'],
    ['blue_wool','Blue Wool'],['red_wool','Red Wool'],['black_wool','Black Wool'],
    ['white_carpet','White Carpet'],['white_concrete','White Concrete'],
    ['white_concrete_powder','White Concrete Powder'],
    ['white_terracotta','White Terracotta'],['white_stained_glass','White Stained Glass'],
    ['flower_pot','Flower Pot'],['item_frame','Item Frame'],
    ['glow_item_frame','Glow Item Frame'],['painting','Painting'],
    ['banner','Banner'],['armor_stand','Armor Stand'],
    ['lantern','Lantern'],['soul_lantern','Soul Lantern'],
    ['torch','Torch'],['soul_torch','Soul Torch'],
    ['sea_lantern','Sea Lantern'],['chain','Chain'],
  ],
};

// ═══════════════════════════════════════════
// ENCHANT DATABASE
// ═══════════════════════════════════════════
const ENCHANTS = {
  'All':[
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Item lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Use XP to repair'},
    {id:'curse_of_binding',name:'Curse of Binding',max:1,desc:'Cannot be removed'},
    {id:'curse_of_vanishing',name:'Curse of Vanishing',max:1,desc:'Lost on death'},
  ],
  'Sword & Axe':[
    {id:'sharpness',name:'Sharpness',max:5,desc:'Damage lebih pada semua mob'},
    {id:'smite',name:'Smite',max:5,desc:'Extra damage to undead'},
    {id:'bane_of_arthropods',name:'Bane of Arthropods',max:5,desc:'Extra damage to arthropods'},
    {id:'knockback',name:'Knockback',max:2,desc:'Push enemies farther'},
    {id:'fire_aspect',name:'Fire Aspect',max:2,desc:'Set enemies on fire'},
    {id:'looting',name:'Looting',max:3,desc:'More drops from mobs'},
    {id:'sweeping',name:'Sweeping Edge',max:3,desc:'Stronger sweep attack'},
  ],
  'Axe':[
    {id:'sharpness',name:'Sharpness',max:5,desc:'Damage lebih pada semua mob'},
    {id:'smite',name:'Smite',max:5,desc:'Extra damage to undead'},
    {id:'bane_of_arthropods',name:'Bane of Arthropods',max:5,desc:'Extra damage to arthropods'},
    {id:'knockback',name:'Knockback',max:2,desc:'Tolak musuh jauh'},
    {id:'fire_aspect',name:'Fire Aspect',max:2,desc:'Set on fire'},
    {id:'looting',name:'Looting',max:3,desc:'Drop lebih banyak'},
    {id:'efficiency',name:'Efficiency',max:5,desc:'Chop wood faster'},
    {id:'silk_touch',name:'Silk Touch',max:1,desc:'Drop original block'},
    {id:'fortune',name:'Fortune',max:3,desc:'Drop lebih banyak'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
  ],
  'Pickaxe':[
    {id:'efficiency',name:'Efficiency',max:5,desc:'Dig faster'},
    {id:'silk_touch',name:'Silk Touch',max:1,desc:'Drop block asal (jangan combine ngan Fortune)'},
    {id:'fortune',name:'Fortune',max:3,desc:'More ore drops'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Pickaxe lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
  ],
  'Shovel':[
    {id:'efficiency',name:'Efficiency',max:5,desc:'Dig fast'},
    {id:'silk_touch',name:'Silk Touch',max:1,desc:'Drop original block'},
    {id:'fortune',name:'Fortune',max:3,desc:'More flint drops'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
  ],
  'Hoe':[
    {id:'efficiency',name:'Efficiency',max:5,desc:'Plow faster'},
    {id:'silk_touch',name:'Silk Touch',max:1,desc:'Drop original block'},
    {id:'fortune',name:'Fortune',max:3,desc:'More drops'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
  ],
  'Armour':[
    {id:'protection',name:'Protection',max:4,desc:'Reduce all damage'},
    {id:'fire_protection',name:'Fire Protection',max:4,desc:'Reduce fire damage'},
    {id:'blast_protection',name:'Blast Protection',max:4,desc:'Reduce explosion damage'},
    {id:'projectile_protection',name:'Projectile Protection',max:4,desc:'Reduce projectile damage'},
    {id:'thorns',name:'Thorns',max:3,desc:'Reflect damage'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Armour lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
    {id:'curse_of_binding',name:'Curse of Binding',max:1,desc:'Cannot be removed'},
    {id:'curse_of_vanishing',name:'Curse of Vanishing',max:1,desc:'Lost on death'},
  ],
  'Helmet':[
    {id:'respiration',name:'Respiration',max:3,desc:'Breathe longer underwater'},
    {id:'aqua_affinity',name:'Aqua Affinity',max:1,desc:'Mine normally underwater'},
  ],
  'Boots':[
    {id:'feather_falling',name:'Feather Falling',max:4,desc:'Reduce fall damage'},
    {id:'depth_strider',name:'Depth Strider',max:3,desc:'Faster in water'},
    {id:'frost_walker',name:'Frost Walker',max:2,desc:'Walk on water (freeze)'},
    {id:'soul_speed',name:'Soul Speed',max:3,desc:'Faster on soul sand'},
    {id:'swift_sneak',name:'Swift Sneak',max:3,desc:'Faster while crouching'},
  ],
  'Bow':[
    {id:'power',name:'Power',max:5,desc:'Stronger arrows'},
    {id:'punch',name:'Punch',max:2,desc:'Arrow knockback'},
    {id:'flame',name:'Flame',max:1,desc:'Arrows set on fire'},
    {id:'infinity',name:'Infinity',max:1,desc:'Infinite arrows'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Bow lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
    {id:'curse_of_vanishing',name:'Curse of Vanishing',max:1,desc:'Lost on death'},
  ],
  'Crossbow':[
    {id:'multishot',name:'Multishot',max:1,desc:'Shoot 3 arrows at once'},
    {id:'piercing',name:'Piercing',max:4,desc:'Arrows pierce enemies'},
    {id:'quick_charge',name:'Quick Charge',max:3,desc:'Reload faster'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
    {id:'curse_of_vanishing',name:'Curse of Vanishing',max:1,desc:'Lost on death'},
  ],
  'Trident':[
    {id:'channeling',name:'Channeling',max:1,desc:'Summon lightning in storm'},
    {id:'loyalty',name:'Loyalty',max:3,desc:'Trident returns'},
    {id:'impaling',name:'Impaling',max:5,desc:'Extra damage to aquatic mobs'},
    {id:'riptide',name:'Riptide',max:3,desc:'Launch yourself in rain'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
    {id:'curse_of_vanishing',name:'Curse of Vanishing',max:1,desc:'Lost on death'},
  ],
  'Fishing Rod':[
    {id:'luck_of_the_sea',name:'Luck of the Sea',max:3,desc:'Better fishing loot'},
    {id:'lure',name:'Lure',max:3,desc:'Fish bite faster'},
    {id:'unbreaking',name:'Unbreaking',max:3,desc:'Lasts longer'},
    {id:'mending',name:'Mending',max:1,desc:'Repair with XP'},
    {id:'curse_of_vanishing',name:'Curse of Vanishing',max:1,desc:'Lost on death'},
  ],
};

// ═══════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════
let curTab = 'give';
let selectedItem = 'diamond_sword';
let selectedEnch = null;
let curEnchCat = 'All';
// history loaded via AJAX
const romans = ['','I','II','III','IV','V','VI','VII','VIII','IX','X'];

// ═══════════════════════════════════════════
// INIT GIVE
// ═══════════════════════════════════════════
function initGive() {
  // Populate category dropdown
  const catSel = document.getElementById('give-cat');
  catSel.innerHTML = '<option value="">— All Items —</option>';
  Object.keys(ITEMS).forEach(cat => {
    const o = document.createElement('option');
    o.value = cat; o.textContent = cat;
    catSel.appendChild(o);
  });
  filterGiveDropdown();
}

function filterGiveDropdown() {
  const cat = document.getElementById('give-cat').value;
  const search = document.getElementById('give-search').value.toLowerCase();
  const itemSel = document.getElementById('give-item-select');

  let all = cat ? (ITEMS[cat]||[]) : Object.values(ITEMS).flat();
  if(search) all = all.filter(([id,name]) => name.toLowerCase().includes(search) || id.includes(search));

  // dedupe
  const seen = new Set();
  all = all.filter(([id])=>{ if(seen.has(id)) return false; seen.add(id); return true; });

  document.getElementById('give-count-label').textContent = all.length + ' item';
  itemSel.innerHTML = '';
  all.forEach(([id,name]) => {
    const o = document.createElement('option');
    o.value = id; o.textContent = name;
    if(id === selectedItem) o.selected = true;
    itemSel.appendChild(o);
  });
  // if current selectedItem not in list, pick first
  if(itemSel.options.length && !itemSel.value) {
    selectedItem = itemSel.options[0].value;
    itemSel.options[0].selected = true;
  }
  document.getElementById('selected-item-id').textContent = selectedItem;
  gen();
}

function selectItemFromDropdown() {
  const sel = document.getElementById('give-item-select');
  selectedItem = sel.value;
  document.getElementById('selected-item-id').textContent = selectedItem;
  gen(); playSelect();
}

function filterGive() { filterGiveDropdown(); }

// ═══════════════════════════════════════════
// INIT ENCHANT
// ═══════════════════════════════════════════
function initEnch() {
  populateEnchDropdown('All');
}

function onEnchCatChange() {
  const cat = document.getElementById('ench-cat-select').value;
  curEnchCat = cat;
  selectedEnch = null;
  document.getElementById('ench-detail-card').style.display = 'none';
  populateEnchDropdown(cat);
  gen();
}

function populateEnchDropdown(cat) {
  const sel = document.getElementById('ench-select');
  let list = ENCHANTS[cat] || [];
  // dedupe by id
  const seen = new Set();
  list = list.filter(e => { if(seen.has(e.id)) return false; seen.add(e.id); return true; });

  sel.innerHTML = '<option value="">— Select Enchantment —</option>';
  list.forEach(e => {
    const o = document.createElement('option');
    o.value = e.id;
    o.textContent = `${e.name}  (Max ${romans[e.max]})`;
    if(e.id === selectedEnch) o.selected = true;
    sel.appendChild(o);
  });
}

function onEnchSelect() {
  const sel = document.getElementById('ench-select');
  const id = sel.value;
  if(!id) { document.getElementById('ench-detail-card').style.display='none'; selectedEnch=null; gen(); return; }

  // Find enchant data across all cats
  let enchData = null;
  Object.values(ENCHANTS).forEach(list => {
    const found = list.find(e => e.id === id);
    if(found) enchData = found;
  });

  if(!enchData) return;
  selectedEnch = id;

  // Update detail card
  document.getElementById('ench-detail-card').style.display = '';
  document.getElementById('ench-detail-name').textContent = enchData.name;
  document.getElementById('ench-detail-desc').textContent = enchData.desc;
  document.getElementById('ench-detail-max').textContent = 'Max ' + romans[enchData.max];

  const lvlEl = document.getElementById('enc-level');
  lvlEl.max = enchData.max;
  if(parseInt(lvlEl.value) > enchData.max) lvlEl.value = enchData.max;
  updateEnchLevel();
  gen();
  playSelect();
}

function filterEnch() {
  // Keep for compatibility — no longer used but referenced
}

function updateEnchLevel() {
  const v = parseInt(document.getElementById('enc-level').value);
  const max = parseInt(document.getElementById('enc-level').max);
  document.getElementById('ench-lvl-num').textContent = v;
  document.getElementById('ench-lvl-roman').textContent = romans[v] || v;
  document.getElementById('ench-max-warn').style.display = v > max ? '' : 'none';
}

// ═══════════════════════════════════════════
// TABS
// ═══════════════════════════════════════════
function setTab(id, btn) {
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
  document.getElementById('sec-'+id).classList.add('active');
  btn.classList.add('active');
  curTab = id;
  gen();
}

// ═══════════════════════════════════════════
// SYNC TARGET
// ═══════════════════════════════════════════
function syncTarget(val) {
  ['give','eff','tp','gm','enc','xp','clr'].forEach(p=>{
    const el = document.getElementById(p+'-target');
    if(el) el.value = val;
  });
  gen();
}
function setTarget(val) {
  document.getElementById('global-target').value = val;
  syncTarget(val);
}

// ═══════════════════════════════════════════
// TOGGLE HELPERS
// ═══════════════════════════════════════════
function toggleTpMode(){
  const m = document.getElementById('tp-mode').value;
  document.getElementById('tp-coord-row').style.display = m==='coord'?'':'none';
  document.getElementById('tp-player-row').style.display = m==='player'?'':'none';
}
function toggleTwType(){
  const t = document.getElementById('tw-type').value;
  document.getElementById('tw-time-opts').style.display = t==='time'?'':'none';
  document.getElementById('tw-weather-opts').style.display = t==='weather'?'':'none';
}
function toggleTwTime(){
  const a = document.getElementById('tw-action').value;
  document.getElementById('tw-tick-f').style.display = a==='query'?'none':'';
  document.getElementById('tw-preset-f').style.display = a==='set'?'':'none';
}
function toggleKillCustom(){
  document.getElementById('kill-custom-wrap').style.display = document.getElementById('kill-target').value==='custom'?'':'none';
}
function applyTimePreset(){
  const v = document.getElementById('tw-preset').value;
  if(v!=='') { document.getElementById('tw-tick').value=v; gen(); }
}

// ═══════════════════════════════════════════
// GENERATE
// ═══════════════════════════════════════════
function v(id){ const el=document.getElementById(id); return el?el.value:''; }
function gen(){
  let cmd='';
  if(curTab==='give'){
    const count = Math.min(64, Math.max(1, parseInt(v('give-count'))||1));
    cmd=`/give ${v('give-target')} ${selectedItem} ${count}`;
  } else if(curTab==='effect'){
    cmd=`/effect give ${v('eff-target')} ${v('eff-type')} ${v('eff-dur')} ${v('eff-amp')} ${v('eff-hide')}`;
  } else if(curTab==='tp'){
    if(v('tp-mode')==='coord') cmd=`/tp ${v('tp-target')} ${v('tp-x')} ${v('tp-y')} ${v('tp-z')}`;
    else cmd=`/tp ${v('tp-target')} ${v('tp-player')}`;
  } else if(curTab==='time'){
    if(v('tw-type')==='time'){
      const a=v('tw-action');
      cmd = a==='query' ? '/time query daytime' : `/time ${a} ${v('tw-tick')}`;
    } else {
      const dur=v('tw-wdur');
      cmd=`/weather ${v('tw-weather')}${dur?' '+dur:''}`;
    }
  } else if(curTab==='gm'){
    cmd=`/gamemode ${v('gm-mode')} ${v('gm-target')}`;
  } else if(curTab==='ench'){
    if(selectedEnch) cmd=`/enchant ${v('enc-target')} ${selectedEnch} ${v('enc-level')}`;
    else cmd='// Pilih enchantment dahulu';
  } else if(curTab==='summon'){
    cmd=`/summon ${v('sum-entity')} ${v('sum-x')} ${v('sum-y')} ${v('sum-z')}`;
  } else if(curTab==='xp'){
    const op=v('xp-op');
    const isLevels = v('xp-type')==='levels';
    if(op==='query') cmd=`/xp query ${v('xp-target')} ${isLevels?'levels':'points'}`;
    else if(op==='set') cmd=`/xp set ${v('xp-target')} ${v('xp-amount')}${isLevels?' levels':' points'}`;
    else cmd=`/xp add ${v('xp-target')} ${v('xp-amount')}${isLevels?'L':''}`;
  } else if(curTab==='kill'){
    const t=v('kill-target');
    cmd=`/kill ${t==='custom'?v('kill-custom'):t}`;
  } else if(curTab==='clear'){
    const item=v('clr-item').trim();
    cmd=`/clear ${v('clr-target')}${item?' '+item:''}`;
  }
  document.getElementById('cmd-display').textContent = cmd;
}

function genClearEffect(){
  document.getElementById('cmd-display').textContent=`/effect clear ${v('eff-target')}`;
  playCopy();showToast('✅ Effect clear — Copy pakai butang di bawah!');
}


// ═══════════════════════════════════════════
// AJAX API
// ═══════════════════════════════════════════
const TAG_COLORS = {GIVE:'#3ddc84',EFFECT:'#9b7ff4',TP:'#4f9cf9',TIME:'#f0874a',GM:'#22d3c8',ENCH:'#f06fa0',SUMMON:'#f06060',XP:'#f5a623',KILL:'#f06060',CLEAR:'#4f9cf9'};

async function api(action, body={}) {
  try {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(body).forEach(([k,v]) => fd.append(k, v));
    const r = await fetch('api.php', {method:'POST', body:fd});
    return await r.json();
  } catch(e) { return {ok:false, error:e.message}; }
}

// ── HISTORY ──────────────────────────────────
function renderHistory(rows) {
  const list = document.getElementById('history-list');
  if(!rows||!rows.length){list.innerHTML='<div class="hist-empty">📭<br>Tiada sejarah lagi.<br>Generate & klik "Simpan".</div>';return;}
  list.innerHTML = rows.map(h=>{
    const tc=TAG_COLORS[h.tab?.toUpperCase()]||'#94a3b8';
    const time=h.created_at?h.created_at.slice(11,16):'';
    return `<div class="hist-item" onclick="loadCmd('${h.command.replace(/\\/g,'\\\\').replace(/'/g,"\\'")}')">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">
        <span class="hist-tag" style="background:${tc}18;border:1px solid ${tc}40;color:${tc}">${(h.tab||'').toUpperCase()}</span>
        <button onclick="historyDel(${h.id},event)" style="background:transparent;border:none;color:var(--text3);cursor:pointer;font-size:13px;padding:0 3px">✕</button>
      </div>
      <div class="hist-cmd">${h.command}</div>
      <div class="hist-time">⏰ ${time}</div>
    </div>`;
  }).join('');
}

async function loadHistoryFromDB(){const r=await api('history_get');if(r.ok)renderHistory(r.rows);}
async function historyDel(id,e){e.stopPropagation();const r=await api('history_delete',{id});if(r.ok)renderHistory(r.rows);playClick();}
async function historyClear(){if(!confirm('Padam semua history?'))return;await api('history_clear');renderHistory([]);playClick();}
function loadCmd(cmd){document.getElementById('cmd-display').textContent=cmd;navigator.clipboard.writeText(cmd).catch(()=>{});playCopy();showToast('📋 Copied!');}

// ── FAVOURITES ────────────────────────────────
function renderFavs(rows){
  const list=document.getElementById('fav-list');
  if(!rows||!rows.length){list.innerHTML='<div class="hist-empty">📭<br>Tiada favourite lagi.</div>';return;}
  list.innerHTML=rows.map(f=>{
    const tc=TAG_COLORS[f.tab?.toUpperCase()]||'#94a3b8';
    return `<div class="hist-item" onclick="loadCmd('${f.command.replace(/\\/g,'\\\\').replace(/'/g,"\\'")}')">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">
        <span class="hist-tag" style="background:${tc}18;border:1px solid ${tc}40;color:${tc}">${(f.tab||'').toUpperCase()}</span>
        <button onclick="favDel(${f.id},event)" style="background:transparent;border:none;color:var(--text3);cursor:pointer;font-size:13px;padding:0 3px">✕</button>
      </div>
      <div class="hist-cmd">${f.command}</div>
      ${f.note?`<div style="font-size:10px;color:var(--text3);font-family:var(--mono);margin-top:3px">📝 ${f.note}</div>`:''}
    </div>`;
  }).join('');
}
async function addFav(){
  const cmd=document.getElementById('cmd-display').textContent;
  if(!cmd||cmd.startsWith('//')){playError();showToast('⚠️ Tiada command!','var(--red)');return;}
  const note=document.getElementById('fav-note').value.trim();
  const r=await api('fav_add',{command:cmd,tab:curTab,note});
  if(!r.ok){showToast('⚠️ '+r.error,'var(--red)');return;}
  document.getElementById('fav-note').value='';
  renderFavs(r.rows);playSave();showToast('⭐ Saved!');
}
async function favDel(id,e){e.stopPropagation();const r=await api('fav_delete',{id});if(r.ok)renderFavs(r.rows);playClick();}

// ── SAVE / COPY ───────────────────────────────
function copyCmd(){
  const cmd=document.getElementById('cmd-display').textContent;
  if(!cmd||cmd.startsWith('//')){playError();showToast('⚠️ Command not ready!','var(--red)');return;}
  navigator.clipboard.writeText(cmd).catch(()=>{const ta=document.createElement('textarea');ta.value=cmd;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);});
  playCopy();showToast('✅ Copied!');
}
async function saveToHistory(){
  const cmd=document.getElementById('cmd-display').textContent;
  if(!cmd||cmd.startsWith('//')){playError();showToast('⚠️ Tiada command!','var(--red)');return;}
  const r=await api('history_add',{command:cmd,tab:curTab});
  if(r.ok)renderHistory(r.rows);
  playSave();showToast('💾 Saved!');
}

// ── SOUND ─────────────────────────────────────
function attachSounds(){
  document.querySelectorAll('.tab').forEach(b=>b.addEventListener('click',()=>playClick()));
  document.querySelectorAll('.chip').forEach(b=>b.addEventListener('click',()=>playClick()));
}

// ═══════════════════════════════════════════
// START
// ═══════════════════════════════════════════
try { initGive(); } catch(e) { console.error('initGive:',e); }
try { initEnch(); } catch(e) { console.error('initEnch:',e); }
try { gen(); } catch(e) { console.error('gen:',e); }
try { loadHistoryFromDB(); } catch(e) {}
try { api('fav_get').then(r=>{ if(r.ok) renderFavs(r.rows); }); } catch(e) {}
setTimeout(attachSounds, 400);
</script>
</body>
</html>
