<?php

declare(strict_types=1);

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del dominio CENTRAL
|--------------------------------------------------------------------------
|
| Explícitamente scopeadas a los dominios centrales (config/tenancy.php,
| TENANCY_CENTRAL_DOMAINS) con Route::domain(). Necesario: sin esto, esta
| ruta y el catch-all de routes/tenant.php tienen la misma URI ("{any?}") sin
| dominio, y la segunda registrada pisa a la primera en la tabla de rutas de
| Laravel. Los dominios de tenant son dinámicos (viven en la tabla `domains`)
| así que ahí no se puede hacer lo mismo — se resuelven con el middleware de
| stancl/tenancy en su lugar.
|
| Las pantallas de cada curaduría se sirven por su propio subdominio — ver
| routes/tenant.php. El catch-all sirve el shell de la SPA; vue-router
| resuelve la pantalla real en el cliente.
|
*/

foreach (config('tenancy.central_domains', []) as $domain) {
    Route::domain($domain)->group(function () {
        // El negative lookahead excluye los prefijos reservados: sin esto,
        // este catch-all también se traga /api/*, /sanctum/*, etc. (el router
        // de Laravel no prioriza por especificidad, solo por orden de
        // registro — un catch-all de {any?} le gana a rutas más concretas
        // registradas después). Mismo patrón en routes/tenant.php.
        Route::get('/{any?}', SpaController::class)
            ->where('any', '^(?!api/|sanctum/|storage/|tenancy/|up$).*$')
            ->name('spa.central');
    });
}
