// The shared visual foundation: the authentic-texture resolver
// (lib/textures.php server-side, MC.textureSrc() client-side), the
// tile/card layer built on top of it (assets/mcvisual.js, MC.visual),
// and the shared validation component (MC.ui.validation) used by the
// Enchantment Hub.
//
// The app no longer draws procedural CSS pixel-art standing in for real
// Minecraft textures (assets/textures.js and its MC.tex API are gone).
// The repository now ships ~580 authentic, legitimately-supplied item
// textures under assets/textures/item/ — this file checks both that
// path (real, permanent files resolving for real ids) and the generic
// drop-in mechanism itself (a fixture PNG written mid-run for an id the
// repo doesn't ship, proving the resolver isn't hardcoded to the
// specific ids currently on disk). assets/textures/block/ intentionally
// ships nothing yet — the supplied archive was items only — so block
// ids still fall back to the catalogued palette colour.
import { launchChromium } from './_launch.mjs';
import { writeFileSync, unlinkSync, existsSync, mkdirSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');

let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

// A 1x1 PNG. Deliberately not a Minecraft texture — it only has to be a
// real image file so the pipeline has something genuine to resolve.
const PNG_1PX = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64');

const FIXTURES = [
  ['item', 'zz_probe_item.png'],
  ['item', 'sand.png'],
  ['block', 'zz_probe_block.png'],
];
function writeFixtures() {
  for (const [kind, name] of FIXTURES) {
    const dir = join(ROOT, 'assets', 'textures', kind);
    if (!existsSync(dir)) mkdirSync(dir, { recursive: true });
    writeFileSync(join(dir, name), PNG_1PX);
  }
}
function removeFixtures() {
  for (const [kind, name] of FIXTURES) {
    try { unlinkSync(join(ROOT, 'assets', 'textures', kind, name)); } catch {}
  }
}

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

try {
  // ── SUPPLIED TEXTURES: the real, permanent asset set ────────────
  // "mace" is a genuine gap in the supplied archive (it predates the
  // Mace's 1.21 addition) — a real, honest example of "no texture yet"
  // rather than a contrived unknown id, and exactly what the fallback
  // chain below must handle cleanly.
  removeFixtures();
  await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && MC.visual);

  const supplied = await page.evaluate(() => ({
    itemCount: Object.keys(MC.data.textures.item).length,
    blockCount: Object.keys(MC.data.textures.block).length,
    diamondSword: MC.textureSrc('diamond_sword', 'item'),
    netheriteSword: MC.textureSrc('netherite_sword', 'item'),
    diamondPickaxe: MC.textureSrc('diamond_pickaxe', 'item'),
    bow: MC.textureSrc('bow', 'item'),
    apple: MC.textureSrc('apple', 'item'),
    equipSword: MC.visual.equipmentTile('Sword', 'diamond_sword', 'lg'),
    equipBow: MC.visual.equipmentTile('Bow', 'bow', 'lg'),
    cardApple: MC.visual.itemCard('apple', 'Apple', null),
  }));
  check('the repository ships the supplied item textures (~580)', supplied.itemCount >= 500, true);
  check('no block textures were supplied (item-only archive)', supplied.blockCount, 0);
  check('Diamond Sword resolves to its real supplied texture', supplied.diamondSword, 'assets/textures/item/diamond_sword.png');
  check('Netherite Sword resolves to its real supplied texture', supplied.netheriteSword, 'assets/textures/item/netherite_sword.png');
  check('Diamond Pickaxe resolves to its real supplied texture', supplied.diamondPickaxe, 'assets/textures/item/diamond_pickaxe.png');
  check('Bow resolves to its real supplied texture', supplied.bow, 'assets/textures/item/bow.png');
  check('Apple resolves to its real supplied texture', supplied.apple, 'assets/textures/item/apple.png');
  has('the equipment tile renders Diamond Sword as a real <img>', supplied.equipSword, '<img class="mc-tex-img" src="assets/textures/item/diamond_sword.png"');
  has('...and the Bow too', supplied.equipBow, 'src="assets/textures/item/bow.png"');
  has('the item card renders the real Apple texture', supplied.cardApple, 'assets/textures/item/apple.png');

  // The files are genuinely served, not just referenced
  const swordRes = await page.request.get(`${BASE}/assets/textures/item/diamond_sword.png`);
  check('the diamond sword texture is actually served', swordRes.status(), 200);
  has('...as an image', swordRes.headers()['content-type'], 'image');

  // ── THE HONEST FALLBACK CHAIN (mace: a genuine supply gap) ──────
  const bare = await page.evaluate(() => ({
    hasMace: MC.hasTexture('mace', 'item'),
    srcNone: MC.textureSrc('mace', 'item'),
    equip: MC.visual.equipmentTile('Mace', 'mace', 'lg'),
    block: MC.visual.blockTile('#7CBD6B', 'md', 'Grass Block', 'grass_block'),
    card: MC.visual.itemCard('mace', 'Mace', { Damage: '7' }),
    cardEscaped: MC.visual.itemCard('x', '<img src=x onerror=alert(1)>', null),
  }));
  check('mace has no supplied texture (predates the archive)', bare.hasMace, false);
  check('the unresolved id has no src', bare.srcNone, null);
  check('with no texture, the equipment tile draws the SVG glyph, not an <img>', bare.equip.includes('<img'), false);
  has('...marked as the glyph tile, not a texture tile', bare.equip, 'mc-tile');
  check('a fallback never emits a broken <img>', bare.block.includes('<img'), false);
  has('a block with no texture falls back to its palette colour', bare.block, '#7CBD6B');
  has('an item card shows its name', bare.card, 'Mace');
  has('an item card shows its passed properties', bare.card, 'Damage: 7');
  check('item card names are escaped, not injected', bare.cardEscaped.includes('<img src=x'), false);

  // ── THE GENERIC DROP-IN MECHANISM (an id the repo doesn't ship) ──
  // Proves the resolver isn't hardcoded to the ~580 supplied ids —
  // any legitimately-added file is picked up with zero code change.
  writeFixtures();
  await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && MC.hasTexture('zz_probe_item', 'item'));

  const dropped = await page.evaluate(() => ({
    hasProbe: MC.hasTexture('zz_probe_item', 'item'),
    src: MC.textureSrc('zz_probe_item', 'item'),
    hasBlockProbe: MC.hasTexture('zz_probe_block', 'block'),
    equip: MC.visual.equipmentTile('Sword', 'zz_probe_item', 'lg'),
    block: MC.visual.blockTile('#7CBD6B', 'md', 'Probe Block', 'zz_probe_block'),
    card: MC.visual.itemCard('zz_probe_item', 'Probe Item', null),
  }));
  check('a dropped-in item texture is found with no code change', dropped.hasProbe, true);
  has('...and resolves to its real path', dropped.src, 'assets/textures/item/zz_probe_item.png');
  check('a dropped-in block texture is found too', dropped.hasBlockProbe, true);
  has('the equipment tile now renders the real texture as an <img>', dropped.equip, '<img class="mc-tex-img"');
  has('...pointing at the real file', dropped.equip, 'assets/textures/item/zz_probe_item.png');
  has('the block tile renders the real texture too', dropped.block, 'src="assets/textures/block/zz_probe_block.png"');
  has('the item card renders the real texture too', dropped.card, 'assets/textures/item/zz_probe_item.png');

  // The file is genuinely served, not just referenced
  const texRes = await page.request.get(`${BASE}/assets/textures/item/zz_probe_item.png`);
  check('the texture is actually served', texRes.status(), 200);
  has('...as an image', texRes.headers()['content-type'], 'image');

  // ── FARMS: ui_material_chip() (PHP) resolves the same way ──────
  await page.goto(`${BASE}/farms.php?farm=sugar-cane`, { waitUntil: 'domcontentloaded' });
  check('the server-rendered material chip uses the dropped-in texture',
    await page.locator('.mc-chip-img img[src*="sand.png"]').count() > 0, true);

  // Removing the asset must fall straight back, with nothing left broken
  removeFixtures();
  await page.goto(`${BASE}/farms.php?farm=sugar-cane`, { waitUntil: 'domcontentloaded' });
  check('removing the asset falls back cleanly (no dangling <img> to it)',
    (await page.content()).includes('sand.png'), false);
  await page.waitForSelector('.mc-chip');
  check('every checklist row still shows a chip', await page.locator('.mc-chip').count() > 0, true);

  // ── ITEM BUILDER: same resolver, no third-party image URLs ─────
  await page.goto(`${BASE}/nbt.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.item-sel-card');
  const nbtHtml = await page.content();
  check('the item picker never references a third-party image host', /https?:\/\/[^"']*\.(png|jpe?g|webp)/i.test(nbtHtml), false);

  await page.fill('#nbt-item-search', 'diamond sword');
  await page.waitForTimeout(150);
  has('a supplied item (Diamond Sword) renders the real texture, not a placeholder',
    await page.locator('.item-sel-card').first().innerHTML(), 'assets/textures/item/diamond_sword.png');

  await page.fill('#nbt-item-search', 'mace');
  await page.waitForTimeout(150);
  check('an item with no supplied texture (Mace) shows an initial, not a broken image',
    await page.locator('.item-sel-card .item-sel-initial').count() > 0, true);
  await page.fill('#nbt-item-search', '');

  // ── MC.ui.validation — shared by the Enchantment Hub target field ──
  const val = await page.evaluate(() => ({
    ok: MC.ui.validation('ok', 'Works on Java 1.21+.'),
    err: MC.ui.validation('error', 'That selector is not real.', 'Invalid target'),
    escaped: MC.ui.validation('info', '<img src=x onerror=alert(1)>'),
  }));
  has('a success message carries its icon as text', val.ok, '✓');
  has('severity is announced for screen readers', val.ok, 'sr-only');
  has('errors are announced as alerts', val.err, 'role="alert"');
  has('...and can carry a title', val.err, 'Invalid target');
  check('validation text is escaped, not injected', val.escaped.includes('<img src=x'), false);

  // ── LIVE PAGES: same resolver, real supplied textures render ────
  await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.item-card .mc-tile, .item-card .mc-block-tile', { timeout: 10000 });
  check('Knowledge Items renders visual tiles', await page.locator('.item-card .mc-tile, .item-card .mc-block-tile').count() > 0, true);
  await page.fill('#item-search', 'diamond sword');
  await page.waitForTimeout(150);
  has('Knowledge renders the real Diamond Sword texture, not a placeholder',
    await page.locator('.item-card').first().innerHTML(), 'assets/textures/item/diamond_sword.png');
  await page.fill('#item-search', '');

  await page.goto(`${BASE}/enchantments.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.mc-tile', { timeout: 10000 });
  check('Enchantment Hub renders an equipment tile', await page.locator('.mc-tile').count() > 0, true);
  await page.fill('#eh-search', 'diamond pickaxe');
  await page.waitForTimeout(150);
  has('Enchantment Hub renders the real Diamond Pickaxe texture, not a placeholder',
    await page.locator('.eh-item-card').first().innerHTML(), 'assets/textures/item/diamond_pickaxe.png');

  // Search never duplicates texture-path logic — it has no per-item
  // Minecraft art of its own to resolve (its result icons are generic
  // category glyphs from lib/registry.php, not item textures), so
  // there is nothing here for the resolver to own.
  const searchJs = readFileSync(join(ROOT, 'assets', 'search.js'), 'utf8');
  check('search.js does not hardcode any texture path', /assets\/textures/.test(searchJs), false);

  // ── ACCESSIBILITY + MOBILE FOUNDATION ─────────────────────────
  // A real Tab keypress (not a scripted .focus()) is what actually
  // triggers :focus-visible matching in Chromium, so this is the
  // honest way to check a keyboard user gets a visible ring.
  await page.keyboard.press('Tab');
  const focusRing = await page.evaluate(() => {
    const el = document.activeElement;
    if (!el || !el.matches('.btn, a, button, input, select')) return null;
    const s = getComputedStyle(el);
    return { outline: s.outlineStyle };
  });
  check('keyboard focus shows a visible ring', focusRing && focusRing.outline !== 'none', true);

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
