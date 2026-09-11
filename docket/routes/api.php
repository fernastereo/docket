<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Consumida por la SPA (cookie de sesión, Sanctum stateful) y, a futuro, por
| terceros con token (widget embebible, marketplace — ver docs/vision-producto.md).
|
| TODO(identidad/auth, próxima fase): estas rutas NO pasan hoy por
| InitializeTenancyByDomain. Cuando se resuelva el modelo de identidad
| (ADR-005), definir cómo se activa la tenancy para requests de API en un
| subdominio de tenant — probablemente aplicando la tenancy al grupo `api`
| según dominio, en vez de (o además de) routes/tenant.php.
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
