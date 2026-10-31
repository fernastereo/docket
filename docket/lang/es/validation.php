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
        // Datos de una cuenta de empleado (CreateEmployee) y del primer administrador de una curaduría.
        'name' => [
            'required' => 'Escribe el nombre.',
            'min' => ['string' => 'El nombre debe tener al menos :min caracteres.'],
            'max' => ['string' => 'El nombre es demasiado largo.'],
        ],
        'email' => [
            'required' => 'Escribe el correo.',
            'email' => 'El correo no es válido.',
            'max' => ['string' => 'El correo es demasiado largo.'],
            'unique' => 'Ya existe una cuenta con ese correo en esta curaduría.',
        ],
        'admin_name' => [
            'required' => 'Escribe el nombre del administrador.',
            'required_with' => 'Escribe el nombre del administrador.',
            'min' => ['string' => 'El nombre debe tener al menos :min caracteres.'],
            'max' => ['string' => 'El nombre es demasiado largo.'],
        ],
        'admin_email' => [
            'required' => 'Escribe el correo del administrador.',
            'required_with' => 'Escribe el correo del administrador.',
            'email' => 'El correo no es válido.',
            'max' => ['string' => 'El correo es demasiado largo.'],
        ],
        'password' => [
            'required' => 'Escribe una contraseña.',
            'string' => 'La contraseña no es válida.',
            'min' => ['string' => 'La contraseña debe tener al menos :min caracteres.'],
            'max' => ['string' => 'La contraseña es demasiado larga.'],
            'confirmed' => 'Las contraseñas no coinciden.',
        ],
    ],
];
