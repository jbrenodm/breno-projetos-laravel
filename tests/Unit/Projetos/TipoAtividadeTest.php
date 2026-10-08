<?php

declare(strict_types=1);

use Src\Projetos\Domain\Entities\Atividade;
use Src\Projetos\Domain\Entities\TipoAtividade;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;

it('RN-43: nome obrigatório (sanitizado, até 60 caracteres); renomeia, inativa e reativa', function () {
    $tipo = new TipoAtividade(uuid(), ' <b>Treinamento</b> ');
    expect($tipo->getNome())->toBe('Treinamento')->and($tipo->isAtivo())->toBeTrue();

    $tipo->renomear('Capacitação');
    $tipo->inativar();
    expect($tipo->getNome())->toBe('Capacitação')->and($tipo->isAtivo())->toBeFalse();

    $tipo->ativar();
    expect($tipo->isAtivo())->toBeTrue();
});

it('RN-43: rejeita nome vazio ou longo demais', function (string $nome) {
    new TipoAtividade(uuid(), $nome);
})->with(['  ', str_repeat('a', 61)])->throws(RegraDeProjetoException::class, 'O nome do tipo de atividade é obrigatório (máximo 60 caracteres).');

it('RN-19: a atividade exige um id de tipo válido', function () {
    Atividade::registrar(uuid(), 'x', 'Comercial', StatusAtividade::NAO_INICIADA,
        new PeriodoAtividade(dia('2026-10-01'), dia('2026-10-02')), uuid(), uuid(), null, dia('2026-10-01'));
})->throws(RegraDeProjetoException::class, 'O identificador do tipo de atividade deve ser um UUID válido.');
