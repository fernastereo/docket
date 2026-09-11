# app/Domain

Capa de dominio, organizada **por contexto** (ADR-011), no por tipo técnico.
Cada contexto es autocontenido: sus propias Actions, Models, Events, Enums,
DataTransferObjects y Queries. No se crea un contexto hasta que hay algo real
que poner ahí (YAGNI). Ver `docs/estructura-repo.md` y `docs/dominio/`.
