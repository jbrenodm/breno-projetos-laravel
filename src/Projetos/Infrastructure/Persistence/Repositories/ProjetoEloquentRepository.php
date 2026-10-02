<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\AtividadeModel;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoModel;
use Src\Projetos\Infrastructure\Persistence\Mappers\ProjetoMapper;
use Src\Shared\Domain\Uuid;

final class ProjetoEloquentRepository implements ProjetoRepositoryInterface
{
    public function buscarPorId(string $id): ?Projeto
    {
        if (! Uuid::ehValido($id)) {
            return null;
        }

        $model = ProjetoModel::query()->with(['fornecedores', 'atividades'])->find($id);

        return $model ? ProjetoMapper::paraDominio($model) : null;
    }

    /** Persiste o agregado inteiro numa transação. */
    public function salvar(Projeto $projeto): void
    {
        DB::transaction(function () use ($projeto): void {
            $model = ProjetoModel::query()->updateOrCreate(
                ['id' => $projeto->getId()],
                ProjetoMapper::projetoParaPersistencia($projeto),
            );

            $model->fornecedores()->delete();
            foreach ($projeto->getFornecedores() as $vinculo) {
                $model->fornecedores()->create([
                    'fornecedor_id' => $vinculo->fornecedorId,
                    'solucao_id' => $vinculo->solucaoId,
                ]);
            }

            foreach ($projeto->getAtividades() as $indice => $atividade) {
                AtividadeModel::query()->updateOrCreate(
                    ['id' => $atividade->getId()],
                    ['projeto_id' => $projeto->getId()] + ProjetoMapper::atividadeParaPersistencia($atividade, $indice + 1),
                );
            }
        });
    }
}
