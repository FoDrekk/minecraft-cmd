// Shared Chromium launcher for the test suite.
//
// package.json pins a Playwright version whose bundled browser revision
// can drift ahead of whatever Chromium build a given CI/sandbox image
// ships. When that happens `chromium.launch()` fails outright ("Executable
// doesn't exist…") instead of running against a perfectly usable browser
// that is already on disk. If PLAYWRIGHT_BROWSERS_PATH holds a `chromium`
// binary, use it explicitly; otherwise fall back to Playwright's own
// resolution so a normal `npx playwright install` setup still works.
import { chromium } from 'playwright';
import { existsSync } from 'node:fs';
import { join } from 'node:path';

export async function launchChromium(opts = {}) {
  const dir = process.env.PLAYWRIGHT_BROWSERS_PATH;
  const pinned = dir && join(dir, 'chromium');
  const executablePath = pinned && existsSync(pinned) ? pinned : undefined;
  return chromium.launch({ ...opts, ...(executablePath ? { executablePath } : {}) });
}
