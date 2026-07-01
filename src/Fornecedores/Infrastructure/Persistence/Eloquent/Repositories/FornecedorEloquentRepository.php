<?php

declare(strict_types=1);

namespace Src\Fornecedores\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Fornecedores\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Fornecedores\Domain\Entities\Fornecedor;
use Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\FornecedorEloquentModel;
use Src\Fornecedores\Infrastructure\Persistence\Mappers\FornecedorMapper;
use Illuminate\Support\Facades\DB;

final class FornecedorEloquentRepository implements FornecedorRepositoryInterface
{
    public function save(Fornecedor $fornecedor): void
    {
        DB::transaction(function () use ($fornecedor) {
            // 1. Salva ou atualiza a raiz (Fornecedor)
            $model = FornecedorEloquentModel::updateOrCreate(
                ['id' => $fornecedor->getId()],
                FornecedorMapper::toPersistence($fornecedor)
            );

            // 2. Sincroniza o catálogo de soluções mapeando o estado atual do agregado
            $solucoesIdsNoDominio = [];

            foreach ($fornecedor->getSolucoes() as $solucao) {
                $solucoesIdsNoDominio[] = $solucao->getId();
                
                $model->solucoes()->updateOrCreate(
                    ['id' => $solucao->getId()],
                    [
                        'nome' => $solucao->getNome(),
                        'ativa' => $solucao->isAtiva()
                    ]
                );
            }

            // Exclui do banco soluções que foram removidas do agregado em memória (Garante consistência)
            $model->solucoes()->whereNotIn('id', $solucoesIdsNoDominio)->delete();
        });
    }

    public function findById(string $id): ?Fornecedor
    {
        $model = FornecedorEloquentModel::with('solucoes')->find($id);

        if ($model === null) {
            return null;
        }

        return FornecedorMapper::toDomain($model);
    }
}