<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataTransferObjects;

use App\Domain\Identity\Enums\EmployeeRole;
use DateTimeInterface;

/** Entrada de la Action CreateEmployee. Solo transporta datos. */
final readonly class CreateEmployeeData
{
    /**
     * @param  list<EmployeeRole>  $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public array $roles,
        public ?DateTimeInterface $validFrom = null,
        public ?DateTimeInterface $validUntil = null,
    ) {}
}
