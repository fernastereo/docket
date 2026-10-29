# CuraduriAPP — atajos para el entorno LOCAL (docker compose). `make` o `make help` lista todo.
#
# Solo cubre desarrollo local. Dev/prod los despliega el workflow de GitHub Actions
# (migrate y tenants:migrate corren allí, no a mano). Ver infra/README.md.
#
# Deliberadamente NO hay un target `down -v`: borra el volumen de Postgres y con él
# todas tus curadurías de prueba. Si de verdad lo necesitas, escríbelo a mano.

COMPOSE := docker compose -f infra/compose/docker-compose.local.yml --env-file infra/env/local.env
APP     := $(COMPOSE) exec app
DB      := docker exec docket-local-db-1 psql -U docket -d docket_central

.DEFAULT_GOAL := help
.PHONY: help up up-build stop down ps logs shell migrate db-test-create \
        test test-clean lint fix stan check tenant employee-password tenant-delete tenants tenant-dbs psql \
        front-install front front-watch test-front front-check

help: ## Lista los comandos disponibles
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# ---------- Entorno ----------

up: ## Levanta el stack local (sin reconstruir imágenes)
	$(COMPOSE) up -d

up-build: ## Levanta el stack local reconstruyendo imágenes (tras cambiar Dockerfile o dependencias)
	$(COMPOSE) up -d --build

stop: ## Detiene los contenedores (conserva los datos)
	$(COMPOSE) stop

down: ## Quita los contenedores (los volúmenes y datos se conservan)
	$(COMPOSE) down

ps: ## Estado de los contenedores
	$(COMPOSE) ps

logs: ## Logs en vivo (make logs S=app para un solo servicio)
	$(COMPOSE) logs -f --tail=100 $(S)

shell: ## Shell dentro del contenedor app
	$(APP) sh

# ---------- Base de datos ----------

migrate: ## Migra la base CENTRAL local (en dev/prod lo hace el deploy)
	$(APP) php artisan migrate --force

db-test-create: ## Crea la base docket_test (una sola vez; el volumen la conserva)
	$(DB) -c "CREATE DATABASE docket_test"

psql: ## Consola psql en la base central
	docker exec -it docket-local-db-1 psql -U docket -d docket_central

# ---------- Calidad (siempre en Postgres real, base docket_test — ADR-011) ----------

test: ## Pest (make test F=nombre filtra por test o archivo)
	$(APP) vendor/bin/pest $(if $(F),--filter="$(F)")

test-clean: ## Borra bases tenanttest_* huérfanas y vacía el catálogo de docket_test (tras interrumpir una corrida)
	@for d in $$(docker exec docket-local-db-1 psql -U docket -d docket_central -tAc "select datname from pg_database where datname like 'tenanttest\_%'"); do \
		echo "borrando $$d"; \
		docker exec docket-local-db-1 psql -U docket -d docket_central -qc "DROP DATABASE \"$$d\" WITH (FORCE)"; \
	done
	@docker exec docket-local-db-1 psql -U docket -d docket_test -qc "delete from tenants" 2>/dev/null || true

lint: ## Pint en modo verificación (no modifica)
	$(APP) vendor/bin/pint --test

fix: ## Pint: corrige el estilo
	$(APP) vendor/bin/pint

stan: ## Larastan
	$(APP) vendor/bin/phpstan analyse --memory-limit=1G --no-progress

check: lint stan test front-check ## Lo mismo que corre el CI: Pint + Larastan + Pest + typecheck y tests del frontend

# ---------- Frontend (Vue) ----------
# Corren con el Node de tu máquina (la imagen de la app no trae Node). El
# frontend compilado queda en docket/public/build, que Caddy sirve desde el
# bind-mount: tras `make front` basta recargar el navegador, sin reiniciar nada.

front-install: ## Instala las dependencias de npm (primera vez, o si cambió package.json)
	cd docket && npm ci

front: ## Compila el frontend (lo que sirve https://docket.test:8443)
	cd docket && npm run build

front-watch: ## Recompila el frontend al guardar un archivo (recarga el navegador a mano)
	cd docket && npm run build -- --watch

test-front: ## Vitest (tests del frontend)
	cd docket && npm test

front-check: ## Typecheck (vue-tsc) + Vitest, como el CI
	cd docket && npm run typecheck && npm test

# ---------- Curadurías (tenants) ----------

tenant: ## Alta de curaduría: make tenant NAME="Curaduría Urbana 1" SUB=cu1 [ACTIVE=1] [ADMIN_NAME="Ana Pérez" ADMIN_EMAIL=ana@cu1.co]
	@test -n "$(NAME)" -a -n "$(SUB)" || { echo 'Uso: make tenant NAME="Curaduría Urbana 1" SUB=cu1 [ACTIVE=1]'; exit 1; }
	$(APP) php artisan tenants:provision "$(NAME)" "$(SUB)" $(if $(ACTIVE),--active) $(if $(ADMIN_EMAIL),--admin-name="$(ADMIN_NAME)" --admin-email="$(ADMIN_EMAIL)")
	@echo "Recuerda: /etc/hosts no admite comodines -> 127.0.0.1 $(shell echo $(SUB) | tr A-Z a-z).docket.test"

employee-password: ## Fija la contraseña de un empleado: make employee-password SUB=cu1 EMAIL=ana@cu1.co
	@test -n "$(SUB)" -a -n "$(EMAIL)" || { echo 'Uso: make employee-password SUB=cu1 EMAIL=ana@cu1.co'; exit 1; }
	$(APP) php artisan employees:set-password "$(SUB)" "$(EMAIL)"

tenant-delete: ## Borra una curaduría Y su base física: make tenant-delete ID=<uuid>
	@test -n "$(ID)" || { echo 'Uso: make tenant-delete ID=<uuid>  (ids con: make tenants)'; exit 1; }
	$(APP) php artisan tinker --execute='App\Platform\Models\Tenant::findOrFail("$(ID)")->delete();'

tenants: ## Catálogo de curadurías: nombre, estado, base física y host
	$(DB) -c "select t.id, t.name, t.status, 'tenant' || t.id as database_name, d.domain from tenants t left join domains d on d.tenant_id = t.id order by t.created_at"

tenant-dbs: ## Bases físicas tenant* que existen en Postgres (compara con `make tenants` para ver huérfanas)
	$(DB) -tAc "select datname from pg_database where datname like 'tenant%' order by 1"
