<?php

declare(strict_types=1);

use Src\Fornecedores\Domain\Entities\Fornecedor;
use Src\Fornecedores\Domain\ValueObjects\FornecedorId;
use Illuminate\Support\Str;

// Helper para instanciar um fornecedor válido nos testes
function criarFornecedorHelper(string $nome = 'Fornecedor Tech Cloud'): Fornecedor {
    return new Fornecedor(
        id: FornecedorId::fromString(Str::uuid()->toString()),
        nomeFantasia: $nome
    );
}

it('deve instanciar um fornecedor válido com sucesso', function () {
    $fornecedor = criarFornecedorHelper('Sophos do Brasil');

    expect($fornecedor->getNomeFantasia())->toBe('Sophos do Brasil')
        ->and($fornecedor->isAtivo())->toBeTrue()
        ->and($fornecedor->getSolucoes())->toBeEmpty();
});

it('deve lançar exceção se tentar instanciar um fornecedor com nome vazio', function () {
    expect(fn () => new Fornecedor(
        id: FornecedorId::fromString(Str::uuid()->toString()),
        nomeFantasia: '   '
    ))->toThrow(InvalidArgumentException::class, 'O nome fantasia do fornecedor não pode ser vazio.');
});

it('deve permitir cadastrar soluções no catálogo do fornecedor', function () {
    $fornecedor = criarFornecedorHelper();
    $solucaoId = Str::uuid()->toString();

    $fornecedor->cadastrarSolucao($solucaoId, 'SentinelOne EDR');

    $solucoes = $fornecedor->getSolucoes();
    
    expect($solucoes)->toHaveCount(1)
        ->and($solucoes[0]->getId())->toBe($solucaoId)
        ->and($solucoes[0]->getNome())->toBe('SentinelOne EDR')
        ->and($solucoes[0]->isAtiva())->toBeTrue();
});

it('deve rejeitar soluções com nomes duplicados para o mesmo fornecedor', function () {
    $fornecedor = criarFornecedorHelper();

    // Cadastra a primeira vez
    $fornecedor->cadastrarSolucao(Str::uuid()->toString(), 'CrowdStrike Falcon');

    // Tenta cadastrar novamente com variação de maiúsculas e espaços para testar a robustez
    expect(fn () => $fornecedor->cadastrarSolucao(
        Str::uuid()->toString(), 
        '  crowdstrike falcon  '
    ))->toThrow(
        InvalidArgumentException::class, 
        "Este fornecedor já possui uma solução cadastrada com o nome '  crowdstrike falcon  '."
    );
});