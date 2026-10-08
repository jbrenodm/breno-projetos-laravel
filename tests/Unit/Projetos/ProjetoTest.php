<?php

declare(strict_types=1);

use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\Exceptions\AtividadeNaoEncontradaException;
use Src\Projetos\Domain\Exceptions\FornecedorObrigatorioException;
use Src\Projetos\Domain\Exceptions\ProjetoCanceladoException;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;

function novoProjeto(): Projeto
{
    return Projeto::criar(ProjetoId::fromString(uuid()), uuid(), [new VinculoFornecedor(uuid())]);
}

function adicionar(Projeto $p, StatusAtividade $status = StatusAtividade::NAO_INICIADA, ?string $am = null, ?string $pv = null): string
{
    return $p->adicionarAtividade(
        uuid(), 'Atividade', TIPO_MAPEAMENTO, $status,
        new PeriodoAtividade(dia('2026-10-01'), dia('2026-10-10')),
        $am, $pv, null, dia('2026-10-02'),
    )->getId();
}

it('RN-07: nasce Não Iniciado, sem atividades', function () {
    $p = novoProjeto();

    expect($p->getStatus())->toBe(StatusProjeto::NAO_INICIADO)
        ->and($p->getAtividades())->toBeEmpty();
});

it('RN-04: exige no mínimo um fornecedor', function () {
    Projeto::criar(ProjetoId::fromString(uuid()), uuid(), []);
})->throws(FornecedorObrigatorioException::class);

it('RN-04: solução é opcional e duplicados são ignorados', function () {
    $f = uuid();
    $p = Projeto::criar(ProjetoId::fromString(uuid()), uuid(), [new VinculoFornecedor($f), new VinculoFornecedor($f)]);

    expect($p->getFornecedores())->toHaveCount(1)
        ->and($p->getFornecedores()[0]->solucaoId)->toBeNull();
});

it('RN-02: código de oportunidade é opcional', function () {
    $p = Projeto::criar(ProjetoId::fromString(uuid()), uuid(), [new VinculoFornecedor(uuid())], new CodigoOportunidade(' OPP-2026-0001 '));

    expect($p->getCodigoOportunidade()->toString())->toBe('OPP-2026-0001');
});

it('RN-13/14: primeira atividade exige AM e PV', function () {
    adicionar(novoProjeto());
})->throws(ResponsavelObrigatorioException::class);

it('RN-14: herda AM e PV da última atividade criada', function () {
    $p = novoProjeto();
    [$am1, $pv1, $am2] = [uuid(), uuid(), uuid()];

    adicionar($p, am: $am1, pv: $pv1);
    adicionar($p, am: $am2);          // troca só o AM
    adicionar($p);                    // herda tudo da 2ª

    $a = $p->getAtividades();
    expect($a[1]->getPreVendasId())->toBe($pv1)
        ->and($a[2]->getAccountManagerId())->toBe($am2)
        ->and($a[2]->getPreVendasId())->toBe($pv1)
        ->and($p->responsaveisSugeridos())->toBe(['account_manager_id' => $am2, 'pre_vendas_id' => $pv1]);
});

it('RN-12: várias atividades abertas ao mesmo tempo', function () {
    $p = novoProjeto();
    adicionar($p, StatusAtividade::EM_ANDAMENTO, uuid(), uuid());
    adicionar($p, StatusAtividade::PARADA);
    adicionar($p, StatusAtividade::NAO_INICIADA);

    expect(array_filter($p->getAtividades(), fn ($a) => $a->estaAberta()))->toHaveCount(3);
});

it('RN-08..10: status do projeto acompanha as atividades', function () {
    $p = novoProjeto();
    $a1 = adicionar($p, StatusAtividade::EM_ANDAMENTO, uuid(), uuid());
    $a2 = adicionar($p);
    expect($p->getStatus())->toBe(StatusProjeto::EM_ANDAMENTO);

    $p->alterarStatusAtividade($a1, StatusAtividade::CONCLUIDA, null, dia('2026-10-05'));
    expect($p->getStatus())->toBe(StatusProjeto::EM_ANDAMENTO);

    $p->alterarStatusAtividade($a2, StatusAtividade::CONCLUIDA, null, dia('2026-10-05'));
    expect($p->getStatus())->toBe(StatusProjeto::CONCLUIDO);

    $p->alterarStatusAtividade($a2, StatusAtividade::EM_ANDAMENTO, null, dia('2026-10-06')); // RN-16: reabrir reabre o projeto
    expect($p->getStatus())->toBe(StatusProjeto::EM_ANDAMENTO);

    $p->alterarStatusAtividade($a2, StatusAtividade::CONCLUIDA, null, dia('2026-10-07'));
    adicionar($p); // nova atividade reabre o projeto
    expect($p->getStatus())->toBe(StatusProjeto::EM_ANDAMENTO);
});

it('RN-11: projeto cancelado não aceita alterações', function () {
    $p = novoProjeto();
    $p->cancelar();

    expect($p->getStatus())->toBe(StatusProjeto::CANCELADO);
    adicionar($p, am: uuid(), pv: uuid());
})->throws(ProjetoCanceladoException::class);

it('falha ao alterar atividade de outro projeto', function () {
    novoProjeto()->alterarStatusAtividade(uuid(), StatusAtividade::CONCLUIDA, null, dia('2026-10-02'));
})->throws(AtividadeNaoEncontradaException::class);

it('RN-28: edita atividade pelo agregado', function () {
    $p = novoProjeto();
    $id = adicionar($p, am: uuid(), pv: uuid());
    $novoAm = uuid();

    $p->editarAtividade($id, 'Editada', TIPO_COMERCIAL, new PeriodoAtividade(dia('2026-10-01'), dia('2026-10-20')),
        $novoAm, uuid(), null, null, dia('2026-10-02'));

    expect($p->buscarAtividade($id)->getAccountManagerId())->toBe($novoAm)
        ->and($p->responsaveisSugeridos()['account_manager_id'])->toBe($novoAm); // RN-14 usa os dados atuais
});

it('RN-11/RN-28: projeto cancelado não permite editar atividade', function () {
    $p = novoProjeto();
    $id = adicionar($p, am: uuid(), pv: uuid());
    $p->cancelar();

    $p->editarAtividade($id, 'Editada', TIPO_COMERCIAL, new PeriodoAtividade(dia('2026-10-01'), dia('2026-10-20')),
        uuid(), uuid(), null, null, dia('2026-10-02'));
})->throws(ProjetoCanceladoException::class);

it('RN-29: troca o cliente, inclusive de projeto concluído', function () {
    $p = novoProjeto();
    adicionar($p, StatusAtividade::CONCLUIDA, uuid(), uuid());
    $novo = uuid();
    $p->alterarCliente($novo);

    expect($p->getClienteId())->toBe($novo)
        ->and($p->getStatus())->toBe(StatusProjeto::CONCLUIDO);
});

it('RN-11/RN-29: projeto cancelado não troca de cliente', function () {
    $p = novoProjeto();
    $p->cancelar();
    $p->alterarCliente(uuid());
})->throws(ProjetoCanceladoException::class);
