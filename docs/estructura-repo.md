# Estructura del repositorio

Monorepo. Nombre clave interno: **Docket** · producto: **CuraduriAPP**.
CI con filtros de ruta: un cambio en `landing/**` no dispara el CI de la
plataforma y viceversa.

```
curaduriapp/
├── docs/                     decisiones (adr/), dominio (dominio/), seguridad (seguridad/),
│                             runbooks (runbooks/), preguntas-abiertas.md, vision-producto.md
├── landing/                  landing en producción (curaduria.app) — no se toca acá
├── docket/                   la plataforma: Laravel (API) + SPA Vue 3/TS (ver abajo)
├── infra/                    Docker, Caddy, compose, scripts, tailscale/acl.json (ver infra/README.md)
├── .github/workflows/        CI y deploy manual (filtros de ruta)
├── CLAUDE.md
└── README.md
```

## `docket/` — Laravel + Vue

Frontend **dentro** del repo de Laravel, mismo origen que la API (Sanctum SPA
con cookie). No es Blade con componentes: es una SPA con ruteo en el cliente,
servida por Laravel. Ver amendment de ADR-003.

```
docket/
├── app/
│   ├── Domain/               capa de dominio POR CONTEXTO (ADR-011)
│   │   ├── Identity/         identidad central + membresías (ADR-005)
│   │   ├── Expediente/       Actions/ States/ Events/ Enums/ DataTransferObjects/ Queries/ Models/
│   │   ├── Predio/  Solicitante/  ActoAdministrativo/
│   │   ├── Documento/        Documento + PlantillaDocumento + fusión
│   │   ├── CustomFields/     ADR-016
│   │   └── Plazos/           TermCalculator / PlazoLegal (ADR-014)
│   ├── Platform/             base central: catálogo de tenants, provisioning,
│   │                         ciclo de vida (ADR-002, ADR-010) — NO es dominio de curaduría.
│   │                         `Models/Tenant.php` (extiende el Tenant base de
│   │                         stancl/tenancy con HasDatabase + HasDomains)
│   ├── Http/                 Controllers/ (delgados) · Requests/ · Resources/ · Middleware/
│   │                         (incl. SecurityHeaders — ADR-017 capa 3)
│   ├── Policies/             RBAC por tenant
│   ├── Support/              fronteras con interfaz + impl + fake: LlmClient, DocumentSigner,
│   │                         SnrGateway, Messaging, Pdf (ADR-011)
│   └── Console/
├── routes/
│   ├── api.php               lo que consume la SPA (y terceros, con token, a futuro)
│   ├── web.php               sirve el shell de la SPA en los dominios CENTRALES
│   ├── tenant.php            sirve el shell de la SPA por subdominio de tenant +
│   │                         verificación pública /verificar/{codigo} (ADR-012, pendiente)
│   └── console.php
├── database/
│   ├── migrations/           base CENTRAL (catálogo de tenants, identidades)
│   ├── migrations/tenant/    base de cada CURADURÍA (todo el negocio) ← tenants:migrate
│   ├── factories/  seeders/
├── resources/js/             SPA: router/ stores/ api/ components/ features/ layouts/
├── tests/                    Pest
│   └── Feature/{Tenancy,Expediente,Expensas,Radicacion}/   obligatorios (ADR-011)
└── config/  bootstrap/  public/
```

## Convenciones

- **Dominio por contexto, no por tipo técnico** — `Domain/Expediente/Actions/…`,
  no `Actions/Expediente/…`. Un contexto se crea cuando hay algo real que poner
  (YAGNI, ADR-011).
- **`Platform/` ≠ `Domain/`** — la plomería multi-tenant no es negocio de una
  curaduría.
- **Interfaces solo en `Support/`** (fronteras). El resto, concreción directa.
- **Dos juegos de migraciones** — `migrations/` central vs `migrations/tenant/`.
  `tenants:migrate` corre solo las de `tenant/`.
- **SPA mismo origen** — `web.php`/`tenant.php` sirven el shell; `api.php` es
  la API tipada. Los catch-all (`{any?}`) excluyen `api/`, `sanctum/`,
  `storage/`, `tenancy/`, `up` con un negative lookahead en el `where()` —
  sin eso, el catch-all central se traga también `/api/*` (el router de
  Laravel prioriza por orden de registro, no por especificidad). Detalle:
  amendment 2026-09-11 de ADR-003.
- **`asset_helper_tenancy = false`** en `config/tenancy.php` — los assets de
  Vite son código compartido, no datos de un tenant; no pasan por el
  mecanismo de disco local por-tenant de stancl/tenancy.
- **Infra fuera de `docket/`** — `infra/`; workflows en `.github/`. Cero
  secretos en el repo.
- **Si se parte en servicios** (worker aparte, API del widget) → `apps/docket/`,
  `apps/worker/`. No ahora.
