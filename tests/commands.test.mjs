// Browser tests for the command builders.
// Run with:  node tests/commands.test.mjs  (a PHP server must be on :8899)
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];

function check(name, actual, expected) {
  const ok = actual === expected;
  if (ok) { pass++; }
  else { fail++; failures.push(`${name}\n    expected: ${expected}\n    actual:   ${actual}`); }
}

function checkIncludes(name, haystack, needle) {
  const ok = String(haystack).includes(needle);
  if (ok) { pass++; }
  else { fail++; failures.push(`${name}\n    expected to contain: ${needle}\n    actual: ${haystack}`); }
}

const browser = await chromium.launch();
const page = await browser.newPage();
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

async function setVersion(v) {
  await page.evaluate(v => MC.setVersion(v), v);
  await page.waitForTimeout(40);
}
const cmd = async (id) => (await page.textContent(`#${id} [data-cmd]`)).trim();
const warns = async (id) => (await page.textContent(`#${id} [data-warn]`)).trim();

// ── GIVE ────────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=give`);
await page.waitForFunction(() => window.MC && document.querySelector('#out-give [data-cmd]').textContent);
check('give: default', await cmd('out-give'), '/give @p diamond_sword');

await page.fill('#give-count', '5');
await page.fill('#give-target', '@a');
await page.waitForTimeout(30);
check('give: target + count', await cmd('out-give'), '/give @a diamond_sword 5');

// enchantment + custom name across all three syntax eras
await page.evaluate(() => document.querySelector('[data-panel="give"] details.adv').open = true);
await page.fill('#give-name', 'Excalibur');
await page.selectOption('#give-name-color', 'gold');
await page.evaluate(() => addEnch());
await page.selectOption('#give-ench .rowitem select', 'sharpness');
await page.fill('#give-ench .rowitem input[type=number]', '5');
await page.waitForTimeout(40);

await setVersion('26.2');
check('give 26.2: SNBT components',
  await cmd('out-give'),
  '/give @a diamond_sword[custom_name={text:"Excalibur",color:"gold",italic:false},enchantments={sharpness:5}] 5');

await setVersion('1.21.4');
check('give 1.21.4: JSON-string components + levels wrapper',
  await cmd('out-give'),
  '/give @a diamond_sword[custom_name=\'{"text":"Excalibur","color":"gold","italic":false}\',enchantments={levels:{sharpness:5}}] 5');

await setVersion('1.20.4');
check('give 1.20.4: legacy NBT',
  await cmd('out-give'),
  '/give @a diamond_sword{display:{Name:\'{"text":"Excalibur","color":"gold","italic":false}\'},Enchantments:[{id:"minecraft:sharpness",lvl:5s}]} 5');

await setVersion('bedrock');
checkIncludes('give bedrock: warns about unsupported components', await warns('out-give'), 'Bedrock /give cannot set custom names');
check('give bedrock: plain item', await cmd('out-give'), '/give @a diamond_sword 5');

await setVersion('26.2');
await page.fill('#give-ench .rowitem input[type=number]', '10');
await page.waitForTimeout(30);
checkIncludes('give: over-max enchant warning', await warns('out-give'), 'above the normal maximum of 5');

// ── EFFECT ──────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=effect`);
await page.waitForFunction(() => document.querySelector('#out-effect [data-cmd]').textContent);
check('effect: default', await cmd('out-effect'), '/effect give @p speed');

await page.fill('#eff-dur', '60');
await page.fill('#eff-level', '3');
await page.waitForTimeout(30);
check('effect: level 3 becomes amplifier 2', await cmd('out-effect'), '/effect give @p speed 60 2');
checkIncludes('effect: explains the zero-based amplifier', await warns('out-effect'), 'amplifier 2');

await page.check('#eff-infinite');
await page.waitForTimeout(30);
check('effect: infinite', await cmd('out-effect'), '/effect give @p speed infinite 2');

await page.check('#eff-hide');
await page.waitForTimeout(30);
check('effect: hide particles', await cmd('out-effect'), '/effect give @p speed infinite 2 true');

await page.click('[data-eff-mode="clear"]');
await page.waitForTimeout(30);
check('effect: clear', await cmd('out-effect'), '/effect clear @p speed');

// ── TELEPORT ────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=teleport`);
await page.waitForFunction(() => document.querySelector('#out-teleport [data-cmd]').textContent);
check('tp: default relative', await cmd('out-teleport'), '/tp @s ~ ~ ~');

await page.evaluate(() => tpPreset('~', '~10', '~'));
check('tp: preset up', await cmd('out-teleport'), '/tp @s ~ ~10 ~');

await page.fill('#tp-x', '^2');
await page.waitForTimeout(30);
checkIncludes('tp: mixed local/relative is rejected', await warns('out-teleport'), 'cannot be mixed');

await page.fill('#tp-x', '100'); await page.fill('#tp-y', '-100'); await page.fill('#tp-z', '20');
await page.waitForTimeout(30);
checkIncludes('tp: below world floor warning', await warns('out-teleport'), 'under the world floor');

await page.fill('#tp-y', '64');
await page.evaluate(() => document.querySelector('[data-panel="teleport"] details.adv').open = true);
await page.fill('#tp-facing', '0 64 0');
await page.waitForTimeout(30);
check('tp: facing', await cmd('out-teleport'), '/teleport @s 100 64 20 facing 0 64 0');

// ── KILL ────────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=kill`);
await page.waitForFunction(() => document.querySelector('#out-kill [data-cmd]').textContent);
check('kill: default items', await cmd('out-kill'), '/kill @e[type=item]');
await page.fill('#kill-target', '@e');
await page.waitForTimeout(30);
checkIncludes('kill: bare @e is flagged', await warns('out-kill'), 'every entity in loaded chunks');

// ── CLEAR ───────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=clear`);
await page.waitForFunction(() => document.querySelector('#out-clear [data-cmd]').textContent);
check('clear: default', await cmd('out-clear'), '/clear @p');
checkIncludes('clear: warns about wiping everything', await warns('out-clear'), 'empties the entire inventory');
await page.fill('#clr-item', 'dirt');
await page.fill('#clr-max', '10');
await page.waitForTimeout(30);
check('clear: item + max', await cmd('out-clear'), '/clear @p dirt 10');

// ── GAMERULE ────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=gamerule`);
await page.waitForFunction(() => document.querySelector('#out-gamerule [data-cmd]').textContent);
check('gamerule: keepInventory default off', await cmd('out-gamerule'), '/gamerule keepInventory false');
await page.check('#rule-bool');
await page.waitForTimeout(30);
check('gamerule: toggled on', await cmd('out-gamerule'), '/gamerule keepInventory true');

await page.selectOption('#rule-select', 'randomTickSpeed');
await page.waitForTimeout(30);
check('gamerule: int rule uses its default', await cmd('out-gamerule'), '/gamerule randomTickSpeed 3');
await page.fill('#rule-int', '5000');
await page.waitForTimeout(30);
checkIncludes('gamerule: extreme tick speed warning', await warns('out-gamerule'), 'lag or crash');

// version filtering of the rule list
await setVersion('1.19.4');
const has1194 = await page.evaluate(() =>
  Array.from(document.querySelectorAll('#rule-select option')).some(o => o.value === 'spawnChunkRadius'));
check('gamerule: 1.20.5-only rule hidden on 1.19.4', has1194, false);
await setVersion('26.2');
const has262 = await page.evaluate(() =>
  Array.from(document.querySelectorAll('#rule-select option')).some(o => o.value === 'spawnChunkRadius'));
check('gamerule: 1.20.5-only rule shown on 26.2', has262, true);

// ── SUMMON ──────────────────────────────────────
await page.goto(`${BASE}/commands.php?t=summon`);
await page.waitForFunction(() => document.querySelector('#out-summon [data-cmd]').textContent);
check('summon: default', await cmd('out-summon'), '/summon zombie ~ ~ ~');
await page.evaluate(() => document.querySelector('[data-panel="summon"] details.adv').open = true);
await page.fill('#sum-name', 'Bob');
await page.check('#sum-noai');
await page.waitForTimeout(30);
await setVersion('26.2');
check('summon 26.2: SNBT CustomName', await cmd('out-summon'), '/summon zombie ~ ~ ~ {CustomName:{text:"Bob"},NoAI:1b}');
await setVersion('1.20.4');
check('summon 1.20.4: JSON-string CustomName', await cmd('out-summon'), '/summon zombie ~ ~ ~ {CustomName:\'{"text":"Bob"}\',NoAI:1b}');

// ── EXPERIENCE / GAMEMODE / TIME / LOCATE ───────
await page.goto(`${BASE}/commands.php?t=experience`);
await page.waitForFunction(() => document.querySelector('#out-experience [data-cmd]').textContent);
check('xp: add levels', await cmd('out-experience'), '/experience add @p 30 levels');
await page.click('[data-xp-op="query"]');
await page.waitForTimeout(30);
check('xp: query', await cmd('out-experience'), '/experience query @p levels');

await page.goto(`${BASE}/commands.php?t=gamemode`);
await page.waitForFunction(() => document.querySelector('#out-gamemode [data-cmd]').textContent);
check('gamemode: default', await cmd('out-gamemode'), '/gamemode survival @s');
await page.click('[data-gm="creative"]');
await page.fill('#gm-target', '@a');
await page.waitForTimeout(30);
check('gamemode: everyone creative', await cmd('out-gamemode'), '/gamemode creative @a');

await page.goto(`${BASE}/commands.php?t=time`);
await page.waitForFunction(() => document.querySelector('#out-time [data-cmd]').textContent);
check('time: default day', await cmd('out-time'), '/time set 1000');
check('weather: default clear', await cmd('out-weather'), '/weather clear');
await page.click('[data-weather="thunder"]');
await page.fill('#weather-dur', '600');
await page.waitForTimeout(30);
check('weather: thunder with duration', await cmd('out-weather'), '/weather thunder 600');

await page.goto(`${BASE}/commands.php?t=locate`);
await page.waitForFunction(() => document.querySelector('#out-locate [data-cmd]').textContent);
check('locate: structure', await cmd('out-locate'), '/locate structure stronghold');
await page.click('[data-locate="biome"]');
await page.waitForTimeout(30);
check('locate: biome', await cmd('out-locate'), '/locate biome deep_dark');

await page.goto(`${BASE}/commands.php?t=difficulty`);
await page.waitForFunction(() => document.querySelector('#out-difficulty [data-cmd]').textContent);
check('difficulty: default', await cmd('out-difficulty'), '/difficulty normal');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) {
  console.log('\nFailures:');
  failures.forEach(f => console.log('  ✗ ' + f));
  process.exit(1);
}
