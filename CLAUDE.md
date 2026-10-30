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
  identidades; resolución de tenant por subdominio. Clarificación 2026-10-09:
  el cambio de curador NO cambia la base (el curador y su período son datos
  dentro de ella); el dominio es de la curaduría.
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
  snapshot de identidades). Provisioning = un comando. Amendment 2026-10-09:
  estados `provisioning`/`failed` del proceso de alta y compensación de
  fallos (reintentar = alta nueva).

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
  glosario de dominio en docs/dominio/glosario.md. Amendment 2026-10-12:
  **todos los tests corren contra PostgreSQL real** (base exclusiva
  `docket_test`, forzada en `phpunit.xml` y con salvaguarda en `TestCase`).

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

**Base central y provisioning CERRADO** (2026-10-09, probado contra
PostgreSQL real): `php artisan tenants:provision "<nombre>" <subdominio>
[--active]` da de alta una curaduría (catálogo + dominio + base
`tenant<uuid>` + migraciones) y deja el tenant en `demo`/`active`; ante un
fallo compensa (borra la base y libera el dominio) y lo deja en `failed`.
Vive en `docket/app/Platform/` como **Action reutilizable**
(`ProvisionTenant` + DTO `ProvisionTenantData`): el comando es solo la
primera puerta de entrada; el panel de administración de plataforma (post
identidad/auth, requiere login+MFA+RBAC) invocará la misma Action desde un
controlador sin reescribir nada. Detalle en el amendment 2026-10-09 de
ADR-010. El catálogo `tenants` tiene columnas reales (`name`, `status`,
`failure_reason`, `provisioned_at`) y el host de cada curaduría es
`<subdominio>.<PLATFORM_TENANT_BASE_DOMAIN>` (por ambiente; **el `dev.env`
del droplet necesita `PLATFORM_TENANT_BASE_DOMAIN=staging.curaduria.app`**).
Los pendientes del bloque (configuración de la curaduría dentro del tenant,
período del curador, suspensión/reactivación, planes, credencial de BD por
tenant, seed base, panel) están en `docs/preguntas-abiertas.md`.

**Tests con PostgreSQL real** (2026-10-12, ADR-011 amendment): una sola
modalidad, base exclusiva `docket_test`. Se corren desde el contenedor:
`docker compose -f docker-compose.local.yml --env-file ../env/local.env exec
app vendor/bin/pest` (ver `infra/README.md`). El CI levanta un servicio
Postgres; ese cambio de `ci.yml` ya corrió en verde en GitHub (2026-10-12).

**Infraestructura de tests de tenants LISTA** (2026-10-12): el **test de
aislamiento** (ADR-011/017) ya no es un `todo` — prueba con dos tenants reales
y una tabla sonda que A nunca ve datos de B (`tests/Feature/Tenancy/
DatabaseIsolationTest.php`). Para tests de dominio sobre la base de un tenant:
`uses(Tests\Concerns\UsesTenantDatabase::class)` (tenant de pruebas compartido
por proceso + transacción revertida por test; las bases de tests se llaman
`tenanttest_<uuid>`, `make test-clean` borra las huérfanas). Nunca se clona un
tenant de desarrollo. Detalle en el amendment 2026-10-12 de ADR-011.

**Identidad/auth, paso 1: usuarios de plataforma** (2026-10-18, en curso): tabla
`platform_users` y guard `platform` propios en la central (ADR-005, amendment
2026-10-18: tabla propia, no un flag). Login/logout/me en
`/api/platform/*` solo en dominios centrales, Argon2id, contraseña de 12+ con
chequeo HIBP, bloqueo tras 5 fallos en 15 min, sesión de 15 min de inactividad
y 8 h absolutas. Alta con `php artisan platform:create-user "<nombre>"
<email>` (pide la contraseña). **MFA de plataforma** (2026-10-25, TOTP +
8 recovery codes, obligatoria): la contraseña sola no abre el panel
(`RequirePlatformMfa`); la pantalla `/platform/mfa` activa o verifica el
segundo factor; `platform:reset-mfa <email>` lo reinicia desde consola.
**Falta**: sesiones en Redis y revocación, solicitantes.

**Cuentas de empleado y RBAC, paso 1** (2026-10-26, en curso): `employees` y las
tablas de `spatie/laravel-permission` en la base de cada curaduría
(`database/migrations/tenant/`), dominio en `app/Domain/Identity/` (`Employee`,
`EmployeeRole` con 12 roles, `CreateEmployee`, `SeedEmployeeRoles`). Al
provisionar se siembran los roles y, con `--admin-name/--admin-email`, se crea
el primer administrador sin contraseña (queda pendiente de invitación).
**Paso 2, login del empleado** (2026-10-29, en curso): `POST
/api/employee/{login,logout,mfa/*}` y `GET /api/employee/me` en el subdominio de
la curaduría (guard `employee`, tenancy por dominio), sesión de 30 min / 12 h,
bloqueo tras 5 fallos, y la misma MFA que plataforma (`app/Support/Mfa/`):
obligatoria para curador y admin, opcional para el resto pero exigida si la
activaron (`RequireEmployeeMfa`). Una cuenta suspendida o fuera de vigencia
pierde la sesión en la siguiente petición. Hasta que exista la invitación, la
contraseña se fija con `employees:set-password <subdominio> <email>`. En el
frontend, el servidor le dice a la SPA si está en el dominio central o en una
curaduría (`data-context` de `#app`) y solo existen las rutas de ese contexto:
`/login`, `/mfa` e inicio en la curaduría; `/platform/*` en el central. Las
pantallas de login y MFA son componentes compartidos
(`features/auth/components/`) y el store de sesión sale de una fábrica
(`defineAuthStore`).
**Paso 3, invitación por correo** (2026-10-30, en curso):
`InviteEmployee` crea un token de un solo uso (solo se guarda su hash, vence a
los 7 días) y envía el correo por la cola (`EmployeeInvitationMail`, cifrado);
el enlace `https://<curaduría>/invitation/<token>` lo atiende
`GET/POST /api/employee/invitation/{token}[/accept]` (público, 404 uniforme).
Al dar de alta una curaduría con admin se le invita solo cuando el tenant ya
está operable; `employees:invite <subdominio> <email>` reenvía. Mientras
`MAIL_MAILER=log` el correo sale al log del worker (local y staging usan
Mailtrap por SMTP). La pantalla del enlace es `/invitation/:token` (pública, en
el contexto de curaduría) y el alta de curaduría del panel ahora **exige** el
primer administrador (`admin_name`/`admin_email`); el listado muestra ese
administrador (se lee de la base de cada curaduría, la central no guarda
empleados) y desde ahí el operador puede enviarle su enlace (invitación, o
recuperación si ya tiene contraseña; mismo token y misma pantalla
`/invitation/:token`) o fijarle la contraseña él mismo (decisión del dueño de
la plataforma; queda en el log quién lo hizo, la MFA del admin se mantiene). La
contraseña de nadie se puede ver (Argon2id). Falta el "olvidé mi contraseña"
de autoservicio. Los tests fuerzan
`MAIL_MAILER=array` y sin puerto en los enlaces, sin depender del `local.env`.
**Falta** (en este orden): configurar Brevo (SMTP) para correo real,
restablecer contraseña y reiniciar MFA de un empleado (lo hace su admin),
solicitantes.

**Panel de plataforma, primera rebanada vertical** (2026-10-18, en curso): se
puede probar desde el navegador. `GET/POST /api/platform/tenants`
(`TenantController`, `TenantPolicy`, `StoreTenantRequest`, `TenantResource`; el
alta es la misma Action `ProvisionTenant` que usa `tenants:provision`) y, en el
frontend, `/platform/login` y `/platform/tenants` (lista + formulario de alta)
en `resources/js/features/platform/`. Vitest + Vue Test Utils quedan como
herramienta de tests del frontend (`make test-front`, también en el CI). El
alta es síncrona (unos segundos); pasará a cola cuando el esquema crezca.

**Base de diseño de la interfaz** (2026-10-18, en curso): la app hereda la
identidad de la landing (paletas lavanda/turquesa/periwinkle/índigo, Inter
Tight) con tokens claro/oscuro, componentes propios sobre Reka UI
(`components/ui/`) y una estructura común (`AppShell`) para plataforma y
curadurías. Detalle en `docs/diseno-ui.md`.

**Siguiente paso** (orden acordado): terminar identidad/auth (ADR-005/006/017):
cuentas de empleado locales al tenant con RBAC,
solicitantes y enrolamiento; eso desbloquea el panel de plataforma y el
usuario administrador de cada tenant;
luego las migraciones del núcleo (las tablas reales de `database/migrations/
tenant/`, hoy vacía) y la máquina de estados del Expediente. Todo el código
nuevo lleva tests Pest.

**Principio de trabajo para lo que sigue**: al capturar conocimiento del
legado, no asumir que su diseño (VB6+Access, 20 años) es el patrón a
replicar — extraer el hecho de negocio y proponer proactivamente el patrón
moderno equivalente, cuestionando el legado por defecto.

## Convenciones de documentación

- Estrategia de pruebas (propuesta, a revisar): docs/estrategia-de-pruebas.md.
- Diseño de la interfaz (tokens, componentes, patrones): docs/diseno-ui.md.
- Comandos del entorno local: `make help` (Makefile en la raíz; detalle en
  infra/README.md).
- Decisiones: docs/adr/ADR-NNN-titulo.md (Contexto, Decisión, Consecuencias,
  Estado). Dominio: docs/dominio/. Pendientes: docs/preguntas-abiertas.md.
  Seguridad: docs/seguridad/. Estructura del repo: docs/estructura-repo.md.
  Onboarding y servicios externos: docs/dev-guide.md. Infra operativa:
  infra/README.md.
- Al tomar una decisión nueva: crear/actualizar el ADR y reflejarla aquí si es
  estructural. Idioma: español.
