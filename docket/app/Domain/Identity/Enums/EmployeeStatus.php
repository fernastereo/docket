<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/** Estado de la cuenta de empleado (ADR-005, amendment 2026-09-02). */
enum EmployeeStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    /** Desvinculado: la cuenta se conserva por trazabilidad, no vuelve a entrar. */
    case Terminated = 'terminated';
}
