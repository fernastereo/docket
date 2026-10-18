<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Data\CreatePlatformUserData;
use App\Platform\Models\PlatformUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Única vía para crear usuarios de plataforma. Hoy la invoca el comando
 * `platform:create-user`; no hay alta desde la UI (un superadmin solo lo crea
 * quien opera la plataforma). La política de contraseña vive acá, no en el
 * comando, para que cualquier puerta de entrada futura la herede.
 */
final class CreatePlatformUser
{
    /**
     * @throws ValidationException
     */
    public function handle(CreatePlatformUserData $data): PlatformUser
    {
        $validated = Validator::make(
            [
                'name' => $data->name,
                'email' => mb_strtolower(trim($data->email)),
                'password' => $data->password,
            ],
            [
                'name' => ['required', 'string', 'min:3', 'max:150'],
                'email' => ['required', 'email:rfc', 'max:254', 'unique:platform_users,email'],
                // 12+ caracteres (politicas.md §2) y chequeo contra brechas
                // conocidas (HIBP, k-anonymity). Sin reglas de composición ni
                // tope bajo (NIST 800-63B).
                'password' => ['required', 'string', Password::min((int) config('platform.password_min_length'))->uncompromised()],
            ],
        )->validate();

        return PlatformUser::create($validated);
    }
}
