# Documento

**Estado**: Definido (propuesta validada con el usuario; catálogo cerrado
pendiente)
**Fecha**: 2026-08-31

## Definición

Todo archivo/contenido asociado a un expediente que **no** es un acto
administrativo (catálogo cerrado y propio, `docs/dominio/acto-
administrativo.md`) cae en una de dos categorías:

- **Documento radicado**: entregado por el solicitante (planos, escrituras,
  cédulas, certificados...).
- **Documento generado**: producido por la curaduría durante el trámite
  (actas, estudios, comunicaciones, liquidación de expensas, formato de
  valla, entre otros — lista completa en `docs/dominio/acto-
  administrativo.md`, sección "Documentos vinculantes del trámite").

El sistema legado resuelve la generación fusionando datos de la BD contra una
plantilla fija de Word — frágil, pesado, dependiente de versión de Word. El
modelo nuevo reemplaza ese enfoque (ver Documento generado y Plantilla).

## A. Documento radicado

Modelo tipo **checklist**:

- Catálogo de requisitos documentales por tipo de trámite (**pendiente**,
  bloque futuro) define qué documentos se esperan.
- Cada ítem se marca **entregado** cuando el solicitante lo radica (o el
  funcionario de ventanilla lo recibe), con el **escaneo adjunto** vinculado
  al expediente.
- Estado: pendiente / entregado / rechazado.
- Si se resubsana (se vuelve a radicar por incompleto o rechazado), se guarda
  como **nueva versión** de ese ítem — no se sobreescribe; se conserva quién
  y cuándo entregó cada versión.

## B. Plantilla de documento (`PlantillaDocumento`)

Entidad de configuración, **propia de cada curaduría** — cada una puede tener
su propio formato/modelo, tanto para actos administrativos como para
documentos generados vinculantes:

- Contenido en **texto enriquecido** (formato, fuentes, colores, formas —
  no un archivo binario de Word) con **campos de fusión** marcados que
  apuntan a datos ya modelados del expediente (ej. datos de Solicitante,
  Predio, Tipo de trámite).
- Una plantilla por tipo de documento: uno de los 4 tipos de acto
  administrativo (catálogo cerrado), o uno de los tipos del catálogo
  configurable `TipoDocumentoGenerado` (ver sección C).
- **`editable_post_generacion`** (booleano): **configurable por curaduría**,
  por tipo de documento/plantilla — decide si ese tipo de documento, una vez
  generado, entra a un flujo de edición (como las resoluciones) o queda
  **expedido y cerrado de inmediato**, sin posibilidad de edición posterior.
  Ejemplo dado por el usuario: acta de observaciones → editable; liquidación
  de expensas → expedición inmediata, nunca editable.

El estudio técnico de qué solución de edición de texto enriquecido usar
(reemplazo del enfoque Word del legado) queda pendiente para el bloque de
implementación.

Igual que en Acto Administrativo (`docs/dominio/acto-administrativo.md`), se
espera un **componente de IA** (ADR-004, candidato 4) que ayude a que la
fusión de datos con el texto de la plantilla se lea coherente, y que revise
el lenguaje del documento generado señalando fallas/inconsistencias de
redacción — aplica igual a cualquier documento generado, no solo a actos
administrativos.

## C. Documento generado (vinculante)

Flujo de generación, común a todos los tipos, gobernado por
`editable_post_generacion` de su plantilla:

1. **Generación**: se resuelven los campos de fusión de la plantilla contra
   los datos reales del expediente, produciendo el contenido inicial.
2. Si **`editable_post_generacion = true`**: el resultado es un **borrador
   editable**, con **historial de versiones** (quién cambió qué y cuándo,
   igual que el mecanismo ya descrito para Acto Administrativo) hasta que se
   marca como expedido/cerrado — momento en que se **congela**.
3. Si **`editable_post_generacion = false`**: el documento queda **expedido y
   congelado de inmediato** al generarse — nunca pasa por un estado editable.

**Catálogo como entidad configurable, no lista fija en código**:
`TipoDocumentoGenerado` es un catálogo propio de cada curaduría (igual
espíritu que `PlantillaDocumento`), no un enum cerrado en la aplicación. El
legado resuelve cada tipo como pantalla/reporte fijo en el software; el
patrón moderno es que una curaduría pueda, a futuro, definir un tipo de
documento propio sin depender de un despliegue de código. Los tipos ya
identificados en la práctica (semilla inicial del catálogo, no lista
cerrada): comunicaciones a vecinos y sus aclaratorias, acta de observaciones,
acta de radicación incompleta, acta de radicación en legal y debida forma,
liquidación de cargos fijos y variables de expensas, liquidación de
impuestos municipales/distritales (gestionada fuera del sistema, solo
documentada), Estudio Arquitectónico, Estudio Jurídico, Estudio
Estructural, Auto de Viabilidad, formato de valla, entre otros. Cada tipo
tiene su propia `PlantillaDocumento` y su propio `editable_post_generacion`.

## Relación con Acto Administrativo

Acto Administrativo (entidad separada, con su propio catálogo cerrado de 4
tipos y su lógica de aclaratorias — `docs/dominio/acto-administrativo.md`)
**reutiliza el mismo mecanismo**: `PlantillaDocumento` propia de cada
curaduría + fusión de datos + edición con historial de versiones hasta el
congelamiento al expedirse. Se mantiene como entidad aparte por su peso legal
propio y sus relaciones específicas (con Expediente y con Tipo de Trámite),
no se fusiona con Documento.

## Campos personalizados

Cada curaduría puede definir **campos personalizados** sobre Documento
(`docs/adr/ADR-016-campos-personalizados-tenant.md`), incluidos campos de
fusión (`surface = documento.merge_fields`) que quedan disponibles en la
`PlantillaDocumento` de los documentos generados vinculantes. Es un mecanismo
transversal, hermano —no sustituto— de `PlantillaDocumento` y del catálogo
configurable `TipoDocumentoGenerado`.

## Pendiente

- Catálogo de requisitos documentales por tipo de trámite/modalidad (qué
  documentos radicados se esperan según el caso) — bloque futuro.
- Mecanismo de gestión del catálogo `TipoDocumentoGenerado` (quién puede
  crear/editar tipos dentro de una curaduría, validaciones mínimas) y su
  `editable_post_generacion` por defecto al crear uno nuevo.
- Estudio técnico de la solución de edición de texto enriquecido
  (implementación).
- `glosario.md`: agregar `Documento radicado → Filed document (?)`,
  `Documento generado → Generated document (?)`,
  `Plantilla de documento → Document template (?)`,
  `Requisito documental → Document requirement (?)`.
