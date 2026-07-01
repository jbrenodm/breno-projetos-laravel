<?php

declare(strict_types=1);

use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;

it('deve instanciar um código de oportunidade válido limpo', function () {
    $codigo = new CodigoOportunidade('  OPP-2026-0001  ');
    expect($codigo->toString())->toBe('OPP-2026-0001');
});

it('deve rejeitar códigos vazios', function () {
    expect(fn () => new CodigoOportunidade('   '))
        ->toThrow(InvalidArgumentException::class, 'O código da oportunidade não pode ser uma string vazia.');
});