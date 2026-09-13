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

Basado en ADR-005 (identidad) y ADR-017 capa 4 (amendment 2026-09-13, que fija
los números concretos). Tres dominios de identidad con política propia,
porque el riesgo por cuenta comprometida es muy distinto entre ellos.

**Contraseñas** (Argon2id en los tres dominios; sin rotación forzada ni
reglas de composición — NIST 800-63B):
| Dominio | Longitud mínima |
|---|---|
| Solicitantes | 8 |
| Empleados de curaduría (todos los roles) | 12 |
| Superadmin/soporte de plataforma | 12 |

Chequeo contra brechas conocidas (HIBP k-anonymity) al crear/cambiar
contraseña, en los tres dominios. Soportar hasta 64+ caracteres, sin truncar.

**MFA (TOTP, RFC 6238)**:
- Superadmin/soporte de plataforma: **obligatoria**.
- Curador y admin de tenant: **obligatoria** (cierra el riesgo de mayor
  impacto: expedir/alterar actos administrativos).
- Resto de roles de empleado (arquitecto, ingeniero, abogado): opcional,
  incentivada.
- Solicitantes: opcional.
- Recovery codes: 8 de un solo uso por activación, regenerables, hasheados
  en reposo.

**Ciclo de vida de sesión** (Sanctum SPA, cookie httpOnly+Secure+SameSite;
sesión respaldada en **Redis**, prefijo por tenant — sin esto no hay
revocación real):
| Dominio | Timeout inactividad | Sesión absoluta |
|---|---|---|
| Solicitantes | sin timeout agresivo ("recordarme") | reautenticación obligatoria antes de acción sensible (radicar/retirar/firmar) |
| Empleados de curaduría | 30 min | 12 h |
| Superadmin/soporte de plataforma | 15 min | 8 h |

Rotación de sesión al cambiar contraseña o privilegio. "Cerrar sesión en
todos los dispositivos" disponible en los tres dominios (revocación
server-side vía Redis).

**Rate limiting de login**: bloqueo temporal de cuenta tras 5 intentos
fallidos en 15 minutos, con backoff progresivo antes de llegar al umbral —
en capa de app (`throttle` de Laravel), además del de Cloudflare (Capa 1).

**RBAC**: por tenant, deny-by-default, en Policies (ADR-011). Acciones
privilegiadas (expedir acto, cambiar tarifas, gestionar usuarios, gestionar
definiciones de campos personalizados) siempre auditadas.

**Acceso privilegiado y break-glass**: plano admin del proveedor
(superadmin/soporte) separado del de tenant; procedimiento break-glass
documentado — _pendiente de redactar el runbook puntual_.

**Acceso de operación** (SSH, admin de BD, deploy, debugging): exclusivamente
por malla Tailscale, sin puerto SSH expuesto a internet como camino real;
MFA obligatoria en el proveedor de identidad de la VPN. Detalle en
`docs/dev-guide.md` §3 e `infra/README.md`.

**Estado de implementación**: todo lo anterior está **decidido, no
implementado** — `docket/` aún no tiene controlador de login, reglas de
contraseña, rate limiting ni MFA (pendiente en `docs/preguntas-abiertas.md`,
bloque de identidad/auth).

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
