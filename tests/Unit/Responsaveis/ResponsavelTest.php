<?php

declare(strict_types=1);

use Src\Responsaveis\Domain\Entities\Responsavel;
use Src\Responsaveis\Domain\Exceptions\RegraDeResponsavelException;
use Src\Responsaveis\Domain\Funcao;

it('RN-42: nome obrigatório (sanitizado), e-mail opcional e funções sem repetição', function () {
    $r = new Responsavel(uuid(), ' <b>Ana</b> ', '  ', [Funcao::ACCOUNT_MANAGER, Funcao::ACCOUNT_MANAGER]);

    expect($r->getNome())->toBe('Ana')
        ->and($r->getEmail())->toBeNull()
        ->and($r->getFuncoes())->toBe([Funcao::ACCOUNT_MANAGER])
        ->and($r->possuiFuncao(Funcao::PRE_VENDAS))->toBeFalse()
        ->and($r->isAtivo())->toBeTrue();
});

it('RN-42: e-mail informado é guardado em minúsculas', function () {
    $r = new Responsavel(uuid(), 'Ana', ' Ana@Empresa.COM ', [Funcao::PRE_VENDAS]);

    expect($r->getEmail())->toBe('ana@empresa.com');
});

it('RN-42: rejeita nome vazio, e-mail inválido e responsável sem função', function (string $nome, ?string $email, array $funcoes, string $mensagem) {
    expect(fn () => new Responsavel(uuid(), $nome, $email, $funcoes))
        ->toThrow(RegraDeResponsavelException::class, $mensagem);
})->with([
    'sem nome' => ['  ', null, [Funcao::PRE_VENDAS], 'O nome é obrigatório'],
    'e-mail inválido' => ['Ana', 'ana@', [Funcao::PRE_VENDAS], 'E-mail inválido.'],
    'sem função' => ['Ana', null, [], 'O responsável deve ter pelo menos uma função.'],
]);

it('RN-42: edita todos os campos, inativa e reativa', function () {
    $r = new Responsavel(uuid(), 'Ana', null, [Funcao::ACCOUNT_MANAGER]);

    $r->atualizar('Ana Souza', 'ana@x.com', [Funcao::ACCOUNT_MANAGER, Funcao::PRE_VENDAS]);
    $r->inativar();
    expect($r->getNome())->toBe('Ana Souza')
        ->and($r->getEmail())->toBe('ana@x.com')
        ->and($r->possuiFuncao(Funcao::PRE_VENDAS))->toBeTrue()
        ->and($r->isAtivo())->toBeFalse();

    $r->ativar();
    expect($r->isAtivo())->toBeTrue();
});
