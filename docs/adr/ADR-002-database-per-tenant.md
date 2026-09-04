# ADR-002: Multi-tenancy con base de datos por curaduría (database-per-tenant)

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

La plataforma servirá a múltiples curadurías. Las curadurías son extremadamente
celosas de su información: manejan expedientes con valor legal y actos
administrativos, y no aceptan que sus datos convivan con los de otra curaduría.
Alternativas evaluadas:

1. **Shared database, shared schema** (columna tenant_id): descartada — aislamiento
   solo lógico, inaceptable comercial y legalmente para este dominio.
2. **Shared cluster, schema por tenant** (schemas de PostgreSQL): aislamiento lógico
   fuerte, administración simple, pero los datos siguen en la misma base física.
3. **Database-per-tenant**: base de datos física independiente por curaduría.

## Decisión

Se adopta **database-per-tenant**:

- Una sola aplicación desplegada (código único, versión única).
- Una **base de datos central** que contiene únicamente el catálogo de tenants:
  curadurías registradas, configuración (municipio, POT aplicable, tarifas de
  expensas, branding), mapeo dominio→tenant e identidades de acceso de
  solicitantes y de plataforma (ADR-005). **Ningún dato de negocio**
  (expedientes, licencias, documentos) reside aquí.
- Una **base PostgreSQL por curaduría** con el esquema completo del negocio,
  idéntico entre tenants. La personalización por curaduría (campos
  personalizados, ADR-016; catálogos configurables) vive en **datos** —filas de
  catálogo tenant-local y valores JSONB—, **nunca** en variaciones de esquema:
  el DDL sigue siendo una sola secuencia de migraciones para todos los tenants.
- Resolución del tenant por **subdominio** (ej. barranquilla1.curaduria.app),
  resuelta temprano en el ciclo del request (middleware de stancl/tenancy,
  ADR-003) que conmuta la conexión de base de datos del tenant antes de
  ejecutar lógica de negocio.

## Consecuencias

Positivas:
- Aislamiento físico total: argumento comercial contundente.
- Backup/restore individual por curaduría (feature vendible).
- Entrega de la base completa a la curaduría al fin del contrato es trivial.
- Un tenant grande puede moverse a otro servidor sin afectar a los demás.
- Migración desde el legado es 1:1 (cada curaduría ya tiene su propio .mdb).
- El RAG por tenant (pgvector en su propia base) mantiene incluso los embeddings
  de documentos aislados.

Costos / obligaciones:
- **Migraciones de esquema**: `tenants:migrate` (stancl/tenancy) itera el
  catálogo de tenants y aplica las migraciones contra cada base, con manejo de
  fallos parciales. Debe existir en el pipeline desde el inicio.
- **Provisioning**: alta de curaduría = un comando (crear BD + migrar + seed +
  subdominio + disco de archivos).
- **Conexiones**: con decenas de tenants, introducir PgBouncer.
- **Plomería multi-tenant**: la resuelve stancl/tenancy (ADR-003) —
  conmutación de conexión por tenant, jobs tenant-aware y disco de archivos por
  tenant (ADR-007). No se implementa a mano.
