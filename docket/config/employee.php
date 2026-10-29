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

    'password_min_length' => 12,
];
