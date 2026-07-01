<?php

declare(strict_types=1);

use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;

it('deve permitir criar um vínculo completo com fornecedor e solução', function () {
    $vinculo = new VinculoFornecedor('fornecedor-uuid', 'solucao-uuid');
    
    expect($vinculo->fornecedorId)->toBe('fornecedor-uuid')
        ->and($vinculo->solucaoId)->toBe('solucao-uuid');
});

it('deve aceitar um vínculo onde a solução é opcional (nula)', function () {
    $vinculo = new VinculoFornecedor('fornecedor-uuid');
    
    expect($vinculo->fornecedorId)->toBe('fornecedor-uuid')
        ->and($vinculo->solucaoId)->toBeNull();
});

it('deve rejeitar se o ID do fornecedor for vazio', function () {
    expect(fn () => new VinculoFornecedor('   '))
        ->toThrow(InvalidArgumentException::class, 'O identificador do fornecedor é obrigatório.');
});