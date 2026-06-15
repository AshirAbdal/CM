#!/usr/bin/env bash
#
# lftp helper for the Majestic Marquees servers (frontend + admin).
#
# Both servers REQUIRE explicit FTPS (FTP over TLS). Plain FTP connects but
# data transfers (ls / get / put) fail with "max-retries exceeded".
#
# Usage:
#   ./ftp-sync.sh <site> <command> [args]
#     <site>    = frontend | admin
#     <command> = shell | ls [path] | pull | push [--dry-run]
#
# Examples:
#   ./ftp-sync.sh frontend ls
#   ./ftp-sync.sh admin push --dry-run
#   ./ftp-sync.sh admin push
#
# Credentials are read from gitignored files next to this script:
#   .ftp-pass-frontend   -> password for the frontend server
#   .ftp-pass-admin      -> password for the admin server
# (You are prompted if the matching file is missing.)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

site="${1:-}"
shift || true

case "$site" in
  frontend)
    HOST="website.majesticmarquees.clickdigim.com"
    USER="admin@website.majesticmarquees.clickdigim.com"
    LOCAL_DIR="mm-frontend"
    PASS_FILE="${SCRIPT_DIR}/.ftp-pass-frontend"
    ;;
  admin)
    HOST="admin.majesticmarquees.clickdigim.com"
    USER="admin@admin.majesticmarquees.clickdigim.com"
    LOCAL_DIR="mm-admin"
    PASS_FILE="${SCRIPT_DIR}/.ftp-pass-admin"
    ;;
  *)
    echo "Usage: ./ftp-sync.sh <frontend|admin> <shell|ls|pull|push> [args]" >&2
    exit 1
    ;;
esac

REMOTE_DIR="."   # remote web root (the FTP login home directory)

# --- resolve password -------------------------------------------------------
if [[ -f "$PASS_FILE" ]]; then
  FTP_PASSWORD="$(<"$PASS_FILE")"
else
  read -r -s -p "FTP password for ${USER}: " FTP_PASSWORD
  echo
fi
export LFTP_PASSWORD="$FTP_PASSWORD"

# Settings that make explicit FTPS work on these hosts.
COMMON_SETTINGS='
set ftp:ssl-allow yes;
set ftp:ssl-force yes;
set ftp:ssl-protect-data yes;
set ssl:verify-certificate no;
set ftp:passive-mode yes;
set net:timeout 20;
set net:max-retries 3;
set net:reconnect-interval-base 5;
'

run_lftp() {
  lftp -u "$USER" --env-password "ftp://${HOST}" -e "${COMMON_SETTINGS} $1"
}

cmd="${1:-shell}"
shift || true

case "$cmd" in
  shell)
    run_lftp "${*:-}"
    ;;
  ls)
    run_lftp "cls -l ${1:-$REMOTE_DIR}; bye"
    ;;
  pull)
    run_lftp "mirror --verbose ${*:-} ${REMOTE_DIR} ${SCRIPT_DIR}/${LOCAL_DIR}; bye"
    ;;
  push)
    # NOTE: no --delete flag, so remote-only files (root assets/, logo.png,
    # favicon.svg, index.html, etc.) are preserved and never removed.
    run_lftp "mirror --reverse --verbose ${*:-} ${SCRIPT_DIR}/${LOCAL_DIR} ${REMOTE_DIR}; bye"
    ;;
  *)
    echo "Unknown command: $cmd" >&2
    echo "Use: shell | ls [path] | pull | push [--dry-run]" >&2
    exit 1
    ;;
esac
