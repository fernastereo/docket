<?php

declare(strict_types=1);

// Configuración de plataforma (base central). Lo de stancl/tenancy sigue en
// config/tenancy.php; esto es lo propio de CuraduriAPP.

return [
    // Dominio bajo el cual cuelgan las curadurías: <subdominio>.<este valor>.
    // local: docket.test · dev: staging.curaduria.app · prod: curaduria.app
    'tenant_base_domain' => env('PLATFORM_TENANT_BASE_DOMAIN', 'docket.test'),

    // Subdominios que ninguna curaduría puede tomar (infraestructura, correo,
    // suplantación de la propia plataforma).
    'reserved_subdomains' => [
        'www', 'api', 'app', 'admin', 'panel', 'plataforma', 'platform',
        'central', 'staging', 'dev', 'test', 'demo', 'prod', 'status',
        'mail', 'smtp', 'imap', 'ftp', 'cdn', 'static', 'assets', 'files',
        'storage', 'docs', 'help', 'soporte', 'support', 'blog', 'landing',
        'login', 'auth', 'portal', 'verificar', 'verify',
    ],

    // Sesión del usuario de plataforma (docs/seguridad/politicas.md §2): más
    // corta que la de cualquier otro dominio porque una cuenta comprometida
    // puede crear y suspender curadurías. Se imponen en
    // EnforcePlatformSessionLimits, además del lifetime general de sesión.
    'session' => [
        'idle_minutes' => (int) env('PLATFORM_SESSION_IDLE_MINUTES', 15),
        'absolute_hours' => (int) env('PLATFORM_SESSION_ABSOLUTE_HOURS', 8),
    ],

    // Login: bloqueo temporal tras N fallos en la ventana (politicas.md §2).
    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 15 * 60,
    ],

    // MFA (TOTP, RFC 6238). Obligatoria para plataforma (ADR-017). `window` =
    // pasos de 30 s que se aceptan a cada lado por desfase de reloj.
    'mfa' => [
        // Nombre que muestra la app de autenticación. Fuera de producción lleva
        // el ambiente entre paréntesis, para no confundir las entradas de
        // local, staging y producción. PLATFORM_MFA_ISSUER lo fuerza si hace falta.
        'issuer' => env('PLATFORM_MFA_ISSUER', env('APP_ENV') === 'production'
            ? 'CuraduriAPP'
            : 'CuraduriAPP ('.env('APP_ENV', 'local').')'),
        'window' => 1,
        'recovery_codes' => 8,
        'max_attempts' => 5,
        'decay_seconds' => 15 * 60,
    ],

    // Contraseña (politicas.md §2): 12 mínimo, sin tope bajo, sin truncar.
    'password_min_length' => 12,
];
