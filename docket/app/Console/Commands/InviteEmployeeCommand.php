<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Actions\InviteEmployee;
use App\Domain\Identity\Models\Employee;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Envía (o reenvía) la invitación para que un empleado fije su contraseña.
 * Herramienta de soporte: un enlace vencido o un correo que no llegó. Invitar
 * de nuevo invalida los enlaces anteriores.
 */
final class InviteEmployeeCommand extends Command
{
    protected $signature = 'employees:invite
        {subdomain : Subdominio de la curaduría (p. ej. cu1bogota)}
        {email : Correo del empleado}';

    protected $description = 'Envía la invitación para fijar la contraseña a un empleado sin contraseña';

    public function handle(InviteEmployee $invite): int
    {
        /** @var class-string<Domain> $domainModel */
        $domainModel = config('tenancy.domain_model');
        $domain = $domainModel::query()
            ->where('domain', TenantSubdomain::hostFor(mb_strtolower(trim((string) $this->argument('subdomain')))))
            ->first();

        if ($domain === null) {
            $this->error('No existe una curaduría con ese subdominio.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));

        return $domain->tenant->run(function () use ($email, $invite) {
            $employee = Employee::query()->where('email', $email)->first();

            if ($employee === null) {
                $this->error('No existe un empleado con ese correo en esa curaduría.');

                return self::FAILURE;
            }

            try {
                $invite->handle($employee);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $this->error($message);
                    }
                }

                return self::FAILURE;
            }

            $this->info("Invitación enviada a {$employee->email}.");

            return self::SUCCESS;
        });
    }
}
