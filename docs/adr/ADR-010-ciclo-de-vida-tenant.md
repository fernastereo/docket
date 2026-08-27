# ADR-010: Ciclo de vida del tenant

**Estado**: Aceptada (detalle contractual pendiente)
**Fecha**: 2026-07-21

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
