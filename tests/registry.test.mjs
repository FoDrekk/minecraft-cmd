// Registry consistency + Minecraft Wiki accuracy tests.
//
// These run against a small PHP endpoint (tests/registry-probe.php) that
// dumps the canonical registries as JSON, so the assertions test the
// real PHP data rather than a JS copy of it.
//
// What this guards:
//   * no duplicate / malformed ids, no missing display names
//   * every item family, category and enchantment reference resolves
//   * ids are canonical vanilla Minecraft spellings
//   * id normalisation is stable and idempotent
//   * Wiki-verified facts (durability, stack size, max levels) hold
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);

const res = await page.goto(`${BASE}/tests/registry-probe.php`, { waitUntil: 'domcontentloaded' });
check('probe endpoint responds 200', res.status(), 200);
const D = await page.evaluate(() => JSON.parse(document.body.innerText));

// ── STRUCTURAL INTEGRITY ─────────────────────────
const ids = D.items.map(i => i.id);
check('registry is non-empty', ids.length > 100, true);
check('no duplicate item ids', new Set(ids).size, ids.length);

const badId = D.items.filter(i => !/^[a-z0-9_]+$/.test(i.id)).map(i => i.id);
check('every id is lowercase snake_case', badId.join(',') || 'none', 'none');

const noName = D.items.filter(i => !i.name || !i.name.trim()).map(i => i.id);
check('every item has a display name', noName.join(',') || 'none', 'none');

const cats = new Set(D.categories);
const badCat = D.items.filter(i => !cats.has(i.cat)).map(i => `${i.id}:${i.cat}`);
check('every item category is a declared category', badCat.join(',') || 'none', 'none');

const fams = new Set(Object.keys(D.families));
const badFam = D.items.filter(i => i.family && !fams.has(i.family)).map(i => `${i.id}:${i.family}`);
check('every item family resolves', badFam.join(',') || 'none', 'none');

// Aliases must not collide with a real id or with each other.
check('no alias collides with a canonical id', D.aliasCollisions.join(',') || 'none', 'none');

// ── ENCHANTMENT RELATIONSHIPS ────────────────────
const slots = new Set(Object.keys(D.applicability));
const famSlots = Object.values(D.families).map(f => f.slot);
const orphanSlot = Object.keys(D.applicability).filter(s => !famSlots.includes(s));
check('every applicability slot maps to a real family', orphanSlot.join(',') || 'none', 'none');

check('every enchantment reference has metadata', D.unknownEnchants.join(',') || 'none', 'none');
check('every recommended enchantment is actually applicable', D.badRecommends.join(',') || 'none', 'none');
check('every preset enchantment is applicable to its slot', D.badPresets.join(',') || 'none', 'none');
check('every conflict-group member is a known enchantment', D.badConflicts.join(',') || 'none', 'none');

// ── CANONICAL VANILLA IDS ────────────────────────
// Minecraft uses American spelling exclusively. These were real bugs:
// the ids below could never match a block/enchantment in-game.
check('Sweeping Edge uses the modern id sweeping_edge', D.enchantIds.includes('sweeping_edge'), true);
check('the pre-1.13 id "sweeping" is gone', D.enchantIds.includes('sweeping'), false);
check('stone bricks use the vanilla spelling chiseled_', D.blockIds.includes('chiseled_stone_bricks'), true);
check('the invalid id chiselled_stone_bricks is gone', D.blockIds.includes('chiselled_stone_bricks'), false);
check('copper uses the vanilla spelling oxidized_', D.blockIds.includes('oxidized_copper'), true);
check('the invalid id oxidised_copper is gone', D.blockIds.includes('oxidised_copper'), false);

const britishItems = D.items.filter(i => /(chiselled|oxidised|_grey|armour_stand)/.test(i.id)).map(i => i.id);
check('no item id uses British spelling', britishItems.join(',') || 'none', 'none');

// ── ID NORMALISATION ─────────────────────────────
check('namespaced id normalises', D.norm['minecraft:diamond_sword'], 'diamond_sword');
check('display name normalises', D.norm['Diamond Sword'], 'diamond_sword');
check('uppercase normalises', D.norm['DIAMOND_SWORD'], 'diamond_sword');
check('normalisation is idempotent', D.norm['diamond_sword'], 'diamond_sword');
check('canonical form is namespaced', D.canonical, 'minecraft:diamond_sword');
check('a namespaced id still finds its family', D.slotOf['minecraft:bow'], 'Bow');
check('a bare id finds its family', D.slotOf['diamond_sword'], 'Sword');
check('a non-equipment item has no family', D.slotOf['diamond'], null);

// ── WIKI-VERIFIED VALUES ─────────────────────────
// https://minecraft.wiki/w/Durability
check('diamond tools have 1561 uses', D.dur.diamond_sword, 1561);
check('netherite tools have 2031 uses', D.dur.netherite_pickaxe, 2031);
check('golden tools have 32 uses (least durable)', D.dur.golden_sword, 32);
check('wooden tools have 59 uses', D.dur.wooden_axe, 59);
check('stone tools have 131 uses', D.dur.stone_shovel, 131);
check('iron tools have 250 uses', D.dur.iron_sword, 250);
check('diamond chestplate has 528 uses', D.dur.diamond_chestplate, 528);
check('netherite helmet has 407 uses', D.dur.netherite_helmet, 407);
check('chainmail matches iron armour', D.dur.chainmail_boots, D.dur.iron_boots);
check('elytra has 432 uses', D.dur.elytra, 432);
check('the mace has 500 uses', D.dur.mace, 500);
check('a non-damageable item reports no durability', D.dur.diamond, null);

// https://minecraft.wiki/w/Item#Stacking
check('most items stack to 64', D.stack.diamond, 64);
check('tools do not stack', D.stack.diamond_sword, 1);
check('armour does not stack', D.stack.iron_helmet, 1);
check('ender pearls stack to 16', D.stack.ender_pearl, 16);
check('honey bottles stack to 16', D.stack.honey_bottle, 16);
check('stews do not stack', D.stack.rabbit_stew, 1);
check('an unknown item reports no stack size (not a guess)', D.stack.definitely_not_an_item, null);

// https://minecraft.wiki/w/Enchanting
check('Sharpness caps at V', D.maxLevel.sharpness, 5);
check('Protection caps at IV', D.maxLevel.protection, 4);
check('Mending caps at I', D.maxLevel.mending, 1);
check('Sweeping Edge caps at III', D.maxLevel.sweeping_edge, 3);
check('Density caps at V', D.maxLevel.density, 5);
check('Breach caps at IV', D.maxLevel.breach, 4);
check('Wind Burst caps at III', D.maxLevel.wind_burst, 3);
check('Efficiency caps at V', D.maxLevel.efficiency, 5);
check('Feather Falling caps at IV', D.maxLevel.feather_falling, 4);
check('an unknown enchantment has no max level', D.maxLevel.not_an_enchantment, null);

// Mace: Sharpness was explicitly removed from maces in 24w18a.
check('the mace does not accept Sharpness', D.applicability.Mace.some(e => e[0] === 'sharpness'), false);
check('the mace accepts Density', D.applicability.Mace.some(e => e[0] === 'density'), true);
check('swords accept Sharpness', D.applicability.Sword.some(e => e[0] === 'sharpness'), true);
check('boots accept Feather Falling', D.applicability.Boots.some(e => e[0] === 'feather_falling'), true);
check('leggings accept Swift Sneak', D.applicability.Leggings.some(e => e[0] === 'swift_sneak'), true);

// https://minecraft.wiki/w/Food
check('Steak restores 8 hunger', D.food.cooked_beef[0], 8);
check('Golden Carrot has the best saturation of the listed foods', D.food.golden_carrot[1], 14.4);
check('a non-food item has no food values', D.food.diamond, null);

// ── VERSION / EDITION AWARENESS ──────────────────
// The mace arrived in 1.21, so older versions must not offer it.
check('the mace is absent on 1.20.4', D.maceOn['1.20.4'], false);
check('the mace is present on 1.21.1', D.maceOn['1.21.1'], true);
check('the mace is present on the default version', D.maceOn['26.2'], true);
check('version filtering never drops a non-gated item', D.itemsOn['1.20.4'] > 100, true);

// ── BLOCK / ITEM RELATIONSHIP ────────────────────
check('material identity resolves through one canonical id', D.blockItem.oak_planks, 'oak_planks');
check('texture key is derived from the canonical id', D.textureKey['minecraft:Diamond Sword'], 'diamond_sword');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) {
  console.log('\nFAILURES:');
  for (const f of failures) console.log('  ✗ ' + f);
}
process.exit(fail ? 1 : 0);
