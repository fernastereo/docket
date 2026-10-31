<?php

declare(strict_types=1);

namespace App\Console\Commands\Concerns;

use App\Domain\Identity\Models\Employee;
use App\Platform\Models\Tenant;
use App\Platform\Rules\TenantSubdomain;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Lo común de los comandos de soporte que actúan sobre una curaduría: encontrarla
 * por su subdominio y, dentro de ella (`$tenant->run(...)`), a un empleado por su
 * correo. Los mensajes salen por la consola y devuelven null para que el comando
 * termine con FAILURE.
 */
trait WorksInsideTenant
{
    private function resolveTenant(string $subdomain): ?Tenant
    {
        /** @var class-string<Domain> $domainModel */
        $domainModel = config('tenancy.domain_model');
        $domain = $domainModel::query()
            ->where('domain', TenantSubdomain::hostFor(mb_strtolower(trim($subdomain))))
            ->first();

        if ($domain === null) {
            $this->error('No existe una curaduría con ese subdominio.');

            return null;
        }

        /** @var Tenant $tenant */
        $tenant = $domain->tenant;

        return $tenant;
    }

    /** Requiere la tenancy inicializada (llamar dentro de `$tenant->run()`). */
    private function findEmployee(string $email): ?Employee
    {
        $employee = Employee::query()->where('email', mb_strtolower(trim($email)))->first();

        if ($employee === null) {
            $this->error('No existe un empleado con ese correo en esa curaduría.');
        }

        return $employee;
    }

    private function reportErrors(ValidationException $e): void
    {
        foreach ($e->errors() as $messages) {
            foreach ($messages as $message) {
                $this->error($message);
            }
        }
    }
}
