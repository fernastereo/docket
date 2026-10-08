#!/bin/sh
# Entrypoint del contenedor de app.
#
# NO corre migraciones: en multi-tenant las migraciones son un paso explícito
# del deploy (`php artisan migrate --force` para la central y
# `php artisan tenants:migrate --force` para los tenants, ADR-002 / ADR-009).
#
# APP_ROLE decide qué proceso levanta este contenedor:
#   fpm       -> php-fpm            (por defecto; lo consume Caddy)
#   worker    -> queue worker tenant-aware
#   scheduler -> scheduler de Laravel
set -eu

cd /var/www/html

# storage/ enlazado a public/ (idempotente)
[ -L public/storage ] || php artisan storage:link >/dev/null 2>&1 || true

# En prod, cachés de framework. En dev (APP_ENV=local) no, para no cachear
# configuración mientras se edita.
if [ "${APP_ENV:-production}" != "local" ]; then
    php artisan config:cache  >/dev/null
    php artisan route:cache   >/dev/null
    php artisan event:cache   >/dev/null
fi

# Un comando explícito (p. ej. `docker compose run --rm app php artisan
# migrate --force`) siempre gana sobre APP_ROLE — si no, un `run` de un
# comando puntual terminaría arrancando php-fpm igual (APP_ROLE del
# servicio) e ignorando el comando para siempre.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

role="${APP_ROLE:-fpm}"
case "$role" in
    fpm)
        exec php-fpm
        ;;
    worker)
        exec php artisan queue:work \
            --queue="${QUEUE_NAMES:-default}" \
            --tries=3 --backoff=10 --max-time=3600 --sleep=1
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        echo "APP_ROLE desconocido: $role" >&2
        exit 1
        ;;
esac
