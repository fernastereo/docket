<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Employee;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Única vía para fijar la contraseña de un empleado. Hoy la usa el comando
 * `employees:set-password`; la invitación por correo y el restablecimiento
 * reutilizarán esta misma Action, así la política de contraseña vive en un solo
 * lugar (largo mínimo y chequeo HIBP, politicas.md §2). Anota cuándo cambió: las
 * sesiones abiertas antes de ese momento se cierran (EnforceEmployeeSessionLimits).
 */
final class SetEmployeePassword
{
    /**
     * @throws ValidationException la contraseña no cumple la política
     */
    public function handle(Employee $employee, #[\SensitiveParameter] string $password): void
    {
        Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', Password::min((int) config('employee.password_min_length'))->uncompromised()]],
        )->validate();

        $employee->forceFill(['password' => $password, 'password_changed_at' => now()])->save();
    }
}
