<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Infrastructure\Queries\EloquentUsuariosQuery;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Domain\Repositories\ClienteRepositoryInterface;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Parceiros\Infrastructure\Persistence\Repositories\ClienteEloquentRepository;
use Src\Parceiros\Infrastructure\Persistence\Repositories\FornecedorEloquentRepository;
use Src\Parceiros\Infrastructure\Queries\EloquentParceirosQuery;
use Src\Projetos\Application\Ports\VerificadorDeParceiros;
use Src\Projetos\Application\Ports\VerificadorDeUsuarios;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Infrastructure\Adapters\VerificadorDeParceirosEloquent;
use Src\Projetos\Infrastructure\Adapters\VerificadorDeUsuariosViaIdentidade;
use Src\Projetos\Infrastructure\Persistence\Repositories\ProjetoEloquentRepository;
use Src\Projetos\Infrastructure\Queries\EloquentProjetoQuery;
use Src\Shared\Application\Ports\GeradorDeId;
use Src\Shared\Application\Ports\Relogio;
use Src\Shared\Infrastructure\GeradorDeIdUuid;
use Src\Shared\Infrastructure\RelogioDoSistema;

/**
 * Liga as interfaces (Domain/Application) às implementações (Infrastructure) de todos os contextos.
 */
final class ContextosServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // Shared
        GeradorDeId::class => GeradorDeIdUuid::class,
        Relogio::class => RelogioDoSistema::class,

        // Identidade
        UsuariosQuery::class => EloquentUsuariosQuery::class,

        // Parceiros
        ClienteRepositoryInterface::class => ClienteEloquentRepository::class,
        FornecedorRepositoryInterface::class => FornecedorEloquentRepository::class,
        ParceirosQuery::class => EloquentParceirosQuery::class,

        // Projetos
        ProjetoRepositoryInterface::class => ProjetoEloquentRepository::class,
        ProjetoQuery::class => EloquentProjetoQuery::class,
        VerificadorDeParceiros::class => VerificadorDeParceirosEloquent::class,
        VerificadorDeUsuarios::class => VerificadorDeUsuariosViaIdentidade::class,
    ];
}
