# Tipo de Trámite

**Estado**: Definido (catálogo alto nivel; requisitos documentales por tipo
quedan para bloque futuro)
**Fecha**: 2026-08-31

## Definición

Reemplaza a lo que iba a llamarse "Licencia": un expediente puede tratar una
o varias **clases** de trámite a la vez (`docs/dominio/expediente.md`), cada
una con sus propias **modalidades**. Cubre tanto licencias urbanísticas
propiamente dichas como "Otras Actuaciones" — trámites vinculados al
desarrollo de proyectos urbanísticos/arquitectónicos que no son licencias en
sí. Estructura alineada con el Decreto 1077/2015.

## Catálogo (Clase → Modalidades)

1. **Urbanización**
   Modalidades: Desarrollo, Saneamiento, Reurbanización.
2. **Parcelación**
   Sin modalidades registradas.
3. **Subdivisión**
   Modalidades: Subdivisión rural, Subdivisión urbana, Reloteo.
4. **Construcción**
   Modalidades: Obra nueva, Ampliación, Adecuación, Modificación,
   Restauración, Reforzamiento Estructural, Demolición, Reconstrucción,
   Cerramiento.
5. **Intervención y ocupación del espacio público**
   Modalidades: Licencia de ocupación del espacio público para la
   localización de equipamiento; Licencia de intervención del espacio
   público; Licencia de intervención y ocupación temporal de playas
   marítimas y terrenos de bajamar.
   > **No aplica hoy a curadurías urbanas** — la resuelven las Secretarías de
   > Planeación municipal. Se incluye en el catálogo porque el sistema debe
   > quedar preparado para que, si en el futuro lo usa una Secretaría de
   > Planeación (u otra autoridad urbanística distinta a una curaduría), esta
   > clase esté disponible. Implica que el catálogo de clases/modalidades
   > **debe ser configurable/activable por tenant**, no una lista fija
   > hardcodeada asumiendo que todo tenant es una curaduría.
6. **Reconocimiento de la existencia de edificaciones**
   Sin modalidades registradas.
7. **Otras actuaciones**
   Trámites vinculados al desarrollo de proyectos urbanísticos/
   arquitectónicos, ejecutables de forma independiente o con ocasión de una
   licencia. Modalidades/ítems: Ajuste de cotas de áreas; Concepto de norma
   urbanística; Concepto de uso del suelo; Copia certificada de planos;
   Aprobación de los Planos de Propiedad Horizontal; Autorización para el
   movimiento de tierras; Aprobación de piscinas; Modificación de planos
   urbanísticos; Aprobación de Proyecto Urbanístico General; **Prórroga (de
   licencia)**.

   > Nota: "Prórroga" vive aquí, como una actuación más — no es una clase
   > aparte. Coincide con `docs/dominio/expediente.md` (expedientes
   > derivados vía `expediente_origen`).

## Objeto (Inicial / Modificación / Revalidación)

Dimensión **distinta e independiente** de Clase/Modalidad: describe si el
trámite es la primera vez que se tramita algo, o si modifica/revalida un
acto administrativo previamente expedido.

- **Inicial**: no depende de ningún trámite anterior.
- **Modificación**: corrige/cambia un acto administrativo ya expedido.
- **Revalidación**: revalida un acto administrativo ya expedido (típicamente
  vencido o próximo a vencer).

Aplica a **todas las clases excepto "Otras actuaciones"** (que ya tiene su
propia lógica de ser independiente o con ocasión de una licencia, sin este
concepto de objeto).

> **No confundir con la modalidad "Modificación" de Construcción** (arriba):
> son dos conceptos totalmente distintos que deben coexistir sin fusionarse.
> - Modalidad "Modificación" (de Construcción) = cambiar los planos
>   aprobados de un proyecto en obra vigente.
> - Objeto "Modificación" = modificar/corregir un acto administrativo ya
>   expedido (de cualquier clase, incluso de otra curaduría).
> Al nombrar esto en código/glosario, evitar que ambos terminen llamándose
> igual (ej. `Modification`) sin distinción de contexto.

Cuando el objeto es Modificación o Revalidación, el trámite **debe estar
amarrado a un acto administrativo anterior** — ver
`docs/dominio/expediente.md`, sección "Expedientes relacionados", para el
detalle de cómo se vincula según si ese acto anterior es de la propia
curaduría o de otra.

## Relación con Acto administrativo

**No es una relación fija 1 a 1.** Cuántos actos administrativos genera un
expediente con varios tipos de trámite depende de **la curaduría** (algunas
resuelven todo en un solo acto; otras generan uno por trámite) y también del
**tipo de trámite específico**. Se modela como una decisión operativa al
momento de expedir, no como una regla de dominio fija — detalle en
`docs/dominio/acto-administrativo.md`.

## Pendiente

- Requisitos documentales por clase/modalidad — bloque futuro.
- Mecanismo de configuración por tenant para activar/desactivar clases según
  el tipo de autoridad (curaduría vs. Secretaría de Planeación) — bloque
  futuro de plataforma/configuración de tenant.
- `glosario.md`: agregar `Tipo de trámite → Filing type (?)`,
  `Clase → License class (?)`, `Modalidad → License modality (?)`,
  `Otras actuaciones → Other actions/proceedings (?)`,
  `Reconocimiento de existencia → Recognition of existing construction (?)`.
