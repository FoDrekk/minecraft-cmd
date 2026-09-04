// The pre-existing generators, checked across syntax eras.
// These used to emit legacy NBT unconditionally, which is rejected
// from 1.20.5 onward — the default version.
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

const browser = await chromium.launch();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
page.setDefaultNavigationTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

const setVersion = async v => { await page.evaluate(x => MC.setVersion(x), v); await page.waitForTimeout(80); };
const out = async sel => (await page.textContent(sel)).trim();

// ── CUSTOM ITEM BUILDER (nbt.php) ───────────────
await page.goto(`${BASE}/nbt.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('nbt-output').textContent);
await page.fill('#nbt-name', 'Excalibur');
await page.waitForTimeout(80);

await setVersion('26.2');
has('nbt 26.2: uses components', await out('#nbt-output'), '[custom_name={text:"Excalibur"');
await setVersion('1.21.4');
has('nbt 1.21.4: JSON-string text', await out('#nbt-output'), '[custom_name=\'{"text":"Excalibur"');
await setVersion('1.20.4');
has('nbt 1.20.4: legacy display tag', await out('#nbt-output'), '{display:{Name:\'{"text":"Excalibur"');

// ── BOOK WRITER ─────────────────────────────────
await page.goto(`${BASE}/book.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('bk-output').textContent);
// Load a preset so there is page content to encode
await page.evaluate(() => loadBookPreset('rules'));
await page.waitForTimeout(150);

await setVersion('26.2');
let cmd = await out('#bk-output');
has('book 26.2: written_book_content component', cmd, 'written_book[written_book_content={');
has('book 26.2: SNBT pages', cmd, 'pages:[{text:');

await setVersion('1.21.4');
cmd = await out('#bk-output');
has('book 1.21.4: component form', cmd, 'written_book[written_book_content={');
has('book 1.21.4: JSON-string pages', cmd, 'pages:[\'{"text":');

await setVersion('1.20.4');
cmd = await out('#bk-output');
has('book 1.20.4: legacy NBT', cmd, 'written_book{title:');
has('book 1.20.4: no component brackets', cmd.includes('written_book['), false);

// ── SIGN ────────────────────────────────────────
await page.goto(`${BASE}/sign.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('sign-output').textContent);
await page.fill('#line-1', 'Welcome');
await page.waitForTimeout(100);

await setVersion('26.2');
cmd = await out('#sign-output');
has('sign 26.2: front_text block entity data', cmd, '{front_text:{messages:[');
has('sign 26.2: SNBT messages', cmd, '{text:"Welcome"}');

await setVersion('1.20.4');
cmd = await out('#sign-output');
has('sign 1.20.4: JSON-string messages', cmd, '\'{"text":"Welcome"}\'');

// ── FIREWORK ────────────────────────────────────
await page.goto(`${BASE}/firework.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('fw-output').textContent);
await page.evaluate(() => loadPreset(Object.keys(PRESETS)[0]));
await page.waitForTimeout(150);

await setVersion('26.2');
cmd = await out('#fw-output');
has('firework 26.2: fireworks component', cmd, 'firework_rocket[fireworks={');
has('firework 26.2: named shape', cmd, 'shape:"');
has('firework 26.2: flight_duration', cmd, 'flight_duration:');

await setVersion('1.20.4');
cmd = await out('#fw-output');
has('firework 1.20.4: legacy Fireworks tag', cmd, 'firework_rocket{Fireworks:{Flight:');
has('firework 1.20.4: numeric Type', cmd, 'Type:');

// ── EVERY LEGACY GENERATOR AGREES WITH THE DOCTOR ──
// Whatever these produce on the default version should pass the Doctor.
const pages = [
  ['nbt.php', '#nbt-output'],
  ['book.php', '#bk-output'],
  ['firework.php', '#fw-output'],
];
for (const [p, sel] of pages) {
  await page.goto(`${BASE}/${p}`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(s => window.MC && document.querySelector(s).textContent, sel);
  await setVersion('26.2');
  const generated = await out(sel);
  const verdict = await page.evaluate(async c => {
    const r = await MC.api('doctor', { command: c, version: '26.2' });
    return r.result.problems.filter(x => x.level === 'error').map(x => x.title);
  }, generated);
  check(`${p}: output passes the Doctor on 26.2`, verdict.join(' | '), '');
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
