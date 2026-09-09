// Regression tests for the Farm Blueprint + Farm Calculator additions.
// Must not weaken the existing farms.php behaviour (checklists etc).
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

// ── Wheat farm: has a real blueprint ──────────────
await page.goto(`${BASE}/farms.php?farm=wheat`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-farm .bp-grid'));
check('wheat farm blueprint has 2 layers (ground + crops)', await page.locator('.bp-layer-btn').count(), 2);
has('ground layer legend includes farmland', await page.textContent('[data-bp-legend]'), 'Farmland');
const layer0 = await page.locator('#bp-viewport-farm .bp-grid').innerHTML();
await page.click('.bp-layer-btn[data-bp-layer="1"]');
await page.waitForTimeout(100);
const layer1 = await page.locator('#bp-viewport-farm .bp-grid').innerHTML();
check('switching layer changes the grid', layer0 !== layer1, true);
has('crop layer legend includes wheat', await page.textContent('[data-bp-legend]'), 'Wheat');
check('wheat farm has no calculator (no stated numeric rate)', await page.locator('#fc-result').count(), 0);

// ── Iron farm: no blueprint, no calculator — honest gaps, not fakes ──
await page.goto(`${BASE}/farms.php?farm=iron`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.querySelector('.guide h1'));
check('iron farm has no blueprint widget', await page.locator('#bp-viewport-farm').count(), 0);
check('iron farm has no calculator', await page.locator('#fc-result').count(), 0);

// ── Creeper farm: the one with a stated rate — real calculator ───
await page.goto(`${BASE}/farms.php?farm=creeper`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.getElementById('fc-result'));
const initial = (await page.textContent('#fc-result')).trim();
has('1-hour estimate uses the stated range', initial, '500');
has('1-hour estimate uses the stated range (high)', initial, '1,000');

await page.fill('#fc-hours', '3');
await page.waitForTimeout(80);
const scaled = (await page.textContent('#fc-result')).trim();
has('3-hour estimate scales the low end (1500)', scaled, '1,500');
has('3-hour estimate scales the high end (3000)', scaled, '3,000');
has('result is explicitly labelled an estimate', scaled, 'estimate');

await page.fill('#fc-hours', '0');
await page.waitForTimeout(80);
has('zero hours gives zero, not a crash or NaN', (await page.textContent('#fc-result')).trim(), '0–0');

// Material checklist still works (existing feature, must not regress)
const firstBox = page.locator('[data-checklist] input[type=checkbox]').first();
await firstBox.check();
await page.waitForTimeout(80);
has('checklist progress updates', await page.textContent('#check-progress'), 'gathered');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
