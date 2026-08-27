#!/bin/sh
set -e

cd /var/www/html

log() { printf '[entrypoint] %s\n' "$1"; }
fail() { printf '[entrypoint] ERROR: %s\n' "$1" >&2; exit 1; }

# One-off commands (php artisan ..., sh) run as-is. Migrating and rebuilding
# caches only makes sense when this container is starting the web server, and
# doing it otherwise would make `key:generate --show` impossible to run before
# an APP_KEY exists.
case "${1:-}" in
    supervisord) ;;
    *) exec "$@" ;;
esac

# --- Required configuration -------------------------------------------------
# Every required value is checked here rather than with compose's ${VAR:?...}
# syntax. That form is evaluated while the compose file is parsed, which happens
# before Portainer applies a stack's environment variables, so a missing value
# surfaced as an unhelpful interpolation error and the stack never deployed.
# Failing here instead names the variable, in the container logs, and beats
# booting into a 500 page either way.
missing=""

require() {
    eval "value=\${$1:-}"
    [ -n "$value" ] || missing="${missing}  ${1}  — ${2}\n"
}

require APP_KEY "generate with: docker run --rm <image> php artisan key:generate --show"
require APP_URL "the public https:// address, e.g. https://apply.example.com"
require DB_PASSWORD "password for the application database user"
require MAIL_HOST "SMTP host; password resets fail without it"
require MAIL_FROM_ADDRESS "the From address on outbound mail"
require ADMIN_EMAIL "the administrator account created on first boot"
require ADMIN_PASSWORD "password for that administrator account"

if [ -n "$missing" ]; then
    printf '[entrypoint] ERROR: required configuration is missing:\n' >&2
    printf "$missing" >&2
    printf '[entrypoint] Set these in your stack environment (Portainer) or deploy.env, then redeploy.\n' >&2
    exit 1
fi

case "${APP_KEY}" in
    base64:*) ;;
    *) fail "APP_KEY must be a base64: value produced by 'php artisan key:generate --show'." ;;
esac

# An empty value is an empty string to Laravel's env(), not "unset", so a blank
# one here would replace the APP_KEY-derived default with nothing and orphan
# every registered passkey. Drop it instead.
if [ -z "${PASSKEYS_USER_HANDLE_SECRET:-}" ]; then
    unset PASSKEYS_USER_HANDLE_SECRET
fi

# --- Storage volume ---------------------------------------------------------
# The volume mounts over an empty directory on first boot, so the tree Laravel
# expects has to be recreated rather than assumed.
log "Preparing storage directories"
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache

if [ ! -L public/storage ]; then
    rm -rf public/storage
    php artisan storage:link --quiet
    log "Linked public/storage"
fi

# --- Wait for the database --------------------------------------------------
if [ "${DB_CONNECTION:-mysql}" != "sqlite" ]; then
    log "Waiting for ${DB_CONNECTION:-mysql} at ${DB_HOST}:${DB_PORT}"
    attempt=0
    until php artisan db:show --quiet >/dev/null 2>&1; do
        attempt=$((attempt + 1))
        [ "$attempt" -lt 60 ] || fail "Database was not reachable after 60 attempts."
        sleep 2
    done
    log "Database is up"
fi

# --- Schema and first-boot content -----------------------------------------
log "Running migrations"
php artisan migrate --force --no-interaction

log "Provisioning starter content"
php artisan app:provision --no-interaction

# --- Caches -----------------------------------------------------------------
# Config has to be cached at runtime, not build time, because every value comes
# from the environment Portainer injects.
log "Caching configuration, routes and views"
php artisan optimize:clear --quiet
php artisan optimize --quiet

log "Ready"

# --- Optional background processes -----------------------------------------
if [ "${RUN_QUEUE_WORKER:-false}" = "true" ]; then
    log "Queue worker enabled"
    sed -i '/^\[program:queue\]/,/^$/ s/^autostart=false/autostart=true/' /etc/supervisor/conf.d/supervisord.conf
fi

if [ "${RUN_SCHEDULER:-false}" = "true" ]; then
    log "Scheduler enabled"
    sed -i '/^\[program:scheduler\]/,/^$/ s/^autostart=false/autostart=true/' /etc/supervisor/conf.d/supervisord.conf
fi

exec "$@"
