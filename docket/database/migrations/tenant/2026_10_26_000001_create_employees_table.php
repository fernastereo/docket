<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cuentas de empleado de la curaduría, en la base del TENANT (ADR-005,
// amendment 2026-09-02): credencial + vigencia + estado. Los roles viven en las
// tablas de spatie/laravel-permission (migración siguiente). `password` es
// nullable: una cuenta recién invitada aún no tiene contraseña. El MFA entra en
// una migración aparte.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
