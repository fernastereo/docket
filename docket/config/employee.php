<?php

declare(strict_types=1);

// Empleados de curaduría (docs/seguridad/politicas.md §2). Cada valor puede
// cambiar por ambiente sin tocar código.

return [
    // Sesión: más larga que la de plataforma, porque trabajan todo el día en el
    // sistema. Se imponen en EnforceEmployeeSessionLimits.
    'session' => [
        'idle_minutes' => (int) env('EMPLOYEE_SESSION_IDLE_MINUTES', 30),
        'absolute_hours' => (int) env('EMPLOYEE_SESSION_ABSOLUTE_HOURS', 12),
    ],

    // Login: bloqueo temporal tras N fallos en la ventana.
    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 15 * 60,
    ],

    // Enlace de recuperación que envía el panel de plataforma a quien ya tiene contraseña.
    'password_reset' => ['valid_days' => 2],

    // Invitación para fijar la contraseña (un solo uso).
    'invitation' => [
        'valid_days' => (int) env('EMPLOYEE_INVITATION_VALID_DAYS', 7),
    ],

    // Más bajo que el de plataforma (12) a propósito: el personal de las curadurías
    // no es técnico. Lo compensan el chequeo contra filtraciones (siempre activo) y
    // el bloqueo tras 5 fallos; curador y admin además llevan MFA obligatoria.
    // El front tiene el mismo número en resources/js/lib/password.ts.
    'password_min_length' => 8,
];
