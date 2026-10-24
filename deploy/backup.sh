#!/usr/bin/env bash
#
# Backup de la base de datos MySQL y de las imágenes de productos.
# Uso:
#   sudo install -m 755 deploy/backup.sh /usr/local/bin/cafeteria-backup
#   sudo /usr/local/bin/cafeteria-backup
#
# Programar en cron (root):
#   0 3 * * * /usr/local/bin/cafeteria-backup >> /var/log/cafeteria-backup.log 2>&1
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/cafeteria}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/cafeteria}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
ENV_FILE="${ENV_FILE:-/etc/cafeteria/backup.env}"

if [[ -f "$ENV_FILE" ]]; then
    set -a
    # shellcheck disable=SC1090
    source "$ENV_FILE"
    set +a
fi

: "${DB_DATABASE:?Falta DB_DATABASE (revisa $ENV_FILE)}"
: "${DB_USERNAME:?Falta DB_USERNAME (revisa $ENV_FILE)}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_PASSWORD="${DB_PASSWORD:-}"

mkdir -p "$BACKUP_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
SQL_FILE="$BACKUP_DIR/cafeteria-db-$STAMP.sql.gz"
STORAGE_FILE="$BACKUP_DIR/cafeteria-storage-$STAMP.tar.gz"

echo "[$(date '+%F %T')] Iniciando backup de $DB_DATABASE..."

export MYSQL_PWD="$DB_PASSWORD"
mysqldump \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    "$DB_DATABASE" | gzip -9 > "$SQL_FILE"
unset MYSQL_PWD

tar -czf "$STORAGE_FILE" -C "$APP_DIR/storage/app" public

sha256sum "$SQL_FILE" "$STORAGE_FILE" > "$BACKUP_DIR/cafeteria-$STAMP.sha256"

find "$BACKUP_DIR" -type f \
    \( -name 'cafeteria-db-*.sql.gz' -o -name 'cafeteria-storage-*.tar.gz' -o -name 'cafeteria-*.sha256' \) \
    -mtime +"$RETENTION_DAYS" -delete

echo "[$(date '+%F %T')] Backup DB:      $SQL_FILE"
echo "[$(date '+%F %T')] Backup storage: $STORAGE_FILE"
echo "[$(date '+%F %T')] Retención:      $RETENTION_DAYS días"
