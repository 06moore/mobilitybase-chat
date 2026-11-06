#!/usr/bin/env bash
# Builds a disposable local WordPress (SQLite, no MySQL needed) for development/testing.
# Usage: tools/dev/setup-local.sh [target_dir]   (default /tmp/lmc-dev)
# Then:  php -S 127.0.0.1:8080 -t /tmp/lmc-dev/wp tools/dev/router.php
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DEV="${1:-${LMC_DEV_DIR:-/tmp/lmc-dev}}"
URL="${LMC_DEV_URL:-http://127.0.0.1:8080}"
mkdir -p "$DEV"
cd "$DEV"
if [ ! -d wp ]; then
  composer create-project --no-interaction --prefer-dist johnpbloch/wordpress-core wp >/dev/null 2>&1
fi
if [ ! -d sqlite ]; then
  git clone --depth 1 https://github.com/WordPress/sqlite-database-integration.git sqlite >/dev/null 2>&1
fi
SP=wp/wp-content/plugins/sqlite-database-integration
rm -rf "$SP" && cp -rL sqlite/packages/plugin-sqlite-database-integration "$SP"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$DEV/$SP#" -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" "$SP/db.copy" > wp/wp-content/db.php
# Link our code
rm -rf wp/wp-content/plugins/lmc-core wp/wp-content/themes/lmc
ln -s "$ROOT/wordpress/wp-content/plugins/lmc-core" wp/wp-content/plugins/lmc-core
ln -s "$ROOT/wordpress/wp-content/themes/lmc" wp/wp-content/themes/lmc
cat > wp/wp-config.php <<CFG
<?php
define('DB_NAME','wp'); define('DB_USER',''); define('DB_PASSWORD',''); define('DB_HOST',''); define('DB_CHARSET','utf8mb4'); define('DB_COLLATE','');
define('DB_DIR', '$DEV/db/'); define('DB_FILE','lmc.sqlite');
\$table_prefix = 'wp_';
define('WP_DEBUG', true); define('WP_DEBUG_LOG', '$DEV/debug.log'); define('WP_DEBUG_DISPLAY', false);
define('WP_HOME','$URL'); define('WP_SITEURL','$URL');
define('LMC_MAIL_CAPTURE', '$DEV/mail.log'); // dev only: emails are written here instead of being sent
define('DISABLE_WP_CRON', true);
foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as \$k) define(\$k, 'dev-only-'.\$k);
if ( ! defined('ABSPATH') ) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
CFG
rm -rf "$DEV/db" && mkdir -p "$DEV/db" && : > "$DEV/mail.log"
php "$ROOT/tools/dev/install.php" "$DEV/wp" "$URL"
echo "Local site ready: $URL  (admin / admin-dev-password)"
