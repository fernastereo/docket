<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/**
 * Roles de una cuenta de empleado de la curaduría (ADR-005). El valor es el
 * nombre del rol en la tabla `roles` del tenant; el código va en inglés y la
 * etiqueta en español es lo que ve la persona (glosario: docs/dominio/glosario.md).
 *
 * Los permisos de cada rol se definen junto al flujo del trámite; por ahora
 * solo existe la estructura. `admin` administra la plataforma dentro de la
 * curaduría (cuentas, configuración); `manager` es la gerencia: ve la
 * operación pero no gobierna cuentas.
 */
enum EmployeeRole: string
{
    case Curator = 'curator';
    case Admin = 'admin';
    case Manager = 'manager';
    case Architect = 'architect';
    case ArchitectAssistant = 'architect_assistant';
    case Engineer = 'engineer';
    case EngineerAssistant = 'engineer_assistant';
    case Lawyer = 'lawyer';
    case LawyerAssistant = 'lawyer_assistant';
    case FrontDesk = 'front_desk';
    case Records = 'records';
    case Accounting = 'accounting';

    public function label(): string
    {
        return match ($this) {
            self::Curator => 'Curador',
            self::Admin => 'Administrador',
            self::Manager => 'Gerencia',
            self::Architect => 'Arquitecto',
            self::ArchitectAssistant => 'Arquitecto auxiliar',
            self::Engineer => 'Ingeniero',
            self::EngineerAssistant => 'Ingeniero auxiliar',
            self::Lawyer => 'Abogado',
            self::LawyerAssistant => 'Abogado auxiliar',
            self::FrontDesk => 'Ventanilla',
            self::Records => 'Archivo',
            self::Accounting => 'Contabilidad',
        };
    }

    /** MFA obligatoria para quien expide o administra (ADR-017, politicas.md §2). */
    public function requiresMfa(): bool
    {
        return in_array($this, [self::Curator, self::Admin], true);
    }
}
