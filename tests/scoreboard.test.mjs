// Regression tests for the Scoreboard generator — must-fix B: display names
// were wrapped in an extra pair of quotes, so any JSON text component came
// out invalid (nested unescaped quotes). See scoreboard.php sbComponentArg().
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));
const hasNot = (n, h, s) => (!String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    should NOT contain: ${s}`)));

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

await page.goto(`${BASE}/scoreboard.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.getElementById('sb-cmd-list'));

const firstCmd = async () => (await page.locator('.cmd-row-text').first().textContent()).trim();

// ── PLAIN DISPLAY NAME (not JSON) ────────────────
await page.fill('#obj-name', 'kills');
await page.fill('#obj-display', 'Kills');
await page.waitForTimeout(80);
let cmd = await firstCmd();
has('plain name: wrapped as a JSON string, not double-quoted', cmd, '/scoreboard objectives add kills dummy "Kills"');
hasNot('plain name: no nested-quote corruption', cmd, '""Kills""');

// ── JSON TEXT COMPONENT ──────────────────────────
await page.fill('#obj-display', '{"text":"Kills"}');
await page.waitForTimeout(80);
cmd = await firstCmd();
check('JSON component: passed through unwrapped', cmd,
  '/scoreboard objectives add kills dummy {"text":"Kills"}');

// ── COLOURED JSON TEXT COMPONENT ─────────────────
await page.fill('#obj-display', '{"text":"Kills","color":"red"}');
await page.waitForTimeout(80);
cmd = await firstCmd();
check('coloured component: valid single-quoted JSON argument', cmd,
  '/scoreboard objectives add kills dummy {"text":"Kills","color":"red"}');
hasNot('coloured component: no outer re-quoting', cmd, '"{"text"');

// ── DISPLAY MODE — modify displayname ────────────
await page.click('button.mtab:has-text("Display")');
await page.fill('#disp-obj', 'kills');
await page.fill('#disp-title', '{"text":"Leaderboard","color":"gold"}');
await page.waitForTimeout(80);
const rows = await page.locator('.cmd-row-text').allTextContents();
has('displayname: valid JSON, not re-quoted', rows.join('\n'),
  '/scoreboard objectives modify kills displayname {"text":"Leaderboard","color":"gold"}');

// Plain text through the modify path too.
await page.fill('#disp-title', 'Leaderboard');
await page.waitForTimeout(80);
const rows2 = await page.locator('.cmd-row-text').allTextContents();
has('displayname: plain text wrapped as JSON string', rows2.join('\n'),
  '/scoreboard objectives modify kills displayname "Leaderboard"');

// ── PRESETS use the same safe quoting ────────────
await page.click('button.preset-btn:has-text("Kill Leaderboard")');
await page.waitForTimeout(80);
const presetRows = await page.locator('.cmd-row-text').allTextContents();
has('preset: coloured display name is valid JSON', presetRows.join('\n'), '"color":"red"');
hasNot('preset: no nested-quote corruption', presetRows.join('\n'), '""');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
