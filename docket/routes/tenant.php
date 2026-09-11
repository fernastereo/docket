<?php

declare(strict_types=1);

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Rutas por TENANT (subdominio de curaduría)
|--------------------------------------------------------------------------
|
| Resolución de tenant por subdominio (ADR-002): cada dominio en la tabla
| `domains` de la base central activa la tenancy para esa curaduría antes de
| ejecutar cualquier ruta de este archivo.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // TODO(ADR-012, fase "diferenciadores"): verificación pública de un acto
    // administrativo, sin login. Vive acá (por subdominio de tenant) porque
    // cada acto pertenece a una curaduría concreta.
    //   Route::get('/verificar/{codigo}', VerifyActController::class)->name('tenant.verificar');

    // Mismo negative lookahead que routes/web.php — evita tragarse /api/*
    // y otros prefijos reservados (ver comentario ahí).
    Route::get('/{any?}', SpaController::class)
        ->where('any', '^(?!api/|sanctum/|storage/|tenancy/|up$).*$')
        ->name('spa.tenant');
});
