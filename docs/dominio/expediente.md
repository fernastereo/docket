# Expediente

**Estado**: Definido (con puntos pendientes explícitos)
**Fecha**: 2026-08-27

## Definición

Agregado raíz del trámite: compila todo el proceso desde la radicación hasta
la expedición del acto administrativo final (licencia u otra actuación). Se
abre **un expediente por cada radicación** — no acumula múltiples
radicaciones dentro de sí.

## Expedientes relacionados (objeto: modificación / revalidación)

Un expediente cuyo **objeto** es Modificación o Revalidación (ver
`docs/dominio/tipo-tramite.md`) no reabre el expediente/acto original: genera
un **expediente nuevo**, con su propia radicación, que debe amarrarse al acto
administrativo del que proviene. "Prórroga" es una actuación dentro de
"Otras actuaciones" y sigue la misma lógica de vínculo con el acto anterior.

El acto administrativo anterior **puede o no vivir en nuestra base de
datos**, porque puede haber sido expedido por **otra curaduría** (otro
tenant — ADR-002 no permite relaciones/joins entre bases de tenants
distintos). Dos casos:

- **El expediente anterior es de la propia curaduría**: relación
  auto-referenciada real, `expediente_origen` (FK al expediente anterior en
  la misma base). Da acceso directo a todo su historial e información.
- **El acto anterior es de otra curaduría** (o de ninguna — nunca pasó por
  esta plataforma): no hay expediente al cual referenciar. Se captura en su
  lugar una **referencia externa al acto administrativo anterior**, con los
  datos mínimos:
  - Número del acto administrativo.
  - Fecha de expedición.
  - Fecha de ejecutoria.
  - Número de la curaduría que lo expidió.
  - Documento escaneado del acto (soporte).

  Sin más datos disponibles — no se puede acceder al expediente que lo
  originó, solo a esta referencia.

En ambos casos, la cadena histórica es recorrible hacia atrás: en el primer
caso navegando expedientes reales; en el segundo, hasta donde llegue la
información capturada como referencia externa.

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

Cada expediente tiene asignado, siempre uno de cada uno: un **arquitecto**,
un **ingeniero civil** y un **abogado** de la curaduría, encargados de llevar
el estudio y trámite hasta el final. El **curador urbano** es una figura
aparte de ese equipo: **aprueba al final** (expide el acto administrativo),
no es uno de los tres roles asignados.

**Modelado como relación, no como columnas fijas**: `Expediente ↔ Empleado`
con atributo `rol` (arquitecto / ingeniero civil / abogado), en vez de tres
columnas dedicadas (`arquitecto_id`, `ingeniero_civil_id`, `abogado_id`) en
el propio Expediente. Mismo mecanismo que la calidad en
`Expediente ↔ Solicitante`. Evita que agregar un rol futuro (ej. un segundo
revisor, un rol exigido solo en algunos municipios) requiera migración de
esquema — solo agrega un valor más al catálogo de roles.

**Con historial, no solo el asignado actual**: la asignación puede cambiar
durante la vida del expediente (reasignaciones), y se debe poder consultar
**quién estuvo asignado, con qué rol, durante qué periodo** — mismo patrón
de vigencia (`desde`/`hasta`) que `RepresentanteLegal` en
`docs/dominio/solicitante.md`. No se sobreescribe la asignación anterior al
reasignar: se cierra su vigencia y se abre una nueva.

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

**Requisito explícito de producto**: este audit log no es solo un respaldo
de cumplimiento — debe poder presentarse como una **línea de tiempo de
actividad por expediente**, al estilo Jira/ClickUp (quién hizo qué, cuándo,
incluyendo reasignaciones de equipo y, más adelante, cambios de estado).
Repercute directamente en el bloque futuro de flujo del trámite/máquina de
estados: las transiciones de estado deben emitir eventos con el mismo nivel
de detalle para poder aparecer en esa misma línea de tiempo.

## Acto administrativo

El **documento** que resuelve el expediente (catálogo cerrado: aprobación,
negación, desistimiento, aclaratoria), firmado/aprobado por el curador. Un
expediente puede generar uno o varios, de forma configurable por curaduría —
detalle completo en `docs/dominio/acto-administrativo.md`. Distinto de
"Actuación", término reservado para el proceso de expedición de ese documento
por parte del curador (se detallará en el bloque de flujo del trámite).

## Documentos vinculantes del trámite (actas, estudios, comunicaciones)

Además de los documentos que radica el solicitante y de los actos
administrativos (catálogo cerrado de 4 tipos, ver
`docs/dominio/acto-administrativo.md`), la curaduría genera durante el
trámite una tercera categoría de documentos, vinculantes pero sin ser actos
administrativos: comunicaciones a vecinos (y sus aclaratorias), acta de
observaciones, acta de radicación incompleta, acta de radicación en legal y
debida forma, liquidación de cargos fijos y variables de expensas,
liquidación de impuestos municipales/distritales (gestionada fuera del
sistema, solo documentada), Estudio Arquitectónico, Estudio Jurídico,
Estudio Estructural, Auto de Viabilidad, formato de valla, entre otros. Se
generan en el momento que corresponda dentro del trámite, por cualquier
usuario interno de la curaduría. Detalle completo en
`docs/dominio/documento.md`.

## Relaciones (resumen)

- `Expediente ↔ Predio`: muchos a muchos (`docs/dominio/predio.md`).
- `Expediente ↔ Solicitante`: muchos a muchos, con calidad en que actúa y
  contacto principal (`docs/dominio/solicitante.md`).
- `Expediente → Documento`: uno a muchos (un expediente = una radicación, sin
  ambigüedad de a cuál radicación pertenece un documento).
- `Expediente → Acto administrativo`: uno a muchos, configurable por
  curaduría (`docs/dominio/acto-administrativo.md`).
- `Expediente → Expediente` (`expediente_origen`, nulo salvo objeto
  modificación/revalidación **con acto anterior de la propia curaduría**) —
  ver "Expedientes relacionados" arriba para el caso de acto anterior externo.

## Campos personalizados

Además de los atributos modelados aquí, cada curaduría puede definir **campos
personalizados** sobre el Expediente (y sobre sus relaciones con Solicitante,
Empleado y Predio) — tipo de dato y pantallas donde aparecen configurables por
tenant, sin cambio de esquema. Mecanismo transversal definido en
`docs/adr/ADR-016-campos-personalizados-tenant.md`. Un campo personalizado
puede marcarse obligatorio para una transición de estado (ver
`docs/dominio/flujo-tramite.md`).

## Pendiente

- Catálogo de estados internos y su mapeo a estados públicos (bloque futuro).
- Formato/lógica de numeración del radicado (bloque futuro) — se sabe que es
  uno por expediente (1 expediente = 1 radicación), falta el detalle fino.
- Si los documentos vinculantes del trámite (actas, estudios, comunicaciones)
  necesitan entidad propia en vez de ser una variante de `Documento` — se
  resuelve en `docs/dominio/documento.md`.
- Roles reales de la curaduría más allá de arquitecto/ingeniero civil/
  abogado/curador (bloque futuro de RBAC).
- `glosario.md`: confirmar `Expediente → Case` o `Filing` (hoy con `(?)`),
  agregar `Estado interno → Internal status (?)`,
  `Estado público → Public status (?)`, `Equipo asignado → Assigned team (?)`.
