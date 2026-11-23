<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cuándo se cambió por última vez la contraseña del empleado. Las sesiones abiertas
// antes de ese momento se cierran en la siguiente petición (politicas.md §2): cambiar
// la contraseña debe sacar a quien tuviera la cuenta comprometida. Null = nunca se
// cambió desde el alta; no afecta a ninguna sesión.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};
