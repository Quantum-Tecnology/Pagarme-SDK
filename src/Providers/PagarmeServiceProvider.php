<?php

declare(strict_types = 1);

namespace QuantumTecnology\PagarmeSDK\Providers;

use Illuminate\Support\ServiceProvider;

class PagarmeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/pagarme.php' => config_path('pagarme.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pagarme.php', 'services'
        );
    }
}
