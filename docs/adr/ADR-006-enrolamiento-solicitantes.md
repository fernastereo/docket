# ADR-006: Radicación en línea y enrolamiento de solicitantes

**Estado**: Aceptada
**Fecha**: 2026-07-21

## Contexto

El portal ciudadano incluye **radicación en línea desde el MVP**, además de la
radicación presencial por ventanilla que hace un funcionario. Se busca que todo
solicitante termine siendo usuario de la plataforma.

## Decisión

**Todo solicitante es un usuario.** Dos orígenes:

1. **Auto-registro en línea**: el ciudadano crea su cuenta y radica desde el
   subdominio de la curaduría.
2. **Ventanilla**: el funcionario radica y captura los datos del solicitante.
   El sistema busca en la central por documento de identidad:
   - Si existe → asocia la identidad existente al expediente (autocompleta
     datos básicos; no revela información de otras curadurías).
   - Si no existe → crea la cuenta en estado **pendiente de activación** y
     dispara el enrolamiento diferido.

**Enrolamiento diferido (versión técnica de la "contraseña temporal")**:
cuenta creada sin contraseña utilizable + token de activación de un solo uso
con vencimiento. Vías de entrega:
- Enlace de activación por email.
- Código por SMS/WhatsApp al celular.
- Código impreso en el recibo de radicación (para solicitantes sin email);
  activación posterior con documento + código en la web.

La cuenta pendiente de activación **no bloquea el trámite**: el expediente
avanza normalmente.

## Consecuencias

- Nadie manipula contraseñas en claro; audit limpio. En Laravel: signed URLs /
  password broker / tokens propios.
- Se requiere validación de formato de documento (CC, NIT, CE) y estrategia de
  deduplicación por documento.
- Todo solicitante, venga de donde venga, puede terminar consultando su
  expediente en línea (mejora de servicio vendible).
