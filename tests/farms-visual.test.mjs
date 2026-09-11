// Regression tests for the Farm visual expansion: blueprint step
// navigator, orientation arrows, auto-highlight, material chips, and
// the prose-step "View in blueprint" jump buttons. Must not weaken
// any existing farms/blueprint behaviour.
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

// ── SUGAR CANE FARM: full step-synced blueprint ───────────────────
await page.goto(`${BASE}/farms.php?farm=sugar-cane`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-farm .bp-grid'));

check('sugar-cane blueprint has 6 layers (one per step)', await page.locator('.bp-layer-btn').count(), 6);
check('step navigator is visible', await page.locator('[data-bp-stepnav]').isVisible(), true);
has('step navigator opens on step 1', (await page.textContent('[data-bp-stepnav-label]')).trim(), 'Step 1 of 6');
has('step 1 text comes from the farm\'s own prose', await page.textContent('[data-bp-stepnav-text]'), 'water channel');
check('Prev is disabled on step 1', await page.locator('[data-bp-step="-1"]').isDisabled(), true);
check('Next is enabled on step 1', await page.locator('[data-bp-step="1"]').isDisabled(), false);

check('material chips render for every structured material', await page.locator('.mc-chip').count(), 9);
check('checklist still has one row per material', await page.locator('[data-checklist] input[type=checkbox]').count(), 9);

// "View in blueprint" on step 3 (observers) jumps + highlights + shows facing
check('step 3 has a View in blueprint button', await page.locator('[data-step-jump="3"]').count(), 1);
await page.click('[data-step-jump="3"]');
await page.waitForTimeout(120);
has('jumping to step 3 updates the navigator label', await page.textContent('[data-bp-stepnav-label]'), 'Step 3 of 6');
check('the active layer tab matches step 3 (observers)', await page.locator('.bp-layer-btn.active').textContent().then(t => t.includes('Observers')), true);
check('step 3 auto-highlights the observer row', await page.locator('.bp-cell-hl').count(), 12);
check('step 3 shows an orientation arrow', await page.locator('[data-bp-facing]').isVisible(), true);
has('facing reads North (observer faces the cane)', await page.textContent('[data-bp-facing]'), 'North');

// Next/Prev buttons move through the step-linked layers in order
await page.click('[data-bp-step="1"]');
await page.waitForTimeout(120);
has('Next from step 3 goes to step 4', await page.textContent('[data-bp-stepnav-label]'), 'Step 4 of 6');
await page.click('[data-bp-step="-1"]');
await page.waitForTimeout(120);
has('Prev from step 4 returns to step 3', await page.textContent('[data-bp-stepnav-label]'), 'Step 3 of 6');

// Manual legend click narrows a multi-char auto-highlight down to one
// material (step 1 auto-highlights both water and sand), then clicking
// that same row again clears it entirely.
await page.click('.bp-layer-btn[data-bp-layer="0"]');
await page.waitForTimeout(120);
check('step 1 auto-highlights both new materials', await page.locator('.bp-cell-hl').count(), 24);
const legendRow = page.locator('[data-bp-hl]').first();
await legendRow.click();
await page.waitForTimeout(100);
check('legend click narrows the highlight to a single material', await page.locator('.bp-cell-hl').count(), 12);
check('legend click dims the other material', await page.locator('.bp-cell-dim').count(), 12);
await legendRow.click();
await page.waitForTimeout(100);
check('legend click again clears the highlight', await page.locator('.bp-cell-hl').count(), 0);

// Whole-build material totals must be real counts, not duplicated across steps
const matText = await page.textContent('.bp-materials');
has('whole-build panel lists Sugar Cane', matText, 'Sugar Cane');
const caneRow = await page.locator('.bp-mat-row', { hasText: 'Sugar Cane' }).textContent();
has('Sugar Cane total is 12 (not doubled by showing it on two steps)', caneRow, '12');
check('Sugar Cane total is not the doubled 24', caneRow.includes('24'), false);

// Last step (6) disables Next
await page.click('.bp-layer-btn[data-bp-layer="5"]');
await page.waitForTimeout(120);
check('Next is disabled on the final step', await page.locator('[data-bp-step="1"]').isDisabled(), true);

// ── CREEPER FARM: module blueprint, distinct footprint per layer ──
await page.goto(`${BASE}/farms.php?farm=creeper`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-farm .bp-grid'));
check('creeper module blueprint has 6 step layers', await page.locator('.bp-layer-btn').count(), 6);
check('creeper materials render as chips', await page.locator('.mc-chip').count(), 8);
check('creeper farm calculator still works alongside the blueprint', await page.locator('#fc-result').count(), 1);

await page.click('[data-step-jump="5"]');
await page.waitForTimeout(120);
has('jumping to step 5 shows the kill chamber', await page.textContent('[data-bp-stepnav-text]'), 'campfire');
check('kill chamber layer has its own smaller footprint (3x3=9 cells)', await page.locator('.bp-cell:not(.bp-cell-air)').count(), 9);

// ── WHEAT FARM: no step data — must not regress into showing a navigator ──
await page.goto(`${BASE}/farms.php?farm=wheat`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#bp-viewport-farm .bp-grid'));
check('wheat farm blueprint has no step navigator (no layer opts into one)', await page.locator('[data-bp-stepnav]').count(), 0);
check('wheat farm has no material chips (plain string materials, unchanged)', await page.locator('.mc-chip').count(), 0);
check('wheat farm has no jump buttons', await page.locator('[data-step-jump]').count(), 0);
check('wheat farm checklist is unaffected', await page.locator('[data-checklist] input[type=checkbox]').count() > 0, true);

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
