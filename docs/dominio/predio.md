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

- Matrícula inmobiliaria (identificador estructurado — ver sección propia
  abajo, no texto libre).
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

## Matrícula inmobiliaria (identificador estructurado)

La matrícula inmobiliaria colombiana es un número compuesto (ej.
`040-84848`): un prefijo de 3 dígitos que identifica la **ORIP** (Oficina de
Registro de Instrumentos Públicos) que lleva ese folio, seguido de un
consecutivo propio de esa oficina. El legado la guarda como un solo texto
libre — con el problema conocido de que si se captura sin guion, no
encuentra el dato. Se modela distinto:

- **`orip`**: no es texto libre — es una **referencia a un catálogo
  precargado de Oficinas de Registro de Instrumentos Públicos** (código +
  nombre de la oficina), dato semilla del sistema (lista oficial, pública y
  estable de la SNR). Valida el prefijo automáticamente y permite mostrar el
  nombre de la oficina, no solo el código.
- **`consecutivo`**: numérico, normalizado (sin separadores), **hasta 10
  dígitos**.
- **Captura en dos campos separados** (selector de ORIP + consecutivo), no
  un solo campo de texto a parsear.
- **Presentación**: el formato "ORIP-consecutivo" se **reconstruye** al
  mostrar, a partir de los dos campos guardados — nunca se guarda el guion
  como parte del dato. Esto elimina de raíz el bug del legado (formato
  inconsistente que no encuentra el registro).
- **Llave de deduplicación**: el par `(orip, consecutivo)`, no un string
  completo — evita que variaciones de formato de captura hagan que el mismo
  predio no se reconozca como tal entre expedientes, y evita falsos
  positivos entre consecutivos iguales de ORIPs distintas.

## Relación con Expediente

`Expediente ↔ Predio` es una relación **muchos a muchos**:

- Un expediente puede tener varios predios.
- Un mismo predio, identificado por su matrícula inmobiliaria como llave
  `(orip, consecutivo)`, puede aparecer en varios expedientes a lo largo del
  tiempo — esto es lo que permite consultar el **historial de trámites de un
  predio** directamente por la relación, sin necesidad de búsqueda difusa
  entre registros duplicados.
- La captura en pantalla siempre parte de cero (no hay autocompletar desde un
  predio existente en esta fase); por debajo, si la llave `(orip,
  consecutivo)` coincide con un predio ya existente, se reutiliza el mismo
  registro. La llave de coincidencia ya queda definida (ver sección
  Matrícula inmobiliaria); el flujo de UX exacto (automático vs. con
  confirmación del usuario cuando hay match) queda pendiente — no se resuelve
  en este bloque.

## Relación con Solicitante

**No hay relación directa** Predio↔Solicitante. La propiedad del predio no se
modela como dato del sistema (no hay campo "propietario" en Predio); se
demuestra con el certificado de tradición y libertad, que es un documento
radicado en el expediente. Quién actúa respecto a un predio (como propietario,
apoderado, etc.) es información que vive en la relación Expediente↔Solicitante
(ver `docs/dominio/solicitante.md`), no en el Predio.

## Pendiente

- Flujo de UX de la deduplicación: si al coincidir `(orip, consecutivo)` con
  un predio existente se reutiliza automáticamente o se pide confirmación al
  usuario.
- Origen/mantenimiento del catálogo semilla de ORIPs (fuente oficial exacta,
  cómo se actualiza si la SNR crea/fusiona oficinas).
- Validación de formatos por campo más allá de dirección y matrícula (rural
  vs. urbano, propiedad horizontal, predios sin catastro formal, etc.) —
  particularidades del legado a revisar en el bloque de datos/migración.
- Si vereda/sector/manzana deberían seguir el mismo patrón de catálogo
  autogestionado que barrio/comuna/localidad/corregimiento, o quedarse como
  texto libre — no se decidió explícitamente, queda para revisar.
- `glosario.md`: confirmar `Predio → Property` (hoy con `(?)`), agregar
  `Catálogo autogestionado → Self-managed catalog (?)`,
  `Nomenclatura → Address numbering (?)`, `ORIP → ORIP (se mantiene la
  sigla) (?)`, `Consecutivo (matrícula) → Sequence number (?)`.
