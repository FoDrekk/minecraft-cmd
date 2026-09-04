// End-to-end checks across every page: no JS errors, nav present,
// key interactions working, and the cross-links actually resolving.
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}`)));

const browser = await chromium.launch();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
page.setDefaultNavigationTimeout(15000);

// Google Fonts is not reachable from this sandbox and every page links it,
// so block it rather than waiting out a network timeout per navigation.
// The stylesheets declare fallback families, so nothing under test changes.
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

const jsErrors = [];
page.on('pageerror', e => jsErrors.push(e.message));
page.on('console', m => {
  // ERR_FAILED is the blocked font request above, not a page fault.
  const t = m.text();
  if (m.type() === 'error' && !t.includes('favicon') && !t.includes('ERR_FAILED')) jsErrors.push('console: ' + t);
});

const PAGES = [
  'index.php', 'tools.php', 'commands.php', 'build.php', 'doctor.php',
  'doctor.php?mode=explain', 'knowledge.php?t=materials', 'knowledge.php?t=palette',
  'knowledge.php?t=tips', 'knowledge.php?t=ideas', 'farms.php', 'farms.php?farm=iron',
  'mystuff.php', 'kit.php', 'sequencer.php', 'nbt.php', 'title.php', 'firework.php',
  'scoreboard.php', 'sign.php', 'book.php', 'dashboard.php',
];

for (const p of PAGES) {
  jsErrors.length = 0;
  const res = await page.goto(`${BASE}/${p}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(150);
  check(`${p}: loads`, res.status(), 200);
  check(`${p}: no JS errors`, jsErrors.join(' | '), '');
  has(`${p}: has navigation`, await page.content(), 'class="topnav"');
}

// ── NAVIGATION LINKS ────────────────────────────
await page.goto(`${BASE}/index.php`);
const navLinks = await page.$$eval('.topnav .nav-link', els => els.map(e => e.getAttribute('href')));
for (const href of navLinks) {
  const r = await page.request.get(`${BASE}/${href}`);
  check(`nav link ${href}`, r.status(), 200);
}

// Every quick-action tile on Home resolves
const tiles = await page.$$eval('.tile', els => els.map(e => e.getAttribute('href')));
check('home: eight quick actions', tiles.length, 8);
for (const href of tiles) {
  const r = await page.request.get(`${BASE}/${href}`);
  check(`home tile ${href}`, r.status(), 200);
}

// ── GLOBAL SEARCH ───────────────────────────────
await page.keyboard.press('/');
await page.waitForSelector('#search-overlay:not([hidden])');

// Results are replaced in place, so wait for the expected text rather
// than for "some hit exists" — the previous query's hits are still there.
async function search(query, expected) {
  await page.fill('#global-search-input', query);
  try {
    await page.waitForFunction(
      e => (document.getElementById('global-search-results').textContent || '').includes(e),
      expected, { timeout: 4000 });
  } catch (err) { /* fall through to the assertion for a useful message */ }
  return page.textContent('#global-search-results');
}

has('search: intent match', await search('make everyone creative', 'Gamemode'), 'Gamemode');
has('search: finds the iron farm', await search('automatic iron', 'Iron Farm'), 'Iron Farm');
has('search: finds blocks', await search('deepslate', 'Deepslate'), 'Deepslate');

await page.keyboard.press('Escape');
check('search: closes on Escape', await page.isHidden('#search-overlay'), true);

// ── VERSION SELECTOR PERSISTS ───────────────────
await page.selectOption('#mc-version-select', '1.20.4');
await page.goto(`${BASE}/commands.php?t=give`);
check('version: survives navigation', await page.inputValue('#mc-version-select'), '1.20.4');
await page.selectOption('#mc-version-select', '26.2');

// ── SURPRISE ME ─────────────────────────────────
await page.goto(`${BASE}/index.php`);
await page.click('button:has-text("Surprise me")');
await page.waitForSelector('.surprise-kind');
const kind = await page.textContent('.surprise-kind');
check('surprise: returns a known kind',
  ['Build idea', 'Farm', 'Building tip', 'Block palette', 'Command challenge', 'Build challenge'].includes(kind.trim()), true);

// ── MATERIALS FILTER ────────────────────────────
await page.goto(`${BASE}/knowledge.php?t=materials`);
await page.waitForSelector('.mat-card');
const total = await page.$$eval('.mat-card', e => e.length);
await page.fill('#mat-search', 'deepslate');
await page.waitForTimeout(120);
const filtered = await page.$$eval('.mat-card', e => e.length);
check('materials: filter narrows results', filtered < total && filtered > 0, true);
await page.fill('#mat-search', '');
await page.selectOption('#mat-style', 'japanese');
await page.waitForTimeout(120);
has('materials: style filter works', await page.textContent('#mat-count'), 'of ' + total);

// ── PALETTE BUILDER ─────────────────────────────
await page.goto(`${BASE}/knowledge.php?t=palette`);
await page.waitForSelector('.pal-role select');
check('palette: five roles', await page.$$eval('.pal-role', e => e.length), 5);
check('palette: wall preview renders', (await page.$$eval('.wall i', e => e.length)) > 100, true);
const before = await page.inputValue('.pal-role select');
await page.click('button:has-text("🎲 Random")');
await page.waitForTimeout(120);
check('palette: randomise keeps five roles', await page.$$eval('.pal-role', e => e.length), 5);
await page.click('[data-preset="cherry-blossom"]');
await page.waitForTimeout(120);
check('palette: preset loads', await page.inputValue('#pal-style'), 'japanese');

// ── FARM GUIDE ──────────────────────────────────
await page.goto(`${BASE}/farms.php?farm=iron`);
await page.waitForSelector('.check-row input');
has('farm: shows the version it was checked against', await page.textContent('.guide-meta'), 'Checked against');
has('farm: material progress', await page.textContent('#check-progress'), 'items to gather');
await page.check('.check-row input');
await page.waitForTimeout(80);
has('farm: checklist counts progress', await page.textContent('#check-progress'), '1 of');
await page.reload();
await page.waitForSelector('.check-row input');
check('farm: checklist persists across reload', await page.isChecked('.check-row input'), true);
await page.uncheck('.check-row input');

// Every farm guide has the full structure
const farmIds = await page.evaluate(async base => {
  const r = await fetch(base + '/farms.php');
  const html = await r.text();
  return [...html.matchAll(/farms\.php\?farm=([a-z-]+)/g)].map(m => m[1]);
}, BASE);
check('farms: twelve guides listed', new Set(farmIds).size, 12);
for (const id of new Set(farmIds)) {
  await page.goto(`${BASE}/farms.php?farm=${id}`);
  const body = await page.textContent('.guide');
  for (const section of ['Materials', 'Before you start', 'Build it', 'How it works', 'Testing it', 'Troubleshooting']) {
    has(`farm ${id}: has ${section}`, body, section);
  }
}

// ── BUILD IDEAS ─────────────────────────────────
await page.goto(`${BASE}/knowledge.php?t=ideas`);
const ideaLinks = await page.$$eval('.idea-card', e => e.map(x => x.getAttribute('href')));
check('ideas: fourteen listed', ideaLinks.length, 14);
await page.goto(`${BASE}/${ideaLinks[0]}`);
has('idea detail: features', await page.textContent('.idea-detail'), 'Key design features');
has('idea detail: palette', await page.textContent('.idea-detail'), 'Suggested palette');
has('idea detail: techniques', await page.textContent('.idea-detail'), 'Techniques this leans on');

// ── MY STUFF ROUND TRIP ─────────────────────────
await page.goto(`${BASE}/commands.php?t=gamemode`);
await page.waitForFunction(() => document.querySelector('#out-gamemode [data-cmd]').textContent);
await page.click('[data-gm="creative"]');
await page.fill('#gm-target', '@a');
await page.waitForTimeout(60);
await page.click('#out-gamemode [data-act="fav"]');
await page.waitForTimeout(350);
await page.goto(`${BASE}/mystuff.php?t=library`);
has('my stuff: saved command appears', await page.textContent('#ms-list'), '/gamemode creative @a');
// clean up so repeat runs stay green
await page.evaluate(async () => {
  const btn = document.querySelector('[data-del-fav]');
  if (btn) await MC.api('fav_delete', { id: btn.dataset.delFav });
});

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
