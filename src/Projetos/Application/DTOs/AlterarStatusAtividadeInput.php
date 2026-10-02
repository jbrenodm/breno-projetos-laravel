<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

use DateTimeImmutable;

final readonly class AlterarStatusAtividadeInput
{
    /**
     * @param  ?DateTimeImmutable  $data  data de início (Em Andamento) ou término (Concluída); nula = hoje
     */
    public function __construct(
        public string $projetoId,
        public string $atividadeId,
        public string $novoStatus,
        public ?DateTimeImmutable $data = null,
        public ?string $usuarioExecutorId = null,
    ) {}
}
