# ADR-001: Modelo de negocio SaaS por suscripción

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

El sistema legado (VB6 + Access) se distribuye como software instalado que cada
curaduría opera por su cuenta. Existen 5 curadurías clientes activas. Se evalúa
el modelo de distribución del nuevo sistema web.

## Decisión

El nuevo sistema se venderá como SaaS: las curadurías pagan suscripción y el
proveedor opera la plataforma (hosting, despliegues, backups, soporte).

## Consecuencias

- Mantenimiento y despliegue centralizados: una sola versión en producción.
- Modelo de ingresos recurrente por suscripción.
- El proveedor asume responsabilidad de disponibilidad, seguridad y backups
  (definir SLA en contratos).
- Las curadurías deben poder exigir la entrega de su base de datos completa al
  terminar el contrato (los expedientes son de la curaduría). El modelo
  database-per-tenant (ADR-002) hace esto trivial.
- Requiere infraestructura de hosting propia o cloud (por definir en ADR de
  infraestructura).
