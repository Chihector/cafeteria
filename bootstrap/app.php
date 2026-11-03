<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Backup diario de SQLite y de las imágenes de productos (03:30, hora del servidor).
        $schedule->command('backup:run')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Enriquece el contexto de cualquier excepción reportada con datos de negocio,
        // sin reemplazar el logging por defecto de Laravel (stack + trace completo).
        $exceptions->reportable(function (Throwable $exception): void {
            $contexto = ['exception' => get_class($exception)];

            if (! app()->runningInConsole()) {
                $usuario = request()->user();
                $contexto = array_merge($contexto, [
                    'user_id' => $usuario?->id,
                    'role' => $usuario?->role,
                    'method' => request()->method(),
                    'path' => request()->path(),
                    'ip' => request()->ip(),
                ]);
            }

            Log::withContext($contexto);
        });
    })->create();
