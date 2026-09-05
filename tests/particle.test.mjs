// /particle builder — options across the 1.20.5 SNBT change, count/delta
// semantics, coordinate validation and edition handling.
import { chromium } from 'playwright';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

const browser = await chromium.launch();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

const cmd = async () => (await page.textContent('#out-particle [data-cmd]')).trim();
const warns = async () => (await page.textContent('#out-particle [data-warn]')).trim();
const setVersion = async x => { await page.evaluate(y => MC.setVersion(y), x); await page.waitForTimeout(80); };
// Clear the search first — the list is filtered, so a leftover query
// would hide the particle we are about to select.
const pick = async id => {
  await page.fill('#pt-search', '');
  await page.waitForTimeout(60);
  await page.click(`[data-particle="${id}"]`);
  await page.waitForTimeout(80);
};

await page.goto(`${BASE}/commands.php?t=particle`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-particle [data-cmd]').textContent);

// ── DEFAULTS ────────────────────────────────────
check('default', await cmd(), '/particle flame ~ ~ ~ 0.5 0.5 0.5 0 20');

// minimum input: count 1, no delta, no speed → short form
await page.fill('#pt-dx', '0'); await page.fill('#pt-dy', '0'); await page.fill('#pt-dz', '0');
await page.fill('#pt-count', '1');
await page.waitForTimeout(80);
check('minimum input drops optional tail', await cmd(), '/particle flame ~ ~ ~');

await page.fill('#pt-dx', '0.5'); await page.fill('#pt-dy', '0.5'); await page.fill('#pt-dz', '0.5');
await page.fill('#pt-count', '20');
await page.waitForTimeout(80);

// ── SEARCH AND SELECT ───────────────────────────
await page.fill('#pt-search', 'heart');
await page.waitForTimeout(80);
check('search narrows the list', await page.$$eval('#pt-list .picker-item', e => e.length), 1);
await pick('heart');
check('selection changes the command', (await cmd()).startsWith('/particle heart '), true);
has('selection shows the id', await page.textContent('#pt-picked'), 'minecraft:heart');
await page.fill('#pt-search', '');
await page.waitForTimeout(80);

// ── DUST OPTIONS ACROSS 1.20.5 ──────────────────
await page.fill('#pt-search', 'flame');
await page.waitForTimeout(60);
await pick('flame');
check('a particle without options hides the option box', await page.isHidden('#pt-options'), true);

await pick('dust');
check('dust shows its options', await page.isHidden('#pt-options'), false);
await page.fill('#pt-scale', '1');
await page.evaluate(() => { document.getElementById('pt-color').value = '#ff0000'; rebuild(); });
await page.waitForTimeout(80);

await setVersion('26.2');
check('dust on 26.2 uses SNBT options', await cmd(),
  '/particle dust{color:[1,0,0],scale:1} ~ ~ ~ 0.5 0.5 0.5 0 20');

await setVersion('1.20.4');
check('dust on 1.20.4 uses separate arguments', await cmd(),
  '/particle dust 1 0 0 1 ~ ~ ~ 0.5 0.5 0.5 0 20');
has('1.20.4 explains the difference', await warns(), 'moved into braces');

await setVersion('26.2');
await pick('dust_color_transition');
await page.evaluate(() => {
  document.getElementById('pt-color').value = '#ff0000';
  document.getElementById('pt-color2').value = '#0000ff';
  rebuild();
});
await page.waitForTimeout(80);
check('dust_color_transition on 26.2', await cmd(),
  '/particle dust_color_transition{from_color:[1,0,0],scale:1,to_color:[0,0,1]} ~ ~ ~ 0.5 0.5 0.5 0 20');
await setVersion('1.20.4');
check('dust_color_transition on 1.20.4', await cmd(),
  '/particle dust_color_transition 1 0 0 1 0 0 1 ~ ~ ~ 0.5 0.5 0.5 0 20');
await setVersion('26.2');

// ── BLOCK AND ITEM OPTIONS ──────────────────────
await pick('block');
await page.fill('#pt-block', 'diamond_block');
await page.waitForTimeout(80);
check('block particle on 26.2', await cmd(),
  '/particle block{block_state:"minecraft:diamond_block"} ~ ~ ~ 0.5 0.5 0.5 0 20');
await setVersion('1.20.4');
check('block particle on 1.20.4', await cmd(),
  '/particle block diamond_block ~ ~ ~ 0.5 0.5 0.5 0 20');
await setVersion('26.2');

await pick('item');
await page.fill('#pt-item', 'apple');
await page.waitForTimeout(80);
check('item particle on 26.2', await cmd(),
  '/particle item{item:{id:"minecraft:apple",count:1}} ~ ~ ~ 0.5 0.5 0.5 0 20');

await pick('shriek');
await page.fill('#pt-number', '10');
await page.waitForTimeout(80);
check('shriek delay is an integer', await cmd(),
  '/particle shriek{delay:10} ~ ~ ~ 0.5 0.5 0.5 0 20');

// ── COUNT AND DELTA SEMANTICS ───────────────────
await page.fill('#pt-search', 'flame'); await page.waitForTimeout(60); await pick('flame');
await page.fill('#pt-count', '0');
await page.waitForTimeout(80);
has('count 0 is explained', await warns(), 'delta becomes a direction');
has('count 0 with speed 0 is flagged', await warns(), 'single motionless particle');
await page.fill('#pt-speed', '0.5');
await page.waitForTimeout(80);
check('count 0 with speed', await cmd(), '/particle flame ~ ~ ~ 0.5 0.5 0.5 0.5 0');

await page.fill('#pt-count', '5000'); await page.fill('#pt-speed', '0');
await page.waitForTimeout(80);
has('very high counts are flagged', await warns(), 'hit frame rate');
await page.fill('#pt-count', '20');
await page.waitForTimeout(80);

// ── MODE AND VIEWERS ────────────────────────────
await page.evaluate(() => { document.querySelector('[data-panel="particle"] details.adv').open = true; });
await page.selectOption('#pt-mode', 'force');
await page.waitForTimeout(80);
check('force mode', await cmd(), '/particle flame ~ ~ ~ 0.5 0.5 0.5 0 20 force');
await page.fill('#pt-viewers', '@a');
await page.waitForTimeout(80);
check('force mode with viewers', await cmd(), '/particle flame ~ ~ ~ 0.5 0.5 0.5 0 20 force @a');
await page.selectOption('#pt-mode', 'normal');
await page.waitForTimeout(80);
check('normal mode still emitted when viewers are set', await cmd(), '/particle flame ~ ~ ~ 0.5 0.5 0.5 0 20 normal @a');
has('normal mode range is explained', await warns(), 'within about 32 blocks');
await page.fill('#pt-viewers', '');
await page.waitForTimeout(80);

// ── INVALID INPUT ───────────────────────────────
await page.fill('#pt-x', '^2');
await page.waitForTimeout(80);
has('mixed coordinates rejected', await warns(), 'cannot be mixed');
await page.fill('#pt-x', '~');
await page.waitForTimeout(80);

// ── BEDROCK ─────────────────────────────────────
await setVersion('bedrock');
has('bedrock is refused honestly', await warns(), 'Java syntax only');
await setVersion('26.2');

// ── PREVIEW ─────────────────────────────────────
check('preview renders dots', (await page.$$eval('#pt-preview i', e => e.length)) > 0, true);

// ── DOCTOR ──────────────────────────────────────
const verdict = await page.evaluate(async c => {
  const r = await MC.api('doctor', { command: c, version: '26.2' });
  return r.result.problems.filter(p => p.level === 'error').map(p => p.title);
}, await cmd());
check('output passes the Doctor', verdict.join(' | '), '');

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
