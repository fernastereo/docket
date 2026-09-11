# Infraestructura — CuraduriAPP (repo "Docket")

Todo lo de despliegue. Decisiones: `docs/adr/ADR-009` (infra) y `docs/adr/ADR-017`
(seguridad). Servidor web: **Caddy** (amendment a ADR-009 — gestiona el cert
wildcard de dev por DNS-01 y el Origin CA de prod como archivo).

```
infra/
├── docker/
│   ├── app.Dockerfile        PHP-FPM 8.4 · targets: dev | prod
│   ├── caddy.Dockerfile      Caddy + módulo DNS de Cloudflare
│   ├── entrypoint.sh         APP_ROLE = fpm | worker | scheduler
│   ├── Caddyfile.dev         *.staging.curaduria.app · Let's Encrypt DNS-01
│   ├── Caddyfile.prod        *.curaduria.app · Origin CA + trusted_proxies Cloudflare
│   └── php/                  php.ini · opcache · www.conf · healthcheck
├── compose/
│   ├── docker-compose.dev.yml
│   └── docker-compose.prod.yml
├── env/
│   ├── dev.env.example       → copiar a dev.env (gitignoreado)
│   └── prod.env.example      → copiar a prod.env (gitignoreado, SOPS en el host)
├── scripts/
│   ├── provision.sh          alta de droplet (Tailscale, hardening, ufw, fail2ban)
│   ├── cloudflare-ips.sh     origin lock: 80/443 solo desde Cloudflare (PROD)
│   └── backup-tenants.sh     pg_dump por tenant → Spaces, cifrado age (PROD, cron)
└── tailscale/acl.json        política de la malla
```

---

## DEV — estado actual (aprovisionado)

| Recurso | Valor |
|---|---|
| Droplet `docket-dev` | `143.198.12.146` · NYC3 · VPC `docket-vpc` |
| Firewall `docket-fw` | SSH ← IP de casa · 80/443 abiertos |
| Spaces | `docket-dev-files` @ `nyc3.digitaloceanspaces.com` |
| DNS (Cloudflare) | `*.staging` y `staging` → `143.198.12.146` (DNS only) |
| Tailscale | tag `tag:docket` · tailnet `tail133784.ts.net` |

### Levantar dev

```bash
# 1. en el droplet, como root (por Tailscale SSH):
ENV=dev HOSTNAME_TS=docket-dev TS_AUTHKEY=tskey-auth-xxx bash infra/scripts/provision.sh

# 2. traer el repo y el env
git clone <repo> /opt/docket/src && cd /opt/docket/src
cp infra/env/dev.env.example infra/env/dev.env   # y completar secretos

# 3. build + up
cd infra/compose
docker compose -f docker-compose.dev.yml --env-file ../env/dev.env up -d --build

# 4. inicializar la app (una vez)
docker compose -f docker-compose.dev.yml exec app php artisan key:generate
docker compose -f docker-compose.dev.yml exec app php artisan migrate --force
```

Verificar: `https://staging.curaduria.app` responde y el cert es de Let's Encrypt.

---

## PROD — runbook de go-live (NADA de esto está aprovisionado)

Se ejecuta cuando se decida ir a producción. Orden:

1. **Droplet** `docket-prod` (Marketplace Docker, 2 GB, NYC3, VPC `docket-vpc`, SSH key).
2. **Reserved IP** → asignar a `docket-prod`.
3. **Managed PostgreSQL** `docket-prod-db` (PG 17, plan chico, misma VPC):
   - Trusted Sources → solo `docket-prod`.
   - Connection details → *Private network*. Descargar el **CA cert** → `/opt/docket/pg-ca.crt`.
   - Crear base `docket_central` y un usuario de app (no `doadmin`). `CREATE EXTENSION vector;` en cada base de tenant lo hace stancl/tenancy vía migración.
4. **Spaces** `docket-prod-files` (privado) y `docket-prod-backups` (privado, **versionado/objeto inmutable + retención** vía API). Key granular por bucket.
5. **Cloud Firewall** de prod: attach a `docket-prod`; correr `FIREWALL_ID=<id> bash infra/scripts/cloudflare-ips.sh` para bloquear 80/443 a rangos de Cloudflare. **Sin regla SSH pública.**
6. **Cloudflare**:
   - Zona `curaduria.app` → plan **Pro**.
   - DNS: `*.curaduria.app` → Reserved IP, **Proxied** (naranja). Root `curaduria.app` sigue en la landing.
   - SSL/TLS → **Full (strict)**, min TLS 1.2, HSTS + preload, Always Use HTTPS.
   - **Origin CA cert** para `*.curaduria.app`, `curaduria.app` → `cert.pem` + `key.pem` en `/opt/docket/origin/`.
   - WAF → Managed + OWASP Core Ruleset. Rate limiting en `/login`, `/verificar/*`. Bot Fight Mode ON.
7. **Provision**: `ENV=prod HOSTNAME_TS=docket-prod TS_AUTHKEY=... bash infra/scripts/provision.sh`.
8. **Secretos**: `infra/env/prod.env` cifrado con SOPS+age en el host.
9. **Deploy** (workflow manual, ver `.github/workflows/`): pull de imágenes → `migrate --force` → `tenants:migrate --force` → `up -d`.
10. **Cron** de backups: `backup-tenants.sh` diario + prueba de restore trimestral.

### Diferencias dev → prod (para no llevarse sorpresas)

| | dev | prod |
|---|---|---|
| Web | Caddy + Let's Encrypt DNS-01 | Caddy + Origin CA, tras Cloudflare proxy |
| PostgreSQL | contenedor `pgvector/pgvector:pg17` | DO Managed PG, SSL `require`, IP privada |
| Imagen app | target `dev`, código bind-mounted | target `prod`, autocontenida (CI) |
| 80/443 | abiertos | solo rangos de Cloudflare |
| SSH público | permitido (red de seguridad) | denegado (solo Tailscale) |
| Secretos | `dev.env` local | `prod.env` cifrado SOPS+age |
