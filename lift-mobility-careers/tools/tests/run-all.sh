#!/usr/bin/env bash
# Runs every test suite, each on a FRESH local site, and writes full output to tools/tests/results/.
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"; OUT="$ROOT/tools/tests/results"; mkdir -p "$OUT"
DEV="${LMC_DEV_DIR:-/tmp/lmc-dev}"
fresh() {
  pkill -f "php -S 0.0.0.0:8080" 2>/dev/null; sleep 0.5
  bash "$ROOT/tools/dev/setup-local.sh" >/dev/null 2>&1
  (PHP_CLI_SERVER_WORKERS=4 php -S 0.0.0.0:8080 -t "$DEV/wp" "$ROOT/tools/dev/router.php" > "$DEV/server.log" 2>&1 &)
  sleep 1
}
status=0
fresh; php "$ROOT/tools/tests/run.php" > "$OUT/integration.txt" 2>&1 || status=1
grep -v "wordpress.org\|wp_update\|wp_version" "$DEV/debug.log" 2>/dev/null | grep -i "warning\|fatal\|notice" > "$OUT/php-warnings.txt"
fresh; node "$ROOT/tools/tests/e2e.js" > "$OUT/browser-basic.txt" 2>&1 || status=1
fresh; LMC_SHOTS="${LMC_SHOTS:-/tmp/lmc-shots}" node "$ROOT/tools/tests/e2e-employer.js" > "$OUT/browser-employer-journey.txt" 2>&1 || status=1
grep -v "wordpress.org\|wp_update\|wp_version" "$DEV/debug.log" 2>/dev/null | grep -i "warning\|fatal\|notice" >> "$OUT/php-warnings.txt"
echo "Integration:      $(tail -1 "$OUT/integration.txt")"
echo "Browser (basic):  $(tail -1 "$OUT/browser-basic.txt")"
echo "Browser (journey):$(tail -1 "$OUT/browser-employer-journey.txt")"
echo "PHP warnings:     $(wc -l < "$OUT/php-warnings.txt")"
exit $status
