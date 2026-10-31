<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Roles de empleado en las curadurías que YA existen: el sembrado de
// ProvisionTenant solo corre al dar de alta una curaduría nueva, y las
// existentes reciben las tablas por `tenants:migrate` pero no los roles. Es una
// foto fija de los roles de ese momento (no depende del enum, que cambiará);
// un rol nuevo en EmployeeRole lleva su propia migración. Idempotente.
return new class extends Migration
{
    private const ROLES = [
        'curator', 'admin', 'manager',
        'architect', 'architect_assistant',
        'engineer', 'engineer_assistant',
        'lawyer', 'lawyer_assistant',
        'front_desk', 'records', 'accounting',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::ROLES as $name) {
            DB::table('roles')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'employee',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('guard_name', 'employee')->whereIn('name', self::ROLES)->delete();
    }
};
