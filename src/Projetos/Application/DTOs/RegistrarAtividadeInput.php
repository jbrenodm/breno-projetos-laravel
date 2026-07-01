<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

use DateTimeImmutable;

final readonly class RegistrarAtividadeInput
{
    public function __construct(
        public string $projetoId,
        public string $descricao,
        public string $statusAtividade,
        public DateTimeImmutable $dataEntrada,
        public DateTimeImmutable $deadline,
        public ?string $accountManagerId = null,
        public ?string $preVendasId = null
    ) {}
}