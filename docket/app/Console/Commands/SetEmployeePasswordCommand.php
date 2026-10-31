<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WorksInsideTenant;
use App\Domain\Identity\Actions\SetEmployeePassword;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Fija la contraseña de un empleado desde la consola. Herramienta de soporte:
 * el empleado normalmente la fija con el enlace de su invitación.
 */
final class SetEmployeePasswordCommand extends Command
{
    use WorksInsideTenant;

    protected $signature = 'employees:set-password
        {subdomain : Subdominio de la curaduría (p. ej. cu1bogota)}
        {email : Correo del empleado}';

    protected $description = 'Fija la contraseña de un empleado de una curaduría (se pide en pantalla)';

    public function handle(SetEmployeePassword $setPassword): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('subdomain'));

        if ($tenant === null) {
            return self::FAILURE;
        }

        $password = (string) $this->secret('Contraseña (mínimo '.config('employee.password_min_length').' caracteres)');

        if ($password !== (string) $this->secret('Repite la contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        return $tenant->run(function () use ($password, $setPassword): int {
            $employee = $this->findEmployee((string) $this->argument('email'));

            if ($employee === null) {
                return self::FAILURE;
            }

            try {
                $setPassword->handle($employee, $password);
            } catch (ValidationException $e) {
                $this->reportErrors($e);

                return self::FAILURE;
            }

            $this->info("Contraseña fijada para {$employee->email}.");

            return self::SUCCESS;
        });
    }
}
