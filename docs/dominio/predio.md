# Predio

**Estado**: Definido
**Fecha**: 2026-08-27

## Definición

Inmueble (lote, terreno, unidad) sobre el que recae un trámite. Un expediente
puede involucrar **más de un predio** (ej. una urbanización que agrupa varios
lotes), y un mismo predio puede estar involucrado en **varios expedientes** a
lo largo del tiempo (el mismo lote tramitando cosas distintas en distintos
momentos).

## Atributos

Capturados en el trámite (sin normalizar formatos todavía — pendiente para el
bloque de datos/migración):

- Matrícula inmobiliaria.
- Dirección.
- Dirección anterior.
- Referencia catastral.
- Área del lote.
- Barrio.
- Comuna.
- Localidad.
- Manzana.
- Vereda.
- Sector.
- Corregimiento.
- Número de lote.

No todos aplican a todo predio (ej. barrio/comuna son urbanos; vereda/
corregimiento son rurales) — la obligatoriedad y validación por tipo se
define en un bloque posterior. Ninguno de estos campos se puede omitir: son
exigidos por ley debido al formato físico existente para este trámite.

### Modernización acordada sobre estos campos

No se quitan ni normalizan como catálogos oficiales externos (ver "Pendiente"
para lo que sí queda pendiente de estudiar), pero sí se estructuran mejor que
como texto plano:

- **Dirección**: se descompone en sus partes típicas de nomenclatura
  colombiana en vez de un solo campo de texto libre: nomenclatura + calle
  principal + complemento + calle secundaria + complemento + número. Facilita
  búsqueda/filtrado y reduce variaciones de formato entre capturas del mismo
  predio.
- **Barrio, comuna, localidad, corregimiento**: se manejan como **catálogo
  autogestionado** — no una tabla pre-cargada, sino una lista que crece
  orgánicamente a medida que se radican expedientes: si el valor ya existe se
  selecciona de la lista, si no existe se crea en el momento. Con el tiempo
  converge a una lista normalizada sin necesidad de mantenimiento manual
  previo ni depender de un catálogo oficial externo.

## Relación con Expediente

`Expediente ↔ Predio` es una relación **muchos a muchos**:

- Un expediente puede tener varios predios.
- Un mismo predio (identificado principalmente por su matrícula inmobiliaria)
  puede aparecer en varios expedientes a lo largo del tiempo — esto es lo que
  permite consultar el **historial de trámites de un predio** directamente
  por la relación, sin necesidad de búsqueda difusa entre registros
  duplicados.
- La captura en pantalla siempre parte de cero (no hay autocompletar desde un
  predio existente en esta fase); por debajo, si los datos coinciden con un
  predio ya existente, se reutiliza el mismo registro. El mecanismo exacto de
  esa coincidencia/deduplicación (automática, asistida, manual) queda
  pendiente — no se resuelve en este bloque.

## Relación con Solicitante

**No hay relación directa** Predio↔Solicitante. La propiedad del predio no se
modela como dato del sistema (no hay campo "propietario" en Predio); se
demuestra con el certificado de tradición y libertad, que es un documento
radicado en el expediente. Quién actúa respecto a un predio (como propietario,
apoderado, etc.) es información que vive en la relación Expediente↔Solicitante
(ver `docs/dominio/solicitante.md`), no en el Predio.

## Pendiente

- Mecanismo de deduplicación/coincidencia de predios entre expedientes (por
  matrícula inmobiliaria u otro criterio).
- Validación de formatos por campo más allá de dirección (rural vs. urbano,
  propiedad horizontal, predios sin catastro formal, etc.) — particularidades
  del legado a revisar en el bloque de datos/migración.
- Si vereda/sector/manzana deberían seguir el mismo patrón de catálogo
  autogestionado que barrio/comuna/localidad/corregimiento, o quedarse como
  texto libre — no se decidió explícitamente, queda para revisar.
- `glosario.md`: confirmar `Predio → Property` (hoy con `(?)`), agregar
  `Catálogo autogestionado → Self-managed catalog (?)`,
  `Nomenclatura → Address numbering (?)`.
