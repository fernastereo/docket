<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Columnas reales del catálogo de curadurías (ADR-002, ADR-010). Se agregan en
// una migración aparte, sin editar la de stancl, porque esa ya corrió en dev.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name');
            $table->string('status')->default('provisioning')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('provisioned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['name', 'status', 'failure_reason', 'provisioned_at']);
        });
    }
};
