import { useState } from "react";
import { z } from "zod";
import { toast } from "sonner";
import { Loader2, Send, CheckCircle2 } from "lucide-react";
import { submitLead } from "@/lib/brevo";

export type LeadFormData = {
  nombre: string;
  curaduria: string;
  email: string;
};

const leadSchema = z.object({
  nombre: z.string().trim().min(2, "Ingresa tu nombre completo").max(100),
  curaduria: z.string().trim().min(2, "Ingresa la curaduría o entidad").max(150),
  email: z.string().trim().email("Correo electrónico inválido").max(255),
});

const EMPTY: LeadFormData = {
  nombre: "",
  curaduria: "",
  email: "",
};

/**
 * Punto único de envío del formulario — llama al server function que
 * registra el lead en Brevo (src/lib/brevo.ts). El API key de Brevo nunca
 * llega al navegador: toda la llamada ocurre en el servidor.
 */
async function onSubmit(data: LeadFormData): Promise<void> {
  await submitLead({ data });
}

const fieldClass =
  "w-full rounded-md border border-peri-700/60 bg-indigo-x-950/60 px-3.5 py-2.5 text-sm text-lav-50 placeholder:text-lav-400/70 outline-none transition focus:border-turq-500 focus:ring-2 focus:ring-turq-500/30";

const labelClass = "mb-1.5 block text-xs font-medium uppercase tracking-wider text-lav-200";

export function LeadForm() {
  const [values, setValues] = useState<LeadFormData>(EMPTY);
  const [errors, setErrors] = useState<Partial<Record<keyof LeadFormData, string>>>({});
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const set = (key: keyof LeadFormData) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setValues((v) => ({ ...v, [key]: e.target.value }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const parsed = leadSchema.safeParse(values);
    if (!parsed.success) {
      const next: Partial<Record<keyof LeadFormData, string>> = {};
      for (const issue of parsed.error.issues) {
        const key = issue.path[0] as keyof LeadFormData;
        if (!next[key]) next[key] = issue.message;
      }
      setErrors(next);
      return;
    }
    setErrors({});
    setLoading(true);
    try {
      await onSubmit(parsed.data as LeadFormData);
      setDone(true);
      setValues(EMPTY);
      toast.success("Registro recibido. Te contactaremos pronto.");
    } catch {
      toast.error("No pudimos enviar tu registro. Intenta de nuevo.");
    } finally {
      setLoading(false);
    }
  };

  if (done) {
    return (
      <div className="rounded-xl border border-turq-500/30 bg-indigo-x-900/70 p-10 text-center">
        <CheckCircle2 className="mx-auto h-10 w-10 text-turq-400" aria-hidden />
        <h3 className="mt-4 text-xl font-semibold text-lav-50">Gracias por tu interés</h3>
        <p className="mt-2 text-sm text-lav-200">
          Registramos tus datos. Te escribiremos con los avances y la invitación al acceso
          anticipado.
        </p>
        <button
          type="button"
          onClick={() => setDone(false)}
          className="mt-6 text-sm font-medium text-turq-400 underline underline-offset-4"
        >
          Registrar otra entidad
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="grid gap-5 sm:grid-cols-3">
      <Field id="nombre" label="Nombre completo" error={errors.nombre}>
        <input
          id="nombre"
          name="nombre"
          className={fieldClass}
          value={values.nombre}
          onChange={set("nombre")}
          placeholder="Ana María Rodríguez"
          autoComplete="name"
        />
      </Field>
      <Field id="curaduria" label="Curaduría o entidad" error={errors.curaduria}>
        <input
          id="curaduria"
          name="curaduria"
          className={fieldClass}
          value={values.curaduria}
          onChange={set("curaduria")}
          placeholder="Curaduría Urbana No. 2 / Secretaría de Planeación"
        />
      </Field>
      <Field id="email" label="Correo electrónico" error={errors.email}>
        <input
          id="email"
          name="email"
          type="email"
          className={fieldClass}
          value={values.email}
          onChange={set("email")}
          placeholder="nombre@curaduria.gov.co"
          autoComplete="email"
        />
      </Field>
      <div className="sm:col-span-3 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-xs text-lav-300">
          Usaremos tus datos únicamente para contactarte sobre CuraduriAPP.
        </p>
        <button
          type="submit"
          disabled={loading}
          className="inline-flex w-full items-center justify-center gap-2 rounded-md bg-turq-500 px-6 py-3 text-sm font-semibold text-indigo-x-950 transition hover:bg-turq-400 disabled:opacity-60 sm:w-auto"
        >
          {loading ? (
            <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
          ) : (
            <Send className="h-4 w-4" aria-hidden />
          )}
          Quiero saber más
        </button>
      </div>
    </form>
  );
}

function Field({
  id,
  label,
  error,
  children,
}: {
  id: string;
  label: string;
  error?: string | undefined;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label htmlFor={id} className={labelClass}>
        {label}
      </label>
      {children}
      {error ? <p className="mt-1.5 text-xs text-turq-300">{error}</p> : null}
    </div>
  );
}
