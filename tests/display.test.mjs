// /playsound, /team and /bossbar builders.
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
const setVersion = async x => { await page.evaluate(y => MC.setVersion(y), x); await page.waitForTimeout(80); };
const T = 70;

/* ═══════════ PLAYSOUND ═══════════ */
await page.goto(`${BASE}/commands.php?t=playsound`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-playsound [data-cmd]').textContent);

check('playsound: default is the short form', await cmd('playsound'),
  '/playsound block.note_block.pling master @a');

await page.fill('#ps-search', 'thunder');
await page.waitForTimeout(T);
await page.click('[data-sound="entity.lightning_bolt.thunder"]');
await page.waitForTimeout(T);
check('playsound: sound selection', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder master @a');
has('playsound: shows the chosen id', await page.textContent('#ps-picked'), 'minecraft:entity.lightning_bolt.thunder');

await page.selectOption('#ps-source', 'weather');
await page.fill('#ps-target', '@p');
await page.waitForTimeout(T);
check('playsound: category and target', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder weather @p');

// position
await page.evaluate(() => { document.querySelector('[data-panel="playsound"] details.adv').open = true; });
await page.check('#ps-usepos');
await page.waitForTimeout(T);
check('playsound: with position', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder weather @p ~ ~ ~');
await page.fill('#ps-x', '100'); await page.fill('#ps-y', '64'); await page.fill('#ps-z', '-20');
await page.waitForTimeout(T);
check('playsound: absolute position', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder weather @p 100 64 -20');

// volume / pitch / min
await page.fill('#ps-volume', '2'); await page.fill('#ps-pitch', '1.5');
await page.waitForTimeout(T);
check('playsound: volume and pitch', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder weather @p 100 64 -20 2 1.5');
has('playsound: volume is range not loudness', await warns('playsound'), 'widens how far it carries');
await page.fill('#ps-min', '0.4');
await page.waitForTimeout(T);
check('playsound: minimum volume', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder weather @p 100 64 -20 2 1.5 0.4');

await page.fill('#ps-pitch', '5');
await page.waitForTimeout(T);
has('playsound: out-of-range pitch is explained', await warns('playsound'), 'clamped by the game');
await page.fill('#ps-pitch', '1'); await page.fill('#ps-volume', '1'); await page.fill('#ps-min', '0');
await page.uncheck('#ps-usepos');
await page.waitForTimeout(T);

// tail without an explicit position fills one in
await page.fill('#ps-volume', '0.5');
await page.waitForTimeout(T);
has('playsound: tail needs a position', await warns('playsound'), 'only be given after a position');
// The coordinate fields keep their last value, so the note names it rather
// than claiming ~ ~ ~ was used.
check('playsound: position filled in for the tail', await cmd('playsound'),
  '/playsound entity.lightning_bolt.thunder weather @p 100 64 -20 0.5 1');
has('playsound: the note names the position used', await warns('playsound'), '100 64 -20 has been filled in');
await page.fill('#ps-volume', '1');
await page.waitForTimeout(T);

// invalid input
await page.fill('#ps-target', '');
await page.waitForTimeout(T);
check('playsound: no target produces no command', await cmd('playsound'), '');
has('playsound: no target is explained', await warns('playsound'), 'needs a target');
await page.fill('#ps-target', '@a');
await page.fill('#ps-custom', 'My Sound!');
await page.waitForTimeout(T);
has('playsound: invalid custom id rejected', await warns('playsound'), 'lower-case letters');
await page.fill('#ps-custom', 'entity.villager.trade');
await page.waitForTimeout(T);
check('playsound: custom id overrides the picker', await cmd('playsound'),
  '/playsound entity.villager.trade weather @a');
await page.fill('#ps-custom', '');
await page.waitForTimeout(T);

/* ═══════════ TEAM ═══════════ */
await page.goto(`${BASE}/commands.php?t=team`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-team [data-cmd]').textContent);

check('team: create', await cmd('team'), '/team add red');
has('team: create explains the next step', await warns('team'), 'does not put anyone in it');
await page.fill('#tm-display', 'Red Team');
await page.waitForTimeout(T);
check('team: create with display name', await cmd('team'), '/team add red {"text":"Red Team"}');

await page.fill('#tm-name', 'red team');
await page.waitForTimeout(T);
has('team: spaces in an id are rejected', await warns('team'), 'cannot contain spaces');
await page.fill('#tm-name', 'red');
await page.fill('#tm-display', '');
await page.waitForTimeout(T);

await page.click('[data-team-op="join"]');
await page.waitForTimeout(T);
check('team: join', await cmd('team'), '/team join red @s');
await page.fill('#tm-members', '');
await page.waitForTimeout(T);
has('team: join needs players', await warns('team'), 'Name the players to add');
await page.fill('#tm-members', '@a');
await page.waitForTimeout(T);

await page.click('[data-team-op="leave"]');
await page.waitForTimeout(T);
check('team: leave takes no team id', await cmd('team'), '/team leave @a');

await page.click('[data-team-op="empty"]');
await page.waitForTimeout(T);
check('team: empty', await cmd('team'), '/team empty red');

await page.click('[data-team-op="remove"]');
await page.waitForTimeout(T);
check('team: delete', await cmd('team'), '/team remove red');
has('team: delete warns', await warns('team'), 'no undo');

await page.click('[data-team-op="list"]');
await page.waitForTimeout(T);
check('team: list a team', await cmd('team'), '/team list red');

await page.click('[data-team-op="modify"]');
await page.waitForTimeout(T);
check('team: modify defaults to display name', await cmd('team'), '');
has('team: modify needs text', await warns('team'), 'Enter the text');
await page.fill('#tm-value-text', 'The Reds');
await page.waitForTimeout(T);
check('team: modify display name', await cmd('team'), '/team modify red displayName {"text":"The Reds"}');

await page.selectOption('#tm-option', 'color');
await page.waitForTimeout(T);
await page.selectOption('#tm-value-select', 'red');
await page.waitForTimeout(T);
check('team: modify colour', await cmd('team'), '/team modify red color red');
has('team: colour affects glow', await warns('team'), 'glow');

await page.selectOption('#tm-option', 'friendlyFire');
await page.waitForTimeout(T);
check('team: bool option uses its default', await cmd('team'), '/team modify red friendlyFire true');
await page.uncheck('#tm-value-bool');
await page.waitForTimeout(T);
check('team: bool option toggled off', await cmd('team'), '/team modify red friendlyFire false');
has('team: friendly fire caveat', await warns('team'), 'splash and explosion damage');

await page.selectOption('#tm-option', 'nametagVisibility');
await page.waitForTimeout(T);
await page.selectOption('#tm-value-select', 'hideForOtherTeams');
await page.waitForTimeout(T);
check('team: nametag visibility', await cmd('team'), '/team modify red nametagVisibility hideForOtherTeams');

await page.selectOption('#tm-option', 'collisionRule');
await page.waitForTimeout(T);
await page.selectOption('#tm-value-select', 'never');
await page.waitForTimeout(T);
check('team: collision rule', await cmd('team'), '/team modify red collisionRule never');

/* ═══════════ BOSSBAR ═══════════ */
await page.goto(`${BASE}/commands.php?t=bossbar`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-bossbar [data-cmd]').textContent);

check('bossbar: create', await cmd('bossbar'), '/bossbar add minecraft:timer {"text":"Time left"}');
has('bossbar: create explains defaults', await warns('bossbar'), 'starts hidden');

await page.fill('#bb-id', 'timer');
await page.waitForTimeout(T);
has('bossbar: missing namespace is noted', await warns('bossbar'), 'minecraft:timer');
await page.fill('#bb-id', 'my timer');
await page.waitForTimeout(T);
has('bossbar: spaces rejected', await warns('bossbar'), 'cannot contain spaces');
await page.fill('#bb-id', 'minecraft:timer');
await page.fill('#bb-title', '');
await page.waitForTimeout(T);
check('bossbar: create needs a title', await cmd('bossbar'), '');
has('bossbar: title required', await warns('bossbar'), 'needs a title');
await page.fill('#bb-title', 'Time left');
await page.waitForTimeout(T);

await page.click('[data-bb-op="set"]');
await page.waitForTimeout(T);
check('bossbar: set value', await cmd('bossbar'), '/bossbar set minecraft:timer value 30');
await page.fill('#bb-value', '90');
await page.waitForTimeout(T);
has('bossbar: value above max explained', await warns('bossbar'), 'show as full');
await page.fill('#bb-value', '30');
await page.waitForTimeout(T);

await page.selectOption('#bb-prop', 'max');
await page.waitForTimeout(T);
check('bossbar: set max', await cmd('bossbar'), '/bossbar set minecraft:timer max 60');

await page.selectOption('#bb-prop', 'color');
await page.selectOption('#bb-color', 'red');
await page.waitForTimeout(T);
check('bossbar: set colour', await cmd('bossbar'), '/bossbar set minecraft:timer color red');

await page.selectOption('#bb-prop', 'style');
await page.selectOption('#bb-style', 'notched_10');
await page.waitForTimeout(T);
check('bossbar: set style', await cmd('bossbar'), '/bossbar set minecraft:timer style notched_10');

await page.selectOption('#bb-prop', 'name');
await page.waitForTimeout(T);
check('bossbar: set title', await cmd('bossbar'), '/bossbar set minecraft:timer name {"text":"Time left"}');

await page.selectOption('#bb-prop', 'visible');
await page.selectOption('#bb-visible', 'false');
await page.waitForTimeout(T);
check('bossbar: set visible', await cmd('bossbar'), '/bossbar set minecraft:timer visible false');

await page.selectOption('#bb-prop', 'players');
await page.waitForTimeout(T);
check('bossbar: set players', await cmd('bossbar'), '/bossbar set minecraft:timer players @a');
await page.fill('#bb-players', '');
await page.waitForTimeout(T);
check('bossbar: no players hides it', await cmd('bossbar'), '/bossbar set minecraft:timer players');
has('bossbar: empty players explained', await warns('bossbar'), 'hidden from everyone');

await page.click('[data-bb-op="get"]');
await page.waitForTimeout(T);
check('bossbar: get value', await cmd('bossbar'), '/bossbar get minecraft:timer value');
await page.selectOption('#bb-get', 'players');
await page.waitForTimeout(T);
check('bossbar: get players', await cmd('bossbar'), '/bossbar get minecraft:timer players');

await page.click('[data-bb-op="remove"]');
await page.waitForTimeout(T);
check('bossbar: delete', await cmd('bossbar'), '/bossbar remove minecraft:timer');

await page.click('[data-bb-op="list"]');
await page.waitForTimeout(T);
check('bossbar: list', await cmd('bossbar'), '/bossbar list');

// preview reflects the settings
await page.click('[data-bb-op="set"]');
await page.selectOption('#bb-prop', 'value');
await page.fill('#bb-value', '30'); await page.fill('#bb-max', '60');
await page.selectOption('#bb-color', 'green');
await page.waitForTimeout(T);
has('bossbar: preview shows progress', await page.textContent('#bb-preview-meta'), '30 / 60');
has('bossbar: preview shows percentage', await page.textContent('#bb-preview-meta'), '50%');
const fillStyle = await page.getAttribute('#bb-preview-fill', 'style');
has('bossbar: preview width follows the value', fillStyle, 'width: 50');
has('bossbar: preview colour follows the choice', fillStyle.replace(/\s/g, ''), 'rgb(93,190,122)');

/* ═══════════ DOCTOR AGREES ═══════════ */
for (const [task, id] of [['playsound', 'playsound'], ['team', 'team'], ['bossbar', 'bossbar']]) {
  await page.goto(`${BASE}/commands.php?t=${task}`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(i => window.MC && document.querySelector('#out-' + i + ' [data-cmd]').textContent, id);
  const generated = await cmd(id);
  const verdict = await page.evaluate(async c => {
    const r = await MC.api('doctor', { command: c, version: '26.2' });
    return r.result.problems.filter(p => p.level === 'error').map(p => p.title);
  }, generated);
  check(`${task}: default output passes the Doctor`, verdict.join(' | '), '');
}

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
