<?php

namespace App\Providers;

use App\Events\PedidoEstadoActualizado;
use App\Listeners\EnviarNotificacionCambioEstado;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       /* if($this->app->enviroment('production')) {
            \URL::forceScheme('https');
        }*/

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Notificaciones push transaccionales
        Event::listen(
            PedidoEstadoActualizado::class,
            EnviarNotificacionCambioEstado::class
        );
    }
}