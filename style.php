<?php // Shared styles + shared runtime — included by every page
require_once __DIR__ . '/lib/ui.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  /* Core palette — deep forest/stone feel */
  --bg:        #0e1014;
  --bg2:       #13161c;
  --bg3:       #191d26;
  --card:      #161a22;
  --card2:     #1c2130;
  --border:    #232836;
  --border2:   #2d3448;
  --border3:   #3d4560;

  /* Brand green — Minecraft grass */
  --green:     #5dbe7a;
  --green2:    #46a862;
  --green3:    #d4f0dc;
  --green-glow:rgba(93,190,122,0.15);

  /* Accent colors */
  --gold:      #e8a94a;
  --gold2:     #c8891a;
  --blue:      #5b9cf6;
  --blue2:     #3b7de8;
  --purple:    #9b78f0;
  --pink:      #e86fa8;
  --teal:      #38c4b8;
  --orange:    #e8844a;
  --red:       #e05555;

  /* Text */
  --text:      #e8ecf4;
  --text2:     #8892a8;
  --text3:     #4a5268;
  --text4:     #2e3448;

  /* Fonts */
  --body:      'Outfit', sans-serif;
  --mono:      'JetBrains Mono', monospace;

  /* Radii */
  --r-xs: 4px;
  --r-sm: 8px;
  --r:    12px;
  --r-lg: 16px;
  --r-xl: 20px;

  /* Shadows */
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
  --shadow:    0 4px 20px rgba(0,0,0,0.4);
  --shadow-lg: 0 8px 40px rgba(0,0,0,0.5);
  --glow-green:0 0 24px rgba(93,190,122,0.2);
  --glow-gold: 0 0 24px rgba(232,169,74,0.2);
}

html { scroll-behavior:smooth }
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--body);
  font-size: 14.5px;
  line-height: 1.55;
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

/* ── BG ── */
body::before {
  content:'';
  position:fixed;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(ellipse 600px 500px at 0% 0%, rgba(93,190,122,0.04) 0%, transparent 70%),
    radial-gradient(ellipse 500px 400px at 100% 100%, rgba(91,156,246,0.04) 0%, transparent 70%);
}

/* ═══════════════════════════════
   NAV
═══════════════════════════════ */
.topnav {
  position: sticky; top: 0; z-index: 200;
  height: 56px;
  display: flex; align-items: center; gap: 2px;
  padding: 0 20px;
  background: rgba(14,16,20,0.92);
  backdrop-filter: blur(24px);
  border-bottom: 1px solid var(--border2);
  box-shadow: 0 1px 0 rgba(255,255,255,0.03), var(--shadow-sm);
}

.nav-logo {
  display: flex; align-items: center; gap: 8px;
  font-family: var(--body); font-size: 15px; font-weight: 800;
  color: var(--text); letter-spacing: -.3px;
  margin-right: 12px; white-space: nowrap; flex-shrink: 0;
  text-decoration: none;
}
.nav-logo-icon {
  width: 28px; height: 28px; border-radius: 7px;
  background: linear-gradient(135deg, var(--green2), var(--teal));
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(93,190,122,0.3);
}
.nav-logo-text { color: var(--text) }
.nav-logo-accent { color: var(--green) }

.nav-divider { width:1px; height:18px; background:var(--border2); margin:0 6px; flex-shrink:0 }

.nav-link {
  display: flex; align-items: center; gap: 5px;
  padding: 5px 10px;
  font-size: 13px; font-weight: 500;
  color: var(--text3); border-radius: var(--r-sm);
  text-decoration: none; transition: all .15s;
  border: 1px solid transparent;
  white-space: nowrap;
}
.nav-link:hover { color: var(--text2); background: rgba(255,255,255,0.04) }
.nav-link.active {
  color: var(--green);
  background: rgba(93,190,122,0.08);
  border-color: rgba(93,190,122,0.18);
  font-weight: 600;
}

.nav-right { margin-left: auto; display:flex; align-items:center; gap:8px }

.nav-user {
  display: flex; align-items: center; gap: 7px;
  background: rgba(232,169,74,0.06);
  border: 1px solid rgba(232,169,74,0.15);
  border-radius: 20px; padding: 4px 12px 4px 8px;
}
.nav-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--green); box-shadow: 0 0 8px var(--green);
  animation: pulse 2.5s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.35;transform:scale(.75)} }
.nav-uname { font-size: 12px; font-family: var(--mono); color: var(--gold); letter-spacing: .3px }

.sound-toggle {
  background: transparent; border: 1px solid var(--border2);
  color: var(--text3); border-radius: var(--r-sm);
  padding: 4px 10px; font-size: 12px; cursor: pointer;
  font-family: var(--mono); transition: all .15s;
}
.sound-toggle:hover { border-color: var(--border3); color: var(--text2) }
.sound-toggle.on { border-color: rgba(93,190,122,0.3); color: var(--green) }

/* ═══════════════════════════════
   PAGE HEADER
═══════════════════════════════ */
.page-header {
  position: relative; z-index: 1;
  padding: 28px 24px 0;
  max-width: 1360px; margin: 0 auto;
}
.page-title {
  font-size: 26px; font-weight: 800;
  color: var(--text); letter-spacing: -.5px; margin-bottom: 4px;
}
.page-title span {
  background: linear-gradient(120deg, var(--green), var(--teal));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.page-sub { font-size: 13px; color: var(--text3); font-family: var(--mono); letter-spacing: .2px }

/* ═══════════════════════════════
   CONTENT & LAYOUT
═══════════════════════════════ */
.content {
  position: relative; z-index: 1;
  padding: 20px 24px 60px;
  max-width: 1360px; margin: 0 auto;
}

/* ═══════════════════════════════
   CARD
═══════════════════════════════ */
.card {
  background: var(--card);
  border: 1px solid var(--border2);
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: border-color .2s;
}
.card-header {
  display: flex; align-items: center; gap: 8px;
  padding: 11px 16px;
  background: rgba(255,255,255,0.015);
  border-bottom: 1px solid var(--border);
  font-size: 11px; font-weight: 600;
  color: var(--text3); font-family: var(--mono);
  letter-spacing: 1.5px; text-transform: uppercase;
  min-height: 40px;
}
.card-header-dot { width:5px; height:5px; border-radius:50%; flex-shrink:0 }
.card-body { padding: 16px }

/* ═══════════════════════════════
   FORM ELEMENTS
═══════════════════════════════ */
.row { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-bottom:12px }
.field { display:flex; flex-direction:column; gap:5px }
.field label {
  font-size: 11px; font-weight: 600;
  color: var(--text3); font-family: var(--mono);
  letter-spacing: .8px; text-transform: uppercase;
}

input[type=text],
input[type=number],
input[type=email],
select,
textarea {
  width: 100%;
  background: var(--bg2);
  border: 1px solid var(--border2);
  color: var(--text);
  padding: 8px 12px;
  border-radius: var(--r-sm);
  font-size: 13.5px;
  font-family: var(--mono);
  transition: border-color .15s, box-shadow .15s, background .15s;
  appearance: none; -webkit-appearance: none;
  line-height: 1.4;
}
input[type=text]:hover,
input[type=number]:hover,
select:hover { border-color: var(--border3) }

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: var(--green2);
  background: var(--bg3);
  box-shadow: 0 0 0 3px rgba(93,190,122,0.1);
}

input::placeholder { color: var(--text3) }

select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%234a5268' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 11px center;
  padding-right: 30px; cursor: pointer;
}
select option { background: var(--bg2); color: var(--text) }
select optgroup { color: var(--text3); font-size: 11px }

textarea { resize: vertical; min-height: 80px; line-height: 1.6 }

input[type=range] {
  width: 100%; -webkit-appearance: none;
  height: 4px; background: var(--border2);
  border-radius: 2px; cursor: pointer; border: none; padding: 0;
}
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none; width: 18px; height: 18px;
  border-radius: 50%; background: var(--green);
  border: 2px solid var(--bg);
  box-shadow: 0 0 8px rgba(93,190,122,0.4);
  transition: transform .1s;
}
input[type=range]::-webkit-slider-thumb:hover { transform: scale(1.15) }
input[type=checkbox] { width:15px; height:15px; accent-color:var(--green); cursor:pointer }

/* ═══════════════════════════════
   BUTTONS
═══════════════════════════════ */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 16px;
  font-size: 13px; font-weight: 600;
  border-radius: var(--r-sm); cursor: pointer;
  border: 1px solid transparent;
  transition: all .15s; font-family: var(--body);
  letter-spacing: .1px; line-height: 1; white-space: nowrap;
  text-decoration: none;
}
.btn:active { transform: scale(.96) }
.btn:disabled { opacity:.4; cursor:not-allowed; transform:none }

.btn-green {
  background: var(--green); color: #0a1a0f;
  border-color: var(--green); font-weight: 700;
}
.btn-green:hover { background: var(--green2); border-color: var(--green2); box-shadow: var(--glow-green) }

.btn-gold {
  background: rgba(232,169,74,0.12);
  border-color: rgba(232,169,74,0.4); color: var(--gold);
}
.btn-gold:hover { background: rgba(232,169,74,0.22); box-shadow: var(--glow-gold) }

.btn-red {
  background: rgba(224,85,85,0.1);
  border-color: rgba(224,85,85,0.35); color: var(--red);
}
.btn-red:hover { background: rgba(224,85,85,0.2) }

.btn-blue {
  background: rgba(91,156,246,0.1);
  border-color: rgba(91,156,246,0.35); color: var(--blue);
}
.btn-blue:hover { background: rgba(91,156,246,0.2) }

.btn-purple {
  background: rgba(155,120,240,0.1);
  border-color: rgba(155,120,240,0.35); color: var(--purple);
}
.btn-purple:hover { background: rgba(155,120,240,0.2) }

.btn-ghost {
  background: transparent; border-color: var(--border2); color: var(--text2);
}
.btn-ghost:hover { background: rgba(255,255,255,0.04); border-color: var(--border3) }

.btn-outline-green {
  background: transparent;
  border-color: rgba(93,190,122,0.4); color: var(--green);
}
.btn-outline-green:hover { background: rgba(93,190,122,0.08) }

.btn-sm { padding:5px 11px; font-size:12px }
.btn-lg { padding:10px 24px; font-size:14px; font-weight:700 }

/* ═══════════════════════════════
   COMMAND OUTPUT
═══════════════════════════════ */
.cmd-output {
  background: var(--bg);
  border: 1px solid var(--border2);
  border-radius: var(--r-sm);
  overflow: hidden; margin-top: 12px;
}
.cmd-output-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 7px 14px;
  background: rgba(255,255,255,0.02);
  border-bottom: 1px solid var(--border);
  font-size: 10px; font-family: var(--mono);
  color: var(--text3); letter-spacing: 2px; text-transform: uppercase;
}
.cmd-output-body {
  padding: 14px 16px;
  font-family: var(--mono); font-size: 13.5px;
  color: var(--gold); word-break: break-all;
  line-height: 1.7; min-height: 46px;
}
.cmd-output-body::before { content:'›  '; color:var(--green2); opacity:.7 }
.cmd-output-actions {
  display: flex; gap: 7px; flex-wrap: wrap;
  padding: 10px 14px;
  border-top: 1px solid var(--border);
  background: rgba(255,255,255,0.01);
}

/* ═══════════════════════════════
   MISC COMPONENTS
═══════════════════════════════ */
.sec-title {
  font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
  font-family: var(--mono); color: var(--text3);
  margin-bottom: 12px;
  display: flex; align-items: center; gap: 10px;
}
.sec-title::after { content:''; flex:1; height:1px; background:var(--border) }

.badge {
  display: inline-flex; align-items: center;
  font-size: 10px; font-family: var(--mono);
  padding: 3px 8px; border-radius: 20px;
  letter-spacing: .5px; line-height: 1.5; font-weight: 600;
}
.badge-green { background:rgba(93,190,122,0.1); border:1px solid rgba(93,190,122,0.25); color:var(--green) }
.badge-gold  { background:rgba(232,169,74,0.1); border:1px solid rgba(232,169,74,0.25); color:var(--gold) }
.badge-red   { background:rgba(224,85,85,0.1);  border:1px solid rgba(224,85,85,0.25);  color:var(--red) }
.badge-blue  { background:rgba(91,156,246,0.1); border:1px solid rgba(91,156,246,0.25); color:var(--blue) }
.badge-purple{ background:rgba(155,120,240,0.1);border:1px solid rgba(155,120,240,0.25);color:var(--purple) }

.hint {
  font-size: 12px; color: var(--text3);
  font-family: var(--mono); line-height: 1.7;
}

.divider { border:none; border-top:1px solid var(--border); margin:14px 0 }

.chip {
  padding: 4px 10px; font-size: 12px; font-weight: 600;
  border-radius: 20px; cursor: pointer;
  border: 1px solid var(--border2);
  color: var(--text3); background: transparent;
  transition: all .12s; font-family: var(--body);
}
.chip:hover { border-color: var(--border3); color: var(--text2) }

/* ── Info block ── */
.info-block {
  background: rgba(93,190,122,0.05);
  border: 1px solid rgba(93,190,122,0.15);
  border-radius: var(--r-sm); padding: 10px 14px;
  font-size: 12px; font-family: var(--mono); color: var(--text2); line-height: 1.8;
}
.warn-block {
  background: rgba(232,169,74,0.05);
  border: 1px solid rgba(232,169,74,0.2);
  border-radius: var(--r-sm); padding: 10px 14px;
  font-size: 12px; font-family: var(--mono); color: var(--gold); line-height: 1.8;
}
.error-block {
  background: rgba(224,85,85,0.05);
  border: 1px solid rgba(224,85,85,0.2);
  border-radius: var(--r-sm); padding: 10px 14px;
  font-size: 12px; font-family: var(--mono); color: var(--red); line-height: 1.8;
}

/* ── Toast ── */
.toast {
  position: fixed; bottom: 24px; right: 24px; z-index: 9999;
  background: var(--card2);
  border: 1px solid rgba(93,190,122,0.35);
  color: var(--green);
  padding: 10px 18px; border-radius: var(--r-sm);
  font-family: var(--mono); font-size: 13px;
  transform: translateY(70px); opacity: 0;
  transition: all .28s cubic-bezier(.34,1.56,.64,1);
  box-shadow: var(--shadow-lg), 0 0 20px rgba(93,190,122,0.1);
  backdrop-filter: blur(12px);
}
.toast.show { transform: translateY(0); opacity: 1 }

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 4px; height: 4px }
::-webkit-scrollbar-track { background: transparent }
::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 4px }
::-webkit-scrollbar-thumb:hover { background: var(--border3) }

/* ── Table ── */
table { width:100%; border-collapse:collapse }
th {
  text-align:left; font-size:11px; font-family:var(--mono);
  color:var(--text3); letter-spacing:1px; text-transform:uppercase;
  padding:8px 12px; border-bottom:1px solid var(--border);
  font-weight:600;
}
td {
  padding:9px 12px; border-bottom:1px solid var(--border);
  font-size:13px; color:var(--text2); font-family:var(--mono);
}
tr:last-child td { border-bottom:none }
tr:hover td { background:rgba(255,255,255,0.02) }

/* ═══════════════════════════════
   NAV EXTRAS — version, search
═══════════════════════════════ */
.nav-link-text { display:inline }
@media(max-width:1080px){ .nav-link-text{display:none} .nav-link{padding:5px 8px;font-size:15px} }

.nav-search-btn {
  display:flex; align-items:center; gap:6px;
  background:rgba(255,255,255,0.03); border:1px solid var(--border2);
  color:var(--text3); padding:5px 10px; border-radius:var(--r-sm);
  font-family:var(--body); font-size:12px; cursor:pointer; transition:all .15s;
}
.nav-search-btn:hover { border-color:var(--border3); color:var(--text2) }
kbd {
  font-family:var(--mono); font-size:10px; padding:1px 5px;
  border:1px solid var(--border2); border-bottom-width:2px;
  border-radius:4px; color:var(--text3); background:var(--bg2);
}
.nav-version {
  display:flex; align-items:center; gap:6px;
  background:rgba(232,169,74,0.06); border:1px solid rgba(232,169,74,0.18);
  border-radius:var(--r-sm); padding:3px 8px 3px 6px;
}
.nav-version-tag { font-size:9px; font-family:var(--mono); letter-spacing:1px; color:var(--gold); font-weight:700 }
.nav-version select {
  background:transparent; border:none; color:var(--gold);
  font-family:var(--mono); font-size:12px; cursor:pointer; outline:none;
}
.nav-version select option { background:var(--bg2); color:var(--text) }
.sound-toggle { min-width:34px; text-align:center }

/* ═══════════════════════════════
   GLOBAL SEARCH
═══════════════════════════════ */
.search-overlay {
  position:fixed; inset:0; z-index:400;
  background:rgba(6,8,11,0.7); backdrop-filter:blur(6px);
  display:flex; align-items:flex-start; justify-content:center; padding:12vh 20px 20px;
}
.search-modal {
  width:100%; max-width:640px; background:var(--card);
  border:1px solid var(--border2); border-radius:var(--r-lg);
  box-shadow:var(--shadow-lg); overflow:hidden;
}
.search-modal-input { display:flex; align-items:center; gap:10px; padding:14px 16px; border-bottom:1px solid var(--border) }
.search-modal-input input {
  flex:1; background:transparent; border:none; outline:none;
  color:var(--text); font-family:var(--body); font-size:15px;
}
.search-results { max-height:56vh; overflow-y:auto; padding:6px }
.search-hit {
  display:flex; align-items:center; gap:11px; padding:9px 12px;
  border-radius:var(--r-sm); cursor:pointer; text-decoration:none;
  border:1px solid transparent;
}
.search-hit:hover, .search-hit.sel { background:rgba(93,190,122,0.07); border-color:rgba(93,190,122,0.2) }
.search-hit-icon { font-size:17px; width:24px; text-align:center; flex-shrink:0 }
.search-hit-title { font-size:13.5px; color:var(--text); font-weight:600 }
.search-hit-desc { font-size:11.5px; color:var(--text3) }
.search-hit-cat {
  margin-left:auto; font-size:9px; font-family:var(--mono); letter-spacing:1px;
  text-transform:uppercase; color:var(--text3); border:1px solid var(--border2);
  padding:2px 6px; border-radius:4px; flex-shrink:0;
}

/* ═══════════════════════════════
   TILES / LAUNCHPAD
═══════════════════════════════ */
.tile-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(215px,1fr)); gap:10px }
.tile {
  display:flex; align-items:flex-start; gap:11px; padding:13px 14px;
  background:var(--card); border:1px solid var(--border);
  border-radius:var(--r); text-decoration:none; transition:all .15s;
  position:relative; overflow:hidden;
}
.tile::before {
  content:''; position:absolute; left:0; top:0; bottom:0; width:2px;
  background:var(--tile-accent,var(--green)); opacity:0; transition:opacity .15s;
}
.tile:hover { border-color:var(--border3); background:var(--card2); transform:translateY(-1px) }
.tile:hover::before { opacity:1 }
.tile-icon { font-size:20px; line-height:1.2; flex-shrink:0 }
.tile-title { font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:2px }
.tile-desc { font-size:11.5px; color:var(--text3); line-height:1.45 }

/* ═══════════════════════════════
   COMMAND OUTPUT WIDGET
═══════════════════════════════ */
.cmdout { border:1px solid var(--border2); border-radius:var(--r); background:var(--bg2); overflow:hidden }
.cmdout-head { display:flex; align-items:center; justify-content:space-between; padding:8px 14px; border-bottom:1px solid var(--border) }
.cmdout-label { font-size:10px; font-family:var(--mono); letter-spacing:2px; color:var(--text3); text-transform:uppercase; font-weight:600 }
.cmdout-meta { font-size:10px; font-family:var(--mono); color:var(--text3) }
.cmdout-body {
  padding:13px 15px; min-height:48px; font-family:var(--mono); font-size:13.5px;
  line-height:1.65; color:var(--gold); word-break:break-all; white-space:pre-wrap;
}
.cmdout-body:empty::after { content:'Fill in the options above'; color:var(--text3); font-style:italic }
.cmdout-warn { padding:0 15px 10px; display:flex; flex-direction:column; gap:5px }
.warn-line {
  font-size:11.5px; font-family:var(--mono); color:var(--gold);
  background:rgba(232,169,74,0.06); border:1px solid rgba(232,169,74,0.2);
  border-radius:var(--r-xs); padding:6px 10px; line-height:1.5;
}
.warn-line.is-error { color:var(--red); background:rgba(224,85,85,0.06); border-color:rgba(224,85,85,0.25) }
.cmdout-actions { display:flex; gap:7px; flex-wrap:wrap; padding:10px 14px; border-top:1px solid var(--border) }

/* ═══════════════════════════════
   WIZARD STEPS
═══════════════════════════════ */
.steps { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:14px }
.step {
  display:flex; align-items:center; gap:7px; padding:6px 12px;
  border:1px solid var(--border); border-radius:20px;
  font-size:12px; color:var(--text3); background:var(--bg2);
  cursor:pointer; transition:all .15s; user-select:none;
}
.step:hover { border-color:var(--border3); color:var(--text2) }
.step-n {
  width:17px; height:17px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; font-family:var(--mono); font-weight:700;
  background:var(--border); color:var(--text3);
}
.step.active { border-color:rgba(93,190,122,0.35); color:var(--green); background:rgba(93,190,122,0.07); font-weight:600 }
.step.active .step-n { background:var(--green); color:#0b1a10 }
.step.done { color:var(--text2) }
.step.done .step-n { background:rgba(93,190,122,0.25); color:var(--green) }

/* ═══════════════════════════════
   CHECKLIST
═══════════════════════════════ */
.checklist { display:flex; flex-direction:column; gap:2px }
.check-row {
  display:flex; align-items:center; gap:9px; padding:6px 9px;
  border-radius:var(--r-xs); cursor:pointer; transition:background .12s;
  font-size:13px; color:var(--text2);
}
.check-row:hover { background:rgba(255,255,255,0.03) }
.check-row input { width:15px; height:15px; accent-color:var(--green); cursor:pointer; flex-shrink:0 }
.check-row input:checked + span { color:var(--text3); text-decoration:line-through }

/* ═══════════════════════════════
   EMPTY STATE
═══════════════════════════════ */
.empty-state { text-align:center; padding:34px 18px; color:var(--text3) }
.empty-icon { font-size:30px; margin-bottom:8px; opacity:.7 }
.empty-title { font-size:13.5px; color:var(--text2); font-weight:600; margin-bottom:3px }
.empty-sub { font-size:12px; color:var(--text3); line-height:1.55 }

/* ═══════════════════════════════
   MISC LAYOUT HELPERS
═══════════════════════════════ */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px }
.grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:14px }
.split { display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start }
@media(max-width:980px){ .grid-2,.grid-3,.split{grid-template-columns:1fr} }
.stack { display:flex; flex-direction:column; gap:14px }
.inline { display:flex; align-items:center; gap:8px; flex-wrap:wrap }
.muted { color:var(--text3) }
.mono { font-family:var(--mono) }
.pill-row { display:flex; gap:5px; flex-wrap:wrap }
.pill {
  padding:4px 11px; font-size:12px; font-weight:600; font-family:var(--body);
  background:transparent; border:1px solid var(--border2); color:var(--text3);
  border-radius:20px; cursor:pointer; transition:all .12s;
}
.pill:hover { border-color:var(--border3); color:var(--text2) }
.pill.active { border-color:rgba(93,190,122,0.4); color:var(--green); background:rgba(93,190,122,0.08) }
.stat-row { display:flex; gap:16px; flex-wrap:wrap }
.stat { flex:1; min-width:96px }
.stat-val { font-size:22px; font-weight:800; font-family:var(--mono); color:var(--green); line-height:1.2 }
.stat-lbl { font-size:10px; font-family:var(--mono); letter-spacing:1.2px; text-transform:uppercase; color:var(--text3); margin-top:2px }

</style>

<script>
// ── SHARED AUDIO ──
let _AC = null;
function getAC(){ if(!_AC) _AC = new (window.AudioContext||window.webkitAudioContext)(); return _AC; }
let soundOn = localStorage.getItem('mc_sound') !== 'off';

function _tone(freq, type='sine', vol=0.08, dur=0.08, delay=0){
  if(!soundOn) return;
  try {
    const ac=getAC(), o=ac.createOscillator(), g=ac.createGain();
    o.connect(g); g.connect(ac.destination);
    o.type=type; o.frequency.value=freq;
    const t=ac.currentTime+delay;
    g.gain.setValueAtTime(0,t);
    g.gain.linearRampToValueAtTime(vol,t+0.01);
    g.gain.exponentialRampToValueAtTime(0.001,t+dur);
    o.start(t); o.stop(t+dur+0.01);
  } catch(e){}
}

function playClick()  { _tone(600,'sine',0.06,0.06) }
function playSelect() { _tone(500,'sine',0.06,0.05); setTimeout(()=>_tone(800,'sine',0.06,0.05),50) }
function playCopy()   { [560,720,900].forEach((f,i)=>_tone(f,'sine',0.07,0.07,i*0.06)) }
function playSave()   { [660,880].forEach((f,i)=>_tone(f,'triangle',0.06,0.1,i*0.08)) }
function playError()  { _tone(200,'sawtooth',0.07,0.18) }

function toggleSound(btn){
  soundOn=!soundOn;
  localStorage.setItem('mc_sound',soundOn?'on':'off');
  btn.textContent = soundOn ? '🔊 Sound ON' : '🔇 Sound OFF';
  btn.classList.toggle('on', soundOn);
}

function showToast(msg, color){
  let t = document.getElementById('toast');
  if(!t){ t=document.createElement('div'); t.id='toast'; t.className='toast'; document.body.appendChild(t) }
  t.textContent = msg;
  t.style.borderColor = color ? color : 'rgba(93,190,122,0.35)';
  t.style.color = color ? color : 'var(--green)';
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(()=>t.classList.remove('show'), 2200);
}

function copyText(txt){
  if(!txt||txt.startsWith('//')){ playError(); showToast('⚠️ Command not ready!','var(--red)'); return false }
  navigator.clipboard.writeText(txt).catch(()=>{
    const ta=document.createElement('textarea'); ta.value=txt;
    document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
  });
  playCopy(); showToast('✅ Copied!'); return true;
}
</script>

<?php ui_runtime_data(); ?>
<script src="assets/mccmd.js"></script>
<script src="assets/search.js"></script>
