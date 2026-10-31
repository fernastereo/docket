<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employee;

use App\Domain\Identity\Actions\AcceptEmployeeInvitation;
use App\Domain\Identity\Models\EmployeeInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\AcceptInvitationRequest;
use Illuminate\Http\JsonResponse;

/**
 * Enlace de invitación (público, en el subdominio de la curaduría). Un token
 * desconocido, usado o vencido responde igual (404): no se revela cuál fue.
 */
final class InvitationController extends Controller
{
    /** Datos mínimos para saludar en la pantalla de elegir contraseña. */
    public function show(string $token): JsonResponse
    {
        $invitation = $this->usable($token);

        return response()->json(['data' => [
            'name' => $invitation->employee->name,
            'email' => $invitation->employee->email,
        ]]);
    }

    public function accept(AcceptInvitationRequest $request, string $token, AcceptEmployeeInvitation $accept): JsonResponse
    {
        $accept->handle($this->usable($token), $request->password());

        return response()->json(status: 204);
    }

    private function usable(string $token): EmployeeInvitation
    {
        return EmployeeInvitation::findUsable($token)
            ?? abort(404, 'This invitation is not valid.');
    }
}
