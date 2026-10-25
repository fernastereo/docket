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
| `Models/PlatformUser` | Soporte / superadmin del proveedor. Tabla `platform_users`, guard `platform` (ADR-005, amendment 2026-10-18). No es empleado de curaduría ni solicitante. |
| `Actions/CreatePlatformUser` | **Única vía** para crear usuarios de plataforma (política de contraseña: 12+, HIBP). La usa `php artisan platform:create-user`. |
| `Actions/AuthenticatePlatformUser` | Login: bloqueo tras 5 fallos en 15 min, mismo error para email inexistente, clave errónea y cuenta deshabilitada. |
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

## Usuarios de plataforma

- Rutas: `POST /api/platform/login`, `POST /api/platform/logout`,
  `GET /api/platform/me`, **solo en los dominios centrales** (`routes/api.php`,
  `Route::domain()`); en un subdominio de tenant no existen.
- Sesión por cookie de Sanctum SPA. Un login sin `Origin`/`Referer` de un
  dominio stateful responde 400 en vez de ignorarse. Límites de sesión en
  `EnforcePlatformSessionLimits` (15 min de inactividad, 8 h absolutas; ver
  `config/platform.php`).
- Las cookies son por host (`SESSION_DOMAIN` vacío): la sesión de plataforma no
  llega a los subdominios de las curadurías. No fijar `SESSION_DOMAIN` a un
  dominio padre.
- Curadurías para el panel: `GET /api/platform/tenants` (lista + `meta.base_domain`)
  y `POST /api/platform/tenants` (`name`, `subdomain`, `active`): llama a la
  misma Action `ProvisionTenant` que el comando. Autoriza `TenantPolicy`; un
  fallo del alta responde 500 con mensaje genérico (el detalle queda en
  `failure_reason` y en el log, no en la respuesta).
- Alta: `php artisan platform:create-user "<nombre>" <email>` (la contraseña se
  pide en pantalla, no como argumento).
- MFA (TOTP, obligatoria): `POST /api/platform/mfa/setup` (QR + clave),
  `/mfa/confirm` (activa y entrega 8 recovery codes, una sola vez) y
  `/mfa/verify` (segundo paso del login). Lógica en `app/Platform/Mfa/
  PlatformMfa` y las Actions `BeginPlatformMfaSetup`, `ConfirmPlatformMfa`,
  `VerifyPlatformMfa`. Las rutas del panel van detrás de `RequirePlatformMfa`
  (una ruta nueva va dentro de ese grupo en `routes/api.php`). Si alguien pierde
  teléfono y códigos: `php artisan platform:reset-mfa <email>`.
- Pendiente: sesiones en Redis con revocación, backoff progresivo antes del
  bloqueo, regenerar recovery codes desde la UI.
