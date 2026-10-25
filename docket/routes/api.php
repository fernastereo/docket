<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\AuthController as PlatformAuthController;
use App\Http\Controllers\Platform\MfaController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Middleware\EnforcePlatformSessionLimits;
use App\Http\Middleware\RequirePlatformMfa;
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

/*
|--------------------------------------------------------------------------
| Usuarios de plataforma (soporte / superadmin) — SOLO dominios centrales
|--------------------------------------------------------------------------
|
| Guard `platform` (ADR-005, amendment 2026-10-18). Se scopean con
| Route::domain() igual que routes/web.php: en un subdominio de tenant estas
| rutas no existen. El login es la única ruta pública; el resto exige
| `auth:platform` + los límites de sesión de plataforma (15 min de inactividad,
| 8 h absolutas — politicas.md §2).
|
*/

foreach (config('tenancy.central_domains', []) as $domain) {
    Route::domain($domain)->prefix('platform')->name('platform.')->group(function () {
        Route::post('/login', [PlatformAuthController::class, 'login'])
            ->name('login');

        Route::middleware(['auth:platform', EnforcePlatformSessionLimits::class])->group(function () {
            Route::post('/logout', [PlatformAuthController::class, 'logout'])->name('logout');
            Route::get('/me', [PlatformAuthController::class, 'me'])->name('me');

            // Segundo factor: lo único (junto con me y logout) que alcanza una
            // sesión con contraseña pero sin MFA verificada.
            Route::post('/mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup');
            Route::post('/mfa/confirm', [MfaController::class, 'confirm'])->name('mfa.confirm');
            Route::post('/mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify');

            // Todo lo demás del panel exige MFA verificada. Una ruta nueva va
            // acá adentro por defecto.
            Route::middleware(RequirePlatformMfa::class)->group(function () {
                Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
                Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
            });
        });
    });
}
