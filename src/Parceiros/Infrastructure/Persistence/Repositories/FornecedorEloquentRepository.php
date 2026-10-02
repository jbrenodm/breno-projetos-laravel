<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use Src\Parceiros\Domain\Entities\Fornecedor;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\FornecedorModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\SolucaoModel;
use Src\Parceiros\Infrastructure\Persistence\Mappers\ParceirosMapper;

final class FornecedorEloquentRepository implements FornecedorRepositoryInterface
{
    public function buscarPorId(string $id): ?Fornecedor
    {
        $model = FornecedorModel::query()->with('solucoes')->find($id);

        return $model ? ParceirosMapper::fornecedorParaDominio($model) : null;
    }

    public function cnpjEmUso(string $cnpj, ?string $ignorarId = null): bool
    {
        return FornecedorModel::query()
            ->where('cnpj', $cnpj)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();
    }

    public function salvar(Fornecedor $fornecedor): void
    {
        DB::transaction(function () use ($fornecedor): void {
            FornecedorModel::query()->updateOrCreate(
                ['id' => $fornecedor->getId()],
                ParceirosMapper::dadosParaPersistencia($fornecedor->getDados(), $fornecedor->isAtivo()),
            );

            // Soluções nunca são apagadas (podem estar vinculadas a projetos) — apenas inseridas/atualizadas.
            foreach ($fornecedor->getSolucoes() as $solucao) {
                SolucaoModel::query()->updateOrCreate(
                    ['id' => $solucao->getId()],
                    [
                        'fornecedor_id' => $fornecedor->getId(),
                        'nome' => $solucao->getNome(),
                        'descricao' => $solucao->getDescricao(),
                        'ativo' => $solucao->isAtivo(),
                    ],
                );
            }
        });
    }
}
