<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Persistence;

use Src\Identidade\Domain\Entities\DefinicaoDePapel;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\Repositories\DefinicaoDePapelRepositoryInterface;

final class DefinicaoDePapelEloquentRepository implements DefinicaoDePapelRepositoryInterface
{
    public function buscar(Papel $papel): DefinicaoDePapel
    {
        $role = RoleModel::garantir($papel);

        return new DefinicaoDePapel($papel, $role->descricao, $role->sigla);
    }

    public function nomeEmUso(string $nome, Papel $ignorar): bool
    {
        return RoleModel::query()->where('nome', '!=', $ignorar->value)
            ->whereRaw('LOWER(descricao) = ?', [mb_strtolower($nome)])->exists();
    }

    public function siglaEmUso(string $sigla, Papel $ignorar): bool
    {
        return RoleModel::query()->where('nome', '!=', $ignorar->value)
            ->whereRaw('LOWER(sigla) = ?', [mb_strtolower($sigla)])->exists();
    }

    public function salvar(DefinicaoDePapel $definicao): void
    {
        RoleModel::garantir($definicao->papel)
            ->forceFill(['descricao' => $definicao->getNome(), 'sigla' => $definicao->getSigla()])
            ->save();
    }
}
