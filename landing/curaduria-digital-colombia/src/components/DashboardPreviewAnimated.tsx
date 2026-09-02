import { useEffect, useRef, useState } from "react";
import { Sparkles } from "lucide-react";
import { DashboardPreview } from "@/components/DashboardPreview";

/**
 * Variante animada de DashboardPreview: no la reemplaza, la envuelve.
 * Simula, en bucle, a alguien abriendo el copiloto de IA dentro del
 * expediente y preguntando qué documentación falta — la IA responde en
 * lenguaje natural. Puramente ilustrativo (mismos datos de ejemplo que
 * DashboardPreview). Respeta prefers-reduced-motion: si está activo,
 * muestra directamente el estado final, sin animar.
 */

type Phase = "closed" | "button" | "question" | "thinking" | "answer" | "closing";

const QUESTION = "¿Qué documentación aún falta en el expediente 2026-0341?";
const ANSWER =
  "Para el expediente 2026-0341 aún falta la copia del Certificado de Tradición y Libertad con no más de 30 días de expedida y la fotografía de la valla instalada. Valdría la pena enviar un recordatorio al solicitante. Quieres que redacte un email y lo envíe al solicitante?";

// Duración de cada fase, en ms.
const TYPE_SPEED = 32;
const DURATIONS: Record<Phase, number> = {
  closed: 3000,
  button: 1400,
  question: QUESTION.length * TYPE_SPEED + 500,
  thinking: 1100,
  answer: 4200,
  closing: 700,
};

const ORDER: Phase[] = ["closed", "button", "question", "thinking", "answer", "closing"];

export function DashboardPreviewAnimated() {
  const [phase, setPhase] = useState<Phase>("closed");
  const [typedQuestion, setTypedQuestion] = useState("");
  const reducedMotionRef = useRef(false);

  useEffect(() => {
    reducedMotionRef.current = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reducedMotionRef.current) {
      setPhase("answer");
      setTypedQuestion(QUESTION);
      return;
    }

    let cancelled = false;
    const timers: ReturnType<typeof setTimeout>[] = [];
    let typeInterval: ReturnType<typeof setInterval> | undefined;

    function goTo(next: Phase) {
      if (cancelled) return;
      setPhase(next);

      if (next === "closed") setTypedQuestion("");

      if (next === "question") {
        let i = 0;
        typeInterval = setInterval(() => {
          i++;
          setTypedQuestion(QUESTION.slice(0, i));
          if (i >= QUESTION.length && typeInterval) clearInterval(typeInterval);
        }, TYPE_SPEED);
      }

      const idx = ORDER.indexOf(next);
      const following = ORDER[idx + 1] ?? "closed";
      timers.push(setTimeout(() => goTo(following), DURATIONS[next]));
    }

    goTo("closed");

    return () => {
      cancelled = true;
      timers.forEach(clearTimeout);
      if (typeInterval) clearInterval(typeInterval);
    };
  }, []);

  const buttonVisible = phase !== "closed";
  const panelVisible = phase === "question" || phase === "thinking" || phase === "answer";

  return (
    <div className="relative">
      <DashboardPreview />

      {/* Panel del copiloto */}
      <div
        className={`absolute bottom-14 right-3 w-[87%] max-w-[19rem] origin-bottom-right rounded-xl border border-lav-200 bg-white shadow-2xl transition-all duration-500 ease-out sm:right-4 ${
          panelVisible
            ? "translate-y-0 scale-100 opacity-100"
            : "pointer-events-none translate-y-2 scale-90 opacity-0"
        }`}
      >
        <div className="flex items-center gap-2 border-b border-lav-100 px-3.5 py-2.5">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-turq-400 to-peri-500 text-white">
            <Sparkles className="h-3 w-3" aria-hidden />
          </span>
          <p className="truncate text-[11px] font-semibold text-indigo-x-900">
            Copiloto · Expediente 2026-0341
          </p>
        </div>

        <div className="space-y-2 p-3.5">
          <div className="flex justify-end">
            <div className="max-w-[90%] rounded-xl rounded-br-sm bg-indigo-x-800 px-3 py-2 text-[11.5px] leading-relaxed text-white">
              {typedQuestion}
              {phase === "question" ? <span className="animate-pulse">▍</span> : null}
            </div>
          </div>

          {phase === "thinking" ? (
            <div className="flex items-center gap-1.5 py-1 pl-1">
              <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-lav-300" />
              <span
                className="h-1.5 w-1.5 animate-bounce rounded-full bg-lav-300"
                style={{ animationDelay: "120ms" }}
              />
              <span
                className="h-1.5 w-1.5 animate-bounce rounded-full bg-lav-300"
                style={{ animationDelay: "240ms" }}
              />
            </div>
          ) : null}

          {phase === "answer" ? (
            <div className="animate-fade-in-up rounded-xl rounded-tl-sm border border-lav-200 bg-lav-50 px-3 py-2 text-[11.5px] leading-relaxed text-indigo-x-800">
              {ANSWER}
            </div>
          ) : null}
        </div>
      </div>

      {/* Botón flotante del copiloto */}
      <div
        className={`absolute bottom-3 right-3 h-11 w-11 transition-transform duration-300 sm:right-4 ${
          buttonVisible ? "scale-100" : "scale-0"
        }`}
      >
        <span
          className="absolute inset-0 -z-10 animate-ping rounded-full bg-turq-400/50"
          aria-hidden
        />
        <span className="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-turq-400 to-peri-500 text-white shadow-lg">
          <Sparkles className="h-5 w-5" aria-hidden />
        </span>
      </div>
    </div>
  );
}
