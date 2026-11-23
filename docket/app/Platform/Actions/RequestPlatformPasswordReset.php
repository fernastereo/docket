<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Mail\PlatformPasswordResetMail;
use App\Platform\Models\PlatformPasswordReset;
use App\Platform\Models\PlatformUser;
use App\Platform\Support\CentralUrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * "Olvidé mi contraseña" del panel de plataforma. Cuenta inexistente o
 * deshabilitada: no pasa nada, y el que llama no puede distinguirlo (el
 * controlador responde igual). La MFA no se toca: el correo solo no da acceso.
 *
 * Como máximo 3 correos cada 15 minutos por cuenta, se cuenten o no los pedidos
 * de cuentas que no existen: así el límite tampoco delata qué correos existen.
 */
final class RequestPlatformPasswordReset
{
    private const MAX_PER_WINDOW = 3;

    private const WINDOW_SECONDS = 15 * 60;

    public function handle(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $key = 'platform-password-reset|'.Str::transliterate($email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_WINDOW)) {
            return;
        }

        RateLimiter::hit($key, self::WINDOW_SECONDS);

        $user = PlatformUser::query()->where('email', $email)->first();

        if ($user === null || $user->isDisabled()) {
            return;
        }

        $minutes = (int) config('platform.password_reset.valid_minutes');
        $token = PlatformPasswordReset::issueFor($user, $minutes);

        Mail::to($user->email)->queue(new PlatformPasswordResetMail(
            userName: $user->name,
            url: CentralUrl::to('/platform/reset-password/'.$token),
            validMinutes: $minutes,
        ));
    }
}
