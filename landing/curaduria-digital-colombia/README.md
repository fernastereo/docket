# Curaduria Digital

Quiero una landing page en español, dirigida a un público muy específico:

**Curadores Urbanos y funcionarios de Secretarías de Planeación municipal en

Colombia**. El objetivo de esta página **no es vender todavía** — es

**validar interés y capturar leads** de un producto SaaS nuevo llamado

**CuraduriAPP** que va a reemplazar el software de gestión

de trámites que usan hoy la mayoría de curadurías, un sistema hecho hace

~20 años en Visual Basic 6 + Access, sin presencia web, sin trazabilidad

moderna y sin ninguna capa de inteligencia artificial.

### Contexto del dominio (para que el copy sea preciso, no genérico)

Las Curadurías Urbanas son oficinas privadas que ejercen una función

pública: estudian, tramitan y expiden licencias urbanísticas (construcción,

urbanización, parcelación, subdivisión, reconocimiento de edificaciones,

"otras actuaciones") bajo la Ley 388 de 1997, la Ley 1796 de 2016 y el

Decreto 1077 de 2015. Cada licencia culmina en un **acto administrativo**

(una Resolución) con pleno valor legal. Manejan expedientes con documentos

sensibles (predios, solicitantes, planos, escrituras) y son extremadamente

cuidadosas con la confidencialidad de su información — cualquier mensaje de

seguridad/aislamiento de datos en la página debe sonar creíble y específico,

no genérico de "seguridad empresarial".

### Público objetivo (dos segmentos, un mismo mensaje central)

1. **Curadores Urbanos** (hay ~50-60 en el país) — dueños/operadores de su

   propia curaduría, deciden qué software usar.

2. **Secretarías de Planeación municipal** — resuelven trámites similares

   (incluyendo licencias de intervención y ocupación del espacio público,

   que las curadurías normalmente no tramitan).

Tono: **profesional, serio, confiable** — es software para función pública

con valor legal, no puede sonar como una app de consumo. Pero al mismo

tiempo **moderno y de ruptura** frente a lo que existe hoy (que es

literalmente un sistema de escritorio de hace 20 años). El contraste

"lo que tienen hoy vs. lo que podrían tener" es el corazón del mensaje.

### Estructura de la página

1. **Hero**: propuesta de valor en una frase fuerte (algo como "El sistema

   de gestión de trámites urbanísticos que tu curaduría necesitaba hace 20

   años" o similar — mejórala tú). CTA principal hacia el formulario de

   interés. Evitar jerga técnica aquí; hablar en términos de lo que gana el

   curador (control, trazabilidad, modernidad, menos fricción).

2. **El problema** (breve, con respeto — no burlarse del legado): sistemas

   de escritorio antiguos, sin radicación en línea, sin trazabilidad

   completa, sin analítica, sin presencia web para los ciudadanos, riesgo de

   pérdida de información, dependencia de una sola persona que sabe operar

   el sistema.

3. **La solución — diferenciadores clave** (usar como estructura de

   tarjetas/secciones, cada una con un ícono y 1-2 frases, no párrafos

   largos):

- **Radicación 100% en línea** además de ventanilla presencial — todo

  solicitante puede seguir su trámite desde internet.

- **Portal ciudadano** con seguimiento del estado del expediente en

  tiempo real, sin necesidad de llamar o ir en persona.

- **Trazabilidad legal completa**: cada expediente tiene una línea de

  tiempo de actividad (al estilo de herramientas modernas de gestión de

  proyectos) — quién hizo qué y cuándo, en cada trámite.

- **Inteligencia artificial en cada paso del proceso**: extracción

  automática de datos desde escrituras/certificados/cédulas al radicar,

  verificación de completitud documental, asistente de redacción de

  actas y resoluciones, consulta normativa (POT, NSR-10) en lenguaje

  natural durante la revisión técnica/jurídica/estructural.

- **Alertas de vencimiento de plazos** antes de que se cumplan (no

  cuando ya es tarde) — reduce desistimientos evitables por descuido de

  términos legales.

- **Verificación pública de actos administrativos por código QR** —

  cualquiera puede confirmar que una resolución es auténtica, sin

  depender de una llamada a la curaduría. Antifraude real.

- **Analítica operativa**: el curador puede ver, por primera vez, dónde

  se atascan los trámites, cuánto tarda cada área, la carga de trabajo

  de su equipo — algo que hoy es imposible de ver.

- **Aislamiento total de datos**: cada curaduría tiene su propia base de

  datos física, separada de todas las demás — no es una tabla compartida

  con una columna de "cliente", es una base independiente. Los datos de

  una curaduría nunca conviven con los de otra.

- **Migración fiel desde el sistema actual**: no se empieza de cero, se

  migra el histórico completo.

- **Firma electrónica** del curador, desde cualquier lugar, con

  trazabilidad legal.

4. **Pensado también para Secretarías de Planeación**: mencionar

   explícitamente que el sistema contempla licencias de intervención y

   ocupación del espacio público, no solo lo que tramita una curaduría

   tradicional — sección corta, un párrafo.

5. **Cómo funciona** (opcional, si el diseño lo permite): una versión

   simplificada y visual del recorrido de un expediente — radicación →

   revisión técnica/jurídica/estructural → resolución → expedición — sin

   entrar en el detalle interno de estados, solo para transmitir que el

   proceso real está completamente cubierto, no es una app genérica.

6. **Formulario de captura de leads** (la sección más importante de la

   página, debe destacar visualmente): título tipo "Sé de los primeros en

   conocerlo" o "Regístrate para acceso anticipado". Campos:

- Nombre completo (texto, requerido)

- Curaduría o entidad (texto, requerido)

- Cargo (texto, requerido — ej. Curador, Coordinador, Arquitecto,

  Secretario de Planeación)

- Ciudad/Municipio (texto, requerido)

- Correo electrónico (email, requerido)

- Teléfono (texto, opcional)

- Comentario/interés particular (textarea, opcional)

- Botón de envío: "Quiero saber más" o similar.

**Importante para la implementación del formulario**: este formulario se

va a conectar después a una API externa (Brevo) para gestión de leads —

estructura el componente del formulario de forma limpia y separada (un

solo componente, con una función `onSubmit` claramente aislada del resto

de la UI) para poder reemplazar fácilmente su lógica de envío más

adelante sin tocar el diseño. Usa nombres de campo simples y en snake_case

o camelCase consistente (ej. `nombre`, `curaduria`, `cargo`, `municipio`,

`email`, `telefono`, `comentario`).

7. **Footer**: datos de contacto genéricos (placeholder), año actual,

   nombre del producto.

### Diseño

- Estética **institucional-moderna**: transmite seriedad y confianza (es

  software para función pública con valor legal) pero sin verse anticuado

  ni genérico de plantilla SaaS. Piensa en el cruce entre un producto

  legal-tech serio y un SaaS moderno bien diseñado — no colores

  estridentes, tipografía limpia y profesional.

- **Usa exactamente esta paleta de colores** (formato Tailwind, con sus

  variantes 50-950 para cada tono — respétala, no inventes otra):

```json
{
  "lavender-grey": {
    "50": "#efeff6",
    "100": "#dfdfec",
    "200": "#c0beda",
    "300": "#a09ec7",

    "400": "#807db5",
    "500": "#615da2",
    "600": "#4d4a82",
    "700": "#3a3861",

    "800": "#272541",
    "900": "#131320",
    "950": "#0e0d17"
  },

  "turquoise": {
    "50": "#e7fdfb",
    "100": "#d0fbf7",
    "200": "#a1f7ef",
    "300": "#71f4e7",

    "400": "#42f0de",
    "500": "#13ecd6",
    "600": "#0fbdab",
    "700": "#0b8e81",

    "800": "#085e56",
    "900": "#042f2b",
    "950": "#03211e"
  },

  "periwinkle": {
    "50": "#edeff8",
    "100": "#dadff1",
    "200": "#b5bfe3",
    "300": "#909fd5",

    "400": "#6b7fc7",
    "500": "#465fb9",
    "600": "#384c94",
    "700": "#2a396f",

    "800": "#1c264a",
    "900": "#0e1325",
    "950": "#0a0d1a"
  },

  "space-indigo": {
    "50": "#eaeefa",
    "100": "#d5dcf6",
    "200": "#acbaec",
    "300": "#8297e3",

    "400": "#5975d9",
    "500": "#2f52d0",
    "600": "#2642a6",
    "700": "#1c317d",

    "800": "#132153",
    "900": "#09102a",
    "950": "#070b1d"
  }
}
```

Úsala así: **space-indigo** y **periwinkle** como base institucional

(fondos oscuros, texto, elementos estructurales), **lavender-grey** como

neutro de apoyo (fondos claros, tarjetas, texto secundario), y

**turquoise** como color de acento — resérvalo casi exclusivamente para

los CTA del formulario y detalles que deban destacar, para que no compita

con el tono institucional del resto de la página.

- Responsive, mobile-first (muchos funcionarios lo verán desde el celular).

- Sin necesidad de autenticación ni backend complejo más allá del

  formulario — es una landing de una sola página (con posibles anclas

  internas), no una aplicación.

### Lo que NO debe tener esta página

- Nada de precios (aún no está definido el modelo comercial).

- Nada de nombres de curadurías reales como "clientes actuales" (el

  producto todavía no tiene clientes — es fase de validación de interés).

- Nada de capturas de pantalla de producto real (no existe todavía) — usar

  ilustraciones, mockups conceptuales, o composiciones abstractas en su

  lugar.

This project was built with [Lovable](https://lovable.dev).

## Build with Lovable

Continue developing this project in the [Lovable editor](https://lovable.dev/projects/36bcc844-18de-486e-896c-e7328b1559c4).

- **Ship faster**: describe what you want to build and Lovable handles the code.
- **Stay in sync**: every change made in Lovable is committed straight to this repository.
- **Full ownership**: this code is yours. Push to `main` on GitHub and your changes sync back into Lovable, ready for your next prompt.

## Development

Prefer working locally? You need Node.js and npm — [install with nvm](https://github.com/nvm-sh/nvm#installing-and-updating).

```sh
git clone <this-repository-url>
cd <repository-name>
npm i
npm run dev
```
