#!/bin/bash
# Generic FTP deploy for the mm-* frontends (mm-blog, mm-admin, mm-frontend).
# Mirrors a local project folder up to its web root over FTP using lftp.
#
# Usage:
#   bash deploy.sh <path-to-deploy.config>            # upload changed files (no deletions)
#   bash deploy.sh <path-to-deploy.config> --dry-run  # show what would change
#   bash deploy.sh <path-to-deploy.config> --delete   # also remove remote-only files (DANGEROUS)
#
# The deploy.config (gitignored, per project) provides:
#   FTP_HOST / FTP_USER / FTP_PASS / FTP_PORT
#   LOCAL_DIR  (absolute path of the folder to upload)
#   REMOTE_DIR (remote target, usually /)
#   EXCLUDES   (optional, space separated glob list)

set -euo pipefail

CONFIG="${1:?Usage: deploy.sh <path-to-deploy.config> [--dry-run] [--delete]}"
shift || true

[ -f "$CONFIG" ] || { echo "Error: config not found: $CONFIG" >&2; exit 1; }
# shellcheck source=/dev/null
source "$CONFIG"

: "${FTP_HOST:?Missing FTP_HOST}"
: "${FTP_USER:?Missing FTP_USER}"
: "${FTP_PASS:?Missing FTP_PASS}"
: "${FTP_PORT:=21}"
: "${LOCAL_DIR:?Missing LOCAL_DIR}"
: "${REMOTE_DIR:=/}"

[ -d "$LOCAL_DIR" ] || { echo "Error: local folder '$LOCAL_DIR' does not exist." >&2; exit 1; }

DRY_RUN=""
DELETE=""
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN="--dry-run" ;;
        --delete)  DELETE="--delete" ;;
        *) echo "Unknown option: $arg" >&2; exit 1 ;;
    esac
done

EXTRA_EXCLUDES=""
for g in ${EXCLUDES:-}; do
    EXTRA_EXCLUDES="$EXTRA_EXCLUDES --exclude-glob $g"
done

echo "Deploying $LOCAL_DIR  ->  ftp://$FTP_HOST:$FTP_PORT$REMOTE_DIR"
[ -n "$DRY_RUN" ] && echo "(dry run - no files will be changed)"
[ -n "$DELETE" ] && echo "(delete mode - remote-only files will be removed)"
echo ""

lftp -u "$FTP_USER","$FTP_PASS" -p "$FTP_PORT" "$FTP_HOST" <<EOF
set ftp:ssl-allow true
set ssl:verify-certificate no
set ftp:ssl-protect-data true
set net:max-retries 5
set net:timeout 20
set net:reconnect-interval-base 5
set net:connection-limit 2
set mirror:parallel-transfer-count 1
mirror --reverse --verbose \
    $DRY_RUN $DELETE \
    --exclude-glob .DS_Store \
    --exclude-glob .git/ \
    --exclude-glob .gitignore \
    --exclude-glob deploy.config \
    $EXTRA_EXCLUDES \
    "$LOCAL_DIR/" "$REMOTE_DIR"
bye
EOF

echo ""
echo "Done."
