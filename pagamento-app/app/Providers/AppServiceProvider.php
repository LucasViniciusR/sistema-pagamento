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
        $this->app->bind(\App\Contracts\NotificadorDeTransferenciaInterface::class, \App\Services\Notificacao\NotificadorKafka::class);
        $this->app->bind(\App\Contracts\TransferenciaRepositoryInterface::class, \App\Repositories\TransferenciaRepository::class);
        $this->app->bind(\App\Contracts\AutorizadorDeTransferenciaInterface::class, \App\Services\ServicoAutorizacaoExterno::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
