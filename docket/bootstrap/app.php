<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA: cookie httpOnly + CSRF, sin CORS (mismo origen — ver
        // amendment de ADR-003 sobre el modelo de servido del frontend).
        $middleware->statefulApi();

        // ADR-017 capa 3: cabeceras de seguridad "por defecto", no añadidas
        // después.
        $middleware->append(SecurityHeaders::class);

        // No hay ruta `login` server-rendered en esta arquitectura (SPA +
        // API, ADR-003 amendment): el guest siempre recibe 401 JSON en vez
        // de un intento de redirect a una ruta que no existe.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
