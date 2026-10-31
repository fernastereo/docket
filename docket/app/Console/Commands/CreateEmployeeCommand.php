<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WorksInsideTenant;
use App\Domain\Identity\Actions\CreateEmployee;
use App\Domain\Identity\Actions\InviteEmployee;
use App\Domain\Identity\DataTransferObjects\CreateEmployeeData;
use App\Domain\Identity\Enums\EmployeeRole;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Crea una cuenta de empleado en una curaduría ya existente (por ejemplo, el
 * administrador de una curaduría que se dio de alta sin él). La cuenta nace sin
 * contraseña: con `--invite` se le envía enseguida su invitación; si no, se hace
 * después con `employees:invite`.
 */
final class CreateEmployeeCommand extends Command
{
    use WorksInsideTenant;

    protected $signature = 'employees:create
        {subdomain : Subdominio de la curaduría (p. ej. cu1bogota)}
        {email : Correo del empleado}
        {--name= : Nombre completo (si falta, se pregunta)}
        {--role=* : Rol del empleado; se puede repetir. Por defecto, admin}
        {--invite : Envía la invitación para fijar la contraseña al crear la cuenta}';

    protected $description = 'Crea un empleado en una curaduría (por defecto, con rol de administrador)';

    public function handle(CreateEmployee $create, InviteEmployee $invite): int
    {
        $roles = $this->roles();

        if ($roles === null) {
            return self::FAILURE;
        }

        $tenant = $this->resolveTenant((string) $this->argument('subdomain'));

        if ($tenant === null) {
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Nombre completo')));

        return $tenant->run(function () use ($create, $invite, $name, $roles): int {
            try {
                $employee = $create->handle(new CreateEmployeeData($name, (string) $this->argument('email'), $roles));

                if ($this->option('invite')) {
                    $invite->handle($employee);
                }
            } catch (ValidationException $e) {
                $this->reportErrors($e);

                return self::FAILURE;
            }

            $list = implode(', ', array_map(fn (EmployeeRole $role) => $role->value, $roles));
            $this->info("Empleado {$employee->email} creado con rol: {$list}.");
            $this->line($this->option('invite')
                ? 'Invitación enviada.'
                : "Sin contraseña todavía: envíale la invitación con `employees:invite {$this->argument('subdomain')} {$employee->email}`.");

            return self::SUCCESS;
        });
    }

    /**
     * @return list<EmployeeRole>|null null si algún rol no existe (ya se avisó)
     */
    private function roles(): ?array
    {
        $given = (array) $this->option('role');
        $roles = [];

        foreach ($given === [] ? [EmployeeRole::Admin->value] : $given as $value) {
            $role = EmployeeRole::tryFrom((string) $value);

            if ($role === null) {
                $valid = implode(', ', array_map(fn (EmployeeRole $r) => $r->value, EmployeeRole::cases()));
                $this->error("El rol \"{$value}\" no existe. Roles: {$valid}.");

                return null;
            }

            $roles[] = $role;
        }

        return $roles;
    }
}
