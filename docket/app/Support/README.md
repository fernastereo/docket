# app/Support

Fronteras del sistema con interfaz + implementación real + fake de test
(ADR-011: interfaces SOLO acá). Candidatos: cliente LLM (ADR-004),
DocumentSigner (ADR-015), integración SNR, Messaging/Brevo (ADR-008),
generación de PDF. Se agregan cuando la integración real se construye —
YAGNI hasta entonces.

También aquí, mientras no tengan mejor casa: piezas transversales que comparten
varios contextos de identidad, como `Mfa/` (TOTP y códigos de recuperación de
plataforma y de empleados).
