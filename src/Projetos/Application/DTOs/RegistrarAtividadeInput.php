<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

use DateTimeImmutable;

final readonly class RegistrarAtividadeInput
{
    public function __construct(
        public string $projetoId,
        public string $descricao,
        public string $tipo,
        public string $status,
        public DateTimeImmutable $dataEntrada,
        public DateTimeImmutable $dataLimite,
        public ?DateTimeImmutable $dataInicio = null,
        public ?DateTimeImmutable $dataTermino = null,
        public ?string $accountManagerId = null,
        public ?string $preVendasId = null,
        public ?string $observacao = null,
        public ?string $usuarioExecutorId = null,
    ) {}
}
