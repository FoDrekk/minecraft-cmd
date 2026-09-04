// Tests for Command Doctor and the Explainer, driven through the real page.
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${String(h).slice(0, 300)}`)));
const hasNot = (n, h, s) => (!String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    should NOT contain: ${s}`)));

const browser = await chromium.launch();
const page = await browser.newPage();
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

async function doctor(cmd, version = '26.2') {
  return page.evaluate(async ([c, v]) => {
    const r = await MC.api('doctor', { command: c, version: v });
    return r.result;
  }, [cmd, version]);
}
async function explain(cmd, version = '26.2') {
  return page.evaluate(async ([c, v]) => {
    const r = await MC.api('explain', { command: c, version: v });
    return r.result;
  }, [cmd, version]);
}
const titles = r => r.problems.map(p => p.title).join(' | ');

await page.goto(`${BASE}/doctor.php`);
await page.waitForFunction(() => window.MC && window.MC.api);

// ── VERSION SYNTAX CONVERSION ───────────────────
let r = await doctor('/give @p diamond_sword{Enchantments:[{id:"minecraft:sharpness",lvl:5}]}', '26.2');
has('nbt on 26.2: flagged', titles(r), 'old NBT syntax');
check('nbt on 26.2: fixed', r.fixed, '/give @p diamond_sword[enchantments={sharpness:5}]');

r = await doctor('/give @p diamond_sword{display:{Name:\'{"text":"Blade","color":"gold"}\'},Unbreakable:1b}', '26.2');
check('nbt name on 26.2: SNBT text', r.fixed,
  '/give @p diamond_sword[custom_name={text:"Blade",color:"gold"},unbreakable={}]');

r = await doctor('/give @p diamond_sword{display:{Name:\'{"text":"Blade"}\'}}', '1.21.4');
check('nbt name on 1.21.4: stays a JSON string', r.fixed,
  '/give @p diamond_sword[custom_name=\'{"text":"Blade"}\']');

r = await doctor('/give @p diamond_sword[enchantments={levels:{sharpness:5}}]', '26.2');
has('levels wrapper on 26.2: flagged', titles(r), 'no longer uses "levels"');
check('levels wrapper on 26.2: fixed', r.fixed, '/give @p diamond_sword[enchantments={sharpness:5}]');

r = await doctor('/give @p diamond_sword[enchantments={sharpness:5}]', '1.21.4');
has('flat enchantments on 1.21.4: flagged', titles(r), 'needs a "levels" wrapper');
check('flat enchantments on 1.21.4: fixed', r.fixed, '/give @p diamond_sword[enchantments={levels:{sharpness:5}}]');

r = await doctor('/give @p diamond_sword[custom_name={text:"Hi"}]', '1.21.4');
has('snbt text on 1.21.4: flagged', titles(r), 'must be a quoted JSON string');
check('snbt text on 1.21.4: fixed', r.fixed, '/give @p diamond_sword[custom_name=\'{"text":"Hi"}\']');

r = await doctor('/give @p diamond_sword[enchantments={sharpness:5}]', '1.20.4');
has('components on 1.20.4: flagged', titles(r), 'which 1.20.4 does not have');

r = await doctor('/give @p diamond_sword[enchantments={sharpness:5}]', '26.2');
check('correct command on 26.2: clean', r.problems.length, 0);
check('correct command on 26.2: ok', r.ok, true);

// ── STRUCTURE ───────────────────────────────────
r = await doctor('/give @p diamond_sword{Enchantments:[{id:"sharpness"');
has('unclosed bracket', titles(r), 'is never closed');
r = await doctor('/give @p stone[custom_name="unterminated]');
has('unclosed quote', titles(r), 'quote is never closed');
r = await doctor('/give @p stone{a:[1,2}]');
has('crossed brackets', titles(r), 'crossed over');

// ── IDS AND NAMES ───────────────────────────────
r = await doctor('/gamemode creativ @a');
has('gamemode typo', titles(r), 'is not a game mode');
has('gamemode typo suggestion', r.problems[0].fix, 'creative');

r = await doctor('/gamerule keepinventory true');
has('gamerule capitalisation', titles(r), 'is not a gamerule');
has('gamerule suggestion', r.problems[0].fix, 'keepInventory');

r = await doctor('/gamerule spawnChunkRadius 4', '1.19.4');
has('gamerule too new', titles(r), 'does not exist in 1.19.4');

r = await doctor('/effect @p speed 30 1');
has('bedrock effect syntax', titles(r), 'needs give or clear first');
has('bedrock effect fix', r.problems[0].fix, '/effect give @p speed');

r = await doctor('/effect give @p speeed 30 1');
has('effect typo', titles(r), 'is not an effect');
has('effect suggestion', r.problems[0].fix, 'speed');

r = await doctor('/summon zomby ~ ~ ~');
has('entity typo', titles(r), 'Unknown entity');

r = await doctor('/xyzzy @p');
has('unknown command', titles(r), 'is not a Minecraft command');

r = await doctor('/kill @z');
has('bad selector', titles(r), '@z is not a real selector');

r = await doctor('/kill @e[typ=zombie]');
has('bad selector argument', titles(r), 'is not a selector argument');
has('selector argument suggestion', r.problems[0].fix, 'type');

// ── COORDINATES AND LIMITS ──────────────────────
r = await doctor('/tp @s ^5 ~ ~');
has('mixed coordinates', titles(r), 'cannot be mixed');

r = await doctor('/fill 0 0 0 200 50 200 stone');
has('fill too big', titles(r), 'too big for one command');
has('fill split suggestion', r.problems[0].fix, 'smaller fills');

r = await doctor('/fill ~ ~ ~ ~10 ~5 ~10 stone');
check('normal fill is clean', r.problems.length, 0);

// ── EXECUTE ─────────────────────────────────────
r = await doctor('/execute as @a at @s say hi');
has('execute without run', titles(r), 'never says what to run');
r = await doctor('/execute as @a at @s run say hi');
check('valid execute is clean', r.problems.length, 0);

// ── TELLRAW JSON ────────────────────────────────
r = await doctor('/tellraw @a {"text":"hi",}', '1.21.4');
has('bad json before 1.21.5', titles(r), 'not valid JSON');
r = await doctor('/tellraw @a {"text":"hello"}', '1.21.4');
check('good json is clean', r.problems.length, 0);

// ── NO SLASH ────────────────────────────────────
r = await doctor('gamemode creative @s');
has('missing slash noted', titles(r), 'No leading slash');
check('missing slash is not an error', r.ok, true);

// ── EXPLAINER ───────────────────────────────────
let e = await explain('/execute as @a at @s if entity @e[type=zombie,distance=..10] run say Zombie nearby');
has('explain execute: summary', e.summary, 'as every player');
const stepText = e.steps.map(s => s[0] + ': ' + s[1]).join('\n');
has('explain execute: as', stepText, 'changes who "@s" means');
has('explain execute: at', stepText, 'changes where ~ ~ ~ means');
has('explain execute: condition', stepText, 'at least one match');
has('explain execute: zombie', stepText, 'that is a zombie');
has('explain execute: distance', stepText, 'within 10 blocks');
has('explain execute: run', stepText, 'say Zombie nearby');

e = await explain('/give @p diamond_sword[custom_name={text:"Blade",color:"gold"},enchantments={sharpness:5}] 1');
has('explain give: who', e.steps.map(s => s[1]).join(' '), 'nearest player');
has('explain give: rename', e.steps.map(s => s[1]).join(' '), 'Renamed to "Blade" in gold');
has('explain give: enchant', e.steps.map(s => s[1]).join(' '), 'sharpness 5');

e = await explain('/fill ~ ~ ~ ~10 ~5 ~10 stone_bricks hollow');
has('explain fill: size', e.steps.map(s => s[1]).join(' '), '726 blocks');
has('explain fill: hollow', e.steps.map(s => s[1]).join(' '), 'inside is cleared to air');

e = await explain('/tp @s ~ ~10 ~');
has('explain tp: relative', e.steps.map(s => s[1]).join(' '), '10 blocks up');

// ── UI ──────────────────────────────────────────
await page.fill('#doc-input', '/gamerule keepinventory true');
await page.click('#doc-run');
await page.waitForSelector('#doc-output .finding');
has('UI: shows the finding', await page.textContent('#doc-output'), 'is not a gamerule');
hasNot('UI: no fixed block when there is nothing to rewrite', await page.textContent('#doc-output'), 'Corrected command');

await page.fill('#doc-input', '/give @p diamond_sword{Enchantments:[{id:"minecraft:sharpness",lvl:5}]}');
await page.click('#doc-run');
await page.waitForSelector('#doc-fixed');
check('UI: offers the fixed command', (await page.textContent('#doc-fixed [data-cmd]')).trim(),
  '/give @p diamond_sword[enchantments={sharpness:5}]');

await page.goto(`${BASE}/doctor.php?mode=explain`);
await page.waitForFunction(() => window.MC && window.MC.api);
await page.fill('#doc-input', '/kill @e');
await page.click('#doc-run');
await page.waitForSelector('.exp-step');
has('UI: explainer renders', await page.textContent('#doc-output'), 'no undo');

await browser.close();
console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) { console.log('\nFailures:'); failures.forEach(f => console.log('  ✗ ' + f)); process.exit(1); }
