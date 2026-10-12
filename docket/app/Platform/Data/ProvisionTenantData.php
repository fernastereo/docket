<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\TenantStatus;

/**
 * Entrada de la Action ProvisionTenant (ADR-011). Solo transporta datos: la
 * validación vive en la Action, para que valga igual la llame un comando o,
 * más adelante, un controlador del panel de plataforma.
 */
final readonly class ProvisionTenantData
{
    public function __construct(
        public string $name,
        public string $subdomain,
        public TenantStatus $status = TenantStatus::Demo,
    ) {}
}
