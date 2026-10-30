<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Models\Employee;
use App\Domain\Identity\Models\EmployeeInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El empleado fija su contraseña con el enlace de su invitación. La política de
 * contraseña es la de SetEmployeePassword. No inicia sesión: entra por el login
 * normal, donde también se le pide la MFA si su rol la exige.
 */
final class AcceptEmployeeInvitation
{
    /**
     * @throws ValidationException la contraseña no cumple la política
     */
    public function handle(EmployeeInvitation $invitation, #[\SensitiveParameter] string $password): Employee
    {
        return DB::transaction(function () use ($invitation, $password): Employee {
            // Con bloqueo: dos envíos simultáneos del mismo enlace no pueden ganar ambos.
            $locked = EmployeeInvitation::query()->usable()->whereKey($invitation->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw ValidationException::withMessages(['token' => __('This invitation is no longer valid.')]);
            }

            $employee = $locked->employee;

            if ($employee->status !== EmployeeStatus::Active) {
                throw ValidationException::withMessages(['token' => __('This invitation is no longer valid.')]);
            }

            app(SetEmployeePassword::class)->handle($employee, $password);

            $locked->forceFill(['used_at' => now()])->save();

            return $employee;
        });
    }
}
