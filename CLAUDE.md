# Proyecto: SaaS para Curadurías Urbanas (nombre provisional)

## Contexto

Modernización de un software de gestión de trámites para Curadurías Urbanas en
Colombia. El sistema legado (~20 años, VB6 + Access) sigue en uso activo en 5
curadurías. El nuevo sistema es una plataforma web SaaS con características de
IA. El autor del proyecto es también el autor del legado: el conocimiento de
dominio es profundo y debe capturarse en docs/dominio/ a medida que se defina.

## Dominio

Las Curadurías Urbanas son oficinas privadas que ejercen función pública:
estudian, tramitan y expiden licencias urbanísticas (urbanización, construcción,
parcelación, subdivisión, etc.). Marco legal: Ley 388/1997, Ley 1796/2016,
Decreto 1077/2015 (principal) más concretamente del artículo 2.2.6.1.1.1 hasta el artículo 2.2.6.6.9.2, dónde se dan las principales definiciones y se detalla el proceso de estudio y expedición de licencias, tambien el procedimiento para el cálculo de expensas. Vigiladas por la Superintendencia de Notariado y Registro.
Las licencias son actos administrativos: trazabilidad y valor legal del
expediente son críticos.

## Decisiones tomadas (ver docs/adr/)

- **ADR-001 — Modelo SaaS**: suscripción; el proveedor opera la plataforma.
- **ADR-002 — Database-per-tenant**: una app desplegada; base PostgreSQL física
  independiente por curaduría; base central solo con catálogo de tenants e
  identidades; resolución de tenant por subdominio.
- **ADR-003 — Stack**: **Laravel** (API) + **Vue.js** (SPA, Sanctum) +
  **PostgreSQL** (+pgvector) + Redis + Docker. Multi-tenancy con
  **stancl/tenancy**. Amendment 2026-09-11: SPA de mismo origen que la API
  (no una app aparte) — cookie de sesión scopeada por subdominio de tenant.
- **ADR-004 — IA** (borrador): extracción documental OCR+LLM, verificación de
  completitud, RAG normativo (nacional compartido + local por tenant en
  pgvector), redacción asistida de actos, consultas contextualizadas por expediente, 
  asistencia en generacion de listados con caracteristicas especiales, 
  acompañamiento en cada etapa del expediente.
- **ADR-005 — Identidad y membresías**: identidad única en la central (UUID,
  documento como llave natural) **solo para solicitantes y usuarios de
  plataforma**; las cuentas de empleado son locales al tenant, las gobierna
  cada curaduría y absorben la membresía (rol + vigencia + estado); RBAC en la
  base de cada tenant. La central nunca revela en qué curadurías tiene trámites
  o empleo una persona. Amendments: 2026-08-27 (persona jurídica), 2026-09-02
  (empleado local al tenant, sin vista nacional, conflicto de interés).
- **ADR-006 — Enrolamiento**: radicación en línea desde el MVP; todo
  solicitante es usuario; radicación por ventanilla crea cuenta pendiente de
  activación con token de un solo uso (email / SMS-WhatsApp / código impreso).
- **ADR-007 — Archivos**: S3-compatible (DO Spaces en prod, MinIO en dev) vía
  Flysystem; prefijo/bucket por tenant; cifrado en reposo; offboarding incluye
  archivos.
- **ADR-008 — Mensajería**: Brevo (email/SMS/WhatsApp); envíos por cola
  tenant-aware; WhatsApp requiere plantillas aprobadas por Meta (tramitar
  temprano); SPF/DKIM.
- **ADR-009 — Infraestructura**: DigitalOcean; **DO Managed PostgreSQL** (un
  cluster, N bases); droplet de app con Docker Compose (app + workers +
  Redis); DNS/TLS wildcard; pg_dump por tenant hacia Spaces (feature
  vendible). Descartado contenedor o droplet por tenant (sobre-ingeniería;
  queda como palanca futura para tenants premium).
- **ADR-010 — Ciclo de vida del tenant**: demo / activo / suspendido
  (solo-lectura, nunca bloqueo total) / terminado (entrega dump + archivos +
  snapshot de identidades). Provisioning = un comando.

- **ADR-012 — Verificación pública de actos administrativos** (borrador):
  código/QR de verificación sin login, hash de integridad del documento.
- **ADR-013 — Analítica operativa** (borrador): BI por curaduría sobre el
  audit log (cuellos de botella, cumplimiento de plazos, carga por
  encargado).
- **ADR-014 — Gestión de plazos legales y alertas predictivas** (borrador,
  **imprescindible**): servicio central de días hábiles colombianos con
  suspensión/reanudación/prórroga, alertas antes de vencer un plazo.
- **ADR-015 — Firma electrónica de actos administrativos** (borrador):
  interfaz + proveedor colombiano externo (por elegir), firma sobre
  documento congelado, integrada con ADR-012.
- **ADR-016 — Campos personalizados por tenant** (modelo cerrado, implementación
  por fases): catálogo tenant-local de definiciones + ubicaciones + valores en
  columna `custom_fields jsonb`; sin EAV, sin DDL en runtime, esquema idéntico
  entre tenants. Cubre formularios, detalle, campos de fusión, listados/filtros,
  analítica y portal ciudadano; puede exigir campos para una transición de
  estado. Resuelve el problema del legado (una versión por curaduría).
- **ADR-017 — Seguridad de la plataforma** (postura fijada, implementación por
  fases): defensa en profundidad por capas. Borde Cloudflare Pro (DDoS + WAF +
  rate limiting + bots), origin lock, DO VPC, malla VPN sin SSH público,
  hardening CIS de host/Docker, cabeceras + CORS + rate limiting en la app,
  Argon2id + MFA (opcional-incentivada para empleados, obligatoria para
  plataforma), ClamAV en subidas, credencial de BD por tenant, scanners de
  seguridad en CI, backups inmutables, plan de respuesta a incidentes. Baseline
  Ley 1581 + alineación OWASP ASVS / CIS. Políticas en `docs/seguridad/`.
  Amendment 2026-09-13: cierre de capa 4 (identidad y acceso) — MFA pasa a
  **obligatoria** para curador y admin de tenant (ya no opcional); números
  concretos de contraseña/sesión/rate-limiting por dominio de identidad, ver
  `docs/seguridad/politicas.md` §2.

- **ADR-011 — Principios de código**: Laravel idiomático + capa de dominio:
  acciones de dominio como única vía de escritura, controladores delgados
  (Form Request → Policy → Action con DTO → API Resource, cero lógica de
  negocio), máquina de estados para el expediente, eventos→audit, enums,
  sin repositorio genérico sobre Eloquent (query scopes/classes para
  lecturas), interfaces solo en fronteras (Brevo, SNR, firma, LLM, PDFs),
  SOLID pragmático + YAGNI. Pint + Larastan + Pest (tests obligatorios en
  transiciones, expensas, numeración y aislamiento de tenants). Vue 3
  Composition API + **TypeScript** + Pinia. **Código en inglés** con
  glosario de dominio en docs/dominio/glosario.md.

## Principios del proyecto

1. Aislamiento de datos de negocio por curaduría es innegociable; la central
   solo contiene catálogo de tenants e identidades de acceso de solicitantes y
   de plataforma (las cuentas de empleado viven en la base de cada tenant —
   ADR-005 amendment 2026-09-02).
2. Trazabilidad legal: audit log completo desde el día uno.
3. Provisioning y migraciones multi-tenant automatizados (stancl/tenancy:
   `tenants:migrate`), con manejo de fallos parciales.
4. Ningún job se despacha fuera de contexto de tenant (salvo los de
   plataforma).
5. La IA asiste, no decide: todo acto administrativo pasa por revisión humana.
6. Migración del legado 1:1 — cada curaduría tiene su propio .mdb → su
   PostgreSQL.
7. Seguridad por defecto / defensa en profundidad (ADR-017); la disponibilidad
   es riesgo legal (hay plazos en días hábiles corriendo).

## Estado actual

Planeación. Cuestiones generales de arquitectura y principios de código
CERRADOS (ADR-001 a 011; ADR-005 con amendments 2026-08-27 — identidad de
persona jurídica — y 2026-09-02 — cuentas de empleado locales al tenant,
fusionadas con la membresía; sin vista nacional para solicitantes; conflicto
de interés). **Campos personalizados por tenant** (ADR-016, 2026-09-02):
modelo cerrado, implementación por fases — catálogo + JSONB, sin DDL por
tenant; elimina el problema del legado de una versión por curaduría.
**Postura de seguridad CERRADA** (ADR-017, 2026-09-06): defensa en profundidad
por capas, Cloudflare Pro, malla VPN, baseline Ley 1581 + OWASP/CIS; controles
de infra en el amendment 2026-09-06 de ADR-009; políticas por redactar en
`docs/seguridad/`.
**Modelo de dominio del núcleo CERRADO** (2026-08-27 a
2026-08-31): Solicitante, Predio, Expediente, Tipo de Trámite (reemplaza a
"Licencia" — incluye Otras Actuaciones), Acto Administrativo, Documento —
ver `docs/dominio/`. Radicación quedó como atributos de Expediente, no como
entidad propia. **Flujo del trámite / máquina de estados CERRADO**
(2026-08-31): `docs/dominio/flujo-tramite.md`, núcleo común contrastado
contra el listado real de estados de una curaduría, configurable por
curaduría. Nombre de producto: **CuraduriAPP** (decidido 2026-08-31, dominio
ya registrado). Nombre clave del repo (distinto, solo interno): **Docket**.

**Diferenciadores de producto en borrador** (ADR-004 ampliado, ADR-012 a
015 — ver `docs/vision-producto.md`): IA copiloto en cada paso del flujo,
verificación pública de actos, analítica operativa, gestión de plazos con
alertas predictivas (imprescindible), firma electrónica.

**Landing (`landing/curaduria-digital-colombia/`) en producción** desde
2026-08-31: `curaduria.app`, captura de leads a Brevo funcionando, alojada
en Cloudflare Workers (TanStack Start/Nitro).

**Arrancando implementación de la plataforma real** (2026-09-01): orden
acordado — esqueleto del repo (Laravel + stancl/tenancy + Vue/TS) → base
central/provisioning → identidad/auth → migraciones del núcleo →
máquina de estados del Expediente → construir pantalla por pantalla desde
ahí (los pendientes de `docs/preguntas-abiertas.md` se resuelven sobre la
marcha, no antes).

**Infra de dev aprovisionada y esqueleto CERRADO y verificado localmente**
(2026-09-11): droplet `docket-dev`, DNS, Tailscale, Spaces ya creados (ver
`docs/dev-guide.md` para el detalle por servicio); `infra/` con Docker/Caddy
para los tres ambientes (local/dev/prod — ver `infra/README.md`, incluye
troubleshooting de lo que ya se depuró). `docket/` = Laravel 13 +
stancl/tenancy + Sanctum SPA + Vue 3/TS/Pinia, probado de punta a punta en
local (dominio central + subdominio de tenant, Pint/Larastan/Pest en verde).
Amendment 2026-09-11 a ADR-003: modelo de servido del frontend (SPA mismo
origen, sin login server-rendered). Infraestructura de prod (`docket-prod`)
concretada en el amendment 2026-09-01 de ADR-009 — **pendiente de ejecutar**
(runbook completo en `infra/README.md`), se hace recién en el go-live.

**Deploy real a `docket-dev` CERRADO y verificado end-to-end** (2026-10-08):
CI (`.github/workflows/ci.yml` — Pint/Larastan/Pest + composer/npm audit +
gitleaks + Semgrep + Trivy) y deploy manual
(`.github/workflows/deploy.yml` — build+push a ghcr.io, conexión por
Tailscale con nodo efímero `tag:ci`, migrate + tenants:migrate + up) ambos
verificados en verde contra `docket-dev` real — `https://staging.curaduria.app`
responde `200` desde el droplet, con los 6 servicios (`app`/`caddy`/`db`/
`redis`/`sched`/`worker`) corriendo. Bugs reales encontrados y corregidos en
el camino (detalle en `infra/README.md` troubleshooting): `entrypoint.sh`
ignoraba el comando pasado a `docker compose run` (siempre arrancaba
`php-fpm` por `APP_ROLE`); el heredoc de SSH del deploy perdía los comandos
posteriores a `migrate` porque `docker compose run` sin `-T </dev/null`
consumía el resto del heredoc como stdin; `APP_ENV` mal copiado en
`dev.env.example`. Pendiente, no bloqueante: Dependabot
(`docs/preguntas-abiertas.md`) para que bumps de seguridad no vuelvan a
aparecer a mitad de un deploy.

**Siguiente paso**: base central/provisioning (ADR-002/ADR-010) — hoy solo
existe el esqueleto de `Tenant`/`Domain`, falta el flujo real de alta de
curaduría.

**Principio de trabajo para lo que sigue**: al capturar conocimiento del
legado, no asumir que su diseño (VB6+Access, 20 años) es el patrón a
replicar — extraer el hecho de negocio y proponer proactivamente el patrón
moderno equivalente, cuestionando el legado por defecto.

## Convenciones de documentación

- Decisiones: docs/adr/ADR-NNN-titulo.md (Contexto, Decisión, Consecuencias,
  Estado). Dominio: docs/dominio/. Pendientes: docs/preguntas-abiertas.md.
  Seguridad: docs/seguridad/. Estructura del repo: docs/estructura-repo.md.
  Onboarding y servicios externos: docs/dev-guide.md. Infra operativa:
  infra/README.md.
- Al tomar una decisión nueva: crear/actualizar el ADR y reflejarla aquí si es
  estructural. Idioma: español.
