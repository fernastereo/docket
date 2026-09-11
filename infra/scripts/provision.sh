#!/usr/bin/env bash
#
# Aprovisiona un droplet recién creado desde la imagen Marketplace "Docker" de
# DigitalOcean para CuraduriAPP (ADR-009 / ADR-017). NO despliega la app —
# eso lo hace el workflow de deploy.
#
# Uso (como root, en el droplet):
#   ENV=dev  HOSTNAME_TS=docket-dev  TS_AUTHKEY=tskey-auth-xxx  bash provision.sh
#   ENV=prod HOSTNAME_TS=docket-prod TS_AUTHKEY=tskey-auth-xxx  bash provision.sh
#
set -euo pipefail

ENV="${ENV:?ENV=dev|prod}"
HOSTNAME_TS="${HOSTNAME_TS:?HOSTNAME_TS=docket-dev|docket-prod}"
TS_AUTHKEY="${TS_AUTHKEY:?TS_AUTHKEY=tskey-auth-...}"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }

log "Hostname + timezone"
hostnamectl set-hostname "$HOSTNAME_TS"
timedatectl set-timezone America/Bogota || true

log "Paquetes base + actualizaciones automáticas de seguridad"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y --no-install-recommends \
    unattended-upgrades apt-listchanges fail2ban ufw auditd curl ca-certificates gnupg age
dpkg-reconfigure -f noninteractive unattended-upgrades

log "Tailscale (malla de operación, sin SSH público — ADR-017)"
if ! command -v tailscale >/dev/null 2>&1; then
    curl -fsSL https://tailscale.com/install.sh | sh
fi
tailscale up \
    --authkey="${TS_AUTHKEY}" \
    --hostname="${HOSTNAME_TS}" \
    --advertise-tags=tag:docket \
    --ssh \
    --accept-dns=false

log "Endurecimiento de SSH (solo llave; el acceso real es por Tailscale)"
install -d -m 0755 /etc/ssh/sshd_config.d
cat > /etc/ssh/sshd_config.d/10-docket.conf <<'EOF'
PermitRootLogin prohibit-password
PasswordAuthentication no
KbdInteractiveAuthentication no
X11Forwarding no
MaxAuthTries 3
EOF
systemctl reload ssh || systemctl reload sshd || true

log "Firewall del host (ufw) — complementa el DO Cloud Firewall"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow in on tailscale0
ufw allow 80/tcp
ufw allow 443/tcp
# SSH público: se deja SOLO en dev y como red de seguridad; en prod, denegado.
if [ "$ENV" = "dev" ]; then ufw allow 22/tcp; fi
ufw --force enable

log "fail2ban (defensa en profundidad aunque el 22 esté detrás de Tailscale)"
systemctl enable --now fail2ban

log "sysctl — hardening básico de red"
cat > /etc/sysctl.d/60-docket.conf <<'EOF'
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.tcp_syncookies = 1
kernel.kptr_restrict = 2
EOF
sysctl --system >/dev/null

log "Docker Compose plugin + estructura /opt/docket"
apt-get install -y --no-install-recommends docker-compose-plugin || true
install -d -m 0750 /opt/docket /opt/docket/origin
install -d -m 0750 /opt/docket/env

log "Listo. Falta: colocar infra/env/${ENV}.env (cifrado con SOPS) y correr el deploy."
if [ "$ENV" = "prod" ]; then
    echo "  + colocar el CA de Managed PG en /opt/docket/pg-ca.crt"
    echo "  + colocar cert.pem y key.pem (Origin CA de Cloudflare) en /opt/docket/origin/"
    echo "  + agendar infra/scripts/backup-tenants.sh vía cron"
fi
