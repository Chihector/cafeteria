<?php

namespace App\Providers;

use App\Services\Printing\NetworkPrinterTransport;
use App\Services\Printing\PrinterTransport;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PrinterTransport::class, function () {
            return new NetworkPrinterTransport(
                config('printing.host'),
                (int) config('printing.port', 9100),
                (float) config('printing.timeout', 3),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
