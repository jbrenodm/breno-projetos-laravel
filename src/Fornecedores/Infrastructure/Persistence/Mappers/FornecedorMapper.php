<?php

declare(strict_types=1);

namespace Src\Fornecedores\Infrastructure\Persistence\Mappers;

use Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\FornecedorEloquentModel;
use Src\Fornecedores\Domain\Entities\Fornecedor;
use Src\Fornecedores\Domain\Entities\Solucao;
use Src\Fornecedores\Domain\ValueObjects\FornecedorId;
use Src\Fornecedores\Domain\ValueObjects\SolucaoId;

final class FornecedorMapper
{
    public static function toDomain(FornecedorEloquentModel $model): Fornecedor
    {
        $solucoes = $model->solucoes->map(function ($solucaoModel) {
            return new Solucao(
                id: SolucaoId::fromString($solucaoModel->id),
                nome: $solucaoModel->nome,
                ativa: $solucaoModel->ativa
            );
        })->toArray();

        return new Fornecedor(
            id: FornecedorId::fromString($model->id),
            nomeFantasia: $model->nome_fantasia,
            ativo: $model->ativo,
            solucoes: $solucoes
        );
    }

    public static function toPersistence(Fornecedor $domain): array
    {
        return [
            'id' => $domain->getId(),
            'nome_fantasia' => $domain->getNomeFantasia(),
            'ativo' => $domain->isAtivo(),
        ];
    }
}