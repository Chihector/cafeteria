<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index('estado', 'pedidos_estado_index');
            $table->index('created_at', 'pedidos_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_estado_index');
            $table->dropIndex('pedidos_created_at_index');
        });
    }
};
