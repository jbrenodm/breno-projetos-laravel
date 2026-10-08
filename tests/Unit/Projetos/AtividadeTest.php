<?php

declare(strict_types=1);

use Src\Projetos\Domain\Entities\Atividade;
use Src\Projetos\Domain\Exceptions\ObservacaoNaoPermitidaException;
use Src\Projetos\Domain\Exceptions\PeriodoInvalidoException;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Exceptions\TransicaoDeStatusInvalidaException;
use Src\Projetos\Domain\ValueObjects\Observacao;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;

function atividade(StatusAtividade $status = StatusAtividade::NAO_INICIADA, ?PeriodoAtividade $periodo = null, ?Observacao $obs = null): Atividade
{
    return Atividade::registrar(
        uuid(), '  <b>Levantar ambiente</b> ', TIPO_MAPEAMENTO, $status,
        $periodo ?? new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30')),
        uuid(), uuid(), $obs, dia('2026-10-02'),
    );
}

it('sanitiza a descrição (sem HTML)', function () {
    expect(atividade()->getDescricao())->toBe('Levantar ambiente');
});

it('RN-17: registra atividade já concluída (histórico) com término informado', function () {
    $a = atividade(StatusAtividade::CONCLUIDA, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), dia('2026-09-02'), dia('2026-09-20')));

    expect($a->getStatus())->toBe(StatusAtividade::CONCLUIDA)
        ->and($a->getPeriodo()->dataTermino->format('Y-m-d'))->toBe('2026-09-20');
});

it('RN-18: concluída sem término usa a data de hoje', function () {
    expect(atividade(StatusAtividade::CONCLUIDA)->getPeriodo()->dataTermino->format('Y-m-d'))->toBe('2026-10-02');
});

it('RN-18: registrada Em Andamento sem início usa a data de entrada', function () {
    expect(atividade(StatusAtividade::EM_ANDAMENTO)->getPeriodo()->dataInicio->format('Y-m-d'))->toBe('2026-09-01');
});

it('RN-18: término só existe em atividade concluída', function () {
    atividade(StatusAtividade::EM_ANDAMENTO, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), null, dia('2026-09-10')));
})->throws(PeriodoInvalidoException::class);

it('RN-18: não iniciada não tem data de início', function () {
    atividade(StatusAtividade::NAO_INICIADA, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), dia('2026-09-03')));
})->throws(PeriodoInvalidoException::class);

it('RN-18: data limite não pode ser anterior à entrada', function () {
    new PeriodoAtividade(dia('2026-09-10'), dia('2026-09-01'));
})->throws(PeriodoInvalidoException::class, 'A data limite não pode ser anterior à data de entrada.');

it('RN-18: término não pode ser anterior ao início', function () {
    new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), dia('2026-09-10'), dia('2026-09-05'));
})->throws(PeriodoInvalidoException::class);

it('RN-16/18: iniciar preenche data de início; concluir preenche término', function () {
    $a = atividade();
    $a->alterarStatus(StatusAtividade::EM_ANDAMENTO, null, dia('2026-09-05'));
    $a->alterarStatus(StatusAtividade::PARADA, null, dia('2026-09-06'));
    $a->alterarStatus(StatusAtividade::EM_ANDAMENTO, null, dia('2026-09-08')); // retomada não muda o início
    $a->alterarStatus(StatusAtividade::CONCLUIDA, dia('2026-09-15'), dia('2026-10-02'));

    expect($a->getPeriodo()->dataInicio->format('Y-m-d'))->toBe('2026-09-05')
        ->and($a->getPeriodo()->dataTermino->format('Y-m-d'))->toBe('2026-09-15');
});

it('RN-16: não se muda para o próprio status', function (StatusAtividade $status) {
    atividade($status)->alterarStatus($status, null, dia('2026-10-02'));
})->with(StatusAtividade::cases())->throws(TransicaoDeStatusInvalidaException::class);

it('RN-16: qualquer status pode ir para qualquer outro', function () {
    foreach (StatusAtividade::cases() as $de) {
        expect($de->destinosPermitidos())->toHaveCount(3)->not->toContain($de);
    }
});

it('RN-16/18: reabrir a concluída apaga o término e mantém o início', function (StatusAtividade $para) {
    $a = atividade(StatusAtividade::CONCLUIDA, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), dia('2026-09-02'), dia('2026-09-20')));

    $a->alterarStatus($para, null, dia('2026-10-02'));

    expect($a->getStatus())->toBe($para)
        ->and($a->getPeriodo()->dataInicio->format('Y-m-d'))->toBe('2026-09-02')
        ->and($a->getPeriodo()->dataTermino)->toBeNull();
})->with([StatusAtividade::EM_ANDAMENTO, StatusAtividade::PARADA]);

it('RN-16/18: voltar para não iniciada apaga início e término', function (StatusAtividade $de) {
    $a = atividade($de, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), dia('2026-09-02'),
        $de === StatusAtividade::CONCLUIDA ? dia('2026-09-20') : null));

    $a->alterarStatus(StatusAtividade::NAO_INICIADA, null, dia('2026-10-02'));

    expect($a->getStatus())->toBe(StatusAtividade::NAO_INICIADA)
        ->and($a->getPeriodo()->dataInicio)->toBeNull()
        ->and($a->getPeriodo()->dataTermino)->toBeNull();

    $a->alterarStatus(StatusAtividade::EM_ANDAMENTO, null, dia('2026-10-05')); // recomeça com novo início
    expect($a->getPeriodo()->dataInicio->format('Y-m-d'))->toBe('2026-10-05');
})->with([StatusAtividade::EM_ANDAMENTO, StatusAtividade::PARADA, StatusAtividade::CONCLUIDA]);

it('RN-20: só o autor edita a observação', function () {
    $autor = uuid();
    $a = atividade(obs: new Observacao('Cliente pediu PoC', $autor));

    $a->registrarObservacao('Atualizado pelo autor', $autor);
    expect($a->getObservacao()->texto)->toBe('Atualizado pelo autor');

    $a->registrarObservacao('Intruso', uuid());
})->throws(ObservacaoNaoPermitidaException::class);

it('valida AM/PV como UUID', function () {
    Atividade::registrar(uuid(), 'x', TIPO_COMERCIAL, StatusAtividade::NAO_INICIADA,
        new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30')), 'am-invalido', uuid(), null, dia('2026-10-02'));
})->throws(RegraDeProjetoException::class);

function editar(Atividade $a, ?PeriodoAtividade $periodo = null, ?string $observacao = null, ?string $usuarioId = null): void
{
    $a->editar(
        ' <i>Descrição corrigida</i> ', TIPO_IMPLANTACAO,
        $periodo ?? new PeriodoAtividade(dia('2026-08-20'), dia('2026-10-20')),
        uuid(), uuid(), $observacao, $usuarioId, dia('2026-10-02'),
    );
}

it('RN-28: edita descrição, tipo, datas, responsáveis e observação sem mudar o status', function () {
    $a = atividade(StatusAtividade::EM_ANDAMENTO);
    editar($a, observacao: 'Nova observação');

    expect($a->getDescricao())->toBe('Descrição corrigida')
        ->and($a->getTipoId())->toBe(TIPO_IMPLANTACAO)
        ->and($a->getStatus())->toBe(StatusAtividade::EM_ANDAMENTO)
        ->and($a->getPeriodo()->dataEntrada->format('Y-m-d'))->toBe('2026-08-20')
        ->and($a->getPeriodo()->dataInicio->format('Y-m-d'))->toBe('2026-08-20') // em andamento sem início → entrada
        ->and($a->getObservacao()->texto)->toBe('Nova observação');
});

it('RN-28: atividade concluída pode ser editada (correção de histórico)', function () {
    $a = atividade(StatusAtividade::CONCLUIDA);
    editar($a, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), dia('2026-09-03'), dia('2026-09-25')));

    expect($a->getStatus())->toBe(StatusAtividade::CONCLUIDA)
        ->and($a->getPeriodo()->dataTermino->format('Y-m-d'))->toBe('2026-09-25');
});

it('RN-28: datas continuam coerentes com o status atual', function () {
    editar(atividade(StatusAtividade::NAO_INICIADA), new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30'), null, dia('2026-09-10')));
})->throws(PeriodoInvalidoException::class);

it('RN-28: descrição vazia é rejeitada e nada é alterado', function () {
    $a = atividade();

    try {
        $a->editar('<b></b>', TIPO_COMERCIAL, new PeriodoAtividade(dia('2026-09-01'), dia('2026-09-30')),
            uuid(), uuid(), null, null, dia('2026-10-02'));
    } catch (RegraDeProjetoException) {
    }

    expect($a->getDescricao())->toBe('Levantar ambiente')
        ->and($a->getTipoId())->toBe(TIPO_MAPEAMENTO);
});

it('RN-28: observação vazia remove a existente', function () {
    $a = atividade(obs: new Observacao('Antiga'));
    editar($a, observacao: '');

    expect($a->getObservacao())->toBeNull();
});

it('RN-28/RN-20: observação de outro autor não pode ser alterada', function () {
    $a = atividade(obs: new Observacao('Do autor', uuid()));
    editar($a, observacao: 'Intruso', usuarioId: uuid());
})->throws(ObservacaoNaoPermitidaException::class);

it('RN-28/RN-20: manter a observação de outro autor não bloqueia a edição', function () {
    $autor = uuid();
    $a = atividade(obs: new Observacao('Do autor', $autor));
    editar($a, observacao: 'Do autor', usuarioId: uuid());

    expect($a->getObservacao()->autorId)->toBe($autor);
});
