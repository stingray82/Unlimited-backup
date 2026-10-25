#!/usr/bin/env bash
set -euo pipefail
script_dir="$(cd -- "$(dirname -- "$0")" && pwd -P)"
cd "$script_dir"
[[ "${1:-}" != "--list-targets" ]] || { printf "%s\n" github github+local local woo edd fluent hoster dlm surecart github+woo github+edd github+fluent github+hoster github+dlm github+surecart local+woo local+edd local+fluent local+hoster local+dlm local+surecart; exit 0; }
[[ -f deploy.cfg ]] || { echo 'Missing deploy.cfg' >&2; exit 1; }
# Simple KEY=VALUE parser; no eval or shell execution from the config.
while IFS= read -r line || [[ -n "$line" ]]; do
  line="${line%$'\r'}"
  [[ "$line" =~ ^[[:space:]]*($|#|\;) ]] && continue
  [[ "$line" == *=* ]] || continue
  key="${line%%=*}"; value="${line#*=}"
  key="$(printf '%s' "$key" | xargs)"
  [[ "$key" =~ ^[A-Z][A-Z0-9_]*$ ]] || continue
  case "$key" in
    UNIVERSAL_HOME|GITHUB_CREDENTIAL_PROFILE|SURECART_CREDENTIAL_PROFILE|WOO_CREDENTIAL_PROFILE|EDD_CREDENTIAL_PROFILE|FLUENT_CREDENTIAL_PROFILE|HOSTER_CREDENTIAL_PROFILE|DLM_CREDENTIAL_PROFILE|PLUGIN_NAME|PLUGIN_SLUG|PLUGIN_DIR|PLUGIN_FILE|ZIP_NAME|PLUGIN_TAGS|CHANGELOG_FILE|STATIC_FILE|CLEANUP_RELEASE_ZIPS|DEPLOY_TARGET|GITHUB_REPO|TOKEN_FILE|DEST_DIR|HEADER_SCRIPT|GENERATOR_SCRIPT|WOO_CREDENTIALS_FILE|WOO_STORE_URL|WOO_SOFTWARE_SLUG|WOO_DOWNLOAD_MODE|WOO_RELEASE_SCOPE|WOO_GRANT_EXISTING|EDD_STORE_URL|EDD_SOFTWARE_SLUG|EDD_DOWNLOAD_ID|EDD_DOWNLOAD_MODE|EDD_CREDENTIALS_FILE|FLUENT_CREDENTIALS_FILE|FLUENT_STORE_URL|FLUENT_SOFTWARE_SLUG|FLUENT_DOWNLOAD_MODE|SURECART_DEPLOY_PHP|SURECART_JSON_FILE|SURECART_BACKUP_JSON|SURECART_FAQ_FILE|SURECART_SKIP_JSON|DEPLOY_DRY_RUN|SURECART_CREDENTIALS_FILE|SURECART_STORE_URL|SURECART_SOFTWARE_SLUG|SURECART_PRODUCT_ID|SURECART_DOWNLOAD_MODE|HOSTER_CREDENTIALS_FILE|HOSTER_STORE_URL|HOSTER_SOFTWARE_SLUG|DLM_CREDENTIALS_FILE|DLM_STORE_URL|DLM_SOFTWARE_ID|DLM_CHANNEL|DLM_NOTIFY|DLM_API_KEY|DLM_CONSUMER_KEY|DLM_CONSUMER_SECRET|SEVEN_ZIP|GIT_PUSH_SOURCE|GIT_BRANCH)
      value="${value#\"}"; value="${value%\"}"
      printf -v "$key" '%s' "$value" ;;
  esac
done < deploy.cfg
# Convert Windows drive paths to Git Bash mount paths when needed.
resolve_path() {
  local p="$1"
  if [[ "$p" =~ ^([A-Za-z]):[\\/](.*)$ ]]; then
    local drive="${BASH_REMATCH[1],,}" tail="${BASH_REMATCH[2]}"
    tail="${tail//\\//}"
    p="/$drive/$tail"
  fi
  printf '%s' "$p"
}
# Relative file locations are resolved from the directory containing deploy.sh.
file_path() {
  local p; p="$(resolve_path "$1")"
  if [[ "$p" != /* ]]; then p="$script_dir/$p"; fi
  printf '%s' "$p"
}

# Repo paths are relative to deploy.sh. Shared paths default to UNIVERSAL_HOME.
# The central folder is outside all Git repositories. A per-field CFG override wins.
UNIVERSAL_HOME="$(file_path "${UNIVERSAL_HOME:-C:/Ignore By Avast/0. PATHED Items/Plugins/deployscripts/universal}")"
shared_path() {
  local override="$1" fallback="$2" path
  if [[ -n "$override" ]]; then
    path="$(resolve_path "$override")"
    # Explicit relative overrides are relative to the repository, for compatibility.
    [[ "$path" == /* ]] || path="$script_dir/$path"
  else
    path="$UNIVERSAL_HOME/$fallback"
  fi
  printf '%s' "$path"
}
HEADER_SCRIPT="$(shared_path "${HEADER_SCRIPT:-}" 'tools/myplugin_headers_rup_gpl3.0.php')"
GENERATOR_SCRIPT="$(shared_path "${GENERATOR_SCRIPT:-}" 'tools/generate_index.php')"
SURECART_DEPLOY_PHP="$(shared_path "${SURECART_DEPLOY_PHP:-}" 'tools/deploy.php')"
# Profiles are simple names, not paths. Profile files live under
# UNIVERSAL_HOME/credentials/<provider>/<profile>.env (GitHub: .token).
# Explicit *_CREDENTIALS_FILE and TOKEN_FILE overrides always win.
profile_file() {
  local provider="$1" profile="$2" suffix="$3"
  [[ "$profile" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]*$ ]] || {
    echo "[ERROR] Invalid ${provider} credentials profile (letters, numbers, _, ., - only)" >&2; exit 1;
  }
  printf '%s/credentials/%s/%s.%s' "$UNIVERSAL_HOME" "$provider" "$profile" "$suffix"
}
credential_path() {
  local override="$1" provider="$2" profile="$3" legacy="$4" extension="$5"
  if [[ -n "$override" ]]; then shared_path "$override" "credentials/$legacy"
  elif [[ -n "$profile" ]]; then profile_file "$provider" "$profile" "$extension"
  else shared_path '' "credentials/$legacy"
  fi
}
TOKEN_FILE="$(credential_path "${TOKEN_FILE:-}" github "${GITHUB_CREDENTIAL_PROFILE:-}" github_token.txt token)"
load_credentials() {
  local provider="$1" key="$2" profile_key="$3" legacy="$4" profile="${!3:-}" path
  path="$(credential_path "${!key:-}" "$provider" "$profile" "$legacy" env)"
  [[ -f "$path" ]] || { echo "[ERROR] Missing $provider credentials: $path" >&2; exit 1; }
  echo "[INFO] Using $provider credentials: $path"
  # Trusted Bash env files stored outside repositories.
  source "$path"
}
# Support either spelling from the older instructions.
WOO_USER="${WOO_USER:-${WOO_USERNAME:-}}"

: "${PLUGIN_SLUG:?Set PLUGIN_SLUG}"
DEPLOY_TARGET="${DEPLOY_TARGET:-woo}"
DEPLOY_TARGET="${DEPLOY_TARGET,,}"
DEPLOY_TARGET="${DEPLOY_TARGET//github_dlm/github+dlm}"
# For SureCart, choose a versioned archive name AFTER deploy.php updates the header.
ZIP_NAME="${ZIP_NAME:-}"
CHANGELOG_FILE="${CHANGELOG_FILE:-changelog.txt}"
STATIC_FILE="${STATIC_FILE:-static.txt}"
PLUGIN_NAME="${PLUGIN_NAME:-$PLUGIN_SLUG}"
PLUGIN_DIR="${PLUGIN_DIR:-$PLUGIN_SLUG}"
plugin_dir="$(file_path "$PLUGIN_DIR")"
plugin_file="$(file_path "${PLUGIN_FILE:-$PLUGIN_DIR/$PLUGIN_SLUG.php}")"
CHANGELOG_FILE="$(file_path "$CHANGELOG_FILE")"
STATIC_FILE="$(file_path "$STATIC_FILE")"
[[ -f "$plugin_file" ]] || { echo "Missing $plugin_file" >&2; exit 1; }
[[ -f "$CHANGELOG_FILE" ]] || { echo "Missing $CHANGELOG_FILE" >&2; exit 1; }
case "$DEPLOY_TARGET" in github|github+local|local|woo|edd|fluent|hoster|dlm|surecart|github+woo|github+edd|github+fluent|github+hoster|github+dlm|github+surecart|local+woo|local+edd|local+fluent|local+hoster|local+dlm|local+surecart) ;; *) echo 'Invalid DEPLOY_TARGET' >&2; exit 1;; esac
# Check github+local settings before any remote upload.
if [[ "$DEPLOY_TARGET" == github+local && -z "${DEST_DIR:-}" ]]; then
  echo '[ERROR] github+local requires DEST_DIR in deploy.cfg' >&2; exit 1
fi
# Dry runs need no remote credentials. Live runs load only chosen providers.
if [[ "${DEPLOY_DRY_RUN:-0}" != 1 ]]; then
  [[ "$DEPLOY_TARGET" != *woo* ]] || load_credentials woo WOO_CREDENTIALS_FILE WOO_CREDENTIAL_PROFILE woo.env
  [[ "$DEPLOY_TARGET" != *edd* ]] || load_credentials edd EDD_CREDENTIALS_FILE EDD_CREDENTIAL_PROFILE edd.env
  [[ "$DEPLOY_TARGET" != *fluent* ]] || load_credentials fluent FLUENT_CREDENTIALS_FILE FLUENT_CREDENTIAL_PROFILE fluent.env
  [[ "$DEPLOY_TARGET" != *hoster* ]] || load_credentials hoster HOSTER_CREDENTIALS_FILE HOSTER_CREDENTIAL_PROFILE hoster.env
  [[ "$DEPLOY_TARGET" != *dlm* ]] || load_credentials dlm DLM_CREDENTIALS_FILE DLM_CREDENTIAL_PROFILE dlm.env
  [[ "$DEPLOY_TARGET" != *surecart* ]] || load_credentials surecart SURECART_CREDENTIALS_FILE SURECART_CREDENTIAL_PROFILE surecart.env
  if [[ "$DEPLOY_TARGET" == *github* ]]; then
    [[ -f "$TOKEN_FILE" ]] || { echo "[ERROR] Missing GitHub token file: $TOKEN_FILE" >&2; exit 1; }
    echo "[INFO] Using GitHub token: $TOKEN_FILE"
  fi
fi
WOO_USER="${WOO_USER:-${WOO_USERNAME:-}}"

# Preflight DLM authentication before any publication (including GitHub combinations).
if [[ "$DEPLOY_DRY_RUN" != 1 && "$DEPLOY_TARGET" == *dlm* ]]; then
  [[ -n "${DLM_CONSUMER_KEY:-}" && -n "${DLM_CONSUMER_SECRET:-}" ]] || {
    echo '[ERROR] DLM requires DLM_CONSUMER_KEY and DLM_CONSUMER_SECRET in its selected credentials profile.' >&2
    exit 1
  }
fi
# Offline dry run avoids remote actions, including GitHub uploads.
DEPLOY_DRY_RUN="${DEPLOY_DRY_RUN:-0}"
[[ "$DEPLOY_DRY_RUN" == 0 || "$DEPLOY_DRY_RUN" == 1 ]] || { echo '[ERROR] DEPLOY_DRY_RUN must be 0 or 1' >&2; exit 1; }
if [[ "$DEPLOY_DRY_RUN" == 1 ]]; then
  echo '[INFO] BUILD-ONLY MODE: no remote providers will be called.'
fi
# SureCart's existing deployment PHP is the source of truth for plugin headers,
# release.json creation, backup and optional FAQ. Do not run two header scripts.
if [[ "$DEPLOY_TARGET" == *surecart* ]]; then
  surecart_deploy_php="$SURECART_DEPLOY_PHP"
  [[ -f "$surecart_deploy_php" ]] || { echo "[ERROR] SureCart deploy.php not found: $surecart_deploy_php" >&2; exit 1; }
  surecart_skip="${SURECART_SKIP_JSON:-0}"
  if [[ "$surecart_skip" == 1 ]]; then
    echo '[INFO] SureCart: updating headers only (--skip-json)'
    php -f "$surecart_deploy_php" --skip-json "$plugin_file"
  else
    : "${SURECART_JSON_FILE:?Set SURECART_JSON_FILE}"
    : "${SURECART_BACKUP_JSON:?Set SURECART_BACKUP_JSON}"
    surecart_json="$(file_path "$SURECART_JSON_FILE")"
    surecart_backup="$(file_path "$SURECART_BACKUP_JSON")"
    surecart_faq=""
    [[ -z "${SURECART_FAQ_FILE:-}" ]] || surecart_faq="$(file_path "$SURECART_FAQ_FILE")"
    [[ -s "$surecart_json" ]] || { echo "[ERROR] Source release.json missing or empty: $surecart_json" >&2; exit 1; }
    [[ -s "$CHANGELOG_FILE" ]] || { echo "[ERROR] Changelog missing or empty: $CHANGELOG_FILE" >&2; exit 1; }
    [[ -f "$plugin_file" ]] || { echo "[ERROR] Plugin PHP missing: $plugin_file" >&2; exit 1; }
    mkdir -p "$(dirname "$surecart_json")" "$(dirname "$surecart_backup")"
    echo "[INFO] SureCart: running bundled deploy.php (headers, JSON, backup, changelog)"
    if [[ -n "$surecart_faq" ]]; then
      [[ -f "$surecart_faq" ]] || { echo "[ERROR] FAQ file not found: $surecart_faq" >&2; exit 1; }
      php -f "$surecart_deploy_php" "$surecart_json" "$surecart_backup" "$CHANGELOG_FILE" "$plugin_file" "$surecart_faq"
    else
      php -f "$surecart_deploy_php" "$surecart_json" "$surecart_backup" "$CHANGELOG_FILE" "$plugin_file"
    fi
    [[ -s "$surecart_json" ]] || { echo "[ERROR] SureCart JSON missing/empty after deploy.php: $surecart_json" >&2; exit 1; }
    php -r '$j=json_decode(file_get_contents($argv[1]),true); if (!is_array($j)) {fwrite(STDERR,"[ERROR] Invalid release.json\n");exit(1);} ' "$surecart_json"
    [[ -s "$surecart_backup" ]] || { echo "[ERROR] SureCart backup JSON missing/empty: $surecart_backup" >&2; exit 1; }

    # Ensure the file to be shipped is inside the plugin directory.
    case "$surecart_json" in "$plugin_dir"/*) ;; *)
      echo '[ERROR] SURECART_JSON_FILE must be within PLUGIN_DIR so the ZIP includes it.' >&2; exit 1;; esac
  fi
else
  if [[ -f "$HEADER_SCRIPT" ]]; then
    if [[ -t 0 ]]; then
      echo "[INFO] Running shared header script: $HEADER_SCRIPT"
      php "$HEADER_SCRIPT" "$plugin_file"
    else
      echo '[INFO] Non-interactive session: skipping interactive header updater.'
    fi
  else
    echo "[ERROR] Header updater not found: $HEADER_SCRIPT" >&2
    exit 1
  fi
fi
version="$(sed -nE 's/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*([^[:space:]*]+).*/\1/p' "$plugin_file" | head -1)"
[[ -n "$version" ]] || { echo 'Plugin header Version: is required' >&2; exit 1; }
if [[ "$DEPLOY_TARGET" == *surecart* && "${SURECART_SKIP_JSON:-0}" != 1 ]]; then
  php -r '$j=json_decode(file_get_contents($argv[1]),true); if (!is_array($j) || (string)($j["version"]??"")!==$argv[2] || !isset($j["sections"]["changelog"]) || !is_string($j["sections"]["changelog"])) {fwrite(STDERR,"[ERROR] release.json version/changelog mismatch\n");exit(1);} ' "$surecart_json" "$version"
  echo "[OK] SureCart metadata validated: version $version"
fi
if [[ -z "$ZIP_NAME" ]]; then
  ZIP_NAME="${PLUGIN_SLUG}-${version}.zip"
fi
requires="$(sed -nE 's/^[[:space:]]*\*?[[:space:]]*Requires at least:[[:space:]]*(.*)/\1/p' "$plugin_file" | head -1)"
tested="$(sed -nE 's/^[[:space:]]*\*?[[:space:]]*Tested up to:[[:space:]]*(.*)/\1/p' "$plugin_file" | head -1)"
requires_php="$(sed -nE 's/^[[:space:]]*\*?[[:space:]]*Requires PHP:[[:space:]]*(.*)/\1/p' "$plugin_file" | head -1)"
if [[ -f "$STATIC_FILE" ]]; then
  { printf '=== %s ===\nTags: %s\nRequires at least: %s\nTested up to: %s\nStable tag: %s\nRequires PHP: %s\n\n' "$PLUGIN_NAME" "${PLUGIN_TAGS:-}" "$requires" "$tested" "$version" "$requires_php"
    cat "$STATIC_FILE"; printf '\n== Changelog ==\n'; cat "$CHANGELOG_FILE"; } > "$plugin_dir/readme.txt"
fi
# Index metadata is needed for the GitHub/static updater workflow, not Woo bridge.
if [[ "$DEPLOY_TARGET" == *github* && -f "$STATIC_FILE" ]]; then
  [[ -f "$GENERATOR_SCRIPT" ]] || { echo "Missing index generator: $GENERATOR_SCRIPT" >&2; exit 1; }
  : "${GITHUB_REPO:?Set GITHUB_REPO}"
  owner="${GITHUB_REPO%%/*}"; repo="${GITHUB_REPO#*/}"
  mkdir -p "$script_dir/uupd"
  php "$GENERATOR_SCRIPT" "$plugin_file" "$CHANGELOG_FILE" "$script_dir/uupd" "$owner" "https://raw.githubusercontent.com/$GITHUB_REPO/main/uupd" "$PLUGIN_SLUG" "$repo" "$STATIC_FILE" "$ZIP_NAME"
elif [[ "$DEPLOY_TARGET" == *github* ]]; then
  if [[ "$DEPLOY_TARGET" == github+surecart ]]; then
    echo '[INFO] static.txt absent: skipping optional GitHub updater index generation (SureCart uses release.json).'
  else
    echo "[ERROR] Missing static file: $STATIC_FILE (required for GitHub updater metadata)" >&2; exit 1
  fi
fi
zip_path="$script_dir/$ZIP_NAME"
rm -f "$zip_path"
SEVEN_ZIP="$(resolve_path "${SEVEN_ZIP:-C:/Program Files/7-Zip/7z.exe}")"
if command -v zip >/dev/null 2>&1; then
  zip -qr "$zip_path" "$PLUGIN_DIR" -x '*/.git/*' '*/.github/*'
elif command -v 7z >/dev/null 2>&1; then
  7z a -tzip "$zip_path" "$PLUGIN_DIR" -xr!.git -xr!.github >/dev/null
elif command -v 7z.exe >/dev/null 2>&1; then
  7z.exe a -tzip "$zip_path" "$PLUGIN_DIR" -xr!.git -xr!.github >/dev/null
elif [[ -x "$SEVEN_ZIP" ]]; then
  "$SEVEN_ZIP" a -tzip "$zip_path" "$PLUGIN_DIR" -xr!.git -xr!.github >/dev/null
else
  echo "[ERROR] Missing zip utility. Checked zip, 7z, 7z.exe and $SEVEN_ZIP" >&2; exit 1
fi
[[ -s "$zip_path" ]] || { echo 'ZIP packaging failed' >&2; exit 1; }
# Dry-run stops before any provider-specific HTTP or GitHub changes.
# Confirm the SureCart release metadata is present INSIDE the archive.
if [[ "$DEPLOY_TARGET" == *surecart* && "${SURECART_SKIP_JSON:-0}" != 1 ]]; then
  if command -v unzip >/dev/null 2>&1; then
    expected="${PLUGIN_DIR%/}/$(basename "$surecart_json")"
    unzip -Z1 "$zip_path" | grep -Fxq "$expected" || { echo "[ERROR] ZIP lacks $expected" >&2; exit 1; }
    echo '[OK] release.json found in ZIP.'
  else
    echo '[WARN] unzip unavailable; unable to check archive contents.'
  fi
fi
if [[ "$DEPLOY_DRY_RUN" == 1 ]]; then
  echo "[OK] Dry run complete: $zip_path; no external releases published."
  exit 0
fi
case "$DEPLOY_TARGET" in
  local|local+woo|local+edd|local+fluent|local+hoster|local+dlm|local+surecart)
    DEST_DIR="$(file_path "${DEST_DIR:-dist}")"; mkdir -p "$DEST_DIR"; cp "$zip_path" "$DEST_DIR/"; echo "[OK] Local: $DEST_DIR" ;;
esac
if [[ "$DEPLOY_TARGET" == *github* ]]; then
  # Source pushes operate on the repository containing deploy.sh, not PLUGIN_SLUG.
  # Accept yes/true/1; reject typos rather than quietly skipping the push.
  case "${GIT_PUSH_SOURCE:-0}" in
    1|yes|true|on) git_push_enabled=1 ;;
    0|no|false|off|'') git_push_enabled=0 ;;
    *) echo "[ERROR] Invalid GIT_PUSH_SOURCE: ${GIT_PUSH_SOURCE} (use 1 or 0)" >&2; exit 1 ;;
  esac
  if [[ "$git_push_enabled" == 1 ]]; then
    repo_root="$(git -C "$script_dir" rev-parse --show-toplevel 2>/dev/null)" || {
      echo "[ERROR] GIT_PUSH_SOURCE=1 but deploy.sh is not inside a Git repository." >&2; exit 1;
    }
    # Do not silently commit a parent/other repository's files.
    if [[ "$(cd "$repo_root" && pwd -P)" != "$script_dir" ]]; then
      echo "[ERROR] Expected Git repository root at $script_dir; found $repo_root" >&2; exit 1
    fi
    git_branch="${GIT_BRANCH:-main}"
    [[ "$(git -C "$repo_root" branch --show-current)" == "$git_branch" ]] || {
      echo "[ERROR] Current Git branch is not $git_branch; refusing source push." >&2; exit 1;
    }
    git -C "$repo_root" remote get-url origin >/dev/null || {
      echo '[ERROR] Git remote origin is missing.' >&2; exit 1;
    }
    echo "[INFO] Git: staging all tracked, modified and untracked nonignored files in $repo_root"
    git -C "$repo_root" add -A
    if ! git -C "$repo_root" diff --cached --quiet; then
      echo "[INFO] Git: committing source changes for version $version..."
      git -C "$repo_root" commit -m "Version $version Release" || {
        echo '[ERROR] Git commit failed; deployment stopped.' >&2; exit 1;
      }
    else
      echo '[INFO] Git: no source changes to commit.'
    fi
    echo "[INFO] Git: pushing $git_branch to origin..."
    git -C "$repo_root" push origin "$git_branch" || {
      echo '[ERROR] Git push failed; deployment stopped before publishing releases.' >&2; exit 1;
    }
    echo '[OK] Git source branch pushed.'
  else
    echo '[INFO] Git source push disabled (GIT_PUSH_SOURCE=0).'
  fi
  : "${GITHUB_REPO:?Set GITHUB_REPO}"
  version="${version//$'\r'/}"
  version="${version//$'\n'/}"
  GITHUB_REPO="${GITHUB_REPO//$'\n'/}"
  GITHUB_REPO="${GITHUB_REPO//[[:space:]]/}"
  # Accept either owner/repo or a GitHub repository URL. Strip whitespace
  # (including accidental Windows CR) and reject unusable values early.
  GITHUB_REPO="${GITHUB_REPO//$'\r'/}"
  GITHUB_REPO="${GITHUB_REPO#https://github.com/}"
  GITHUB_REPO="${GITHUB_REPO#http://github.com/}"
  GITHUB_REPO="${GITHUB_REPO#github.com/}"
  GITHUB_REPO="${GITHUB_REPO%/}"
  GITHUB_REPO="${GITHUB_REPO%.git}"
  if [[ ! "$GITHUB_REPO" =~ ^[a-zA-Z0-9_.-]+/[a-zA-Z0-9_.-]+$ ]]; then
    echo "[ERROR] Invalid GITHUB_REPO: '$GITHUB_REPO'" >&2
    echo '[HINT] Set GITHUB_REPO=owner/repository (for example stingray82/test-update)' >&2
    exit 1
  fi
  if [[ -f "$TOKEN_FILE" ]]; then GITHUB_TOKEN="$(tr -d '\r\n' < "$TOKEN_FILE")"; fi
  : "${GITHUB_TOKEN:?Set GITHUB_TOKEN or TOKEN_FILE for GitHub deployment}"
  # Windows Git Bash often exposes Python through the py launcher rather than python3.
  # Use an array so multi-part commands such as `py -3` are invoked safely.
  if command -v py >/dev/null 2>&1 && py -3 -c 'import sys; sys.exit(sys.version_info[0] != 3)' >/dev/null 2>&1; then
    PYTHON_CMD=(py -3)
  elif command -v python3 >/dev/null 2>&1 && python3 -c 'import sys; sys.exit(sys.version_info[0] != 3)' >/dev/null 2>&1; then
    PYTHON_CMD=(python3)
  elif command -v python >/dev/null 2>&1 && python -c 'import sys; sys.exit(sys.version_info[0] != 3)' >/dev/null 2>&1; then
    PYTHON_CMD=(python)
  else
    echo '[ERROR] Python 3 not found. On Windows, check that `py -3` works.' >&2
    exit 1
  fi
  echo "[INFO] Using Python: ${PYTHON_CMD[*]}"
  echo '[INFO] Creating/replacing GitHub release...'
  tmp="$(mktemp -d)"; trap 'rm -rf "$tmp"' EXIT
  "${PYTHON_CMD[@]}" - "$version" "$CHANGELOG_FILE" "$tmp/body.json" <<'PY'
import json,sys
version,changelog,out=sys.argv[1:]
with open(changelog,encoding='utf8') as f: body=f.read()
with open(out,'w',encoding='utf8') as f: json.dump({'tag_name':'v'+version,'name':version,'body':body,'draft':False,'prerelease':False},f)
PY
  api="https://api.github.com/repos/$GITHUB_REPO/releases"
  echo "[INFO] GitHub repository: $GITHUB_REPO"
  echo "[INFO] GitHub release endpoint: $api/tags/v$version"
  # Distinguish a malformed URL/transport error from a valid 404 (no tag yet).
  if ! code="$(curl --globoff -sS -o "$tmp/existing.json" -w '%{http_code}' \
    -H "Authorization: Bearer $GITHUB_TOKEN" \
    -H 'Accept: application/vnd.github+json' \
    "$api/tags/v$version")"; then
    echo '[ERROR] GitHub tag lookup failed before a valid HTTP response was received.' >&2
    echo '[HINT] Check GITHUB_REPO and run: type -a curl' >&2
    exit 1
  fi
  case "$code" in
    200|404) ;;
    *) echo "[ERROR] GitHub tag lookup returned HTTP $code" >&2; exit 1 ;;
  esac
  if [[ "$code" == 200 ]]; then
    id="$("${PYTHON_CMD[@]}" -c 'import json,sys;print(json.load(open(sys.argv[1], encoding="utf-8"))["id"])' "$tmp/existing.json")"
    echo '[INFO] GitHub: updating existing release metadata...'
    curl --globoff -fsS -X PATCH -H "Authorization: Bearer $GITHUB_TOKEN" -H 'Accept: application/vnd.github+json' -H 'Content-Type: application/json' --data-binary @"$tmp/body.json" "$api/$id" > "$tmp/release.json"
  else
    echo '[INFO] GitHub: creating release metadata...'
    curl --globoff -fsS -X POST -H "Authorization: Bearer $GITHUB_TOKEN" -H 'Accept: application/vnd.github+json' -H 'Content-Type: application/json' --data-binary @"$tmp/body.json" "$api" > "$tmp/release.json"
  fi
  id="$("${PYTHON_CMD[@]}" -c 'import json,sys;print(json.load(open(sys.argv[1], encoding="utf-8"))["id"])' "$tmp/release.json")"
  [[ "$id" =~ ^[0-9]+$ ]] || { echo '[ERROR] GitHub returned invalid release ID' >&2; exit 1; }
  # Delete matching old asset if release already existed (GitHub rejects duplicate names).
  echo "[INFO] GitHub: retrieving assets for release ID $id..."
  curl --globoff -fsS -H "Authorization: Bearer $GITHUB_TOKEN" -H 'Accept: application/vnd.github+json' "$api/$id/assets" > "$tmp/assets.json"
  "${PYTHON_CMD[@]}" - "$tmp/assets.json" "$(basename "$zip_path")" > "$tmp/delete-ids" <<'PY'
import json,sys
for asset in json.load(open(sys.argv[1], encoding="utf-8")):
 if asset.get('name')==sys.argv[2]:print(asset['id'])
PY
  while IFS= read -r oldid || [[ -n "$oldid" ]]; do
    # Python on Windows can emit CRLF; a trailing CR makes curl reject the URL.
    oldid="${oldid//$'\r'/}"
    oldid="${oldid//$'\n'/}"
    [[ -z "$oldid" ]] && continue
    [[ "$oldid" =~ ^[0-9]+$ ]] || { echo "[ERROR] Unsafe GitHub asset ID returned: $oldid" >&2; exit 1; }
    echo "[INFO] GitHub: removing old asset ID $oldid..."
    curl --globoff -fsS -X DELETE -H "Authorization: Bearer $GITHUB_TOKEN" "$api/assets/$oldid" >/dev/null
  done < "$tmp/delete-ids"
  echo '[INFO] GitHub: uploading ZIP asset...'
  curl --globoff -fsS -X POST -H "Authorization: Bearer $GITHUB_TOKEN" -H 'Content-Type: application/zip' --data-binary @"$zip_path" "https://uploads.github.com/repos/$GITHUB_REPO/releases/$id/assets?name=$(basename "$zip_path")" > "$tmp/asset.json"
  echo '[OK] GitHub release published.'
fi
# For github+local, copy only after GitHub has successfully published its ZIP.
# Using a temporary file and rename avoids SyncBack observing a partially copied archive.
if [[ "$DEPLOY_TARGET" == github+local ]]; then
  [[ -n "${DEST_DIR:-}" ]] || { echo '[ERROR] github+local requires DEST_DIR in deploy.cfg' >&2; exit 1; }
  local_destination="$(file_path "$DEST_DIR")"
  mkdir -p "$local_destination" || { echo "[ERROR] Cannot create DEST_DIR: $local_destination" >&2; exit 1; }
  local_filename="$(basename "$zip_path")"
  local_target="$local_destination/$local_filename"
  # Avoid inadvertently copying a ZIP onto itself.
  if [[ "$(cd "$local_destination" && pwd -P)/$local_filename" == "$zip_path" ]]; then
    echo '[INFO] Local ZIP already resides in DEST_DIR; no copy needed.'
  else
    local_tmp="$(mktemp "$local_destination/.${local_filename}.tmp.XXXXXXXX")" || { echo '[ERROR] Cannot create temporary file in DEST_DIR' >&2; exit 1; }
    cp -p -- "$zip_path" "$local_tmp" || { rm -f -- "$local_tmp"; echo '[ERROR] Local release copy failed' >&2; exit 1; }
    mv -f -- "$local_tmp" "$local_target" || { rm -f -- "$local_tmp"; echo '[ERROR] Local release rename failed' >&2; exit 1; }
    echo "[OK] Local release copied: $local_target"
  fi
fi
# Use the same release API request for Stackless, native UUPD Server and EDD adapter.
# EDD differs only in its product identification and settings.
release_upload() {
  local target="$1" store="$2" slug="$3" mode="$4" user="$5" password="$6" download_id="${7:-}"
  [[ -n "$store" && -n "$slug" && -n "$user" && -n "$password" ]] || { echo "[ERROR] $target store/slug/credentials missing" >&2; exit 1; }
  [[ "$mode" == replace || "$mode" == append ]] || { echo "[ERROR] $target download mode must be replace or append" >&2; exit 1; }
  local curl_zip_path="$zip_path"
  if [[ -n "${MSYSTEM:-}" ]] && command -v cygpath >/dev/null 2>&1; then curl_zip_path="$(cygpath -m "$zip_path")"; fi
  [[ -r "$zip_path" && -s "$zip_path" ]] || { echo "[ERROR] Missing ZIP: $zip_path" >&2; exit 1; }
  local url="${store%/}/wp-json/uupd/v1/products/$slug/release"
  echo "[INFO] Publishing $target release $slug $version ($mode)..."
  echo "[INFO] Package: $curl_zip_path"
  local extra_args=()
  if [[ -n "$download_id" ]]; then extra_args=(-F "download_id=$download_id"); fi
  if [[ "$target" == SureCart ]]; then extra_args=(-F "product_id=$SURECART_PRODUCT_ID"); fi
  # Optional Stackless variation release settings. These are only supported by
  # the Woo bridge; the server-side product opt-in remains authoritative.
  if [[ "$target" == Stackless ]]; then
    local scope="${WOO_RELEASE_SCOPE:-}" grant="${WOO_GRANT_EXISTING:-0}"
    case "$scope" in
      ''|product|all_variations) ;;
      *) echo '[ERROR] WOO_RELEASE_SCOPE must be blank, product, or all_variations' >&2; exit 1 ;;
    esac
    case "$grant" in
      1|yes|true|on) grant=1 ;;
      0|no|false|off|'') grant=0 ;;
      *) echo '[ERROR] WOO_GRANT_EXISTING must be 1 or 0 (also accepts yes/no, true/false, on/off)' >&2; exit 1 ;;
    esac
    # A blank scope intentionally omits the field, allowing the per-product
    # WordPress setting to choose product vs all_variations.
    [[ -z "$scope" ]] || extra_args+=(-F "release_scope=$scope")
    # A false grant is not sent; historical access requires explicit opt-in.
    [[ "$grant" != 1 ]] || extra_args+=(-F 'grant_existing=true')
  fi
  curl --fail-with-body --globoff -sS -u "$user:$password" \
    -F "version=$version" -F "download_mode=$mode" -F "package=@${curl_zip_path};type=application/zip" \
    -F "requires_wp=$requires" -F "tested_wp=$tested" -F "requires_php=$requires_php" \
    "${extra_args[@]}" --form-string "changelog=$(cat "$CHANGELOG_FILE")" "$url"
  echo; echo "[OK] $target release published."
}
if [[ "$DEPLOY_TARGET" == *woo* ]]; then
  release_upload 'Stackless' "${WOO_STORE_URL:-}" "${WOO_SOFTWARE_SLUG:-$PLUGIN_SLUG}" "${WOO_DOWNLOAD_MODE:-replace}" "${WOO_USER:-}" "${WOO_APP_PASSWORD:-}"
fi
if [[ "$DEPLOY_TARGET" == *edd* ]]; then
  release_upload 'EDD' "${EDD_STORE_URL:-}" "${EDD_SOFTWARE_SLUG:-$PLUGIN_SLUG}" "${EDD_DOWNLOAD_MODE:-append}" "${EDD_USER:-${WOO_USER:-}}" "${EDD_APP_PASSWORD:-${WOO_APP_PASSWORD:-}}" "${EDD_DOWNLOAD_ID:-}"
fi
# Hoster uses its own API with the same multipart package field.
if [[ "$DEPLOY_TARGET" == *hoster* ]]; then
  : "${HOSTER_STORE_URL:?Set HOSTER_STORE_URL}"
  : "${HOSTER_USER:?Set HOSTER_USER in the secure env file}"
  : "${HOSTER_APP_PASSWORD:?Set HOSTER_APP_PASSWORD in the secure env file}"
  hoster_slug="${HOSTER_SOFTWARE_SLUG:-$PLUGIN_SLUG}"
  echo "[INFO] Publishing Hoster release $hoster_slug $version..."
  curl_zip_path="$zip_path"
  if [[ -n "${MSYSTEM:-}" ]] && command -v cygpath >/dev/null 2>&1; then curl_zip_path="$(cygpath -m "$zip_path")"; fi
  curl --fail-with-body --globoff -sS -u "$HOSTER_USER:$HOSTER_APP_PASSWORD" \
    -F "version=$version" -F "package=@${curl_zip_path};type=application/zip" \
    --form-string "changelog=$(cat "$CHANGELOG_FILE")" \
    "${HOSTER_STORE_URL%/}/wp-json/hoster/v1/downloads/$hoster_slug/release"
  echo; echo '[OK] Hoster release published.'
fi
if [[ "$DEPLOY_TARGET" == *fluent* ]]; then
  release_upload 'FluentCart' "${FLUENT_STORE_URL:-}" "${FLUENT_SOFTWARE_SLUG:-$PLUGIN_SLUG}" "${FLUENT_DOWNLOAD_MODE:-append}" "${FLUENT_USER:-}" "${FLUENT_APP_PASSWORD:-}"
fi
if [[ "$DEPLOY_TARGET" == *surecart* ]]; then
  : "${SURECART_PRODUCT_ID:?Set SURECART_PRODUCT_ID (SureCart product UUID)}"
  [[ "${SURECART_DOWNLOAD_MODE:-append}" == append ]] || { echo '[ERROR] SureCart v0.7 requires append mode' >&2; exit 1; }
  # SureCart requires a native product UUID in addition to the common software slug.
  release_upload 'SureCart' "${SURECART_STORE_URL:-}" "${SURECART_SOFTWARE_SLUG:-$PLUGIN_SLUG}" append "${SURECART_USER:-}" "${SURECART_APP_PASSWORD:-}" ""
fi
if [[ "$DEPLOY_TARGET" == *dlm* ]]; then
  : "${DLM_STORE_URL:?Set DLM_STORE_URL}"
  : "${DLM_SOFTWARE_ID:?Set numeric DLM_SOFTWARE_ID}"
  [[ "$DLM_SOFTWARE_ID" =~ ^[0-9]+$ ]] || { echo '[ERROR] DLM_SOFTWARE_ID must be numeric' >&2; exit 1; }
  : "${DLM_CONSUMER_KEY:?Set DLM_CONSUMER_KEY in DLM credentials file}"
  : "${DLM_CONSUMER_SECRET:?Set DLM_CONSUMER_SECRET in DLM credentials file}"
  curl_zip_path="$zip_path"
  if [[ -n "${MSYSTEM:-}" ]] && command -v cygpath >/dev/null 2>&1; then curl_zip_path="$(cygpath -m "$zip_path")"; fi
  # DLM Pro requires compatibility fields on each release; reuse the header values
  # already collected for the other providers instead of duplicating CFG settings.
  [[ -n "${requires//[[:space:]]/}" && -n "${tested//[[:space:]]/}" && -n "${requires_php//[[:space:]]/}" ]] || {
    echo '[ERROR] DLM requires plugin headers: Requires at least, Tested up to, Requires PHP' >&2; exit 1;
  }
  echo "[INFO] Publishing DLM software $DLM_SOFTWARE_ID release $version..."
  # Digital License Manager API consumer key/secret via HTTP Basic over HTTPS.
  curl --fail-with-body --globoff -sS --basic --user "$DLM_CONSUMER_KEY:$DLM_CONSUMER_SECRET" \
    -F "version=$version" -F "file=@${curl_zip_path};type=application/zip" \
    -F "requires_wp=$requires" -F "tested_wp=$tested" -F "requires_php=$requires_php" \
    -F "channel=${DLM_CHANNEL:-stable}" -F "make_current=true" -F "notify=${DLM_NOTIFY:-false}" \
    --form-string "changelog=$(cat "$CHANGELOG_FILE")" \
    "${DLM_STORE_URL%/}/wp-json/dlm/v1/software/$DLM_SOFTWARE_ID/releases"
  echo; echo '[OK] DLM release published.'
fi
# Only remove local versioned release archives once every selected publisher has returned success.
# Opt-in because users may retain build artifacts for debugging, local archives, or other targets.
case "${CLEANUP_RELEASE_ZIPS:-0}" in
  0|no|false|off|'') cleanup_zips_enabled=0 ;;
  1|yes|true|on) cleanup_zips_enabled=1 ;;
  *) echo "[ERROR] Invalid CLEANUP_RELEASE_ZIPS: ${CLEANUP_RELEASE_ZIPS} (use 0 or 1)" >&2; exit 1 ;;
esac
if [[ "$cleanup_zips_enabled" == 1 ]]; then
  case "$DEPLOY_TARGET" in
    github|github+*|surecart|woo|edd|fluent|hoster|dlm)
      deleted=0
      shopt -s nullglob
      for candidate in "$script_dir"/"$PLUGIN_SLUG"-[0-9]*.zip; do
        [[ -f "$candidate" && ! -L "$candidate" ]] || continue
        base="${candidate##*/}"
        suffix="${base#"$PLUGIN_SLUG"-}"
        # Only known version-shaped basenames (e.g. 0.9.5.3 or 1.2.0-beta.1).
        [[ "$suffix" =~ ^[0-9]+([.][0-9A-Za-z]+)*([-+][0-9A-Za-z.-]+)?[.]zip$ ]] || continue
        rm -- "$candidate" || { echo "[ERROR] Failed to clean $candidate" >&2; exit 1; }
        echo "[INFO] Removed local release archive: $base"
        deleted=$((deleted+1))
      done
      shopt -u nullglob
      echo "[OK] Local release ZIP cleanup complete ($deleted removed)." ;;
    *) echo "[INFO] Skipping ZIP cleanup for target $DEPLOY_TARGET; local copy may be required." ;;
  esac
fi
echo "[OK] Deployment complete: $DEPLOY_TARGET ($version)"
