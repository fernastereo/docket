<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataTransferObjects;

/** Entrada del login de un empleado. Solo transporta datos. */
final readonly class EmployeeLoginData
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter]
        public string $password,
        public string $ip,
    ) {}
}
