<?php

declare(strict_types=1);

namespace App\Platform\Actions\Concerns;

use App\Domain\Identity\Models\Employee;
use App\Platform\Models\Tenant;
use Closure;
use Illuminate\Validation\ValidationException;

/**
 * Ejecuta algo sobre el administrador de una curaduría, desde plataforma. La
 * excepción de validación se captura dentro de run() y se relanza afuera: si
 * escapa de run(), la tenancy queda inicializada.
 */
trait RunsAsTenantAdmin
{
    /**
     * @param  Closure(Employee): void  $callback
     *
     * @throws ValidationException la curaduría no está lista, no tiene administrador o el callback falla
     */
    private function asAdmin(Tenant $tenant, Closure $callback, bool $writes = false): void
    {
        $ready = $writes ? $tenant->status->isWritable() : $tenant->status->isAccessible();

        if (! $ready) {
            throw ValidationException::withMessages(['tenant' => __('This curaduría is not ready for that yet.')]);
        }

        $failure = $tenant->run(function () use ($callback): ?ValidationException {
            try {
                $admin = Employee::firstAdmin()
                    ?? throw ValidationException::withMessages(['tenant' => __('This curaduría has no administrator.')]);

                $callback($admin);
            } catch (ValidationException $e) {
                return $e;
            }

            return null;
        });

        if ($failure !== null) {
            throw $failure;
        }
    }
}
