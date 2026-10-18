<?php

declare(strict_types=1);

namespace App\Platform\Exceptions;

use App\Platform\Models\Tenant;
use RuntimeException;
use Throwable;

final class TenantProvisioningFailed extends RuntimeException
{
    public static function for(Tenant $tenant, Throwable $previous): self
    {
        return new self(
            sprintf('El alta del tenant %s falló: %s', $tenant->getTenantKey(), $previous->getMessage()),
            previous: $previous,
        );
    }
}
