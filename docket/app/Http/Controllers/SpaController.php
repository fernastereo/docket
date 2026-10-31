<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Sirve el shell de la SPA (Vue 3 + TS, resources/js/). Mismo origen que la
 * API — ver amendment de ADR-003 sobre el modelo de servido del frontend.
 * El ruteo real de pantallas lo resuelve vue-router en el cliente.
 */
class SpaController extends Controller
{
    public function __invoke(): View
    {
        // El mismo shell sirve al panel de plataforma (dominio central) y a
        // cada curaduría (su subdominio): la SPA decide qué pantallas existen
        // según el contexto que el servidor le informa.
        $tenant = tenant();

        return view('app', [
            'context' => $tenant === null ? 'platform' : 'tenant',
            'tenantName' => $tenant?->name,
        ]);
    }
}
