<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Única vía para crear cuentas de empleado (ADR-011). Debe correr con la
 * tenancy inicializada: el correo es único dentro de la curaduría, y la misma
 * persona puede tener cuenta en otras curadurías sin que ninguna lo sepa.
 *
 * La cuenta nace SIN contraseña: la persona la fija al aceptar la invitación.
 * Hasta entonces no puede entrar.
 */
final class CreateEmployee
{
    /**
     * @throws ValidationException
     */
    public function handle(CreateEmployeeData $data): Employee
    {
        $validated = Validator::make(
            [
                'name' => $data->name,
                'email' => mb_strtolower(trim($data->email)),
                'roles' => array_map(fn ($role) => $role->value, $data->roles),
                'valid_from' => $data->validFrom?->format('Y-m-d'),
                'valid_until' => $data->validUntil?->format('Y-m-d'),
            ],
            [
                'name' => ['required', 'string', 'min:3', 'max:150'],
                'email' => ['required', 'email:rfc', 'max:254', 'unique:employees,email'],
                'roles' => ['required', 'array', 'min:1'],
                'valid_from' => ['nullable', 'date'],
                'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            ],
        )->validate();

        return DB::transaction(function () use ($validated, $data): Employee {
            $employee = Employee::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'valid_from' => $validated['valid_from'] ?? now()->toDateString(),
                'valid_until' => $validated['valid_until'] ?? null,
            ]);

            $employee->syncRoles(array_map(fn ($role) => $role->value, $data->roles));

            return $employee;
        });
    }
}
