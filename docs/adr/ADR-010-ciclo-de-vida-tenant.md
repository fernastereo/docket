# ADR-010: Ciclo de vida del tenant

**Estado**: Aceptada (detalle contractual pendiente)
**Fecha**: 2026-07-21
**Amendment 2026-10-09**: estados del proceso de alta (`provisioning`,
`failed`) y política de compensación de fallos (ver sección Amendments al final).

## Decisión

Estados del tenant:

- **Demo/Prueba**: provisionado igual que uno real, con datos ficticios.
- **Activo**: operación normal.
- **Suspendido** (ej. no pago): **modo solo-lectura** — empleados consultan
  pero no radican ni expiden; los ciudadanos conservan consulta de sus
  expedientes. Nunca bloqueo total: hay expedientes con plazos legales
  corriendo y derecho de los ciudadanos a consultar su trámite.
- **Terminado** (offboarding): entrega a la curaduría de
  1) dump completo de su base PostgreSQL,
  2) todos sus archivos,
  3) snapshot de datos básicos de las identidades referenciadas en su audit.
  Periodo de retención acordado por contrato antes del borrado definitivo.

**Provisioning** (alta): un solo comando — crear BD + migrar + seed (tipos de
licencia, plantillas, tarifas) + configurar subdominio y disk de archivos.

## Pendiente

- Detalle contractual: SLA, plazos de suspensión, retención post-terminación.

## Amendments

### 2026-10-09 — Implementación del alta (`ProvisionTenant`)

Implementado en `App\Platform\Actions\ProvisionTenant` (única vía de
escritura, ADR-011), con el comando `tenants:provision {nombre} {subdominio}
[--active]` como primera puerta de entrada. El panel de plataforma invocará la
misma Action con un DTO (`ProvisionTenantData`), sin reescribirla.

**Estados** (`App\Platform\Enums\TenantStatus`): a los cuatro de arriba se
suman dos del proceso de alta, no del negocio:

- `provisioning`: transitorio mientras se crea la base y se migra.
- `failed`: el alta no terminó. El tenant se conserva solo para diagnóstico.

`isAccessible()` (demo, activo y suspendido) e `isWritable()` (demo y activo)
codifican la regla de ADR-010: suspendido es **solo-lectura**, nunca bloqueo
total. Un tenant nace únicamente como `demo` o `active`.

**Flujo del alta**: validar → crear tenant (`provisioning`) → crear dominio
(`<subdominio>.<PLATFORM_TENANT_BASE_DOMAIN>`) → crear base `tenant<uuid>` →
`tenants:migrate` del tenant → `demo`/`active` + `provisioned_at`.

**Compensación de fallos**: `CREATE DATABASE` no corre dentro de una
transacción de PostgreSQL, así que un rollback no cubre la base física. Ante
cualquier fallo la Action borra la base si existe, borra el dominio (el
subdominio queda libre), marca el tenant `failed` con `failure_reason` y lanza
`TenantProvisioningFailed`. **Reintentar es un alta nueva** (otro UUID, otra
base); nunca se reutiliza un tenant fallido. Borrar después el registro del
tenant fallido es seguro (`DeleteTenantDatabase` tolera la base ya borrada).

**Notas de implementación**: el pipeline automático `TenantCreated` de stancl
se desactivó a propósito (un `Tenant::create()` suelto no crea base); y
`MigrateDatabase` de stancl ignora el código de salida de `tenants:migrate`,
por eso la Action lo verifica ella misma.

**Sigue pendiente**: seed base del tenant, credencial de BD por tenant
(ADR-017), prefijo de archivos (ADR-007), suspensión/terminación/offboarding,
reactivación, y alta en cola (hoy síncrona). Ver `docs/preguntas-abiertas.md`.
