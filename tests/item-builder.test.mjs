// Custom Item Builder (nbt.php), checked across syntax eras.
// It used to emit legacy NBT unconditionally, which is rejected from
// 1.20.5 onward — the default version.
//
// This file also covered book.php, sign.php and firework.php until those
// specialist generators were cut from the product; their coverage went
// with them rather than being weakened into passing.
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

const browser = await launchChromium();
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

// ── THE BUILDER AGREES WITH THE DOCTOR ──────────
// Whatever it produces on the default version should pass the Doctor.
await page.goto(`${BASE}/nbt.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('nbt-output').textContent);
await setVersion('26.2');
const generated = await out('#nbt-output');
const verdict = await page.evaluate(async c => {
  const r = await MC.api('doctor', { command: c, version: '26.2' });
  return r.result.problems.filter(x => x.level === 'error').map(x => x.title);
}, generated);
check('nbt.php: output passes the Doctor on 26.2', verdict.join(' | '), '');

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
