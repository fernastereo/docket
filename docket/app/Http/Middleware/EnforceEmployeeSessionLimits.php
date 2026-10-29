<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Identity\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límites de sesión del empleado (politicas.md §2): 30 min de inactividad y
 * 12 h absolutas (config/employee.php). Corre después de `auth:employee`. Cierra
 * la sesión con 401 si venció o si la cuenta dejó de estar activa (suspendida,
 * terminada o fuera de vigencia): un cambio de estado surte efecto en la
 * siguiente petición, no al próximo login.
 */
final class EnforceEmployeeSessionLimits
{
    public const LOGGED_IN_AT = 'employee.logged_in_at';

    public const LAST_ACTIVITY_AT = 'employee.last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $now = now()->getTimestamp();

        $loggedInAt = (int) $session->get(self::LOGGED_IN_AT, 0);
        $lastActivityAt = (int) $session->get(self::LAST_ACTIVITY_AT, 0);

        $idleLimit = (int) config('employee.session.idle_minutes') * 60;
        $absoluteLimit = (int) config('employee.session.absolute_hours') * 3600;

        $employee = $request->user('employee');

        // Sin sello de login la sesión no nació por nuestro flujo: se descarta.
        $expired = $loggedInAt === 0
            || ($now - $loggedInAt) > $absoluteLimit
            || ($now - $lastActivityAt) > $idleLimit
            || ! $employee instanceof Employee
            || ! $employee->isActive();

        if ($expired) {
            Auth::guard('employee')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return response()->json(['message' => 'Session expired.'], 401);
        }

        $session->put(self::LAST_ACTIVITY_AT, $now);

        return $next($request);
    }
}
