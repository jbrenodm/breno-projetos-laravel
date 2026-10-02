<?php

declare(strict_types=1);

use Src\Parceiros\Domain\Entities\Fornecedor;
use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;

it('RN-22: aceita CNPJ válido com ou sem máscara', function () {
    expect((new Cnpj('11.222.333/0001-81'))->valor)->toBe('11222333000181')
        ->and((new Cnpj('11222333000181'))->formatado())->toBe('11.222.333/0001-81');
});

it('RN-22: rejeita CNPJ inválido', function (string $cnpj) {
    new Cnpj($cnpj);
})->with(['11222333000180', '11111111111111', '123'])->throws(RegraDeParceiroException::class);

it('RN-21: nome fantasia e CNPJ opcionais; razão social obrigatória', function () {
    $d = new DadosCadastrais('ACME Ltda', '  ', null);

    expect($d->nomeFantasia)->toBeNull()
        ->and($d->nomeExibicao())->toBe('ACME Ltda');

    new DadosCadastrais('   ');
})->throws(RegraDeParceiroException::class);

it('RN-23: soluções não se repetem no mesmo fornecedor', function () {
    $f = new Fornecedor(uuid(), new DadosCadastrais('Sophos Ltda', 'Sophos'));
    $f->adicionarSolucao(uuid(), 'XGS Firewall');

    $f->adicionarSolucao(uuid(), 'xgs firewall');
})->throws(RegraDeParceiroException::class);
