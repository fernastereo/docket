<?php

declare(strict_types=1);

namespace App\Platform\Data;

/** Entrada del login de plataforma. Solo transporta datos. */
final readonly class PlatformLoginData
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter]
        public string $password,
        public string $ip,
    ) {}
}
