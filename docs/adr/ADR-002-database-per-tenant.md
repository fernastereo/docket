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
  expensas, branding), mapeo dominio→tenant y autenticación global si aplica.
  **Ningún dato de negocio** (expedientes, licencias, documentos) reside aquí.
- Una **base PostgreSQL por curaduría** con el esquema completo del negocio,
  idéntico entre tenants.
- Resolución del tenant por **subdominio** (ej. curaduria1bquilla.plataforma.com),
  resuelta en un listener temprano del request que configura la conexión Doctrine
  antes de ejecutar lógica de negocio.

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
- **Migraciones de esquema**: comando propio que itera el catálogo de tenants y
  ejecuta doctrine:migrations contra cada base, con manejo de fallos parciales.
  Debe existir en el pipeline desde el inicio.
- **Provisioning**: alta de curaduría = un comando (crear BD + migrar + seed +
  subdominio).
- **Conexiones**: con decenas de tenants, introducir PgBouncer.
- En Symfony: múltiples conexiones/entity managers o bundle de multi-tenancy
  (evaluar hakam/multi-tenancy-bundle vs implementación propia con
  ConnectionWrapper). → pendiente en preguntas abiertas.
