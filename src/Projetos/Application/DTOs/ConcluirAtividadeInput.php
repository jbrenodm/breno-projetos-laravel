<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

use DateTimeImmutable;

final readonly class ConcluirAtividadeInput
{
    public function __construct(
        public string $projetoId,
        public string $atividadeId,
        public DateTimeImmutable $dataTermino
    ) {}
}