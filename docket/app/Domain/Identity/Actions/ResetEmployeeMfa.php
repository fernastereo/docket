<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Employee;
use App\Support\Mfa\Mfa;

/**
 * Quita el segundo factor de un empleado que perdió su app de autenticación y sus
 * códigos de recuperación. En su próximo acceso se le exige activarlo de nuevo
 * (si su rol lo exige, no llega a nada más) y se libera el bloqueo del segundo
 * paso, si lo tenía. La contraseña no se toca. Corre con la tenancy inicializada.
 */
final class ResetEmployeeMfa
{
    public function __construct(private readonly Mfa $mfa) {}

    public function handle(Employee $employee): void
    {
        $employee->forceFill([
            'mfa_secret' => null,
            'mfa_confirmed_at' => null,
            'mfa_last_used_timestamp' => null,
            'mfa_recovery_codes' => null,
        ])->save();

        $this->mfa->clearAttempts($employee);
    }
}
