#!/usr/bin/env bash
#
# lftp helper for the Majestic Marquees frontend server.
#
# The server REQUIRES explicit FTPS (FTP over TLS). Plain FTP connects but
# data transfers (ls / get / put) fail with "max-retries exceeded".
#
# Usage:
#   ./ftp-sync.sh shell          # open an interactive lftp session
#   ./ftp-sync.sh ls [path]      # list a remote directory
#   ./ftp-sync.sh pull           # mirror remote  -> local  (download site)
#   ./ftp-sync.sh push           # mirror local   -> remote (upload site)
#   ./ftp-sync.sh push --dry-run # preview what an upload would change
#
# Credentials: set the password once in your shell, e.g.
#   export FTP_PASSWORD='K1fpuzc3%TzyVvKu'
# or create a file ".ftp-pass" (gitignored) containing just the password.

set -euo pipefail

HOST="website.majesticmarquees.clickdigim.com"
USER="admin@website.majesticmarquees.clickdigim.com"
LOCAL_DIR="mm-frontend"   # local folder that maps to the remote web root
REMOTE_DIR="."            # remote web root (the FTP login home directory)

# --- resolve password -------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -z "${FTP_PASSWORD:-}" && -f "${SCRIPT_DIR}/.ftp-pass" ]]; then
  FTP_PASSWORD="$(<"${SCRIPT_DIR}/.ftp-pass")"
fi
if [[ -z "${FTP_PASSWORD:-}" ]]; then
  read -r -s -p "FTP password for ${USER}: " FTP_PASSWORD
  echo
fi
export LFTP_PASSWORD="$FTP_PASSWORD"

# Settings that make explicit FTPS work on this host.
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
    run_lftp "mirror --verbose ${*} \"$REMOTE_DIR\" \"${SCRIPT_DIR}/${LOCAL_DIR}\"; bye"
    ;;
  push)
    run_lftp "mirror --reverse --verbose --delete-first=no ${*} \"${SCRIPT_DIR}/${LOCAL_DIR}\" \"$REMOTE_DIR\"; bye"
    ;;
  *)
    echo "Unknown command: $cmd" >&2
    echo "Use: shell | ls [path] | pull | push [--dry-run]" >&2
    exit 1
    ;;
esac
