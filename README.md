# CuraduriAPP

SaaS de gestión de trámites para Curadurías Urbanas en Colombia. Reemplaza un
sistema legado (VB6 + Access, ~20 años) por una plataforma web multi-tenant con
características de IA. Nombre clave del repo: **Docket**.

## Mapa del repo

| Carpeta | Qué |
|---|---|
| `docs/` | Decisiones (`adr/`), modelo de dominio (`dominio/`), seguridad (`seguridad/`), onboarding (`dev-guide.md`), preguntas abiertas |
| `docket/` | La plataforma — Laravel (API) + SPA Vue 3 / TypeScript |
| `infra/` | Docker, Caddy, compose dev/prod, scripts de aprovisionamiento — ver `infra/README.md` |
| `landing/` | Landing en producción (`curaduria.app`) |
| `.github/` | CI y deploy manual |

Estructura detallada: `docs/estructura-repo.md`.

## Stack

Laravel · Vue 3 + TypeScript + Pinia · PostgreSQL + pgvector · Redis · Docker ·
multi-tenancy con `stancl/tenancy` (database-per-tenant) · Caddy · DigitalOcean.
Ver `docs/adr/ADR-003` y `ADR-011`.

## Desarrollo

El entorno de dev corre en el droplet `docket-dev` (no en la laptop) para que
sea idéntico al de prod. Instrucciones: `infra/README.md`.

```bash
cd infra/compose
docker compose -f docker-compose.dev.yml --env-file ../env/dev.env up -d --build
```

## Estado

Planeación cerrada (ADR-001 a 017). Arrancando implementación: esqueleto +
infra de dev. Ver `CLAUDE.md` → "Estado actual".
