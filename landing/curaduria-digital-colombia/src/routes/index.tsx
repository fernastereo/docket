import { createFileRoute } from "@tanstack/react-router";
import {
  Globe,
  Users,
  History,
  Sparkles,
  BellRing,
  QrCode,
  BarChart3,
  DatabaseZap,
  ArrowRightLeft,
  PenTool,
  ArrowRight,
  Building2,
} from "lucide-react";
import { Toaster } from "@/components/ui/sonner";
import { LeadForm } from "@/components/LeadForm";
import heroImage from "@/assets/hero-abstract.jpg";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "CuraduriAPP | Gestión moderna de trámites urbanísticos" },
      {
        name: "description",
        content:
          "Software para Curadurías Urbanas y Secretarías de Planeación en Colombia: radicación en línea, trazabilidad legal, IA y verificación QR. Regístrate para acceso anticipado.",
      },
      { property: "og:title", content: "CuraduriAPP | Gestión moderna de trámites urbanísticos" },
      {
        property: "og:description",
        content:
          "Radicación en línea, portal ciudadano, trazabilidad legal completa e inteligencia artificial para licencias urbanísticas en Colombia.",
      },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: Landing,
});

const diferenciadores = [
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
    icon: History,
    title: "Trazabilidad legal completa",
    text: "Cada expediente tiene su línea de tiempo: quién hizo qué, cuándo y sobre qué documento.",
  },
  {
    icon: Sparkles,
    title: "Inteligencia artificial en cada paso",
    text: "Extracción de datos de escrituras, certificados y cédulas; verificación de completitud; asistente de redacción de actas y resoluciones; consulta de POT y NSR-10 en lenguaje natural.",
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
    icon: BarChart3,
    title: "Analítica operativa",
    text: "Dónde se atascan los trámites, cuánto tarda cada área y la carga real de trabajo de su equipo.",
  },
  {
    icon: DatabaseZap,
    title: "Aislamiento total de datos",
    text: "Cada curaduría opera sobre su propia base de datos independiente. No es una tabla compartida con una columna de cliente: los expedientes de una curaduría nunca conviven con los de otra.",
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
    text: "Técnica, jurídica y estructural, con control de términos.",
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
    <div className="min-h-screen bg-background text-foreground">
      <Toaster position="top-center" />

      <header className="surface-dark">
        <nav className="mx-auto flex max-w-6xl items-center justify-between px-5 py-5">
          <span className="text-lg font-semibold tracking-tight text-lav-50">
            Curaduri<span className="text-turq-400">APP</span>
          </span>
          <a
            href="#registro"
            className="rounded-md border border-turq-500/40 px-4 py-2 text-sm font-medium text-turq-300 transition hover:bg-turq-500/10"
          >
            Acceso anticipado
          </a>
        </nav>

        <div className="mx-auto grid max-w-6xl items-center gap-12 px-5 pb-20 pt-10 lg:grid-cols-2 lg:pb-28 lg:pt-16">
          <div>
            <span className="inline-block rounded-full border border-peri-500/40 bg-peri-900/50 px-3 py-1 text-xs font-medium uppercase tracking-wider text-lav-200">
              Ley 388 de 1997 · Ley 1796 de 2016 · Decreto 1077 de 2015
            </span>
            <h1 className="mt-6 text-4xl font-semibold leading-tight tracking-tight text-lav-50 sm:text-5xl lg:text-6xl">
              La gestión de licencias urbanísticas,{" "}
              <span className="text-gradient-accent">
                por fin a la altura de su responsabilidad legal
              </span>
            </h1>
            <p className="mt-6 max-w-xl text-base leading-relaxed text-lav-200 sm:text-lg">
              CuraduriAPP es el sistema con el que una curaduría urbana o una secretaría de
              planeación recupera el control de sus trámites: todo en línea, todo trazable, todo
              verificable — y con inteligencia artificial acompañando cada etapa del expediente.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <a
                href="#registro"
                className="inline-flex items-center justify-center gap-2 rounded-md bg-turq-500 px-6 py-3 text-sm font-semibold text-indigo-x-950 transition hover:bg-turq-400"
              >
                Quiero conocerlo primero <ArrowRight className="h-4 w-4" aria-hidden />
              </a>
              <a
                href="#solucion"
                className="inline-flex items-center justify-center rounded-md border border-lav-400/30 px-6 py-3 text-sm font-medium text-lav-100 transition hover:bg-lav-50/5"
              >
                Ver qué incluye
              </a>
            </div>
          </div>
          <img
            src={heroImage}
            alt="Representación abstracta del recorrido digital de un expediente de licencia urbanística"
            width={1280}
            height={960}
            className="w-full rounded-xl border border-peri-700/50 shadow-2xl"
          />
        </div>
      </header>

      <section id="solucion" className="mx-auto max-w-6xl px-5 py-20">
        <h2 className="max-w-2xl text-3xl font-semibold tracking-tight text-indigo-x-900 sm:text-4xl">
          Lo que cambia con CuraduriAPP
        </h2>
        <p className="mt-4 max-w-2xl text-base leading-relaxed text-lav-600">
          Cada función responde a algo que hoy no se puede hacer, o que cuesta demasiado esfuerzo
          hacer bien.
        </p>
        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {diferenciadores.map(({ icon: Icon, title, text }) => (
            <article
              key={title}
              className="rounded-xl border border-lav-200 bg-card p-6 transition hover:border-turq-500/40"
            >
              <Icon className="h-6 w-6 text-turq-600" aria-hidden />
              <h3 className="mt-4 text-base font-semibold text-indigo-x-900">{title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-lav-600">{text}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="surface-dark">
        <div className="mx-auto max-w-6xl px-5 py-20">
          <div className="rounded-xl border border-peri-700/50 bg-indigo-x-900/50 p-8 sm:p-10">
            <Building2 className="h-7 w-7 text-turq-400" aria-hidden />
            <h2 className="mt-4 text-2xl font-semibold tracking-tight text-lav-50 sm:text-3xl">
              Pensado también para Secretarías de Planeación
            </h2>
            <p className="mt-4 max-w-3xl text-base leading-relaxed text-lav-200">
              El sistema no se limita a lo que tramita una curaduría tradicional: contempla también
              las actuaciones propias de una secretaría de planeación municipal, incluidas las
              licencias de intervención y ocupación del espacio público, con el mismo control de
              términos, la misma trazabilidad y la misma capacidad de expedir actos administrativos
              verificables.
            </p>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-5 py-20">
        <h2 className="text-3xl font-semibold tracking-tight text-indigo-x-900 sm:text-4xl">
          El recorrido completo del expediente
        </h2>
        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {flujo.map((f) => (
            <div key={f.paso} className="rounded-lg border-l-2 border-turq-500 bg-lav-100/60 p-5">
              <span className="text-xs font-semibold tracking-widest text-peri-600">{f.paso}</span>
              <h3 className="mt-2 text-base font-semibold text-indigo-x-900">{f.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-lav-600">{f.text}</p>
            </div>
          ))}
        </div>
      </section>

      <section id="registro" className="surface-dark scroll-mt-8">
        <div className="mx-auto max-w-4xl px-5 py-20">
          <div className="text-center">
            <h2 className="text-3xl font-semibold tracking-tight text-lav-50 sm:text-4xl">
              Sé de los primeros en conocerlo
            </h2>
            <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-lav-200">
              Estamos construyendo CuraduriAPP junto a curadores y equipos de planeación. Déjanos
              tus datos y te contactaremos para mostrarte el avance y escuchar tus necesidades.
            </p>
          </div>
          <div className="mx-auto mt-10 max-w-4xl rounded-xl border border-peri-700/60 bg-indigo-x-950/40 p-6 sm:p-8">
            <LeadForm />
          </div>
        </div>
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
