<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WorksInsideTenant;
use App\Domain\Identity\Actions\ResetEmployeeMfa;
use Illuminate\Console\Command;

/**
 * Reinicia el MFA de un empleado de una curaduría (perdió su teléfono y sus códigos
 * de recuperación). Como `platform:reset-mfa`, es un comando de consola a propósito:
 * quien lo ejecuta tiene acceso al servidor. Más adelante lo hará el admin de la
 * curaduría desde la pantalla de personal.
 */
final class ResetEmployeeMfaCommand extends Command
{
    use WorksInsideTenant;

    protected $signature = 'employees:reset-mfa
        {subdomain : Subdominio de la curaduría (p. ej. cu1bogota)}
        {email : Correo del empleado}';

    protected $description = 'Desactiva el MFA de un empleado de una curaduría (deberá activarlo de nuevo al entrar)';

    public function handle(ResetEmployeeMfa $reset): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('subdomain'));

        if ($tenant === null) {
            return self::FAILURE;
        }

        return $tenant->run(function () use ($reset): int {
            $employee = $this->findEmployee((string) $this->argument('email'));

            if ($employee === null) {
                return self::FAILURE;
            }

            $reset->handle($employee);

            $this->info("MFA desactivado para {$employee->email}. Deberá activarlo de nuevo en su próximo acceso.");

            return self::SUCCESS;
        });
    }
}
