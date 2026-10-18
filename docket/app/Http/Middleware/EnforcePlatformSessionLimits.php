<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límites de sesión del usuario de plataforma (politicas.md §2): 15 min de
 * inactividad y 8 h absolutas (configurables en config/platform.php). Corre
 * después de `auth:platform`; al vencer cierra la sesión y responde 401.
 *
 * Los tiempos los sella el controlador de login en la sesión
 * (`platform.logged_in_at`); acá solo se comprueban y se renueva la actividad.
 */
final class EnforcePlatformSessionLimits
{
    public const LOGGED_IN_AT = 'platform.logged_in_at';

    public const LAST_ACTIVITY_AT = 'platform.last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $now = now()->getTimestamp();

        $loggedInAt = (int) $session->get(self::LOGGED_IN_AT, 0);
        $lastActivityAt = (int) $session->get(self::LAST_ACTIVITY_AT, 0);

        $idleLimit = (int) config('platform.session.idle_minutes') * 60;
        $absoluteLimit = (int) config('platform.session.absolute_hours') * 3600;

        // Sin sello de login la sesión no nació por nuestro flujo: se descarta.
        $expired = $loggedInAt === 0
            || ($now - $loggedInAt) > $absoluteLimit
            || ($now - $lastActivityAt) > $idleLimit;

        if ($expired) {
            Auth::guard('platform')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return response()->json(['message' => 'Session expired.'], 401);
        }

        $session->put(self::LAST_ACTIVITY_AT, $now);

        return $next($request);
    }
}
