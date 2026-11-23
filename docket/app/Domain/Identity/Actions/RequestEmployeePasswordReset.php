<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Employee;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * "Olvidé mi contraseña": el empleado pide un enlace desde el login de su curaduría.
 * Quien no tiene contraseña todavía recibe de nuevo su invitación; quien ya la tiene,
 * un enlace de recuperación. Cuenta inexistente, suspendida o fuera de vigencia: no
 * pasa nada, y el que llama no puede distinguirlo (el controlador responde igual).
 *
 * Como máximo 3 correos cada 15 minutos por cuenta, se cuenten o no los pedidos de
 * cuentas que no existen: así tampoco el límite delata qué correos existen y nadie
 * puede inundar un buzón. Corre con la tenancy inicializada.
 */
final class RequestEmployeePasswordReset
{
    private const MAX_PER_WINDOW = 3;

    private const WINDOW_SECONDS = 15 * 60;

    public function __construct(
        private readonly InviteEmployee $invite,
        private readonly SendEmployeePasswordReset $reset,
    ) {}

    public function handle(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $key = 'employee-password-reset|'.tenant()?->getTenantKey().'|'.Str::transliterate($email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_WINDOW)) {
            return;
        }

        RateLimiter::hit($key, self::WINDOW_SECONDS);

        $employee = Employee::query()->where('email', $email)->first();

        if ($employee === null || ! $employee->isActive()) {
            return;
        }

        $employee->password === null
            ? $this->invite->handle($employee)
            : $this->reset->handle($employee);
    }
}
