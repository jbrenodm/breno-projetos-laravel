<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\DTOs;

final readonly class AlterarSituacaoResponsavelInput
{
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $responsavelId,
        public bool $ativo,
    ) {}
}
