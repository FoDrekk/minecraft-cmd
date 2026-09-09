// Regression tests for Knowledge -> Items and Knowledge -> Enchantments
// (Stage 8), plus the cross-link into the Enchantment Hub.
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

// ── ITEMS TAB ──────────────────────────────────────
await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('item-grid').children.length > 0);
const totalItems = await page.locator('.item-card').count();
check('items tab lists a substantial catalogue', totalItems > 50, true);

await page.fill('#item-search', 'diamond sword');
await page.waitForTimeout(100);
check('search narrows to the exact item', await page.locator('.item-card').count(), 1);
has('enchantable item shows the Enchant Hub cross-link', await page.textContent('.item-card'), 'Enchant this');

await page.fill('#item-search', 'apple');
await page.waitForTimeout(100);
const appleCard = await page.textContent('.item-card');
check('non-enchantable item has no Enchant Hub link', appleCard.includes('Enchant this'), false);

await page.fill('#item-search', '');
await page.selectOption('#item-cat', 'Armour');
await page.waitForTimeout(100);
const armourCount = await page.locator('.item-card').count();
check('category filter actually narrows results', armourCount < totalItems && armourCount > 0, true);

// click-to-copy an item id (existing Material Library convention, reused
// here) — verify the id is wired to the shared data-copy handler rather
// than actually reading the clipboard back (unreliable headless).
await page.selectOption('#item-cat', '');
await page.fill('#item-search', 'netherite sword');
await page.waitForTimeout(100);
check('item id carries the shared data-copy attribute', await page.locator('.item-id').getAttribute('data-copy'), 'netherite_sword');

// ── ENCHANTMENTS TAB ───────────────────────────────
await page.goto(`${BASE}/knowledge.php?t=enchants`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('ench-know-grid').children.length > 0);
const totalEnch = await page.locator('.ench-know-card').count();
check('enchantments tab lists every enchantment', totalEnch > 30, true);

await page.fill('#ench-search', 'sharpness');
await page.waitForTimeout(100);
check('search narrows to Sharpness', await page.locator('.ench-know-card').count(), 1);
const sharpnessCard = await page.textContent('.ench-know-card');
has('Sharpness lists its real max level', sharpnessCard, 'Max level:');
has('Sharpness lists its conflicts (Smite/Bane of Arthropods)', sharpnessCard, 'Smite');
has('conflict list is not empty for a known-conflicting enchant', sharpnessCard, 'Bane of Arthropods');

await page.fill('#ench-search', 'mending');
await page.waitForTimeout(100);
has('Mending lists Infinity as a conflict', await page.textContent('.ench-know-card'), 'Infinity');

await page.fill('#ench-search', 'density');
await page.waitForTimeout(100);
has('mace-exclusive Density notes its version requirement', await page.textContent('.ench-know-card'), 'Requires');
has('Density notes Java 1.21+', await page.textContent('.ench-know-card'), '1.21');

// deep link with ?q= pre-fills the search
await page.goto(`${BASE}/knowledge.php?t=enchants&q=looting`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.getElementById('ench-know-grid').children.length > 0);
check('?q= deep link pre-filters to the right enchantment', await page.locator('.ench-know-card').count(), 1);
has('deep link found Looting', await page.textContent('.ench-know-card'), 'Looting');

// ── CROSS-LINK: Items -> Enchantment Hub actually preselects the item ──
await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.getElementById('item-grid').children.length > 0);
await page.fill('#item-search', 'diamond sword');
await page.waitForTimeout(100);
const enchHref = await page.locator('.item-card .ench-know-link').getAttribute('href');
has('the link points at the Enchantment Hub with the item id', enchHref, 'item=diamond_sword');

await page.goto(new URL(enchHref, BASE + '/').href, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && !document.getElementById('eh-picked-wrap').hidden);
has('following the link actually selects Diamond Sword in the Hub', await page.textContent('#eh-selected-head'), 'Diamond Sword');

// ── GLOBAL SEARCH surfaces both new types, labelled ──
const results = await page.evaluate(async () => {
  const r = await MC.api('search', { q: 'sharpness' });
  return r.rows.map(x => ({ title: x.title, cat: x.cat }));
});
check('global search finds the Sharpness enchantment entry', results.some(r => r.title === 'Sharpness' && r.cat === 'enchantment'), true);

const results2 = await page.evaluate(async () => {
  const r = await MC.api('search', { q: 'netherite sword' });
  return r.rows.map(x => ({ title: x.title, cat: x.cat }));
});
check('global search finds the Netherite Sword item entry', results2.some(r => r.title === 'Netherite Sword' && r.cat === 'item'), true);

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
