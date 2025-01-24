<?php

namespace GustavoSantarosa\PagarmeSDK\Providers;

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
        // Publica o arquivo de configuração
        $this->publishes([
            __DIR__.'/../config/pagarme.php' => config_path('pagarme.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Mescla a configuração do pacote com a configuração da aplicação
        $this->mergeConfigFrom(
            __DIR__.'/../config/pagarme.php', 'services.pagarme'
        );
    }
}
