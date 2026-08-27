# ADR-008: Mensajería y notificaciones (Brevo)

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

El enrolamiento diferido y el seguimiento de trámites requieren email
transaccional y, para el público colombiano, SMS y WhatsApp.

## Decisión

- Proveedor único: **Brevo** (email transaccional, SMS y WhatsApp).
- Integración vía API (canales de notificación de Laravel) y/o SMTP para email.
- Todo envío se despacha por cola (job tenant-aware) y queda registrado en el
  audit del tenant (qué se notificó, a quién, por qué canal, resultado).

## Consecuencias / obligaciones

- **WhatsApp**: requiere verificación del negocio ante Meta y **plantillas de
  mensaje pre-aprobadas** para mensajes proactivos (ej. cambio de estado del
  trámite). Tramitar con anticipación — toma días o semanas.
- **Email**: configurar SPF/DKIM del dominio de la plataforma para
  entregabilidad.
- Definir en implementación: preferencias de canal por usuario, y catálogo de
  eventos que notifican (radicación exitosa, observaciones, expedición, etc.).
