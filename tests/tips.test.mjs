// Regression tests for Tips & Hacks (Stage 9): new categories render,
// and category/search filtering actually hides non-matching tips.
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

await page.goto(`${BASE}/knowledge.php?t=tips`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.getElementById('tips-list'));

const totalTips = await page.locator('.tip').count();
check('tips list is non-trivial', totalTips > 20, true);
const groupOptions = await page.locator('#tip-group option').allTextContents();
has('new Redstone category exists', groupOptions.join(','), 'Redstone');
has('new Command & Search category exists', groupOptions.join(','), 'Command & Search');

await page.selectOption('#tip-group', 'Redstone');
await page.waitForTimeout(100);
const visibleRedstone = await page.locator('.tip:visible').count();
check('Redstone filter narrows the list', visibleRedstone < totalTips && visibleRedstone > 0, true);
check('tip-count reflects the filter', await page.textContent('#tip-count'), `${visibleRedstone} of ${totalTips} tips`);
check('only the Redstone group heading is visible', await page.locator('[data-tip-group-title]:visible').count(), 1);

await page.selectOption('#tip-group', '');
await page.fill('#tip-search', 'observer');
await page.waitForTimeout(100);
const searchResults = await page.locator('.tip:visible').count();
check('text search narrows the list', searchResults >= 1 && searchResults < totalTips, true);
has('search finds the observer tip', await page.locator('.tip:visible .tip-title').first().textContent(), 'Observers');

await page.fill('#tip-search', '');
await page.selectOption('#tip-group', '');
await page.waitForTimeout(100);
check('clearing filters restores every tip', await page.locator('.tip:visible').count(), totalTips);

// Existing behaviour must not regress: a tip still expands to show its body
await page.click('.tip summary');
await page.waitForTimeout(80);
has('expanding a tip shows "Try this now"', await page.locator('.tip[open] .tip-try').first().textContent(), 'Try this now');

// Accuracy spot-check: the redstone tips describe real, well-known mechanics
await page.selectOption('#tip-group', 'Redstone');
await page.waitForTimeout(100);
const redstoneText = await page.textContent('#tips-list');
has('diagonal dust tip present', redstoneText, 'never runs diagonally');
has('repeater lock tip present', redstoneText, 'locks it');
has('observer tip present', redstoneText, 'block update');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
