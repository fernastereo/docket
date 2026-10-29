<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Domain\Identity\Models\Employee;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforceEmployeeSessionLimits;
use App\Http\Middleware\RequireEmployeeMfa;
use App\Http\Requests\MfaCodeRequest;
use App\Http\Resources\Employee\EmployeeResource;
use App\Support\Mfa\BeginMfaSetup;
use App\Support\Mfa\ConfirmMfa;
use App\Support\Mfa\VerifyMfa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Segundo factor del empleado. Controlador delgado: la lógica está en las
 * Actions compartidas con plataforma (app/Support/Mfa). Estas rutas son las
 * únicas (junto con me y logout) que una sesión sin MFA verificada puede usar.
 */
final class MfaController extends Controller
{
    /** Genera el secreto y devuelve QR + clave manual. No activa nada todavía. */
    public function setup(Request $request, BeginMfaSetup $begin): JsonResponse
    {
        return response()->json(['data' => $begin->handle($this->employee($request))]);
    }

    /** Activa MFA con un primer código válido y entrega los códigos de recuperación (una sola vez). */
    public function confirm(MfaCodeRequest $request, ConfirmMfa $confirm): JsonResponse
    {
        $codes = $confirm->handle($this->employee($request), $request->code());

        $this->markVerified($request);

        return response()->json([
            'data' => [
                'recovery_codes' => $codes,
                'user' => (new EmployeeResource($this->employee($request)->refresh()))->resolve($request),
            ],
        ]);
    }

    /** Segundo paso del login. */
    public function verify(MfaCodeRequest $request, VerifyMfa $verify): EmployeeResource
    {
        $verify->handle($this->employee($request), $request->code());

        $this->markVerified($request);

        return new EmployeeResource($this->employee($request)->refresh());
    }

    private function employee(Request $request): Employee
    {
        /** @var Employee */
        return $request->user('employee');
    }

    private function markVerified(Request $request): void
    {
        // Cambio de privilegio: rotar el id de sesión (anti fijación) y marcarla.
        $request->session()->regenerate();
        $request->session()->put(RequireEmployeeMfa::PASSED, true);
        $request->session()->put(EnforceEmployeeSessionLimits::LAST_ACTIVITY_AT, now()->getTimestamp());
    }
}
