<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Mappers;

use DateTimeImmutable;
use DateTimeInterface;
use Src\Projetos\Domain\Entities\Atividade;
use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\Observacao;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\AtividadeModel;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoFornecedorModel;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoModel;

/** Data Mapper: Domínio <-> tabelas. Única ponte entre o agregado e o Eloquent. */
final class ProjetoMapper
{
    public static function paraDominio(ProjetoModel $model): Projeto
    {
        return Projeto::reconstituir(
            id: ProjetoId::fromString($model->id),
            clienteId: $model->cliente_id,
            fornecedores: $model->fornecedores
                ->map(fn (ProjetoFornecedorModel $f) => new VinculoFornecedor($f->fornecedor_id, $f->solucao_id))
                ->values()->all(),
            status: StatusProjeto::from($model->status),
            codigoOportunidade: CodigoOportunidade::opcional($model->codigo_oportunidade),
            atividades: $model->atividades->map(fn (AtividadeModel $a) => self::atividadeParaDominio($a))->values()->all(),
        );
    }

    /** @return array<string, mixed> */
    public static function projetoParaPersistencia(Projeto $projeto): array
    {
        return [
            'cliente_id' => $projeto->getClienteId(),
            'codigo_oportunidade' => $projeto->getCodigoOportunidade()?->toString(),
            'status' => $projeto->getStatus()->value,
        ];
    }

    /** @return array<string, mixed> */
    public static function atividadeParaPersistencia(Atividade $atividade, int $sequencia): array
    {
        $periodo = $atividade->getPeriodo();

        return [
            'sequencia' => $sequencia,
            'descricao' => $atividade->getDescricao(),
            'tipo_id' => $atividade->getTipoId(),
            'status' => $atividade->getStatus()->value,
            'data_entrada' => $periodo->dataEntrada->format('Y-m-d'),
            'data_limite' => $periodo->dataLimite->format('Y-m-d'),
            'data_inicio' => $periodo->dataInicio?->format('Y-m-d'),
            'data_termino' => $periodo->dataTermino?->format('Y-m-d'),
            'account_manager_id' => $atividade->getAccountManagerId(),
            'pre_vendas_id' => $atividade->getPreVendasId(),
            'observacao' => $atividade->getObservacao()?->texto,
            'observacao_autor_id' => $atividade->getObservacao()?->autorId,
        ];
    }

    private static function atividadeParaDominio(AtividadeModel $a): Atividade
    {
        return Atividade::reconstituir(
            id: $a->id,
            descricao: $a->descricao,
            tipoId: $a->tipo_id,
            status: StatusAtividade::from($a->status),
            periodo: new PeriodoAtividade(
                self::data($a->data_entrada),
                self::data($a->data_limite),
                $a->data_inicio ? self::data($a->data_inicio) : null,
                $a->data_termino ? self::data($a->data_termino) : null,
            ),
            accountManagerId: $a->account_manager_id,
            preVendasId: $a->pre_vendas_id,
            observacao: Observacao::opcional($a->observacao, $a->observacao_autor_id),
        );
    }

    private static function data(DateTimeInterface $data): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($data);
    }
}
