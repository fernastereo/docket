<?php

declare(strict_types=1);

namespace App\Platform\Data;

/** Entrada del alta de un usuario de plataforma. */
final readonly class CreatePlatformUserData
{
    public function __construct(
        public string $name,
        public string $email,
        #[\SensitiveParameter]
        public string $password,
    ) {}
}
