<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barrera de MFA del panel de plataforma (obligatoria — ADR-017). Corre después
 * de `auth:platform`: la contraseña sola deja una sesión que únicamente puede
 * activar o verificar el segundo factor; todo lo demás responde 403 hasta que
 * la sesión queda marcada como verificada.
 *
 * La marca vive en la sesión y se pone en falso en cada login, así que un
 * login nuevo siempre vuelve a pedir el código.
 */
final class RequirePlatformMfa
{
    public const PASSED = 'platform.mfa_passed';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(self::PASSED) === true) {
            return $next($request);
        }

        $enabled = (bool) $request->user('platform')?->hasMfaEnabled();

        return response()->json([
            'message' => $enabled ? 'Two-factor verification required.' : 'Two-factor setup required.',
            'mfa' => $enabled ? 'verification_required' : 'setup_required',
        ], 403);
    }
}
