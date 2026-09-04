# Solicitante

**Estado**: Definido (ver amendment 2026-08-27 en ADR-005)
**Fecha**: 2026-08-27

## Definición

Persona (natural o jurídica) que participa como parte interesada en un
expediente. Un expediente puede tener **varios solicitantes** a la vez (ej.
copropietarios que radican juntos, o una empresa junto con su representante
legal). No existe la noción de "solicitante principal" fija en la entidad: la
**calidad en que actúa** cada solicitante (propietario, apoderado,
representante legal, etc.) es un atributo de su relación con **ese**
expediente, no de la persona en sí — la misma persona puede ser propietaria
en un trámite, apoderada en otro, y representante legal de una empresa en un
tercero.

> Nota de alcance: esta entidad no debe confundirse con **Tercero**, un
> concepto distinto (aún por definir) para partes ajenas al trámite que
> pueden verse afectadas por él (ej. vecinos colindantes). Se aborda en un
> bloque futuro.

## Identidad y tipos

Persona natural y persona jurídica (empresa) son ambas solicitantes de pleno
derecho, con **login propio e independiente**:

- **Persona natural**: identidad central (ADR-005) por cédula/CE/PEP.
- **Persona jurídica**: identidad central por **NIT**. Tiene sus propias
  credenciales; la curaduría no interviene en quién dentro de la empresa las
  administra (es responsabilidad de la empresa, no del sistema).

Esto amplía ADR-005 — ver amendment 2026-08-27 en ese documento.

Cada login (natural o jurídica) entra por el subdominio de la curaduría de
interés y ve únicamente los proyectos que tiene **en esa curaduría** — no hay
vista unificada entre curadurías, sin cambios al aislamiento por tenant de
ADR-002.

## Atributos

Capturados y cacheados localmente en la base del tenant (para búsqueda y
listado sin cruzar con la base central):

- Tipo de persona: natural / jurídica.
- Número y tipo de documento de identificación (cédula/CE para natural, NIT
  para jurídica).
- Nombre completo o razón social.
- Teléfono de contacto.
- Email.
- Dirección de correspondencia.
- Referencia a la identidad central (UUID).

*(La "calidad en que actúa" NO es un atributo del Solicitante — vive en la
relación Expediente↔Solicitante, ver abajo.)*

## Representación legal de personas jurídicas

Entidad relacionada `RepresentanteLegal`, local al tenant:

- Vincula un Solicitante tipo jurídica con un Solicitante tipo natural.
- Vigencia: fecha desde / fecha hasta (nulo si sigue vigente).
- Documento soporte: certificado de existencia y representación legal
  (Cámara de Comercio).
- Puede haber historial: representantes cambian en el tiempo: se conserva el
  histórico para poder determinar quién representaba a la empresa en la
  fecha de un acto administrativo concreto.

## Relación con Expediente

Relación muchos-a-muchos `Expediente ↔ Solicitante`, con atributos propios:

- **`calidad_en_que_actua`**: propietario, apoderado, representante legal de
  empresa, etc. (catálogo cerrado, pendiente de completar).
- **`es_contacto_principal`**: booleano. Cuando hay varios solicitantes, se
  marca uno como contacto principal para efectos de comunicación.

Cuando una empresa participa a través de su representante legal, se generan
**dos filas** en esta relación: la empresa (ej. calidad = propietario) y la
persona natural que la representa (calidad = representante legal), ligadas
conceptualmente al `RepresentanteLegal` vigente en la fecha del acto.

## Reglas de negocio

- Todos los solicitantes de un expediente son notificados y tratados por
  igual — no hay jerarquía legal entre ellos.
- **Documentos con efecto legal** (licencia, resolución): deben listar a
  **todos** los solicitantes del expediente.
- **Documentos sin efecto legal** (comunicaciones internas, actas
  informativas, etc.): se imprime el contacto principal seguido de
  "Y OTROS" cuando hay más de un solicitante.
- No existe inhabilitación para radicar: al ser un servicio público, no hay
  condición (mora, sanción, etc.) que impida a alguien radicar un trámite.

## Notas del legado

- El legado guarda hoy el dato de la empresa como campo adicional del
  registro, sin distinguir correctamente al representante como una persona
  con identidad propia (texto libre). El modelo nuevo lo corrige tratando al
  representante como un Solicitante natural más, vinculado vía
  `RepresentanteLegal`.

## Campos personalizados

Cada curaduría puede definir **campos personalizados** sobre Solicitante
(`docs/adr/ADR-016-campos-personalizados-tenant.md`). Viven en la **copia
tenant-local** del Solicitante (los atributos cacheados de arriba), **no** en
la identidad central — coherente con ADR-005 (central mínima, amendment
2026-09-02). También aplican a la relación `Expediente ↔ Solicitante`.

## Consecuencias / pendientes

- ADR-005 fue enmendado (2026-08-27) para admitir identidad de persona
  jurídica. Ver ese documento.
- Falta cerrar el catálogo cerrado de `calidad_en_que_actua` (bloque futuro:
  flujo del trámite / roles).
- `glosario.md`: agregar/confirmar `Solicitante → Applicant`,
  `Tercero → Party (?)` (reservado, no usar aún),
  `Representante legal → Legal representative (?)`,
  `Calidad en que actúa → Acting capacity (?)`.
