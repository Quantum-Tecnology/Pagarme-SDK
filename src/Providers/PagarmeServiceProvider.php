<?php

declare(strict_types = 1);

namespace QuantumTecnology\PagarmeSDK\Providers;

use Illuminate\Support\ServiceProvider;

class PagarmeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/pagarme.php' => config_path('pagarme.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pagarme.php', 'services'
        );
    }
}
