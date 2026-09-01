import { createFileRoute } from "@tanstack/react-router";
import {
  Globe,
  Users,
  History,
  Sparkles,
  BellRing,
  QrCode,
  BarChart3,
  PenTool,
  ArrowRight,
  Building2,
  Wand2,
  ScanText,
  ClipboardCheck,
  BookOpenCheck,
  MessageCircle,
} from "lucide-react";
import { Toaster } from "@/components/ui/sonner";
import { LeadForm } from "@/components/LeadForm";
import { DashboardPreview } from "@/components/DashboardPreview";
import { Reveal } from "@/components/Reveal";
import logo from "@/assets/logo.png";
import tabletPreview from "@/assets/tablet-preview.jpg";

const structuredData = {
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  name: "CuraduriAPP",
  applicationCategory: "BusinessApplication",
  operatingSystem: "Web",
  description:
    "Software para Curadurías Urbanas y Secretarías de Planeación en Colombia: radicación en línea, trazabilidad legal, inteligencia artificial y verificación pública de actos administrativos.",
  url: "https://curaduria.app/",
  inLanguage: "es-CO",
};

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "CuraduriAPP | IA al servicio de trámites urbanísticos" },
      {
        name: "description",
        content:
          "Software para Curadurías Urbanas y Secretarías de Planeación en Colombia: radicación en línea, trazabilidad legal, IA y verificación QR. Regístrate para acceso anticipado.",
      },
      { property: "og:title", content: "CuraduriAPP | IA al servicio de trámites urbanísticos" },
      {
        property: "og:description",
        content:
          "Radicación en línea, portal ciudadano, trazabilidad legal completa e inteligencia artificial para licencias urbanísticas en Colombia.",
      },
    ],
    scripts: [
      {
        attrs: { type: "application/ld+json" },
        children: JSON.stringify(structuredData),
      },
    ],
  }),
  component: Landing,
});

const iaFeatures = [
  {
    icon: Wand2,
    title: "Copiloto de redacción",
    text: "El equipo anota hallazgos en lenguaje suelto y la IA redacta el Acta de Observaciones, y ayuda a que cada Resolución se lea coherente — no como un mail-merge. Siempre con revisión humana antes de enviarse.",
    featured: true,
  },
  {
    icon: ScanText,
    title: "Extracción automática de documentos",
    text: "Escrituras, cédulas y certificados se leen solos al radicar: los datos llegan pre-llenados al expediente.",
  },
  {
    icon: ClipboardCheck,
    title: "Verificación de completitud",
    text: "Antes de que un humano revise, la IA ya avisa si falta algún documento según el tipo de trámite.",
  },
  {
    icon: BookOpenCheck,
    title: "Normativa en lenguaje natural",
    text: "POT y NSR-10 consultables en el momento exacto de la revisión técnica, jurídica y estructural.",
  },
  {
    icon: MessageCircle,
    title: "Seguimiento conversacional",
    text: 'El ciudadano pregunta "¿en qué va mi trámite?" y recibe una respuesta clara, sin jerga legal.',
  },
];

const diferenciadores = [
  {
    icon: History,
    title: "Trazabilidad legal completa",
    text: "Cada expediente tiene su línea de tiempo, al estilo de las herramientas modernas de gestión de proyectos: quién hizo qué, cuándo y sobre qué documento — auditable de principio a fin.",
    featured: true,
  },
  {
    icon: BarChart3,
    title: "Analítica operativa",
    text: "Dónde se atascan los trámites, cuánto tarda cada área y la carga real de trabajo de su equipo — algo que hoy es imposible de ver.",
    featured: true,
  },
  {
    icon: Globe,
    title: "Radicación 100% en línea",
    text: "Sin eliminar la ventanilla presencial. Cualquier solicitante puede radicar y continuar su trámite desde internet.",
  },
  {
    icon: Users,
    title: "Portal ciudadano",
    text: "El solicitante consulta el estado real de su expediente en tiempo real, sin llamar ni desplazarse.",
  },
  {
    icon: BellRing,
    title: "Alertas de vencimiento de términos",
    text: "Avisos antes de que se cumpla el plazo legal, no después. Menos desistimientos evitables.",
  },
  {
    icon: QrCode,
    title: "Verificación pública por código QR",
    text: "Cualquier tercero confirma la autenticidad de una resolución sin llamar a la curaduría. Antifraude real.",
  },
  {
    icon: PenTool,
    title: "Firma electrónica del curador",
    text: "Firma desde cualquier lugar, con registro y trazabilidad legal del acto administrativo.",
  },
];

const flujo = [
  {
    paso: "01",
    title: "Radicación",
    text: "Presencial o en línea, con validación documental asistida.",
  },
  {
    paso: "02",
    title: "Revisión",
    text: "Técnica, jurídica y estructural, con control de términos y Copiloto IA en cada paso.",
  },
  {
    paso: "03",
    title: "Resolución",
    text: "Proyección del acto administrativo y firma electrónica.",
  },
  { paso: "04", title: "Expedición", text: "Entrega, notificación y verificación pública por QR." },
];

function Landing() {
  const year = new Date().getFullYear();

  return (
    <div className="min-h-screen overflow-x-hidden bg-background text-foreground">
      <Toaster position="top-center" />

      <header className="surface-dark relative overflow-hidden">
        <div
          className="animate-float-glow pointer-events-none absolute -top-24 right-[-10%] h-[32rem] w-[32rem] rounded-full bg-turq-500/20 blur-3xl"
          aria-hidden
        />
        <div
          className="animate-float-glow pointer-events-none absolute bottom-[-10rem] left-[-10%] h-[28rem] w-[28rem] rounded-full bg-peri-500/20 blur-3xl"
          style={{ animationDelay: "3s" }}
          aria-hidden
        />

        <nav className="relative mx-auto flex max-w-6xl items-center justify-between px-5 py-5">
          <img src={logo} alt="CuraduriAPP" className="h-12 w-auto sm:h-14" />
          <a
            href="#registro"
            className="rounded-md border border-turq-500/40 px-4 py-2 text-sm font-medium text-turq-300 transition hover:bg-turq-500/10"
          >
            Acceso anticipado
          </a>
        </nav>

        <div className="relative mx-auto grid max-w-6xl items-center gap-12 px-5 pb-20 pt-10 lg:grid-cols-2 lg:pb-28 lg:pt-16">
          <div>
            <span className="animate-fade-in-up inline-flex items-center gap-2 rounded-full border border-turq-500/30 bg-turq-500/10 px-3 py-1 text-xs font-medium uppercase tracking-wider text-turq-300">
              <Sparkles className="h-3.5 w-3.5" aria-hidden />
              Construido por un equipo con 20+ años en el sector
            </span>
            <h1
              className="animate-fade-in-up mt-6 text-4xl font-semibold leading-tight tracking-tight text-lav-50 sm:text-5xl lg:text-6xl"
              style={{ animationDelay: "80ms" }}
            >
              La gestión de licencias urbanísticas,{" "}
              <span className="text-gradient-accent">
                por fin a la altura de su responsabilidad legal
              </span>
            </h1>
            <p
              className="animate-fade-in-up mt-6 max-w-xl text-base leading-relaxed text-lav-200 sm:text-lg"
              style={{ animationDelay: "160ms" }}
            >
              CuraduriAPP es el sistema con el que una Curaduría Urbana o una Secretaría de
              Planeación recupera el control de sus trámites: todo en línea, todo trazable, todo
              verificable y con inteligencia artificial acompañando cada etapa del expediente.
            </p>
            <div
              className="animate-fade-in-up mt-8 flex flex-col gap-3 sm:flex-row"
              style={{ animationDelay: "240ms" }}
            >
              <a
                href="#registro"
                className="inline-flex items-center justify-center gap-2 rounded-md bg-turq-500 px-6 py-3 text-sm font-semibold text-indigo-x-950 shadow-lg shadow-turq-500/20 transition hover:bg-turq-400"
              >
                Quiero conocerlo primero <ArrowRight className="h-4 w-4" aria-hidden />
              </a>
              <a
                href="#ia"
                className="inline-flex items-center justify-center rounded-md border border-lav-400/30 px-6 py-3 text-sm font-medium text-lav-100 transition hover:bg-lav-50/5"
              >
                Ver el copiloto de IA
              </a>
            </div>
          </div>
          <div className="animate-fade-in-up relative" style={{ animationDelay: "200ms" }}>
            <div
              className="pointer-events-none absolute -inset-4 rounded-2xl bg-gradient-to-br from-turq-500/30 via-peri-500/10 to-transparent blur-2xl"
              aria-hidden
            />
            <div className="relative">
              <DashboardPreview />
              <p className="mt-3 text-center text-xs text-lav-400">
                Vista ilustrativa — no representa la interfaz final del sistema.
              </p>
            </div>
          </div>
        </div>
      </header>

      <section id="ia" className="relative mx-auto max-w-6xl px-5 py-20 scroll-mt-8">
        <Reveal>
          <span className="inline-flex items-center gap-2 rounded-full border border-turq-500/30 bg-turq-500/10 px-3 py-1 text-xs font-medium uppercase tracking-wider text-turq-700">
            <Sparkles className="h-3.5 w-3.5" aria-hidden />
            Inteligencia artificial
          </span>
          <h2 className="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-indigo-x-900 sm:text-4xl">
            Un copiloto de IA en cada paso del expediente
          </h2>
          <p className="mt-4 max-w-2xl text-base leading-relaxed text-lav-600">
            No es una casilla de "IA" pegada encima del sistema — acompaña el proceso real, desde
            que se radica hasta que se responde al ciudadano.
          </p>
        </Reveal>
        <div className="mt-12 grid gap-5 lg:grid-cols-3">
          {iaFeatures.map(({ icon: Icon, title, text, featured }, i) => (
            <Reveal key={title} delay={i * 80} className={featured ? "lg:col-span-2" : ""}>
              <article className="group relative h-full overflow-hidden rounded-xl border border-lav-200 bg-card p-6 transition hover:-translate-y-1 hover:border-turq-500/50 hover:shadow-lg hover:shadow-turq-500/10">
                <div
                  className="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-turq-500/0 blur-2xl transition group-hover:bg-turq-500/10"
                  aria-hidden
                />
                <div className="relative flex h-11 w-11 items-center justify-center rounded-lg bg-turq-500/10 text-turq-600">
                  <Icon className="h-5.5 w-5.5" aria-hidden />
                </div>
                <h3 className="relative mt-4 text-base font-semibold text-indigo-x-900">{title}</h3>
                <p className="relative mt-2 text-sm leading-relaxed text-lav-600">{text}</p>
              </article>
            </Reveal>
          ))}
        </div>
      </section>

      <section className="surface-dark relative overflow-hidden">
        <div
          className="pointer-events-none absolute left-1/2 top-0 h-[36rem] w-[60rem] -translate-x-1/2 rounded-full bg-turq-500/10 blur-3xl"
          aria-hidden
        />
        <div className="relative mx-auto max-w-6xl px-5 py-20">
          <div className="grid items-center gap-10 lg:grid-cols-2">
            <Reveal>
              <h2 className="max-w-2xl text-3xl font-semibold tracking-tight text-lav-50 sm:text-4xl">
                Lo que más cambia con CuraduriAPP
              </h2>
              <p className="mt-4 max-w-2xl text-base leading-relaxed text-lav-200">
                Cada función responde a algo que hoy no se puede hacer, o que cuesta demasiado
                esfuerzo hacer bien.
              </p>
            </Reveal>
            <Reveal delay={120} className="relative mx-auto w-full max-w-xs lg:max-w-sm">
              <div
                className="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-turq-500/25 via-peri-500/10 to-transparent blur-2xl"
                aria-hidden
              />
              <img
                src={tabletPreview}
                alt="Vista de referencia de CuraduriAPP en una tablet"
                className="relative w-full rounded-2xl border border-peri-700/50 shadow-2xl"
              />
            </Reveal>
          </div>
          <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {diferenciadores.map(({ icon: Icon, title, text, featured }, i) => (
              <Reveal key={title} delay={i * 70} className={featured ? "lg:col-span-2" : ""}>
                <article className="group relative h-full overflow-hidden rounded-xl border border-peri-700/50 bg-indigo-x-900/50 p-6 transition hover:-translate-y-1 hover:border-turq-500/40">
                  <div
                    className="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-turq-500/0 blur-2xl transition group-hover:bg-turq-500/20"
                    aria-hidden
                  />
                  <Icon className="relative h-6 w-6 text-turq-400" aria-hidden />
                  <h3 className="relative mt-4 text-base font-semibold text-lav-50">{title}</h3>
                  <p className="relative mt-2 text-sm leading-relaxed text-lav-200">{text}</p>
                </article>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-5 py-20">
        <Reveal>
          <div className="rounded-xl border border-lav-200 bg-card p-8 sm:p-10">
            <Building2 className="h-7 w-7 text-turq-600" aria-hidden />
            <h2 className="mt-4 text-2xl font-semibold tracking-tight text-indigo-x-900 sm:text-3xl">
              Pensado también para Secretarías de Planeación
            </h2>
            <p className="mt-4 text-base leading-relaxed text-lav-600">
              El sistema no se limita a lo que tramita una curaduría tradicional: contempla también
              las actuaciones propias de una secretaría de planeación municipal, incluidas las
              licencias de intervención y ocupación del espacio público, con el mismo control de
              términos, la misma trazabilidad y la misma capacidad de expedir actos administrativos
              verificables.
            </p>
          </div>
        </Reveal>
      </section>

      <section className="surface-dark">
        <div className="mx-auto max-w-6xl px-5 py-20">
          <Reveal>
            <h2 className="text-3xl font-semibold tracking-tight text-lav-50 sm:text-4xl">
              El recorrido completo del expediente
            </h2>
          </Reveal>
          <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {flujo.map((f, i) => (
              <Reveal key={f.paso} delay={i * 90}>
                <div className="rounded-lg border-l-2 border-turq-500 bg-indigo-x-900/50 p-5">
                  <span className="text-xs font-semibold tracking-widest text-peri-300">
                    {f.paso}
                  </span>
                  <h3 className="mt-2 text-base font-semibold text-lav-50">{f.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-lav-200">{f.text}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section id="registro" className="mx-auto max-w-4xl scroll-mt-8 px-5 py-20">
        <Reveal>
          <div className="text-center">
            <h2 className="text-3xl font-semibold tracking-tight text-indigo-x-900 sm:text-4xl">
              Sé de los primeros en conocerlo
            </h2>
            <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-lav-600">
              Estamos construyendo CuraduriAPP con equipo con mas de 20 años de experiencia en el
              sector, implementando soluciones digitales en las Curadurías Urbanas del pais. Déjanos
              tus datos y te contactaremos para mostrarte cada avance, escuchar tus necesidades y
              ayudarte a implementar CuraduriAPP en tu organización.
            </p>
          </div>
        </Reveal>
        <Reveal delay={120}>
          <div className="mx-auto mt-10 max-w-4xl rounded-xl border border-peri-700/60 bg-indigo-x-950/95 p-6 shadow-xl sm:p-8">
            <LeadForm />
          </div>
        </Reveal>
      </section>

      <footer className="bg-indigo-x-950 py-10">
        <div className="mx-auto flex max-w-6xl flex-col gap-3 px-5 text-sm text-lav-300 sm:flex-row sm:items-center sm:justify-between">
          <span className="font-semibold text-lav-100">CuraduriAPP</span>
          <span>info@css-sas.com · +57 301 7560109 · Colombia</span>
          <span>© {year} CSS SAS - CuraduriAPP</span>
        </div>
      </footer>
    </div>
  );
}
