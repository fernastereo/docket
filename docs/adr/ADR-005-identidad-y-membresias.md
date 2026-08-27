# ADR-005: Identidad centralizada, membresía y autorización por tenant

**Estado**: Aceptada
**Fecha**: 2026-07-21
**Amendment 2026-08-27**: se admite identidad de persona jurídica además de
persona natural (ver sección Amendments al final).

## Contexto

Usuarios del sistema: empleados de curaduría (radicadores, revisores, curador,
etc.) y solicitantes — personas naturales y **también personas jurídicas**
(empresas), que pueden radicar trámites en varias curadurías con cuenta
propia (ver amendment 2026-08-27). No hay hoy empleados que trabajen para más
de una curaduría, pero el modelo no debe impedirlo. El aislamiento de datos
de negocio por tenant es innegociable (ADR-002).

## Decisión

Separar **identidad** de **membresía**:

- **Identidad (base central)**: una cuenta única por persona — email, contraseña,
  nombre, **documento de identidad (llave natural única)**, teléfono. Aplica a
  ciudadanos y empleados por igual. Identificador técnico: **UUID**.
  Flag de plataforma para usuarios de soporte/superadmin del proveedor.
  Desde el amendment 2026-08-27, una identidad puede ser de **tipo natural**
  (documento: cédula/CE/PEP) o **tipo jurídica** (documento: NIT), cada una
  con credenciales propias e independientes — ver Amendments.
- **Membresía y autorización (base de cada tenant)**: tabla de miembros que
  referencia a la identidad por UUID (sin FK física, es otra base) y define el
  rol dentro de esa curaduría (radicador, revisor técnico, revisor jurídico,
  curador, etc.). El RBAC completo vive en el tenant.
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

- Audit log del tenant registra "UUID-X con rol Y hizo Z"; para que la base
  entregada en offboarding sea legible, el export incluye un snapshot de los
  datos básicos de las identidades referenciadas.
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
