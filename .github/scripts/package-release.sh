#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root"

output="${1:-lastround-release.tar.gz}"

if [[ ! -d public/build ]]; then
    echo "public/build is missing. Run npm ci && npm run build first." >&2
    exit 1
fi

manifest=""
if [[ -f public/build/manifest.json ]]; then
    manifest="public/build/manifest.json"
elif [[ -f public/build/.vite/manifest.json ]]; then
    manifest="public/build/.vite/manifest.json"
else
    echo "Vite manifest was not found in public/build." >&2
    find public/build -maxdepth 3 -type f >&2 || true
    exit 1
fi

if [[ -z "$(find public/build -type f -name '*.css' -print -quit)" ]]; then
    echo "No CSS assets found in public/build." >&2
    exit 1
fi

if [[ -z "$(find public/build -type f -name '*.js' -print -quit)" ]]; then
    echo "No JavaScript assets found in public/build." >&2
    exit 1
fi

if [[ -z "$(find public/build -type f \( -name '*.woff2' -o -name '*.woff' -o -name '*.ttf' -o -name '*.otf' \) -print -quit)" ]]; then
    echo "No font assets found in public/build." >&2
    exit 1
fi

echo "Packaging release from ${root}"
echo "Using Vite manifest: ${manifest}"

tar -czf "$output" \
    --exclude='public/hot' \
    --exclude='public/storage' \
    --exclude='public/build.zip' \
    --exclude='public/.well-known' \
    --exclude='public/cgi-bin' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='database/*.sqlite' \
    --exclude='database/*.sqlite3' \
    --exclude='database/*.sql' \
    app \
    bootstrap \
    config \
    database \
    public \
    resources \
    routes \
    artisan \
    composer.json \
    composer.lock

echo "Created ${output} ($(du -h "$output" | awk '{print $1}'))"

listing="$(tar -tzf "$output")"

fail_if_present() {
    local pattern="$1"
    local message="$2"
    if grep -E "$pattern" <<<"$listing" >/dev/null; then
        echo "$message" >&2
        grep -E "$pattern" <<<"$listing" >&2
        exit 1
    fi
}

fail_if_present '(^|/)\.env$' 'Release archive contains a .env file.'
fail_if_present '(^|/)vendor/' 'Release archive contains vendor files.'
fail_if_present '(^|/)node_modules/' 'Release archive contains node_modules.'
fail_if_present '(^|/)\.git/' 'Release archive contains .git data.'
fail_if_present '\.sqlite3?$' 'Release archive contains a SQLite database.'
fail_if_present '(^|/)storage/' 'Release archive contains storage files.'

if ! grep -Eq '(^|/)public/build/.+' <<<"$listing"; then
    echo "Release archive is missing public/build assets." >&2
    exit 1
fi

if ! grep -Eq '(^|/)(public/build/manifest\.json|public/build/\.vite/manifest\.json)$' <<<"$listing"; then
    echo "Release archive is missing the Vite manifest." >&2
    exit 1
fi

echo "Release archive contents look valid."
