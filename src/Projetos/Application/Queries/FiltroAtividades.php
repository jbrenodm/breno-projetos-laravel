<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Queries;

use DateTimeImmutable;

/** Filtros e ordenação da listagem de todas as atividades. Nulo = sem filtro. Padrão: data de entrada, mais recentes primeiro. */
final readonly class FiltroAtividades
{
    public function __construct(
        public ?string $status = null,
        public ?string $busca = null,
        public ?string $clienteId = null,
        public ?string $tipo = null,
        public ?string $accountManagerId = null,
        public ?string $preVendasId = null,
        public ?string $fornecedorId = null,
        public bool $somenteAtrasadas = false,
        public ?DateTimeImmutable $entradaDe = null,
        public ?DateTimeImmutable $entradaAte = null,
        public OrdenacaoAtividades $ordenarPor = OrdenacaoAtividades::DATA_ENTRADA,
        public bool $decrescente = true,
    ) {}
}
