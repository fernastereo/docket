# Flujo del Trámite / Máquina de Estados

**Estado**: Definido — contrastado contra el listado real de estados de una
curaduría (2026-08-31). Quedan puntos puntuales marcados como pendientes.
**Fecha**: 2026-08-31

## Definición

Máquina de estados del Expediente (`docs/dominio/expediente.md`), desde la
radicación hasta el cierre. Camino principal descrito abajo; la **negación**
puede ocurrir en más de un punto (ver sección propia) y siempre desvía
directo a redacción de Resolución.

**Base legal**: el proceso de estudio y expedición de licencias está reglado
en el Decreto 1077/2015, arts. **2.2.6.1.1.1 a 2.2.6.6.9.2** (definiciones,
procedimiento de estudio/expedición y cálculo de expensas). Los estados y
plazos de abajo son la lectura operativa de ese procedimiento; las
particularidades por curaduría se marcan como configurables.

## Máquina de estados configurable por curaduría

**No es una máquina de estados única y fija para todas las curadurías.**
Contrastado contra el listado real de una curaduría (IDESTADO/DESCRIPCION):
confirma un **núcleo común** (los estudios, legal y debida forma, pagos,
resolución, ejecutoria, desistimiento) más **variaciones propias** de cada
una (ej. un estado de "estudio jurídico incompleto" que resultó ser
particular de esa curaduría — no se incluye en el núcleo). El documento
que sigue describe el **núcleo común**; cada curaduría puede tener estados
adicionales propios sin que eso rompa el modelo.

Un valor como `SIN ESTADO` (IDESTADO 0) del legado es un **placeholder
técnico** (registro sin clasificar), no un estado de negocio — no se
replica.

**Campos personalizados como requisito de transición**: una curaduría puede
exigir que uno o más campos personalizados del expediente
(`docs/adr/ADR-016-campos-personalizados-tenant.md`,
`surface = workflow.transition_requirement`) estén diligenciados para permitir
una transición concreta. Se evalúa como invariante de dominio en el guard de
la transición (ADR-011); si falta el valor, la transición se rechaza
(`MissingRequiredCustomFieldException` → 422). El requisito puede condicionarse
(ej. solo si la clase es Construcción).

## Estados internos y transiciones (núcleo común)

1. **Radicado** — se recibe documentación del solicitante, se liquidan
   cargos fijos de expensas.
2. Al terminar la radicación, transición excluyente a uno de dos estados:
   - **Incompleto**: plazo 30 días hábiles, **sin prórroga**. Completar
     parcialmente **no reinicia el plazo ni cambia el estado** — solo
     completar totalmente lo dispara. Si vence sin completar →
     **Desistido por documentación incompleta** (terminal). Si se completa a
     tiempo → pasa a Legal y Debida Forma.
   - **Legal y Debida Forma**: arranca el **término general de 45 días
     hábiles** de la curaduría para resolver.
3. **Asignación de equipo** — el despacho de la curaduría (curador, o un
   coordinador según la curaduría) asigna el arquitecto/ingeniero/abogado
   encargados (`Expediente ↔ Empleado` con vigencia,
   `docs/dominio/expediente.md`). Inicia el proceso real.
4. En algún punto de este tramo la curaduría genera el documento **Formato
   de Valla** — **no es un estado**, es un documento que se expide y punto
   (`docs/dominio/documento.md`), no una transición de la máquina. Momento
   exacto dentro del tramo: pendiente de confirmar.
5. **En estudios** — Estudio Jurídico, Estudio Arquitectónico (el legado lo
   llama indistintamente "técnico") y Estudio Estructural. Orden libre o en
   paralelo, **a discreción de cada curaduría**. Es un **punto de
   sincronización**: no se avanza hasta que los 3 estén completos.
6. **Consolidación** (por el arquitecto encargado, revisando los 3
   estudios) — tres desenlaces posibles:
   - **Sin observaciones** → pasa a Auto de Viabilidad (8).
   - **Con observaciones** → se genera el **Acta de Observaciones** (única
     en todo el proceso, **no se repite** como documento hacia el
     solicitante) → estado **Suspendido por observaciones**: plazo 30 días
     hábiles, prorrogable **una vez** por 15 días hábiles adicionales.
     - **Internamente se rastrea por área**: revisión de correcciones
       técnicas, jurídicas y estructurales por separado (cada una certifica
       su parte), con el mismo tipo de punto de sincronización que los
       estudios iniciales — hasta que las 3 áreas certifiquen subsanación no
       se retoman términos, aunque de cara al solicitante solo exista un
       Acta de Observaciones.
     - Si las 3 áreas certifican subsanación completa dentro del plazo → se
       retoman los términos generales → pasa a Auto de Viabilidad (8).
     - Si alguna corrección entregada no subsana lo suyo, **el estado no
       cambia**: sigue "Suspendido por observaciones", el plazo sigue
       corriendo sin reiniciarse.
     - Si vence el plazo (30 + 15 días hábiles) sin que las 3 áreas
       certifiquen → **Desistido por incumplimiento del Acta de
       Observaciones** (terminal).
   - **Negación** → salta directo a Redacción de Resolución (10), como
     negación.
7. *(fusionado en 6)*
8. **Auto de Viabilidad** (expedido por el arquitecto encargado) — dos
   desenlaces:
   - **Viable**: se liquidan cargos variables de expensas y, por separado,
     el impuesto municipal/distrital (gestionado **fuera del sistema**, solo
     se documenta evidencia/escaneo). Son **dos pagos independientes**
     (confirmado contra el legado: estados separados de pago de expensas y
     pago de distrito) → estado **Suspendido por pago**: plazo 30 días
     hábiles, **sin prórroga**, aplicable a ambos pagos.
     - Si el solicitante paga ambos a tiempo → se retoman los términos →
       quedan **5 días hábiles** para expedir la Resolución.
     - Si vence sin pago (de cualquiera de los dos) → **Desistido por no
       pago de expensas/impuestos** (terminal).
   - **No viable** → Negación → salta directo a Redacción de Resolución (10).
9. *(fusionado en 8)*
10. **Redacción de Resolución** — cadena interna (normalmente lineal;
    puede variar):
    1. Para proyectar Resolución (borrador inicial).
    2. Resolución proyectada.
    3. Revisión/concepto jurídico de la Resolución.
    4. Revisión de la Resolución por el curador.
    5. Visto bueno en coordinación de proyectos.
    6. Para entregar al solicitante.
    Todo sobre el mecanismo de plantilla+fusión+edición con historial de
    versiones ya definido (`docs/dominio/documento.md`,
    `docs/dominio/acto-administrativo.md`). Resultado: **aprobación** o
    **negación** según el punto del proceso del que venga.
11. **Expedida** — Resolución firmada por el curador, con número (formato de
    numeración pendiente) y fecha de expedición. Empiezan a correr los
    términos del **Código Contencioso Administrativo**.
12. **Ejecutoriada** — la Resolución queda en firme según esos términos.
13. **Cerrado** (terminal) — se cierra al quedar Ejecutoriada, **no** al
    momento de expedirse.

## Estados de tránsito/custodia (intermedios, configurables por curaduría)

Distintos de los estados sustantivos de arriba: son estados **puramente de
ubicación/custodia interna** (ej. "en sección técnica", "en coordinación de
proyectos", "en despacho de la curadora"), que pueden insertarse **entre
cualquier par de estados sustantivos** para mostrar más fielmente dónde está
físicamente el expediente — sin plazos ni significado legal propio; existen
para la línea de tiempo de actividad (Jira/ClickUp-style,
`docs/dominio/expediente.md`) y para el traslado interno de responsabilidad.
Quién los usa y con qué nombres es **configurable por curaduría** (algunas
usan "Coordinador de proyectos" como rol; otras el arquitecto encargado
directamente).

## Comunicaciones a vecinos y objeciones/recursos

Las comunicaciones a vecinos se envían **una vez el expediente queda en
Legal y Debida Forma**, en cualquier punto posterior pero **antes de
finalizar el Estudio Arquitectónico**. Tras enviarse, los vecinos pueden
**objetar el proyecto o interponer recursos legales** — "objeción" y
"recurso" son el mismo concepto (se fusionan, no se distinguen). Esto se
estudia y resuelve, pero la respuesta formal solo se da dentro de la
Resolución final (aprobación o negación) — **no genera un estado bloqueante
ni suspende los términos** de la curaduría; corre en paralelo al resto del
proceso.

## Negación: no es un estado único, es un desenlace posible en varios puntos

La negación **no espera necesariamente hasta el final**: puede determinarse
en la Consolidación (6) o en el Auto de Viabilidad (8), y en cualquiera de
esos casos el expediente **salta directo** a Redacción de Resolución sin
completar los pasos restantes del camino de aprobación.

## Desistimiento: catálogo de motivos

1. **Documentación incompleta** (30 días hábiles, sin prórroga).
2. **Incumplimiento del Acta de Observaciones** (30+15 días hábiles).
3. **No pago de expensas y/o impuestos municipales/distritales** (30 días
   hábiles, sin prórroga).

Confirmado contra el legado: es **un solo estado** (`DESISTIDA`), el motivo
va como atributo — no como estados/tipos separados.

## Plazos legales (resumen)

| Punto | Plazo | ¿Prorrogable? | Si vence |
|---|---|---|---|
| Incompleto | 30 días hábiles | No | Desistido por documentación incompleta |
| Legal y Debida Forma (término general) | 45 días hábiles | — | Se suspende por observaciones o por pago; no vence por sí solo |
| Suspendido por observaciones | 30 días hábiles | Sí, +15 días hábiles (una sola prórroga) | Desistido por incumplimiento del Acta de Observaciones |
| Suspendido por pago (expensas y distrito) | 30 días hábiles | No | Desistido por no pago de expensas/impuestos |
| Post-pago hasta expedir Resolución | 5 días hábiles | — | Ver nota abajo |
| Firmeza | Términos del Código Contencioso Administrativo | — | Ejecutoriada → Cierre |

**Nota sobre exceder el plazo de 5 días**: hay consecuencia legal en teoría,
pero el sistema **no debe endurecer esto contra la propia curaduría**. Debe
permitir **ajuste administrativo manual de términos**, no bloquear ni
generar una alerta legal automática e irreversible. La única situación
donde ese margen desaparece es si el **solicitante interpone un recurso
legal** por el incumplimiento del plazo. Modelar ese flag/evento queda
pendiente para la implementación del contador de términos.

Todos los plazos son en **días hábiles**. El término general (45 días) se
**suspende** (no se reinicia) durante "Suspendido por observaciones" y
"Suspendido por pago", y se retoma desde donde iba al resolverse la
suspensión — mecánica exacta a precisar en implementación.

## Mapeo a estado público — PROPUESTA, pendiente de confirmar

| Estados internos | Estado público propuesto |
|---|---|
| Radicado, Incompleto, Legal y Debida Forma, Asignación de equipo | Radicado |
| En estudios, Consolidación, Suspendido por observaciones (+ estados de tránsito) | En trámite |
| Auto de Viabilidad (viable), Suspendido por pago | En trámite (pendiente de pago) |
| Redacción de Resolución (toda la cadena interna) | En trámite (resolución en curso) |
| Expedida | Expedida |
| Ejecutoriada, Cerrado (con Resolución de aprobación) | Aprobada |
| Ejecutoriada, Cerrado (con Resolución de negación) | Negada |
| Desistido (cualquier motivo) | Desistida |

`Resolviendo objeciones/recursos de vecinos` no aparece en la tabla porque
no es un estado bloqueante — es una marca paralela, no reemplaza el estado
sustantivo vigente en ese momento.

## Pendiente

- Confirmar el mapeo estado interno → estado público propuesto arriba.
- Modelar el flag/evento "recurso legal interpuesto" que quita el margen de
  ajuste manual de términos tras exceder el plazo post-pago (ver
  `docs/adr/ADR-014-gestion-plazos-alertas.md`).
- Estructura de datos para objeciones/recursos de vecinos (quién objeta,
  cuándo, contenido, resuelto sí/no) — no abordada en este documento.
- Mecánica exacta de cómputo de días hábiles suspendidos/retomados: **ya
  resuelta a nivel de decisión** en
  `docs/adr/ADR-014-gestion-plazos-alertas.md` (servicio central de plazos +
  alertas predictivas); falta implementación.
- `glosario.md`: agregar los nombres de estados y motivos de desistimiento
  listados en este documento (pendiente de una pasada dedicada).
