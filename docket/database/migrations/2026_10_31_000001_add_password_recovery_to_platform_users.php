<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Recuperación de contraseña de los usuarios de plataforma (base central).
// - platform_password_resets: enlace de un solo uso por correo; solo se guarda el
//   hash del token (el token en claro viaja únicamente en el correo).
// - platform_users.password_changed_at: cuándo se cambió por última vez la
//   contraseña. Las sesiones abiertas antes se cierran (politicas.md §2). Null =
//   nunca se cambió desde el alta; no afecta a ninguna sesión.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_password_resets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('platform_users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });

        Schema::dropIfExists('platform_password_resets');
    }
};
