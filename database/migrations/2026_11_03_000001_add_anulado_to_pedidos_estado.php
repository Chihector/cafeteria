<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade el estado terminal "anulado" a pedidos.estado.
 *
 * SQLite no permite alterar un CHECK constraint, así que se reconstruye la
 * tabla copiando los datos (transaccionalmente atómico). MySQL usa la
 * sintaxis nativa de ENUM. PostgreSQL intenta reemplazar el CHECK conocido.
 */
return new class extends Migration
{
    private const ESTADOS_ANTERIORES = "'pendiente', 'preparando', 'listo', 'entregado', 'pagado'";

    private const ESTADOS_NUEVOS = "'pendiente', 'preparando', 'listo', 'entregado', 'pagado', 'anulado'";

    public function up(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->reconstruirTabla(self::ESTADOS_ANTERIORES, self::ESTADOS_NUEVOS),
            'mysql' => DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente', 'preparando', 'listo', 'entregado', 'pagado', 'anulado') NOT NULL DEFAULT 'pendiente'"),
            'pgsql' => $this->postgresCheck(),
            default => throw new RuntimeException('Driver de base de datos no soportado para ampliar pedidos.estado.'),
        };
    }

    public function down(): void
    {
        // Las filas anuladas no caben en el constraint anterior; se conservan
        // convirtiéndolas a "pendiente" para no perder el histórico.
        DB::statement("UPDATE pedidos SET estado = 'pendiente' WHERE estado = 'anulado'");

        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->reconstruirTabla(self::ESTADOS_NUEVOS, self::ESTADOS_ANTERIORES),
            'mysql' => DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente', 'preparando', 'listo', 'entregado', 'pagado') NOT NULL DEFAULT 'pendiente'"),
            'pgsql' => $this->postgresCheck(false),
            default => throw new RuntimeException('Driver de base de datos no soportado para ampliar pedidos.estado.'),
        };
    }

    private function postgresCheck(bool $nuevo = true): void
    {
        $estados = $nuevo ? self::ESTADOS_NUEVOS : self::ESTADOS_ANTERIORES;
        DB::statement('ALTER TABLE pedidos DROP CONSTRAINT IF EXISTS pedidos_estado_check');
        DB::statement("ALTER TABLE pedidos ADD CONSTRAINT pedidos_estado_check CHECK (estado IN ({$estados}))");
    }

    private function reconstruirTabla(string $anteriores, string $nuevos): void
    {
        $fila = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'pedidos'");

        if (! $fila || ! isset($fila->sql)) {
            throw new RuntimeException('No se encontró la tabla pedidos.');
        }

        $sql = (string) $fila->sql;
        $nuevo = str_replace($anteriores, $nuevos, $sql);

        if ($nuevo === $sql) {
            throw new RuntimeException('No se encontró la lista de estados de pedidos.estado para reemplazar.');
        }

        $nuevo = str_replace('CREATE TABLE "pedidos"', 'CREATE TABLE "pedidos_migracion"', $nuevo);

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('BEGIN');
        try {
            DB::statement($nuevo);
            DB::statement('INSERT INTO pedidos_migracion SELECT * FROM pedidos');
            DB::statement('DROP TABLE pedidos');
            DB::statement('ALTER TABLE pedidos_migracion RENAME TO pedidos');
            DB::statement('CREATE INDEX IF NOT EXISTS pedidos_estado_index ON pedidos(estado)');
            DB::statement('CREATE INDEX IF NOT EXISTS pedidos_created_at_index ON pedidos(created_at)');
            DB::statement('COMMIT');
        } catch (Throwable $e) {
            DB::statement('ROLLBACK');
            throw $e;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
