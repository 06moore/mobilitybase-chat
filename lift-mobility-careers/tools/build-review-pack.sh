#!/usr/bin/env bash
# Builds the review ZIP. Usage: tools/build-review-pack.sh <screenshots-dir> <output.zip>
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; SHOTS="$1"; OUT="$2"
STAGE="$(mktemp -d)/lift-mobility-careers-review"; mkdir -p "$STAGE"
cp -a "$ROOT/." "$STAGE/" && rm -rf "$STAGE/dist" && find "$STAGE" -name .DS_Store -delete
cp "$ROOT/docs/REVIEW-GUIDE.md" "$STAGE/REVIEW-GUIDE.md"
cp "$ROOT/docs/CHANGES-AND-STATUS.md" "$STAGE/CHANGES-AND-STATUS.md"
mkdir -p "$STAGE/screenshots" && cp "$SHOTS"/*.png "$STAGE/screenshots/"
{
  echo "# Source code – Lift and Mobility Careers"; echo
  echo "Every custom code file, concatenated. Generated $(date -u +%Y-%m-%d). Third-party fonts and images are omitted."; echo
  echo "## File index"; echo
  cd "$ROOT"
  FILES=$(find wordpress/wp-content/plugins/lmc-core wordpress/wp-content/themes/lmc tools/dev tools/tests tools/brand tools/*.sh -type f \( -name '*.php' -o -name '*.css' -o -name '*.js' -o -name '*.sh' -o -name '*.py' \) ! -path '*/results/*' | sort)
  for f in $FILES; do echo "- \`$f\` ($(wc -l < "$f") lines)"; done; echo
  for f in $FILES; do
    ext="${f##*.}"; [ "$ext" = "sh" ] && ext=bash; [ "$ext" = "py" ] && ext=python
    echo "---"; echo; echo "## \`$f\`"; echo; echo '```'"$ext"; cat "$f"; echo; echo '```'; echo
  done
} > "$STAGE/SOURCE-CODE.md"
rm -f "$OUT"; (cd "$(dirname "$STAGE")" && zip -qr "$OUT" "$(basename "$STAGE")")
echo "Built $OUT: $(unzip -l "$OUT" | tail -1)"
