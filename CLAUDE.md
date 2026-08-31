# Proyecto: SaaS para Curadurías Urbanas (nombre provisional)

## Contexto

Modernización de un software de gestión de trámites para Curadurías Urbanas en
Colombia. El sistema legado (~20 años, VB6 + Access) sigue en uso activo en 5
curadurías. El nuevo sistema es una plataforma web SaaS con características de
IA. El autor del proyecto es también el autor del legado: el conocimiento de
dominio es profundo y debe capturarse en docs/dominio/ a medida que se defina.

## Dominio

Las Curadurías Urbanas son oficinas privadas que ejercen función pública:
estudian, tramitan y expiden licencias urbanísticas (urbanización, construcción,
parcelación, subdivisión, etc.). Marco legal: Ley 388/1997, Ley 1796/2016,
Decreto 1077/2015. Vigiladas por la Superintendencia de Notariado y Registro.
Las licencias son actos administrativos: trazabilidad y valor legal del
expediente son críticos.

## Decisiones tomadas (ver docs/adr/)

- **ADR-001 — Modelo SaaS**: suscripción; el proveedor opera la plataforma.
- **ADR-002 — Database-per-tenant**: una app desplegada; base PostgreSQL física
  independiente por curaduría; base central solo con catálogo de tenants e
  identidades; resolución de tenant por subdominio.
- **ADR-003 — Stack**: **Laravel** (API) + **Vue.js** (SPA, Sanctum) +
  **PostgreSQL** (+pgvector) + Redis + Docker. Multi-tenancy con
  **stancl/tenancy**.
- **ADR-004 — IA** (borrador): extracción documental OCR+LLM, verificación de
  completitud, RAG normativo (nacional compartido + local por tenant en
  pgvector), redacción asistida de actos.
- **ADR-005 — Identidad y membresías**: identidad única en la central (UUID,
  documento de identidad como llave natural; ciudadanos y empleados);
  membresía + RBAC en la base de cada tenant. La central nunca revela en qué
  curadurías tiene trámites una persona.
- **ADR-006 — Enrolamiento**: radicación en línea desde el MVP; todo
  solicitante es usuario; radicación por ventanilla crea cuenta pendiente de
  activación con token de un solo uso (email / SMS-WhatsApp / código impreso).
- **ADR-007 — Archivos**: S3-compatible (DO Spaces en prod, MinIO en dev) vía
  Flysystem; prefijo/bucket por tenant; cifrado en reposo; offboarding incluye
  archivos.
- **ADR-008 — Mensajería**: Brevo (email/SMS/WhatsApp); envíos por cola
  tenant-aware; WhatsApp requiere plantillas aprobadas por Meta (tramitar
  temprano); SPF/DKIM.
- **ADR-009 — Infraestructura**: DigitalOcean; **DO Managed PostgreSQL** (un
  cluster, N bases); droplet de app con Docker Compose (app + workers +
  Redis); DNS/TLS wildcard; pg_dump por tenant hacia Spaces (feature
  vendible). Descartado contenedor o droplet por tenant (sobre-ingeniería;
  queda como palanca futura para tenants premium).
- **ADR-010 — Ciclo de vida del tenant**: demo / activo / suspendido
  (solo-lectura, nunca bloqueo total) / terminado (entrega dump + archivos +
  snapshot de identidades). Provisioning = un comando.

- **ADR-012 — Verificación pública de actos administrativos** (borrador):
  código/QR de verificación sin login, hash de integridad del documento.
- **ADR-013 — Analítica operativa** (borrador): BI por curaduría sobre el
  audit log (cuellos de botella, cumplimiento de plazos, carga por
  encargado).
- **ADR-014 — Gestión de plazos legales y alertas predictivas** (borrador,
  **imprescindible**): servicio central de días hábiles colombianos con
  suspensión/reanudación/prórroga, alertas antes de vencer un plazo.
- **ADR-015 — Firma electrónica de actos administrativos** (borrador):
  interfaz + proveedor colombiano externo (por elegir), firma sobre
  documento congelado, integrada con ADR-012.

- **ADR-011 — Principios de código**: Laravel idiomático + capa de dominio:
  acciones de dominio como única vía de escritura, controladores delgados
  (Form Request → Policy → Action con DTO → API Resource, cero lógica de
  negocio), máquina de estados para el expediente, eventos→audit, enums,
  sin repositorio genérico sobre Eloquent (query scopes/classes para
  lecturas), interfaces solo en fronteras (Brevo, SNR, firma, LLM, PDFs),
  SOLID pragmático + YAGNI. Pint + Larastan + Pest (tests obligatorios en
  transiciones, expensas, numeración y aislamiento de tenants). Vue 3
  Composition API + **TypeScript** + Pinia. **Código en inglés** con
  glosario de dominio en docs/dominio/glosario.md.

## Principios del proyecto

1. Aislamiento de datos de negocio por curaduría es innegociable; la central
   solo contiene catálogo de tenants e identidades de acceso.
2. Trazabilidad legal: audit log completo desde el día uno.
3. Provisioning y migraciones multi-tenant automatizados (stancl/tenancy:
   `tenants:migrate`), con manejo de fallos parciales.
4. Ningún job se despacha fuera de contexto de tenant (salvo los de
   plataforma).
5. La IA asiste, no decide: todo acto administrativo pasa por revisión humana.
6. Migración del legado 1:1 — cada curaduría tiene su propio .mdb → su
   PostgreSQL.

## Estado actual

Planeación. Cuestiones generales de arquitectura y principios de código
CERRADOS (ADR-001 a 011; ADR-005 con amendment 2026-08-31 — identidad de
persona jurídica). **Modelo de dominio del núcleo CERRADO** (2026-08-27 a
2026-08-31): Solicitante, Predio, Expediente, Tipo de Trámite (reemplaza a
"Licencia" — incluye Otras Actuaciones), Acto Administrativo, Documento —
ver `docs/dominio/`. Radicación quedó como atributos de Expediente, no como
entidad propia. **Flujo del trámite / máquina de estados CERRADO**
(2026-08-31): `docs/dominio/flujo-tramite.md`, núcleo común contrastado
contra el listado real de estados de una curaduría, configurable por
curaduría. Nombre de producto: **CuraduriAPP** (decidido 2026-08-31, dominio
ya registrado). Nombre clave del repo (distinto, solo interno): **Docket**.

**Diferenciadores de producto en borrador** (ADR-004 ampliado, ADR-012 a
015 — ver `docs/vision-producto.md`): IA copiloto en cada paso del flujo,
verificación pública de actos, analítica operativa, gestión de plazos con
alertas predictivas (imprescindible), firma electrónica.

Siguiente bloque: por definir con el usuario — candidatos abiertos incluyen
requisitos documentales por tipo de trámite, roles/RBAC completo,
liquidación de expensas, o empezar el esqueleto técnico del repo en
paralelo. Ver `docs/preguntas-abiertas.md` para el detalle completo de
pendientes abiertos.

**Principio de trabajo para lo que sigue**: al capturar conocimiento del
legado, no asumir que su diseño (VB6+Access, 20 años) es el patrón a
replicar — extraer el hecho de negocio y proponer proactivamente el patrón
moderno equivalente, cuestionando el legado por defecto.

## Convenciones de documentación

- Decisiones: docs/adr/ADR-NNN-titulo.md (Contexto, Decisión, Consecuencias,
  Estado). Dominio: docs/dominio/. Pendientes: docs/preguntas-abiertas.md.
- Al tomar una decisión nueva: crear/actualizar el ADR y reflejarla aquí si es
  estructural. Idioma: español.
