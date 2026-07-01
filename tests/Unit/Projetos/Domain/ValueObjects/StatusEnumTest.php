<?php

declare(strict_types=1);

use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;

it('deve validar os estados válidos do macro-status do projeto', function () {
    expect(StatusProjeto::from('Em Andamento'))->toBe(StatusProjeto::EM_ANDAMENTO);
    
    expect(fn () => StatusProjeto::from('Status Inexistente'))
        ->toThrow(ValueError::class);
});

it('deve validar os estados válidos do micro-status da atividade', function () {
    expect(StatusAtividade::from('Parada'))->toBe(StatusAtividade::PARADA);
    
    expect(fn () => StatusAtividade::from('Invalido'))
        ->toThrow(ValueError::class);
});