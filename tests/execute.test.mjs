// /execute chain builder — chain assembly, ordering, validation and the
// plain-language summary (which comes from the Command Explainer).
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));
const hasNot = (n, h, s) => (!String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    should NOT contain: ${s}\n    actual: ${h}`)));

const browser = await chromium.launch();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

const cmd = async () => (await page.textContent('#out-execute [data-cmd]')).trim();
const warns = async () => (await page.textContent('#out-execute [data-warn]')).trim();
const T = 70;
const reset = async () => { await page.evaluate(() => { while (document.querySelector('.chain-btn.danger')) document.querySelector('.chain-btn.danger').click(); }); await page.waitForTimeout(T); };
const add = async t => { await page.click(`[data-add="${t}"]`); await page.waitForTimeout(T); };
// Links and arrows are sibling divs, so nth-of-type would count the arrows.
// Address links by position with a locator instead.
const link = i => page.locator('.chain-link').nth(i - 1);
const field = (i, idx) => link(i).locator('.chain-field').nth(idx - 1).locator('input');
const sel = (i, idx) => link(i).locator('.chain-field').nth(idx - 1).locator('select');

await page.goto(`${BASE}/commands.php?t=execute`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.querySelector('#out-execute [data-cmd]').textContent);

// ── DEFAULT WORKED EXAMPLE ──────────────────────
check('default chain matches the brief\'s example', await cmd(),
  '/execute as @a at @s if entity @e[type=zombie,distance=..10] run say Zombie nearby');
check('four links rendered', await page.$$eval('.chain-link', e => e.length), 4);
check('arrows between links', await page.$$eval('.chain-arrow', e => e.length), 3);

// summary comes from the Explainer
await page.waitForFunction(() => document.querySelector('#ex-summary .ex-summary-line'));
has('summary explains who', await page.textContent('#ex-summary'), 'as every player');
has('summary explains the condition', await page.textContent('#ex-summary'), 'at least one match');
has('summary explains the final command', await page.textContent('#ex-summary'), 'say Zombie nearby');

// ── EMPTY CHAIN ─────────────────────────────────
await reset();
check('empty chain produces nothing', await cmd(), '');
has('empty chain is explained', await warns(), 'Add a link to start the chain');
check('empty state shown', await page.isVisible('#ex-empty'), true);

// ── MINIMUM CHAIN ───────────────────────────────
await add('run');
check('run alone', await cmd(), '/execute run say Zombie nearby');
await field(1, 1).fill('say hi');
await page.waitForTimeout(T);
check('run with a custom command', await cmd(), '/execute run say hi');
await field(1, 1).fill('/say hi');
await page.waitForTimeout(T);
check('a leading slash on the run command is stripped', await cmd(), '/execute run say hi');
await field(1, 1).fill('');
await page.waitForTimeout(T);
has('run needs a command', await warns(), 'needs a command to run');

// ── VALIDATION: NO RUN, NO CONDITION ────────────
await reset();
await add('as');
check('context-only chain is refused', await cmd(), '/execute as @a');
has('context-only chain explains why', await warns(), 'never says what to do');

// ── CONDITION-ONLY IS VALID ─────────────────────
await reset();
await add('if_entity');
check('a bare condition is allowed', await cmd(), '/execute if entity @e[type=zombie,distance=..10]');
has('bare condition is explained', await warns(), 'only reports whether the condition matched');

// ── IF / UNLESS TOGGLE ──────────────────────────
await page.click('.chain-link .chain-btn');
await page.waitForTimeout(T);
check('toggles to unless', await cmd(), '/execute unless entity @e[type=zombie,distance=..10]');
await page.click('.chain-link .chain-btn');
await page.waitForTimeout(T);
check('toggles back to if', await cmd(), '/execute if entity @e[type=zombie,distance=..10]');

// ── ALL CONTEXT LINKS ───────────────────────────
await reset();
await add('positioned');
await field(1, 1).fill('100'); await field(1, 2).fill('64'); await field(1, 3).fill('-20');
await add('run');
await page.waitForTimeout(T);
check('positioned', await cmd(), '/execute positioned 100 64 -20 run say Zombie nearby');

await reset(); await add('positioned_as'); await add('run');
check('positioned as', await cmd(), '/execute positioned as @s run say Zombie nearby');

await reset(); await add('in'); await add('run');
check('in dimension', await cmd(), '/execute in the_nether run say Zombie nearby');
await sel(1, 1).selectOption('the_end');
await page.waitForTimeout(T);
check('in the_end', await cmd(), '/execute in the_end run say Zombie nearby');

await reset(); await add('rotated'); await add('run');
check('rotated', await cmd(), '/execute rotated ~ ~ run say Zombie nearby');

await reset(); await add('rotated_as'); await add('run');
check('rotated as', await cmd(), '/execute rotated as @s run say Zombie nearby');

await reset(); await add('facing'); await add('run');
check('facing coordinates', await cmd(), '/execute facing ~ ~ ~ run say Zombie nearby');

await reset(); await add('facing_entity'); await add('run');
check('facing entity', await cmd(), '/execute facing entity @p eyes run say Zombie nearby');
await sel(1, 2).selectOption('feet');
await page.waitForTimeout(T);
check('facing entity feet', await cmd(), '/execute facing entity @p feet run say Zombie nearby');

await reset(); await add('anchored'); await add('run');
check('anchored', await cmd(), '/execute anchored eyes run say Zombie nearby');

// ── CONDITIONS ──────────────────────────────────
await reset(); await add('if_block'); await add('run');
check('if block', await cmd(), '/execute if block ~ ~-1 ~ stone run say Zombie nearby');

await reset(); await add('if_score'); await add('run');
check('if score matches', await cmd(), '/execute if score @s points matches 10 run say Zombie nearby');
await sel(1, 3).selectOption('>=');
await field(1, 4).fill('@p total');
await page.waitForTimeout(T);
check('if score comparison', await cmd(), '/execute if score @s points >= @p total run say Zombie nearby');

// ── STORE ───────────────────────────────────────
await reset(); await add('store');
has('store without run is refused', await warns(), 'needs a run link too');
await add('run');
check('store result into a score', await cmd(), '/execute store result score @s points run say Zombie nearby');
await sel(1, 1).selectOption('success');
await page.waitForTimeout(T);
check('store success', await cmd(), '/execute store success score @s points run say Zombie nearby');
await sel(1, 2).selectOption('bossbar');
await field(1, 3).fill('minecraft:timer');
await field(1, 4).fill('value');
await page.waitForTimeout(T);
check('store into a bossbar', await cmd(), '/execute store success bossbar minecraft:timer value run say Zombie nearby');

// ── ORDERING ────────────────────────────────────
await reset();
await add('as'); await add('at'); await add('run');
check('as then at', await cmd(), '/execute as @a at @s run say Zombie nearby');
// move "at" above "as"
await link(2).locator('.chain-btn[title="Move up"]').click();
await page.waitForTimeout(T);
check('links can be reordered', await cmd(), '/execute at @s as @a run say Zombie nearby');
// run cannot be moved off the end
await link(3).locator('.chain-btn[title="Move up"]').click();
await page.waitForTimeout(T);
check('run stays last', await cmd(), '/execute at @s as @a run say Zombie nearby');
// adding a link after run still inserts before it
await add('anchored');
check('new links go before run', await cmd(), '/execute at @s as @a anchored eyes run say Zombie nearby');
// only one run
await add('run');
check('a second run is refused', await page.$$eval('.chain-link', e => e.length), 4);

// ── GUIDANCE ────────────────────────────────────
await reset();
await add('as'); await add('run');
has('as without at is flagged', await warns(), 'most common /execute mistake');
await add('at');
await page.waitForTimeout(T);
hasNot('adding at clears that note', await warns(), 'most common /execute mistake');

// condition ordering guidance
await reset();
await add('if_entity'); await add('at'); await add('run');
has('context after a condition is flagged', await warns(), 'read left to right');

// ── INVALID COORDINATES ─────────────────────────
await reset();
await add('positioned');
await field(1, 1).fill('^5');
await add('run');
await page.waitForTimeout(T);
has('mixed coordinates rejected', await warns(), 'cannot be mixed');

// ── MISSING FIELDS ──────────────────────────────
await reset();
await add('as');
await field(1, 1).fill('');
await add('run');
await page.waitForTimeout(T);
has('empty selector rejected', await warns(), 'needs a target selector');

// ── SUMMARY SUPPRESSED WHILE INVALID ────────────
has('summary is withheld while the chain is invalid', await page.textContent('#ex-summary'), 'Build a chain');

// ── DOCTOR AGREES ───────────────────────────────
await reset();
await add('as'); await add('at'); await add('if_entity'); await add('run');
const verdict = await page.evaluate(async c => {
  const r = await MC.api('doctor', { command: c, version: '26.2' });
  return r.result.problems.filter(p => p.level === 'error').map(p => p.title);
}, await cmd());
check('output passes the Doctor', verdict.join(' | '), '');

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
