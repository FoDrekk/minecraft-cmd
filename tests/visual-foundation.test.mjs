// The current shared visual foundation: CSS pixel-art textures
// (assets/textures.js, MC.tex) plus the SVG-glyph/colour fallback tiles
// built on top of it (assets/mcvisual.js, MC.visual), and the shared
// validation component (MC.ui.validation) used by the Enchantment Hub.
//
// Rewritten 2026-09: the previous version of this file tested a real-file
// drop-in texture registry (lib/textures.php + MC.visual.resolve/render)
// that a later merge removed from the app entirely in favour of this
// CSS-pixel-art system. That PHP registry and its now-dead references
// were removed as part of the same cleanup that rewrote this file — see
// lib/textures.php's deletion. Nothing here tests functionality the app
// no longer has.
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';

let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

try {
  await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && MC.tex && MC.visual);

  // ── MC.tex — the CSS pixel-art texture system ──────────────────
  const tex = await page.evaluate(() => ({
    hasKnown: MC.tex.has('diamond_sword'),
    hasAlias: MC.tex.has('grass'),
    hasUnknown: MC.tex.has('not_a_real_minecraft_thing'),
    known: MC.tex.render('diamond_sword', 'md'),
    unknown: MC.tex.render('not_a_real_minecraft_thing', 'md'),
  }));
  check('a drawn texture exists for a known item', tex.hasKnown, true);
  check('the alias table resolves grass -> grass_block', tex.hasAlias, true);
  check('an unrecognised id has no texture', tex.hasUnknown, false);
  has('a known item renders as pixel-art (box-shadow grid)', tex.known, 'box-shadow');
  check('a fallback never emits a broken <img>', tex.unknown.includes('<img'), false);
  has('the fallback is visibly marked, not invisible', tex.unknown, 'mc-tex-missing');

  // ── MC.visual — the tile/card layer every page renders through ──
  const visual = await page.evaluate(() => ({
    equip: MC.visual.equipmentTile('Sword', 'diamond_sword', 'lg'),
    block: MC.visual.blockTile('#7CBD6B', 'md', 'Grass Block'),
    card: MC.visual.itemCard('diamond_sword', 'Diamond Sword', { Damage: '7' }),
    cardEscaped: MC.visual.itemCard('x', '<img src=x onerror=alert(1)>', null),
  }));
  has('an equipment tile renders a tile container', visual.equip, 'mc-tile');
  has('a known equipment item is drawn from MC.tex, not the SVG fallback', visual.equip, 'mc-tile-tex');
  has('a block tile renders a tile container', visual.block, 'mc-block-tile');
  has('an item card shows its name', visual.card, 'Diamond Sword');
  has('an item card shows its passed properties', visual.card, 'Damage: 7');
  check('item card names are escaped, not injected', visual.cardEscaped.includes('<img src=x'), false);

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

  // ── LIVE PAGES actually use these without erroring ──────────────
  await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.item-card .mc-tile, .item-card .mc-block-tile', { timeout: 10000 });
  check('Knowledge Items renders visual tiles', await page.locator('.item-card .mc-tile, .item-card .mc-block-tile').count() > 0, true);

  await page.goto(`${BASE}/enchantments.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.mc-tile', { timeout: 10000 });
  check('Enchantment Hub renders an equipment tile', await page.locator('.mc-tile').count() > 0, true);

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
  await browser.close();
}

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
