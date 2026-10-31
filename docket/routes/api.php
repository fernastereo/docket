<?php

declare(strict_types=1);

use App\Http\Controllers\Employee\AuthController as EmployeeAuthController;
use App\Http\Controllers\Employee\InvitationController;
use App\Http\Controllers\Employee\MfaController as EmployeeMfaController;
use App\Http\Controllers\Platform\AuthController as PlatformAuthController;
use App\Http\Controllers\Platform\MfaController;
use App\Http\Controllers\Platform\TenantAdminController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Middleware\EnforceEmployeeSessionLimits;
use App\Http\Middleware\EnforcePlatformSessionLimits;
use App\Http\Middleware\RequireEmployeeMfa;
use App\Http\Middleware\RequirePlatformMfa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Consumida por la SPA (cookie de sesión, Sanctum stateful) y, a futuro, por
| terceros con token (widget embebible, marketplace — ver docs/vision-producto.md).
|
| Las rutas de plataforma van en los dominios centrales (abajo) y las de
| empleado en los subdominios de curaduría, donde la tenancy se inicializa por
| dominio antes de tocar la sesión o la base.
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
                Route::post('/tenants/{tenant}/admin', [TenantAdminController::class, 'store'])
                    ->middleware('throttle:10,1')
                    ->name('tenants.admin.store');
                Route::delete('/tenants/{tenant}/admin/mfa', [TenantAdminController::class, 'resetMfa'])
                    ->middleware('throttle:10,1')
                    ->name('tenants.admin.mfa.destroy');
                Route::post('/tenants/{tenant}/admin/link', [TenantAdminController::class, 'sendLink'])
                    ->middleware('throttle:10,1')
                    ->name('tenants.admin.link');
                Route::put('/tenants/{tenant}/admin/password', [TenantAdminController::class, 'setPassword'])
                    ->middleware('throttle:10,1')
                    ->name('tenants.admin.password');
            });
        });
    });
}

/*
|--------------------------------------------------------------------------
| Empleados de curaduría — SOLO subdominios de tenant
|--------------------------------------------------------------------------
|
| Guard `employee` (ADR-005, amendment 2026-09-02): la cuenta vive en la base
| de la curaduría, así que la tenancy se inicializa por dominio ANTES de
| cualquier consulta, y los dominios centrales quedan excluidos. Mismo patrón
| que plataforma: login público; el resto exige sesión + límites de sesión
| (30 min / 12 h) y, salvo me/logout/mfa, el segundo factor cuando aplica.
|
*/

Route::middleware([InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class])
    ->prefix('employee')->name('employee.')->group(function () {
        Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login');

        // Enlace de la invitación (públicas; el token es la credencial). Con
        // límite por IP aunque un token de 64 caracteres no se adivine.
        Route::middleware('throttle:20,1')->prefix('invitation/{token}')->name('invitation.')->group(function () {
            Route::get('/', [InvitationController::class, 'show'])->name('show');
            Route::post('/accept', [InvitationController::class, 'accept'])->name('accept');
        });

        Route::middleware(['auth:employee', EnforceEmployeeSessionLimits::class])->group(function () {
            Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('logout');
            Route::get('/me', [EmployeeAuthController::class, 'me'])->name('me');

            Route::post('/mfa/setup', [EmployeeMfaController::class, 'setup'])->name('mfa.setup');
            Route::post('/mfa/confirm', [EmployeeMfaController::class, 'confirm'])->name('mfa.confirm');
            Route::post('/mfa/verify', [EmployeeMfaController::class, 'verify'])->name('mfa.verify');

            // Todo lo demás de la curaduría exige MFA cuando aplica. Una ruta
            // nueva va acá adentro por defecto.
            Route::middleware(RequireEmployeeMfa::class)->group(function () {
                // (aún sin rutas de negocio: llegan con el núcleo del expediente)
            });
        });
    });
