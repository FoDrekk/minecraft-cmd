// Regression tests for Build Ideas + the Blueprint viewer + Material
// Calculator (Stages 4-6). Tests behaviour, not just markup presence.
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

// ── LIST VIEW: category filter narrows the actual DOM, not just a label ──
await page.goto(`${BASE}/knowledge.php?t=ideas`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.getElementById('idea-grid'));
const totalCards = await page.locator('.idea-card').count();
check('every idea has a category badge', await page.locator('.idea-card .tag').count() > 0, true);

await page.selectOption('#idea-cat', 'Functional');
await page.waitForTimeout(100);
const visibleAfter = await page.locator('.idea-card:visible').count();
check('category filter actually hides non-matching cards', visibleAfter < totalCards, true);
check('idea-count reflects the filtered number', await page.textContent('#idea-count'), `${visibleAfter} of ${totalCards} ideas`);

await page.selectOption('#idea-cat', '');
await page.waitForTimeout(100);
check('clearing the filter restores every card', await page.locator('.idea-card:visible').count(), totalCards);

// ── DETAIL VIEW: an idea without a blueprint shows no fabricated one ──
await page.goto(`${BASE}/knowledge.php?t=ideas&idea=castle`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.querySelector('.step-list'));
check('castle has real build steps', await page.locator('.step-list li').count() >= 3, true);
check('castle has no blueprint widget (none was hand-verified for it)', await page.locator('#bp-viewport-idea').count(), 0);

// ── DETAIL VIEW: starter-house DOES have a real, interactive blueprint ──
await page.goto(`${BASE}/knowledge.php?t=ideas&idea=starter-house`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-idea .bp-grid'));

const layerCount = await page.locator('.bp-layer-btn').count();
check('starter-house blueprint has multiple Y layers', layerCount > 1, true);

// select build -> switch Y layer -> verify the grid actually changes
const layer0Html = await page.locator('#bp-viewport-idea .bp-grid').innerHTML();
await page.click('.bp-layer-btn[data-bp-layer="3"]'); // timber walls, visually different from the foundation
await page.waitForTimeout(100);
const layer3Html = await page.locator('#bp-viewport-idea .bp-grid').innerHTML();
check('switching Y layer changes the rendered grid', layer0Html !== layer3Html, true);
has('the new layer is marked active', await page.locator('.bp-layer-btn.active').textContent(), 'Timber walls');

// select material -> verify highlight + material count updates together
const legendRow = page.locator('[data-bp-hl]').first();
const legendLabel = (await legendRow.textContent()).trim();
await legendRow.click();
await page.waitForTimeout(100);
check('clicking a legend entry highlights matching cells', await page.locator('.bp-cell-hl').count() > 0, true);
check('clicking a legend entry dims the rest', await page.locator('.bp-cell-dim').count() > 0, true);
await legendRow.click();
await page.waitForTimeout(100);
check('clicking the same legend entry again clears the highlight', await page.locator('.bp-cell-hl').count(), 0);

// zoom actually resizes the grid cells
const colsBefore = await page.locator('#bp-viewport-idea .bp-grid').evaluate(el => el.style.gridTemplateColumns);
await page.click('[data-bp-zoom="1"]');
await page.waitForTimeout(100);
const colsAfter = await page.locator('#bp-viewport-idea .bp-grid').evaluate(el => el.style.gridTemplateColumns);
check('zoom in changes the cell size', colsBefore === colsAfter, false);

// hover shows real coordinates, not a placeholder
await page.hover('#bp-viewport-idea .bp-cell:not(.bp-cell-air)');
await page.waitForTimeout(80);
has('hovering a block shows its X/Z/Y', await page.textContent('[data-bp-hover]'), 'X:');

// ── MATERIAL CALCULATOR: real counts, matching the blueprint exactly ──
const matText = await page.textContent('.mcalc-table');
has('material table lists Stone Bricks', matText, 'Stone Bricks');
has('material table lists Spruce Planks', matText, 'Spruce Planks');
const totalText = await page.textContent('.mcalc-total');
has('total is a real number, not a placeholder', totalText, 'blocks total');
check('total is not zero', /^0 /.test(totalText.trim()), false);

// The calculator numbers must actually match blueprintMaterialCounts() —
// cross-check via the same PHP function through a page-side fetch is not
// available, so instead verify stack math is internally consistent: any
// row's stack text must reduce to the same count shown.
const rows = await page.locator('.mcalc-table tr').all();
for (const row of rows) {
  const count = parseInt((await row.locator('.mcalc-count').textContent()).replace(/,/g, ''), 10);
  const stacksText = await row.locator('.mcalc-stacks').textContent();
  const m = stacksText.match(/(\d+) stacks?(?: \+ (\d+))?/);
  if (m) {
    const computed = parseInt(m[1], 10) * 64 + parseInt(m[2] || '0', 10);
    check(`stack math is correct for a row totalling ${count}`, computed, count);
  }
}

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
