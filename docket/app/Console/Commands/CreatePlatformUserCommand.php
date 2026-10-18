<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Platform\Actions\CreatePlatformUser;
use App\Platform\Data\CreatePlatformUserData;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Alta de un usuario de plataforma desde consola. Es la única puerta de
 * entrada por ahora: no hay UI para crear superadmins. La contraseña se pide
 * de forma interactiva (no como argumento, para que no quede en el historial
 * del shell ni en `ps`).
 */
final class CreatePlatformUserCommand extends Command
{
    protected $signature = 'platform:create-user {name : Nombre completo} {email : Email de acceso}';

    protected $description = 'Crea un usuario de plataforma (soporte / superadmin del proveedor)';

    public function handle(CreatePlatformUser $createPlatformUser): int
    {
        $password = $this->secret('Contraseña (mínimo '.config('platform.password_min_length').' caracteres)');
        $confirmation = $this->secret('Repite la contraseña');

        if ($password !== $confirmation) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        try {
            $user = $createPlatformUser->handle(new CreatePlatformUserData(
                name: (string) $this->argument('name'),
                email: (string) $this->argument('email'),
                password: (string) $password,
            ));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info("Usuario de plataforma creado: {$user->email} ({$user->id})");

        return self::SUCCESS;
    }
}
