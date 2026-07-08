#!/usr/bin/env bash
# Filament 后台已并入 php.do/admin/
# 日常部署：git push server main（post-receive 会 composer + optimize + reload FrankenPHP）
# 本脚本仅用于单独 rsync 调试。

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SERVER="${SERVER:-65.109.62.100}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/gentpan.pem}"
REMOTE_DIR="${REMOTE_DIR:-/var/www/php.do/admin}"
SSH=(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "root@$SERVER")

echo "==> Sync to $SERVER:$REMOTE_DIR"
rsync -az --delete \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='.env' \
  --exclude='action.php' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" \
  "$ROOT/" "root@$SERVER:$REMOTE_DIR/"

"${SSH[@]}" bash -s <<EOF
set -e
cd $REMOTE_DIR
if [ ! -f .env ]; then
  echo "Missing $REMOTE_DIR/.env — copy from previous install or .env.example"
  exit 1
fi
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev -o --no-interaction
php artisan filament:assets --no-interaction
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache || true
systemctl reload frankenphp
EOF

echo "==> Deployed Filament admin at $REMOTE_DIR (https://php.do/admin)"
