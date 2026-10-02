<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class RenomearPapelInput
{
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $papel,
        public string $nome,
        public ?string $sigla = null,
    ) {}
}
