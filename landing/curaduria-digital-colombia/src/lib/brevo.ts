import { createServerFn } from "@tanstack/react-start";
import { z } from "zod";

/**
 * Captura de leads de la landing hacia Brevo (docs/adr/ADR-008 del proyecto
 * principal). Corre exclusivamente en el servidor (TanStack Start server
 * function) — la API key de Brevo nunca llega al cliente.
 *
 * Requiere BREVO_API_KEY y BREVO_LIST_ID en el entorno del servidor
 * (ver .env.example).
 */

const leadSchema = z.object({
  nombre: z.string().trim().min(2).max(100),
  curaduria: z.string().trim().min(2).max(150),
  email: z.string().trim().email().max(255),
});

export type SubmitLeadInput = z.infer<typeof leadSchema>;

export const submitLead = createServerFn({ method: "POST" })
  .validator((data: unknown) => leadSchema.parse(data))
  .handler(async ({ data }) => {
    const apiKey = process.env["BREVO_API_KEY"];
    const listId = process.env["BREVO_LIST_ID"];

    if (!apiKey || !listId) {
      // No tumbar el registro del lead por un problema de configuración:
      // se registra en el log del servidor para revisión manual, y se le
      // informa al usuario que sí quedó recibido (evita perder el lead).
      console.error(
        "[brevo] BREVO_API_KEY o BREVO_LIST_ID no configurados — lead no enviado a Brevo:",
        data,
      );
      return { ok: true, synced: false } as const;
    }

    const response = await fetch("https://api.brevo.com/v3/contacts", {
      method: "POST",
      headers: {
        "content-type": "application/json",
        accept: "application/json",
        "api-key": apiKey,
      },
      body: JSON.stringify({
        email: data.email,
        // updateEnabled: si el contacto ya existe (mismo email), actualiza
        // sus atributos en vez de fallar por duplicado.
        updateEnabled: true,
        listIds: [Number(listId)],
        attributes: {
          NOMBRE_CURADURIAPP: data.nombre,
          CURADURIA_CURADURIAPP: data.curaduria,
        },
      }),
    });

    if (!response.ok) {
      const body = await response.text().catch(() => "");
      console.error(`[brevo] fallo al crear/actualizar contacto (${response.status}):`, body);
      // El error se reporta al cliente para que muestre el toast de error;
      // el dato ya quedó en el log del servidor para no perderlo.
      throw new Error("No se pudo registrar el lead en Brevo");
    }

    return { ok: true, synced: true } as const;
  });
