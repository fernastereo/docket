<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Usuarios de plataforma (soporte / superadmin del proveedor), base central.
// Tabla propia y no un flag sobre la identidad de solicitante: ADR-005,
// amendment 2026-10-18. El MFA (columnas de secreto y recovery codes) entra en
// una migración aparte.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_users');
    }
};
