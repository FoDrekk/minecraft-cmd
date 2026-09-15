// Regression tests for the Enchantment Hub (Stage 3).
// Select item -> Recommended -> Customize -> Conflict check -> Command
// preview -> Copy -> Save, driven through the real page.
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));
const hasNot = (n, h, s) => (!String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    should NOT contain: ${s}`)));

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

const setVersion = async v => { await page.evaluate(x => MC.setVersion(x), v); await page.waitForTimeout(80); };
const cmd = async () => (await page.textContent('#out-ench [data-cmd]')).trim();
const warn = async () => (await page.textContent('#out-ench [data-warn]')).trim();

async function pickItem(query) {
  await page.fill('#eh-search', query);
  await page.waitForTimeout(80);
  await page.locator('.eh-item-card:not(.locked)').first().click();
  await page.waitForTimeout(80);
}
// Enchant rows are addressed by their stable id (#eh-ck-<id>), not by
// display name — several names ("Protection" / "Fire Protection") are
// substrings of each other, which breaks a hasText match.
async function toggle(id) {
  await page.locator('#eh-ck-' + id).click();
  await page.waitForTimeout(80);
}
async function isChecked(id) {
  return page.locator('#eh-ck-' + id).isChecked();
}
async function levelInput(id) {
  return page.locator('#eh-ck-' + id).locator('xpath=ancestor::div[contains(@class,"eh-ench-row")]').locator('input[type=number]');
}

await page.goto(`${BASE}/enchantments.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('eh-item-grid').children.length > 0);
await setVersion('26.2');

// ── ITEM PICKER ──────────────────────────────────
const totalCards = await page.locator('.eh-item-card').count();
check('item picker: has cards', totalCards > 20, true);

await page.fill('#eh-search', 'diamond sword');
await page.waitForTimeout(80);
check('search filters to one match', await page.locator('.eh-item-card').count(), 1);

await page.click('[data-eh-cat="Weapons"]');
await page.fill('#eh-search', '');
await page.waitForTimeout(80);
const weaponsOnly = await page.locator('.eh-item-card').count();
check('category tab narrows the grid', weaponsOnly < totalCards, true);
await page.click('[data-eh-cat=""]');

// ── SELECT ITEM REVEALS THE REST ─────────────────
await pickItem('diamond sword');
check('picking an item reveals the rest of the flow', await page.$eval('#eh-picked-wrap', el => el.hidden), false);
check('no enchants yet: plain give command', await cmd(), '/give @p diamond_sword');

// ── RECOMMENDED ───────────────────────────────────
const recNames = await page.locator('.eh-rec-card .eh-rec-name').allTextContents();
has('sword recommendations include Sharpness', recNames.join(','), 'Sharpness');
has('sword recommendations include Mending', recNames.join(','), 'Mending');

await page.click('.eh-rec-card:has-text("Sharpness")');
await page.waitForTimeout(80);
check('clicking a recommended card checks it below', await isChecked('sharpness'), true);
has('command reflects the recommended pick', await cmd(), 'sharpness:5');

// ── CUSTOMIZE: level respects the normal maximum ──
await (await levelInput('sharpness')).fill('999');
await page.waitForTimeout(80);
has('level input is clamped to the normal max (5) without Advanced', await cmd(), 'sharpness:5');

await page.click('#eh-advanced');
await page.waitForTimeout(80);
await (await levelInput('sharpness')).fill('10');
await page.waitForTimeout(80);
has('Advanced allows a level above the normal maximum', await cmd(), 'sharpness:10');
has('over-level warns it is command-only', await warn(), 'not from an enchanting table or anvil');
await page.click('#eh-advanced');
await page.waitForTimeout(80);

// ── CONFLICT CHECKER ──────────────────────────────
await toggle('smite');
await page.waitForTimeout(80);
check('conflict banner appears', await page.$eval('#eh-conflict-banner', el => el.hidden), false);
has('conflict names both enchantments', await page.textContent('#eh-conflict-banner'), 'Sharpness');
has('conflict names both enchantments (2)', await page.textContent('#eh-conflict-banner'), 'Smite');
has('conflicting rows are visually flagged', await page.locator('.eh-ench-row.conflict').count() > 0 ? 'yes' : 'no', 'yes');

await page.click('#eh-conflict-banner button:has-text("Fix conflicts")');
await page.waitForTimeout(80);
check('Fix conflicts clears the banner', await page.$eval('#eh-conflict-banner', el => el.hidden), true);
check('Fix conflicts keeps the first pick (Sharpness)', await isChecked('sharpness'), true);
check('Fix conflicts drops the later conflicting pick (Smite)', await isChecked('smite'), false);

// Every documented vanilla conflict pair, one item each.
const conflictCases = [
  ['diamond pickaxe', 'silk_touch', 'fortune'],
  ['diamond boots', 'frost_walker', 'depth_strider'],
  ['bow', 'infinity', 'mending'],
  ['crossbow', 'multishot', 'piercing'],
  ['diamond helmet', 'protection', 'fire_protection'],
];
for (const [item, a, b] of conflictCases) {
  await pickItem(item);
  await toggle(a);
  await toggle(b);
  await page.waitForTimeout(80);
  check(`${a} vs ${b} on ${item}: flagged as a conflict`,
    await page.$eval('#eh-conflict-banner', el => el.hidden), false);
}

// Trident: Riptide conflicts with BOTH Loyalty and Channeling.
await pickItem('trident');
await toggle('riptide');
await toggle('loyalty');
await page.waitForTimeout(80);
check('Riptide vs Loyalty: flagged', await page.$eval('#eh-conflict-banner', el => el.hidden), false);
await toggle('loyalty');
await toggle('channeling');
await page.waitForTimeout(80);
check('Riptide vs Channeling: flagged', await page.$eval('#eh-conflict-banner', el => el.hidden), false);

// ── VERSION AWARENESS ─────────────────────────────
await pickItem('diamond sword');
await toggle('sharpness');
await page.waitForTimeout(80);
await setVersion('1.21.4');
has('1.21.4: component syntax, JSON-string text stays a bracket component', await cmd(), 'diamond_sword[enchantments={levels:{sharpness:5}}]');
await setVersion('1.20.4');
has('1.20.4: legacy NBT enchant list', await cmd(), 'Enchantments:[{id:"minecraft:sharpness",lvl:5s}]');
await setVersion('bedrock');
check('Bedrock: enchantments silently unsupported by /give, plain item only', await cmd(), '/give @p diamond_sword');
has('Bedrock: warns that custom data is not supported', await warn(), 'Bedrock /give cannot set custom names, lore or enchantments');
await setVersion('26.2');

// ── NAME / LORE — safe encoding of special characters ──
await page.fill('#eh-name', 'Boss "Killer" <Test>');
await page.waitForTimeout(80);
has('name with quotes and angle brackets is escaped into valid SNBT', await cmd(), 'text:"Boss \\"Killer\\" <Test>"');

// A real XSS probe: if the command were ever written via innerHTML instead
// of textContent, this markup would actually execute.
await page.fill('#eh-name', '<img src=x onerror="window.__ench_pwned=1">');
await page.waitForTimeout(80);
const pwned = await page.evaluate(() => window.__ench_pwned);
check('command output never executes injected markup (textContent, not innerHTML)', pwned, undefined);
await page.fill('#eh-name', 'Boss Killer');
await page.waitForTimeout(80);

await page.click('summary:has-text("Lore")');
await page.fill('.eh-lore-lines input', 'Forged for the End');
await page.waitForTimeout(80);
has('lore line included in the command', await cmd(), 'Forged for the End');

// ── MACE — Java 1.21+ gate on both the item and its exclusive enchants ──
await setVersion('1.20.6');
await page.fill('#eh-search', 'mace');
await page.waitForTimeout(80);
check('Mace is locked before 1.21', await page.locator('.eh-item-card').first().evaluate(el => el.classList.contains('locked')), true);

await setVersion('26.2');
await page.waitForTimeout(80);
check('Mace unlocks from 1.21 onward', await page.locator('.eh-item-card').first().evaluate(el => el.classList.contains('locked')), false);
await page.locator('.eh-item-card').first().click();
await page.waitForTimeout(80);
const maceEnch = await page.locator('.eh-ench-row .eh-ench-name').allTextContents();
has('Mace customize list includes its exclusive enchantments', maceEnch.join(','), 'Density');
has('Mace customize list includes Breach', maceEnch.join(','), 'Breach');
hasNot('Mace cannot take Sharpness', maceEnch.join(','), 'Sharpness');

await toggle('density');
await toggle('smite');
await page.waitForTimeout(80);
check('Density vs Smite on a mace: flagged (mace-specific conflict group)',
  await page.$eval('#eh-conflict-banner', el => el.hidden), false);

// ── GLOBAL SEARCH + NAV INTEGRATION ───────────────
const searchResult = await page.evaluate(async () => {
  const r = await MC.api('search', { q: 'enchant' });
  return r.rows.map(x => x.href);
});
has('global search surfaces the Enchantment Hub', searchResult.join(','), 'enchantments.php');

await page.goto(`${BASE}/enchantments.php`, { waitUntil: 'domcontentloaded' });
check('Tools nav item is active on the Enchantment Hub page',
  await page.locator('.nav-link.active').first().textContent().then(t => t.trim().includes('Tools')), true);

// ── TARGET: quick selector chips, custom names, live validation ──
const says = async () => (await page.textContent('#eh-target-says')).trim();
await pickItem('diamond sword');
check('four quick selector chips are offered', await page.locator('[data-eh-target]').count(), 4);
has('the default target is explained in plain language', await says(), 'the nearest player');
check('the matching chip is lit',
  await page.locator('[data-eh-target="@p"]').evaluate(e => e.classList.contains('active')), true);

await page.click('[data-eh-target="@s"]');
await page.waitForTimeout(80);
has('picking @s rewrites the command', await cmd(), '/give @s diamond_sword');
has('@s is explained', await says(), 'whoever runs the command');

await page.fill('#eh-target', 'nizkbiits');
await page.waitForTimeout(80);
has('a custom player name is accepted', await cmd(), '/give nizkbiits diamond_sword');
has('the custom name is echoed back', await says(), 'the player nizkbiits');
check('no chip stays lit for a custom name', await page.locator('[data-eh-target].active').count(), 0);

await page.fill('#eh-target', '@z');
await page.waitForTimeout(80);
check('an invalid selector generates no command at all', await cmd(), '');
has('the invalid selector is explained', await warn(), 'not a real selector');

await page.fill('#eh-target', '@nizkbiits');
await page.waitForTimeout(80);
has('a player name written with @ is caught specifically', await says(), 'without the @');

await page.fill('#eh-target', '@a[distance=..10]');
await page.waitForTimeout(80);
has('a selector with arguments still works', await cmd(), '/give @a[distance=..10] diamond_sword');

await page.fill('#eh-target', 'ab');
await page.waitForTimeout(80);
has('a suspicious short name warns but still generates', await cmd(), '/give ab diamond_sword');
has('the short name is flagged', await warn(), '3-16 characters');
await page.fill('#eh-target', '@p');
await page.waitForTimeout(80);

// ── SAVE (Library) round-trips through the existing api.php ──
await pickItem('netherite sword');
await page.click('#out-ench [data-act="fav"]');
await page.waitForTimeout(250);
const saved = await page.evaluate(async () => {
  const r = await MC.api('fav_get', {});
  return r.rows.find(x => x.command.includes('netherite_sword') && x.tab === 'enchant');
});
check('Library save reaches the shared favourites store', !!saved, true);
if (saved) await page.evaluate(id => MC.api('fav_delete', { id }), saved.id);

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
