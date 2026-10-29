<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Identity\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barrera de MFA del empleado. Corre después de `auth:employee`.
 *
 * - Curador y admin: MFA obligatoria. Sin MFA activa solo pueden activarla,
 *   aunque la sesión ya esté marcada como verificada (p. ej. si les dieron el
 *   rol con la sesión abierta).
 * - Resto de roles: opcional. Si no la activaron entran con la contraseña; si
 *   la activaron, el login les pide el código igual (una MFA que se puede
 *   saltar no protege).
 *
 * La marca de la sesión se fija en el login (AuthController) y se pone en
 * verdadero al verificar el código.
 */
final class RequireEmployeeMfa
{
    public const PASSED = 'employee.mfa_passed';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Employee $employee */
        $employee = $request->user('employee');

        $enabled = $employee->hasMfaEnabled();
        $mustSetUp = $employee->mustUseMfa() && ! $enabled;

        if (! $mustSetUp && $request->session()->get(self::PASSED) === true) {
            return $next($request);
        }

        return response()->json([
            'message' => $enabled ? 'Two-factor verification required.' : 'Two-factor setup required.',
            'mfa' => $enabled ? 'verification_required' : 'setup_required',
        ], 403);
    }
}
