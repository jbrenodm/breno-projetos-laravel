<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use Src\Projetos\Infrastructure\Persistence\Mappers\ProjetoMapper;
use Illuminate\Support\Facades\DB;

final class ProjetoEloquentRepository implements ProjetoRepositoryInterface
{
    public function findById(string $id): ?Projeto
    {
        // Carrega o projeto trazendo junto os seus relacionamentos (Eager Loading)
        $model = ProjetoEloquentModel::with(['fornecedores', 'atividades'])->find($id);

        if ($model === null) {
            return null;
        }

        return ProjetoMapper::toDomain($model);
    }

    public function save(Projeto $projeto): void
    {
        // Envolvemos a persistência do Agregado Completo numa transação única do banco
        DB::transaction(function () use ($projeto) {
            
            // 1. Salva ou Atualiza a tabela mãe (Projetos)
            $projetoModel = ProjetoEloquentModel::updateOrCreate(
                ['id' => $projeto->getId()],
                ProjetoMapper::toEloquentArray($projeto)
            );

            // 2. Sincroniza os fornecedores vinculados (Abordagem limpa: remove antigos e insere atuais)
            $projetoModel->fornecedores()->delete();
            foreach ($projeto->getFornecedores() as $vinculo) {
                $projetoModel->fornecedores()->create([
                    'fornecedor_id' => $vinculo->fornecedorId,
                    'solucao_id' => $vinculo->solucaoId,
                ]);
            }

            // 3. Persiste/Atualiza o grafo de atividades concorrentes
            foreach ($projeto->getAtividades() as $atividade) {
                $projetoModel->atividades()->updateOrCreate(
                    ['id' => $atividade->getId()],
                    [
                        'descricao' => $atividade->getDescricao(),
                        'status' => $atividade->getStatus()->value,
                        'data_entrada' => $atividade->getPeriodo()->dataEntrada,
                        'deadline' => $atividade->getPeriodo()->deadline,
                        'data_termino' => $atividade->getPeriodo()->dataTermino,
                        'account_manager_id' => $atividade->getAccountManagerId(),
                        'pre_vendas_id' => $atividade->getPreVendasId(),
                    ]
                );
            }
        });
    }
}