<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\EmployeeStatus;
use App\Domain\Identity\Mail\EmployeePasswordResetMail;
use App\Domain\Identity\Models\Employee;
use App\Domain\Identity\Models\EmployeeInvitation;
use App\Platform\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Envía a un empleado que ya tiene contraseña un enlace para elegir otra. Usa el
 * mismo mecanismo y la misma pantalla que la invitación (token de un solo uso,
 * `/invitation/<token>`); la contraseña actual sigue valiendo hasta que use el
 * enlace. Corre dentro de la curaduría (tenancy inicializada).
 */
final class SendEmployeePasswordReset
{
    /**
     * @throws ValidationException la cuenta no tiene contraseña (se invita) o no está activa
     */
    public function handle(Employee $employee): void
    {
        if ($employee->password === null) {
            throw ValidationException::withMessages(['email' => __('This employee has no password yet; send an invitation instead.')]);
        }

        if ($employee->status !== EmployeeStatus::Active) {
            throw ValidationException::withMessages(['email' => __('This employee account is not active.')]);
        }

        /** @var Tenant $tenant */
        $tenant = tenant();
        $days = (int) config('employee.password_reset.valid_days');
        $token = EmployeeInvitation::issueFor($employee, $days);

        Mail::to($employee->email)->queue(new EmployeePasswordResetMail(
            employeeName: $employee->name,
            tenantName: $tenant->name,
            url: $tenant->url('/invitation/'.$token),
            validDays: $days,
        ));
    }
}
