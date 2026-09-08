// /attribute and /data builders — operations, version differences and
// the validation that stops an unrunnable command being offered.
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

const cmd = async id => (await page.textContent(`#out-${id} [data-cmd]`)).trim();
const warns = async id => (await page.textContent(`#out-${id} [data-warn]`)).trim();
const setVersion = async x => { await page.evaluate(y => MC.setVersion(y), x); await page.waitForTimeout(90); };
const T = 70;

/* ═══════════ ATTRIBUTE ═══════════ */
await page.goto(`${BASE}/commands.php?t=attribute`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-attribute [data-cmd]').textContent);

check('attribute: default get', await cmd('attribute'), '/attribute @s max_health get');
has('attribute: explains the attribute', await page.textContent('#at-attr-desc'), 'Total hearts');
has('attribute: shows the id used', await page.textContent('#at-attr-id'), 'max_health on 26.2');
has('attribute: get is explained', await warns('attribute'), 'after every modifier');

// ── ID PREFIX ACROSS VERSIONS ───────────────────
await setVersion('1.20.6');
check('attribute: prefixed id before 1.21.4', await cmd('attribute'), '/attribute @s generic.max_health get');
await setVersion('1.21.1');
check('attribute: 1.21.1 still writes the prefix', await cmd('attribute'), '/attribute @s generic.max_health get');
has('attribute: the ambiguous window is flagged', await warns('attribute'), 'sources disagree on exactly which release');
await setVersion('1.21.4');
check('attribute: un-prefixed from 1.21.4', await cmd('attribute'), '/attribute @s max_health get');
await setVersion('26.2');

// ── SCALE ───────────────────────────────────────
await page.evaluate(() => { document.querySelector('[data-panel="attribute"] details.adv').open = true; });
await page.fill('#at-scale', '2');
await page.waitForTimeout(T);
check('attribute: get with scale', await cmd('attribute'), '/attribute @s max_health get 2');
await page.fill('#at-scale', '1');
await page.waitForTimeout(T);

// ── BASE SET ────────────────────────────────────
await page.click('[data-at-op="base-set"]');
await page.waitForTimeout(T);
check('attribute: base set', await cmd('attribute'), '/attribute @s max_health base set 20');
has('attribute: raising health does not heal', await warns('attribute'), 'does not heal');
await page.fill('#at-value', '0');
await page.waitForTimeout(T);
has('attribute: zero max health rejected', await warns('attribute'), 'must be above 0');
await page.fill('#at-value', '40');
await page.waitForTimeout(T);
check('attribute: base set 40', await cmd('attribute'), '/attribute @s max_health base set 40');

await page.selectOption('#at-attr', 'knockback_resistance');
await page.fill('#at-value', '5');
await page.waitForTimeout(T);
has('attribute: out-of-range resistance flagged', await warns('attribute'), 'runs from 0 to 1');
await page.selectOption('#at-attr', 'movement_speed');
await page.fill('#at-value', '0.2');
await page.waitForTimeout(T);
check('attribute: decimal base value survives', await cmd('attribute'), '/attribute @s movement_speed base set 0.2');

// ── MODIFIERS ───────────────────────────────────
await page.click('[data-at-op="modifier-add"]');
await page.fill('#at-value', '2');
await page.waitForTimeout(T);
check('attribute: modifier add on 26.2', await cmd('attribute'),
  '/attribute @s movement_speed modifier add minecraft:my_boost 2 add_value');

await page.selectOption('#at-mod-op', 'add_multiplied_total');
await page.waitForTimeout(T);
check('attribute: 1.21 operation names', await cmd('attribute'),
  '/attribute @s movement_speed modifier add minecraft:my_boost 2 add_multiplied_total');

await setVersion('1.20.6');
await page.waitForTimeout(T);
check('attribute: pre-1.21 takes a uuid and a name', await cmd('attribute'),
  '/attribute @s generic.movement_speed modifier add minecraft:my_boost my_boost 2 multiply_total');
has('attribute: the uuid change is explained', await warns('attribute'), 'replaced by a single id');
has('attribute: uuid format is flagged', await warns('attribute'), 'must be a UUID');
await setVersion('26.2');

await page.fill('#at-mod-id', 'my_boost');
await page.waitForTimeout(T);
has('attribute: missing namespace noted', await warns('attribute'), 'minecraft:my_boost');
await page.fill('#at-mod-id', '');
await page.waitForTimeout(T);
check('attribute: modifier without an id produces nothing', await cmd('attribute'), '');
has('attribute: modifier id required', await warns('attribute'), 'needs an id');
await page.fill('#at-mod-id', 'minecraft:my_boost');
await page.waitForTimeout(T);

await page.click('[data-at-op="modifier-remove"]');
await page.waitForTimeout(T);
check('attribute: modifier remove', await cmd('attribute'),
  '/attribute @s movement_speed modifier remove minecraft:my_boost');

await page.click('[data-at-op="modifier-get"]');
await page.waitForTimeout(T);
check('attribute: modifier value get', await cmd('attribute'),
  '/attribute @s movement_speed modifier value get minecraft:my_boost');

// ── TARGET VALIDATION ───────────────────────────
await page.click('[data-at-op="get"]');
await page.fill('#at-target', '@a');
await page.waitForTimeout(T);
has('attribute: group selector flagged', await warns('attribute'), 'needs exactly one');
await page.fill('#at-target', '');
await page.waitForTimeout(T);
check('attribute: no target produces nothing', await cmd('attribute'), '');
await page.fill('#at-target', '@s');
await page.waitForTimeout(T);

// ── BEDROCK ─────────────────────────────────────
await setVersion('bedrock');
check('attribute: bedrock produces nothing', await cmd('attribute'), '');
has('attribute: bedrock refused honestly', await warns('attribute'), 'Bedrock has no /attribute command');
await setVersion('26.2');

// version-filtered attribute list
const has1204 = await page.evaluate(async () => {
  MC.setVersion('1.20.4');
  await new Promise(r => setTimeout(r, 120));
  const found = Array.from(document.querySelectorAll('#at-attr option')).some(o => o.value === 'scale');
  MC.setVersion('26.2');
  await new Promise(r => setTimeout(r, 120));
  return found;
});
check('attribute: 1.20.5-only attributes hidden on 1.20.4', has1204, false);

/* ═══════════ DATA ═══════════ */
await page.goto(`${BASE}/commands.php?t=data`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-data [data-cmd]').textContent);

check('data: default get', await cmd('data'), '/data get entity @s Health');

// path presets
await page.click('[data-dt-path="Inventory"]');
await page.waitForTimeout(T);
check('data: path preset applies', await cmd('data'), '/data get entity @s Inventory');
await page.fill('#dt-path', 'Health');
await page.waitForTimeout(T);

// scale
await page.evaluate(() => { document.querySelector('[data-panel="data"] details.adv').open = true; });
await page.fill('#dt-scale', '2');
await page.waitForTimeout(T);
check('data: get with scale', await cmd('data'), '/data get entity @s Health 2');
await page.fill('#dt-path', '');
await page.waitForTimeout(T);
has('data: scale needs a path', await warns('data'), 'needs a path pointing at one');
has('data: no path prints everything', await warns('data'), 'prints everything on the target');
check('data: get with no path', await cmd('data'), '/data get entity @s');
await page.fill('#dt-path', 'Health'); await page.fill('#dt-scale', '1');
await page.waitForTimeout(T);

// targets
await page.click('[data-dt-target="block"]');
await page.waitForTimeout(T);
check('data: block target', await cmd('data'), '/data get block ~ ~ ~ Health');
await page.fill('#dt-x', '^2');
await page.waitForTimeout(T);
has('data: mixed coordinates rejected', await warns('data'), 'cannot be mixed');
await page.fill('#dt-x', '~');
await page.click('[data-dt-target="storage"]');
await page.waitForTimeout(T);
check('data: storage target', await cmd('data'), '/data get storage minecraft:my_data Health');
await page.fill('#dt-storage', 'counters');
await page.waitForTimeout(T);
has('data: storage namespace noted', await warns('data'), 'minecraft:counters');
await page.click('[data-dt-target="entity"]');
await page.waitForTimeout(T);

// modify
await page.click('[data-dt-op="modify"]');
await page.waitForTimeout(T);
check('data: modify set value', await cmd('data'), '/data modify entity @s Health set value 20.0f');
await page.fill('#dt-value', '20.0');
await page.waitForTimeout(T);
has('data: number typing explained', await warns('data'), 'is a double');
await page.fill('#dt-value', '20.0f');
await page.waitForTimeout(T);

await page.selectOption('#dt-mode', 'append');
await page.waitForTimeout(T);
check('data: append', await cmd('data'), '/data modify entity @s Health append value 20.0f');
has('data: list-only modes explained', await warns('data'), 'points at a list');

await page.selectOption('#dt-mode', 'insert');
await page.waitForTimeout(T);
check('data: insert takes an index', await cmd('data'), '/data modify entity @s Health insert 0 value 20.0f');
await page.fill('#dt-index', '2');
await page.waitForTimeout(T);
check('data: insert at index 2', await cmd('data'), '/data modify entity @s Health insert 2 value 20.0f');
await page.selectOption('#dt-mode', 'set');
await page.waitForTimeout(T);

// modify from
await page.click('[data-dt-source="from"]');
await page.waitForTimeout(T);
check('data: modify from entity', await cmd('data'), '/data modify entity @s Health set from entity @p Health');
await page.selectOption('#dt-from-type', 'storage');
await page.fill('#dt-from-target', 'mypack:counters');
await page.fill('#dt-from-path', 'score');
await page.waitForTimeout(T);
check('data: modify from storage', await cmd('data'), '/data modify entity @s Health set from storage mypack:counters score');
await page.fill('#dt-from-target', '');
await page.waitForTimeout(T);
check('data: from with no source produces nothing', await cmd('data'), '');
has('data: from source required', await warns('data'), 'copied from');
await page.click('[data-dt-source="value"]');
await page.waitForTimeout(T);

// merge
await page.click('[data-dt-op="merge"]');
await page.waitForTimeout(T);
check('data: merge', await cmd('data'), '/data merge entity @s {Invulnerable:1b}');
has('data: merge only changes listed keys', await warns('data'), 'only changes the keys you list');
await page.fill('#dt-merge', 'Invulnerable:1b');
await page.waitForTimeout(T);
has('data: merge needs a compound', await warns('data'), 'start with { and end with }');
await page.fill('#dt-merge', '');
await page.waitForTimeout(T);
check('data: merge with nothing produces nothing', await cmd('data'), '');

// remove
await page.click('[data-dt-op="remove"]');
await page.waitForTimeout(T);
check('data: remove', await cmd('data'), '/data remove entity @s Health');
has('data: remove warns', await warns('data'), 'deletes that data outright');
await page.fill('#dt-path', '');
await page.waitForTimeout(T);
check('data: remove needs a path', await cmd('data'), '');
await page.fill('#dt-path', 'Health');
await page.waitForTimeout(T);

// group selector
await page.click('[data-dt-op="get"]');
await page.fill('#dt-entity', '@a');
await page.waitForTimeout(T);
has('data: group selector flagged', await warns('data'), 'needs exactly one');
await page.fill('#dt-entity', '@s');
await page.waitForTimeout(T);

/* ═══════════ DOCTOR AGREES ═══════════ */
for (const [task, id] of [['attribute', 'attribute'], ['data', 'data']]) {
  await page.goto(`${BASE}/commands.php?t=${task}`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(i => window.MC && document.querySelector('#out-' + i + ' [data-cmd]').textContent, id);
  const verdict = await page.evaluate(async c => {
    const r = await MC.api('doctor', { command: c, version: '26.2' });
    return r.result.problems.filter(p => p.level === 'error').map(p => p.title);
  }, await cmd(id));
  check(`${task}: default output passes the Doctor`, verdict.join(' | '), '');
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
