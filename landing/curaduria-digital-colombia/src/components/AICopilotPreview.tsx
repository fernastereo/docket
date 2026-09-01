import {
  LayoutDashboard,
  FolderOpen,
  Users,
  BarChart3,
  Settings,
  Sparkles,
  FileText,
  Send,
  MapPin,
} from "lucide-react";

/**
 * Segunda versión exploratoria del mockup del hero — no reemplaza a
 * DashboardPreview.tsx. Muestra la consulta normativa en lenguaje natural
 * (RAG): una pregunta real sobre la norma, respondida con cita de la
 * fuente exacta, con el expediente activo visible al lado para dar
 * contexto — no un chatbot genérico flotando sobre la nada.
 */

const sidebarItems = [LayoutDashboard, FolderOpen, Users, BarChart3, Settings];

export function AICopilotPreview() {
  return (
    <div className="overflow-hidden rounded-2xl border border-lav-200 bg-white shadow-2xl">
      <div className="flex items-center gap-1.5 border-b border-lav-200 bg-lav-50 px-4 py-3">
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="h-2.5 w-2.5 rounded-full bg-lav-300" aria-hidden />
        <span className="ml-3 truncate rounded-md bg-white px-3 py-1 text-xs text-lav-500">
          curaduria.app/expedientes/2026-0341/normativa
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
                i === 1 ? "bg-turq-500/15 text-turq-600" : "text-lav-400"
              }`}
            >
              <Icon className="h-4.5 w-4.5" aria-hidden />
            </div>
          ))}
        </div>

        <div className="grid min-w-0 flex-1 sm:grid-cols-[1.6fr_1fr]">
          {/* Consulta normativa */}
          <div className="flex min-w-0 flex-col border-b border-lav-200 p-5 sm:border-b-0 sm:border-r">
            <p className="text-sm font-semibold text-indigo-x-900">Consulta normativa</p>
            <p className="text-xs text-lav-500">Expediente 2026-0341 · Estudio Arquitectónico</p>

            <div className="mt-4 space-y-3">
              <div className="flex justify-end">
                <div className="max-w-[85%] rounded-2xl rounded-br-md bg-indigo-x-800 px-3.5 py-2.5 text-[13px] leading-relaxed text-white">
                  ¿Cuál es el retiro lateral mínimo para una edificación de 4 pisos en zona
                  residencial?
                </div>
              </div>

              <div className="flex items-start gap-2">
                <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-turq-400 to-peri-500 text-white">
                  <Sparkles className="h-3 w-3" aria-hidden />
                </span>
                <div className="max-w-[90%] space-y-2">
                  <div className="rounded-2xl rounded-tl-md border border-lav-200 bg-lav-50 px-3.5 py-2.5 text-[13px] leading-relaxed text-indigo-x-800">
                    Para edificaciones de 4 pisos en zona residencial, el retiro lateral mínimo es
                    de <strong>3 metros</strong>. Si el predio colinda con una edificación existente
                    sin retiro, aplica la norma de medianería del mismo artículo.
                  </div>
                  <div className="rounded-lg border-l-4 border-turq-500 bg-turq-50 px-3 py-2">
                    <p className="flex items-center gap-1.5 text-[11px] font-semibold text-turq-800">
                      <FileText className="h-3 w-3" aria-hidden />
                      POT · Acuerdo 011 de 2019, Art. 245
                    </p>
                    <p className="mt-1 text-[11px] italic leading-relaxed text-lav-600">
                      "...las edificaciones de cuatro (4) o más pisos deberán guardar un retiro
                      lateral no inferior a tres (3) metros, salvo lo dispuesto para predios en
                      medianería..."
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-4 flex items-center gap-2 rounded-full border border-lav-200 bg-lav-50 py-2 pl-4 pr-2">
              <span className="flex-1 truncate text-[12px] text-lav-400">
                Preguntar sobre normativa aplicable a este expediente…
              </span>
              <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-turq-500 text-indigo-x-950">
                <Send className="h-3.5 w-3.5" aria-hidden />
              </span>
            </div>
          </div>

          {/* Contexto del expediente */}
          <div className="min-w-0 bg-lav-50 p-5">
            <p className="text-[11px] font-medium uppercase tracking-wide text-lav-500">
              Expediente activo
            </p>
            <div className="mt-2 rounded-lg border border-lav-200 bg-white p-3">
              <p className="text-sm font-semibold text-indigo-x-900">2026-0341</p>
              <p className="mt-0.5 text-[11px] text-lav-500">Construcción · Obra nueva</p>
              <div className="mt-2.5 flex items-start gap-1.5 text-[11px] text-lav-600">
                <MapPin className="mt-0.5 h-3 w-3 shrink-0 text-lav-400" aria-hidden />
                Mz. 4 Lote 12, Bucaramanga
              </div>
              <span className="mt-2.5 inline-block rounded-full bg-peri-500/15 px-2 py-0.5 text-[10px] font-medium text-peri-700">
                En estudio
              </span>
            </div>

            <p className="mt-4 text-[11px] font-medium uppercase tracking-wide text-lav-500">
              Fuentes consultadas
            </p>
            <div className="mt-2 space-y-1.5">
              <div className="flex items-center gap-1.5 rounded-md border border-lav-200 bg-white px-2.5 py-1.5 text-[11px] text-lav-600">
                <FileText className="h-3 w-3 shrink-0 text-turq-500" aria-hidden />
                POT Bucaramanga — Acuerdo 011/2019
              </div>
              <div className="flex items-center gap-1.5 rounded-md border border-lav-200 bg-white px-2.5 py-1.5 text-[11px] text-lav-600">
                <FileText className="h-3 w-3 shrink-0 text-peri-500" aria-hidden />
                NSR-10 · Título K
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
