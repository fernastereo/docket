# Glosario del dominio (español → inglés)

Lenguaje ubicuo del proyecto. El código se escribe en inglés (ADR-011); este
glosario fija la traducción **definitiva** de cada término del dominio. Una vez
un término entra aquí y al código, no se cambia.

> Estado: en construcción — bloque de modelo de dominio del núcleo avanzado
> (2026-08-27 a 2026-08-31: Solicitante, Predio, Expediente, Tipo de
> Trámite, Acto Administrativo, Documento; ver `docs/dominio/`). Las
> traducciones marcadas (?) son propuestas pendientes de confirmar — ninguna
> se ha dado aún por definitiva, incluidas las que surgieron en este bloque.

| Español (dominio) | Inglés (código) | Notas |
|---|---|---|
| Curaduría (el tenant) | Tenant / CuratorOffice (?) | En infra se usa "tenant"; para el concepto de negocio confirmar término. |
| Curador urbano | Curator (?) | |
| Trámite / Expediente | Case / Expedient / Filing (?) | Decidir: probablemente el agregado raíz. |
| Radicación | Filing (?) | Acto de radicar y su consecutivo oficial. |
| Radicado (número) | Filing number (?) | |
| Solicitante | Applicant | |
| Predio | Property / Parcel (?) | |
| Licencia urbanística | License | |
| Licencia de construcción | Building license / Construction license (?) | |
| Licencia de urbanización | Urbanization license (?) | |
| Parcelación | Land parceling (?) | |
| Subdivisión | Subdivision | |
| Acto administrativo | Administrative act | |
| Acta de observaciones | Observations report (?) | |
| Subsanar / Subsanación | Cure / Remediation (?) | |
| Desistimiento | Withdrawal / Desistance (?) | |
| Expensas | Fees / Curator fees (?) | |
| Liquidación (de expensas) | Fee settlement (?) | |
| Impuesto de delineación | Delineation tax (?) | |
| Visto bueno | Approval / Sign-off (?) | |
| Ventanilla | Front desk (?) | Canal de radicación presencial. |
| POT (Plan de Ordenamiento Territorial) | POT (se mantiene la sigla) | Término propio; no traducir. |
| NSR-10 | NSR-10 | Norma sismorresistente; no traducir. |
| SNR (Superintendencia de Notariado y Registro) | SNR | No traducir. |
| Curaduría cero | Curaduría Cero (se mantiene) | Figura propia del sistema colombiano. |
| Certificado de tradición y libertad | Property title certificate (?) | |
| Propiedad horizontal | Horizontal property regime (?) | Término legal colombiano. |
| Calidad en que actúa | Acting capacity (?) | Propietario, apoderado, representante legal... |
| Tercero | Party (?) | Reservado — concepto futuro, distinto de Solicitante; no usar todavía. |
| Representante legal | Legal representative (?) | Persona natural (Solicitante) que representa a una jurídica; relación con vigencia. |
| Empresa (persona jurídica) | Company (?) | Tipo de identidad central desde el amendment 2026-08-31 a ADR-005; ver `docs/dominio/solicitante.md`. |
| Nomenclatura (dirección) | Address numbering (?) | Componente de la dirección de un predio. |
| Catálogo autogestionado | Self-managed catalog (?) | Patrón para barrio/comuna/localidad/corregimiento en Predio: se selecciona si existe, se crea si no. |
| Equipo asignado | Assigned team (?) | Relación Expediente↔Empleado con rol y vigencia (arquitecto, ingeniero civil, abogado); curador aparte, aprueba al final. |
| Estado interno | Internal status (?) | Estado detallado del expediente, uso operativo de la curaduría. |
| Estado público | Public status (?) | Estado simplificado, derivado del interno, de cara al ciudadano. |
| Tipo de trámite | Filing type (?) | Reemplaza a "Licencia" como concepto — cubre licencias y Otras Actuaciones. |
| Clase (de trámite) | Filing class (?) | Urbanización, Parcelación, Subdivisión, Construcción, Intervención y ocupación del espacio público, Reconocimiento de existencia de edificaciones, Otras actuaciones. |
| Modalidad | Filing modality (?) | Subtipo dentro de una clase (ej. Construcción → Obra nueva, Ampliación...). |
| Objeto (del trámite) | Filing purpose (?) | Inicial / Modificación / Revalidación — dimensión distinta de clase/modalidad; no aplica a Otras Actuaciones. |
| Otras actuaciones | Other proceedings (?) | Clase de trámite vinculada al desarrollo de proyectos, no es una licencia en sí. |
| Reconocimiento de la existencia de edificaciones | Recognition of existing construction (?) | Clase de trámite propia, sin modalidades registradas. |
| Resolución | Resolution (?) | Forma que toman los actos administrativos de aprobación/negación/desistimiento. |
| Resolución aclaratoria | Clarifying resolution (?) | Acto administrativo que corrige error/ambigüedad de un acto propio ya expedido. |
| Documento vinculante | Binding document (?) | Documento generado durante el trámite que no es acto administrativo. |
| Documento radicado | Filed document (?) | Documento entregado por el solicitante. |
| Documento generado | Generated document (?) | Documento producido por la curaduría a partir de plantilla + fusión de datos. |
| Tipo de documento generado | Generated document type (?) | Catálogo configurable por curaduría (no lista fija en código). |
| Plantilla de documento | Document template (?) | Configurable y propia de cada curaduría; contiene campos de fusión. |
| Requisito documental | Document requirement (?) | Catálogo (pendiente) de documentos radicados esperados por tipo/modalidad de trámite. |
| ORIP (Oficina de Registro de Instrumentos Públicos) | ORIP (se mantiene la sigla) | No traducir — catálogo precargado, código+nombre de oficina; prefijo de la matrícula inmobiliaria. |
| Consecutivo (de matrícula inmobiliaria) | Sequence number (?) | Hasta 10 dígitos; junto con ORIP forma la llave de deduplicación de Predio. |
