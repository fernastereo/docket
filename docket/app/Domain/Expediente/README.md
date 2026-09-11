# Domain/Expediente

Agregado raíz del trámite y su máquina de estados.
Ver `docs/dominio/expediente.md` y `docs/dominio/flujo-tramite.md`.
Pendiente — fase "migraciones del núcleo" / "máquina de estados" (CLAUDE.md).

- `Actions/`   acciones de dominio (única vía de escritura, ADR-011 punto 1)
- `States/`    transiciones de la máquina de estados (spatie/laravel-model-states)
- `Events/`    eventos de dominio → audit log (ADR-011 punto 6)
- `Enums/`     catálogos cerrados (ADR-011 punto 7)
- `DataTransferObjects/`  DTOs tipados entre Request y Action
- `Queries/`   query classes para lecturas complejas (ADR-011 punto 8)
- `Models/`    Eloquent
