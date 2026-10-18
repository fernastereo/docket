<?php

declare(strict_types=1);

namespace App\Platform\Enums;

/**
 * Ciclo de vida de una curaduría en la plataforma (ADR-010).
 *
 * `Provisioning` y `Failed` son estados del proceso de alta (ProvisionTenant),
 * no del negocio: un tenant solo es operable desde `Demo` en adelante.
 */
enum TenantStatus: string
{
    case Provisioning = 'provisioning';
    case Failed = 'failed';
    case Demo = 'demo';
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    /** ¿Puede la gente entrar a la curaduría? Suspendido es solo-lectura, nunca bloqueo total (ADR-010). */
    public function isAccessible(): bool
    {
        return match ($this) {
            self::Demo, self::Active, self::Suspended => true,
            self::Provisioning, self::Failed, self::Terminated => false,
        };
    }

    /** ¿Admite escrituras? Suspendido consulta pero no modifica. */
    public function isWritable(): bool
    {
        return $this === self::Demo || $this === self::Active;
    }
}
