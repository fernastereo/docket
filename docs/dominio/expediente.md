# Expediente

**Estado**: Definido (con puntos pendientes explícitos)
**Fecha**: 2026-08-27

## Definición

Agregado raíz del trámite: compila todo el proceso desde la radicación hasta
la expedición del acto administrativo final (licencia u otra actuación). Se
abre **un expediente por cada radicación** — no acumula múltiples
radicaciones dentro de sí.

## Expedientes relacionados (modificación, revalidación, prórroga)

Una modificación, revalidación o prórroga de una licencia **no reabre el
expediente original**: genera un **expediente nuevo**, con su propia
radicación, que referencia al expediente del que proviene.

- Relación auto-referenciada: `expediente_origen` (nulo si es un expediente
  original, sin trámite previo).
- Preserva el historial completo: desde cualquier expediente se puede
  recorrer la cadena hacia atrás hasta el original.
- El **tipo de trámite** de un expediente derivado (modificación,
  revalidación, prórroga) es distinto del tipo del expediente original —
  detalle en `docs/dominio/tipo-tramite.md`.

## Radicación

No es una entidad aparte — son atributos del propio Expediente (colapsado
aquí porque 1 expediente = 1 radicación, sin radicaciones múltiples dentro de
un mismo expediente):

- Fecha de radicación.
- Canal: ventanilla (presencial) o en línea (ADR-006).
- Número de radicado (formato/lógica de numeración: bloque futuro).

## Tipo(s) de trámite

Un expediente puede contener **más de un tipo de trámite a la vez** (ej.
licencia de construcción + licencia de urbanización en el mismo trámite; o
combinaciones dentro de "Otras Actuaciones"). No se asume un único tipo por
expediente. Detalle del catálogo en `docs/dominio/tipo-tramite.md` (entidad
renombrada desde "Licencia" — ver esa nota ahí).

## Equipo asignado

Cada expediente tiene asignado, siempre uno de cada uno:

- Un **arquitecto**.
- Un **ingeniero civil**.
- Un **abogado**.

de la curaduría, encargados de llevar el estudio y trámite hasta el final.
El **curador urbano** es una figura aparte de ese equipo: **aprueba al final**
(expide el acto administrativo), no es uno de los tres roles asignados.

Cualquier otro usuario interno de la curaduría puede generar actuaciones
puntuales dentro del expediente (radicar, generar un acta, generar una
comunicación, etc.) sin necesidad de ser parte del equipo asignado — ver
Historia/auditoría.

## Estado

Dos niveles, **no dos campos independientes**:

- **Estado interno**: detallado, para el trámite operativo dentro de la
  curaduría (catálogo completo pendiente — bloque futuro de flujo/máquina de
  estados).
- **Estado público**: simplificado, de cara al ciudadano. Se **deriva/mapea**
  automáticamente desde el estado interno (varios estados internos pueden
  agruparse bajo un mismo estado público), para que nunca queden
  desincronizados. No es un campo que alguien actualice manualmente aparte.

El catálogo de estados (internos y su mapeo a públicos) se define en el
bloque de flujo del trámite / máquina de estados (pendiente).

## Historia / auditoría

Toda la vida del expediente dentro de la curaduría debe quedar trazada: qué
cambió, quién lo hizo, cuándo, y cualquier otro dato relevante de contexto.
Esto **no requiere una entidad de dominio nueva**: lo cubre el mecanismo ya
decidido en ADR-011 (eventos de dominio → audit log) — cada acción relevante
sobre el expediente (radicar, cambiar de estado, generar un documento,
asignar equipo, etc.) emite un evento que alimenta el audit log del tenant.
El usuario se refirió a esto informalmente como "auditoría" o "bitácora"; se
mantiene como el audit log general, no como una entidad `Auditoria` separada.

## Acto administrativo

El **documento** que se expide al final del trámite (licencia, negación,
resolución, etc.), firmado/aprobado por el curador. Se modela como entidad
propia — ver `docs/dominio/acto-administrativo.md` (siguiente en la
entrevista). Distinto de "Actuación", término reservado para el proceso de
expedición de ese documento por parte del curador (se detallará en el bloque
de flujo del trámite).

## Documentos generados internamente (actas, comunicaciones)

Además de los documentos que radica el solicitante, la curaduría genera
documentos propios durante el trámite (actas, comunicaciones) como salida de
acciones puntuales del sistema — hoy el legado solo los imprime, no los
gestiona como un concepto de dominio separado. Por ahora se tratan como una
variante de `Documento` (discriminador de origen: radicado por el
solicitante vs. generado por la curaduría) — **queda abierto**, no está claro
todavía si merecen su propia entidad. Se revisa al detallar
`docs/dominio/documento.md`.

## Relaciones (resumen)

- `Expediente ↔ Predio`: muchos a muchos (`docs/dominio/predio.md`).
- `Expediente ↔ Solicitante`: muchos a muchos, con calidad en que actúa y
  contacto principal (`docs/dominio/solicitante.md`).
- `Expediente → Documento`: uno a muchos (un expediente = una radicación, sin
  ambigüedad de a cuál radicación pertenece un documento).
- `Expediente → Acto administrativo`: uno a muchos (pendiente de precisar
  cardinalidad exacta al definir esa entidad).
- `Expediente → Expediente` (`expediente_origen`): auto-referencia para
  modificación/revalidación/prórroga.

## Pendiente

- Catálogo de estados internos y su mapeo a estados públicos (bloque futuro).
- Formato/lógica de numeración del radicado (bloque futuro) — se sabe que es
  uno por expediente (1 expediente = 1 radicación), falta el detalle fino.
- Si los documentos generados internamente (actas, comunicaciones) necesitan
  entidad propia en vez de ser una variante de `Documento`.
- Roles reales de la curaduría más allá de arquitecto/ingeniero civil/
  abogado/curador (bloque futuro de RBAC).
- `glosario.md`: confirmar `Expediente → Case` o `Filing` (hoy con `(?)`),
  agregar `Estado interno → Internal status (?)`,
  `Estado público → Public status (?)`, `Equipo asignado → Assigned team (?)`.
