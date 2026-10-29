<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Domain\Identity\Actions\AuthenticateEmployee;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforceEmployeeSessionLimits;
use App\Http\Middleware\RequireEmployeeMfa;
use App\Http\Requests\Employee\EmployeeLoginRequest;
use App\Http\Resources\Employee\EmployeeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sesión del empleado en el subdominio de su curaduría (SPA de mismo origen,
 * cookie de sesión — ADR-003). Controlador delgado: la lógica está en
 * AuthenticateEmployee.
 */
final class AuthController extends Controller
{
    public function login(EmployeeLoginRequest $request, AuthenticateEmployee $authenticate): JsonResponse|EmployeeResource
    {
        // Sin sesión (petición que no viene de la SPA) no hay dónde guardar el login.
        if (! $request->hasSession()) {
            return response()->json(['message' => 'A stateful request from the SPA is required.'], 400);
        }

        $employee = $authenticate->handle($request->toData());

        // Rotar el id de sesión al cambiar de privilegio (anti fijación).
        $request->session()->regenerate();
        $now = now()->getTimestamp();
        $request->session()->put(EnforceEmployeeSessionLimits::LOGGED_IN_AT, $now);
        $request->session()->put(EnforceEmployeeSessionLimits::LAST_ACTIVITY_AT, $now);
        // Con MFA obligatoria o ya activada, la contraseña sola no basta.
        $request->session()->put(
            RequireEmployeeMfa::PASSED,
            ! $employee->mustUseMfa() && ! $employee->hasMfaEnabled(),
        );

        return new EmployeeResource($employee);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('employee')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    public function me(Request $request): EmployeeResource
    {
        return new EmployeeResource($request->user('employee'));
    }
}
