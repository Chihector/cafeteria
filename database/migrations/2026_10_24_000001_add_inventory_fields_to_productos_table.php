<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // El control de stock es optativo para no romper productos ya existentes.
            $table->boolean('controlar_stock')->default(false)->after('disponible');
            $table->integer('stock')->default(0)->after('controlar_stock');
            $table->integer('stock_minimo')->default(0)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['controlar_stock', 'stock', 'stock_minimo']);
        });
    }
};
