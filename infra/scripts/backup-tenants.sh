#!/usr/bin/env bash
#
# Backup por tenant + central → Spaces, cifrado con age (ADR-009 / ADR-017).
# Pensado para correr por cron en el droplet de prod:
#   15 3 * * *  /opt/docket/infra/scripts/backup-tenants.sh >> /var/log/docket-backup.log 2>&1
#
# Requisitos en el host:
#   - pg_dump (postgresql-client-17)
#   - age  + clave pública en $AGE_RECIPIENTS
#   - awscli o s3cmd configurado para Spaces (bucket docket-prod-backups)
#   - variables: DB_HOST DB_PORT DB_USERNAME PGPASSWORD BACKUP_BUCKET AGE_RECIPIENTS
#
set -euo pipefail

: "${DB_HOST:?}" "${DB_PORT:=25060}" "${DB_USERNAME:?}" "${PGPASSWORD:?}"
: "${BACKUP_BUCKET:?ej. s3://docket-prod-backups}" "${AGE_RECIPIENTS:?clave(s) age pública(s)}"
: "${S3_ENDPOINT:=https://nyc3.digitaloceanspaces.com}"

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

dump_one() {  # $1 = nombre de base
    local db="$1" out="$work/${1}-${stamp}.sql.gz.age"
    echo "  · $db"
    PGPASSWORD="$PGPASSWORD" pg_dump \
        --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" \
        --no-owner --no-privileges --format=plain "$db" \
    | gzip -9 \
    | age --encrypt --recipients-file <(printf '%s\n' $AGE_RECIPIENTS) --output "$out"
    aws --endpoint-url "$S3_ENDPOINT" s3 cp "$out" "${BACKUP_BUCKET}/${db}/${stamp}.sql.gz.age" --only-show-errors
}

echo "[$(date -u +%FT%TZ)] backup start"

# Central
dump_one "${DB_DATABASE:-docket_central}"

# Tenants: la lista sale de la central. `tenants:list` imprime un id/base por línea.
if command -v php >/dev/null 2>&1 && [ -f /var/www/html/artisan ]; then
    php /var/www/html/artisan tenants:list --only=tenancy_db_name 2>/dev/null \
        | while read -r tdb; do [ -n "$tdb" ] && dump_one "$tdb"; done
else
    echo "  ! artisan no disponible en el host; correr tenants:list dentro del contenedor de app"
fi

echo "[$(date -u +%FT%TZ)] backup done"
