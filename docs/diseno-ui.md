# Diseño de la interfaz

**Estado**: base definida el 2026-10-18. Una sola apariencia para todos los
tipos de usuario (equipo de plataforma, personal de cada curaduría y, más
adelante, solicitantes): cambia el menú y las pantallas, no el diseño.

## 1. Punto de partida

La app hereda la identidad de la landing
(`landing/curaduria-digital-colombia/src/styles.css`): las mismas paletas
(lavanda, turquesa, periwinkle, índigo) y la misma tipografía (Inter Tight).
**Si cambia la marca, cambia en los dos sitios.** El código del diseño vive en:

| Qué | Dónde |
|---|---|
| Paletas y tokens (claro y oscuro) | `docket/resources/css/app.css` |
| Componentes base | `docket/resources/js/components/ui/` |
| Estructura de pantalla y piezas compartidas | `docket/resources/js/components/layout/` |
| Tema claro/oscuro | `docket/resources/js/composables/useTheme.ts` |

## 2. Principios

1. **Es una herramienta de trabajo, no una vitrina.** La gente la usa todo el
   día: legibilidad, densidad razonable y teclado antes que adornos.
2. **Un solo elemento memorable.** La barra lateral oscura con el indicador
   turquesa y la pantalla de acceso con el fondo de marca. El resto es sobrio.
3. **El color comunica.** El turquesa marca "dónde estás" y "todo bien"; los
   estados de negocio usan tonos de la paleta (no un semáforo genérico).
4. **Sin decoración que no informe:** sin degradados de relleno en la app, sin
   mayúsculas sostenidas en etiquetas, sin flechas en cada botón.
5. **Texto claro, en lenguaje de quien usa la herramienta:** "Nueva curaduría",
   no "Crear tenant". Los errores dicen qué pasó y qué hacer; un estado vacío
   invita a la acción. Una acción se llama igual en el botón y en el aviso
   ("Crear curaduría" → "Curaduría creada").

## 3. Tokens

Los componentes **nunca usan colores de paleta directamente**: usan los tokens
semánticos (`bg-primary`, `text-muted-foreground`, `border-border`,
`bg-success/15`…), que cambian solos entre claro y oscuro.

| Token | Claro | Oscuro |
|---|---|---|
| `background` | lav-50 | lav-950 |
| `card` | blanco | lav-900 |
| `foreground` | indigo-x-900 | lav-50 |
| `primary` (acción principal) | indigo-x-800 | lav-100 |
| `accent` (marca / foco de atención) | turq-500 | turq-500 |
| `muted-foreground` (texto secundario) | lav-600 | lav-300 |
| `destructive` / `success` / `info` / `warning` | estados de negocio | ídem, más claros |
| `sidebar*` | índigo oscuro (en ambos modos) | índigo aún más oscuro |

Tipografía: **Inter Tight** (400, 500, 600, 700), alojada en nuestro servidor
(el plugin de Vite la descarga al compilar; no se pide nada a terceros desde el
navegador). Cifras en tablas con `tabular-nums`.

## 4. Componentes

`components/ui/` son componentes **propios en el repo**, escritos al estilo de
shadcn-vue sobre **Reka UI** (accesibles: foco, teclado, ARIA) y los tokens de
arriba: Button, Input, Label, Card, Table, Badge, Dialog, DropdownMenu,
Separator, Skeleton, Sheet, Tooltip, IconButton, InitialsAvatar y el aviso (`vue-sonner`). Para agregar uno nuevo se
copia ese patrón; **no usar la CLI de shadcn-vue con Node < 22.15** (falla al
arrancar, ya probado).

### Qué significa "componentes propios"

No se escribe cada componente desde cero. Hay tres capas:

| Capa | Quién la pone | Qué resuelve |
|---|---|---|
| **Reka UI** (dependencia) | la librería | el comportamiento accesible: trampa de foco, teclado, ARIA, posicionamiento de menús y diálogos |
| **Tokens** (`app.css`) | nosotros, una vez | colores, radios y tipografía de la marca, en claro y oscuro |
| **Archivos de `components/ui/`** | nosotros, por componente | solo el aspecto: unas pocas clases de Tailwind sobre los tokens (20–40 líneas) |

Un componente nuevo es copiar el patrón de uno existente y cambiarle las clases,
no escribir lógica. Los archivos viven en el repo (no en `node_modules`), así
que la apariencia se cambia sin pelear con una librería.

**Cómo agregar un componente**

1. Con **Node >= 22.15**: `npx shadcn-vue@latest add <componente>` (configuración
   en `docket/components.json`). Genera el archivo con los colores semánticos;
   hay que revisar que no traiga colores de paleta sueltos.
2. Con Node más viejo (el generador falla al arrancar): copiar el patrón de un
   componente de `components/ui/` sobre la primitiva equivalente de Reka UI.
3. Cualquiera de las dos: probarlo en modo claro y oscuro, con teclado, y
   añadirlo a este documento.

**Piezas complejas del dominio** (selector de fechas con días hábiles
colombianos —ADR-014—, tabla de expedientes con filtros y orden, editor de
actos): se resuelven con una librería puntual para la lógica (p. ej. TanStack
Table, que no impone estilos) vestida con nuestros componentes. No se adopta
una librería de interfaz completa.

**Por qué no una librería de interfaz completa** (PrimeVue, Vuetify, Naive UI,
Nuxt UI): cuesta ajustarla a la identidad de la marca, pesa más, varias aplican
estilos en línea que chocan con nuestra CSP (ADR-017) y queda uno atado a sus
cambios de versión. No se descarta para siempre: si algún día el costo de
mantener los componentes supera ese de adoptarla, se revisa en un ADR.

Piezas compartidas (`components/layout/`):

- `AppShell`: navegación lateral (cajón en móvil) + contenido. Cada área
  (plataforma, curaduría) le pasa su lista de enlaces (`items`) y el usuario.
- `PageHeader`: título, descripción y acciones de la pantalla.
- `EmptyState`: icono, título, explicación y la acción para salir del vacío.
- `BrandMark`: marca (ícono + nombre) de la barra y de las pantallas de acceso.

**Acciones con icono** (regla de toda la interfaz): una acción clicable que sea
solo un icono —cambiar contraseña, enviar enlace, sugerir, mostrar/ocultar— se
dibuja como **icono suelto, estilo enlace**: 18 px, sin borde ni fondo, con la
mano al pasar el ratón y cambio de color a turquesa (`text-muted-foreground` →
`text-action`; el token `--action` es `turq-700` en claro y `turq-400` en
oscuro), más un tooltip que dice qué hace. El hover tiene que notarse: un cambio
de tono (lavanda → turquesa), no de luminosidad. Se usa el componente `IconButton`, nunca un
`Button` con borde. Los botones con borde o relleno quedan para acciones con
texto (guardar, cancelar, crear). Deshabilitado, el tooltip explica por qué.
Todos los `<button>` llevan `cursor-pointer` (Tailwind 4 los deja con la flecha).

Patrones:

- **Crear algo** → diálogo con el formulario; al guardar, el diálogo se cierra
  y aparece un aviso. Si falla, el diálogo sigue abierto con el error en el campo.
- **Listas** → tabla dentro de una tarjeta, con esqueletos mientras carga, un
  estado vacío que invita a crear, y un mensaje si no se pudo cargar.
- **Tablas de personas y entidades** → una fila por entidad, con su dirección o
  dato secundario como segunda línea del nombre (no una columna aparte), las
  personas con avatar de iniciales (`InitialsAvatar`), el estado como `Badge`
  con punto, las fechas como "30 oct 2026" y las acciones de la fila al final,
  alineadas a la derecha y como iconos. Cabecera con fondo tenue y filas con
  hover.
- **Estados de negocio** → un `Badge` por estado con un componente propio
  (p. ej. `StatusBadge`), no colores sueltos.

## 5. Modo oscuro

Tres opciones por usuario: claro, oscuro y según el sistema (menú del usuario,
abajo a la izquierda). Se guarda en el navegador. Un script en
`resources/views/app.blade.php` lo aplica antes de pintar para que no haya
destello; lleva el nonce de la CSP. Más adelante, la preferencia podría
guardarse en el perfil del usuario.

## 6. Accesibilidad y seguridad (mínimos)

- Foco visible en todo elemento interactivo; etiquetas reales en formularios;
  `role="alert"` en errores. (Pendiente: enlace "saltar al contenido".)
- Se respeta `prefers-reduced-motion`.
- Contraste de texto sobre fondo claro y oscuro: **pendiente de verificar AA**
  con herramienta (en especial `muted-foreground` y los badges).
- CSP (`SecurityHeaders`): sin estilos ni scripts en línea sin nonce. Los
  componentes aplican estilos por propiedades (permitido), nunca con atributos
  `style="…"` escritos a mano en el HTML.

## 7. Pendiente

- [ ] Actualizar Node en las máquinas de desarrollo a >= 22.15 (el generador de
      componentes lo necesita y npm avisa `EBADENGINE`; el CI ya usa el último 22).
- [ ] Logo definitivo (hoy: ícono de edificio público + nombre).
- [ ] Verificar contrastes AA (claro y oscuro) con una herramienta.
- [ ] Revisar con datos reales la densidad de las tablas (expedientes) cuando
      existan.
- [ ] Revisar el diseño en móvil con quien vaya a usarlo en campo (visitas).
