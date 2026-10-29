<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\DataTransferObjects\EmployeeLoginData;
use App\Domain\Identity\Models\Employee;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login de un empleado con el guard `employee`, dentro de la base de su
 * curaduría (la tenancy ya está inicializada por el subdominio).
 *
 * - Bloqueo temporal tras 5 fallos en 15 minutos, por (curaduría + email + IP).
 * - Mismo mensaje para email inexistente, contraseña errónea, cuenta sin
 *   contraseña (invitación pendiente) y cuenta inactiva o fuera de vigencia:
 *   no se revela cuál fue.
 * - No toca la sesión: regenerarla y sellar los tiempos es del controlador.
 */
final class AuthenticateEmployee
{
    /**
     * @throws ValidationException credenciales inválidas
     * @throws ThrottleRequestsException demasiados intentos fallidos
     */
    public function handle(EmployeeLoginData $data): Employee
    {
        $key = $this->throttleKey($data);

        if (RateLimiter::tooManyAttempts($key, (int) config('employee.login.max_attempts'))) {
            $seconds = RateLimiter::availableIn($key);

            throw new ThrottleRequestsException(
                __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
                null,
                ['Retry-After' => $seconds],
            );
        }

        $provider = Auth::createUserProvider('employees');
        $employee = $provider->retrieveByCredentials(['email' => mb_strtolower(trim($data->email))]);

        if (! $employee instanceof Employee || $employee->password === null) {
            // Gasta el mismo tiempo de hash que una contraseña real: sin esto,
            // el tiempo de respuesta delata qué emails existen.
            Hash::make($data->password);
            $employee = null;
        } elseif (! $provider->validateCredentials($employee, ['password' => $data->password]) || ! $employee->isActive()) {
            $employee = null;
        }

        if ($employee === null) {
            RateLimiter::hit($key, (int) config('employee.login.decay_seconds'));

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        Auth::guard('employee')->login($employee);

        RateLimiter::clear($key);
        $employee->forceFill(['last_login_at' => now()])->save();

        return $employee;
    }

    private function throttleKey(EmployeeLoginData $data): string
    {
        $tenant = (string) tenant()?->getTenantKey();

        return 'employee-login|'.$tenant.'|'.Str::transliterate(mb_strtolower(trim($data->email))).'|'.$data->ip;
    }
}
