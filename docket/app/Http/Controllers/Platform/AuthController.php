<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforcePlatformSessionLimits;
use App\Http\Requests\Platform\PlatformLoginRequest;
use App\Http\Resources\Platform\PlatformUserResource;
use App\Platform\Actions\AuthenticatePlatformUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sesión del usuario de plataforma (SPA de mismo origen, cookie de sesión —
 * ADR-003). Controlador delgado: la lógica está en AuthenticatePlatformUser.
 */
final class AuthController extends Controller
{
    public function login(PlatformLoginRequest $request, AuthenticatePlatformUser $authenticate): JsonResponse|PlatformUserResource
    {
        // Sin sesión (petición que no viene de la SPA: sin Origin/Referer de
        // un dominio stateful) no hay dónde guardar el login.
        if (! $request->hasSession()) {
            return response()->json(['message' => 'A stateful request from the SPA is required.'], 400);
        }

        $user = $authenticate->handle($request->toData());

        // Rotar el id de sesión al cambiar de privilegio (anti fijación).
        $request->session()->regenerate();
        $now = now()->getTimestamp();
        $request->session()->put(EnforcePlatformSessionLimits::LOGGED_IN_AT, $now);
        $request->session()->put(EnforcePlatformSessionLimits::LAST_ACTIVITY_AT, $now);

        return new PlatformUserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    public function me(Request $request): PlatformUserResource
    {
        return new PlatformUserResource($request->user('platform'));
    }
}
