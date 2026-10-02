<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

final readonly class AlterarClienteDoProjetoInput
{
    public function __construct(
        public string $projetoId,
        public string $clienteId,
        public ?string $usuarioExecutorId = null,
    ) {}
}
