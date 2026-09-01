import { Sparkles, FileCheck2, Wand2, Loader2, ScanSearch } from "lucide-react";

/**
 * Segunda versión exploratoria del mockup del hero — no reemplaza a
 * DashboardPreview.tsx. En vez de un panel de gestión general, se enfoca
 * en mostrar el copiloto de IA en acción sobre un documento radicado:
 * extracción de campos + una sugerencia de observación generada.
 * Paleta más amplia (colores estándar de Tailwind), no solo la de marca.
 */

const extractedFields = [
  { label: "Nombre", value: "Ana Rodríguez", color: "blue" as const, top: "18%", left: "30%" },
  { label: "Cédula", value: "1.098.XXX.XXX", color: "purple" as const, top: "34%", left: "50%" },
  { label: "Predio", value: "Mz. 4 Lote 12", color: "emerald" as const, top: "52%", left: "28%" },
  { label: "Área", value: "312 m²", color: "amber" as const, top: "68%", left: "48%" },
];

const dotColor: Record<(typeof extractedFields)[number]["color"], string> = {
  blue: "border-blue-400 bg-blue-50 text-blue-700",
  purple: "border-purple-400 bg-purple-50 text-purple-700",
  emerald: "border-emerald-400 bg-emerald-50 text-emerald-700",
  amber: "border-amber-400 bg-amber-50 text-amber-700",
};

export function AICopilotPreview() {
  return (
    <div className="overflow-hidden rounded-2xl border border-lav-200 bg-white shadow-2xl">
      <div className="flex items-center gap-1.5 border-b border-lav-200 bg-lav-50 px-4 py-3">
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="ml-3 flex items-center gap-1.5 truncate rounded-md bg-white px-3 py-1 text-xs text-lav-500">
          <ScanSearch className="h-3 w-3" aria-hidden />
          curaduria.app/expedientes/2026-0341/copiloto
        </span>
      </div>

      <div className="grid sm:grid-cols-[1.1fr_1fr]">
        {/* Documento radicado con extracción de campos */}
        <div className="border-b border-lav-200 bg-lav-50 p-4 sm:border-b-0 sm:border-r sm:p-5">
          <p className="text-[11px] font-medium uppercase tracking-wide text-lav-500">
            Documento radicado
          </p>
          <div className="relative mt-2 aspect-[3/4] w-full rounded-lg border border-lav-200 bg-white p-3 shadow-sm">
            <div className="space-y-1.5 opacity-70">
              <div className="h-1.5 w-3/4 rounded bg-lav-200" />
              <div className="h-1.5 w-1/2 rounded bg-lav-200" />
              <div className="mt-3 h-1.5 w-full rounded bg-lav-100" />
              <div className="h-1.5 w-5/6 rounded bg-lav-100" />
              <div className="h-1.5 w-2/3 rounded bg-lav-100" />
              <div className="mt-3 h-1.5 w-full rounded bg-lav-100" />
              <div className="h-1.5 w-4/6 rounded bg-lav-100" />
            </div>

            {extractedFields.map((f) => (
              <div
                key={f.label}
                className="absolute flex -translate-x-1/2 -translate-y-1/2 items-center gap-1"
                style={{ top: f.top, left: f.left }}
              >
                <span
                  className={`whitespace-nowrap rounded-md border px-1.5 py-0.5 text-[9px] font-medium shadow-sm ${dotColor[f.color]}`}
                >
                  {f.label}: {f.value}
                </span>
              </div>
            ))}
          </div>
          <p className="mt-2 flex items-center gap-1.5 text-[10px] text-lav-500">
            <FileCheck2 className="h-3 w-3 text-emerald-500" aria-hidden />4 campos extraídos
            automáticamente
          </p>
        </div>

        {/* Panel del copiloto */}
        <div className="flex flex-col p-4 sm:p-5">
          <div className="flex items-center gap-2">
            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-turq-400 to-peri-500 text-white">
              <Sparkles className="h-3.5 w-3.5" aria-hidden />
            </span>
            <p className="text-xs font-semibold text-indigo-x-900">Copiloto CuraduriAPP</p>
          </div>

          <div className="mt-3 space-y-2.5">
            <div className="flex items-center gap-1.5 text-[10px] text-lav-400">
              <Loader2 className="h-3 w-3 animate-spin" aria-hidden />
              Analizando documentación del expediente…
            </div>

            <div className="rounded-lg rounded-tl-sm bg-lav-100 p-3 text-[11px] leading-relaxed text-indigo-x-800">
              Revisé los documentos radicados: falta el certificado de tradición y libertad vigente.
              Encontré 4 campos que puedo prellenar en el expediente.
            </div>

            <div className="rounded-lg border border-turq-200 bg-turq-50 p-3">
              <p className="flex items-center gap-1.5 text-[10px] font-semibold text-turq-800">
                <Wand2 className="h-3 w-3" aria-hidden />
                Sugerencia de Acta de Observaciones
              </p>
              <p className="mt-1.5 text-[11px] leading-relaxed text-indigo-x-800">
                "Se requiere allegar certificado de tradición y libertad con fecha de expedición no
                mayor a 3 meses…"
              </p>
              <div className="mt-2 flex gap-2">
                <span className="rounded-md bg-turq-500 px-2.5 py-1 text-[10px] font-semibold text-indigo-x-950">
                  Usar sugerencia
                </span>
                <span className="rounded-md border border-lav-300 px-2.5 py-1 text-[10px] font-medium text-lav-600">
                  Editar
                </span>
              </div>
            </div>
          </div>

          <div className="mt-auto flex gap-2 pt-4">
            <span className="h-1.5 flex-1 rounded-full bg-blue-400" />
            <span className="h-1.5 flex-1 rounded-full bg-purple-400" />
            <span className="h-1.5 flex-1 rounded-full bg-emerald-400" />
            <span className="h-1.5 flex-1 rounded-full bg-amber-400" />
          </div>
        </div>
      </div>
    </div>
  );
}
