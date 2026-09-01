import {
  LayoutDashboard,
  FolderOpen,
  Users,
  BarChart3,
  Settings,
  Search,
  Bell,
} from "lucide-react";

/**
 * Vista ilustrativa de cómo lucirá el sistema por dentro — no es una
 * captura real ni representa la interfaz final, es un layout de referencia
 * para dar una idea de forma en la landing (hero).
 */

const sidebarItems = [
  { icon: LayoutDashboard, active: true },
  { icon: FolderOpen, active: false },
  { icon: Users, active: false },
  { icon: BarChart3, active: false },
  { icon: Settings, active: false },
];

const stats = [
  { label: "Radicados este mes", value: "24" },
  { label: "En trámite", value: "63" },
  { label: "Próximos a vencer", value: "5" },
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
] as const;

const pillClass: Record<string, string> = {
  peri: "bg-peri-500/10 text-peri-700",
  indigo: "bg-indigo-x-500/10 text-indigo-x-700",
  turq: "bg-turq-500/15 text-turq-700",
};

export function DashboardPreview() {
  return (
    <div className="overflow-hidden rounded-xl border border-lav-200 bg-white shadow-2xl">
      <div className="flex items-center gap-1.5 border-b border-lav-200 bg-lav-50 px-4 py-2.5">
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="ml-3 truncate rounded-md bg-white px-3 py-1 text-[11px] text-lav-500">
          curaduria.app/expedientes
        </span>
      </div>

      <div className="flex">
        <div className="flex w-12 flex-col items-center gap-3 border-r border-lav-200 bg-lav-50 py-4">
          {sidebarItems.map(({ icon: Icon, active }, i) => (
            <div
              key={i}
              className={`flex h-8 w-8 items-center justify-center rounded-lg ${
                active ? "bg-turq-500/15 text-turq-600" : "text-lav-400"
              }`}
            >
              <Icon className="h-4 w-4" aria-hidden />
            </div>
          ))}
        </div>

        <div className="min-w-0 flex-1 p-4 sm:p-5">
          <div className="flex items-center justify-between gap-3">
            <div className="flex min-w-0 flex-1 items-center gap-2 rounded-md border border-lav-200 bg-lav-50 px-2.5 py-1.5">
              <Search className="h-3.5 w-3.5 shrink-0 text-lav-400" aria-hidden />
              <span className="truncate text-[11px] text-lav-400">Buscar expediente…</span>
            </div>
            <Bell className="h-4 w-4 shrink-0 text-lav-400" aria-hidden />
            <span className="h-7 w-7 shrink-0 rounded-full bg-peri-200" aria-hidden />
          </div>

          <div className="mt-4 grid grid-cols-3 gap-2.5">
            {stats.map((s) => (
              <div key={s.label} className="rounded-lg border border-lav-200 bg-lav-50 p-2.5">
                <p className="text-lg font-semibold text-indigo-x-900">{s.value}</p>
                <p className="mt-0.5 truncate text-[10px] leading-tight text-lav-500">{s.label}</p>
              </div>
            ))}
          </div>

          <div className="mt-4 overflow-hidden rounded-lg border border-lav-200">
            <div className="grid grid-cols-[1fr_1.4fr_1fr] gap-2 bg-lav-50 px-3 py-2 text-[10px] font-medium uppercase tracking-wide text-lav-500">
              <span>Radicado</span>
              <span>Solicitante</span>
              <span>Estado</span>
            </div>
            {expedientes.map((e) => (
              <div
                key={e.radicado}
                className="grid grid-cols-[1fr_1.4fr_1fr] items-center gap-2 border-t border-lav-200 px-3 py-2"
              >
                <span className="truncate text-[11px] text-lav-600">{e.radicado}</span>
                <span className="truncate text-[11px] text-indigo-x-900">{e.solicitante}</span>
                <span
                  className={`w-fit truncate rounded-full px-2 py-0.5 text-[10px] font-medium ${pillClass[e.tone]}`}
                >
                  {e.estado}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
