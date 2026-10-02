<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Em desenvolvimento, falha alto se algum atributo não listado em $fillable for atribuído (anti mass assignment).
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
