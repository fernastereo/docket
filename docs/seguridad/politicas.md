# Políticas de seguridad — índice

**Estado**: Esqueleto. Cada sección se redacta durante la implementación
(ADR-017). Este archivo es el índice del set de políticas; cuando una sección
crezca se mueve a su propio archivo en `docs/seguridad/`.

Marco: baseline **Ley 1581/2012 + Decreto 1377/2013**, controles técnicos
alineados a **OWASP ASVS** y **CIS Benchmarks** (ver `docs/adr/ADR-017-seguridad-plataforma.md`).

---

## 1. Política maestra de seguridad de la información

_Pendiente de redactar._ Alcance, roles y responsabilidades (responsable de
tratamiento, responsable de seguridad), revisión periódica, relación con los
principios del proyecto (`CLAUDE.md`).

## 2. Control de acceso

_Pendiente de redactar._ Basado en ADR-005 (identidad), ADR-017 capa 4.
Contraseñas (Argon2id, chequeo de brechas), MFA (alcance y roles obligatorios),
ciclo de vida de sesión, RBAC por tenant, acceso privilegiado y break-glass,
acceso de operación por malla VPN.

## 3. Clasificación y manejo de datos

_Pendiente de redactar._ Categorías (datos de identidad, PII de trámite, actos
administrativos, audit log, secretos). Reglas de cifrado en reposo / en
tránsito / a nivel de aplicación, blind index para campos buscables,
minimización en la central (ADR-002/005) y en prompts de LLM (ADR-004).

## 4. Respuesta a incidentes

_Pendiente de redactar._ Niveles de severidad, contacto/on-call, runbooks de
contención (activar "Under Attack", rotar credencial de BD de tenant, revocar
sesiones), plantillas de comunicación (curadurías; SIC + titulares ante brecha
de PII, con el plazo legal), post-mortem obligatorio. `security.txt` y
divulgación responsable.

## 5. Continuidad, backups y DR

_Pendiente de redactar._ Basado en ADR-009 y ADR-017 capa 5. `pg_dump` por
tenant + backups del Managed PG, cifrado, bucket inmutable + retención, pruebas
de restore trimestrales, RPO/RTO objetivo, escenario ransomware, relación
disponibilidad ↔ términos legales (ADR-014).

## 6. SDLC seguro

_Pendiente de redactar._ Basado en ADR-011 y ADR-017 capa 7. Gate de CI (Pint,
Larastan, Pest + `composer audit`, `npm audit`, gitleaks, SAST, Trivy), tests
de aislamiento que bloquean deploy, gestión de dependencias
(Renovate/Dependabot), ramas protegidas, deploy manual (ADR-009), gestión de
secretos (SOPS+age / OIDC).

## 7. Gestión de subencargados

_Pendiente de redactar._ Registro y DPA de: DigitalOcean, Cloudflare, Brevo
(ADR-008), proveedor de LLM (ADR-004), proveedor de firma (ADR-015). Revisión
periódica, garantías de no-entrenamiento y de ubicación de datos donde aplique.

## 8. Cumplimiento Ley 1581 (Habeas Data)

_Pendiente de redactar._ Registro RNBD ante la SIC, aviso de privacidad y
autorización en el enrolamiento (ADR-006), procedimiento de derechos del
titular (consulta/reclamo con plazos), deber de notificación de incidentes,
política de tratamiento publicada.
