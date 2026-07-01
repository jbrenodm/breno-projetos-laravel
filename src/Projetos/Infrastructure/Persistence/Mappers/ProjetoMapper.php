<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Mappers;

use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use DateTimeImmutable;

final class ProjetoMapper
{
    /**
     * Transforma o Model do Eloquent (Banco) na nossa Entidade Rica de Domínio.
     */
    public static function toDomain(ProjetoEloquentModel $model): Projeto
    {
        $fornecedoresDoDominio = [];
        foreach ($model->fornecedores as $fornecedorModel) {
            $fornecedoresDoDominio[] = new VinculoFornecedor(
                fornecedorId: $fornecedorModel->fornecedor_id,
                solucaoId: $fornecedorModel->solucao_id
            );
        }

        $projeto = new Projeto(
            id: ProjetoId::fromString($model->id),
            clienteId: $model->cliente_id,
            fornecedores: $fornecedoresDoDominio,
            status: StatusProjeto::from($model->status),
            codigoOportunidade: $model->codigo_oportunidade ? new CodigoOportunidade($model->codigo_oportunidade) : null
        );

        // Hidratação do histórico de atividades preservando as invariantes
        foreach ($model->atividades as $atividadeModel) {
            $projeto->adicionarAtividade(
                idAtividade: $atividadeModel->id,
                descricao: $atividadeModel->descricao,
                statusAtividade: StatusAtividade::from($atividadeModel->status),
                periodo: new PeriodoAtividade(
                    dataEntrada: DateTimeImmutable::createFromInterface($atividadeModel->data_entrada),
                    deadline: DateTimeImmutable::createFromInterface($atividadeModel->deadline),
                    dataTermino: $atividadeModel->data_termino ? DateTimeImmutable::createFromInterface($atividadeModel->data_termino) : null
                ),
                accountManagerId: $atividadeModel->account_manager_id,
                preVendasId: $atividadeModel->pre_vendas_id
            );
        }

        return $projeto;
    }

    /**
     * Prepara os dados planos do macro-projeto para o formato que o Eloquent espera salvar.
     */
    public static function toEloquentArray(Projeto $projeto): array
    {
        return [
            'id' => $projeto->getId(),
            'cliente_id' => $projeto->getClienteId(),
            'status' => $projeto->getStatus()->value,
            'codigo_oportunidade' => $projeto->getCodigoOportunidade()?->toString(),
        ];
    }
}