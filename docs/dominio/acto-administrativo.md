# Acto Administrativo

**Estado**: Definido
**Fecha**: 2026-08-31

## Definición

El documento formal con valor legal que resuelve un expediente, expedido y
firmado/aprobado por el curador urbano. Es un conjunto **cerrado** de tipos —
no cualquier documento producido durante el trámite es un acto administrativo
(ver "Documentos vinculantes del trámite" abajo, y `docs/dominio/documento.md`
cuando se detalle).

## Tipos (catálogo cerrado)

1. **Resolución de Licencia — Aprobación**.
2. **Resolución de Licencia — Negación**.
3. **Resolución de Desistimiento**.
4. **Resolución Aclaratoria**: expedida después de una de las tres
   anteriores, para resolver un error o ambigüedad detectado en ella.
   Referencia al acto original (auto-relación `acto_origen`, siempre interna
   — a diferencia del `expediente_origen`, aquí no hay caso de acto de otra
   curaduría, porque la aclaratoria corrige un acto propio).

## Relación con Expediente y con Tipo de Trámite

**Configurable por curaduría** (confirmado, no es una regla fija de dominio):

- Algunas curadurías expiden **un solo acto administrativo por expediente**,
  cubriendo todos los tipos/modalidades que contenga.
- Otras expiden **uno o más actos por expediente**, cada uno cubriendo uno o
  varios de los tipos de trámite específicos del expediente (no
  necesariamente todos a la vez).
- Las resoluciones aclaratorias se generan **a partir de** cualquiera de los
  actos ya expedidos en el expediente (pueden ser varias sobre un mismo
  acto).

Esto implica: `Expediente → Acto administrativo` es uno a muchos, y cuando
hay más de uno, cada acto puede relacionarse con un subconjunto de los
"tipo de trámite" del expediente en vez de con el expediente completo — el
mecanismo exacto de esa asociación (qué tipos cubre cada acto) se precisa en
el bloque de flujo del trámite.

## Documentos vinculantes del trámite (no son actos administrativos)

Todo lo demás que se produce durante el trámite es vinculante pero **no**
acto administrativo: comunicaciones a vecinos (y sus aclaratorias), acta de
observaciones, acta de radicación incompleta, acta de radicación en legal y
debida forma, liquidación de cargos fijos y variables de expensas,
liquidación de impuestos municipales/distritales (gestionada fuera del
sistema, solo documentada), Estudio Arquitectónico, Estudio Jurídico,
Estudio Estructural, Auto de Viabilidad, formato de valla, entre otros. Se
generan en el momento que corresponda dentro del trámite. Detalle completo en
`docs/dominio/documento.md`.

## Contenido y redacción

- Se redacta a partir de una **`PlantillaDocumento`** (`docs/dominio/
  documento.md`) — configurable y propia de **cada curaduría** (cada una
  maneja su propio modelo/formato), combinada con datos estructurados del
  expediente. Para Acto Administrativo siempre es editable después de
  generado (agregar/quitar/corregir información) hasta que se expide y se
  congela — no aplica aquí el flag `editable_post_generacion = false` de los
  documentos vinculantes, dado su peso legal siempre requiere ese repaso.
- Requisito explícito: **texto enriquecido real** (formato, fuentes, colores,
  formas), no generación de texto plano. El legado actual resuelve esto
  fusionando datos de la BD contra una plantilla de Word, con los problemas
  típicos de esa integración (dependencia de versión de Word, fragilidad,
  peso) — el sistema nuevo debe evitar ese enfoque. Queda como estudio
  técnico abierto qué solución de edición de texto enriquecido usar.
- Se espera un **componente de IA** con dos funciones (detalle en ADR-004,
  candidato 4): ayudar a que la **fusión** de datos estructurados con el
  texto de la plantilla se lea coherente (no mecánica), y **revisar el
  lenguaje** del documento ya generado, señalando fallas o inconsistencias
  de redacción para que el usuario las corrija antes de expedir. El estudio
  de la solución de edición enriquecida y la implementación de este
  componente se abordan en el bloque de IA/implementación, no en este
  documento de dominio.

## Campos personalizados

Cada curaduría puede definir **campos personalizados** sobre el Acto
Administrativo (`docs/adr/ADR-016-campos-personalizados-tenant.md`). Los que se
marquen como campo de fusión (`surface = acto.merge_fields`) quedan disponibles
en la `PlantillaDocumento` del acto (`{{ cf.<key> }}`), sin código — la vía
para que una curaduría lleve un dato propio suyo al texto de la resolución.

## Pendiente

- Precisar, en el bloque de flujo del trámite, el mecanismo de asociación
  entre un acto administrativo y el/los tipo(s) de trámite que cubre cuando
  una curaduría expide más de uno por expediente.
- Estudio técnico de edición de texto enriquecido para reemplazar el enfoque
  de plantillas Word del legado (bloque de implementación).
- `glosario.md`: confirmar `Acto administrativo → Administrative act` (ya
  está sin `(?)`), agregar `Resolución → Resolution (?)`,
  `Resolución aclaratoria → Clarifying resolution (?)`,
  `Documento vinculante → Binding document (?)`.
