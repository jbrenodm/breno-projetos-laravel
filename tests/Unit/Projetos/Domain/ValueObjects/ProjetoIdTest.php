<?php

declare(strict_types=1);

use Src\Projetos\Domain\ValueObjects\ProjetoId;

it('deve aceitar um UUIDv4 válido', function () {
    $uuid = 'fe3b2b1a-8743-4818-b32f-9ea93174cbf5';
    
    $projetoId = ProjetoId::fromString($uuid);
    
    expect($projetoId->toString())->toBe($uuid);
});

it('deve lançar exceção para um UUID inválido', function () {
    expect(fn () => ProjetoId::fromString('id-invalido-123'))
        ->toThrow(InvalidArgumentException::class, 'O identificador do projeto deve ser um UUIDv4 válido.');
});