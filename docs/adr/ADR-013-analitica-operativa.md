# ADR-013: Analítica operativa (BI interno por curaduría)

**Estado**: Borrador
**Fecha**: 2026-08-31

## Contexto

El audit log del expediente (`docs/dominio/expediente.md`, requisito de
línea de tiempo estilo Jira/ClickUp, derivado de eventos de dominio por
ADR-011) ya captura todo movimiento del expediente: cambios de estado,
reasignaciones de equipo, generación de documentos, con quién y cuándo. Eso
es la materia prima para dar visibilidad operativa a la curaduría —
visibilidad que el legado (VB6+Access) no ofrece en absoluto. Identificado
como diferenciador prioritario por el usuario (2026-08-31).

## Decisión

Construir un módulo de **analítica operativa por tenant**, alimentado del
audit log y del catálogo de plazos legales
(`docs/dominio/flujo-tramite.md`), con al menos:

- **Cuellos de botella**: tiempo promedio (y outliers) que los expedientes
  pasan en cada estado interno, desglosado por área/encargado.
- **Cumplimiento de plazos**: expedientes activos comparados contra sus
  plazos legales (término general, suspensión por observaciones, suspensión
  por pago) — cuántos están dentro de margen, cuántos en riesgo.
- **Carga de trabajo por encargado**: cuántos expedientes activos tiene
  asignados cada arquitecto/ingeniero/abogado (`Expediente ↔ Empleado` con
  vigencia).
- **Desenlaces**: proporción de aprobados / negados / desistidos (y por qué
  motivo de desistimiento, `docs/dominio/flujo-tramite.md`) en un periodo.

Acceso restringido a roles administrativos/curador (RBAC del tenant,
pendiente en `docs/preguntas-abiertas.md`).

## Consecuencias

- El audit log crudo (evento por evento) no es eficiente para agregaciones
  de este tipo a medida que crece el volumen — se necesita una capa de
  **lectura agregada** (vistas materializadas o tablas de reporte
  refrescadas periódicamente), coherente con el principio de ADR-011 de
  "query classes dedicadas" para consultas genuinamente complejas, no
  repositorio genérico.
- Depende de que el cómputo de plazos/días hábiles ya esté resuelto
  (ADR-014) — sin eso no se puede calcular "cumplimiento de plazos" de forma
  confiable.
- Argumento de venta directo: un curador hoy no puede ver esto de su propia
  oficina sin pedirlo manualmente.

## Pendiente

- Elegir el mecanismo técnico de agregación (vistas materializadas de
  Postgres, tablas de resumen actualizadas por job, u otro) — decisión de
  implementación.
- Definir el catálogo completo de métricas más allá de las 4 listadas
  arriba (a medida que se use el sistema real saldrán más).
- **Campos personalizados en la analítica** (ADR-016): las definiciones
  marcadas `reportable` se proyectan a columnas tipadas dentro de esa misma
  capa de lectura agregada (vistas materializadas / tablas de resumen), para
  que una curaduría pueda reportar/segmentar por sus variables propias sin
  acoplar el esquema transaccional. Nunca vía columnas reales por tenant.
- Si esta analítica se ofrece también agregada a nivel de toda la
  plataforma (benchmarks entre curadurías, de forma anónima) — decisión de
  producto/negocio, no solo técnica; requiere cuidado por el principio de
  ADR-005 de que la central nunca revela información cruzada entre tenants.
