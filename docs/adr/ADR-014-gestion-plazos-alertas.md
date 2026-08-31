# ADR-014: Gestión de plazos legales y alertas predictivas

**Estado**: Borrador (diseño técnico por validar en implementación) —
**marcado por el usuario como imprescindible**, no opcional.
**Fecha**: 2026-08-31

## Contexto

`docs/dominio/flujo-tramite.md` define varios plazos legales en días
hábiles (30 para completar radicación, 45 término general, 30+15 para
subsanar observaciones, 30 para pagar expensas/impuestos, 5 para expedir
tras el pago), con reglas de suspensión/reanudación y consecuencias
distintas por vencimiento. Ese documento ya deja pendiente "la mecánica
exacta de cómputo de días hábiles" — esta ADR la resuelve. El usuario
identificó las alertas **antes** de vencer un plazo (no solo detectar
después) como diferenciador **imprescindible**: reduce desistimientos
evitables y presión de última hora sobre el equipo — algo que el legado no
ofrece.

## Decisión

### Servicio central de plazos (`PlazoLegal` / `TermCalculator`)

Un servicio de dominio único, reutilizado por **todos** los plazos del
flujo (no lógica repetida por cada tipo de plazo):

- Calcula fecha límite a partir de fecha de inicio + N días hábiles, contra
  un **calendario de días hábiles de Colombia** (fines de semana + festivos
  oficiales). Los festivos se mantienen como **catálogo semilla**,
  actualizado anualmente (mismo espíritu que el catálogo de ORIPs en
  `docs/dominio/predio.md` — dato de referencia, no texto libre).
- Soporta **suspensión y reanudación**: al suspender, congela los días
  hábiles ya transcurridos; al reanudar, continúa el conteo desde ahí (no
  reinicia) — mecánica ya exigida por `docs/dominio/flujo-tramite.md` para
  "Suspendido por observaciones" y "Suspendido por pago".
- Soporta **prórroga** (solo donde el flujo lo permite: observaciones +15
  días hábiles; ninguna prórroga en incompleto ni en pago).
- Expone, para cualquier plazo activo de un expediente: fecha límite
  vigente, días hábiles restantes, si está suspendido y desde cuándo.

### Alertas predictivas

Job periódico (Laravel Queue, tenant-aware — ADR-003) que evalúa los
expedientes con plazo activo y dispara notificación (in-app + canal por
preferencia del usuario, Brevo — ADR-008) en umbrales **antes** del
vencimiento, no solo al vencer. Umbrales propuestos (ajustables):

- Aviso temprano a la mitad del plazo transcurrido.
- Aviso urgente cuando quedan pocos días hábiles (valor por definir en
  implementación, ej. 5 o 20% del plazo total, el que sea mayor).
- Aviso final si se vence (para que quede claro que ya se disparó
  desistimiento/consecuencia, no como sorpresa).

Las alertas van al **encargado del expediente** con ese rol vigente en ese
momento (`Expediente ↔ Empleado`, `docs/dominio/expediente.md`), y quedan
también reflejadas en la línea de tiempo de actividad del expediente.

### Ajuste manual (ya decidido en `docs/dominio/flujo-tramite.md`)

El sistema **no bloquea automáticamente** ni genera consecuencia legal
irreversible solo por exceder un plazo administrativamente — permite ajuste
manual de términos, salvo que se registre un "recurso legal interpuesto"
(pendiente de modelar), que retira ese margen. Este servicio de plazos es la
pieza que materializa esa regla: el ajuste manual es una operación explícita
sobre el `PlazoLegal` de un expediente, auditada como cualquier otra acción
de dominio (ADR-011).

## Consecuencias

- Este servicio es la base de la que depende ADR-013 (analítica de
  cumplimiento de plazos) — se construye primero.
- Requiere mantener el catálogo de festivos colombianos actualizado cada
  año (bajo mantenimiento, pero de bajo esfuerzo — dato público y estable).
- Al ser un servicio único reutilizado, un cambio en la lógica de cómputo
  (ej. una corrección legal) se corrige en un solo lugar, no en cada tipo de
  plazo por separado.

## Pendiente

- Umbral(es) exactos de alerta temprana/urgente — afinar con el usuario o
  dejarlo configurable por curaduría.
- Fuente concreta del catálogo de festivos colombianos (mantenimiento manual
  vs. librería/paquete existente para Laravel).
- Modelar el flag/evento "recurso legal interpuesto" (ya identificado en
  `docs/dominio/flujo-tramite.md`) que retira el margen de ajuste manual.
- Definir si el umbral de alerta es configurable por curaduría o fijo para
  toda la plataforma.
