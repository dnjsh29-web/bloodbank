#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-10000}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:[0-9]\\+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear --no-ansi || true
php artisan route:clear --no-ansi || true
php artisan view:clear --no-ansi || true

if [ -n "${APP_KEY:-}" ]; then
    php artisan config:cache --no-ansi || true
fi

exec "$@"
