# ADR-017: Seguridad de la plataforma y postura de defensa

**Estado**: Aceptada (postura y controles fijados; implementación por fases con
la infra — ADR-009 — y el código — ADR-011)
**Fecha**: 2026-09-06

## Contexto

Antes de aprovisionar infraestructura hay que fijar cómo se protege la
plataforma ante ataques externos (DDoS / intento de tumbar el sitio, credential
stuffing, alteración de expedientes o actos, exfiltración de datos), qué
políticas se adoptan y a qué estándar de industria se alinea. Es una decisión
transversal que condiciona ADR-009 (infra), ADR-011 (código) y ADR-005/006
(identidad y auth).

**Por qué el dominio sube el listón:**

- Los **actos administrativos** tienen valor legal: integridad y no repudio son
  críticos (ADR-012 verificación pública, ADR-015 firma).
- **Datos personales** de ciudadanos → Ley 1581/2012 (Habeas Data), vigilada
  por la SIC, con deber de notificación de incidentes.
- El **aislamiento multi-tenant** (ADR-002) es la joya de la corona: una brecha
  que cruce curadurías es existencial.
- **La disponibilidad es riesgo legal**: los expedientes tienen plazos en días
  hábiles corriendo (`docs/dominio/flujo-tramite.md`, ADR-014). Una caída
  sostenida puede perjudicar a un ciudadano; el DoS tiene consecuencia
  jurídica, no solo operativa.
- Las curadurías son **vigiladas por la SNR** y ejercen función pública.

## Decisión

Adoptar un modelo de **defensa en profundidad** por capas, con estas
definiciones de partida (2026-09-06):

- **Cloudflare Pro** en la zona `curaduria.app` (ya frontea la landing) como
  capa de borde: DDoS, WAF, rate limiting, bots.
- Cumplimiento: **baseline Ley 1581/2012 + Decreto 1377/2013**, con los
  controles técnicos alineados a **OWASP ASVS** y **CIS Benchmarks**. Sin
  certificación formal (ISO 27001 / SOC 2 / MSPI de MinTIC) por ahora — se
  diseña "compatible con" y se certifica más adelante si el negocio lo pide.
- **MFA (TOTP) opcional pero incentivada** para empleados de curaduría
  (ver "Riesgo residual — MFA"). **Obligatoria** para soporte/superadmin de
  plataforma y para el acceso a la malla de operación.
- Acceso de operación (SSH, admin de BD, deploy, debugging) exclusivamente por
  **malla VPN tipo Tailscale/WireGuard**; **sin puerto SSH expuesto** a
  internet.

### Capa 0 — Gobierno y cumplimiento

- **Ley 1581/2012 + Decreto 1377/2013**: registro de bases de datos en el
  **RNBD** ante la SIC; aviso de privacidad + autorización capturados en el
  enrolamiento (ADR-006); canal de derechos del titular (consulta/reclamo con
  plazos legales); **deber de notificación de incidentes** a SIC y a los
  afectados; responsable de tratamiento designado.
- **Frameworks de referencia técnica**: OWASP ASVS (checklist de verificación
  de la app), OWASP Top 10 / API Security Top 10, CIS Benchmarks (Ubuntu,
  Docker, PostgreSQL, Nginx).
- **Registro de subencargados + DPA** con: DigitalOcean, Cloudflare, Brevo
  (ADR-008), proveedor de LLM (ADR-004), proveedor de firma (ADR-015).
- **Pentest externo anual** y antes de releases mayores. `security.txt` +
  política de divulgación responsable de vulnerabilidades.

### Capa 1 — Borde (Cloudflare Pro)

- DDoS L3/L4 automático e ilimitado; L7 gestionado.
- **WAF**: OWASP Core Ruleset + Cloudflare Managed Ruleset activos, más reglas
  propias para patrones conocidos.
- **Rate limiting** en: login, recuperación de contraseña,
  activación/enrolamiento (ADR-006), envío de radicación, endpoint público de
  verificación (ADR-012), emisión de tokens de API.
- Bot Fight Mode; managed challenge ante tráfico sospechoso.
- **"Under Attack" mode** como palanca documentada en el runbook de incidentes.
- Cache de estáticos en el borde; **nunca** cachear respuestas autenticadas.
- **Origin lock**: el firewall del droplet acepta 80/443 **solo** desde los
  rangos de IP de Cloudflare (autoactualizados). Un ataque directo a la IP de
  origen no llega. La malla VPN es la única otra vía de entrada.
- TLS: Cloudflare **Full (Strict)** + certificado **Origin CA** (amendment
  2026-09-01 de ADR-009); mínimo TLS 1.2, preferente 1.3; **HSTS con preload**.

### Capa 2 — Red y host

- **DO VPC**: la app se conecta al **DO Managed PostgreSQL** solo por IP
  privada; "trusted sources" del cluster = únicamente el droplet de app; PG
  nunca en internet público.
- DO Cloud Firewall + `ufw` en el host: entrante público = 443/80 desde
  Cloudflare y nada más.
- **Malla Tailscale/WireGuard** para todo el plano de operación. Puerto 22 no
  expuesto. ACLs de la malla; auth de dispositivo + **MFA obligatoria** en el
  proveedor de identidad de la VPN.
- **Hardening del host** (CIS): `unattended-upgrades` de seguridad, sin login
  root, solo llaves, `fail2ban` (defensa en profundidad aun tras la VPN),
  paquetes mínimos, `auditd`.
- **Hardening de Docker** (CIS): contenedores non-root, rootfs de solo lectura
  donde se pueda, drop de capabilities, sin `--privileged`, **límites de
  recursos por contenedor** (contiene un contenedor comprometido o en bucle —
  también es control de DoS local), imágenes base pinneadas, secretos no
  horneados en la imagen.
- `docket-prod` / `docket-dev` separados; **dev nunca con datos reales** de
  ciudadanos (seed o anonimizado — encaja con el tenant demo de ADR-010).

### Capa 3 — Aplicación (HTTP / Laravel / Vue)

- Middleware de **cabeceras de seguridad**: CSP basada en nonce para la SPA,
  `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`,
  `frame-ancestors 'none'`.
- **Sanctum SPA**: cookies httpOnly + Secure + SameSite; token CSRF forzado;
  **CORS** con allowlist estricta (solo subdominios de tenant).
- **Rate limiting también a nivel app** (`throttle` de Laravel) en los mismos
  endpoints sensibles — defensa en profundidad si se evade el borde.
- Entrada: Form Requests para forma (ADR-011); invariantes de negocio en el
  dominio; binding de parámetros de Eloquent; sin SQL crudo con input de
  usuario; autoescape de Vue; DTOs evitan mass assignment (ADR-011).
- **Subida de archivos** (resuelve el pendiente de ADR-007): escaneo **ClamAV**
  asíncrono antes de que el archivo sea descargable; allowlist de MIME +
  extensión; tope de tamaño; claves aleatorias; almacenamiento en Spaces (fuera
  del webroot, no ejecutable); cuarentena ante positivo.
- **SSRF**: la ruta de OCR/LLM que descarga documentos (ADR-004) y cualquier
  webhook saliente validan la URL y bloquean rangos privados/link-local y
  endpoints de metadata.
- `APP_DEBUG=false` en prod; sin stack traces al usuario; error detallado solo
  a Sentry con PII depurada.

### Capa 4 — Identidad y acceso

- Contraseñas: **Argon2id**, longitud mínima + **chequeo contra brechas**
  (HIBP k-anonymity), sin rotación forzada (NIST 800-63B).
- **MFA (TOTP)** disponible para todos. Empleados de curaduría: opcional pero
  incentivada (ver "Riesgo residual"). **Soporte/superadmin de plataforma:
  obligatoria** — es la cuenta que puede tocar todos los tenants.
- Login: rate limit + backoff progresivo + bloqueo + challenge tras N fallos
  (también en el borde).
- Sesiones/tokens: corta duración, timeout por inactividad y absoluto, rotación
  al cambiar privilegio o contraseña, revocación server-side, "cerrar sesión en
  todos los dispositivos".
- Tokens de enrolamiento (ADR-006): ya firmados, un solo uso, con TTL — se
  mantienen.
- **RBAC** por tenant, deny-by-default, en Policies (ADR-011). Acciones
  privilegiadas (expedir acto, cambiar configuración de tarifas, gestionar
  usuarios, gestionar definiciones de campos personalizados — ADR-016)
  **siempre auditadas**.
- Plano admin del proveedor separado de la app de tenant; procedimiento
  **break-glass** documentado.

### Capa 5 — Protección de datos

- **En reposo**: cifrado del DO Managed PG, de Spaces (ADR-007) y del volumen
  del droplet.
- **En tránsito**: TLS en todo, incluido app↔DB (conexión PG con SSL
  requerido).
- **Cifrado a nivel de aplicación** (`encrypted` cast) para campos sensibles
  definidos. Para campos que deben ser buscables (ej. documento como llave
  natural, ADR-005) usar **hash con clave / blind index** en vez de texto plano
  donde el modelo de amenaza lo justifique — se decide por campo al modelar.
- **Secretos**: nunca en git; `.env` en el host con permisos estrictos +
  herramienta de secretos (recomendación **SOPS + age**, cifrado en el repo,
  sin SaaS extra, apto para equipo de una persona). CI: **GitHub OIDC → DO**,
  sin llaves cloud de larga vida; secrets de Actions por entorno; deploy manual
  (ADR-009).
- **Backups** (ADR-009): `pg_dump` por tenant + backups automáticos del Managed
  PG; cifrados; en Spaces con IAM restringido; **bucket con versionado/objeto
  inmutable + retención** (resiliencia ante ransomware); **pruebas de restore
  trimestrales** documentadas.
- **Higiene de logs**: PII depurada de los logs de aplicación; logs enviados
  fuera del host, append-only, con retención por política.
- **PII en prompts de LLM** (cierra parte del pendiente de ADR-004):
  minimización/redacción antes del prompt; DPA + garantía de no-entrenamiento
  con el proveedor; documentar qué dato sale del tenant.

### Capa 6 — Aislamiento de tenants (refuerza ADR-002)

- Frontera física database-per-tenant; conexión conmutada por request
  (stancl/tenancy).
- **Usuario/credencial de BD distinto por base de tenant** — mínimo privilegio:
  una credencial filtrada abre un tenant, no todos.
- Usuario de app de la BD central con grants mínimos.
- Prefijo de clave Redis por tenant; jobs de cola tenant-aware (ADR-003);
  prefijo de almacenamiento por tenant (ADR-007).
- **Tests de aislamiento automáticos obligatorios** (ADR-011) en CI — un test de
  aislamiento en rojo **bloquea el deploy**.

### Capa 7 — SDLC seguro / cadena de suministro (extiende el gate de ADR-011)

- CI en cada push/PR: Pint, Larastan, Pest (ya) **+** `composer audit`,
  `npm audit --audit-level=high`, secret scanning (**gitleaks**), SAST
  (**CodeQL** o Semgrep), **Trivy** sobre la imagen construida.
- **Renovate/Dependabot** para actualizaciones de dependencias; lockfiles
  versionados; sin tags flotantes.
- `main` / `dev` protegidas, revisión requerida (checklist aunque sea
  self-review), tags de release firmados.

### Capa 8 — Monitoreo, detección y respuesta

- Logs centralizados y etiquetados por tenant (ver `docs/preguntas-abiertas.md`,
  "Observabilidad") con retención + integridad.
- **Alertas de señal de seguridad**: picos de fallo de auth, picos de 5xx/4xx,
  latencia / agotamiento de recursos (señal temprana de DoS), picos de bloqueos
  WAF (notificaciones de Cloudflare), expiración de certificados, fallo de
  backup, saturación de disco/CPU.
- **Monitor de uptime externo** independiente del stack, que alerta ante caída
  total.
- Sentry para errores de aplicación (PII depurada).
- **Disponibilidad = riesgo legal**: se enlaza con ADR-014 (el calculador de
  términos admite ajuste administrativo) y con el SLA/contrato (ADR-001,
  pendiente de ADR-010) — debe contemplar caída de la plataforma frente a
  términos corriendo.
- **Plan de respuesta a incidentes**: niveles de severidad, contacto/on-call,
  runbooks de contención ("activar Under Attack", "rotar credencial de BD de
  tenant", "revocar todas las sesiones"), plantillas de comunicación (a
  curadurías; a SIC + titulares ante brecha de PII dentro del plazo legal),
  post-mortem obligatorio.

## Riesgo residual — MFA

La decisión es "opcional pero incentivada" para empleados de curaduría. Queda
constancia de que:

- Una cuenta de **curador** comprometida puede **expedir o alterar actos
  administrativos** (documentos con valor legal) — es el account-takeover de
  mayor impacto del sistema.
- **Recomendación a revisar antes del go-live**: MFA **obligatoria** para roles
  privilegiados (curador, admin de tenant). Es barato sobre el mismo mecanismo
  TOTP y cierra el hueco de mayor impacto sin fricción para el resto del
  personal.
- MFA de soporte/superadmin de plataforma y de la malla de operación:
  **obligatoria ya**, no sujeta a esta decisión.

## Alternativas descartadas

- **Mitigación de DDoS propia** (scrubbing, reglas en Nginx, balanceador con
  reglas L7): ni el volumen ni el equipo lo justifican. El estándar de la
  industria a esta escala es poner un proveedor de borde (Cloudflare / Fastly /
  AWS Shield) delante; ya usamos Cloudflare para la landing.
- **SSH público endurecido** en vez de malla VPN: mantiene una superficie de
  fuerza bruta y de 0-day de OpenSSH innecesaria. La malla la elimina.
- **Certificación ISO 27001 / SOC 2 / MSPI desde el día uno**: overhead de
  proceso alto para un equipo de una persona en fase de arranque. Se diseña
  "compatible con" y se certifica cuando haya demanda comercial.
- **Cifrado con HSM/KMS dedicado**: se usa cifrado gestionado por la plataforma
  + SOPS/age para secretos; se revisa a escala.

## Consecuencias

- Costo recurrente nuevo: Cloudflare Pro (~USD 20/mes/zona), pentest anual,
  posible plan de Tailscale, tiempo de mantenimiento de reglas WAF y del
  catálogo de IPs de Cloudflare en el firewall.
- La infra (ADR-009) deja de ser "dos droplets con Docker": incluye VPC, malla
  VPN, firewall con origin lock, hardening CIS y scanners en CI — ver amendment
  de ADR-009.
- El gate de CI de ADR-011 se amplía con scanners de seguridad; un fallo de
  scanner o de test de aislamiento bloquea el deploy.
- Se asume la obligación de mantener el RNBD, los DPA y el plan de respuesta a
  incidentes al día — trabajo de gobierno continuo, no una tarea única.
- Varias piezas ya decididas encajan como controles de seguridad y se
  referencian desde aquí: ADR-006 (tokens de enrolamiento), ADR-007 (archivos),
  ADR-012 (verificación pública), ADR-015 (firma), ADR-011 (audit log, tests de
  aislamiento).

## Pendiente (ver `docs/preguntas-abiertas.md`)

- Selección de proveedor de pentest.
- Momento del registro RNBD ante la SIC (antes del primer tenant real con
  datos de ciudadanos).
- Elección final de herramienta de secretos (SOPS+age vs Doppler vs otro).
- Umbral (tenants / volumen) a partir del cual se revisa un SIEM dedicado.
- Ciberseguro (decisión de negocio).
- Implementar en código el bloque de identidad/auth con los parámetros del
  amendment 2026-09-13 (todavía no existe controlador de login, reglas de
  contraseña, rate limiting ni MFA en `docket/`).

## Amendments

### 2026-09-13 — Cierre de Capa 4: MFA obligatoria para roles privilegiados y números concretos de identidad/acceso

**Motivo**: la Capa 4 dejaba "MFA obligatoria para roles privilegiados" como
riesgo residual sin resolver, y varios parámetros (longitud de contraseña,
timeouts de sesión, mecanismo de revocación de sesión) sin fijar — bloqueaba
redactar `docs/seguridad/politicas.md` §2 y arrancar la implementación del
bloque de identidad/auth. Se definieron alineados a NIST SP 800-63B (política
de contraseñas y autenticación) y OWASP ASVS nivel 2 (verificable, estándar de
facto para apps que manejan PII o tienen valor legal — ambos aplican acá).

**Cambio**:

1. **MFA obligatoria para curador y admin de tenant**, deja de ser opcional.
   Cierra el riesgo residual señalado en la sección original: un curador
   comprometido puede expedir o alterar actos administrativos — el
   account-takeover de mayor impacto del sistema. Sigue siendo TOTP
   (RFC 6238), el mismo mecanismo que el resto de roles — no agrega
   infraestructura nueva. El resto de roles de empleado (arquitecto,
   ingeniero, abogado) mantiene MFA opcional pero incentivada, sin cambio.
   MFA de soporte/superadmin de plataforma sigue obligatoria, sin cambios (ya
   lo era).

2. **Política de contraseña por dominio de identidad** (Argon2id en todos,
   sin rotación forzada — NIST 800-63B):
   - Solicitantes: longitud mínima 8.
   - Empleados de curaduría (todos los roles): longitud mínima 12.
   - Superadmin/soporte de plataforma: longitud mínima 12.
   Sin regla de composición (mayúscula+símbolo+número obligatorios) en ningún
   caso — no aporta seguridad real medible y frustra al usuario (NIST
   800-63B). Soportar contraseñas de hasta 64+ caracteres, sin truncar.

3. **Ciclo de vida de sesión, por dominio** (ASVS L2):
   - Solicitantes: sesión persistente ("recordarme"), sin timeout de
     inactividad agresivo — es un portal de baja frecuencia de uso. Cualquier
     acción sensible (radicar, retirar, firmar) exige reautenticación
     (confirmación de contraseña) sin importar la antigüedad de la sesión.
   - Empleados de curaduría: timeout por inactividad 30 minutos, sesión
     absoluta máxima 12 horas.
   - Superadmin/soporte de plataforma: timeout por inactividad 15 minutos,
     sesión absoluta máxima 8 horas — más estricto por el radio de impacto
     (todos los tenants a la vez).
   En los tres casos: rotación de sesión al cambiar contraseña o privilegio;
   revocación server-side disponible ("cerrar sesión en todos los
   dispositivos").

4. **Mecanismo de revocación**: las sesiones de Sanctum (SPA, cookie) se
   respaldan en **Redis**, no en el driver de sesión por archivo/BD por
   defecto de Laravel, con el prefijo por tenant que ya fija la Capa 6 de
   este ADR. Sin esto, "cerrar sesión en todos los dispositivos" no es
   implementable de forma confiable ni escalable.

5. **Rate limiting de login** (a nivel app, además del de Cloudflare de la
   Capa 1): bloqueo temporal de la cuenta tras 5 intentos fallidos en 15
   minutos, con backoff progresivo antes de llegar a ese umbral.

6. **Recovery codes de MFA**: 8 códigos de un solo uso generados al activar
   TOTP, regenerables bajo demanda, almacenados hasheados (no en texto
   plano).

**Alternativa evaluada y descartada**: exigir WebAuthn/llave de hardware en
vez de TOTP para el rol de superadmin/soporte de plataforma. Se descarta por
ahora — fricción operativa (logística de llaves físicas) sin que el volumen
de personas con ese rol lo justifique todavía; TOTP obligatorio ya cierra la
brecha principal de ese rol. Revisar si el equipo de soporte de plataforma
crece.

**No cambia**: el resto de la Capa 4 (RBAC deny-by-default, plano admin
separado con break-glass, tokens de enrolamiento de ADR-006) queda igual.
