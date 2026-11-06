#!/usr/bin/env bash
# Packages the plugin and theme as installable zips in dist/ (upload via WP Admin → Plugins/Themes → Add New → Upload).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; mkdir -p "$ROOT/dist"
cd "$ROOT/wordpress/wp-content/plugins" && rm -f "$ROOT/dist/lmc-core.zip" && zip -qr "$ROOT/dist/lmc-core.zip" lmc-core -x '*/.DS_Store'
cd "$ROOT/wordpress/wp-content/themes" && rm -f "$ROOT/dist/lmc-theme.zip" && zip -qr "$ROOT/dist/lmc-theme.zip" lmc -x '*/.DS_Store'
ls -la "$ROOT/dist"
