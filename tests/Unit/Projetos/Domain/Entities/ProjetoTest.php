<?php

declare(strict_types=1);

use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Illuminate\Support\Str;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;

// Helper para criar um projeto limpo nos testes
function criarProjetoHelper(): Projeto {
    return new Projeto(
        id: ProjetoId::fromString('fe3b2b1a-8743-4818-b32f-9ea93174cbf5'),
        clienteId: 'cliente-uuid-123',
        fornecedores: [new VinculoFornecedor('fornecedor-uuid-999')],
        status: StatusProjeto::NAO_INICIADO
    );
}

it('deve lançar exceção se tentar adicionar a primeira atividade sem definir AM e PV', function () {
    $projeto = criarProjetoHelper();

    $periodo = new PeriodoAtividade(new DateTimeImmutable('now'), new DateTimeImmutable('+5 days'));

    expect(fn () => $projeto->adicionarAtividade(
        idAtividade: 'ativ-1',
        descricao: 'Primeira atividade comercial',
        statusAtividade: StatusAtividade::EM_ANDAMENTO,
        periodo: $periodo,
        accountManagerId: null, // Sem responsável
        preVendasId: null       // Sem responsável
    ))->toThrow(
        \InvalidArgumentException::class, // 👈 Mudou para InvalidArgumentException
        'Não é possível aplicar o fallback temporal: a primeira atividade do projeto exige a definição explícita do AM e do Pre-Vendas.' // 👈 Texto atualizado do Domínio
    );
});

it('deve permitir criar a primeira atividade se AM e PV forem enviados e herdar para as próximas', function () {
    $projeto = criarProjetoHelper();
    $periodo = new PeriodoAtividade(new DateTimeImmutable('now'), new DateTimeImmutable('+5 days'));

    // 1. Cadastra a primeira atividade forçando os responsáveis manuais
    $projeto->adicionarAtividade(
        idAtividade: 'ativ-1',
        descricao: 'Reunião de Alinhamento',
        statusAtividade: StatusAtividade::EM_ANDAMENTO,
        periodo: $periodo,
        accountManagerId: 'am-breno',
        preVendasId: 'pv-duarte'
    );

    // 2. Cadastra a segunda atividade OMITINDO os responsáveis (deve acionar o Fallback Temporal)
    $projeto->adicionarAtividade(
        idAtividade: 'ativ-2',
        descricao: 'Desenho da Solução Concorrente',
        statusAtividade: StatusAtividade::EM_ANDAMENTO,
        periodo: $periodo,
        accountManagerId: null,
        preVendasId: null
    );

    $atividades = $projeto->getAtividades();

    expect($atividades)->toHaveCount(2)
        ->and($atividades[0]->getAccountManagerId())->toBe('am-breno')
        ->and($atividades[1]->getAccountManagerId())->toBe('am-breno') // Herdado com sucesso!
        ->and($atividades[1]->getPreVendasId())->toBe('pv-duarte');     // Herdado com sucesso!
});

it('deve permitir a coexistência de múltiplas atividades abertas em simultâneo', function () {
    $projeto = criarProjetoHelper();
    $periodo = new PeriodoAtividade(new DateTimeImmutable('now'), new DateTimeImmutable('+2 days'));

    $projeto->adicionarAtividade('ativ-1', 'Tarefa 1', StatusAtividade::EM_ANDAMENTO, $periodo, 'am-1', 'pv-1');
    $projeto->adicionarAtividade('ativ-2', 'Tarefa 2', StatusAtividade::PARADA, $periodo, 'am-1', 'pv-1');

    $atividades = $projeto->getAtividades();

    expect($atividades[0]->getStatus())->toBe(StatusAtividade::EM_ANDAMENTO)
        ->and($atividades[1]->getStatus())->toBe(StatusAtividade::PARADA); // Independentes e paralelas!
});

it('deve transicionar o status do projeto para Em Andamento assim que a primeira atividade for adicionada', function () {
    $projeto = new Projeto(
        id: ProjetoId::fromString(Str::uuid()->toString()),
        clienteId: Str::uuid()->toString(),
        fornecedores: [new VinculoFornecedor(Str::uuid()->toString(), null)],
        status: StatusProjeto::NAO_INICIADO
    );

    expect($projeto->getStatus())->toBe(StatusProjeto::NAO_INICIADO);

    // Adiciona uma atividade
    $projeto->adicionarAtividade(
        idAtividade: Str::uuid()->toString(),
        descricao: 'Primeira atividade de escopo',
        statusAtividade: StatusAtividade::NAO_INICIADA,
        periodo: new PeriodoAtividade(new DateTimeImmutable('now'), new DateTimeImmutable('+5 days')),
        accountManagerId: Str::uuid()->toString(),
        preVendasId: Str::uuid()->toString()
    );

    // O status do macro-projeto mudou automaticamente!
    expect($projeto->getStatus())->toBe(StatusProjeto::EM_ANDAMENTO);
});

it('deve transicionar o status do projeto para Concluido quando todas as suas atividades forem concluidas', function () {
    $projeto = new Projeto(
        id: ProjetoId::fromString(Str::uuid()->toString()),
        clienteId: Str::uuid()->toString(),
        fornecedores: [new VinculoFornecedor(Str::uuid()->toString(), null)],
        status: StatusProjeto::NAO_INICIADO
    );

    $atividadeId = Str::uuid()->toString();

    $projeto->adicionarAtividade(
        idAtividade: $atividadeId,
        descricao: 'Atividade única',
        statusAtividade: StatusAtividade::EM_ANDAMENTO,
        periodo: new PeriodoAtividade(new DateTimeImmutable('now'), new DateTimeImmutable('+5 days')),
        accountManagerId: Str::uuid()->toString(),
        preVendasId: Str::uuid()->toString()
    );

    expect($projeto->getStatus())->toBe(StatusProjeto::EM_ANDAMENTO);

    // Conclui a única atividade existente
    $projeto->concluirAtividade($atividadeId, new DateTimeImmutable('now'));

    // O projeto fechou sozinho de forma consistente!
    expect($projeto->getStatus())->toBe(StatusProjeto::CONCLUIDO);
});