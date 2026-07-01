<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Repositories\ProjetoEloquentRepository;

final class ProjetosServiceProvider extends ServiceProvider
{
    /**
     * Registra os binds de contratos para implementações de infraestrutura.
     */
    public array $bindings = [
        ProjetoRepositoryInterface::class => ProjetoEloquentRepository::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}