<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Support\SingleUseToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Invitación de un solo uso para que un empleado fije su contraseña. Solo se
 * guarda el hash del token; el token en claro viaja únicamente en el correo.
 *
 * @property string $id
 * @property string $employee_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property-read Employee $employee
 */
class EmployeeInvitation extends Model
{
    use HasUuids, SingleUseToken;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Emite un enlace nuevo para el empleado y devuelve el token en claro (única
     * vez que existe). Los enlaces anteriores sin usar quedan invalidados.
     */
    public static function issueFor(Employee $employee, int $days): string
    {
        $token = Str::random(64);

        DB::transaction(function () use ($employee, $token, $days): void {
            self::query()->where('employee_id', $employee->id)->whereNull('used_at')->delete();

            self::create([
                'employee_id' => $employee->id,
                'token_hash' => self::hashToken($token),
                'expires_at' => now()->addDays($days),
            ]);
        });

        return $token;
    }
}
