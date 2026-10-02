<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Queries;

use Src\Identidade\Application\Queries\PapeisQuery;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Infrastructure\Persistence\RoleModel;

final class EloquentPapeisQuery implements PapeisQuery
{
    public function nomes(): array
    {
        $roles = RoleModel::query()->get(['nome', 'descricao', 'sigla'])->keyBy('nome');

        $nomes = [];
        foreach (Papel::cases() as $papel) { // papel ainda sem linha no banco usa o padrão
            $role = $roles->get($papel->value);
            $nomes[$papel->value] = [
                'nome' => $role?->descricao ?? $papel->nomePadrao(),
                'sigla' => $role === null ? $papel->siglaPadrao() : $role->sigla,
            ];
        }

        return $nomes;
    }

    public function listar(): array
    {
        $usuarios = RoleModel::query()->withCount('usuarios')->pluck('usuarios_count', 'nome');
        $nomes = $this->nomes();

        return array_map(fn (Papel $papel) => [
            'papel' => $papel->value,
            ...$nomes[$papel->value],
            'usuarios' => (int) ($usuarios[$papel->value] ?? 0),
        ], Papel::cases());
    }
}
