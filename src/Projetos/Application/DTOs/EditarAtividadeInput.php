<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

use DateTimeImmutable;

final readonly class EditarAtividadeInput
{
    public function __construct(
        public string $projetoId,
        public string $atividadeId,
        public string $descricao,
        public string $tipo,
        public DateTimeImmutable $dataEntrada,
        public DateTimeImmutable $dataLimite,
        public string $accountManagerId,
        public string $preVendasId,
        public ?DateTimeImmutable $dataInicio = null,
        public ?DateTimeImmutable $dataTermino = null,
        public ?string $observacao = null,
        public ?string $usuarioExecutorId = null,
    ) {}
}
