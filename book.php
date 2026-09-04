<?php
$defaultTarget = 'nizkbiits';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Book Writer — Minecraft CMD</title>
<?php include __DIR__ . '/style.php'; ?>
<style>
.book-layout{display:grid;grid-template-columns:1fr 380px;gap:20px}
@media(max-width:920px){.book-layout{grid-template-columns:1fr}}
.book-preview{
  background:linear-gradient(135deg,#f0e6d0,#e8d8b8);
  border:2px solid #8B6914;border-radius:4px 12px 12px 4px;
  min-height:300px;padding:20px 24px;
  font-family:'Georgia',serif;color:#2a1a0a;
  position:relative;
  box-shadow:4px 4px 16px rgba(0,0,0,0.5),-2px 0 8px rgba(0,0,0,0.3);
}
.book-preview::before{
  content:'';position:absolute;left:0;top:0;bottom:0;width:18px;
  background:linear-gradient(90deg,#6B4F10,#9B7A1A,#8B6914);
  border-radius:4px 0 0 4px;
}
.book-content{margin-left:18px;padding-left:10px}
.book-title{font-size:16px;font-weight:900;text-align:center;margin-bottom:8px;color:#1a0a00}
.book-author{font-size:11px;text-align:center;color:#6B4F10;margin-bottom:14px;font-style:italic}
.book-text{font-size:13px;line-height:1.8;color:#2a1a0a;white-space:pre-wrap;word-break:break-word}
.book-page-num{position:absolute;bottom:10px;right:16px;font-size:10px;color:#8B6914;font-style:italic}
.pages-list{display:flex;flex-direction:column;gap:8px;max-height:420px;overflow-y:auto}
.page-item{background:var(--bg3);border:1px solid var(--border2);border-radius:8px;overflow:hidden}
.page-header{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg2);border-bottom:1px solid var(--border)}
.page-num{font-size:11px;font-family:var(--mono);color:var(--text2);font-weight:700}
.page-chars{font-size:10px;font-family:var(--mono);color:var(--text3)}
.page-actions{display:flex;gap:4px}
.page-del{padding:3px 8px;font-size:11px;background:rgba(240,96,96,0.08);border:1px solid rgba(240,96,96,0.25);color:var(--red);border-radius:5px;cursor:pointer}
textarea.page-text{
  width:100%;background:transparent;border:none;padding:10px 12px;
  font-family:var(--mono);font-size:12px;color:var(--text);
  resize:none;min-height:80px;outline:none;line-height:1.6;
}
textarea.page-text:focus{background:rgba(255,255,255,0.02)}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="toast" id="toast"></div>

<div class="page-header">
  <div class="page-title">📖 <span>Book Writer</span></div>
  <div class="page-sub">// Write a Minecraft book — generates a /give command for a written book</div>
</div>

<div class="content">
<div class="book-layout">

<!-- LEFT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- BOOK INFO -->
  <div class="card">
    <div class="card-header"><span style="color:var(--gold)">●</span> BOOK DETAILS</div>
    <div class="card-body">
      <div class="row">
        <div class="field">
          <label>Title</label>
          <input type="text" id="bk-title" placeholder="e.g. The Adventure" oninput="buildBook()" maxlength="32">
        </div>
        <div class="field">
          <label>Author</label>
          <input type="text" id="bk-author" value="<?= $defaultTarget ?>" oninput="buildBook()" maxlength="16">
        </div>
        <div class="field">
          <label>Give to</label>
          <input type="text" id="bk-target" value="<?= $defaultTarget ?>" oninput="buildBook()">
        </div>
        <div class="field">
          <label>Quantity</label>
          <input type="number" id="bk-qty" value="1" min="1" max="64" oninput="buildBook()">
        </div>
      </div>
    </div>
  </div>

  <!-- PAGES -->
  <div class="card">
    <div class="card-header">
      <span style="color:var(--purple)">●</span> PAGES
      <span id="page-count-badge" class="badge badge-purple" style="margin-left:6px">0 / 100</span>
      <button class="btn btn-purple btn-sm" style="margin-left:auto" onclick="addPage()">+ Add Page</button>
    </div>
    <div class="card-body">
      <div class="pages-list" id="pages-list">
        <div style="color:var(--text3);font-family:var(--mono);font-size:12px;padding:8px">// Click "Add Page" to start writing</div>
      </div>
    </div>
  </div>

  <!-- PRESETS -->
  <div class="card">
    <div class="card-header"><span style="color:var(--teal)">●</span> TEMPLATE BUKU</div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
        <button class="btn btn-ghost" onclick="loadBookPreset('rules')">📜 Server Rules</button>
        <button class="btn btn-ghost" onclick="loadBookPreset('quest')">⚔️ Quest Guide</button>
        <button class="btn btn-ghost" onclick="loadBookPreset('lore')">📚 Lore Story</button>
        <button class="btn btn-ghost" onclick="loadBookPreset('welcome')">👋 Welcome Book</button>
      </div>
    </div>
  </div>

</div><!-- /left -->

<!-- RIGHT -->
<div style="display:flex;flex-direction:column;gap:14px">

  <!-- BOOK PREVIEW -->
  <div class="card">
    <div class="card-header"><span style="color:var(--orange)">●</span> PREVIEW</div>
    <div class="card-body" style="padding:12px">
      <div class="book-preview">
        <div class="book-content">
          <div class="book-title" id="prev-title">Untitled Book</div>
          <div class="book-author" id="prev-author">by nizkbiits</div>
          <div class="book-text" id="prev-text">[ Nothing written yet ]</div>
        </div>
        <div class="book-page-num" id="prev-pagenum">Page 1</div>
      </div>
      <div style="display:flex;gap:6px;margin-top:8px;justify-content:center">
        <button class="btn btn-ghost btn-sm" onclick="prevPage()">◀ Prev</button>
        <span style="font-size:12px;color:var(--text3);font-family:var(--mono);padding:5px 10px" id="page-indicator">0 / 0</span>
        <button class="btn btn-ghost btn-sm" onclick="nextPage()">Next ▶</button>
      </div>
    </div>
  </div>

  <!-- OUTPUT -->
  <div class="card" style="position:sticky;top:70px">
    <div class="card-header"><span style="color:var(--gold)">●</span> COMMAND</div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:10px">
        <div style="text-align:center;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px">
          <div style="font-size:20px;font-weight:800;font-family:var(--mono);color:var(--purple)" id="stat-pages">0</div>
          <div style="font-size:10px;color:var(--text3);font-family:var(--mono)">Pages</div>
        </div>
        <div style="text-align:center;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px">
          <div style="font-size:20px;font-weight:800;font-family:var(--mono);color:var(--blue)" id="stat-chars">0</div>
          <div style="font-size:10px;color:var(--text3);font-family:var(--mono)">Chars</div>
        </div>
        <div style="text-align:center;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px">
          <div style="font-size:20px;font-weight:800;font-family:var(--mono);color:var(--gold)" id="stat-cmd-len">0</div>
          <div style="font-size:10px;color:var(--text3);font-family:var(--mono)">Cmd len</div>
        </div>
      </div>
      <div class="cmd-output">
        <div class="cmd-output-header">
          <span>// /give written_book</span>
          <span id="cmd-warn" style="color:var(--red);display:none">⚠ Too long!</span>
        </div>
        <div class="cmd-output-body" id="bk-output" style="font-size:11px;word-break:break-all;white-space:pre-wrap">/give nizkbiits written_book 1</div>
        <div class="cmd-output-actions">
          <button class="btn btn-green" onclick="copyText(document.getElementById('bk-output').textContent)">📋 Copy</button>
        </div>
      </div>
      <p class="hint">💡 Max 100 pages · Max 256 chars per page · The command can get very long</p>
    </div>
  </div>

</div><!-- /right -->
</div><!-- /book-layout -->
</div>

<script>
let pages = [];
let previewPage = 0;
let pageCount = 0;

function v(id){ return document.getElementById(id)?.value||'' }

function addPage(content='') {
  if(pages.length >= 100) { showToast('⚠️ Max 100 pages!','var(--red)'); return; }
  pageCount++;
  const id = 'page_'+pageCount;
  pages.push({id, content});
  renderPages();
  buildBook();
  if(pages.length===1) previewPage=0;
  updatePreview();
  playClick();
}

function renderPages() {
  const list = document.getElementById('pages-list');
  if(!pages.length) {
    list.innerHTML='<div style="color:var(--text3);font-family:var(--mono);font-size:12px;padding:8px">// Click "Add Page" to start writing</div>';
    document.getElementById('page-count-badge').textContent='0 / 100';
    return;
  }
  document.getElementById('page-count-badge').textContent=pages.length+' / 100';
  list.innerHTML = pages.map((p,i) => `
    <div class="page-item">
      <div class="page-header">
        <span class="page-num">Page ${i+1}</span>
        <span class="page-chars">${p.content.length} / 256 chars</span>
        <div class="page-actions">
          <button class="page-del" onclick="removePage(${i})">✕</button>
        </div>
      </div>
      <textarea class="page-text" id="pt-${p.id}" maxlength="256"
        oninput="updatePageContent(${i},this)" placeholder="Write page ${i+1}…">${p.content}</textarea>
    </div>`).join('');
}

function updatePageContent(idx, el) {
  pages[idx].content = el.value;
  el.closest('.page-item').querySelector('.page-chars').textContent = el.value.length+' / 256 chars';
  buildBook();
  if(idx===previewPage) updatePreview();
}

function removePage(idx) {
  pages.splice(idx,1);
  if(previewPage >= pages.length) previewPage = Math.max(0,pages.length-1);
  renderPages();
  buildBook();
  updatePreview();
  playClick();
}

function prevPage() { if(previewPage>0){previewPage--;updatePreview();playClick();} }
function nextPage() { if(previewPage<pages.length-1){previewPage++;updatePreview();playClick();} }

function updatePreview() {
  const title = v('bk-title') || 'Untitled Book';
  const author = v('bk-author') || 'Unknown';
  document.getElementById('prev-title').textContent = title;
  document.getElementById('prev-author').textContent = 'by '+author;
  document.getElementById('page-indicator').textContent = (pages.length?previewPage+1:0)+' / '+pages.length;
  document.getElementById('prev-pagenum').textContent = 'Page '+(previewPage+1);
  if(pages.length) {
    document.getElementById('prev-text').textContent = pages[previewPage]?.content || '[ Empty page ]';
  } else {
    document.getElementById('prev-text').textContent = '[ Nothing written yet ]';
  }
}

document.addEventListener('mc:version', () => buildBook());

function buildBook() {
  const target = v('bk-target')||'@p';
  const qty = v('bk-qty')||1;
  const title = v('bk-title')||'Book';
  const author = v('bk-author')||'Unknown';

  document.getElementById('stat-pages').textContent = pages.length;
  const totalChars = pages.reduce((a,p)=>a+p.content.length,0);
  document.getElementById('stat-chars').textContent = totalChars;

  updatePreview();

  if(!pages.length) {
    document.getElementById('bk-output').textContent = `/give ${target} written_book ${qty}`;
    return;
  }

  // Pages are text components stored inside item data, so how they are
  // written changed twice: NBT before 1.20.5, the written_book_content
  // component after, and SNBT rather than JSON strings from 1.21.5.
  const pageNodes = pages.map(p => MC.raw(MC.nbtText(MC.text(p.content))));
  const content = { title: title, author: author, pages: pageNodes };

  const item = MC.syn().items === 'components'
    ? MC.item('written_book', { components: { written_book_content: content } })
    : 'written_book' + MC.snbt(content);

  const cmd = `/give ${target} ${item} ${qty}`;

  document.getElementById('bk-output').textContent = cmd;
  document.getElementById('stat-cmd-len').textContent = cmd.length;
  document.getElementById('cmd-warn').style.display = cmd.length > 32500 ? '' : 'none';
}

const BOOK_PRESETS = {
  rules:{
    title:'Server Rules',author:'Admin',
    pages:[
      '§l§6SERVER RULES§r\n\n§71. Be respectful\n2. No griefing\n3. No hacking\n4. No spam',
      '§l§cPENALTIES§r\n\n§7Breaking rules will result in:\n\n- Warning\n- Temp ban\n- Perm ban',
      '§l§aHAVE FUN!§r\n\n§7This server is meant to be a fun and safe place for everyone.\n\nEnjoy!'
    ]
  },
  quest:{
    title:'The Lost Sword',author:'Quest Master',
    pages:[
      '§l§6CHAPTER 1§r\n§oThe Beginning§r\n\nA sword of legend has been lost deep in the dungeon. Your quest begins...',
      '§l§6OBJECTIVES§r\n\n§a✦ §rFind the dungeon entrance\n§a✦ §rDefeat the guardian\n§a✦ §rRetrieve the sword',
      '§l§6REWARD§r\n\n§eComplete this quest to receive:\n\n- Enchanted Netherite Sword\n- 1000 Gold Coins\n- Special Title'
    ]
  },
  lore:{
    title:'Chronicles of nizkbiits',author:'nizkbiits',
    pages:[
      '§l§5CHAPTER I§r\n§oThe Origin§r\n\nIn the beginning there was darkness. Then a pickaxe struck stone, and light flooded the world...',
      '§l§5CHAPTER II§r\n§oThe Journey§r\n\nAcross deserts and oceans, through the Nether and beyond the End, the legend grew...',
      '§l§5TO BE CONTINUED...§r\n\n§8The story is still being written.\n\n§7— nizkbiits'
    ]
  },
  welcome:{
    title:'Welcome Guide',author:'nizkbiits',
    pages:[
      '§l§aWELCOME!§r\n\nThank you for joining.\n\nThis book will help you get started on the server.',
      '§l§6GETTING STARTED§r\n\n§7✦ §rRead the rules\n§7✦ §rJoin a team\n§7✦ §rBuild your base\n§7✦ §rHave fun!',
      '§l§bCOMMANDS§r\n\n§e/spawn §r- Go to spawn\n§e/home §r- Your home\n§e/tp <name> §r- Teleport\n§e/help §r- All commands'
    ]
  },
};

function loadBookPreset(key) {
  const p = BOOK_PRESETS[key]; if(!p) return;
  document.getElementById('bk-title').value = p.title;
  document.getElementById('bk-author').value = p.author;
  pages = [];
  pageCount = 0;
  p.pages.forEach(content => addPage(content));
  previewPage = 0;
  buildBook();
  playSelect();
  showToast('✅ Template loaded!');
}

buildBook();
</script>
</body>
</html>
