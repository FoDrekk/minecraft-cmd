// The shared visual foundation: asset registry, texture resolver, the
// fallback chain, and the shared UI components built on top of it.
//
// The repository ships no Minecraft textures (they are not ours to
// redistribute), so the drop-in path is proved by writing a fixture into
// assets/textures/ during the run and removing it again. That exercises
// the real code path rather than a mock.
import { launchChromium } from './_launch.mjs';
import { writeFileSync, unlinkSync, existsSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const TEX_DIR = join(ROOT, 'assets', 'textures');

let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

// A 1x1 PNG. Deliberately not a Minecraft texture — it only has to be a
// real image file so the pipeline has something genuine to resolve.
const PNG_1PX = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64');

const FIXTURES = ['zz_probe_item.png', 'sand.png'];
function writeFixtures() {
  if (!existsSync(TEX_DIR)) mkdirSync(TEX_DIR, { recursive: true });
  for (const f of FIXTURES) writeFileSync(join(TEX_DIR, f), PNG_1PX);
}
function removeFixtures() {
  for (const f of FIXTURES) { try { unlinkSync(join(TEX_DIR, f)); } catch {} }
}

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

try {
  // ── WITH NO ASSETS: the honest fallback chain ──────────────────
  removeFixtures();
  await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && window.MC.visual);

  const bare = await page.evaluate(() => ({
    manifestEmpty: Object.keys(MC.data.textures).length === 0,
    sword: MC.visual.resolve('diamond_sword'),
    planks: MC.visual.resolve('oak_planks'),
    nonsense: MC.visual.resolve('not_a_real_minecraft_thing'),
    prefixed: MC.visual.resolve('minecraft:stone'),
    messy: MC.visual.resolve('  Diamond Sword  '),
  }));
  check('no textures shipped in the repository', bare.manifestEmpty, true);
  check('equipment falls back to its drawn glyph', bare.sword.source, 'equipment');
  check('the glyph is picked from the id', bare.sword.category, 'Sword');
  check('a block falls back to its palette colour', bare.planks.source, 'block');
  check('that colour is the Material Library one', bare.planks.hex, '#b08a52');
  check('an unknown id lands on the neutral fallback', bare.nonsense.source, 'fallback');
  check('nothing unknown invents a colour', bare.nonsense.hex, null);
  check('a minecraft: prefix is stripped', bare.prefixed.source, 'block');
  check('spaces and case are normalised', bare.messy.id, 'diamond_sword');

  // pickaxe must not be read as axe — alternation order matters
  const tools = await page.evaluate(() => ['diamond_pickaxe', 'iron_axe', 'golden_shovel', 'netherite_hoe']
    .map(id => MC.visual.resolve(id).category));
  check('pickaxe resolves to Pickaxe, not Axe', tools.join(','), 'Pickaxe,Axe,Shovel,Hoe');

  // Rendered markup: never a broken image, always labelled or hidden
  const markup = await page.evaluate(() => ({
    labelled: MC.visual.render('oak_planks', { size: 'sm', label: 'Oak Planks' }),
    bare: MC.visual.render('oak_planks', { size: 'sm' }),
    unknown: MC.visual.render('nope_not_real', { size: 'md' }),
  }));
  has('a labelled visual is exposed as an image', markup.labelled, 'role="img"');
  has('...with the name as its accessible label', markup.labelled, 'aria-label="Oak Planks"');
  has('an unlabelled visual is hidden from screen readers', markup.bare, 'aria-hidden="true"');
  check('a fallback renders no <img> to break', markup.unknown.includes('<img'), false);
  has('the fallback is marked as such', markup.unknown, 'mc-visual-fallback');

  // ── WITH AN ASSET PRESENT: the drop-in path ───────────────────
  writeFixtures();
  await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && Object.keys(MC.data.textures).length > 0);

  const dropped = await page.evaluate(() => ({
    probe: MC.visual.resolve('zz_probe_item'),
    sand: MC.visual.resolve('sand'),
    html: MC.visual.render('zz_probe_item', { size: 'lg', label: 'Probe' }),
  }));
  check('a dropped-in file is found with no code change', dropped.probe.source, 'asset');
  has('...and resolves to its real path', dropped.probe.src, 'assets/textures/zz_probe_item.png');
  check('a real texture beats the palette colour', dropped.sand.source, 'asset');
  has('a real texture renders as an image', dropped.html, '<img src="assets/textures/zz_probe_item.png"');
  has('textures load lazily', dropped.html, 'loading="lazy"');

  // The file is genuinely served, not just referenced
  const texRes = await page.request.get(`${BASE}/assets/textures/zz_probe_item.png`);
  check('the texture is actually served', texRes.status(), 200);
  has('...as an image', texRes.headers()['content-type'], 'image');

  // ── BLUEPRINT CONSUMES THE SAME RESOLVER ──────────────────────
  await page.goto(`${BASE}/farms.php?farm=sugar-cane`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-farm .bp-grid'));
  const cells = await page.$$eval('.bp-cell:not(.bp-cell-air)', els => els.map(e => e.getAttribute('style')));
  check('blueprint cells paint the dropped-in texture',
    cells.filter(s => s.includes('assets/textures/sand.png')).length, 12);
  check('cells with no texture keep their colour',
    cells.some(s => s.startsWith('background:#')), true);
  check('the server-rendered material chip uses it too',
    await page.locator('.mc-chip-tex img').count() > 0, true);

  // Removing the asset must fall straight back, with nothing left broken
  removeFixtures();
  await page.goto(`${BASE}/farms.php?farm=sugar-cane`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-farm .bp-grid'));
  const after = await page.$$eval('.bp-cell:not(.bp-cell-air)', els => els.map(e => e.getAttribute('style')));
  check('removing the asset falls back cleanly', after.some(s => s.includes('assets/textures')), false);
  check('every cell still paints something', after.every(s => s && s.length > 0), true);

  // ── SHARED COMPONENTS ─────────────────────────────────────────
  const comps = await page.evaluate(() => ({
    ok: MC.ui.validation('ok', 'Works on Java 1.21+.'),
    err: MC.ui.validation('error', 'That selector is not real.', 'Invalid target'),
    bogus: MC.ui.validation('nonsense-kind', 'Unknown severity.'),
    escaped: MC.ui.validation('info', '<img src=x onerror=alert(1)>'),
  }));
  has('a success message carries its icon as text', comps.ok, '<i class="validation-icon" aria-hidden="true">✓</i>');
  has('severity is announced, not just coloured', comps.ok, '<span class="sr-only">Valid: </span>');
  has('errors are announced to screen readers', comps.err, 'role="alert"');
  has('...and can carry a title', comps.err, '<b class="validation-title">Invalid target</b>');
  has('an unknown severity degrades to info', comps.bogus, 'validation-info');
  check('validation text is escaped, not injected', comps.escaped.includes('<img src=x'), false);
  has('...it is shown as text instead', comps.escaped, '&lt;img');

  // ── ACCESSIBILITY + MOBILE FOUNDATION ─────────────────────────
  const focusRing = await page.evaluate(() => {
    const btn = document.querySelector('.btn');
    if (!btn) return null;
    btn.focus();
    const s = getComputedStyle(btn);
    return { outline: s.outlineStyle, width: s.outlineWidth };
  });
  check('buttons show a focus ring for keyboard users', focusRing && focusRing.outline !== 'none', true);

  for (const [label, width] of [['mobile', 390], ['tablet', 768]]) {
    await page.setViewportSize({ width, height: 800 });
    for (const path of ['index.php', 'tools.php', 'farms.php?farm=sugar-cane', 'enchantments.php', 'build.php?t=clear']) {
      await page.goto(`${BASE}/${path}`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(120);
      const overflow = await page.evaluate(() =>
        document.documentElement.scrollWidth - document.documentElement.clientWidth);
      check(`${label} ${path}: no horizontal overflow`, overflow <= 0, true);
    }
  }
  await page.setViewportSize({ width: 1280, height: 900 });
} finally {
  removeFixtures();
  await browser.close();
}

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
