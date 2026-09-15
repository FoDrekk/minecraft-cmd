// /tellraw builder — JSON generation, events across the 1.21.5 rename,
// preview, validation and raw mode.
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

const cmd = async () => (await page.textContent('#out-tellraw [data-cmd]')).trim();
const warns = async () => (await page.textContent('#out-tellraw [data-warn]')).trim();
const setVersion = async x => { await page.evaluate(y => MC.setVersion(y), x); await page.waitForTimeout(80); };
const seg = (i, sel) => `#tr-segments .seg:nth-child(${i}) ${sel}`;

await page.goto(`${BASE}/commands.php?t=tellraw`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-tellraw [data-cmd]').textContent);

// ── BASIC ───────────────────────────────────────
check('default: single text segment', await cmd(), '/tellraw @a {"text":"Hello world"}');

await page.fill('#tr-target', '@p');
await page.fill(seg(1, '.seg-value'), 'Welcome');
await page.waitForTimeout(60);
check('target and text', await cmd(), '/tellraw @p {"text":"Welcome"}');

// ── COLOUR AND STYLE ────────────────────────────
await page.selectOption(seg(1, '.seg-color'), 'gold');
await page.click(seg(1, '.seg-style[data-style="bold"]'));
await page.waitForTimeout(60);
check('colour + bold', await cmd(), '/tellraw @p {"text":"Welcome","color":"gold","bold":true}');

await page.click(seg(1, '.seg-style[data-style="italic"]'));
await page.waitForTimeout(60);
check('two styles', await cmd(), '/tellraw @p {"text":"Welcome","color":"gold","bold":true,"italic":true}');
await page.click(seg(1, '.seg-style[data-style="bold"]'));
await page.click(seg(1, '.seg-style[data-style="italic"]'));
await page.selectOption(seg(1, '.seg-color'), '');
await page.waitForTimeout(60);

// ── MULTIPLE SEGMENTS ───────────────────────────
await page.evaluate(() => trAddSegment());
await page.fill(seg(2, '.seg-value'), ' and goodbye');
await page.selectOption(seg(2, '.seg-color'), 'red');
await page.waitForTimeout(60);
check('two segments become a list', await cmd(),
  '/tellraw @p [{"text":"Welcome"},{"text":" and goodbye","color":"red"}]');

// ── SELECTOR AND SCORE SEGMENTS ─────────────────
await page.selectOption(seg(2, '.seg-type'), 'selector');
await page.fill(seg(2, '.seg-value'), '@s');
await page.selectOption(seg(2, '.seg-color'), '');
await page.waitForTimeout(60);
check('selector segment', await cmd(), '/tellraw @p [{"text":"Welcome"},{"selector":"@s"}]');

await page.selectOption(seg(2, '.seg-type'), 'score');
await page.fill(seg(2, '.seg-value'), '@s');
await page.waitForTimeout(60);
has('score without objective is an error', await warns(), 'needs an objective name');
await page.fill(seg(2, '.seg-extra'), 'kills');
await page.waitForTimeout(60);
check('score segment', await cmd(),
  '/tellraw @p [{"text":"Welcome"},{"score":{"name":"@s","objective":"kills"}}]');

// ── CLICK AND HOVER ACROSS THE 1.21.5 RENAME ────
await page.evaluate(() => { document.querySelectorAll('#tr-segments .seg')[1].remove(); });
await page.evaluate(() => { document.querySelector('.seg-adv').open = true; });
await page.selectOption(seg(1, '.seg-click'), 'run_command');
await page.fill(seg(1, '.seg-click-value'), '/say hi');
await page.fill(seg(1, '.seg-hover'), 'Click me');
await page.waitForTimeout(60);

await setVersion('26.2');
check('1.21.5+: snake_case events', await cmd(),
  '/tellraw @p {"text":"Welcome","click_event":{"action":"run_command","command":"/say hi"},"hover_event":{"action":"show_text","value":{"text":"Click me"}}}');

await setVersion('1.21.4');
check('pre-1.21.5: camelCase events', await cmd(),
  '/tellraw @p {"text":"Welcome","clickEvent":{"action":"run_command","value":"/say hi"},"hoverEvent":{"action":"show_text","contents":{"text":"Click me"}}}');

await setVersion('1.20.4');
check('1.20.4 uses the same older shape', await cmd(),
  '/tellraw @p {"text":"Welcome","clickEvent":{"action":"run_command","value":"/say hi"},"hoverEvent":{"action":"show_text","contents":{"text":"Click me"}}}');

await setVersion('26.2');
await page.selectOption(seg(1, '.seg-click'), 'open_url');
await page.fill(seg(1, '.seg-click-value'), 'example.com');
await page.waitForTimeout(60);
has('open_url without a scheme is rejected', await warns(), 'must start with http://');
await page.fill(seg(1, '.seg-click-value'), 'https://example.com');
await page.waitForTimeout(60);
has('open_url uses the url field on 1.21.5+', await cmd(), '"click_event":{"action":"open_url","url":"https://example.com"}');
await setVersion('1.21.4');
has('open_url uses value before 1.21.5', await cmd(), '"clickEvent":{"action":"open_url","value":"https://example.com"}');
await setVersion('26.2');

// ── EMPTY / EDGE ────────────────────────────────
await page.evaluate(() => { document.getElementById('tr-segments').innerHTML = ''; });
await page.evaluate(() => rebuild());
await page.waitForTimeout(60);
check('no segments produces no command', await cmd(), '');
has('no segments explains why', await warns(), 'Add at least one segment');
has('preview shows an empty state', await page.textContent('#tr-preview'), 'Add a segment');

// ── BEDROCK ─────────────────────────────────────
await page.evaluate(() => trAddSegment());
await page.fill(seg(1, '.seg-value'), 'Hi');
await setVersion('bedrock');
has('bedrock is refused honestly', await warns(), 'Bedrock has no /tellraw text components in this form');
await setVersion('26.2');

// ── RAW MODE ────────────────────────────────────
await page.check('#tr-raw');
await page.waitForTimeout(60);
has('raw mode with no input prompts', await warns(), 'Raw mode is on');
await page.fill('#tr-raw-json', '{"text":"custom","color":"aqua"}');
await page.waitForTimeout(60);
check('raw mode passes JSON through', await cmd(), '/tellraw @p {"text":"custom","color":"aqua"}');
await page.fill('#tr-raw-json', '{"text":"broken",}');
await page.waitForTimeout(60);
has('raw mode reports bad JSON', await warns(), 'not valid JSON');
await page.uncheck('#tr-raw');
await page.waitForTimeout(60);

// ── PREVIEW ─────────────────────────────────────
await page.fill(seg(1, '.seg-value'), 'Preview text');
await page.selectOption(seg(1, '.seg-color'), 'green');
await page.waitForTimeout(60);
has('preview renders the text', await page.textContent('#tr-preview'), 'Preview text');
const previewColor = await page.getAttribute('#tr-preview span', 'style');
has('preview applies the colour', previewColor, '#55FF55');

// ── GENERATED JSON IS VALID ─────────────────────
const generated = (await cmd()).replace(/^\/tellraw \S+ /, '');
let parsed = null;
try { parsed = JSON.parse(generated); } catch (e) { /* leave null */ }
check('generated payload parses as JSON', parsed !== null, true);

// ── COMMAND DOCTOR AGREES ───────────────────────
const verdict = await page.evaluate(async c => {
  const r = await MC.api('doctor', { command: c, version: '26.2' });
  return r.result.problems.filter(p => p.level === 'error').map(p => p.title);
}, await cmd());
check('output passes the Doctor', verdict.join(' | '), '');

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
