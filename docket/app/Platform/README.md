# app/Platform

Base central: catálogo de tenants, provisioning, ciclo de vida del tenant
(ADR-002, ADR-010). **No es dominio de negocio de una curaduría** — es
plomería de plataforma, vive aparte de `app/Domain/` a propósito.

## Qué hay

| Pieza | Para qué |
|---|---|
| `Models/Tenant` | Catálogo de curadurías. **Declara sus columnas en `getCustomColumns()`**: stancl manda al JSON `data` cualquier atributo no declarado, aunque la columna exista; al agregar una columna a `tenants` hay que sumarla ahí también. |
| `Enums/TenantStatus` | `provisioning`, `failed`, `demo`, `active`, `suspended`, `terminated`. `isAccessible()` / `isWritable()` codifican "suspendido = solo-lectura". |
| `Data/ProvisionTenantData` | DTO de entrada del alta (nombre, subdominio, estado inicial). Solo transporta datos. |
| `Actions/ProvisionTenant` | **Única vía** para crear tenants: valida, crea tenant + dominio + base + migraciones, y compensa si falla. |
| `Rules/TenantSubdomain` | Validación del subdominio (formato DNS, reservados, ocupado). La reutilizará el Form Request del panel. |
| `Jobs/DeleteTenantDatabase` | Borrado de base tolerante a que ya no exista (tenants en `failed`). |
| `Exceptions/TenantProvisioningFailed` | El alta falló y quedó compensada. |

Puerta de entrada actual: `php artisan tenants:provision "<nombre>" <subdominio> [--active]`
(`app/Console/Commands/ProvisionTenantCommand.php`).

## Reglas a no romper

- **Un `Tenant::create()` suelto NO crea base.** El pipeline automático de
  stancl (`TenantCreated`) está desactivado en `TenancyServiceProvider`;
  orquesta todo `ProvisionTenant`. Es a propósito: orden y compensación de
  fallos explícitos.
- **`CREATE DATABASE` no corre dentro de una transacción**: un rollback no
  cubre la base física. Por eso la compensación es manual, y por eso los tests
  que crean bases usan `DatabaseMigrations`, no `RefreshDatabase`.
- **Reintentar un alta fallida es un alta nueva** (otro UUID); un tenant en
  `failed` nunca se reutiliza.
- Host de cada curaduría: `<subdominio>.<PLATFORM_TENANT_BASE_DOMAIN>`
  (`config/platform.php`, distinto por ambiente).

Pendiente de este contexto: `docs/preguntas-abiertas.md` → sección
"Plataforma / provisioning".
