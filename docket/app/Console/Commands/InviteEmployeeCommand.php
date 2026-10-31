<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WorksInsideTenant;
use App\Domain\Identity\Actions\InviteEmployee;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Envía (o reenvía) la invitación para que un empleado fije su contraseña.
 * Herramienta de soporte: un enlace vencido o un correo que no llegó. Invitar
 * de nuevo invalida los enlaces anteriores.
 */
final class InviteEmployeeCommand extends Command
{
    use WorksInsideTenant;

    protected $signature = 'employees:invite
        {subdomain : Subdominio de la curaduría (p. ej. cu1bogota)}
        {email : Correo del empleado}';

    protected $description = 'Envía la invitación para fijar la contraseña a un empleado sin contraseña';

    public function handle(InviteEmployee $invite): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('subdomain'));

        if ($tenant === null) {
            return self::FAILURE;
        }

        return $tenant->run(function () use ($invite): int {
            $employee = $this->findEmployee((string) $this->argument('email'));

            if ($employee === null) {
                return self::FAILURE;
            }

            try {
                $invite->handle($employee);
            } catch (ValidationException $e) {
                $this->reportErrors($e);

                return self::FAILURE;
            }

            $this->info("Invitación enviada a {$employee->email}.");

            return self::SUCCESS;
        });
    }
}
