# ADR-009: Infraestructura (DigitalOcean, Docker, PostgreSQL administrado)

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

Hosting para el SaaS con database-per-tenant. Se evaluó: (a) un contenedor
PostgreSQL por base de tenant, (b) droplet independiente por tenant,
(c) una instancia PostgreSQL con N bases, autogestionada o administrada.

## Decisión

- **Proveedor**: DigitalOcean.
- **Base de datos**: **DO Managed PostgreSQL** — un cluster administrado que
  contiene las N bases de tenant + la base central. Backups automáticos,
  point-in-time recovery, parcheo y failover a cargo del proveedor.
- **Aplicación**: un droplet con Docker Compose:
  - contenedor app (Laravel / PHP-FPM + Nginx)
  - contenedor workers de colas
  - contenedor Redis (colas, caché, sesiones)
- **Archivos**: DO Spaces (ADR-007).
- **DNS**: wildcard `*.plataforma.com` + certificado TLS wildcard para los
  subdominios de tenant.
- **Backups por tenant** (feature vendible): `pg_dump` individual por base,
  programado, hacia Spaces — por encima de los backups del cluster administrado.

## Alternativas descartadas y por qué

- **Contenedor PostgreSQL por tenant**: el aislamiento exigido es de *datos*,
  y lo cumple una instancia con N bases (las bases no comparten datos y los
  usuarios de conexión se restringen por base). Contenedor-por-tenant solo
  aportaría aislamiento de recursos/versiones, a costa de memoria multiplicada
  (shared_buffers por instancia), N procesos que monitorear/parchear, y
  backups/actualizaciones coordinados por contenedor. Sobre-ingeniería a esta
  escala.
- **Droplet por tenant**: multiplica costo fijo y administración sin necesidad.
  Se reserva como palanca futura para tenants enormes o que exijan (y paguen)
  infraestructura dedicada — el modelo database-per-tenant permite mover una
  base a su propia instancia en cualquier momento sin rediseño.

## Escalamiento previsto

- App stateless (sesiones en Redis, archivos en Spaces) → segundo droplet de
  app tras un Load Balancer de DO cuando el tráfico lo pida.
- PgBouncer si el número de tenants/conexiones crece.
- Staging replica la estructura multi-tenant completa (para ensayar
  migraciones multi-base). Tenant "Curaduría Demo" con datos ficticios para
  desarrollo, pruebas y ventas.
