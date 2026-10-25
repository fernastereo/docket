<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MFA (TOTP) de los usuarios de plataforma — ADR-017, politicas.md §2.
// - mfa_secret: secreto TOTP CIFRADO con APP_KEY (cast `encrypted`); hay que
//   poder leerlo para calcular el código esperado, por eso no se hashea.
// - mfa_confirmed_at: null = sin MFA activa (el secreto, si existe, es de una
//   activación a medias que aún no se confirmó con un código).
// - mfa_last_used_timestamp: último paso de 30 s aceptado; un código no puede
//   usarse dos veces.
// - mfa_recovery_codes: HMAC de cada código de recuperación (nunca el código);
//   al usar uno se elimina de la lista.
// Todas nullable: los usuarios existentes entran y se les exige activar MFA.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_users', function (Blueprint $table) {
            $table->text('mfa_secret')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->unsignedBigInteger('mfa_last_used_timestamp')->nullable();
            $table->text('mfa_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_confirmed_at', 'mfa_last_used_timestamp', 'mfa_recovery_codes']);
        });
    }
};
