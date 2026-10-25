<?php

declare(strict_types=1);

namespace App\Platform\Mfa;

use App\Platform\Models\PlatformUser;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Piezas de MFA de plataforma (TOTP RFC 6238 + códigos de recuperación). Las
 * tres Actions (iniciar, confirmar, verificar) se apoyan acá; este servicio no
 * decide flujos, solo comprueba y genera.
 *
 * - El secreto TOTP se guarda cifrado (cast `encrypted`): hay que leerlo para
 *   calcular el código esperado, por eso no se hashea.
 * - Los códigos de recuperación se guardan como HMAC (nunca el código). Son
 *   aleatorios de 50 bits, no contraseñas humanas: un HMAC rápido basta y
 *   permite comparar los 8 sin el costo de Argon2id.
 * - Un código TOTP no se acepta dos veces (`mfa_last_used_timestamp`).
 */
final class PlatformMfa
{
    /** Sin 0/O/1/I/L: se teclean a mano desde un papel. */
    private const RECOVERY_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly Google2FA $google2fa) {}

    public function newSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function provisioningUri(PlatformUser $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl((string) config('platform.mfa.issuer'), $user->email, $secret);
    }

    /** QR como SVG en `data:` URI: se pinta con <img>, sin HTML dinámico, y el secreto no sale a ningún servicio externo. */
    public function qrDataUri(string $uri): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($uri));
    }

    /** Valida un código de 6 dígitos y, si es válido, lo marca como usado. */
    public function verifyTotp(PlatformUser $user, string $code): bool
    {
        if ($user->mfa_secret === null || preg_match('/^\d{6}$/', $code) !== 1) {
            return false;
        }

        $window = (int) config('platform.mfa.window');

        // La librería devuelve `true` (no el paso) si no hay un paso previo, y
        // sin el paso no hay protección contra reutilización. Siempre se pasa
        // uno: el último usado, o el límite inferior de la ventana actual.
        $oldest = $this->google2fa->getTimestamp() - $window - 1;
        $step = $this->google2fa->verifyKeyNewer(
            $user->mfa_secret,
            $code,
            max($user->mfa_last_used_timestamp ?? 0, $oldest),
            $window,
        );

        if (! is_int($step)) {
            return false;
        }

        $user->forceFill(['mfa_last_used_timestamp' => $step])->save();

        return true;
    }

    /**
     * Valida un código de recuperación y, si es válido, lo consume.
     */
    public function consumeRecoveryCode(PlatformUser $user, string $code): bool
    {
        $candidate = $this->hashRecoveryCode($code);
        $stored = $user->mfa_recovery_codes ?? [];

        $matched = null;
        foreach ($stored as $index => $hash) {
            if (hash_equals($hash, $candidate)) {
                $matched = $index;
            }
        }

        if ($matched === null) {
            return false;
        }

        unset($stored[$matched]);
        $user->forceFill(['mfa_recovery_codes' => array_values($stored)])->save();

        return true;
    }

    /**
     * Genera los códigos nuevos, guarda sus HMAC y devuelve los códigos en
     * claro: es la única vez que existen fuera de la cabeza de quien los anota.
     *
     * @return list<string>
     */
    public function issueRecoveryCodes(PlatformUser $user): array
    {
        $codes = [];
        for ($i = 0; $i < (int) config('platform.mfa.recovery_codes'); $i++) {
            $raw = '';
            for ($j = 0; $j < 10; $j++) {
                $raw .= self::RECOVERY_ALPHABET[random_int(0, strlen(self::RECOVERY_ALPHABET) - 1)];
            }
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        $user->forceFill([
            'mfa_recovery_codes' => array_map($this->hashRecoveryCode(...), $codes),
        ])->save();

        return $codes;
    }

    /** Falla con 429 si ya se agotaron los intentos del segundo paso. */
    public function ensureNotLocked(PlatformUser $user): void
    {
        $key = $this->throttleKey($user);

        if (RateLimiter::tooManyAttempts($key, (int) config('platform.mfa.max_attempts'))) {
            $seconds = RateLimiter::availableIn($key);

            throw new ThrottleRequestsException(
                __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
                null,
                ['Retry-After' => $seconds],
            );
        }
    }

    /**
     * @throws ValidationException siempre: registra el fallo y responde 422
     */
    public function fail(PlatformUser $user): never
    {
        RateLimiter::hit($this->throttleKey($user), (int) config('platform.mfa.decay_seconds'));

        throw ValidationException::withMessages(['code' => __('The code is not valid.')]);
    }

    public function clearAttempts(PlatformUser $user): void
    {
        RateLimiter::clear($this->throttleKey($user));
    }

    private function hashRecoveryCode(string $code): string
    {
        $normalized = strtoupper(str_replace(['-', ' '], '', $code));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    private function throttleKey(PlatformUser $user): string
    {
        return 'platform-mfa|'.$user->id;
    }
}
