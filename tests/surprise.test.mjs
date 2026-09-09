// Regression test for the Surprise Me pool including the new
// Enchantment entry (Stage 9 cross-integration), run enough times to
// exercise every pool branch at least once.
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (a === e ? pass++ : (fail++, failures.push(`${n}\n    expected: ${e}\n    actual:   ${a}`)));

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && window.MC.api);

const kinds = new Set();
let sawEnchantment = false;
for (let i = 0; i < 40; i++) {
  const r = await page.evaluate(async () => (await MC.api('surprise', {})).pick);
  check(`pick #${i} has a kind`, typeof r.kind === 'string' && r.kind.length > 0, true);
  check(`pick #${i} has a title`, typeof r.title === 'string' && r.title.length > 0, true);
  check(`pick #${i} has an href`, typeof r.href === 'string' && r.href.length > 0, true);
  kinds.add(r.kind);
  if (r.kind === 'Enchantment') {
    sawEnchantment = true;
    check('Enchantment pick links to the Enchantment Hub with an item preselected', r.href.startsWith('enchantments.php?item='), true);
  }
}
check('Enchantment appeared in the pool across 40 draws', sawEnchantment, true);
check('multiple distinct kinds appeared (not stuck on one)', kinds.size > 3, true);

// Following an Enchantment pick actually preselects that item in the Hub
const enchPick = await page.evaluate(async () => {
  for (let i = 0; i < 60; i++) {
    const r = (await MC.api('surprise', {})).pick;
    if (r.kind === 'Enchantment') return r;
  }
  return null;
});
if (enchPick) {
  await page.goto(new URL(enchPick.href, BASE + '/').href, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && !document.getElementById('eh-picked-wrap').hidden);
  check('the Enchantment Hub actually opened with an item selected', await page.locator('#eh-selected-head').textContent().then(t => t.trim().length > 0), true);
} else {
  fail++; failures.push('Never drew an Enchantment pick in 60 tries to verify the deep link');
}

// UI smoke test: the button on Home actually renders a result
await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC);
await page.click('button:has-text("Surprise me")');
await page.waitForTimeout(300);
check('clicking Surprise Me renders a result card', await page.locator('.surprise-title').count() > 0, true);

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
