# docket/ — Laravel app

Este archivo es específico de `docket/`. Las decisiones del proyecto viven en
el `CLAUDE.md` de la raíz del repo y en `docs/adr/` — leerlos primero.

Convenciones de código: **ADR-011** (acciones de dominio, controladores
delgados, máquina de estados, enums, sin repositorio genérico, interfaces
solo en `app/Support/`, Pint + Larastan + Pest). Estructura de carpetas:
`docs/estructura-repo.md`. Multi-tenancy: `stancl/tenancy`
(`config/tenancy.php`), migraciones central en `database/migrations/`,
migraciones de tenant en `database/migrations/tenant/`.

Laravel scaffoldeó por defecto un flujo de bootstrap para instalar
**Laravel Boost** (MCP con herramientas de introspección para agentes). No se
adoptó todavía — es una decisión pendiente, no una que se tomó implícitamente
al generar el esqueleto. Si se decide instalarlo: `composer require
laravel/boost --dev && php artisan boost:install` (esto reemplaza este
archivo con guías generadas; conservar la referencia a `docs/adr/ADR-011` en
lo que genere).

## Tests

Pest **siempre contra PostgreSQL real**, base exclusiva `docket_test` (ADR-011,
amendment 2026-10-12). Se corren dentro del contenedor `app` (ver
`infra/README.md`). `phpunit.xml` fuerza la base de pruebas en `<env>` **y** en
`<server>`, y `tests/TestCase.php` aborta si la base no termina en `_test`:
no quitar ninguna de las dos defensas — sin ellas la suite hace `migrate:fresh`
sobre `docket_central`. Los tests que crean bases físicas usan
`DatabaseMigrations` (no `RefreshDatabase`).

**Tests de dominio** (expedientes, expensas…) sobre la base de un tenant: en el
archivo, `uses(Tests\Concerns\UsesTenantDatabase::class);`. Corren dentro de un
tenant de pruebas compartido y en una transacción que se revierte por test;
`$this->tenant` es ese tenant. No escribir en la central desde estos tests. Las
bases físicas de tests se llaman `tenanttest_<uuid>`; si una corrida se
interrumpe, `make test-clean`. Para probar provisioning o aislamiento NO se usa
el trait: ver `tests/Feature/Tenancy/DatabaseIsolationTest.php`.
