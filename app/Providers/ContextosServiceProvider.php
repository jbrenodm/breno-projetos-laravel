<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Identidade\Application\Ports\AcessosDoUsuario;
use Src\Identidade\Application\Ports\HashDeSenha;
use Src\Identidade\Application\Queries\PapeisQuery;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Domain\Repositories\DefinicaoDePapelRepositoryInterface;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Infrastructure\Adapters\AcessosDoUsuarioSanctum;
use Src\Identidade\Infrastructure\Adapters\HashDeSenhaLaravel;
use Src\Identidade\Infrastructure\Persistence\DefinicaoDePapelEloquentRepository;
use Src\Identidade\Infrastructure\Persistence\UsuarioEloquentRepository;
use Src\Identidade\Infrastructure\Queries\EloquentPapeisQuery;
use Src\Identidade\Infrastructure\Queries\EloquentUsuariosQuery;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Domain\Repositories\ClienteRepositoryInterface;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Parceiros\Infrastructure\Persistence\Repositories\ClienteEloquentRepository;
use Src\Parceiros\Infrastructure\Persistence\Repositories\FornecedorEloquentRepository;
use Src\Parceiros\Infrastructure\Queries\EloquentParceirosQuery;
use Src\Projetos\Application\Ports\NomesDosResponsaveis;
use Src\Projetos\Application\Ports\PermissaoDeAdmin as PermissaoDeAdminEmProjetos;
use Src\Projetos\Application\Ports\VerificadorDeParceiros;
use Src\Projetos\Application\Ports\VerificadorDeResponsaveis;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Application\Queries\TiposAtividadeQuery;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\Repositories\TipoAtividadeRepositoryInterface;
use Src\Projetos\Infrastructure\Adapters\NomesDosResponsaveisViaIdentidade;
use Src\Projetos\Infrastructure\Adapters\PermissaoDeAdminViaIdentidade as PermissaoDeAdminEmProjetosViaIdentidade;
use Src\Projetos\Infrastructure\Adapters\VerificadorDeParceirosEloquent;
use Src\Projetos\Infrastructure\Adapters\VerificadorDeResponsaveisViaResponsaveis;
use Src\Projetos\Infrastructure\Persistence\Repositories\ProjetoEloquentRepository;
use Src\Projetos\Infrastructure\Persistence\Repositories\TipoAtividadeEloquentRepository;
use Src\Projetos\Infrastructure\Queries\EloquentProjetoQuery;
use Src\Projetos\Infrastructure\Queries\EloquentTiposAtividadeQuery;
use Src\Responsaveis\Application\Ports\PermissaoDeAdmin;
use Src\Responsaveis\Application\Queries\ResponsaveisQuery;
use Src\Responsaveis\Domain\Repositories\ResponsavelRepositoryInterface;
use Src\Responsaveis\Infrastructure\Adapters\PermissaoDeAdminViaIdentidade;
use Src\Responsaveis\Infrastructure\Persistence\ResponsavelEloquentRepository;
use Src\Responsaveis\Infrastructure\Queries\EloquentResponsaveisQuery;
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
        UsuarioRepositoryInterface::class => UsuarioEloquentRepository::class,
        DefinicaoDePapelRepositoryInterface::class => DefinicaoDePapelEloquentRepository::class,
        PapeisQuery::class => EloquentPapeisQuery::class,
        HashDeSenha::class => HashDeSenhaLaravel::class,
        AcessosDoUsuario::class => AcessosDoUsuarioSanctum::class,

        // Parceiros
        ClienteRepositoryInterface::class => ClienteEloquentRepository::class,
        FornecedorRepositoryInterface::class => FornecedorEloquentRepository::class,
        ParceirosQuery::class => EloquentParceirosQuery::class,

        // Responsáveis
        ResponsavelRepositoryInterface::class => ResponsavelEloquentRepository::class,
        ResponsaveisQuery::class => EloquentResponsaveisQuery::class,
        PermissaoDeAdmin::class => PermissaoDeAdminViaIdentidade::class,

        // Projetos
        ProjetoRepositoryInterface::class => ProjetoEloquentRepository::class,
        ProjetoQuery::class => EloquentProjetoQuery::class,
        VerificadorDeParceiros::class => VerificadorDeParceirosEloquent::class,
        VerificadorDeResponsaveis::class => VerificadorDeResponsaveisViaResponsaveis::class,
        NomesDosResponsaveis::class => NomesDosResponsaveisViaIdentidade::class,
        TipoAtividadeRepositoryInterface::class => TipoAtividadeEloquentRepository::class,
        TiposAtividadeQuery::class => EloquentTiposAtividadeQuery::class,
        PermissaoDeAdminEmProjetos::class => PermissaoDeAdminEmProjetosViaIdentidade::class,
    ];
}
