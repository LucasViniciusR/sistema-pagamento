<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Repositories\UsuarioRepository::class, \App\Repositories\UsuarioRepository::class);
        $this->app->bind(\App\Repositories\CarteiraRepository::class, \App\Repositories\CarteiraRepository::class);
        $this->app->bind(\App\Repositories\TransferenciaRepository::class, \App\Repositories\TransferenciaRepository::class);
        $this->app->bind(\App\Contracts\NotificadorDeTransferencia::class, \App\Services\Notificacao\NotificadorKafka::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
