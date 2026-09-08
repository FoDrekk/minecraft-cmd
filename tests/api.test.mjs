// Regression tests for must-fix A: saving a command through api.php used to
// run it through strip_tags(), which silently deleted '<' and '>' from
// legitimate Minecraft syntax (tellraw JSON, literal chat text, etc).
// Rendering already escapes with e() (see mystuff.php ms_entry()), so the
// fix is to stop stripping tags on the way into storage.
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
page.on('pageerror', e => { fail++; failures.push('PAGE ERROR: ' + e.message); });

await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => window.MC && window.MC.api);

const CMD = '/tellraw @a {"text":"<Server> hi"}';

async function api(action, body) {
  return page.evaluate(async ([a, b]) => MC.api(a, b), [action, body]);
}

// ── HISTORY ───────────────────────────────────────
let r = await api('history_add', { command: CMD, tab: 'tellraw', version: '26.2' });
check('history_add: ok', r.ok, true);
let row = r.rows.find(x => x.command.startsWith('/tellraw'));
check('history: command kept its < and >', row && row.command, CMD);
if (row) await api('history_delete', { id: row.id });

// ── FAVOURITES (Command Library) ──────────────────
// Use a fresh command each run so re-running the suite never collides with
// favAdd()'s duplicate check.
const FAV_CMD = CMD + ' ' + Date.now();
r = await api('fav_add', { command: FAV_CMD, tab: 'tellraw', name: 'Server ping', version: '26.2' });
check('fav_add: ok', r.ok, true);
row = r.rows.find(x => x.command === FAV_CMD);
check('favourite: command kept its < and >', row && row.command, FAV_CMD);
// The name is a cosmetic label, not Minecraft syntax — it is intentionally
// still run through the stricter sanitize() (strip_tags kept), unlike the
// command text itself. This is the boundary the fix draws.
check('favourite: name field unaffected', row && row.name, 'Server ping');

// The saved command must round-trip through My Stuff without corruption,
// and must be HTML-escaped where it is rendered — never both stripped
// AND unescaped, which would be a real XSS risk this fix must not add.
await page.goto(`${BASE}/mystuff.php?t=library`, { waitUntil: 'domcontentloaded' });
const entryText = await page.locator('.entry-cmd', { hasText: '<Server>' }).first().textContent();
has('My Stuff: renders the literal text (escaped, not stripped)', entryText.trim(), FAV_CMD);
const html = await page.content();
hasNot('My Stuff: raw <Server> tag never lands unescaped in the DOM', html, '<Server> hi');
has('My Stuff: it is present as an escaped entity instead', html, '&lt;Server&gt;');

if (row) await api('fav_delete', { id: row.id });

await browser.close();

console.log(`\n${pass} passed, ${fail} failed`);
if (failures.length) console.log('\nFAILURES:\n' + failures.map(f => '  ✗ ' + f).join('\n\n'));
process.exit(fail ? 1 : 0);
