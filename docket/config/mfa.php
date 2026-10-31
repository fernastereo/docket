<?php

declare(strict_types=1);

// MFA (TOTP, RFC 6238) de usuarios de plataforma y empleados de curaduría
// (ADR-017, politicas.md §2). `window` = pasos de 30 s que se aceptan a cada
// lado por desfase de reloj.

return [
    // Nombre que muestra la app de autenticación. Fuera de producción lleva el
    // ambiente entre paréntesis, para no confundir las entradas de local,
    // staging y producción. PLATFORM_MFA_ISSUER lo fuerza si hace falta.
    'issuer' => env('PLATFORM_MFA_ISSUER', env('APP_ENV') === 'production'
        ? 'CuraduriAPP'
        : 'CuraduriAPP ('.env('APP_ENV', 'local').')'),
    'window' => 1,
    'recovery_codes' => 8,
    'max_attempts' => 5,
    'decay_seconds' => 15 * 60,
];
