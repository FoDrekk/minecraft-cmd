// Browser tests for the build tools.
import { chromium } from 'playwright';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));

const browser = await chromium.launch();
const page = await browser.newPage();
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

const cmd = async id => (await page.textContent(`#${id} [data-cmd]`)).trim();
const warns = async id => (await page.textContent(`#${id} [data-warn]`)).trim();
const text = async sel => (await page.textContent(sel)).replace(/\s+/g, ' ').trim();
const setRegion = async (p, f, t) => {
  for (const [i, ax] of ['x', 'y', 'z'].entries()) {
    await page.fill(`#${p}-${ax}1`, f[i]);
    await page.fill(`#${p}-${ax}2`, t[i]);
  }
  await page.waitForTimeout(40);
};

// ── FILL ────────────────────────────────────────
await page.goto(`${BASE}/build.php?t=fill`);
await page.waitForFunction(() => window.MC && document.querySelector('#out-fill [data-cmd]').textContent);
check('fill: default', await cmd('out-fill'), '/fill ~ ~ ~ ~10 ~5 ~10 stone_bricks');
has('fill: shows dimensions', await text('#fill-volume'), '11 × 6 × 11');
has('fill: shows block count', await text('#fill-volume'), '726 blocks');

await page.selectOption('#fill-mode', 'hollow');
await page.waitForTimeout(40);
check('fill: hollow mode', await cmd('out-fill'), '/fill ~ ~ ~ ~10 ~5 ~10 stone_bricks hollow');
has('fill: hollow counts only the shell', await text('#fill-volume'), '402 blocks affected of 726');

await page.selectOption('#fill-mode', 'replace');
await page.check('#fill-usefilter');
await page.selectOption('#fill-filter', 'grass_block');
await page.waitForTimeout(40);
check('fill: replace with filter', await cmd('out-fill'), '/fill ~ ~ ~ ~10 ~5 ~10 stone_bricks replace grass_block');

// block limit
await page.uncheck('#fill-usefilter');
await setRegion('fill', ['0', '0', '0'], ['100', '50', '100']);
has('fill: over the block limit is an error', await warns('out-fill'), 'over the 32,768-block limit');

// unknown size when coordinate forms are mixed
await setRegion('fill', ['~', '~', '~'], ['50', '~5', '~10']);
has('fill: mixed coordinate forms report unknown', await text('#fill-volume'), 'Size unknown');

// block state
await setRegion('fill', ['~', '~', '~'], ['~3', '~', '~3']);
await page.selectOption('#fill-block', 'oak_planks');
await page.fill('#fill-state', 'axis=y');
await page.waitForTimeout(40);
check('fill: block state', await cmd('out-fill'), '/fill ~ ~ ~ ~3 ~ ~3 oak_planks[axis=y]');
has('fill: flags a one-block-tall region', await warns('out-fill'), 'One block tall');

// ── CLEAR AREA ──────────────────────────────────
await page.goto(`${BASE}/build.php?t=clear`);
await page.waitForFunction(() => document.querySelector('#out-clear [data-cmd]').textContent);
check('clear: everything', await cmd('out-clear'), '/fill ~ ~ ~ ~10 ~5 ~10 air');
await page.click('[data-clear="trees"]');
await page.waitForTimeout(40);
check('clear: trees uses tags, leaves first',
  await cmd('out-clear'),
  '/fill ~ ~ ~ ~10 ~5 ~10 air replace #minecraft:leaves\n/fill ~ ~ ~ ~10 ~5 ~10 air replace #minecraft:logs');
has('clear: explains the ordering', await warns('out-clear'), 'leaves one first');

await page.evaluate(() => MC.setVersion('bedrock'));
await page.waitForTimeout(60);
has('clear: bedrock cannot use block tags', await warns('out-clear'), 'Bedrock does not support #block tags');
await page.evaluate(() => MC.setVersion('26.2'));
await page.waitForTimeout(60);

await page.click('[data-clear="water"]');
await page.waitForTimeout(40);
has('clear: water and lava', await cmd('out-clear'), 'air replace water');

// ── REPLACE ─────────────────────────────────────
await page.goto(`${BASE}/build.php?t=replace`);
await page.waitForFunction(() => document.querySelector('#out-replace [data-cmd]').textContent);
check('replace: default', await cmd('out-replace'), '/fill ~ ~ ~ ~10 ~5 ~10 stone_bricks replace cobblestone');
await page.selectOption('#rep-to', 'cobblestone');
await page.waitForTimeout(40);
has('replace: same block is a no-op', await warns('out-replace'), 'nothing will change');

// ── SET BLOCK ───────────────────────────────────
await page.goto(`${BASE}/build.php?t=setblock`);
await page.waitForFunction(() => document.querySelector('#out-setblock [data-cmd]').textContent);
check('setblock: default', await cmd('out-setblock'), '/setblock ~ ~ ~ stone');
has('setblock: explains ~ ~ ~', await warns('out-setblock'), 'block you are standing in');
await page.fill('#sb-state', 'facing=north,half=top');
await page.selectOption('#sb-block', 'oak_planks');
await page.selectOption('#sb-mode', 'keep');
await page.waitForTimeout(40);
check('setblock: state and mode', await cmd('out-setblock'), '/setblock ~ ~ ~ oak_planks[facing=north,half=top] keep');
await page.fill('#sb-state', '{Text1:"hi"}');
await page.waitForTimeout(40);
has('setblock: braces in a block state are rejected', await warns('out-setblock'), 'square brackets');

// ── CLONE ───────────────────────────────────────
await page.goto(`${BASE}/build.php?t=clone`);
await page.waitForFunction(() => document.querySelector('#out-clone [data-cmd]').textContent);
check('clone: default', await cmd('out-clone'), '/clone ~ ~ ~ ~10 ~5 ~10 ~20 ~ ~');
has('clone: plain-language explanation', await text('#clone-explain'), 'lowest north-west corner');
await page.selectOption('#cln-mask', 'masked');
await page.waitForTimeout(40);
check('clone: masked', await cmd('out-clone'), '/clone ~ ~ ~ ~10 ~5 ~10 ~20 ~ ~ masked');
await page.selectOption('#cln-mask', 'filtered');
await page.selectOption('#cln-filter', 'stone_bricks');
await page.selectOption('#cln-mode', 'move');
await page.waitForTimeout(40);
check('clone: filtered + move', await cmd('out-clone'), '/clone ~ ~ ~ ~10 ~5 ~10 ~20 ~ ~ filtered stone_bricks move');
has('clone: move warns the original is cleared', await warns('out-clone'), 'empties the original region');

// ── AREA CALCULATOR ─────────────────────────────
await page.goto(`${BASE}/build.php?t=area`);
await page.waitForFunction(() => document.querySelector('#area-results').textContent);
// The worked example from the brief: 19 × 19 × 5 = 1,805
await setRegion('area', ['0', '64', '0'], ['18', '68', '18']);
has('area: dimensions', await text('#area-results'), '19 × 5 × 19');
has('area: total blocks', await text('#area-results'), '1,805');
has('area: floor area', await text('#area-results'), '361');
has('area: stacks', await text('#area-results'), '29');

// ── COORDINATE HELPER ───────────────────────────
await page.goto(`${BASE}/build.php?t=coords`);
await page.waitForFunction(() => document.querySelector('#co-results').textContent);
has('coords: size', await text('#co-results'), '19 × 9 × 19');
has('coords: centre', await text('#co-results'), '109 68 -11');
has('coords: direction', await text('#co-results'), 'south-east');
has('coords: relative conversion', await text('#co-rel-results'), '~18 ~8 ~18');
has('coords: ready-made teleport', await text('#co-rel-results'), '/tp @s 118 72 -2');

// ── BUILD PLANNER ───────────────────────────────
await page.goto(`${BASE}/build.php?t=planner`);
await page.waitForFunction(() => document.querySelector('#pl-results').textContent);
has('planner: footprint', await text('#pl-results'), '11 × 9');
has('planner: floor area', await text('#pl-results'), '99');
has('planner: site to clear', await text('#pl-results'), '17 × 15');
// walls: perimeter (2*(11+9)-4 = 36) × 4 layers = 144
has('planner: wall count', await text('#pl-materials'), '144');
has('planner: suggests a palette', await text('#pl-palette'), 'Lighting');

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
