# ADR-005: Identidad centralizada, membresía y autorización por tenant

**Estado**: Aceptada
**Fecha**: 2026-07-21
**Amendment 2026-08-27**: se admite identidad de persona jurídica además de
persona natural (ver sección Amendments al final).
**Amendment 2026-09-02**: las identidades de empleado salen de la central —
pasan a la base de cada tenant y absorben la membresía; se descarta toda
vista nacional agregada para solicitantes; se añade regla de conflicto de
interés (ver sección Amendments al final).

## Contexto

Usuarios del sistema: empleados de curaduría (radicadores, revisores, curador,
etc.) y solicitantes — personas naturales y **también personas jurídicas**
(empresas), que pueden radicar trámites en varias curadurías con cuenta
propia (ver amendment 2026-08-27). Puede haber empleados vinculados laboralmente a
más de una curaduría a la vez — poco común, pero el modelo no debe impedirlo
(ver amendment 2026-09-02). El aislamiento de datos de negocio por tenant es
innegociable (ADR-002).

## Decisión

Separar **identidad** de **membresía**:

- **Identidad (base central)**: una cuenta única por persona — email, contraseña,
  nombre, **documento de identidad (llave natural única)**, teléfono. Aplica a
  ciudadanos y a usuarios de plataforma; **desde el amendment 2026-09-02 ya no
  a empleados** — la cuenta de empleado es local al tenant (ver Amendments).
  Identificador técnico: **UUID**.
  Flag de plataforma para usuarios de soporte/superadmin del proveedor.
  Desde el amendment 2026-08-27, una identidad puede ser de **tipo natural**
  (documento: cédula/CE/PEP) o **tipo jurídica** (documento: NIT), cada una
  con credenciales propias e independientes — ver Amendments.
- **Membresía y autorización (base de cada tenant)**: define el rol dentro de
  esa curaduría (radicador, revisor técnico, revisor jurídico, curador, etc.).
  El RBAC completo vive en el tenant. Para **ciudadanos**, la membresía
  referencia a la identidad central por UUID (sin FK física, es otra base).
  Para **empleados**, desde el amendment 2026-09-02 la membresía se **fusiona
  con la propia cuenta de empleado del tenant** (rol + vigencia + estado en la
  misma entidad; ya no hay identidad central de empleado a la que referir).
- **Ciudadanos**: una sola cuenta; acceden por el subdominio de la curaduría de
  interés. Sus expedientes en cada curaduría son independientes y viven en la
  base de cada tenant.
- **Datos personales mínimos en la central**: solo lo necesario para
  autenticar/identificar. Los datos del solicitante en contexto de trámite
  (dirección, calidad en que actúa, etc.) viven en el expediente del tenant.
- **Personas jurídicas**: tienen su propia identidad central (tipo jurídica,
  NIT) y su propio login, independiente del de su representante legal — ver
  Amendments. El expediente registra además en calidad de qué actúa cada
  solicitante (propietario, apoderado, representante legal de empresa, etc.)
  y, para las personas jurídicas, el historial de representantes legales con
  vigencia (detalle en `docs/dominio/solicitante.md`). La administración de
  las credenciales de la cuenta de la empresa (quién dentro de la empresa las
  conoce/usa) es responsabilidad de la empresa, no del sistema.

## Reglas de privacidad entre tenants

- Al buscar por cédula desde ventanilla, la central responde únicamente si la
  identidad existe y sus datos básicos. **Nunca** revela en qué otras
  curadurías tiene trámites o membresías.

## Consecuencias

- Audit log del tenant registra "actor con rol Y hizo Z"; para acciones de
  ciudadano el actor es el UUID central, para acciones de empleado es el id
  local de la cuenta de empleado (ver amendment 2026-09-02). Para que la base
  entregada en offboarding sea legible, el export incluye un snapshot de los
  datos básicos de las identidades **de ciudadano** referenciadas (las de
  empleado ya viajan dentro del dump del tenant).
- Colisiones de identidad (mismo documento, email distinto) se resuelven por
  flujo de recuperación/verificación, nunca creando cuenta duplicada.

## Amendments

### 2026-08-27 — Identidad de persona jurídica

**Motivo**: surgió al modelar la entidad Solicitante
(`docs/dominio/solicitante.md`). Una empresa puede radicar trámites en más de
una curaduría, igual que una persona natural, y necesita poder ingresar y ver
sus proyectos sin depender de que un representante legal específico esté
disponible o vigente.

**Cambio**: la identidad central deja de asumir siempre persona natural.
Ahora admite dos tipos:
- **Natural**: documento cédula/CE/PEP, como hasta ahora.
- **Jurídica**: documento NIT, con credenciales propias e independientes de
  cualquier persona natural asociada a ella.

El representante legal de una empresa **también** tiene su propia identidad
natural y su propio login — no inicia sesión "como" la empresa, sino que ve,
bajo su propia cuenta, tanto sus trámites personales como los de las
empresas que representa o representó (relación `RepresentanteLegal` con
vigencia, definida en `docs/dominio/solicitante.md`).

No cambia el resto del ADR: el acceso sigue resuelto por subdominio de
curaduría (una identidad, sea natural o jurídica, ve solo lo que tiene *en
esa* curaduría — sin vista unificada entre tenants), y la central sigue sin
revelar en qué curadurías tiene trámites una identidad.

### 2026-09-02 — Cuentas de empleado por curaduría; fusión con membresía; sin agregación nacional

**Motivo**: al preparar el bloque de identidad/auth de la implementación se
contrastó el modelo contra tres casos reales:

1. Una persona vinculada laboralmente a **más de una curaduría a la vez**
   (poco común, pero posible).
2. Esa misma persona actuando además como **solicitante**.
3. Un solicitante —normalmente una empresa— con expedientes en **varias
   curadurías del país**.

**Cambio a) Las identidades de empleado salen de la base central.** Este ADR
asumía identidad central *"para ciudadanos y empleados por igual"*. A partir
de aquí, **solo los solicitantes y los usuarios de plataforma tienen
identidad central**. La cuenta de empleado vive en la **base del tenant**, la
provisiona y gobierna esa curaduría (alta, baja, política de contraseña/MFA,
reset), con **email corporativo**.

- Una persona empleada por dos curadurías tiene **dos cuentas
  independientes**, una en cada base, sin nada compartido (credenciales,
  sesión, recuperación). Es intencional: contiene el radio de impacto de un
  incidente y deja que cada curaduría atestigüe ante la SNR que administra
  sus propios accesos.
- El número de documento se guarda en la cuenta de empleado para
  identificación y para la verificación de conflicto de interés (cambio d).
  Unicidad: `(tenant, documento)`, no global.
- La central deja de conocer el mapa persona→curadurías de empleo — refuerza
  la regla de privacidad que este ADR ya buscaba.

**Cambio b) La cuenta de empleado absorbe la membresía.** Credencial y
vínculo laboral iban separados porque la identidad era central. Al quedar la
cuenta de empleado dentro del tenant, se **fusionan en una sola entidad**:
credencial + rol(es) RBAC + `vigencia_desde` / `vigencia_hasta` + `estado`
(activo / suspendido / desvinculado). Se elimina la tabla de membresía
separada. El RBAC completo sigue viviendo en el tenant, sin cambios. La
membresía de **ciudadano** (cuando aplique) sí sigue referenciando el UUID
central.

**Cambio c) No hay vista nacional para solicitantes.** Se evaluó y descartó
—sobredimensionado para el volumen real— un índice agregador cross-tenant en
la central. Se mantiene lo que este ADR ya decía: **una sola credencial de
solicitante (email/contraseña), reutilizable en el subdominio de cualquier
curaduría** donde tenga trámites; en cada subdominio ve únicamente lo que
tiene *en esa* curaduría. La central sigue sin revelar en qué curadurías
tiene trámites una persona y sin ofrecer vista unificada.

**Cambio d) Conflicto de interés (nuevo).** Un empleado no puede tramitar su
propia solicitud en la curaduría donde trabaja. Como la cuenta de empleado
(tenant) y la identidad de solicitante (central) de una misma persona
comparten número de documento, el tenant puede detectar localmente que un
documento presente como solicitante en un expediente coincide con el de un
empleado activo, y marcar/bloquear. Verificación **local** al tenant; no
requiere consulta a la central más allá de resolver la identidad del
solicitante.

**Consecuencias**:

- **Audit log**: las acciones de empleado referencian el id **local** de la
  cuenta de empleado del tenant; las de solicitante siguen referenciando el
  UUID central sin FK física.
- **Offboarding de un tenant**: las cuentas de empleado y su historial ya
  viajan dentro del dump de la base del tenant; el snapshot de identidades
  del export solo cubre las **identidades de solicitante** referenciadas.
- **Provisioning multi-tenant**: el esquema de cuentas de empleado + RBAC
  entra en las migraciones de tenant (`tenants:migrate`), no en la central.
- El amendment 2026-08-27 no cambia: la empresa es una identidad de
  **solicitante**, central, con credencial única reutilizable por subdominio.
- Queda abierto (`docs/preguntas-abiertas.md`): si un empleado necesita
  autenticarse una sola vez para varias curadurías en el futuro, se
  resolvería con un IdP/SSO por encima de las cuentas locales, no
  reintroduciendo identidad de empleado en la central.
