#!/usr/bin/env bash
# Online, consistent SQLite backup for a Linux Vira installation.
set -euo pipefail
umask 077

if ! command -v sqlite3 >/dev/null 2>&1; then
    echo 'ERROR: sqlite3 CLI is required; use your hosting backup tool if unavailable.' >&2
    exit 2
fi

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
database_path="${1:-${project_root}/database/database.sqlite}"
backup_dir="${VIRA_BACKUP_DIR:-${HOME}/ViraBackups}"

if [[ ! -f "$database_path" || ! -r "$database_path" ]]; then
    echo "ERROR: SQLite database does not exist or is unreadable: $database_path" >&2
    exit 2
fi

mkdir -p -- "$backup_dir"
backup_dir="$(cd -- "$backup_dir" && pwd -P)"
case "${backup_dir}/" in
    "${project_root}/"|"${project_root}/"*)
        echo 'ERROR: backup destination must be outside the Git project and public web root.' >&2
        exit 2
        ;;
esac

stamp="$(date -u +'%Y%m%dT%H%M%SZ')"
backup_file="${backup_dir}/vira-${stamp}-$$.sqlite"
tmp_file="$(mktemp "${backup_dir}/.vira-sqlite-XXXXXXXX.tmp")"
trap 'rm -f -- "$tmp_file"' EXIT

# SQLite's online backup API provides a consistent snapshot while the source is in use.
sqlite3 "$database_path" ".timeout 5000" ".backup \"$tmp_file\""
if [[ "$(sqlite3 "$tmp_file" 'PRAGMA integrity_check;')" != 'ok' ]]; then
    echo 'ERROR: SQLite backup integrity check failed.' >&2
    exit 1
fi

mv -- "$tmp_file" "$backup_file"
chmod 600 -- "$backup_file"

printf 'Backup: %s\nSize: %s bytes\nSHA256: ' "$backup_file" "$(wc -c < "$backup_file" | tr -d ' ')"
if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$backup_file" | awk '{print $1}'
elif command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$backup_file" | awk '{print $1}'
else
    echo '(install sha256sum or shasum to verify the hash)'
fi
