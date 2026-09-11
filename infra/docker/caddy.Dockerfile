# syntax=docker/dockerfile:1.7
#
# Caddy con el módulo DNS de Cloudflare (para el reto DNS-01 del wildcard
# *.staging.curaduria.app en dev). En prod el TLS lo sirve Cloudflare y el
# origen usa el certificado Origin CA como archivo estático — el módulo no
# estorba.

FROM caddy:2.8-builder-alpine AS builder
RUN xcaddy build \
    --with github.com/caddy-dns/cloudflare

FROM caddy:2.8-alpine
COPY --from=builder /usr/bin/caddy /usr/bin/caddy
