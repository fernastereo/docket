#!/usr/bin/env bash
#
# Sincroniza las reglas inbound 80/443 del DO Cloud Firewall para que solo
# acepten tráfico desde los rangos publicados de Cloudflare (origin lock,
# ADR-017). Solo aplica a PROD — en dev *.staging va DNS-only y 80/443 quedan
# abiertos.
#
# Requiere: doctl autenticado (`doctl auth init`) con un token Read+Write.
# Uso:  FIREWALL_ID=<id> bash cloudflare-ips.sh
#
set -euo pipefail

FIREWALL_ID="${FIREWALL_ID:?FIREWALL_ID=<id del docket-fw de prod>}"

v4="$(curl -fsSL https://www.cloudflare.com/ips-v4/)"
v6="$(curl -fsSL https://www.cloudflare.com/ips-v6/)"
addrs="$(printf '%s\n%s\n' "$v4" "$v6" | grep -E '.' | paste -sd, -)"

rule_for() {  # $1 = puerto
    printf 'protocol:tcp,ports:%s,address:%s' "$1" "$addrs"
}

echo "Rangos Cloudflare: $(printf '%s\n%s' "$v4" "$v6" | grep -c .) prefijos"

doctl compute firewall update "$FIREWALL_ID" \
    --inbound-rules "$(rule_for 443)" \
    --inbound-rules "$(rule_for 80)" \
    --outbound-rules "protocol:icmp,address:0.0.0.0/0,address:::/0" \
    --outbound-rules "protocol:tcp,ports:all,address:0.0.0.0/0,address:::/0" \
    --outbound-rules "protocol:udp,ports:all,address:0.0.0.0/0,address:::/0"

echo "docket-fw actualizado. SSH público NO se incluye (acceso por Tailscale)."
