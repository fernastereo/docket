# ADR-004: Características de IA

**Estado**: Borrador (alcance por priorizar)
**Fecha**: 2026-07-21

## Contexto

Diferenciador clave del nuevo sistema frente al legado y a competidores. El dominio
tiene casos de uso naturales para LLMs y RAG.

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
   expediente.

## Principios

- La IA asiste, no decide: todo acto administrativo pasa por revisión humana.
- Los datos de un tenant nunca se usan para responder consultas de otro.
- Los embeddings de documentos de un tenant viven en su propia base (pgvector).

## Pendiente

- Priorización (¿cuál es el MVP de IA?).
- Proveedor de LLM y costos por trámite.
- Manejo de datos personales en prompts (Ley 1581 de 2012).
