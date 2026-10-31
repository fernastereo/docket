<?php

declare(strict_types=1);

// Traducciones de lo que hoy ve una persona en pantalla. Solo la política de
// contraseña: lo que no está acá cae al inglés del framework (fallback_locale).
// Los mensajes no dan ejemplos de contraseñas a propósito.
return [
    'password' => [
        'uncompromised' => 'Esa contraseña aparece en filtraciones de datos conocidas. Elige otra.',
    ],

    'custom' => [
        'password' => [
            'required' => 'Escribe una contraseña.',
            'string' => 'La contraseña no es válida.',
            'min' => ['string' => 'La contraseña debe tener al menos :min caracteres.'],
            'max' => ['string' => 'La contraseña es demasiado larga.'],
            'confirmed' => 'Las contraseñas no coinciden.',
        ],
    ],
];
