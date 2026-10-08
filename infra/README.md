# Infraestructura — CuraduriAPP (repo "Docket")

Todo lo de despliegue. Decisiones: `docs/adr/ADR-009` (infra) y `docs/adr/ADR-017`
(seguridad). Servidor web: **Caddy** (amendment a ADR-009 — gestiona certs
wildcard por DNS-01 en dev/local y el Origin CA de prod como archivo).

**Flujo general**: se programa **local** (tu Mac o cualquier PC, código propio,
sin conectarte a ningún servidor) → `git push` → PR contra `dev` → CI corre
automático (lint, tests, scanners de ADR-017) → merge → un **workflow de deploy
manual** (`workflow_dispatch`) construye la imagen, la publica en **ghcr.io** y
la despliega en el droplet correspondiente por Tailscale. **Ningún droplet se
usa para programar** — solo corren la imagen que el pipeline les manda.

```
infra/
├── docker/
│   ├── app.Dockerfile        PHP-FPM 8.4 · targets: dev (bind-mount, Xdebug) | prod (autocontenida)
│   ├── caddy.Dockerfile      Caddy + módulo DNS de Cloudflare
│   ├── entrypoint.sh         APP_ROLE = fpm | worker | scheduler
│   ├── Caddyfile.local       *.docket.test · CA interna de Caddy (sin ACME)
│   ├── Caddyfile.dev         *.staging.curaduria.app · Let's Encrypt DNS-01
│   ├── Caddyfile.prod        *.curaduria.app · Origin CA + trusted_proxies Cloudflare
│   └── php/                  php.ini · opcache · www.conf · healthcheck
├── compose/
│   ├── docker-compose.local.yml   TU MÁQUINA — programar día a día
│   ├── docker-compose.dev.yml     droplet docket-dev — staging compartido (deploy target)
│   └── docker-compose.prod.yml    droplet docket-prod (deploy target)
├── env/
│   ├── local.env.example     → copiar a local.env (tu máquina, gitignoreado)
│   ├── dev.env.example       → vive en docket-dev, cifrado SOPS en el host
│   └── prod.env.example      → vive en docket-prod, cifrado SOPS en el host
├── scripts/
│   ├── provision.sh          alta de droplet (Tailscale, hardening, ufw, fail2ban)
│   ├── cloudflare-ips.sh     origin lock: 80/443 solo desde Cloudflare (PROD)
│   └── backup-tenants.sh     pg_dump por tenant → Spaces, cifrado age (PROD, cron)
└── tailscale/acl.json        política de la malla
```

---

## Programar local (tu Mac o cualquier PC)

```bash
# una vez
cp infra/env/local.env.example infra/env/local.env   # completar (ver infra/dev-guide.md)
echo "127.0.0.1 docket.test central.docket.test tenant1.docket.test" | sudo tee -a /etc/hosts

cd infra/compose
docker compose -f docker-compose.local.yml --env-file ../env/local.env up -d --build

docker compose -f docker-compose.local.yml exec app php artisan key:generate
docker compose -f docker-compose.local.yml exec app php artisan migrate --force
```

**Confiar el certificado local en tu Mac** (evita la advertencia del
navegador; opcional — si no lo hacés, el navegador va a mostrar "conexión no
segura" y hay que aceptar manualmente cada vez). `caddy trust` corrido
*dentro* del contenedor solo confía la CA para llamadas hechas desde ahí
adentro — no alcanza al navegador del host. Hay que sacar el root cert del
volumen y agregarlo al keychain del Mac:

```bash
docker run --rm -v docket-local_caddy_data:/data alpine \
  cat /data/caddy/pki/authorities/local/root.crt > /tmp/caddy-local-root.crt

sudo security add-trusted-cert -d -r trustRoot \
  -k /Library/Keychains/System.keychain /tmp/caddy-local-root.crt
```

Editás con tu editor de siempre (el código real vive en `docket/` del repo,
montado dentro del contenedor). Guardaste → recargás
`https://docket.test:8443`. Puertos **8080/8443** en vez de 80/443 a
propósito (para no chocar con otros proyectos/servicios locales usando esos
puertos) — Caddy adentro del contenedor sigue en 80/443 normalmente. Nada de
esto toca los droplets.

### Troubleshooting local (lo que ya nos mordió una vez)

- **Siempre con `:8443` en la URL.** `http://docket.test/` o `docket.test`
  sin puerto van al 80/443 del *host* — que puede estar ocupado por **otro
  proyecto Docker tuyo** corriendo al mismo tiempo (nos pasó: otro proyecto
  con Caddy propio ya tenía el 80/443). Ese otro servidor no conoce
  `docket.test` y responde igual (200) pero con contenido irrelevante — se ve
  "en blanco", no es que nuestro stack se haya caído. `docker ps` para
  confirmar qué contenedor tiene cada puerto.
- **El redirect HTTP→HTTPS automático de Caddy no sirve acá.** Con Docker
  remapeando 8080/8443→80/443, el redirect automático de Caddy genera
  `Location: https://docket.test/` (puerto 443 implícito) — nada escucha ahí
  desde afuera. `Caddyfile.local` lo resuelve con `auto_https
  disable_redirects` + un `redir` explícito al puerto real. (Probamos primero
  `http_port`/`https_port`, la opción "oficial" de Caddy para este caso —
  en esta versión termina moviendo el bind real y rompe el mapeo de Docker;
  no usar.)
- **Advertencia de certificado no confiable**: normal la primera vez (ver
  arriba, "Confiar el certificado"). Si ya confiaste la CA y sigue avisando,
  reiniciá el navegador — Chrome/Safari cachean el estado de confianza.
- **El cert interno de Caddy dura ~12 h** y se renueva solo. No hay que hacer
  nada.

---

## `docket-dev` — staging compartido (aprovisionado)

Lo actualiza el **pipeline de deploy** después de cada merge a `dev` — nadie
entra a editar código ahí. Sirve para ver "qué hay desplegado ahora mismo".

| Recurso | Valor |
|---|---|
| Droplet `docket-dev` | `143.198.12.146` · NYC3 · VPC `docket-vpc` |
| Firewall `docket-fw` | SSH ← IP de casa (temporal) · 80/443 abiertos |
| Spaces | `docket-dev-files` @ `nyc3.digitaloceanspaces.com` |
| DNS (Cloudflare) | `*.staging` y `staging` → `143.198.12.146` (DNS only) |
| Tailscale | tag `tag:docket` · tailnet `tail133784.ts.net` |

Primer alta del droplet (una vez):

```bash
# por Tailscale SSH, como root:
ENV=dev HOSTNAME_TS=docket-dev TS_AUTHKEY=tskey-auth-xxx bash infra/scripts/provision.sh
mkdir -p /opt/docket && cd /opt/docket
# infra/env/dev.env se coloca ahí (cifrado con SOPS, ver ADR-017), NO se clona el repo completo
```

A partir de ahí, todos los despliegues los hace el workflow de deploy
(`docker compose -f docker-compose.dev.yml pull && up -d`) — ver
`.github/workflows/`.

### Configurar el deploy manual (`.github/workflows/deploy.yml`)

Por cada ambiente (`dev`, `prod`), crear un **GitHub Environment**
(`Settings → Environments`) con:

**Secrets:**
| Nombre | Qué es | Cómo se genera |
|---|---|---|
| `TS_OAUTH_CLIENT_ID` / `TS_OAUTH_SECRET` | credencial del runner de CI para unirse al tailnet como nodo efímero `tag:ci` | `login.tailscale.com/admin/settings/trust-credentials` → "Credential" → "OAuth" → paso Scopes, sección **Keys** → marcar "Auth Keys: Write" |
| `DEPLOY_SSH_KEY` | llave privada **propia de CI** (no la de un admin humano) autorizada en `authorized_keys` del droplet | generar un par ed25519 dedicado (`ssh-keygen`), agregar la pública al droplet, guardar la privada como este secret |

**Variables** (no sensibles, `Settings → Environments → <env> → Variables`):
| Nombre | Valor |
|---|---|
| `DROPLET_TS_HOSTNAME` | `docket-dev` (o `docket-prod`) — nombre MagicDNS del droplet en el tailnet |

No hace falta un token de ghcr.io persistido en el droplet: el workflow usa
el `GITHUB_TOKEN` efímero de la propia corrida (válido solo mientras dura el
run) tanto para publicar las imágenes como para que el droplet haga
`docker login` al momento de hacer `pull` — sin credencial cloud de larga
vida en el host (ADR-017 capa 5).

**Gotcha del OAuth client de Tailscale**: al marcar "Auth Keys: Write" en el
paso Scopes aparece un campo nuevo, fácil de pasar por alto, **"Tags
(required for write scope)"** — sin seleccionar ahí `tag:ci` explícitamente,
el botón "Generate credential" queda deshabilitado, pero si se fuerza o se
genera sin fijarse, el client queda sin permiso de otorgar ningún tag y
`tailscale up` falla en el deploy con `403 calling actor does not have
enough permissions`. Hay que seleccionar `tag:ci` ahí antes de generar — no
alcanza con que el ACL (`infra/tailscale/acl.json`) ya lo permita en
general, el client mismo necesita el permiso. **No** agregar `tag:docket`
acá — ese tag lo aplican los droplets vía `provision.sh`, no el pipeline de
CI (mínimo privilegio: el client de CI solo necesita poder ser `tag:ci`, no
poder re-taggear droplets).

**Nota sobre el secreto**: tanto el Client ID/Secret de Tailscale como los
secrets de GitHub se muestran **una sola vez** al crearlos — ninguno de los
dos se puede volver a ver después. No hace falta guardarlos en otro lado
"por si acaso": si se pierden o hay que rotarlos, el procedimiento es
siempre generar un client nuevo y reemplazar el secret en GitHub, nunca
recuperar el valor viejo.

El ACL de Tailscale (`infra/tailscale/acl.json`) ya tiene la regla para
`tag:ci` — aplicarlo en `login.tailscale.com/admin/acls/file` si todavía no
se hizo (ver `docs/dev-guide.md` §3).

---

## `docket-prod` — runbook de go-live (NADA de esto está aprovisionado)

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
8. **Registro de imágenes**: `ghcr.io` (namespace del repo/org) — gratis, autenticación nativa desde GitHub Actions. No hace falta token persistido en el droplet: `deploy.yml` usa el `GITHUB_TOKEN` efímero de cada corrida para el `docker login` remoto.
9. **Secretos**: `infra/env/prod.env` cifrado con SOPS+age en el host. Además, crear el GitHub Environment `prod` con sus propios `TS_OAUTH_CLIENT_ID`/`TS_OAUTH_SECRET`/`DEPLOY_SSH_KEY` y variable `DROPLET_TS_HOSTNAME=docket-prod` (ver "Configurar el deploy manual" arriba — mismo mecanismo que dev, credenciales propias de prod).
10. **Deploy** (workflow manual, `.github/workflows/deploy.yml`, elegir `prod`): build+push de imágenes → pull en el droplet por Tailscale → `migrate --force` → `tenants:migrate --force` → `up -d`.
11. **Cron** de backups: `backup-tenants.sh` diario + prueba de restore trimestral.

### Diferencias entre los tres ambientes (para no llevarse sorpresas)

| | **local** (tu máquina) | **dev** (droplet, staging) | **prod** (droplet) |
|---|---|---|---|
| Quién edita código ahí | vos, con tu editor | nadie — solo el pipeline | nadie — solo el pipeline |
| Imagen app | build local, target `dev`, bind-mount | pull de ghcr.io, target `prod` | pull de ghcr.io, target `prod` |
| Dominio | `*.docket.test` | `*.staging.curaduria.app` | `*.curaduria.app` |
| TLS | CA interna de Caddy | Let's Encrypt DNS-01 (Cloudflare) | Origin CA de Cloudflare |
| PostgreSQL | contenedor local | contenedor en el droplet | DO Managed PG, SSL `require`, IP privada |
| 80/443 | tu máquina, localhost | abiertos | solo rangos de Cloudflare |
| SSH público | n/a | permitido (temporal, se cierra con Tailscale) | denegado (solo Tailscale) |
| Secretos | `local.env`, tuyo | `dev.env`, cifrado SOPS en el host | `prod.env`, cifrado SOPS en el host |

La única diferencia real entre **dev** y **prod** es la base de datos (contenedor
vs. Managed PG) y el origen del certificado — todo lo demás (imagen, topología
de contenedores, config) es idéntico. Eso es lo que hace que "anda en dev" sea
una señal confiable de "va a andar en prod".
