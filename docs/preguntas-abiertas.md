# Preguntas abiertas

Lista viva. Al resolverse, mover la decisión a un ADR.

## Arquitectura / plataforma
- [ ] Escaneo antivirus y límites (tamaño/tipos) de documentos subidos.
- [ ] Preferencias de canal de notificación por usuario y catálogo de eventos
      que notifican.
- [ ] Estrategia de deduplicación/colisiones de identidad (mismo documento,
      email distinto) — flujo de verificación.
- [ ] Firma de actos administrativos: ¿firma electrónica/digital? ¿proveedor
      colombiano (ej. certicámara, GSE)?
- [ ] Cumplimiento Ley 1581/2012 (datos personales): política, avisos,
      tratamiento en prompts de IA.

## Dominio (capturar del conocimiento del sistema legado) — SIGUIENTE BLOQUE
- [ ] Modelo de datos del núcleo: expediente, radicación, solicitante, predio,
      licencia, acto administrativo, documento.
- [ ] Flujo completo de un trámite: estados, plazos legales, vistos buenos.
- [ ] Tipos de licencia y modalidades, y sus requisitos documentales.
- [ ] Reglas de liquidación de expensas (¿varían por municipio/tarifa?).
- [ ] Reportes obligatorios (SNR, municipio, curaduría cero, DANE si aplica).
- [ ] Numeración/radicación oficial: formato, consecutivos, valor legal.
- [ ] Roles reales de una curaduría (para el RBAC del tenant).
- [ ] Completar/confirmar el glosario español→inglés (docs/dominio/glosario.md).

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
- [ ] Nombre del producto.

## Operativo (no urgente)
- [ ] Tramitar verificación Meta/WhatsApp y plantillas en Brevo (toma tiempo).
- [ ] SPF/DKIM del dominio.
- [ ] Observabilidad: logs etiquetados por tenant, monitoreo del cluster.
