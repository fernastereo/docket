# ADR-003: Stack tecnológico

**Estado**: Aceptada
**Fecha**: 2026-07-21
**Amendment 2026-09-11**: modelo de servido del frontend — SPA mismo origen
que la API, no una app aparte. Ver sección Amendments al final. Implementado
en el esqueleto (`docket/`).

## Contexto

Se requiere un stack productivo para una plataforma SaaS multi-tenant
(database-per-tenant, ADR-002) con manejo documental pesado, audit log,
portal ciudadano y componentes de IA.

## Decisión

- **Backend**: PHP / **Laravel**, expuesto como API.
- **Frontend**: **Vue.js** (SPA) consumiendo la API. Autenticación con
  Laravel Sanctum.
- **Base de datos**: **PostgreSQL** (+ pgvector para embeddings de IA).
- **Multi-tenancy**: paquete **stancl/tenancy** (Tenancy for Laravel) —
  soporta database-per-tenant nativo: identificación por subdominio, creación
  automática de BD por tenant, `tenants:migrate`, contexto de tenant en jobs
  encolados y en el filesystem.
- **Colas**: Laravel Queues con Redis.
- **Contenedores**: Docker (desarrollo y producción).
- **Almacenamiento de archivos**: S3-compatible vía Flysystem (ver ADR-006).
- **Mensajería (email/SMS/WhatsApp)**: Brevo (ver ADR-008).

## Consecuencias

- stancl/tenancy resuelve gran parte de la plomería multi-tenant (conexiones,
  migraciones por tenant, jobs tenant-aware, discos por tenant).
- Vue SPA implica diseñar la API desde el inicio (versionado, recursos,
  autorización por rol en el backend).
- pgvector permite RAG sin infraestructura vectorial adicional.

## Amendments

### 2026-09-11 — Frontend: SPA de mismo origen, no una app aparte

**Motivo**: al armar el esqueleto había que decidir si la SPA de Vue vive en
un repo/dominio aparte que solo consume la API, o dentro del propio repo de
Laravel, servida al mismo origen.

**Decisión**: Vue vive en `docket/resources/js/` (SPA real — vue-router +
Pinia, ruteo en el cliente — no Blade con componentes sueltos), compilada con
Vite y **servida al mismo origen** que la API.

- **Sanctum en modo SPA**: cookie de sesión `httpOnly + Secure + SameSite` +
  CSRF, habilitado con `$middleware->statefulApi()` en `bootstrap/app.php`.
  Nada de token en `localStorage`. `SANCTUM_STATEFUL_DOMAINS` admite wildcard
  (`*.staging.curaduria.app`, etc.) para cubrir todos los subdominios de
  tenant — ver `infra/env/*.env.example`.
- **Por qué no una SPA aparte**: con multi-tenancy por subdominio (ADR-002),
  una SPA en `app.curaduria.app` consumiendo `api.curaduria.app` obliga a
  compartir la cookie de sesión entre subdominios (`domain=.curaduria.app`),
  lo que la hace visible en *todos* los subdominios de tenant a la vez —
  contradice el aislamiento por tenant. Sirviendo la SPA desde el mismo
  subdominio que la API, la cookie queda scopeada exactamente a ese tenant,
  sin configuración adicional. También evita CORS y un segundo pipeline de
  build/deploy.
- **Sin ruta `login` server-rendered**: esta arquitectura no tiene login
  renderizado por el servidor — el guest siempre recibe 401 JSON
  (`$middleware->redirectGuestsTo(fn () => null)` en `bootstrap/app.php`), en
  vez del intento de redirect a una ruta `login` que no existe.
- **Ruteo central vs. tenant**: `routes/web.php` sirve el shell de la SPA para
  los dominios centrales (`Route::domain()` explícito, uno por cada valor de
  `TENANCY_CENTRAL_DOMAINS`); `routes/tenant.php` lo mismo para cualquier
  subdominio de tenant, detrás de `InitializeTenancyByDomain`. Los dos
  catch-all (`{any?}`) excluyen explícitamente los prefijos reservados
  (`api/`, `sanctum/`, `storage/`, `tenancy/`, `up`) con un negative lookahead
  en el `where()` — sin eso, el catch-all se traga también las rutas de API
  (el router de Laravel no prioriza por especificidad, solo por orden de
  registro).
- **Assets compilados no son datos de tenant**: `config/tenancy.php` tiene
  `'asset_helper_tenancy' => false` — el JS/CSS de Vite es código de la app,
  igual para todas las curadurías; los documentos propios de cada una van a
  S3/Spaces con prefijo por tenant (ADR-007), no por el mecanismo de disco
  local suffixed de stancl/tenancy.
- **API sigue siendo API real y tipada** (ADR-011): la SPA la consume por
  cookie; terceros (widget, marketplace — `docs/vision-producto.md`) la
  consumirán por token, sobre las mismas rutas.

Descartado: **Inertia.js** (el "monolito Laravel + Vue" idiomático sin API
propia) — la visión de producto exige una API real para el widget embebible,
el marketplace y la verificación pública; con Inertia habría que construir
esa API aparte de las pantallas.
