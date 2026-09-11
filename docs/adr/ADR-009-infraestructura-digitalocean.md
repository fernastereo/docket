# ADR-009: Infraestructura (DigitalOcean, Docker, PostgreSQL administrado)

**Estado**: Aceptada
**Fecha**: 2026-07-21
**Amendment 2026-09-01**: concreta dominio, ambientes y CI/CD — ver sección
Amendments al final. Todavía sin ejecutar (nada de esto está aprovisionado
todavía) — es el plan acordado para el arranque de la implementación.
**Amendment 2026-09-06**: controles de seguridad de infraestructura (borde,
red, host, backups) — ver sección Amendments. Derivan de ADR-017.

## Contexto

Hosting para el SaaS con database-per-tenant. Se evaluó: (a) un contenedor
PostgreSQL por base de tenant, (b) droplet independiente por tenant,
(c) una instancia PostgreSQL con N bases, autogestionada o administrada.

## Decisión

- **Proveedor**: DigitalOcean.
- **Base de datos**: **DO Managed PostgreSQL** — un cluster administrado que
  contiene las N bases de tenant + la base central. Backups automáticos,
  point-in-time recovery, parcheo y failover a cargo del proveedor.
- **Aplicación**: un droplet con Docker Compose:
  - contenedor app (Laravel / PHP-FPM + Nginx)
  - contenedor workers de colas
  - contenedor Redis (colas, caché, sesiones)
- **Archivos**: DO Spaces (ADR-007).
- **DNS**: wildcard `*.plataforma.com` + certificado TLS wildcard para los
  subdominios de tenant.
- **Backups por tenant** (feature vendible): `pg_dump` individual por base,
  programado, hacia Spaces — por encima de los backups del cluster administrado.

## Alternativas descartadas y por qué

- **Contenedor PostgreSQL por tenant**: el aislamiento exigido es de *datos*,
  y lo cumple una instancia con N bases (las bases no comparten datos y los
  usuarios de conexión se restringen por base). Contenedor-por-tenant solo
  aportaría aislamiento de recursos/versiones, a costa de memoria multiplicada
  (shared_buffers por instancia), N procesos que monitorear/parchear, y
  backups/actualizaciones coordinados por contenedor. Sobre-ingeniería a esta
  escala.
- **Droplet por tenant**: multiplica costo fijo y administración sin necesidad.
  Se reserva como palanca futura para tenants enormes o que exijan (y paguen)
  infraestructura dedicada — el modelo database-per-tenant permite mover una
  base a su propia instancia en cualquier momento sin rediseño.

## Escalamiento previsto

- App stateless (sesiones en Redis, archivos en Spaces) → segundo droplet de
  app tras un Load Balancer de DO cuando el tráfico lo pida.
- PgBouncer si el número de tenants/conexiones crece.
- Staging replica la estructura multi-tenant completa (para ensayar
  migraciones multi-base). Tenant "Curaduría Demo" con datos ficticios para
  desarrollo, pruebas y ventas.

## Amendments

### 2026-09-01 — Ambientes, dominio y CI/CD concretos

**Motivo**: al planear el arranque de la implementación, con `curaduria.app`
ya registrado y con la landing en producción (`docs/vision-producto.md`,
`landing/`), se concretaron varias cosas que esta ADR dejaba abiertas.
Objetivo explícito del usuario: desplegar seguido desde el principio
(esqueleto de Laravel ya desplegable, cada feature se prueba en dev antes
de pasar a producción) en vez de un único despliegue grande al final.

**Dos droplets** (Marketplace image "Docker", región **NYC** — la más
cercana a Colombia entre las que ofrece DO, tamaño inicial **2 GB RAM / 1
vCPU**, ambos escalables sin rediseño):
- **`docket-prod`**: app + workers + Redis en Docker Compose, conectado a
  un **DO Managed PostgreSQL** aparte (`docket-prod-db`) — como ya decidía
  esta ADR.
- **`docket-dev`**: mismo stack, pero con **PostgreSQL en un contenedor más
  del propio docker-compose** (no administrado) — dev no necesita
  backups/PITR/failover automáticos y así se evita pagar un segundo cluster
  gestionado desde el arranque.

**Dominio**: se reutiliza `curaduria.app` (ya usado por la landing en la
raíz, gestionada aparte en Cloudflare Workers) en vez de registrar uno
nuevo:
- Producción: `*.curaduria.app` → IP de `docket-prod`.
- Dev/staging: `*.staging.curaduria.app` → IP de `docket-dev`.
- La raíz (`curaduria.app`) sigue siendo la landing; no se toca.

**TLS en los droplets**: certificado de **Cloudflare Origin CA** (gratis,
válido 15 años) en Nginx, con Cloudflare en modo **Full (strict)** — evita
depender de renovaciones de Let's Encrypt para esto.

**CI/CD**: GitHub Actions.
- Lint/tests (Pint, Larastan, Pest — ADR-011) corren automático en cada
  push/PR a `dev` y `main`.
- El **despliegue es un workflow separado, con disparo manual**
  (`workflow_dispatch`, se elige el ambiente) — explícitamente **no**
  automático en cada push/merge, por decisión del usuario.

**Estado de ejecución (para retomar en próxima sesión)**: el usuario ya
tiene cuenta de DigitalOcean. Pendiente que el usuario cree los dos
droplets y el cluster `docket-prod-db` siguiendo estos parámetros y reporte
IPs/credenciales; en paralelo se prepara del lado del repo: Dockerfiles,
`docker-compose.prod.yml` / `docker-compose.dev.yml`, config de Nginx, y
los workflows de GitHub Actions — nada de esto se ha escrito todavía.

### 2026-09-06 — Controles de seguridad de infraestructura

**Motivo**: ADR-017 fija la postura de seguridad de la plataforma; esta
sección concreta lo que cae en la capa de infraestructura y modifica el
aprovisionamiento descrito arriba.

**Borde (Cloudflare Pro en la zona `curaduria.app`)**:
- Se sube el plan de la zona a **Pro**. WAF (OWASP Core + Cloudflare Managed
  Ruleset), rate limiting en endpoints sensibles (login, recuperación,
  activación/enrolamiento, envío de radicación, verificación pública, tokens de
  API), Bot Fight Mode, "Under Attack" mode como palanca de runbook.
- **Origin lock**: el firewall del droplet acepta 80/443 **solo** desde los
  rangos de IP publicados por Cloudflare (job que los mantiene al día). El
  resto del tráfico entrante público se descarta.
- TLS: se mantiene Origin CA + Full (Strict) del amendment anterior; se añade
  **HSTS con preload** y mínimo TLS 1.2 (preferente 1.3).

**Red**:
- Los dos droplets y el `docket-prod-db` van en una **DO VPC**. La app se
  conecta al Managed PostgreSQL solo por **IP privada**; "trusted sources" del
  cluster = únicamente el droplet `docket-prod`. El cluster **no** queda
  accesible desde internet.
- `docket-dev` con su Postgres en contenedor: ese contenedor no publica puerto
  al host más allá de la red interna de Docker.

**Acceso de operación**:
- **Sin puerto SSH expuesto a internet.** SSH, administración de BD, deploy
  manual y debugging entran por una **malla Tailscale/WireGuard**. ACLs de la
  malla; MFA obligatoria en el proveedor de identidad de la malla.
- Host: `unattended-upgrades` de seguridad, sin login root, solo llaves,
  `fail2ban`, `auditd`, paquetes mínimos (hardening CIS Ubuntu).
- Docker (hardening CIS): contenedores non-root, sin `--privileged`, drop de
  capabilities, rootfs de solo lectura donde se pueda, **límites de recursos
  por contenedor**, imágenes base pinneadas.

**Backups** (sobre lo ya decidido: `pg_dump` por tenant hacia Spaces):
- Cifrados, en un bucket de Spaces con **versionado/objeto inmutable +
  retención** (resiliencia ante ransomware), IAM restringido.
- **Prueba de restore trimestral** documentada.

**CI/CD**:
- El workflow de lint/tests suma scanners de seguridad: `composer audit`,
  `npm audit --audit-level=high`, gitleaks, SAST (CodeQL o Semgrep), Trivy
  sobre la imagen. Un fallo bloquea el merge/deploy (ver ADR-011).
- El deploy manual usa **GitHub OIDC → DigitalOcean**, sin llaves cloud de
  larga vida; secrets de Actions separados por ambiente.
- Secretos de runtime en el host vía **SOPS + age** (cifrados en el repo) o
  equivalente — decisión final en `docs/preguntas-abiertas.md`.

**Impacto en el aprovisionamiento**: la lista de "un comando" de provisioning
y el trabajo del lado repo ahora incluyen VPC, reglas de firewall con origin
lock, alta en la malla VPN, y el hardening de host/Docker además de los
Dockerfiles / compose / workflows ya listados.

### 2026-09-07 — Servidor web: Caddy en vez de Nginx

Donde los amendments anteriores dicen "Nginx", léase **Caddy**. Motivo: en dev
se necesita un certificado **wildcard** para `*.staging.curaduria.app` (que va
DNS-only, sin proxy de Cloudflare), y Caddy lo emite y renueva solo vía reto
**DNS-01** con el token de Cloudflare — sin cron de renovación ni volúmenes de
certbot. En prod, Caddy sirve el certificado **Origin CA** de Cloudflare como
archivo estático, igual de simple, con `trusted_proxies` apuntando a los rangos
de Cloudflare. Un solo servidor web para ambos ambientes, "seguro por defecto"
(cabeceras, HTTP→HTTPS, HSTS). Implementación: `infra/docker/Caddyfile.{dev,prod}`
y `infra/docker/caddy.Dockerfile` (Caddy + módulo `caddy-dns/cloudflare`).
