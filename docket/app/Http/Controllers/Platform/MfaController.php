<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforcePlatformSessionLimits;
use App\Http\Middleware\RequirePlatformMfa;
use App\Http\Requests\Platform\PlatformMfaCodeRequest;
use App\Http\Resources\Platform\PlatformUserResource;
use App\Platform\Models\PlatformUser;
use App\Support\Mfa\BeginMfaSetup;
use App\Support\Mfa\ConfirmMfa;
use App\Support\Mfa\VerifyMfa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Segundo factor del usuario de plataforma. Controlador delgado: la lógica
 * está en las Actions. Estas rutas son las únicas que una sesión sin MFA
 * verificada puede usar (además de me y logout).
 */
final class MfaController extends Controller
{
    /** Genera el secreto y devuelve QR + clave manual. No activa nada todavía. */
    public function setup(Request $request, BeginMfaSetup $begin): JsonResponse
    {
        return response()->json(['data' => $begin->handle($this->user($request))]);
    }

    /** Activa MFA con un primer código válido y entrega los códigos de recuperación (una sola vez). */
    public function confirm(PlatformMfaCodeRequest $request, ConfirmMfa $confirm): JsonResponse
    {
        $codes = $confirm->handle($this->user($request), $request->code());

        $this->markVerified($request);

        return response()->json([
            'data' => [
                'recovery_codes' => $codes,
                'user' => (new PlatformUserResource($this->user($request)->refresh()))->resolve($request),
            ],
        ]);
    }

    /** Segundo paso del login. */
    public function verify(PlatformMfaCodeRequest $request, VerifyMfa $verify): PlatformUserResource
    {
        $verify->handle($this->user($request), $request->code());

        $this->markVerified($request);

        return new PlatformUserResource($this->user($request)->refresh());
    }

    private function user(Request $request): PlatformUser
    {
        /** @var PlatformUser */
        return $request->user('platform');
    }

    private function markVerified(Request $request): void
    {
        // Cambio de privilegio: rotar el id de sesión (anti fijación) y marcarla.
        $request->session()->regenerate();
        $request->session()->put(RequirePlatformMfa::PASSED, true);
        $request->session()->put(EnforcePlatformSessionLimits::LAST_ACTIVITY_AT, now()->getTimestamp());
    }
}
