<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use Stancl\Tenancy\Events\DatabaseDeleted;
use Stancl\Tenancy\Events\DeletingDatabase;
use Stancl\Tenancy\Jobs\DeleteDatabase;

/**
 * DeleteDatabase de stancl falla si la base ya no existe. Pasa con un tenant
 * en `failed`: ProvisionTenant ya borró la base física al compensar, y borrar
 * después ese registro (limpieza de fallidos) no debe explotar por eso.
 */
class DeleteTenantDatabase extends DeleteDatabase
{
    public function handle()
    {
        $database = $this->tenant->database();

        if (! $database->manager()->databaseExists($database->getName())) {
            return;
        }

        event(new DeletingDatabase($this->tenant));

        $database->manager()->deleteDatabase($this->tenant);

        event(new DatabaseDeleted($this->tenant));
    }
}
