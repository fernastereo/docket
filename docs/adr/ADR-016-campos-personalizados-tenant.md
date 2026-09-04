# ADR-016: Campos personalizados por tenant (custom fields)

**Estado**: Aceptada (modelo cerrado; implementación por fases, pendiente)
**Fecha**: 2026-09-02

## Contexto

Las curadurías comparten un núcleo común de datos por entidad (Expediente,
Predio, Solicitante, etc.), pero **cada una captura variables particulares**
—por exigencia municipal, por decisión interna o por conveniencia operativa—
que no forman parte de ese núcleo.

En el sistema legado (VB6 + Access) esto se resolvió **manteniendo una versión
distinta de la aplicación por curaduría**: 5 versiones que con el tiempo
empezaron a diferir entre sí, con un costo de mantenimiento creciente. Eliminar
ese patrón es una de las razones de ser de la nueva plataforma (principio 6 de
`CLAUDE.md`; no replicar el legado 1:1).

El requisito es un mecanismo **genérico y único** para que cada tenant defina
campos personalizados sobre las entidades del núcleo, especificando su **tipo
de dato** y **en qué módulos/pantallas aparecen**, sin tocar código ni el
esquema físico, y sin reintroducir divergencia entre curadurías.

**Restricción que habilita el diseño**: database-per-tenant (ADR-002) — cada
curaduría tiene su PostgreSQL físico propio. Aun así, la divergencia de
*esquema* entre tenants es justamente lo que hay que evitar: rompería la
premisa de "una sola app desplegada" y el modelo de migraciones multi-tenant
(`tenants:migrate`) de stancl/tenancy. La personalización debe vivir en
**datos**, no en estructura.

**Alcance decidido con el usuario (2026-09-02)**:

- **UI**: completo desde la v1 — formularios de captura, vista de detalle,
  campos de fusión de `PlantillaDocumento`, columnas y filtros de listado,
  analítica operativa (ADR-013) y portal ciudadano (opt-in por campo).
- **Flujo**: un campo personalizado puede marcarse **obligatorio para una
  transición** de la máquina de estados.
- **Entidades**: todas las extensibles desde la v1 — Expediente, Predio,
  Solicitante, Acto Administrativo, Documento y las relaciones
  Expediente↔Solicitante, Expediente↔Empleado, Expediente↔Predio.
- **Fuera de alcance**: entidades personalizadas (tablas nuevas definidas por
  el usuario) — feature distinta y mucho mayor.

## Decisión

**Catálogo de definiciones + ubicaciones (tablas tenant-local) + valores en una
columna `custom_fields jsonb` sobre cada entidad extensible.** Sin EAV y sin
DDL en tiempo de ejecución. El esquema es idéntico en todos los tenants; solo
cambian las **filas** del catálogo y el contenido del JSONB.

### 1. Catálogo (tenant-local, creado por `tenants:migrate`)

**`custom_field_definitions`** — qué campo es:

| Campo | Notas |
|---|---|
| `key` | slug inmutable, único por `entity_type`; nombre máquina usado en el JSONB y la API |
| `label`, `help_text` | presentación |
| `entity_type` | **enum PHP cerrado**: `Expediente`, `Predio`, `Solicitante`, `ActoAdministrativo`, `Documento`, `ExpedienteSolicitante`, `ExpedienteEmpleado`, `ExpedientePredio`. El set de entidades extensibles es decisión de código, no dato de usuario. |
| `data_type` | **enum PHP**: `text`, `textarea`, `number`, `integer`, `boolean`, `date`, `datetime`, `select_single`, `select_multi`, `money`. (`computed`, `file_reference` → fase posterior.) |
| `config` jsonb | específico del tipo: opciones de select, min/max, decimales, prefijo/sufijo, máscara |
| `validation` jsonb | `required`, `unique` (dentro del tenant), regex, longitud… → se traduce a reglas de validación de Laravel en runtime |
| `default_value` jsonb | opcional |
| `is_active` bool | desactiva el campo sin borrar valores |
| `is_system` bool | definición semilla núcleo-común; el tenant no la borra |
| `reportable` bool | se proyecta a la analítica (§3) |
| `citizen_visible` bool | puerta dura para exponerlo en el portal ciudadano; **default false** |
| `sort_order` | |
| audit | `created_by`, `updated_by`, timestamps |

**`custom_field_placements`** — una definición → N ubicaciones. Aquí se cumple
"define el tipo **y** dónde se muestra":

| Campo | Notas |
|---|---|
| `definition_id` | FK |
| `surface` | **enum PHP cerrado** = puntos de extensión que la app expone a propósito (lista abajo) |
| `section` | slug de agrupación dentro de la surface |
| `sort_order` | |
| `mode` | enum: `editable` / `readonly` / `hidden_unless_set` |
| `required_here` bool | el campo puede ser opcional en general y obligatorio en esta pantalla/transición |
| `config` jsonb | datos propios de la surface (ej. lista de transiciones que exige el campo) |
| `visibility` jsonb | condición simple v1 (`campo op valor`, combinadas con AND); puede referirse a campos propios de la entidad, a clase/modalidad/objeto del tipo de trámite y al estado actual del expediente |

**Surfaces (enum, v1):**

- Captura/detalle: `expediente.radicacion_form`, `expediente.detail.<section>`,
  `predio.form`, `solicitante.form`, `acto.form`, `documento.form`
- Relaciones: `expediente_solicitante.form`, `expediente_empleado.form`,
  `expediente_predio.form`
- Documentos: `acto.merge_fields`, `documento.merge_fields`
- Listados: `list.<entity>.columns`, `list.<entity>.filters`
- Analítica: `report.<entity>`
- Portal ciudadano: `portal.expediente_seguimiento` (lectura),
  `portal.radicacion_online` (captura, ADR-006)
- Flujo: `workflow.transition_requirement` (§6)

### 2. Valores

- Columna `custom_fields jsonb not null default '{}'` en `expedientes`,
  `predios`, `solicitantes`, `actos_administrativos`, `documentos` y en las
  tablas pivote de las tres relaciones. Clave del objeto = `key` de la
  definición.
- **Mecanismo**: cast JSONB propio y delgado (sin dependencia nueva; coherente
  con la conservación de dependencias de ADR-011). `spatie/laravel-schemaless-
  attributes` queda como alternativa si el cast propio genera fricción real.
- **Escritura únicamente por acciones de dominio** (ADR-011): un value object
  `SetCustomFieldValues` invocado **dentro** de la acción relevante
  (`FileApplication`, `UpdateExpediente`, `RegisterPredio`, …), nunca un
  endpoint genérico suelto. La validación se construye en runtime a partir de
  las definiciones activas + `required_here` de la placement en juego.
- Cada cambio emite un evento de dominio → audit log (ADR-011) → aparece en la
  línea de tiempo de actividad del expediente estilo Jira/ClickUp
  (`docs/dominio/expediente.md`). El legado no dejaba rastro de estas capturas.

### 3. Consulta, listados, filtros y analítica

- Índice **GIN** (`jsonb_path_ops`) sobre cada columna `custom_fields`.
- Campos marcados `reportable` o usados en `list.*.filters`: **índice B-tree de
  expresión** `((custom_fields->>'key'))` creado por una migración **normal y
  uniforme** (igual en todos los tenants). Nunca DDL en runtime sobre las
  tablas transaccionales.
- Lecturas: **query scopes** (`->whereCustomField('altura_maxima', '>', 12)`) y
  **query classes** dedicadas para listados multi-criterio (ADR-011).
- Analítica (ADR-013): **vistas materializadas / tablas resumen** que proyectan
  las claves `reportable` a columnas tipadas, refrescadas por schedule.
  Reporte rápido sin acoplar el esquema transaccional a las variables de cada
  curaduría.

### 4. Type safety

- `entity_type`, `data_type`, `surface`, `mode` son **enums PHP** →
  analizables por Larastan. No hay columnas dinámicas: el análisis estático y
  los contratos tipados de ADR-011 se mantienen intactos.
- DTOs tipados: `ResolvedCustomField`, `CustomFieldValue` (unión discriminada
  por `data_type`), `CustomFieldSchema` (por entidad + surface).
- Frontend: contrato TS generado desde el catálogo; componente
  `<CustomFieldRenderer>` con un subcomponente por `data_type`; store Pinia que
  cachea el schema por tenant.
- Las API Resource incluyen un bloque `custom_fields` ya resuelto (definición +
  valor + placement aplicable a esa vista).

### 5. Multi-tenant y ciclo de vida

- Catálogo y valores 100% tenant-local → viajan dentro del dump de offboarding
  (ADR-010) sin tratamiento especial.
- Semilla de definiciones núcleo-común (`is_system = true`) en el provisioning
  del tenant (ADR-010); cada curaduría agrega/desactiva las suyas.
- **La central no tiene custom fields.** Los de Solicitante viven en la copia
  tenant-local de Solicitante, no en la identidad central — coherente con el
  amendment de ADR-005 del 2026-09-02.
- Test de aislamiento obligatorio (ADR-011): definiciones y valores del tenant
  A jamás visibles desde B.

### 6. Participación en el flujo del trámite

- Placement con `surface = workflow.transition_requirement`; en su `config`, la
  o las transiciones de la máquina de estados (`docs/dominio/flujo-tramite.md`)
  que el campo condiciona.
- Se evalúa como **invariante de dominio en el guard de la transición**
  (spatie/laravel-model-states, ADR-011), no en el controlador. Si falta el
  valor, la transición lanza `MissingRequiredCustomFieldException` → 422, con
  el mismo tratamiento que cualquier otra invariante de negocio.
- `visibility` permite condicionar el requisito (ej. "obligatorio para salir de
  Consolidación solo si la clase es Construcción").

### 7. Relación con mecanismos configurables ya existentes

Custom fields es un mecanismo **hermano** de los catálogos configurables ya
decididos, no su reemplazo:

- **`PlantillaDocumento`**: los `key` con placement `*.merge_fields` quedan
  disponibles como campos de fusión (`{{ cf.altura_maxima }}`). Dato propio de
  la curaduría fluye a documentos propios de la curaduría, sin código.
- **Máquina de estados**, **estados de tránsito/custodia**, **clases/
  modalidades activables por tenant**, **`TipoDocumentoGenerado`**, **catálogo
  autogestionado de barrio/comuna/localidad**: siguen siendo catálogos propios
  y no se fusionan con custom fields.

### 8. Gobernanza

- Permiso RBAC `custom_fields.manage` (admin de tenant / curador). El detalle
  fino de roles engancha con la pregunta abierta de RBAC
  (`docs/preguntas-abiertas.md`).
- Guardas: `key` inmutable tras crearse; no se cambia `data_type` con valores
  existentes (se crea un campo nuevo y se migra); borrar una definición con
  datos exige confirmación explícita.
- Tope blando de campos por entidad, para evitar abuso y degradación.

## Alternativas descartadas

- **EAV (entity-attribute-value)**: tabla de valores polimórfica con join por
  cada campo. La app es intensiva en lectura del agregado Expediente (línea de
  tiempo, pantalla por pantalla); EAV obliga a fan-out de joins y disciplina
  anti-N+1, y complica cargar el agregado. Su única ventaja real (columna de
  valor por tipo) es marginal frente a lo que ya resuelve JSONB indexado en
  PostgreSQL. Descartado.
- **Columnas reales por tenant vía `ALTER TABLE` en runtime**: técnicamente
  posible porque cada tenant tiene su base física. Se descarta porque
  **recrea exactamente la divergencia del legado** —ahora en el esquema en vez
  de en 5 codebases—, pelea con el modelo uniforme de `tenants:migrate`, rompe
  el análisis estático y los contratos tipados (ADR-011), y tiene modos de
  fallo peligrosos (locks, migraciones parciales entre N bases). El caso donde
  brillaría (analítica) se cubre mejor con vistas materializadas que proyectan
  claves JSONB.
- **Motor de reglas/fórmulas completo para `visibility` en la v1**: YAGNI
  (ADR-011). Se arranca con una gramática mínima (`campo op valor`, AND) y se
  amplía cuando haya demanda concreta.

## Consecuencias

- Una curaduría puede modelar sus variables particulares sin despliegue de
  código y sin que su esquema difiera del de las demás — el problema de las 5
  versiones del legado desaparece por construcción.
- El esquema físico de todos los tenants se mantiene idéntico; las migraciones
  multi-tenant siguen siendo una sola secuencia.
- Costo asumido: una capa de resolución (definición + placement + valor →
  DTO/Resource) y su equivalente en el frontend; disciplina de índices de
  expresión para los campos que entran a listados/analítica; los custom fields
  no tienen integridad referencial a nivel de BD (el catálogo es la fuente de
  verdad, la app valida).
- La analítica (ADR-013) y el portal ciudadano dependen de este mecanismo para
  las columnas no-núcleo; se coordinan vía `reportable` y `citizen_visible`.
- Si una "variable particular" resulta ser común a todas las curadurías, se
  **gradúa** a atributo modelado del núcleo mediante una migración normal; los
  custom fields son siempre aditivos, nunca redefinen el núcleo.

## Implementación por fases (el modelo de este ADR se implementa completo, el build se escalona)

1. Catálogo (`custom_field_definitions`, `custom_field_placements`) + enums +
   DTOs + cast JSONB + acción de escritura + eventos/audit.
2. Superficies de captura y detalle (`*.form`, `expediente.detail.*`,
   relaciones) + `<CustomFieldRenderer>` + contrato TS.
3. Campos de fusión en `PlantillaDocumento`.
4. Listados: columnas y filtros + índices de expresión + query scopes.
5. `workflow.transition_requirement` en los guards de la máquina de estados.
6. Analítica (ADR-013): proyección de `reportable` a vistas materializadas.
7. Portal ciudadano: `portal.*` con puerta `citizen_visible` + modo lectura /
   captura online (ADR-006).

## Pendiente (ver `docs/preguntas-abiertas.md`)

- Roles concretos que pueden gestionar definiciones (engancha con el RBAC del
  tenant).
- Semántica exacta al borrar una definición con valores (conservar huérfanos /
  purgar / bloquear).
- Alcance final de la gramática de `visibility`.
- Set definitivo de `data_type` (si entran `computed` y `file_reference`, y
  cuándo).
- `glosario.md`: `Campo personalizado → Custom field`,
  `Definición de campo → Field definition`, `Ubicación → Placement`,
  `Superficie → Surface`.
