<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ForgotPasswordRequest;
use App\Http\Requests\Platform\ResetPasswordRequest;
use App\Platform\Actions\AcceptPlatformPasswordReset;
use App\Platform\Actions\RequestPlatformPasswordReset;
use App\Platform\Models\PlatformPasswordReset;
use Illuminate\Http\JsonResponse;

use function Illuminate\Support\defer;

/**
 * Recuperación de contraseña del panel de plataforma (público, solo dominios
 * centrales). "Olvidé mi contraseña" responde siempre lo mismo y sin esperar al
 * trabajo real, que corre al terminar la respuesta: ni el contenido ni el tiempo
 * dicen si el correo existe. Un enlace desconocido, usado o vencido responde 404.
 */
final class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request, RequestPlatformPasswordReset $reset): JsonResponse
    {
        $email = $request->email();

        defer(fn () => $reset->handle($email));

        return response()->json([
            'message' => __('If that email has a platform account, we have sent a link to choose a new password.'),
        ], 202);
    }

    /** Datos mínimos para saludar en la pantalla de elegir contraseña. */
    public function show(string $token): JsonResponse
    {
        $reset = $this->usable($token);

        return response()->json(['data' => [
            'name' => $reset->platformUser->name,
            'email' => $reset->platformUser->email,
        ]]);
    }

    public function reset(ResetPasswordRequest $request, string $token, AcceptPlatformPasswordReset $accept): JsonResponse
    {
        $accept->handle($this->usable($token), $request->password());

        return response()->json(status: 204);
    }

    private function usable(string $token): PlatformPasswordReset
    {
        return PlatformPasswordReset::findUsable($token) ?? abort(404, 'This link is not valid.');
    }
}
