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
