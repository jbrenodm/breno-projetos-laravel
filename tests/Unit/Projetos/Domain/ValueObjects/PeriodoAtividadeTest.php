<?php

declare(strict_types=1);

use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;

it('deve instanciar um período de atividade válido', function () {
    $dataEntrada = new DateTimeImmutable('2026-06-26 10:00:00');
    $deadline = new DateTimeImmutable('2026-06-30 18:00:00');

    $periodo = new PeriodoAtividade($dataEntrada, $deadline);

    expect($periodo->dataEntrada)->toBe($dataEntrada)
        ->and($periodo->deadline)->toBe($deadline)
        ->and($periodo->dataTermino)->toBeNull();
});

it('deve lançar exceção se o deadline for anterior à data de entrada', function () {
    $dataEntrada = new DateTimeImmutable('2026-06-26 10:00:00');
    $deadlineInvalido = new DateTimeImmutable('2026-06-25 18:00:00');

    expect(fn () => new PeriodoAtividade($dataEntrada, $deadlineInvalido))
        ->toThrow(InvalidArgumentException::class, 'O prazo (deadline) não pode ser anterior à data de entrada.');
});

it('deve permitir concluir uma atividade com sucesso', function () {
    $periodoInicial = new PeriodoAtividade(
        new DateTimeImmutable('2026-06-26 10:00:00'),
        new DateTimeImmutable('2026-06-30 18:00:00')
    );
    $dataTermino = new DateTimeImmutable('2026-06-28 14:00:00');

    $novoPeriodo = $periodoInicial->concluir($dataTermino);

    expect($novoPeriodo->dataTermino)->toBe($dataTermino)
        ->and($novoPeriodo->dataEntrada)->toBe($periodoInicial->dataEntrada);
});

it('deve detetar se a atividade está atrasada', function () {
    $deadline = new DateTimeImmutable('2026-06-28 18:00:00');
    $periodo = new PeriodoAtividade(
        new DateTimeImmutable('2026-06-26 10:00:00'),
        $deadline
    );

    $dataVerificacaoAtrasada = new DateTimeImmutable('2026-06-29 09:00:00');

    expect($periodo->estaAtrasado($dataVerificacaoAtrasada))->toBeTrue();
});