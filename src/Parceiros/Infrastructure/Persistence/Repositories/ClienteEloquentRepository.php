<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Persistence\Repositories;

use Src\Parceiros\Domain\Entities\Cliente;
use Src\Parceiros\Domain\Repositories\ClienteRepositoryInterface;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use Src\Parceiros\Infrastructure\Persistence\Mappers\ParceirosMapper;

final class ClienteEloquentRepository implements ClienteRepositoryInterface
{
    public function buscarPorId(string $id): ?Cliente
    {
        $model = ClienteModel::query()->find($id);

        return $model ? ParceirosMapper::clienteParaDominio($model) : null;
    }

    public function cnpjEmUso(string $cnpj, ?string $ignorarId = null): bool
    {
        return ClienteModel::query()
            ->where('cnpj', $cnpj)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();
    }

    public function salvar(Cliente $cliente): void
    {
        ClienteModel::query()->updateOrCreate(
            ['id' => $cliente->getId()],
            ParceirosMapper::dadosParaPersistencia($cliente->getDados(), $cliente->isAtivo()),
        );
    }
}
