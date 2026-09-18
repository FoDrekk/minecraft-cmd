// Regression tests for the Python-data-engine -> PHP integration
// (Phase 10): lib/generated.php's own behaviour (via tests/generated-probe.php),
// plus an end-to-end check that Knowledge -> Materials/Items render the
// "✓ scan-verified" tag when data/generated/*.json confirms an id, and
// render exactly as before when it's absent (the default, gitignored state
// documented in python/README.md).
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { launchChromium } from './_launch.mjs';

const BASE = process.env.MCCMD_BASE || 'http://127.0.0.1:8899';
let pass = 0, fail = 0;
const failures = [];
const check = (n, a, e) => (JSON.stringify(a) === JSON.stringify(e) ? pass++ : (fail++, failures.push(`${n}\n    expected: ${JSON.stringify(e)}\n    actual:   ${JSON.stringify(a)}`)));
const has = (n, h, s) => (String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected to contain: ${s}\n    actual: ${h}`)));
const lacks = (n, h, s) => (!String(h).includes(s) ? pass++ : (fail++, failures.push(`${n}\n    expected NOT to contain: ${s}\n    actual: ${h}`)));

const HERE = path.dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = path.resolve(HERE, '..');
const REAL_GENERATED_DIR = path.join(REPO_ROOT, 'data', 'generated');
const FIXTURE_DIR = path.join(HERE, 'fixtures', 'generated');

const browser = await launchChromium();
const page = await browser.newPage();
page.setDefaultTimeout(15000);
await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

// ── lib/generated.php unit behaviour, via the probe endpoint ──────
const res = await page.goto(`${BASE}/tests/generated-probe.php`, { waitUntil: 'domcontentloaded' });
check('probe endpoint responds 200', res.status(), 200);
const D = await page.evaluate(() => JSON.parse(document.body.innerText));

check('no generated/ directory -> unavailable', D.missing.available, false);
check('no generated/ directory -> versions() is null', D.missing.versions, null);
check('no generated/ directory -> item summary degrades to unverified', D.missing.itemSummary, { verified: false, sources: [], changed: false });
check('no generated/ directory -> block summary degrades to unverified', D.missing.blockSummary, { verified: false, sources: [], changed: false });

check('valid fixture set -> available', D.valid.available, true);
check('valid fixture set -> default_version read through', D.valid.defaultVersion, '26.2');
check('a known item is verified', D.valid.knownItem.verified, true);
check('id normalisation matches the app-wide convention (minecraft: prefix, case, spaces)', D.valid.knownItemNamespaced.verified, true);
check('an id never seen by any scan is not verified', D.valid.unknownItem, { verified: false, sources: [], changed: false });
check('a known block is verified', D.valid.knownBlock.verified, true);
check('a block seen in two sources reports both', D.valid.sourcesForSword.sort(), ['1.20.1', '26.2']);
check('version-diff.json marks the changed block as changed', D.valid.changedBlock.changed, true);
check('a block with no diff entry is not marked changed', D.valid.knownBlock.changed, false);

check('malformed items.json still reports available (versions.json alone is valid)', D.malformed.available, true);
check('malformed items.json degrades that lookup to unverified rather than erroring', D.malformed.itemSummary, { verified: false, sources: [], changed: false });

// ── End-to-end: absent by default (real repo state) ───────────────
if (fs.existsSync(REAL_GENERATED_DIR)) {
  fail++;
  failures.push(`data/generated/ unexpectedly exists on disk at ${REAL_GENERATED_DIR} — this test assumes the default gitignored (absent) state`);
} else {
  // Check the rendered grid TEXT, not the full page HTML — the
  // .verified-tag CSS *rule* is always present in <style> regardless of
  // data, so asserting against page.content() would always "find" the
  // class name and the check would be meaningless.
  await page.goto(`${BASE}/knowledge.php?t=materials`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && document.getElementById('mat-grid').children.length > 0);
  lacks('materials tab shows no verified tag when data/generated/ is absent', await page.textContent('#mat-grid'), 'scan-verified');

  await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && document.getElementById('item-grid').children.length > 0);
  lacks('items tab shows no verified tag when data/generated/ is absent', await page.textContent('#item-grid'), 'scan-verified');
}

// ── End-to-end: present (temporarily copy the fixture set in) ─────
let restored = true;
try {
  fs.mkdirSync(REAL_GENERATED_DIR, { recursive: true });
  for (const f of fs.readdirSync(FIXTURE_DIR)) {
    fs.copyFileSync(path.join(FIXTURE_DIR, f), path.join(REAL_GENERATED_DIR, f));
  }
  restored = false;

  await page.goto(`${BASE}/knowledge.php?t=materials`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && document.getElementById('mat-grid').children.length > 0);
  await page.fill('#mat-search', 'oak planks');
  await page.waitForTimeout(100);
  has('a block confirmed by the fixture scan shows the verified tag', await page.textContent('#mat-grid'), 'scan-verified');

  await page.fill('#mat-search', 'candle');
  await page.waitForTimeout(100);
  has('a block whose art changed between scanned sources notes it', await page.textContent('#mat-grid'), 'art updated');

  await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => window.MC && document.getElementById('item-grid').children.length > 0);
  await page.fill('#item-search', 'diamond sword');
  await page.waitForTimeout(100);
  has('an item confirmed by the fixture scan shows the verified tag', await page.textContent('#item-grid'), 'scan-verified');

  await page.fill('#item-search', 'apple');
  await page.waitForTimeout(100);
  // apple only appears in items.json under source "1.20.1", so it's still verified — but never claims "changed".
  const appleCard = await page.textContent('#item-grid');
  has('apple (present in the fixture set) is verified', appleCard, 'scan-verified');
  lacks('apple has no diff entry, so it never claims art changed', appleCard, 'art updated');
} finally {
  fs.rmSync(REAL_GENERATED_DIR, { recursive: true, force: true });
  restored = true;
}
check('the temporary data/generated/ fixture copy was cleaned up', restored, true);

// ── Version awareness: the Items tab now respects the selected version ──
// (Materials already did via blocksList($version); Items previously
// used the unfiltered $ITEMS global for every version.)
await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
await page.evaluate(() => { document.cookie = 'mc_version=1.19.4;path=/'; });
await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('item-grid').children.length > 0);
await page.fill('#item-search', 'mace');
await page.waitForTimeout(100);
check('the mace (added in 1.21) is hidden on 1.19.4', await page.locator('.item-card').count(), 0);

await page.evaluate(() => { document.cookie = 'mc_version=26.2;path=/'; });
await page.goto(`${BASE}/knowledge.php?t=items`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && document.getElementById('item-grid').children.length > 0);
await page.fill('#item-search', 'mace');
await page.waitForTimeout(100);
check('the mace is shown on 26.2', await page.locator('.item-card').count(), 1);

// Give Item / Kit Builder / NBT Builder must stay unfiltered by version —
// this integration must not change that documented, intentional behaviour.
await page.evaluate(() => { document.cookie = 'mc_version=1.19.4;path=/'; });
const giveItems = await page.evaluate(async (base) => {
  const r = await fetch(base + '/commands.php?t=give');
  return await r.text();
}, BASE);
has('Give Item still offers the mace on old versions (unfiltered by design)', giveItems, '"mace"');

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
