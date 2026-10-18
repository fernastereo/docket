<?php

declare(strict_types=1);

namespace App\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Subdominio de una curaduría. Es parte de la URL pública y del host con el
 * que stancl resuelve el tenant, así que se valida estricto: una etiqueta DNS
 * en minúsculas, sin reservados y sin que otro tenant ya lo ocupe.
 *
 * Una sola regla para todas las puertas de entrada: el comando artisan hoy,
 * el Form Request del panel de plataforma mañana.
 */
final class TenantSubdomain implements ValidationRule
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 40;

    public static function hostFor(string $subdomain): string
    {
        return $subdomain.'.'.config('platform.tenant_base_domain');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El subdominio debe ser texto.');

            return;
        }

        if (strlen($value) < self::MIN_LENGTH || strlen($value) > self::MAX_LENGTH) {
            $fail(sprintf('El subdominio debe tener entre %d y %d caracteres.', self::MIN_LENGTH, self::MAX_LENGTH));

            return;
        }

        // Etiqueta DNS: minúsculas, dígitos y guiones; ni empieza ni termina en guion.
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $value)) {
            $fail('El subdominio solo admite minúsculas, números y guiones, y no puede empezar ni terminar en guion.');

            return;
        }

        // `xn--` marca punycode (dominios internacionalizados): vector clásico de suplantación visual.
        if (str_contains($value, '--')) {
            $fail('El subdominio no puede contener guiones consecutivos.');

            return;
        }

        if (in_array($value, config('platform.reserved_subdomains'), true)) {
            $fail('Ese subdominio está reservado por la plataforma.');

            return;
        }

        $domainModel = config('tenancy.domain_model');

        if ($domainModel::query()->where('domain', self::hostFor($value))->exists()) {
            $fail('Ese subdominio ya está en uso por otra curaduría.');
        }
    }
}
