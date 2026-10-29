<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MFA (TOTP) de los empleados, igual que la de plataforma (ADR-017,
// politicas.md §2): secreto cifrado con APP_KEY, último paso aceptado para que
// un código no se use dos veces, y HMAC de los códigos de recuperación. Todas
// nullable: la cuenta nace sin MFA; a curador y admin se les exige activarla en
// su primer acceso (EmployeeRole::requiresMfa()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('mfa_secret')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->unsignedBigInteger('mfa_last_used_timestamp')->nullable();
            $table->text('mfa_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_confirmed_at', 'mfa_last_used_timestamp', 'mfa_recovery_codes']);
        });
    }
};
