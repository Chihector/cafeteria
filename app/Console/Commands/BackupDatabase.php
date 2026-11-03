<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Copia de seguridad diaria de la cafetería:
 *  - VACUUM INTO (copia en caliente, segura con la DB abierta) del sqlite.
 *  - Copia recursiva de storage/app/public (imágenes de productos).
 *  - Rotación automática: conserva los últimos N backups (BACKUPS_TO_KEEP, por defecto 7).
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:run
        {--keep= : Número de backups a conservar (por defecto BACKUPS_TO_KEEP o 7)}';

    protected $description = 'Backup en caliente del SQLite + storage/public, con rotación';

    public function handle(): int
    {
        $origenDb = config('database.connections.sqlite.database');

        if (! is_string($origenDb) || ! File::exists($origenDb)) {
            $this->error("Base de datos SQLite no encontrada en: {$origenDb}");

            return self::FAILURE;
        }

        $destinoRoot = storage_path('backups');
        $nombreDir = now()->format('Y-m-d_His');
        $destinoDir = $destinoRoot.DIRECTORY_SEPARATOR.$nombreDir;
        File::ensureDirectoryExists($destinoDir);

        // 1) Copia en caliente usando VACUUM INTO (transaccionalmente consistente).
        $destinoDb = $destinoDir.DIRECTORY_SEPARATOR.'database.sqlite';
        try {
            $this->vacuumInto($origenDb, $destinoDb);
        } catch (\Throwable $e) {
            $this->error('VACUUM INTO falló: '.$e->getMessage());
            // No dejar carpeta a medias si falla.
            File::deleteDirectory($destinoDir);

            return self::FAILURE;
        }

        // 2) Copia de uploads (public/storage -> imágenes de productos).
        $fuenteStorage = rtrim(Storage::disk('public')->path(''), '/\\');
        $destinoStorage = $destinoDir.DIRECTORY_SEPARATOR.'public';
        if (File::isDirectory($fuenteStorage)) {
            File::ensureDirectoryExists($destinoStorage);
            File::copyDirectory($fuenteStorage, $destinoStorage);
        }

        // 3) Copiar .env para poder restaurar la configuración junto con los datos.
        if (File::exists(base_path('.env'))) {
            File::copy(base_path('.env'), $destinoDir.DIRECTORY_SEPARATOR.'env.backup');
        }

        // 4) Rotación: conserva los N más recientes.
        $keep = max(1, (int) ($this->option('keep') ?? env('BACKUPS_TO_KEEP', 7)));
        $this->pruneBackups($destinoRoot, $keep);

        $tamanoMb = $this->dirSizeInMb($destinoDir);
        $this->info("Backup completo en {$destinoDir} ({$tamanoMb} MB)");

        return self::SUCCESS;
    }

    /**
     * SQLite exige que el archivo destino no exista; se garantiza con delete.
     */
    private function vacuumInto(string $origen, string $destino): void
    {
        if (File::exists($destino)) {
            File::delete($destino);
        }

        $pdo = new \PDO('sqlite:'.$origen);
        // El driver PDO de sqlite acepta VACUUM INTO con escape de comillas simples.
        $destinoEscapado = str_replace("'", "''", $destino);
        $pdo->exec("VACUUM INTO '{$destinoEscapado}'");
        $pdo = null;
    }

    /**
     * Conserva solo los $keep directorios de backup más recientes.
     */
    private function pruneBackups(string $destinoRoot, int $keep): void
    {
        $candidatos = collect(File::directories($destinoRoot))
            ->sortBy(fn (string $dir) => File::lastModified($dir))
            ->values();

        $excedente = $candidatos->count() - $keep;
        if ($excedente <= 0) {
            return;
        }

        $candidatos->take($excedente)->each(function (string $dir) {
            File::deleteDirectory($dir);
            $this->line("Backup antiguo eliminado: {$dir}");
        });
    }

    private function dirSizeInMb(string $dir): string
    {
        $bytes = 0;
        foreach (File::allFiles($dir) as $file) {
            $bytes += $file->getSize();
        }

        return number_format($bytes / 1024 / 1024, 2);
    }
}
