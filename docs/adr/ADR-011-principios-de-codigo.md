# ADR-011: Principios de código y arquitectura de aplicación

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

Definir el paradigma y las convenciones de código antes del primer commit.
Se evaluó el espectro entre Laravel "vanilla" (lógica en controladores/modelos)
y DDD/hexagonal completo, y se discutió el patrón servicio-repositorio y la
aplicación de SOLID en un equipo de una persona con framework opinado.

## Decisión

**Laravel idiomático con capa de dominio explícita** (OOP). Backend en PHP
8.3+, frontend Vue 3 con **TypeScript**. **Código en inglés** en su totalidad
(clases, variables, tablas, rutas); el lenguaje ubicuo del dominio se documenta
en `docs/dominio/glosario.md` (español → inglés) y la traducción elegida para
cada término es definitiva.

### Backend

1. **Acciones de dominio como única vía de escritura**: cada operación de
   negocio es una clase de propósito único (`FileApplication`, `IssueLicense`,
   `SettleFees`...). Nada crea o muta entidades de negocio por fuera de ellas.
2. **Controladores delgados** (adaptadores HTTP). Su anatomía exacta:
   - Reciben la petición validada en *forma* por un **Form Request**
     (campos, formatos, tipos).
   - **Autorizan** invocando la Policy correspondiente (la regla vive en la
     Policy, no en el controlador).
   - **Invocan la acción de dominio** con datos limpios, idealmente como DTO
     tipado (no el Request crudo).
   - **Traducen el resultado a HTTP**: API Resource, código de estado, y mapeo
     de excepciones de dominio a respuestas (ej.
     `IllegalStateTransitionException` → 422).
   - **Cero lógica de negocio.**
3. **Prueba de fuego permanente**: toda operación de negocio debe poder
   ejecutarse completa sin que exista un request HTTP (desde Artisan, jobs o
   tests), con el mismo audit y las mismas validaciones.
4. **Validación de forma vs. de negocio**: la de forma en Form Requests; las
   invariantes del negocio ("no puede expedirse con observaciones sin
   subsanar") en el dominio, y se ejecutan siempre, venga la orden de donde
   venga.
5. **Máquina de estados explícita** para el ciclo de vida del expediente
   (spatie/laravel-model-states o propia). Las transiciones son código de
   primera clase; cada transición emite su evento.
6. **Eventos de dominio → audit log**: el audit del tenant se deriva de
   eventos vía listeners, nunca se escribe "a mano" en cada lugar.
7. **Enums nativos de PHP** para catálogos cerrados (tipos de licencia,
   modalidades, roles, estados).
8. **Sin patrón repositorio genérico sobre Eloquent.** Razones: Eloquent es
   Active Record (el repositorio encima resulta en passthrough que no abstrae
   nada real); la BD no va a cambiar (ADR-003/009) y el multi-tenant de
   conexiones lo maneja stancl/tenancy; los tests van contra base real
   (`RefreshDatabase`), no mockeando persistencia. Para lecturas:
   - **Query scopes** para filtros reutilizables.
   - **Query classes dedicadas** para consultas genuinamente complejas
     (dashboards, reportes SNR, búsquedas multi-criterio).
9. **Campos personalizados por tenant** (ADR-016): catálogo tenant-local de
   definiciones/ubicaciones + valores en columna `custom_fields jsonb` sobre
   las entidades extensibles. **No contradice** este ADR: los metatipos
   (`entity_type`, `data_type`, `surface`, `mode`) son enums PHP (punto 7),
   el esquema físico es idéntico en todos los tenants (nada de DDL en runtime),
   la escritura pasa por acciones de dominio (punto 1) y cada cambio emite
   evento → audit (punto 6). Nunca se usa `ALTER TABLE` por tenant para esto.

### SOLID pragmático

- **SRP estricto siempre** (acciones, controladores delgados, query classes):
  el principio con mejor relación costo/beneficio.
- **Interfaces solo en las fronteras del sistema**, donde la implementación
  puede variar o debe fakearse en tests: mensajería (Brevo), integraciones
  externas (SNR, catastro, firma digital), proveedor de LLM/IA, generación de
  PDFs. Implementación real + fake de test por cada una.
- **Dentro del sistema, concreciones directas** (Eloquent, acciones, eventos).
  Sin interfaces "por si acaso".
- **YAGNI arquitectónico**: si un componente interno demuestra necesitar
  abstracción (segunda implementación real o dolor concreto en tests), se
  introduce en ese momento, localmente.

### Calidad automatizada (desde el commit uno)

- **Pint** (formato), **Larastan/PHPStan** (análisis estático), **Pest**
  (tests).
- Cobertura pragmática, pero **tests obligatorios** para lo que tiene
  consecuencias legales o de dinero: transiciones de estado, liquidación de
  expensas, numeración de radicados, y **aislamiento de tenants** (tests que
  verifican que el tenant A jamás ve datos del B). Un test de aislamiento en
  rojo **bloquea el deploy** (ADR-017).
- **Scanners de seguridad en CI** (ADR-017, capa 7): `composer audit`,
  `npm audit --audit-level=high`, secret scanning (gitleaks), SAST (CodeQL o
  Semgrep) y Trivy sobre la imagen. Un fallo bloquea el merge/deploy.
- **Seguro por defecto**: cabeceras de seguridad, CORS estricto, rate limiting
  y `APP_DEBUG=false` en prod son parte del esqueleto, no un añadido posterior
  (detalle en ADR-017).

### Frontend

- Vue 3 **Composition API** con `<script setup>` + **TypeScript** + **Pinia**.
- **Interfaz** (amendment 2026-10-18): Tailwind 4 con los tokens de marca de la
  landing, componentes propios sobre **Reka UI** al estilo de shadcn-vue, una
  sola apariencia para todos los tipos de usuario, claro/oscuro. Los
  componentes usan tokens semánticos, nunca colores de paleta sueltos. Tests
  del frontend con **Vitest** + Vue Test Utils (corren en el CI). Detalle en
  `docs/diseno-ui.md`.
- Contratos de la API tipados (previene bugs de integración y rinde mejor con
  agentes de código).

## Consecuencias

- El patrón por endpoint es mecánico y uniforme (Request → Controller →
  Action → Resource): navegable para humanos y óptimo para generación asistida
  con Claude Code.
- Las acciones de dominio son reutilizables en la migración masiva desde
  Access (comandos Artisan) con audit y validaciones idénticos.
- Costo asumido: disciplina de mantener el glosario y los DTOs al día.

## Amendments

### 2026-10-12 — Todos los tests corren contra PostgreSQL real

Los tests de provisioning y aislamiento de tenants crean **bases físicas**, lo
que SQLite no puede reproducir (y PostgreSQL se comporta distinto en `jsonb`,
restricciones, transacciones y, más adelante, `pgvector`). Tener dos
modalidades (SQLite por defecto, PostgreSQL solo para algunos) dejaba tests
saltados en silencio que parecían verdes. Se unifica:

- **Una sola modalidad: PostgreSQL**, con una base exclusiva `docket_test`.
  Nunca se comparte con `docket_central` ni con bases de tenants de desarrollo.
- `phpunit.xml` **fuerza** `DB_CONNECTION` y `DB_DATABASE` tanto en `<env>` como
  en `<server>` con `force="true"`. Las dos son necesarias: el contenedor `app`
  trae `DB_DATABASE=docket_central` como variable real, y Laravel lee primero de
  `$_SERVER`, que `<env>` no pisa. Host, usuario y clave sí vienen del entorno.
- `tests/TestCase.php` aborta **antes** de los traits de base de datos
  (`setUpTraits()`) si la conexión no es `pgsql` o la base no termina en
  `_test`. Es la defensa final: `DatabaseMigrations` hace `migrate:fresh`.
- Tests que crean bases físicas usan `DatabaseMigrations`, no
  `RefreshDatabase` (`CREATE DATABASE` no corre en transacción), y limpian sus
  tenants en `afterEach` (el evento `TenantDeleted` borra la base física).
- El CI levanta un servicio `postgres` efímero (`ci.yml`).

**Tests sobre la base de un tenant** (mismo amendment, 2026-10-12). Dos
categorías, para que la suite no se vuelva inusable cuando el esquema del tenant
tenga decenas de tablas:

1. *Provisioning y aislamiento* (pocos): crean tenants reales con
   `DatabaseMigrations` (`tests/Feature/Platform/`, `tests/Feature/Tenancy/
   DatabaseIsolationTest.php`). El de aislamiento usa dos tenants reales y una
   tabla sonda propia: prueba el mecanismo sin depender del esquema del núcleo.
2. *Dominio* (la gran mayoría): `uses(Tests\Concerns\UsesTenantDatabase::class)`.
   **Un tenant de pruebas por proceso**, provisionado por los propios tests con
   `ProvisionTenant`, y **una transacción por test, revertida al terminar**
   (el DDL también: PostgreSQL es transaccional). Al terminar el proceso se
   borran su base física y su fila. **Nunca se clona un tenant de desarrollo.**
   Los tests de dominio no escriben en la base central.

Las bases físicas de tenants creadas por tests usan el prefijo `tenanttest_`
(no `tenant`), fijado en `TestCase`: se distinguen de las de desarrollo y
`make test-clean` borra las huérfanas sin riesgo. Optimización futura si
provisionar el tenant compartido se vuelve lento: base plantilla de pruebas
(`CREATE DATABASE … TEMPLATE`, `template_tenant_connection` de stancl), creada
y migrada por los tests, no un tenant real.

La estrategia completa —rendimiento al crecer el esquema y regresión con datos
realistas (casos borde, tests dorados de cálculos, ensayo de migración sobre
copia real)— está en `docs/estrategia-de-pruebas.md`, marcada como propuesta a
revisar.

**Incidente que motivó las dos defensas** (2026-10-12): la primera versión solo
usaba `<env>` sin `force`; al correr la suite en el contenedor, `migrate:fresh`
se ejecutó sobre `docket_central` y vació el catálogo local. Sin pérdida real
(datos de prueba), pero es exactamente el riesgo que las salvaguardas evitan.
