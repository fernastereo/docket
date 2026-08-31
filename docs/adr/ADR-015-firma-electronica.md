# ADR-015: Firma electrónica de actos administrativos

**Estado**: Borrador (patrón de integración definido; proveedor por elegir)
**Fecha**: 2026-08-31

## Contexto

El paso "Expedida" de `docs/dominio/flujo-tramite.md` requiere la firma del
curador sobre la versión definitiva de la Resolución
(`docs/dominio/acto-administrativo.md`) para que el acto quede legalmente
expedido. Ya identificado como pregunta abierta en
`docs/preguntas-abiertas.md` ("¿firma electrónica/digital? ¿proveedor
colombiano — Certicámara, GSE?"). El usuario lo marcó como diferenciador a
resolver: el curador debe poder firmar desde cualquier lugar, con
trazabilidad legal completa.

## Decisión

Firma electrónica/digital vía **proveedor colombiano externo**, integrado
como **frontera del sistema con interfaz + implementación real + fake de
test** — patrón ya decidido en ADR-011 para este caso exacto ("firma
digital" ya estaba listada ahí como ejemplo de frontera con interfaz).

- Interfaz `FirmaElectronica` (nombre de dominio en inglés a definir en el
  código, ej. `DocumentSigner`), con una única implementación real
  intercambiable según el proveedor elegido.
- Se activa sobre el documento ya **congelado** (mecanismo de
  `docs/dominio/documento.md`) — nunca se firma un borrador editable.
- El resultado de la firma (certificado, timestamp, hash firmado, evidencia
  del proveedor) se guarda asociado al Acto Administrativo, y dispara el
  evento de dominio que lo marca **Expedida** (ADR-011: eventos → audit
  log) — la firma es la transición, no un paso aparte sin trazabilidad.
- Compatible con el código de verificación pública de ADR-012: el hash
  firmado puede ser el mismo que se valida en la página pública, o
  derivarse de él.
- El curador puede firmar desde cualquier dispositivo con acceso al
  proveedor (sin depender de estar físicamente en la curaduría) —
  requisito explícito del usuario.

## Consecuencias

- Costo recurrente por transacción de firma (según proveedor) — a
  presupuestar por trámite, similar a los costos de LLM (ADR-004) y de
  mensajería (ADR-008).
- Al vivir detrás de una interfaz, cambiar de proveedor en el futuro no
  afecta el resto del sistema (mismo principio que Brevo en ADR-008).
- Refuerza la trazabilidad legal exigida en los principios del proyecto
  (`CLAUDE.md`, principio 2): el acto administrativo queda firmado con
  evidencia verificable, no solo "marcado como expedido" en la base de
  datos.

## Pendiente

- Elegir proveedor concreto (Certicámara, GSE, u otro) — evaluación de
  costo, API, y soporte de firma remota/móvil.
- Definir si se requiere firma electrónica simple o firma digital
  certificada (peso legal distinto en Colombia) para actos administrativos
  de curaduría — validar con criterio legal.
- Flujo de contingencia si el proveedor de firma no está disponible en el
  momento en que el curador necesita firmar (no debe bloquear
  indefinidamente un acto ya listo).
