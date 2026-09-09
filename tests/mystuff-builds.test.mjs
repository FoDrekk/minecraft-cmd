// Regression tests for My Stuff -> Saved Builds (Stage 11): save, load,
// and delete round-trip through the real api.php + SQLite/MySQL storage.
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

// ── SAVE via the real UI on the idea detail page ──
const buildName = 'Test Save ' + Date.now();
await page.goto(`${BASE}/knowledge.php?t=ideas&idea=starter-house`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('build-save-name'));
await page.fill('#build-save-name', buildName);
await page.click('button:has-text("Save build")');
await page.waitForTimeout(300);

const saved = await page.evaluate(async (name) => {
  const r = await MC.api('build_get', {});
  return r.rows.find(x => x.build_name === name);
}, buildName);
check('build was actually persisted with the right idea', saved && saved.idea_id, 'starter-house');
check('build persisted the palette too', saved && saved.palette_id, 'medieval-village');

// ── LOAD: it appears in My Stuff, cross-linked correctly ──
await page.goto(`${BASE}/mystuff.php?t=builds`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction((name) => document.body.textContent.includes(name), buildName);
const entryText = await page.locator('.entry', { hasText: buildName }).first().textContent();
has('My Stuff shows the idea title', entryText, 'Starter House');
has('My Stuff shows the palette name', entryText, 'Medieval Village');
const openLink = await page.locator('.entry', { hasText: buildName }).first().locator('a', { hasText: 'Open build' }).getAttribute('href');
has('the Open link points back at the exact idea', openLink, 'idea=starter-house');

// ── An invalid idea id is rejected server-side, not silently stored ──
const rejected = await page.evaluate(async () => {
  const r = await MC.api('build_save', { name: 'bogus', idea: 'not-a-real-idea', palette: '' });
  return r;
});
check('saving a nonexistent idea id is rejected', rejected.ok, false);

// ── DELETE round-trips too ──
await page.goto(`${BASE}/mystuff.php?t=builds`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction((name) => document.body.textContent.includes(name), buildName);
page.once('dialog', d => d.accept());
await page.locator('.entry', { hasText: buildName }).first().locator('[data-del-build]').click();
await page.waitForTimeout(300);
const stillThere = await page.evaluate(async (name) => {
  const r = await MC.api('build_get', {});
  return !!r.rows.find(x => x.build_name === name);
}, buildName);
check('deleting removes it from storage', stillThere, false);
check('deleting removes it from the DOM immediately', await page.locator('.entry', { hasText: buildName }).count(), 0);

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
