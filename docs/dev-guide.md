# Dev Guide — Servicios externos

Guía de onboarding para quien se sume al proyecto. Explica **cada servicio
externo** que usa CuraduriAPP: qué es, por qué lo elegimos, qué se creó en él
paso a paso, y cómo se usa en el día a día.

- Decisiones de fondo: `docs/adr/` (sobre todo ADR-003, ADR-007, ADR-008,
  ADR-009, ADR-017).
- Detalle operativo de infra: `infra/README.md`.
- **Ningún secreto vive en este documento ni en el repo.** Los tokens y claves
  están en el gestor de secretos del equipo y, en los servidores, cifrados con
  SOPS+age (ADR-017). Acá solo hay nombres de recursos e IDs no sensibles.

## Panorama

| Servicio | Para qué | Estado |
|---|---|---|
| **DigitalOcean** | Hosting: servidores, base de datos administrada, almacenamiento de archivos | Dev aprovisionado · Prod pendiente (go-live) |
| **Cloudflare** | DNS del dominio + seguridad de borde (WAF, DDoS, TLS) | Zona existente (landing) · DNS de dev creado · Pro/WAF pendiente |
| **Tailscale** | Red privada para administrar los servidores sin exponer SSH a internet | Configurado |
| **GitHub** | Repositorio + CI/CD (lint, tests, escáneres, deploy manual) | Repo activo · Environments/secrets pendientes |
| **Brevo** | Envío de email / SMS / WhatsApp transaccional | Pendiente (ADR-008) |
| **Proveedor LLM** | Características de IA (OCR+LLM, RAG, redacción asistida) | Pendiente (ADR-004) |
| **Proveedor de firma** | Firma electrónica de actos administrativos | Pendiente (ADR-015) |
| **Sentry** | Captura de errores de la aplicación en runtime | Pendiente (ADR-017) |

---

# 1. DigitalOcean

## Qué es

Proveedor de infraestructura cloud (servidores virtuales, bases de datos
administradas, almacenamiento tipo S3, redes). Equivalente a AWS/GCP pero más
simple y barato para nuestra escala.

## Por qué lo usamos

ADR-009. Necesitamos hosting propio para un SaaS **database-per-tenant**
(ADR-002): una app desplegada, una base PostgreSQL física por curaduría. DO da
todo lo necesario (droplets con Docker, PostgreSQL administrado con backups y
PITR, almacenamiento S3-compatible, red privada) sin la complejidad de los
hyperscalers. La cuenta es del proveedor del SaaS (ADR-001).

## Qué se creó (paso a paso)

Región de todo: **NYC3** (la más cercana a Colombia con disponibilidad de
todos los productos). Todo dentro de una misma red privada.

### 1.1 VPC `docket-vpc`
`Networking → VPC → Create` · región NYC3.
- **Qué es**: una red privada aislada. Los recursos dentro se hablan por IP
  privada sin salir a internet.
- **Para qué**: que la app llegue a la base de datos por IP privada, nunca
  pública (ADR-017). Todo recurso nuevo se crea *dentro* de esta VPC.

### 1.2 Droplet `docket-dev`
`Create → Droplets` · imagen **Marketplace → Docker** · plan Basic 2 GB / 1 vCPU
· VPC `docket-vpc` · SSH key · Monitoring on.
- IP pública: `143.198.12.146`.
- **Qué es**: un servidor virtual Linux con Docker preinstalado.
- **Para qué**: corre el entorno de **desarrollo/staging** completo (app +
  workers + Redis + PostgreSQL en contenedor + Caddy) vía `docker compose`.
  Mismo stack que prod para no llevarse sorpresas al desplegar. Se prueba cada
  feature acá antes de prod.
- El droplet `docket-prod` es idéntico pero se crea recién en el go-live (para
  no incurrir en el gasto antes de tiempo). Runbook en `infra/README.md`.

### 1.3 Reserved IP (prod)
Se creó y luego se **soltó** — se vuelve a reservar en el go-live.
- **Qué es**: una IP pública fija que se puede reasignar entre droplets.
- **Para qué**: que la IP de prod no cambie si hay que recrear el droplet (así
  el registro DNS de Cloudflare no se toca). En dev no hace falta.

### 1.4 Cloud Firewall `docket-fw`
`Networking → Firewalls` · asignado a `docket-dev`.
- Inbound: SSH 22 ← solo la IP del desarrollador · HTTP 80 y HTTPS 443 ←
  abiertos (en dev).
- Outbound: todo permitido.
- **Qué es**: un firewall a nivel de red de DO, delante del droplet.
- **Para qué**: reducir la superficie expuesta. En **prod** se endurece: 80/443
  solo desde rangos de Cloudflare (`infra/scripts/cloudflare-ips.sh`, "origin
  lock" de ADR-017) y **sin** regla SSH pública (el acceso va por Tailscale).

### 1.5 Managed PostgreSQL `docket-prod-db` — PENDIENTE (go-live)
`Create → Databases` · PostgreSQL 17 · misma VPC.
- **Qué es**: un clúster PostgreSQL administrado por DO (backups automáticos,
  point-in-time recovery, parcheo, failover).
- **Para qué**: la base de datos de **producción**. Contiene la base central
  (catálogo de tenants + identidades, ADR-005) y una base por curaduría. En dev
  no se usa: `docket-dev` corre PostgreSQL en un contenedor más (con la
  extensión `pgvector` para la IA), para no pagar un segundo clúster.
- Al crearlo: *Trusted Sources* = solo `docket-prod`; conexión por red privada
  con SSL obligatorio; usuario de app dedicado (no `doadmin`).

### 1.6 Spaces `docket-dev-files`
`Create → Spaces Object Storage` · NYC3 · File Listing **Restricted** (privado)
· CDN desactivado.
- Endpoint: `https://nyc3.digitaloceanspaces.com` · bucket `docket-dev-files`.
- **Qué es**: almacenamiento de objetos compatible con la API de Amazon S3.
- **Para qué**: guardar todos los archivos de los expedientes (planos,
  escrituras, PDFs, escaneos) fuera del servidor de la app (ADR-007). La app
  los sube/baja vía Flysystem; el servidor queda *stateless*. En prod habrá
  `docket-prod-files` y `docket-prod-backups` (este último con versionado/objeto
  inmutable para resiliencia ante ransomware, ADR-017).
- **Access Key** `docket-dev-app`: `API → Spaces Keys → Create Access Key`,
  **scopeada solo a este bucket** (least privilege), permisos read/write/delete/
  list. El secret vive en el gestor de secretos y en `infra/env/dev.env` (no en
  el repo).

## Cómo se usa

- El equipo administra estos recursos desde el panel de DO y con `doctl` (CLI).
- La app consume: PostgreSQL (conexión), Spaces (Flysystem, disco `s3`).
- `infra/scripts/provision.sh` prepara un droplet nuevo; `cloudflare-ips.sh` y
  `backup-tenants.sh` son tareas de prod.

## Acceso para un dev nuevo

Pedir al owner de la cuenta DO: invitación al **team** de DO (rol según
necesidad), y una **Spaces key** propia o acceso a la del equipo. Para SSH a los
droplets: ver Tailscale (sección 3).

---

# 2. Cloudflare

## Qué es

Servicio de DNS + CDN + seguridad de borde. Todo el tráfico hacia el dominio
pasa primero por su red: filtra ataques, cachea, termina TLS.

## Por qué lo usamos

- El dominio **`curaduria.app`** ya estaba en Cloudflare (lo usa la landing).
  Se reutiliza para la plataforma (ADR-009 amendment 2026-09-01).
- Es la palanca principal contra **DDoS / intentos de tumbar el sitio** y la
  capa de **WAF** (ADR-017). A nuestra escala no se construye mitigación
  propia: se pone un proveedor de borde delante.

## Qué se creó (paso a paso)

### 2.1 Registros DNS de dev
`curaduria.app → DNS → Records → Add record`:
- `A` · `*.staging` · `143.198.12.146` · **DNS only** (nube gris)
- `A` · `staging` · `143.198.12.146` · **DNS only**
- **Para qué**: `staging.curaduria.app` = dominio central del app en dev;
  `<algo>.staging.curaduria.app` = subdominio por tenant (multi-tenancy se
  resuelve por subdominio, ADR-002). "DNS only" = Cloudflare no hace de proxy en
  dev; el navegador pega directo al droplet.

### 2.2 API Token `docket-dev-letsencrypt`
`My Profile → API Tokens → Create Token → Custom`:
- Permisos: **Zone → DNS → Edit** y **Zone → Zone → Read**
- Zone Resources: solo `curaduria.app`
- **Para qué**: Caddy (en el droplet) usa este token para resolver el reto
  **DNS-01** de Let's Encrypt y emitir/renovar el certificado **wildcard**
  `*.staging.curaduria.app` automáticamente. El token está en el gestor de
  secretos y en `infra/env/dev.env` como `CLOUDFLARE_API_TOKEN`.

### 2.3 PENDIENTE (go-live)
- Subir la zona a plan **Pro** (~USD 20/mes): habilita WAF con reglas
  administradas y rate limiting.
- DNS: `*.curaduria.app` → Reserved IP de prod, **Proxied** (nube naranja).
- SSL/TLS: modo **Full (strict)**, TLS mínimo 1.2, HSTS + preload.
- **Origin CA certificate** para `*.curaduria.app` → se instala en Caddy en el
  droplet de prod (`Caddyfile.prod`).
- WAF: Cloudflare Managed + OWASP Core Ruleset; rate limiting en `/login` y
  `/verificar/*`; Bot Fight Mode.

## Cómo se usa

- DNS: cualquier subdominio nuevo se agrega acá.
- En dev, Caddy renueva el cert wildcard solo con el token — no hay que hacer
  nada.
- En prod, Cloudflare está en modo proxy: filtra, cachea estáticos, y el origen
  solo confía en su tráfico.

## Acceso para un dev nuevo

Invitación al account de Cloudflare con rol acorde (normalmente
**DNS** o **Domain Administrator** sobre `curaduria.app`). La raíz del dominio la
usa la landing: coordinar antes de tocar registros existentes.

---

# 3. Tailscale

## Qué es

Una **VPN de malla** basada en WireGuard. Crea una red privada ("tailnet")
donde cada dispositivo (tu laptop, los droplets, un runner de CI) tiene una IP
privada estable y se hablan cifrado punta a punta, estén donde estén.
Explicación larga: buscar "Tailscale" en el historial del proyecto o
tailscale.com.

## Por qué lo usamos

ADR-017. **No queremos el puerto SSH (22) abierto a internet** en los
servidores — es ruido constante de fuerza bruta y exposición a CVEs de OpenSSH.
Con Tailscale el droplet no expone SSH: se entra por la malla privada. Es la
versión pragmática de "bastion/VPN" para un equipo chico (no hay servidor VPN
propio que mantener).

## Qué se creó (paso a paso)

### 3.1 Cuenta y tailnet
Registro en tailscale.com con login de Google/GitHub (ese IdP es el login;
**2FA obligatoria en esa cuenta**). Plan Personal (gratis, hasta 100
dispositivos).
- Tailnet: `tail133784.ts.net`.

### 3.2 Dispositivo: la laptop
Instalar el cliente (`brew install --cask tailscale`), `Log in`. La máquina
queda en el tailnet — es desde donde se hace SSH a los droplets.

### 3.3 Tag `tag:docket`
`Access Controls` (login.tailscale.com/admin/acls) → se agregó al policy file:
```jsonc
"tagOwners": { "tag:docket": ["autogroup:admin"] }
```
- **Para qué**: etiquetar los droplets como "de un rol" y no "de una persona",
  para que las ACLs y la expiración de llaves se comporten bien con servidores.
- La política endurecida definitiva está en `infra/tailscale/acl.json` (se
  aplica cuando se cierre la fase de hardening; hoy la política es allow-all).

### 3.4 Auth key
`Settings → Keys → Generate auth key` · **Reusable** · **Ephemeral: no** · tag
`tag:docket`.
- **Para qué**: que un droplet se una a la malla solo, sin login interactivo.
  La usa `infra/scripts/provision.sh` (`tailscale up --authkey=... --advertise-tags=tag:docket --ssh`).
- Está en el gestor de secretos. Se rota tras enrolar los droplets.

## Cómo se usa

- SSH a un droplet: `ssh root@docket-dev` (MagicDNS resuelve el nombre dentro
  de la malla) o `ssh root@<IP 100.x>`.
- El deploy desde GitHub Actions se une a la malla de forma efímera para correr
  los comandos en el droplet (sin llaves cloud de larga vida).
- Cuando Tailscale está activo en un droplet, se elimina la regla SSH pública
  del Cloud Firewall.

## Acceso para un dev nuevo

El admin del tailnet lo invita (`Users → Invite`). El dev instala el cliente,
inicia sesión, y ya puede hacer SSH a las máquinas que la ACL le permita.

---

# 4. GitHub

## Qué es

Hosting del repositorio Git + GitHub Actions (CI/CD).

## Por qué lo usamos

ADR-009 / ADR-011. Un solo repo (monorepo: `docs/`, `docket/`, `infra/`,
`landing/`). CI corre lint, análisis estático, tests y escáneres de seguridad en
cada push/PR; el **deploy es manual** (`workflow_dispatch`, se elige ambiente) —
decisión explícita, no automático en cada merge.

## Qué se creó / se creará

- **Repositorio**: ya existe (rama principal `main`, rama de integración `dev`).
- **Environments** `dev` y `prod` (PENDIENTE): `Settings → Environments`.
  Guardan los secrets por ambiente (`APP_KEY`, credenciales de BD, Spaces,
  token de Cloudflare, `TS_AUTHKEY`, cert Origin CA, etc.). Ver la lista en
  `infra/env/*.env.example`.
- **Deploy key / OIDC** (PENDIENTE): el runner entra a los droplets por
  Tailscale + SSH; el token de DO (solo para tareas de infra y backups) se
  guarda como secret.
- **Branch protection** en `main`/`dev`: revisión requerida, checks de CI
  obligatorios.

## Cómo se usa

- PR contra `dev` → corre CI. Merge a `dev` → deploy manual a `docket-dev`.
- Promoción a `main` → deploy manual a `docket-prod`.
- Los workflows viven en `.github/workflows/` (se crean en la Fase 4).

## Acceso para un dev nuevo

Invitación al repo con permiso `Write`. Para disparar deploys: acceso al
environment correspondiente.

---

# 5. Servicios pendientes (contexto para cuando se activen)

| Servicio | Qué es | Para qué en el proyecto | ADR |
|---|---|---|---|
| **Brevo** | Plataforma de mensajería transaccional (email/SMS/WhatsApp) | Enrolamiento diferido, notificaciones de cambio de estado, alertas de plazos. WhatsApp requiere verificación de negocio ante Meta + plantillas aprobadas (tramitar temprano). | ADR-008 |
| **Proveedor LLM** (por elegir) | API de modelo de lenguaje | Extracción de datos de documentos (OCR+LLM), RAG normativo, redacción asistida de actas/actos, chatbot de estado. Datos personales en prompts se rigen por ADR-017 (redacción + DPA + no-entrenamiento). | ADR-004 |
| **Proveedor de firma** (Certicámara / GSE / otro) | Firma electrónica/digital certificada colombiana | Firma del curador sobre el acto administrativo congelado, con trazabilidad legal. | ADR-015 |
| **Sentry** | Captura y agregación de errores de aplicación | Observabilidad en runtime, con PII depurada. | ADR-017 |
| **Registro de imágenes** | ghcr.io o DO Container Registry | Guardar las imágenes Docker que CI construye para que prod haga `pull`. | ADR-009 |

---

# Checklist de onboarding para un dev nuevo

1. [ ] Acceso al **repo** de GitHub (`Write`).
2. [ ] Invitación al **team de DigitalOcean** + una **Spaces key** (o la del equipo).
3. [ ] Invitación al **tailnet de Tailscale**; instalar cliente + 2FA en el IdP.
4. [ ] Acceso a la **zona de Cloudflare** `curaduria.app` (rol DNS).
5. [ ] Recibir el archivo de **secretos de dev** (`infra/env/dev.env`) por canal seguro, o las claves para descifrarlo con SOPS.
6. [ ] Leer `docs/adr/` (al menos ADR-002, 003, 005, 009, 011, 017), `docs/estructura-repo.md` e `infra/README.md`.
7. [ ] Levantar el entorno dev siguiendo `infra/README.md`.
