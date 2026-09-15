#!/usr/bin/env bash
# Run the whole browser test suite against a local PHP server.
#
#   ./tests/run.sh
#
# Starts php -S on a free port, runs every *.test.mjs, then stops it.
set -uo pipefail
cd "$(dirname "$0")/.."

PORT="${MCCMD_PORT:-8899}"
BASE="http://127.0.0.1:$PORT"
STARTED=0

if ! curl -sf -o /dev/null "$BASE/index.php" 2>/dev/null; then
  php -S "127.0.0.1:$PORT" > /tmp/mccmd-test-server.log 2>&1 &
  SERVER_PID=$!
  STARTED=1
  for _ in $(seq 1 40); do
    curl -sf -o /dev/null "$BASE/index.php" 2>/dev/null && break
    sleep 0.25
  done
fi

cleanup() { [ "$STARTED" = 1 ] && kill "$SERVER_PID" 2>/dev/null; }
trap cleanup EXIT

if ! node -e "import('playwright')" 2>/dev/null; then
  echo "Playwright is not installed. Run: npm install" >&2
  exit 1
fi

echo "Linting PHP…"
FAILED=0
for f in *.php lib/*.php lib/data/*.php lib/panels/*.php; do
  php -l "$f" > /dev/null || FAILED=1
done
[ "$FAILED" = 1 ] && { echo "PHP lint failed"; exit 1; }
echo "  all files parse"

echo "Checking JavaScript…"
for f in assets/*.js; do node --check "$f" || FAILED=1; done
[ "$FAILED" = 1 ] && { echo "JS syntax check failed"; exit 1; }
echo "  all files parse"

for suite in tests/*.test.mjs; do
  echo ""
  echo "── $(basename "$suite") ──"
  MCCMD_BASE="$BASE" node "$suite" || FAILED=1
done

echo ""
[ "$FAILED" = 0 ] && echo "✅ All suites passed" || echo "❌ Some suites failed"
exit "$FAILED"
