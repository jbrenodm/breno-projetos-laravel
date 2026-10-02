<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class RedefinirSenhaTemporariaInput
{
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $usuarioId,
        public string $senhaTemporaria,
    ) {}
}
