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

it('RN-32: edita nome e descrição da solução, sanitizando', function () {
    $f = new Fornecedor(uuid(), new DadosCadastrais('Sophos Ltda'));
    $s = $f->adicionarSolucao($id = uuid(), 'XGS');

    $f->editarSolucao($id, ' <b>XGS Firewall</b> ', '  Firewall de próxima geração ');

    expect($s->getNome())->toBe('XGS Firewall')
        ->and($s->getDescricao())->toBe('Firewall de próxima geração');

    $f->editarSolucao($id, 'xgs firewall', null); // mesmo nome (outra caixa) da própria solução é permitido
    expect($s->getNome())->toBe('xgs firewall')->and($s->getDescricao())->toBeNull();
});

it('RN-32/RN-23: renomear para o nome de outra solução é rejeitado e nada muda', function () {
    $f = new Fornecedor(uuid(), new DadosCadastrais('Sophos Ltda'));
    $f->adicionarSolucao(uuid(), 'Intercept X');
    $s = $f->adicionarSolucao($id = uuid(), 'XGS', 'Original');

    expect(fn () => $f->editarSolucao($id, 'INTERCEPT X', 'Nova'))->toThrow(RegraDeParceiroException::class);
    expect($s->getNome())->toBe('XGS')->and($s->getDescricao())->toBe('Original');
});

it('RN-31: inativa e reativa solução; solução de outro fornecedor é rejeitada', function () {
    $f = new Fornecedor(uuid(), new DadosCadastrais('Sophos Ltda'));
    $s = $f->adicionarSolucao($id = uuid(), 'XGS');

    $f->alterarSituacaoDaSolucao($id, false);
    expect($s->isAtivo())->toBeFalse();
    $f->alterarSituacaoDaSolucao($id, true);
    expect($s->isAtivo())->toBeTrue();

    $f->alterarSituacaoDaSolucao(uuid(), false);
})->throws(RegraDeParceiroException::class, 'A solução informada não pertence a este fornecedor.');
