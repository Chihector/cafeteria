<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normaliza categorías creadas con los valores antiguos del formulario.
     */
    public function up(): void
    {
        DB::table('productos')->where('categoria', 'cafes')->update(['categoria' => 'coffee']);
        DB::table('productos')->where('categoria', 'pasteleria')->update(['categoria' => 'pastries']);
        DB::table('productos')->where('categoria', 'bebidas_frias')->update(['categoria' => 'cold_drinks']);
    }

    public function down(): void
    {
        // No se revierte: los valores nuevos son los canónicos.
    }
};
