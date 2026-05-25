#!/bin/sh
set -eu

if [ ! -f /app/.env ]; then
cat > /app/.env <<EOF
DB_DRIVER=${DB_DRIVER:-pgsql}
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}
DB_NAME=${DB_NAME:-itisgram}
DB_USER=${DB_USER:-postgres}
DB_PASSWORD=${DB_PASSWORD:-87918791}
APP_ENV=${APP_ENV:-development}
APP_DEBUG=${APP_DEBUG:-true}
WS_HOST=${WS_HOST:-0.0.0.0}
WS_PORT=${WS_PORT:-8080}
EOF
fi

exec "$@"