#!/usr/bin/env bash
set -euo pipefail

# Remote production deploy for LastRound on Namecheap shared hosting.
# Intended to be executed over SSH as: bash -s < deploy-on-server.sh

APP_DIR="${DEPLOY_PATH:-}"
COMPOSER_BIN="${COMPOSER_BIN:-/home/fitcbfra/bin/composer}"
RELEASE_ARCHIVE="${RELEASE_ARCHIVE:-}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-https://lastround.online/up}"
GIT_SHA="${GIT_SHA:-unknown}"
PHP_BIN="${PHP_BIN:-}"

ALLOWED_APP_DIR="/home/fitcbfra/lastround-app"
LOCK_DIR=""
STAGING=""
MAINTENANCE_ENABLED=0
DEPLOY_OK=0

log() {
    printf '[deploy] %s\n' "$*"
}

fail() {
    printf '[deploy] ERROR: %s\n' "$*" >&2
    exit 1
}

cleanup() {
    local status=$?

    if [[ "$DEPLOY_OK" -eq 1 ]]; then
        if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
            "$PHP_BIN" artisan up --no-interaction >/dev/null 2>&1 || true
        fi
    elif [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        log "Leaving the application in maintenance mode because deployment failed."
        log "After fixing the issue, run: ${PHP_BIN:-php} artisan up"
    fi

    if [[ -n "$STAGING" && -d "$STAGING" ]]; then
        rm -rf "$STAGING"
    fi

    if [[ -n "$RELEASE_ARCHIVE" && -f "$RELEASE_ARCHIVE" ]]; then
        rm -f "$RELEASE_ARCHIVE"
    fi

    if [[ -n "$LOCK_DIR" && -d "$LOCK_DIR" ]]; then
        rmdir "$LOCK_DIR" 2>/dev/null || true
    fi

    exit "$status"
}

trap cleanup EXIT

[[ -n "$APP_DIR" ]] || fail "DEPLOY_PATH is required."
[[ -n "$RELEASE_ARCHIVE" ]] || fail "RELEASE_ARCHIVE is required."

APP_DIR="${APP_DIR%/}"

if [[ "$APP_DIR" != "$ALLOWED_APP_DIR" ]]; then
    fail "Refusing to deploy outside ${ALLOWED_APP_DIR} (received: ${APP_DIR})."
fi

case "$APP_DIR" in
    *fitcaretta*)
        fail "Refusing to deploy to a fitcaretta path."
        ;;
esac

if [[ "$HEALTHCHECK_URL" != https://lastround.online/* ]]; then
    fail "HEALTHCHECK_URL must be an https://lastround.online URL."
fi

[[ -d "$APP_DIR" ]] || fail "Application directory does not exist: ${APP_DIR}"
[[ -f "$APP_DIR/.env" ]] || fail "Production .env is missing; it will not be created by this deploy."
[[ -f "$RELEASE_ARCHIVE" ]] || fail "Release archive not found: ${RELEASE_ARCHIVE}"

if [[ -z "$PHP_BIN" ]]; then
    if command -v php >/dev/null 2>&1 && php -r 'exit((PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3) ? 0 : 1);'; then
        PHP_BIN="$(command -v php)"
    elif [[ -x /opt/cpanel/ea-php83/root/usr/bin/php ]]; then
        PHP_BIN="/opt/cpanel/ea-php83/root/usr/bin/php"
    else
        fail "PHP 8.3 was not found. Set the PHP_BIN GitHub secret to the server PHP 8.3 binary."
    fi
fi

if [[ "$PHP_BIN" != "php" && ! -x "$PHP_BIN" ]]; then
    fail "PHP binary is not executable: ${PHP_BIN}"
fi

if [[ ! -f "$COMPOSER_BIN" && ! -x "$(command -v "$COMPOSER_BIN" 2>/dev/null || true)" ]]; then
    fail "Composer is not available at ${COMPOSER_BIN}"
fi

php_version="$("$PHP_BIN" -r 'echo PHP_VERSION;')"
"$PHP_BIN" -r 'exit((PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3) ? 0 : 1);' \
    || fail "Expected PHP 8.3, got ${php_version}."

LOCK_DIR="${APP_DIR}/storage/framework/deploy.lock"
mkdir -p "${APP_DIR}/storage/framework" "${HOME}/tmp"
if ! mkdir "$LOCK_DIR" 2>/dev/null; then
    fail "Another deployment is already in progress."
fi

STAGING="$(mktemp -d "${HOME}/tmp/lastround-staging.XXXXXX")"
log "Git SHA: ${GIT_SHA}"
log "PHP: ${PHP_BIN} (${php_version})"
log "Composer: ${COMPOSER_BIN}"
log "Extracting release to staging: ${STAGING}"

tar -xzf "$RELEASE_ARCHIVE" -C "$STAGING"

[[ -f "$STAGING/artisan" ]] || fail "Release archive is missing artisan."
[[ -f "$STAGING/composer.lock" ]] || fail "Release archive is missing composer.lock."
[[ -d "$STAGING/public/build" ]] || fail "Release archive is missing public/build."

rm -f "$STAGING/.env" "$STAGING/.env".* || true
rm -rf "$STAGING/storage" "$STAGING/vendor" "$STAGING/node_modules"
rm -rf "$STAGING/public/.well-known" "$STAGING/public/cgi-bin" "$STAGING/public/storage"

cd "$APP_DIR"

log "Enabling maintenance mode."
"$PHP_BIN" artisan down --retry=60 --no-interaction >/dev/null 2>&1 || true
MAINTENANCE_ENABLED=1

log "Updating application files without deleting preserved paths."
rm -rf "${APP_DIR}/public/build"
mkdir -p "${APP_DIR}/public"
tar -C "$STAGING" -cf - . | tar -C "$APP_DIR" -xf -

chmod +x "${APP_DIR}/artisan" || true

[[ -f "$APP_DIR/.env" ]] || fail "Production .env disappeared during file copy; aborting."
[[ -d "$APP_DIR/public/build" ]] || fail "public/build was not deployed."

log "Installing production Composer dependencies from composer.lock."
COMPOSER_MEMORY_LIMIT=-1 "$PHP_BIN" "$COMPOSER_BIN" install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-ansi \
    --no-progress

log "Running pending migrations."
"$PHP_BIN" artisan migrate --force --no-interaction

log "Refreshing configuration and view caches."
"$PHP_BIN" artisan optimize:clear --no-interaction
"$PHP_BIN" artisan config:cache --no-interaction
"$PHP_BIN" artisan view:cache --no-interaction
"$PHP_BIN" artisan event:cache --no-interaction
# Route caching is skipped because routes/web.php contains closures.

if [[ ! -e "${APP_DIR}/public/storage" ]]; then
    log "Creating the public storage link."
    "$PHP_BIN" artisan storage:link --no-interaction
fi

"$PHP_BIN" artisan queue:restart --no-interaction >/dev/null 2>&1 || true

log "Disabling maintenance mode."
"$PHP_BIN" artisan up --no-interaction
MAINTENANCE_ENABLED=0

log "Verifying health check at ${HEALTHCHECK_URL}"
curl -fsS --retry 5 --retry-delay 2 --max-time 30 "$HEALTHCHECK_URL" >/dev/null
log "Health check passed."

"$PHP_BIN" artisan about --only=environment --no-interaction || true

DEPLOY_OK=1
log "Production deployment completed for ${GIT_SHA}."
