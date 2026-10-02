<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class AlterarSituacaoUsuarioInput
{
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $usuarioId,
        public bool $ativo,
    ) {}
}
