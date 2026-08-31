# Visión de producto — diferenciadores a explorar

**Estado**: Ideas. Los puntos 2, 3, 4, 7 y 8 fueron confirmados por el
usuario como prioritarios (2026-08-31) y **ya se promovieron a ADR**
(ADR-004 ampliado, ADR-012, ADR-013, ADR-014, ADR-015) — quedan enlazados
abajo. Los puntos 1, 5 y 6 siguen como ideas sin desarrollar, para refinarse
cuando arranque el desarrollo (ver `docs/preguntas-abiertas.md`, y la nota
de "Estado actual" en `CLAUDE.md`).
**Fecha**: 2026-08-31

El modelo de dominio (`docs/dominio/`) y el flujo del trámite
(`docs/dominio/flujo-tramite.md`) capturan fielmente el **proceso real** de
una curaduría — eso no cambia, es la base legal/operativa. Esta es la lista
de diferenciadores que pueden convertir la plataforma en algo que no se
parece al legado en absoluto, no solo en tecnología sino en experiencia y
alcance.

## 1. Widget embebible en la web propia de cada curaduría

Muchas curadurías ya tienen su propia página web (con su propio dominio,
hecha con cualquier proveedor). En vez de forzarlas a redirigir todo su
tráfico al subdominio de la plataforma, ofrecer un **widget embebible**
(script + web component, al estilo Calendly/Intercom/Stripe): la curaduría
pega una línea de código en su sitio y aparece "Radica tu trámite" /
"Consulta el estado de tu expediente" / un chat de estado — funcionando por
debajo contra su subdominio de tenant (ADR-002), sin que el ciudadano note
que cambió de sitio. Es low-effort para la curaduría adoptarlo (no touca su
web existente) y es un argumento de venta fuerte frente al legado, que no
tiene ninguna presencia web.

## 2. IA como copiloto en cada paso del proceso, no solo en documentos

**→ Promovido a ADR-004** (ampliado 2026-08-31, incluye mapeo al flujo del
trámite).

ADR-004 ya cubre extracción documental, verificación de completitud, RAG
normativo y redacción asistida de actos. Llevarlo más allá, ahora que el
flujo completo está modelado (`docs/dominio/flujo-tramite.md`):
- **Asistente de redacción de observaciones**: el revisor anota hallazgos en
  lenguaje suelto, la IA redacta el Acta de Observaciones formal.
- **RAG normativo en contexto de revisión**: durante el Estudio Jurídico o
  Arquitectónico, consulta in-situ del POT/NSR-10 aplicable, no un buscador
  aparte.
- **Triage de objeciones de vecinos**: si llegan varias objeciones sobre un
  mismo expediente, la IA las resume/agrupa por tema para quien las estudia.
- **Chat de estado para el ciudadano**: "¿en qué va mi trámite?" en lenguaje
  natural, explicando el estado público sin jerga legal.

## 3. Verificación pública de actos administrativos

**→ Promovido a `docs/adr/ADR-012-verificacion-publica-actos.md`.**

Cada Acto Administrativo expedido (`docs/dominio/acto-administrativo.md`)
lleva un **código/QR de verificación pública** — cualquiera con el PDF o
impreso en mano puede escanearlo y confirmar, sin login, que es auténtico
(expediente, curaduría, fecha, vigente/no alterado). Antifraude real
(actos administrativos con valor legal son objetivo natural de
falsificación) y una demostración muy visible de que la plataforma es seria.

## 4. Analítica operativa para la curaduría

**→ Promovido a `docs/adr/ADR-013-analitica-operativa.md`.**

El requisito de línea de tiempo estilo Jira/ClickUp
(`docs/dominio/expediente.md`) ya da la materia prima: un dashboard de
**cuellos de botella** (qué área/persona concentra más tiempo de los
expedientes, comparado contra los plazos legales de
`docs/dominio/flujo-tramite.md`), cumplimiento de SLA interno, carga de
trabajo por encargado. El legado (VB6+Access) no tiene nada de esto — es un
argumento de venta directo a un curador que hoy no puede ver esto de su
propia oficina.

## 5. API pública / marketplace de integraciones

Más allá del widget (punto 1): una API documentada y con llaves por tenant
para que terceros consulten (con el consentimiento/alcance adecuado) si un
predio tiene licencia vigente, o el estado de un expediente — útil para
portales inmobiliarios, notarías, gremios de arquitectos/constructores.
Convierte la plataforma en un ecosistema, no solo una herramienta interna.

## 6. Portal ciudadano como PWA con notificaciones push

Ya está decidido el portal ciudadano y la mensajería por Brevo (ADR-004,
ADR-008). Complementarlo con una **PWA instalable** (sin pasar por tiendas
de apps) con notificaciones push nativas para cambios de estado, y un QR en
el recibo de radicación físico (ADR-006) que lleva directo al seguimiento en
línea del expediente.

## 7. Alertas predictivas de plazos, no solo reactivas

**→ Promovido a `docs/adr/ADR-014-gestion-plazos-alertas.md`** — marcado
**imprescindible** por el usuario, no opcional.

El manejo de términos (`docs/dominio/flujo-tramite.md`) ya identifica varios
plazos críticos en días hábiles. En vez de solo marcar cuando un plazo ya
se venció, alertar **antes** (ej. "quedan 5 días hábiles del término
general y el expediente sigue en Estudio Estructural") — reduce
desistimientos evitables y presión de última hora sobre el equipo.

## 8. Firma electrónica del curador con trazabilidad completa

**→ Promovido a `docs/adr/ADR-015-firma-electronica.md`.**

Ya identificado como pregunta abierta (`docs/preguntas-abiertas.md`,
"Firma de actos administrativos"). Resolverlo con un proveedor colombiano
de firma electrónica/digital, integrado al flujo de expedición
(`docs/dominio/flujo-tramite.md`, paso "Expedida"), con el mismo mecanismo
de auditoría ya decidido (ADR-011) — el curador puede firmar desde
cualquier lugar, con trazabilidad legal completa del acto de firmar.

## Cómo se va a trabajar esto

No se diseñan a fondo ahora. Se retoman y refinan **una vez arranque el
desarrollo**, con el criterio ya fijado de no replicar el legado por
defecto y proponer activamente el patrón moderno — ver memoria de trabajo
del proyecto. Nuevas ideas que surjan durante el desarrollo se agregan aquí.
