import {
  LayoutDashboard,
  FolderOpen,
  Users,
  BarChart3,
  Settings,
  Search,
  Bell,
  Plus,
  TrendingUp,
  Clock,
  AlertTriangle,
} from "lucide-react";

/**
 * Vista ilustrativa de cómo lucirá el sistema por dentro — no es una
 * captura real ni representa la interfaz final, es un layout de referencia
 * para dar una idea de forma en la landing (hero).
 */

const sidebarItems = [LayoutDashboard, FolderOpen, Users, BarChart3, Settings];

const stats = [
  { label: "Radicados este mes", value: "24", trend: "+12%", icon: TrendingUp, tone: "turq" },
  { label: "En trámite", value: "63", trend: "En curso", icon: Clock, tone: "peri" },
  {
    label: "Próximos a vencer",
    value: "5",
    trend: "Atención",
    icon: AlertTriangle,
    tone: "indigo",
  },
] as const;

const barData = [
  { label: "Expedidas", value: 42, tone: "bg-turq-500" },
  { label: "En estudio", value: 34, tone: "bg-peri-500" },
  { label: "Radicado", value: 18, tone: "bg-lav-400" },
  { label: "Con observaciones", value: 9, tone: "bg-indigo-x-500" },
];

const expedientes = [
  { radicado: "2026-0341", solicitante: "Ana Rodríguez", estado: "En estudio", tone: "peri" },
  {
    radicado: "2026-0338",
    solicitante: "Constructora Alba",
    estado: "Con observaciones",
    tone: "indigo",
  },
  { radicado: "2026-0335", solicitante: "Jorge Salazar", estado: "Expedida", tone: "turq" },
  { radicado: "2026-0329", solicitante: "Predios del Norte", estado: "En estudio", tone: "peri" },
  { radicado: "2026-0322", solicitante: "Mónica Duarte", estado: "Radicado", tone: "lav" },
] as const;

type Tone = "turq" | "peri" | "indigo" | "lav";

const toneStyles: Record<Tone, { pill: string; avatar: string; icon: string }> = {
  turq: {
    pill: "bg-turq-500/15 text-turq-700",
    avatar: "bg-turq-200 text-turq-800",
    icon: "bg-turq-500/15 text-turq-600",
  },
  peri: {
    pill: "bg-peri-500/15 text-peri-700",
    avatar: "bg-peri-200 text-peri-800",
    icon: "bg-peri-500/15 text-peri-600",
  },
  indigo: {
    pill: "bg-indigo-x-500/15 text-indigo-x-700",
    avatar: "bg-indigo-x-200 text-indigo-x-800",
    icon: "bg-indigo-x-500/15 text-indigo-x-600",
  },
  lav: {
    pill: "bg-lav-300/50 text-lav-700",
    avatar: "bg-lav-300 text-lav-800",
    icon: "bg-lav-300/50 text-lav-700",
  },
};

export function DashboardPreview() {
  return (
    <div className="overflow-hidden rounded-2xl border border-lav-200 bg-white shadow-2xl">
      <div className="flex items-center gap-1.5 border-b border-lav-200 bg-lav-50 px-4 py-3">
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="ml-3 min-w-0 flex-1 truncate rounded-md bg-white px-3 py-1 text-xs text-lav-500">
          curaduria.app/expedientes
        </span>
      </div>

      <div className="flex">
        <div className="flex w-14 flex-col items-center gap-4 border-r border-lav-200 bg-lav-50 py-5">
          <span
            className="h-7 w-7 rounded-lg bg-gradient-to-br from-turq-400 to-peri-500"
            aria-hidden
          />
          {sidebarItems.map((Icon, i) => (
            <div
              key={i}
              className={`flex h-9 w-9 items-center justify-center rounded-lg ${
                i === 0 ? "bg-turq-500/15 text-turq-600" : "text-lav-400"
              }`}
            >
              <Icon className="h-4.5 w-4.5" aria-hidden />
            </div>
          ))}
        </div>

        <div className="min-w-0 flex-1 p-5 sm:p-6">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h4 className="text-base font-semibold text-indigo-x-900">Panel de expedientes</h4>
              <p className="text-xs text-lav-500">Curaduría Urbana No. 2 de Barranquilla</p>
            </div>
            <div className="hidden items-center gap-2 sm:flex">
              <Bell className="h-4 w-4 text-lav-400" aria-hidden />
              <span
                className="h-8 w-8 rounded-full bg-gradient-to-br from-peri-300 to-indigo-x-300"
                aria-hidden
              />
            </div>
          </div>

          <div className="mt-4 flex items-center gap-2 rounded-md border border-lav-200 bg-lav-50 px-3 py-2">
            <Search className="h-3.5 w-3.5 shrink-0 text-lav-400" aria-hidden />
            <span className="min-w-0 flex-1 truncate text-xs text-lav-400">
              Buscar por radicado, solicitante o predio…
            </span>
            <span className="ml-auto inline-flex shrink-0 items-center gap-1 rounded-md bg-turq-500 px-2.5 py-1 text-[11px] font-semibold text-indigo-x-950">
              <Plus className="h-3 w-3" aria-hidden />
              Nuevo
            </span>
          </div>

          <div className="mt-4 grid grid-cols-3 gap-3">
            {stats.map((s) => {
              const t = toneStyles[s.tone];
              return (
                <div
                  key={s.label}
                  className="min-w-0 rounded-lg border border-lav-200 bg-lav-50 p-3"
                >
                  <div
                    className={`inline-flex h-6 w-6 items-center justify-center rounded-md ${t.icon}`}
                  >
                    <s.icon className="h-3.5 w-3.5" aria-hidden />
                  </div>
                  <p className="mt-2 text-xl font-semibold text-indigo-x-900">{s.value}</p>
                  <p className="truncate text-[10px] leading-tight text-lav-500">{s.label}</p>
                </div>
              );
            })}
          </div>

          <div className="mt-4 rounded-lg border border-lav-200 p-3">
            <p className="text-[11px] font-medium text-lav-600">Trámites por estado</p>
            <div className="mt-2.5 space-y-2">
              {barData.map((b) => (
                <div key={b.label} className="flex items-center gap-2">
                  <span className="w-24 shrink-0 truncate text-[10px] text-lav-500">{b.label}</span>
                  <div className="h-2 flex-1 overflow-hidden rounded-full bg-lav-100">
                    <div
                      className={`h-full rounded-full ${b.tone}`}
                      style={{ width: `${(b.value / 42) * 100}%` }}
                    />
                  </div>
                  <span className="w-5 shrink-0 text-right text-[10px] text-lav-500">
                    {b.value}
                  </span>
                </div>
              ))}
            </div>
          </div>

          <div className="mt-4 overflow-hidden rounded-lg border border-lav-200">
            <div className="grid grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,1fr)] gap-2 bg-lav-50 px-3 py-2 text-[10px] font-medium uppercase tracking-wide text-lav-500">
              <span>Radicado</span>
              <span>Solicitante</span>
              <span>Estado</span>
            </div>
            {expedientes.map((e) => {
              const t = toneStyles[e.tone];
              return (
                <div
                  key={e.radicado}
                  className="grid grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,1fr)] items-center gap-2 border-t border-lav-200 px-3 py-2"
                >
                  <span className="min-w-0 truncate text-[11px] text-lav-600">{e.radicado}</span>
                  <span className="flex min-w-0 items-center gap-1.5">
                    <span
                      className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[9px] font-semibold ${t.avatar}`}
                    >
                      {e.solicitante.charAt(0)}
                    </span>
                    <span className="truncate text-[11px] text-indigo-x-900">{e.solicitante}</span>
                  </span>
                  <span
                    className={`min-w-0 truncate rounded-full px-2 py-0.5 text-[10px] font-medium ${t.pill}`}
                  >
                    {e.estado}
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
