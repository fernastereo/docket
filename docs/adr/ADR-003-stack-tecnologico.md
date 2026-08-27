# ADR-003: Stack tecnológico

**Estado**: Aceptada
**Fecha**: 2026-07-21

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
