<?php

declare(strict_types=1);

use Src\Identidade\Domain\Entities\DefinicaoDePapel;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;

it('RN-41: sanitiza nome e sigla; sigla vazia vira nula', function () {
    $d = new DefinicaoDePapel(Papel::ACCOUNT_MANAGER, ' <b>Gerente de Contas</b> ', '  ');

    expect($d->getNome())->toBe('Gerente de Contas')->and($d->getSigla())->toBeNull();
});

it('RN-41: nome obrigatório (até 60) e sigla até 10 caracteres', function (string $nome, ?string $sigla) {
    new DefinicaoDePapel(Papel::PRE_VENDAS, $nome, $sigla);
})->with([
    'nome vazio' => ['   ', null],
    'nome longo' => [str_repeat('x', 61), null],
    'sigla longa' => ['Pré-vendas', 'ABCDEFGHIJK'],
])->throws(RegraDeIdentidadeException::class);
