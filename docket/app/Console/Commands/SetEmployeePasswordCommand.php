<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Actions\SetEmployeePassword;
use App\Domain\Identity\Models\Employee;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Fija la contraseña de un empleado desde la consola. Es un puente: mientras
 * no exista la invitación por correo (el empleado fija la suya con un enlace),
 * es la única forma de dar acceso. Después queda como herramienta de soporte.
 */
final class SetEmployeePasswordCommand extends Command
{
    protected $signature = 'employees:set-password
        {subdomain : Subdominio de la curaduría (p. ej. cu1bogota)}
        {email : Correo del empleado}';

    protected $description = 'Fija la contraseña de un empleado de una curaduría (se pide en pantalla)';

    public function handle(SetEmployeePassword $setPassword): int
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
        $password = (string) $this->secret('Contraseña (mínimo '.config('employee.password_min_length').' caracteres)');

        if ($password !== (string) $this->secret('Repite la contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        return $domain->tenant->run(function () use ($email, $password, $setPassword) {
            $employee = Employee::query()->where('email', $email)->first();

            if ($employee === null) {
                $this->error('No existe un empleado con ese correo en esa curaduría.');

                return self::FAILURE;
            }

            try {
                $setPassword->handle($employee, $password);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $this->error($message);
                    }
                }

                return self::FAILURE;
            }

            $this->info("Contraseña fijada para {$employee->email}.");

            return self::SUCCESS;
        });
    }
}
