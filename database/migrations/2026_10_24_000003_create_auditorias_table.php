<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 60);
            $table->string('modulo', 40);
            $table->string('descripcion');
            $table->nullableMorphs('modelo');
            $table->json('cambios')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['modulo', 'created_at']);
            $table->index(['accion', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
