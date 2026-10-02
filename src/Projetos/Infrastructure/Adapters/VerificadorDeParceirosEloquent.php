<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Adapters;

use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\FornecedorModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\SolucaoModel;
use Src\Projetos\Application\Ports\VerificadorDeParceiros;
use Src\Shared\Domain\Uuid;

final class VerificadorDeParceirosEloquent implements VerificadorDeParceiros
{
    public function clienteEstaAtivo(string $clienteId): bool
    {
        return Uuid::ehValido($clienteId)
            && ClienteModel::query()->whereKey($clienteId)->where('ativo', true)->exists();
    }

    public function fornecedorEstaAtivo(string $fornecedorId): bool
    {
        return Uuid::ehValido($fornecedorId)
            && FornecedorModel::query()->whereKey($fornecedorId)->where('ativo', true)->exists();
    }

    public function solucaoAtivaPertenceAoFornecedor(string $solucaoId, string $fornecedorId): bool
    {
        return Uuid::ehValido($solucaoId)
            && SolucaoModel::query()->whereKey($solucaoId)
                ->where('fornecedor_id', $fornecedorId)
                ->where('ativo', true)
                ->exists();
    }
}
