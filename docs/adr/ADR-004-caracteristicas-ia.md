# ADR-004: Características de IA

**Estado**: Borrador (alcance por priorizar; ampliado 2026-08-31 — visión de
"copiloto en cada paso del proceso", confirmada por el usuario como
diferenciador prioritario)
**Fecha**: 2026-07-21

## Contexto

Diferenciador clave del nuevo sistema frente al legado y a competidores. El dominio
tiene casos de uso naturales para LLMs y RAG. Con el flujo del trámite ya
modelado (`docs/dominio/flujo-tramite.md`), la visión se concreta: la IA no
es una feature aislada, es un **copiloto presente en cada paso del proceso**
donde un humano hoy redacta, busca normativa o resume información a mano.

## Candidatos de funcionalidad

1. **Extracción de datos de documentos**: OCR + LLM sobre escrituras, certificados
   de tradición y libertad, cédulas y planos para prellenar formularios de
   radicación.
2. **Verificación de completitud**: asistente que revisa si la documentación
   radicada está completa según el tipo de licencia, antes de revisión humana.
3. **RAG normativo**: consulta en lenguaje natural sobre POT municipal, decretos
   y NSR-10. Arquitectura: colección compartida para normativa nacional +
   colección pgvector por tenant para normativa local (POT, circulares).
4. **Redacción asistida** de actas de observaciones y actos administrativos con
   plantillas legales. Dos funciones concretas identificadas al modelar
   `docs/dominio/documento.md` y `docs/dominio/acto-administrativo.md`:
   - **Fusión coherente**: al combinar el texto base de la plantilla
     (`PlantillaDocumento`) con los datos estructurados del expediente, la IA
     ayuda a que el resultado se lea como texto natural y coherente, no como
     un mail-merge mecánico de espacios en blanco.
   - **Revisión de lenguaje**: sobre el documento ya generado (editable o a
     punto de expedirse), la IA señala posibles fallas o inconsistencias de
     redacción (no de contenido legal) para que el usuario las identifique y
     corrija fácilmente antes de expedir.
5. **Cálculo asistido de expensas** y liquidaciones.
6. **Portal ciudadano** con seguimiento de trámite y chatbot de estado del
   expediente — de cara al ciudadano, responde en lenguaje natural sobre el
   **estado público** (`docs/dominio/flujo-tramite.md`), sin exponer nunca
   detalle interno (evita filtrar en qué "sección" o con qué encargado está
   el expediente).
7. **Asistente de redacción del Acta de Observaciones**: en el paso
   Consolidación (`docs/dominio/flujo-tramite.md`), el revisor anota
   hallazgos en lenguaje suelto/notas; la IA redacta el Acta formal a partir
   de esas notas, sobre el mismo mecanismo de plantilla+edición ya definido
   (`docs/dominio/documento.md`). El revisor siempre aprueba/edita antes de
   enviarla al solicitante — no se envía nada generado sin revisión humana.
8. **Triage de objeciones/recursos de vecinos**: cuando llegan varias sobre
   un mismo expediente (`docs/dominio/flujo-tramite.md`, sección
   "Comunicaciones a vecinos y objeciones/recursos"), la IA las agrupa por
   tema y resume los puntos repetidos, para que quien las estudie no lea
   cada una desde cero.

## Mapeo al flujo del trámite — dónde se activa cada función

| Paso del flujo (`docs/dominio/flujo-tramite.md`) | Función de IA | Quién la usa |
|---|---|---|
| Radicado | Extracción de datos de documentos (1), Verificación de completitud (2) | Solicitante / funcionario de ventanilla |
| En estudios (Jurídico/Arquitectónico/Estructural) | RAG normativo en contexto de revisión (3) — consulta in-situ del POT/NSR-10 aplicable, no un buscador aparte | Arquitecto/Jurídico/Ingeniero encargados |
| Consolidación → Acta de Observaciones | Redacción asistida del acta desde notas (7) | Arquitecto encargado |
| Comunicaciones a vecinos / objeciones-recursos | Triage y resumen de objeciones (8) | Equipo asignado |
| Redacción de Resolución | Fusión coherente + revisión de lenguaje (4) | Curador / equipo asignado |
| Cualquier punto (portal ciudadano) | Chatbot de estado (6) | Ciudadano |
| Auto de Viabilidad / liquidaciones | Cálculo asistido de expensas (5) | Arquitecto encargado |

## Principios

- La IA asiste, no decide: todo acto administrativo pasa por revisión humana.
- Los datos de un tenant nunca se usan para responder consultas de otro.
- Los embeddings de documentos de un tenant viven en su propia base (pgvector).

## Pendiente

- Priorización (¿cuál es el MVP de IA?).
- Proveedor de LLM y costos por trámite.
- Manejo de datos personales en prompts (Ley 1581 de 2012).
