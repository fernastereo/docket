# Preguntas abiertas

Lista viva. Al resolverse, mover la decisión a un ADR.

## Arquitectura / plataforma
- [ ] Escaneo antivirus y límites (tamaño/tipos) de documentos subidos.
- [ ] Preferencias de canal de notificación por usuario y catálogo de eventos
      que notifican.
- [ ] Estrategia de deduplicación/colisiones de identidad de **solicitante**
      (mismo documento, email distinto; también cambio de tipo de documento,
      ej. CE→cédula) — flujo de verificación/fusión. Ya no aplica a empleados:
      su cuenta es local al tenant (ADR-005 amendment 2026-09-02).
- [ ] SSO de empleado entre curadurías (si en el futuro un empleado
      multi-curaduría pide login único): se resolvería con un IdP por encima
      de las cuentas locales, sin reintroducir identidad de empleado en la
      central (ADR-005 amendment 2026-09-02). No urgente.
- [ ] Firma de actos administrativos: patrón de integración definido en
      `docs/adr/ADR-015-firma-electronica.md` (interfaz + proveedor
      colombiano externo); falta elegir proveedor concreto (Certicámara,
      GSE u otro).
- [ ] Cumplimiento Ley 1581/2012 (datos personales): política, avisos,
      tratamiento en prompts de IA.
- [ ] Campos personalizados por tenant (ADR-016): **modelo cerrado**, quedan
      detalles — roles que pueden gestionar definiciones (engancha con RBAC del
      tenant), semántica al borrar una definición con valores (huérfanos /
      purgar / bloquear), alcance final de la gramática de `visibility`, y set
      definitivo de `data_type` (si entran `computed` / `file_reference`).

## Dominio (capturar del conocimiento del sistema legado)

- [x] **Modelo de datos del núcleo** (2026-08-27 a 2026-08-31): resuelto en
      `docs/dominio/solicitante.md`, `predio.md`, `expediente.md`,
      `tipo-tramite.md` (reemplaza a "licencia"), `acto-administrativo.md`,
      `documento.md`. Incluye amendment 2026-08-27 a ADR-005 (identidad de
      persona jurídica). Radicación quedó colapsada como atributos de
      Expediente, no como entidad propia.
- [ ] Catálogo de clases/modalidades de trámite: **resuelto** a nivel de
      lista (`docs/dominio/tipo-tramite.md`); **falta** el mecanismo de
      configuración por tenant para activar/desactivar clases según tipo de
      autoridad (curaduría vs. Secretaría de Planeación).
- [ ] Requisitos documentales por clase/modalidad de trámite (catálogo de
      documentos radicados esperados) — no abordado todavía.
- [ ] Flujo completo de un trámite: catálogo de estados internos y su mapeo
      a estados públicos, plazos legales, vistos buenos. **SIGUIENTE
      BLOQUE.** Debe considerar el requisito de línea de tiempo de
      actividad por expediente (estilo Jira/ClickUp) ya fijado en
      `docs/dominio/expediente.md`.
- [ ] Mecanismo de asociación entre un acto administrativo y los tipos de
      trámite que cubre, cuando una curaduría expide más de uno por
      expediente (`docs/dominio/acto-administrativo.md`).
- [ ] Mecanismo de deduplicación/coincidencia de predios entre expedientes
      (por matrícula inmobiliaria u otro criterio) — `docs/dominio/predio.md`.
- [ ] Si vereda/sector/manzana deben seguir el mismo patrón de catálogo
      autogestionado que barrio/comuna/localidad/corregimiento — `docs/
      dominio/predio.md`.
- [ ] Gestión del catálogo `TipoDocumentoGenerado` (quién puede crear/editar
      tipos de documento dentro de una curaduría) — `docs/dominio/documento.md`.
- [ ] Estudio técnico de edición de texto enriquecido para plantillas
      (reemplazo del enfoque Word del legado) — bloque de implementación.
- [ ] Reglas de liquidación de expensas (¿varían por municipio/tarifa?).
      Punto de partida: el procedimiento de cálculo está reglado en el Decreto
      1077/2015 dentro del rango de arts. 2.2.6.1.1.1 a 2.2.6.6.9.2 — extraer
      de ahí la fórmula de cargos fijos y variables antes de modelar tarifas.
- [ ] Reportes obligatorios (SNR, municipio, curaduría cero, DANE si aplica).
- [ ] Numeración/radicación oficial: formato, consecutivos, valor legal.
- [ ] Roles reales de una curaduría, más allá de arquitecto/ingeniero civil/
      abogado/curador ya fijados como equipo asignado del expediente (para
      el RBAC del tenant).
- [ ] Completar/confirmar el glosario español→inglés
      (`docs/dominio/glosario.md`) — se agregaron ~20 términos nuevos
      durante este bloque, todos aún marcados `(?)`, ninguno confirmado
      como definitivo.

## Diferenciadores de producto (ver `docs/vision-producto.md`)

- [ ] ADR-012 (verificación pública): formato del código de verificación,
      si vive en subdominio del tenant o dominio neutral.
- [ ] ADR-013 (analítica operativa): mecanismo técnico de agregación
      (vistas materializadas vs. tablas de resumen), catálogo completo de
      métricas.
- [ ] ADR-014 (plazos y alertas — **imprescindible**): umbrales exactos de
      alerta, fuente del catálogo de festivos colombianos, si el umbral es
      configurable por curaduría.
- [ ] ADR-015 (firma electrónica): elegir proveedor concreto (Certicámara,
      GSE u otro), firma simple vs. digital certificada, contingencia si el
      proveedor no está disponible.
- [ ] Ideas 1 (widget embebible), 5 (API/marketplace) y 6 (portal PWA) de
      `docs/vision-producto.md` — sin desarrollar todavía, no urgentes.

## Migración del legado
- [ ] Inventario del esquema Access actual (tablas, relaciones, rarezas).
- [ ] Estrategia por curaduría (.mdb → PostgreSQL del tenant).
- [ ] ¿Todo el histórico o solo activos + archivo consultable?
- [ ] Periodo de convivencia legado/nuevo.

## IA
- [ ] MVP de IA: ¿cuál funcionalidad primero?
- [ ] Proveedor LLM y costos por trámite.
- [ ] Fuentes del RAG normativo y proceso de actualización de normas.

## Negocio
- [ ] Precio de suscripción y modelo (¿por trámite, por usuario, plano?).
      Infraestructura dedicada como plan premium (ADR-009).
- [ ] SLA y contrato tipo (backups, suspensión, entrega de datos, retención).
- [ ] Estrategia de venta a curadurías nuevas (~50-60 curadores en el país).
- [x] Nombre del producto: **CuraduriAPP** (2026-08-31, dominio ya
      registrado). Nombre clave del repo, distinto: Docket.

## Operativo (no urgente)
- [ ] Tramitar verificación Meta/WhatsApp y plantillas en Brevo (toma tiempo).
- [ ] SPF/DKIM del dominio.
- [ ] Observabilidad: logs etiquetados por tenant, monitoreo del cluster.
