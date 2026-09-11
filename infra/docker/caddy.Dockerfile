# syntax=docker/dockerfile:1.7
#
# Caddy con el módulo DNS de Cloudflare (para el reto DNS-01 del wildcard
# *.staging.curaduria.app en dev). En prod el TLS lo sirve Cloudflare y el
# origen usa el certificado Origin CA como archivo estático — el módulo no
# estorba.

# Tag de línea principal (no fijado a un patch) a propósito: el módulo
# caddy-dns/cloudflare sigue el Caddy core más reciente, y fijar un patch
# viejo del builder puede romper el build por desalineación de dependencias
# transitivas (p. ej. la API de go.uber.org/zap).
FROM caddy:2-builder-alpine AS builder
RUN xcaddy build \
    --with github.com/caddy-dns/cloudflare

FROM caddy:2-alpine
COPY --from=builder /usr/bin/caddy /usr/bin/caddy
