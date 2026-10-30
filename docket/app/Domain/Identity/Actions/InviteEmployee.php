<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Mail\EmployeeInvitationMail;
use App\Domain\Identity\Models\Employee;
use App\Domain\Identity\Models\EmployeeInvitation;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Invita a un empleado a fijar su contraseña: crea un token de un solo uso y
 * lo envía por correo (cola, ADR-008). Corre dentro de la curaduría (tenancy
 * inicializada). Invitar de nuevo invalida los enlaces anteriores sin usar.
 *
 * Solo para cuentas sin contraseña y activas: quien ya tiene contraseña usa el
 * restablecimiento, no una invitación.
 */
final class InviteEmployee
{
    /**
     * @throws ValidationException la cuenta ya tiene contraseña o no está activa
     */
    public function handle(Employee $employee): void
    {
        if ($employee->password !== null) {
            throw ValidationException::withMessages(['email' => __('This employee already has a password.')]);
        }

        if ($employee->status !== EmployeeStatus::Active) {
            throw ValidationException::withMessages(['email' => __('This employee account is not active.')]);
        }

        /** @var Tenant $tenant */
        $tenant = tenant();
        $days = (int) config('employee.invitation.valid_days');
        $token = Str::random(64);

        DB::transaction(function () use ($employee, $token, $days): void {
            EmployeeInvitation::query()->where('employee_id', $employee->id)->whereNull('used_at')->delete();

            EmployeeInvitation::create([
                'employee_id' => $employee->id,
                'token_hash' => EmployeeInvitation::hashToken($token),
                'expires_at' => now()->addDays($days),
            ]);
        });

        Mail::to($employee->email)->queue(new EmployeeInvitationMail(
            employeeName: $employee->name,
            tenantName: $tenant->name,
            url: $tenant->url('/invitation/'.$token),
            validDays: $days,
        ));
    }
}
