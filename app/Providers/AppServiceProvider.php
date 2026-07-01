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
        $this->app->bind(
            \Src\Fornecedores\Domain\Repositories\FornecedorRepositoryInterface::class,
            \Src\Fornecedores\Infrastructure\Persistence\Eloquent\Repositories\FornecedorEloquentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
