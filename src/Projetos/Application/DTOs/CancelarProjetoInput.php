<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

final readonly class CancelarProjetoInput
{
    public function __construct(
        public string $projetoId,
        public ?string $usuarioExecutorId = null,
    ) {}
}
