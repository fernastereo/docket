<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Data\PlatformLoginData;
use App\Platform\Models\PlatformUser;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login de un usuario de plataforma con el guard `platform`.
 *
 * - Bloqueo temporal tras 5 fallos en 15 minutos, por (email + IP)
 *   (politicas.md §2).
 * - Mismo mensaje para email inexistente, contraseña errónea y cuenta
 *   deshabilitada: no se revela cuál de las tres fue.
 * - No toca la sesión: regenerarla y sellar los tiempos es del controlador,
 *   que es quien tiene la request.
 */
final class AuthenticatePlatformUser
{
    /**
     * @throws ValidationException credenciales inválidas
     * @throws ThrottleRequestsException demasiados intentos fallidos
     */
    public function handle(PlatformLoginData $data): PlatformUser
    {
        $key = $this->throttleKey($data);
        $max = (int) config('platform.login.max_attempts');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new ThrottleRequestsException(
                __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => ceil(RateLimiter::availableIn($key) / 60)]),
                null,
                ['Retry-After' => RateLimiter::availableIn($key)],
            );
        }

        $guard = Auth::guard('platform');
        $provider = Auth::createUserProvider('platform_users');

        $user = $provider->retrieveByCredentials(['email' => mb_strtolower(trim($data->email))]);

        if ($user === null) {
            // Gasta el mismo tiempo de hash que una contraseña real: sin esto,
            // el tiempo de respuesta delata qué emails existen.
            Hash::make($data->password);
        } elseif (! $provider->validateCredentials($user, ['password' => $data->password])) {
            $user = null;
        }

        // Una cuenta deshabilitada no entra aunque la contraseña sea correcta.
        if (! $user instanceof PlatformUser || $user->isDisabled()) {
            $user = null;
        }

        if ($user === null) {
            RateLimiter::hit($key, (int) config('platform.login.decay_seconds'));

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $guard->login($user);

        RateLimiter::clear($key);
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    private function throttleKey(PlatformLoginData $data): string
    {
        return 'platform-login|'.Str::transliterate(mb_strtolower(trim($data->email))).'|'.$data->ip;
    }
}
