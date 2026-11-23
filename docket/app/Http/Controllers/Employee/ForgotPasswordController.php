<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Domain\Identity\Actions\RequestEmployeePasswordReset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;

use function Illuminate\Support\defer;

/**
 * "Olvidé mi contraseña" (público, en el subdominio de la curaduría). Responde
 * siempre lo mismo y lo hace sin esperar al trabajo real, que corre al terminar la
 * respuesta: ni el contenido ni el tiempo de respuesta dicen si el correo existe.
 */
final class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, RequestEmployeePasswordReset $reset): JsonResponse
    {
        $email = $request->email();

        defer(fn () => $reset->handle($email));

        return response()->json([
            'message' => __('If that email has an account in this curaduría, we have sent a link to choose a new password.'),
        ], 202);
    }
}
