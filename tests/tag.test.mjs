// Regression tests for the /tag builder (must-fix D).
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

const out = async sel => (await page.textContent(sel)).trim();

await page.goto(`${BASE}/commands.php?t=tag`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('out-tag'));

// The panel should be visible (not hidden) since ?t=tag selected it.
check('tag panel is the active task', await page.$eval('[data-panel="tag"]', el => el.hidden), false);

// ── ADD (default state) ──────────────────────────
await page.fill('#tag-name', 'quest_done');
await page.waitForTimeout(80);
check('tag add: default target @s', await out('#out-tag [data-cmd]'), '/tag @s add quest_done');

// ── Custom target ─────────────────────────────────
await page.fill('#tag-target', '@e[type=cow]');
await page.waitForTimeout(80);
check('tag add: selector target', await out('#out-tag [data-cmd]'), '/tag @e[type=cow] add quest_done');

// ── REMOVE ─────────────────────────────────────────
await page.click('[data-tag-op="remove"]');
await page.waitForTimeout(80);
check('tag remove', await out('#out-tag [data-cmd]'), '/tag @e[type=cow] remove quest_done');

// ── LIST — the tag name field should be hidden and unused ──
await page.click('[data-tag-op="list"]');
await page.waitForTimeout(80);
check('tag list', await out('#out-tag [data-cmd]'), '/tag @e[type=cow] list');
const nameFieldHidden = await page.$eval('[data-tag-field="name"]', el => el.hidden);
check('tag list: name field hidden', nameFieldHidden, true);

// ── VALIDATION: empty name blocked on add ────────
await page.click('[data-tag-op="add"]');
await page.fill('#tag-name', '');
await page.waitForTimeout(80);
check('tag add: empty name produces no command', await out('#out-tag [data-cmd]'), '');

// ── VALIDATION: spaces flagged ────────────────────
await page.fill('#tag-name', 'quest done');
await page.waitForTimeout(80);
const warn = await out('#out-tag [data-warn]');
has('tag add: space in name warns', warn, 'cannot contain spaces');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
