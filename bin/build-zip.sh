#!/usr/bin/env bash
#
# Builds the installable module zip that is sent to merchants.
#
#   bin/build-zip.sh [git-ref]      (default: HEAD)
#
# Output: dist/paylands-<version>.zip, containing a single "paylands/" folder.
#
# The zip is always built from a clean export of the git ref (never from a working
# folder), so local files such as logs/*.log, .idea or test shops can never leak
# into it. Uncommitted changes are NOT included: commit before building a release.
#
set -euo pipefail

MODULE=paylands
REF="${1:-HEAD}"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

fail() { echo "ERROR: $*" >&2; exit 1; }

for cmd in git composer zip unzip tar; do
    command -v "$cmd" >/dev/null 2>&1 || fail "'$cmd' is required"
done

git rev-parse --verify --quiet "$REF^{commit}" >/dev/null || fail "unknown git ref: $REF"

if [ "$REF" = "HEAD" ] && [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "WARNING: there are uncommitted changes; they will NOT be in the zip (building $REF)." >&2
fi

VERSION="$(git show "$REF:$MODULE.php" \
    | sed -n "s/.*\$this->version *= *['\"]\([^'\"]*\)['\"].*/\1/p" | head -n1)"
[ -n "$VERSION" ] || fail "could not read the module version from $MODULE.php"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
BUILD="$WORK/$MODULE"

echo "Building $MODULE $VERSION from $REF ($(git rev-parse --short "$REF"))"

# 1. Clean export of the repository (honours export-ignore in .gitattributes).
git archive --format=tar --prefix="$MODULE/" "$REF" | tar -x -C "$WORK"

# 2. Production dependencies only: dev tools (phpunit...) must never reach a shop.
composer install --working-dir="$BUILD" --no-dev --prefer-dist --optimize-autoloader \
    --no-interaction --no-progress --quiet

# 3. Remove everything that is not needed to run the module.
#    (explicit list as well, in case .gitattributes is missing in an old ref)
rm -rf "$BUILD/tests" "$BUILD/bin" "$BUILD/dist" "$BUILD/.idea" "$BUILD/.github" \
       "$BUILD/phpunit.xml" "$BUILD/phpunit.xml.dist" "$BUILD/.phpunit.result.cache" "$BUILD/.phpunit.cache" \
       "$BUILD/.gitignore" "$BUILD/.gitattributes"
find "$BUILD/logs" -type f ! -name 'index.php' ! -name '.htaccess' -delete
find "$BUILD/vendor" -mindepth 3 -maxdepth 3 -type d \( -name tests -o -name test -o -name Tests \) -prune -exec rm -rf {} +
find "$BUILD/vendor" -mindepth 3 -maxdepth 3 -type f \
    \( -name 'phpunit.xml*' -o -name '.gitignore' -o -name '.travis.yml' \) -delete

# 4. vendor/ must never be served over HTTP.
cat > "$BUILD/vendor/.htaccess" <<'EOF'
# Apache 2.2
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>

# Apache 2.4
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
EOF

# 5. Zip (no macOS metadata, no extra attributes).
mkdir -p "$ROOT/dist"
ZIP="$ROOT/dist/$MODULE-$VERSION.zip"
rm -f "$ZIP"
(cd "$WORK" && zip -rqX "$ZIP" "$MODULE")

# 6. Safety checks on the final zip: abort if anything forbidden slipped in.
LISTING="$(unzip -Z1 "$ZIP")"
forbidden="$(printf '%s\n' "$LISTING" | grep -E \
    -e "^$MODULE/logs/.*\.log$" \
    -e "^$MODULE/tests/" \
    -e "^$MODULE/vendor/(phpunit|bin)/" \
    -e "(^|/)eval-stdin\.php$" \
    -e "(^|/)(\.git|\.idea|__MACOSX)(/|$)" \
    -e "(^|/)\.DS_Store$" || true)"
if [ -n "$forbidden" ]; then
    rm -f "$ZIP"
    fail "forbidden files found in the zip (zip deleted):
$forbidden"
fi

top="$(printf '%s\n' "$LISTING" | cut -d/ -f1 | sort -u)"
[ "$top" = "$MODULE" ] || { rm -f "$ZIP"; fail "the zip must contain only the '$MODULE/' folder, found: $top"; }

for required in "$MODULE/$MODULE.php" "$MODULE/vendor/autoload.php" "$MODULE/vendor/.htaccess" "$MODULE/logs/.htaccess"; do
    printf '%s\n' "$LISTING" | grep -qxF "$required" || { rm -f "$ZIP"; fail "missing $required in the zip"; }
done

echo "OK: $ZIP ($(printf '%s\n' "$LISTING" | grep -vc '/$') files)"
